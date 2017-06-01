//"use strict";
var online = 0;
var db_ver = "4.3";
var usersCheck, timerCheck, processQueue, pageLoader;

var syncClients = 0,
    syncUsers = 0,
    syncContracts = 0,
    syncReports = 0,
    syncObs = 0,
    actionsOpen = [];

function addslashes(string) {
    return string.replace(/\\/g, '\\\\').
        replace(/\u0008/g, '\\b').
        //replace(/\t/g, '\\t').
        //replace(/\n/g, '\\n').
        //replace(/\f/g, '\\f').
        //replace(/\r/g, '\\r').
        replace(/'/g, "\\'").
        replace(/"/g, '\\"');
}

var app = {
    initialize: function () {
        this.bindEvents();
    },
    bindEvents: function () {
        document.addEventListener('deviceready', this.onDeviceReady, false);
        document.addEventListener('online', this.onOnline, false);
        document.addEventListener('offline', this.onOffline, false);
    },
    onDeviceReady: function () {
        console.log(navigator.connection.type);
        FastClick.attach(document.body);
        /*cordova.plugins.printer.isAvailable(
            function (isAvailable) {
                //console.log(isAvailable ? 'Service is available' : 'Service NOT available');
            }
        );*/

        //var obsArray = [];
        if (window.localStorage.getItem("db_ver") === null) {
            window.localStorage.db_ver = "1.0";
        }
        pageLoader = setInterval( function() {
            console.log("trying to load page");
            renderPage();
        }, 1 * 200);

        $("#queue").fadeOut(0);

        //renderPage();
    },
    onOnline: function () {
        //console.log("Doing sync...");
        $("#offline").slideUp(1000);
        $("#online").slideDown(1000);
        //$("#queue").slideUp(1000);
        //processQueue = setTimeout( function() {
            process_queue();
        //}, 60 * 1000);



        online = 1;
        //console.log(online);
    },
    onOffline: function () {
        //console.log("Working offline");
        $("#online").slideUp(1000);
        $("#offline").slideDown(1000);
        $("#queue").fadeOut(500);
        //clearTimeout( processQueue );
        online = 0;


    }
};

function process_queue() {

    var db = open_db();
    var reportID;
    var items = 0;
    db.transaction(
        function(tx) {
            tx.executeSql("SELECT * FROM queue ORDER BY queueAdded ASC", [], function(tx, rs) {
                console.log("In processing");
                if ( rs.rows.length > 0 ) {
                    items = rs.rows.length;
                    console.log(items);
                    $("#queue span.count").html(items);
                    $("#queue").fadeIn(500);
                } else {
                    console.log(items);
                    $("#queue").fadeOut(500);
                }
                for (i = 0; i < rs.rows.length; i += 1) {
                    reportID = rs.rows.item(i).queueReport;
                    if( rs.rows.item(i).queueReport != null ) {
                        console.log("processing report: " + reportID);
                        var reportQuery = 'SELECT * FROM report LEFT JOIN client ON clientID = reportClient LEFT JOIN contract ON contractID = reportContract INNER JOIN user ON reportUser = userID WHERE reportID="'+reportID+'"';

                        tx.executeSql(reportQuery, [], function(tx, rs2) {
                            $.post('http://swsreports.com/ajax/add-report-new.php', rs2.rows.item(0), function(response) {}, 'json');
                            var obsQuery = 'SELECT * FROM obs WHERE obsReport="'+reportID+'"';
                            tx.executeSql(obsQuery, [], function(tx, rs3) {
                                for (x = 0; x < rs3.rows.length; x += 1) {
                                    $.post('http://swsreports.com/ajax/add-obs.php', rs3.rows.item(x), function(response) {}, 'json');
                                    var imageQuery = 'SELECT * FROM image WHERE imageObs="'+rs3.rows.item(x).obsID+'"';
                                    tx.executeSql(imageQuery, [], function(tx, rs4) {
                                        for (y = 0; y < rs4.rows.length; y += 1) {
                                            $.post('http://swsreports.com/ajax/add-image.php', rs4.rows.item(y), function(response) {}, 'json');
                                        }
                                    });
                                }

                                if( x === rs3.rows.length ) {
                                    setTimeout(function() {
                                        ajax_notify(reportID);
                                        items = items - 1;
                                        $("#queue span.count").html(items);
                                        if(items == 0) {
                                            $("#queue").fadeOut(500);
                                        }
                                    }, 5000);

                                }
                            });

                        });
                    }
                    // Delete from Queue
                    var delQuery = 'DELETE FROM queue WHERE queueReport="'+ reportID +'"';
                    tx.executeSql(delQuery, [], function() {});

                }
            });
        }
    );
}

function ajax_insert_new_report( report ) {

    var data = {
        report: report
        //obs: obs
    };

    $.post('http://swsreports.com/ajax/add-report-new.php', data, function (response) {
        //console.log(response);
    }, 'json');
}

function ajax_insert_obs(reportID, obsID, obsItem, obsObs, obsPriority, obsName) {
    var data = {
        obsReport: reportID,
        obsID: obsID,
        obsItem: obsItem,
        obsObs: obsObs,
        obsPriority: obsPriority,
        obsName: obsName
    };
    $.post('http://swsreports.com/ajax/add-obs.php', data, function (data) {

    }, 'json');
}

function ajax_insert_report(reportID, reportClient, reportWork, reportContract, reportDate, reportTime, reportTick, reportUser, reportAction, reportAvail, reportClientSig) {

    var data = {
        reportID: reportID,
        reportClient: reportClient,
        reportWork: reportWork,
        reportContract: reportContract,
        reportDate: reportDate,
        reportTime: reportTime,
        reportTick: reportTick,
        reportUser: reportUser,
        reportPrevAvail: reportAvail,
        reportPrevAction: reportAction,
        reportClientSig: reportClientSig
    };
    //console.log ( data );
    $.post('http://swsreports.com/ajax/add-report.php', data, function (data) {

    }, 'json');
}

function ajax_insert_image(imageID, obsID, imageData) {
    var data = {
        imageID: imageID,
        imageObs: obsID,
        imageData: imageData
    };
    //console.log ( 'attempted upload of image '+imageID );
    $.post('http://swsreports.com/ajax/add-image.php', data, function (data) {

    }, 'json');
}

function ajax_notify(report) {
    var data = {
        reportID: report
    };
    console.log("notify in 2 seconds");
    setTimeout(
        function() {
            console.log("notify now");
            $.post('http://swsreports.com/ajax/notify.php', data, function (data) {
            }, 'json');
        },
        2*1000
    );

}

function open_db() {

    try {

        var shortName = 'sws_db';
        var displayName = 'SWS Database';
        var maxSize = 49*1024*1024; // in bytes
        var db = window.sqlitePlugin.openDatabase({ name: 'sws_db.db', location: 'default' });
        //var db = window.sqlitePlugin.openDatabase(shortName, "", displayName, maxSize);
        //console.log(db);
        return db;

    } catch (e) {
        // Error handling code goes here.
        if (e == INVALID_STATE_ERR) {
            // Version number mismatch.
            alert("Invalid database version.");
        } else {
            alert("Unknown error " + e + ".");
        }
        return;
    }
}

function generateUUID() {
    var d = new Date().getTime();
    var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        var r = (d + Math.random() * 16) % 16 | 0;
        d = Math.floor(d / 16);
        return (c == 'x' ? r : (r & 0x7 | 0x8)).toString(16);
    });
    return uuid;
}

function errorCB(err) {
    //console.log("Error processing SQL: ");
    //console.log(err);
}

function successCB() {
    //console.log("Databases created!");
}


function populateDB(tx) {
    // Drop tables - remove in production

        tx.executeSql('DROP TABLE IF EXISTS client');
        tx.executeSql('DROP TABLE IF EXISTS obs');
        tx.executeSql('DROP TABLE IF EXISTS report');
        tx.executeSql('DROP TABLE IF EXISTS user');
        tx.executeSql('DROP TABLE IF EXISTS contact');
        tx.executeSql('DROP TABLE IF EXISTS contract');
        tx.executeSql('DROP TABLE IF EXISTS image');


        // Create tables
        tx.executeSql('CREATE TABLE IF NOT EXISTS client (clientID TEXT PRIMARY KEY, clientName TEXT, clientEmail TEXT, clientLogo TEXT, clientActive INTEGER DEFAULT "1", clientCreated TEXT DEFAULT CURRENT_TIMESTAMP, clientUpdated TEXT DEFAULT CURRENT_TIMESTAMP)');

        tx.executeSql('CREATE TABLE IF NOT EXISTS obs (obsID TEXT PRIMARY KEY, obsReport TEXT, obsItem TEXT, obsObs TEXT, obsPriority TEXT, obsMedia TEXT, obsName TEXT, obsCreated TEXT DEFAULT CURRENT_TIMESTAMP, obsUpdated TEXT DEFAULT CURRENT_TIMESTAMP )');

        tx.executeSql('CREATE TABLE IF NOT EXISTS report (reportID TEXT PRIMARY KEY, reportClient TEXT, reportWork TEXT, reportContract TEXT, reportDate TEXT, reportTime TEXT, reportTick TEXT, reportUser TEXT, reportPrevAvail TEXT, reportPrevAction TEXT, reportComments TEXT, reportSigReq INTEGER DEFAULT 1, reportClientSig TEXT, reportUserSig TEXT, reportDeleted INTEGER DEFAULT "0", reportCreated TEXT DEFAULT CURRENT_TIMESTAMP, reportUpdated TEXT DEFAULT CURRENT_TIMESTAMP, actionsOpen TEXT)');

        tx.executeSql('CREATE TABLE IF NOT EXISTS user (userID TEXT PRIMARY KEY, userName TEXT, userEmail TEXT, userPass TEXT, userActive INTEGER DEFAULT "1", userType INTEGER DEFAULT "0", userCreated TEXT DEFAULT CURRENT_TIMESTAMP, userUpdated TEXT DEFAULT CURRENT_TIMESTAMP)');

        tx.executeSql('CREATE TABLE IF NOT EXISTS contact (contactID TEXT PRIMARY KEY, contactName TEXT, contactClient TEXT, contactEmail TEXT, contactTel TEXT, contactActive INTEGER DEFAULT "1", contactCreated TEXT DEFAULT CURRENT_TIMESTAMP, contactUpdated TEXT DEFAULT CURRENT_TIMESTAMP)');

        tx.executeSql('CREATE TABLE IF NOT EXISTS contract (contractID TEXT PRIMARY KEY, contractClient TEXT, contractNumber TEXT, contractLocation TEXT, contractActive INTEGER DEFAULT "1", contractCreated TEXT DEFAULT CURRENT_TIMESTAMP, contractUpdated TEXT DEFAULT CURRENT_TIMESTAMP)');

        tx.executeSql('CREATE TABLE IF NOT EXISTS image (imageID TEXT PRIMARY KEY, imageObs TEXT, imageData TEXT )');

        tx.executeSql('CREATE TABLE IF NOT EXISTS queue (queueReport TEXT PRIMARY KEY, queueAdded TEXT DEFAULT CURRENT_TIMESTAMP )');

        tx.executeSql('CREATE TRIGGER userUpdate AFTER UPDATE OF userID, userEmail, userPass, userActive, userType ON user FOR EACH ROW BEGIN UPDATE user SET userUpdated = datetime() WHERE userID = old.userID; END;');

        tx.executeSql('CREATE TRIGGER clientUpdate AFTER UPDATE OF clientID, clientName, clientContract, clientWork, clientLogo, clientActive ON client FOR EACH ROW BEGIN UPDATE client SET clientUpdated=datetime() WHERE clientID=OLD.clientID; END;');

        tx.executeSql('CREATE TRIGGER obsUpdate AFTER UPDATE OF obsID, obsReport, obsItem, obsObs, obsPriority, obsMedia ON obs FOR EACH ROW BEGIN UPDATE obs SET obsUpdated=datetime() WHERE obsID=OLD.obsID; END;');

        tx.executeSql('CREATE TRIGGER reportUpdate AFTER UPDATE OF reportID, reportClient, reportWork, reportContract, reportDate, reportTime, reportTick, reportUser, reportClientSig, reportUserSig ON report FOR EACH ROW BEGIN UPDATE report SET reportUpdated=datetime() WHERE reportID=OLD.reportID; END;');

}

function get_users() {
    $('.users-sync .rem-icon').remove();
    // TODO: Add a check on last updated column
    $('.users-sync').append('<i class="fa fa-circle rem-icon fa-stack-2x"></i><i class="fa fa-circle-o-notch rem-icon fa-spin fa-stack-1x fa-inverse"></i>');
    $.get('http://swsreports.com/ajax/get-users.php', {
        "_": $.now()
    }, function (data) {

        var db = open_db();
        var count = 0;
        db.transaction(
            function (tx) {
                $.each(data, function (i, item) {
                    var query = 'INSERT OR REPLACE INTO user ( userID, userName, userEmail, userPass, userActive, userType, userCreated ) VALUES ( "' + data[i].userID + '", "' + data[i].userName + '", "' + data[i].userEmail + '", "' + data[i].userPass + '", "' + data[i].userActive + '", "' + data[i].userType + '", "' + data[i].userCreated + '" )';

                            tx.executeSql(query, [], function (tx, rs) {
                                count += 1;
                            });
                });
            }, function(error) {
                console.log('users transaction failed');
                console.log(error)
                $('.users-sync .rem-icon').remove();
                $('.users-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-times red-fa fa-stack-1x fa-inverse"></i>');
            }, function() {
                console.log("Inserted "+count+" users");
                $("#loginusers").html('There are currently '+count+' users in the database');
                $('.users-sync .rem-icon').remove();
                $('.users-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-check green-fa fa-stack-1x fa-inverse"></i>');
                syncUsers = 1
            }
        ); //end db.transaction


    }, 'json').fail(function () {
        $('.users-sync .rem-icon').remove();
        $('.users-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-times red-fa fa-stack-1x fa-inverse"></i>');
    });
}

function get_clients() {
    $('.clients-sync .rem-icon').remove();
    $('.clients-sync').append('<i class="fa fa-circle rem-icon fa-stack-2x"></i><i class="fa fa-circle-o-notch rem-icon fa-spin fa-stack-1x fa-inverse"></i>');
    // TODO: Add a check on last updated column
    //console.log('getting clients...');
    $.get('http://swsreports.com/ajax/get-clients.php', {
        "_": $.now()
    }, function (data) {
        var db = open_db();
        db.transaction(
            function (tx) {
                $.each(data, function (i, item) {
                    var query = 'INSERT OR REPLACE INTO client ( clientID, clientName, clientEmail, clientActive, clientCreated ) VALUES ( "' + data[i].clientID + '", "' + data[i].clientName + '", "' + data[i].clientEmail + '", "' + data[i].clientActive + '", "' + data[i].clientCreated + '" )';
                    tx.executeSql(query, [], function (tx, rs) {
                        //console.log('Inserted client: ' + data[i].clientName);
                    });
                });
            }, function(error) {
                console.log('clients transaction failed');
                $('.clients-sync .rem-icon').remove();
                $('.clients-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-times red-fa fa-stack-1x fa-inverse"></i>');
            }, function() {
                console.log('clients ok');
                $('.clients-sync .rem-icon').remove();
                $('.clients-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-check green-fa fa-stack-1x fa-inverse"></i>');
                syncClients = 1
            }
        ); //end db

    }, 'json').fail(function () {
        $('.clients-sync .rem-icon').remove();
        $('.clients-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-times red-fa fa-stack-1x fa-inverse"></i>');
    });
}

function get_reports() {
    $('.reports-sync .rem-icon').remove();
    $('.reports-sync').append('<i class="fa fa-circle rem-icon fa-stack-2x"></i><i class="fa fa-circle-o-notch rem-icon fa-spin fa-stack-1x fa-inverse"></i>');
    // TODO: Add a check on last updated column
    //console.log('getting reports...');
    $.get('http://swsreports.com/ajax/get-reports.php', {
        "_": $.now()
    }, function (data) {
        var db = open_db();
        db.transaction(
            function (tx) {
                $.each(data, function (i, item) {
                    //var query = "INSERT OR REPLACE INTO report ( reportID, reportClient, reportWork, reportContract, reportDate, reportTime, reportTick, reportUser, reportClientSig, reportUserSig, reportComments, reportPrevAction, reportPrevAvail, reportDeleted, reportCreated ) VALUES ( '" + data[i].reportID + "', '" + data[i].reportClient + "', \"" + data[i].reportWork + "\", '" + data[i].reportContract + "', '" + data[i].reportDate + "', '" + data[i].reportTime + "', '" + data[i].reportTick + "', '" + data[i].reportUser + "', '" + data[i].reportClientSig + "', '" + data[i].reportUserSig + "', '" + addslashes ( data[i].reportComments )+ "', '" + data[i].reportPrevAction + "', '" + data[i].reportPrevAvail + "', '" + data[i].reportDeleted + "', '" + data[i].reportCreated + "')";
                        var query = "INSERT OR REPLACE INTO report ( reportID, reportClient, reportWork, reportContract, reportDate, reportTime, reportTick, reportUser, reportClientSig, reportUserSig, reportComments, reportPrevAction, reportPrevAvail, reportDeleted, reportCreated ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";
                        tx.executeSql(query, [ data[i].reportID, data[i].reportClient, data[i].reportWork, data[i].reportContract, data[i].reportDate, data[i].reportTime, data[i].reportTick, data[i].reportUser, data[i].reportClientSig, data[i].reportUserSig, data[i].reportComments, data[i].reportPrevAction, data[i].reportPrevAvail, data[i].reportDeleted, data[i].reportCreated ], function (tx, rs) {
                            console.log('Inserted report: ' + data[i].reportID);
                        }, function(error) {
                            console.log('reports transaction failed' + JSON.stringify(error));
                            console.log('Failed: '+data[i].reportID);
                            console.log( data[i].reportComments );
                            console.log( addslashes ( data[i].reportComments ) );
                        });
                });
            }, function(error) {
                console.log('reports transaction failed' + JSON.stringify(error));
                $('.reports-sync .rem-icon').remove();
                $('.reports-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-times red-fa fa-stack-1x fa-inverse"></i>');
            }, function() {
                console.log('reports transaction ok');
                $('.reports-sync .rem-icon').remove();
                $('.reports-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-check green-fa fa-stack-1x fa-inverse"></i>');
                syncReports = 1
            }
        );
        //console.log('getting obs...');


        //send_data();

    }, 'json').fail(function (xhr, ajaxOptions, thrownError) {
        $('.reports-sync .rem-icon').remove();
        $('.reports-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-times red-fa fa-stack-1x fa-inverse"></i>');
    });
}

function get_obs() {
    $('.obs-sync .rem-icon').remove();
    $('.obs-sync').append('<i class="fa fa-circle rem-icon fa-stack-2x"></i><i class="fa fa-circle-o-notch rem-icon fa-spin fa-stack-1x fa-inverse"></i>');
    $.get('http://swsreports.com/ajax/get-obs.php', {
            "_": $.now()
        }, function (data) {
            var db = open_db();
            db.transaction(
                function (tx) {
                    $.each(data, function (i, item) {
                        var query = "INSERT OR REPLACE INTO obs ( obsID, obsReport, obsItem, obsObs, obsPriority, obsMedia, obsCreated ) VALUES (?,?,?,?,?,?,?)";
                        tx.executeSql(query, [data[i].obsID, data[i].obsReport, data[i].obsItem, data[i].obsObs, data[i].obsPriority, data[i].obsMedia, data[i].obsCreated], function (tx, rs) {
                        });
                    });
                }, function(error) {
                    console.log('obs transaction failed' + error );
                    $('.obs-sync .rem-icon').remove();
                    $('.obs-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-times red-fa fa-stack-1x fa-inverse"></i>');
                }, function() {
                    console.log('obs transaction ok');
                    $('.obs-sync .rem-icon').remove();
                    $('.obs-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-check green-fa fa-stack-1x fa-inverse"></i>');
                    syncObs = 1
                }
            );
        }, 'json').fail(function (xhr, ajaxOptions, thrownError) {
            $('.obs-sync .rem-icon').remove();
            $('.obs-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-times red-fa fa-stack-1x fa-inverse"></i>');
        }
    );

}

function get_contracts() {
    $('.contracts-sync .rem-icon').remove();
    $('.contracts-sync').append('<i class="fa fa-circle rem-icon fa-stack-2x"></i><i class="fa fa-circle-o-notch rem-icon fa-spin fa-stack-1x fa-inverse"></i>');
    // TODO: Add a check on last updated column
    //console.log('getting contracts...');
    $.get('http://swsreports.com/ajax/get-contracts.php', {
        "_": $.now()
    }, function (data) {
        var db = open_db();
        db.transaction(
            function (tx) {
                $.each(data, function (i, item) {

                    var query = 'INSERT OR REPLACE INTO contract ( contractID, contractClient, contractNumber, contractLocation, contractActive, contractCreated, contractUpdated ) VALUES ( "' + data[i].contractID + '", "' + data[i].contractClient + '", "' + data[i].contractNumber + '", "' + addslashes ( data[i].contractLocation ) + '", "' + data[i].contractActive + '", "' + data[i].contractCreated + '", "' + data[i].contractUpdated + '" )';
                    tx.executeSql(query, [], function (tx, rs) {
                        //console.log('Inserted contract: ' + data[i].contractID);
                    });
                });
            }, function(error) {
                console.log('contracts transaction failed' + JSON.stringify(error));
                $('.contracts-sync .rem-icon').remove();
                $('.contracts-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-times red-fa fa-stack-1x fa-inverse"></i>');
            }, function() {
                console.log('contracts ok');
                $('.contracts-sync .rem-icon').remove();
                $('.contracts-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-check green-fa fa-stack-1x fa-inverse"></i>');
                syncContracts = 1;
            }
        );
    }, 'json').fail(function (xhr, ajaxOptions, thrownError) {
        $('.contracts-sync .rem-icon').remove();
        $('.contracts-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-times red-fa fa-stack-1x fa-inverse"></i>');
    });
}

function send_data() {
    var queries = [
        { 'query': 'SELECT * FROM obs', 'url': 'add-obs' },
        { 'query': 'SELECT * FROM report', 'url': 'add-report-new' },
        { 'query': 'SELECT * FROM image', 'url': 'add-image' }
    ];
    $('.upload-sync .rem-icon').remove();
    $('.upload-sync').append('<i class="fa fa-circle rem-icon fa-stack-2x"></i><i class="fa fa-circle-o-notch rem-icon fa-spin fa-stack-1x fa-inverse"></i>');
    var db = open_db();
        db.transaction(
            function(tx) {
                $.each(queries, function(index, item) {
                    tx.executeSql(item.query, [], function(tx, rs) {
                        var i;
                        for (i = 0; i < rs.rows.length; i += 1) {
                            //console.log(rs.rows.item(i))
                            var data = rs.rows.item(i);
                            console.log($(data).serialize());
                            $.post('http://swsreports.com/ajax/'+item.url+'.php', data, function(response) {
                                //console.log(response.status);
                                if (response.status == 0) {
                                    alert( "error status 0 "+item.url );
                                    $("#sync-page").after(data);
                                } else {
                                    //console.log(data);
                                    //$("#sync-page").after(JSON.stringify(data));
                                }
                            }, 'json')
                            .fail(function(err) {
                                //alert( "error fail "+item.url );
                                $("#sync-page").after(JSON.stringify(data));
                                //alert(data);
                                //alert(JSON.stringify(err));
                              });
                        }
                    });
                });
            }, function(error) {
                console.log('send transaction failed' + JSON.stringify(error));
                $('.upload-sync .rem-icon').remove();
                $('.upload-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-times red-fa fa-stack-1x fa-inverse"></i>');
            }, function() {
                console.log('send data ok');
                $('.upload-sync .rem-icon').remove();
                $('.upload-sync').append('<i class="fa fa-circle rem-icon fa-faded fa-stack-2x"></i><i class="fa rem-icon fa-check green-fa fa-stack-1x fa-inverse"></i>');
            }
        );
}

function askSync() {
    navigator.notification.confirm('The tables have been cleared. Do you want to re-sync data now?', function (index) {
        if (index === 1) {
            var title = $('a.syncpage').attr('title');
            $('a.dashboard').removeClass('active');
            $('a.syncpage').addClass('active');
            $("#main").load('sync.html', function () {
                $('.navbar-fixed-top .navbar-brand').html('Sync Data');
                $('.sync').trigger('click');
            });
        } else {
            var title = $('a.dashboard').attr('title');
            $("#main").load('dashboard.html', function () {
                $('.navbar-fixed-top .navbar-brand').html(title);
            });
        }
    }, 'Sync Data?', ['Yes', 'No']);
}
$('.logindebug').click(function() {
    $('.login-modal').modal('hide');
});
function login(user, pass) {
    if (online === 1) {
        get_users();
    }
    var db = open_db();

    db.transaction(
        function (tx) {
            tx.executeSql("SELECT userID, userEmail, userPass FROM user WHERE userEmail='" + user.trim() + "'", [], function (tx, rs) {
                if ( rs.rows.length > 0 && ( rs.rows.item(0).userPass == pass.trim() ) ) {
                    window.localStorage.userID = rs.rows.item(0).userID;
                    clearInterval(usersCheck);
                    $('.login-modal').modal('hide');
                    if (online === 1) {
                        //askSync();
                        var title = $('a.dashboard').attr('title');
                        $("#main").load('dashboard.html', function () {
                            $('.navbar-fixed-top .navbar-brand').html(title);
                        });
                    }
                } else {
                    navigator.notification.alert(
                        '\nYour login has failed\n\nPlease check your email address and password and try again.\n\nError code: 2',
                        function () {},
                        'Login failed',
                        'Retry'
                    );
                }
            }, function(error) {
                console.log(error);
                navigator.notification.alert(
                    '\nYour login has failed\n\nPlease check your email address and password and try again.\n\nError code: 1\n\n'+error.message,
                    function () {},
                    'Login failed',
                    'Retry'
                );
            });
        },
        function (error) {
            console.log(error);
            navigator.notification.alert(
                '\nYour login has failed\n\nPlease check your email address and password and try again.\n\nError code: 3\n\n'+error.message,
                function () {},
                'Login failed',
                'Retry'
            );
        }
    );
}



function renderPage() {

    //console.log(window.localStorage.db_ver);
    //console.log( db_ver);
    if ( window.localStorage.db_ver != db_ver ) {
        console.log(window.localStorage.db_ver);
        console.log( db_ver);
        console.log("error here");
        localStorage.db_ver = db_ver;
        var db = open_db();
        db.transaction(populateDB,
            function(error) {
                navigator.notification.alert(
                    'There was an error creating the tables\n\n'+error.message,
                    function () {},
                    'Error',
                    'OK'
                );
        }, function() {
                // navigator.notification.alert(
                //     'The tables were created successfully',
                //     function () {},
                //     'Success',
                //     'OK'
                // );
                askSync();
        });
    }
    //window.localStorage.userID = 1;
    if(window.localStorage.userID == undefined)  {
        get_users();

        $('.login-modal').modal({
          backdrop: 'static',
          keyboard: false,
          show: true
        });
    } else {
        //if (online === 1) {
            //askSync();
            get_clients();
            get_contracts();
            var title = $('a.dashboard').attr('title');
            $("#main").load('dashboard.html', function () {
                $('.navbar-fixed-top .navbar-brand').html(title);
            });
        //}
    }

    clearInterval( pageLoader );

    var page;

    var loadtest = 0;
    //var obsArray = [];
    $("a[target='_system']").click(function (event) {
        event.preventDefault();
        cordova.InAppBrowser.open($(this).attr("href"), '_system');
    });

    $(document)
        .ready(function (e) {
            var prevpage = 'dashboard.html';
            var currpage = 'dashboard.html';
            sigCapture = new SignatureCapture("signature");
            sigCapture2 = new SignatureCapture("signature2");
            $("#ajaxdata").remove();
            $('.datepicker').datepicker({
                format: 'dd/mm/yyyy',
                todayBtn: "linked",
                clearBtn: true,
                autoclose: true
            });

            /*get_users();
            get_clients();
            get_reports();
            get_contracts();*/
            if (online === 1) {

            } else {
                var title = $('a.dashboard').attr('title');
                $("#main").load('dashboard.html', function () {
                    $('.navbar-fixed-top .navbar-brand').html(title);
                });
            }

            //if( online == 1 ) {

            //}
        })
        .on( "click", '#time', function(e) {

            var timeField = $(this);

            var options = {
                date: new Date(),
                mode: 'time'
            };
            function onSuccess(date) {
                //alert('Selected date: ' + date.getHours() + ":" + date.getMinutes() + ":" + date.getSeconds());
                timeField.val( ('0' + date.getHours()).slice(-2) + ":" + ('0' + date.getMinutes()).slice(-2) );
            }
            Keyboard.hide();
            datePicker.show(options, onSuccess);

        })
        .on( "click", '#date', function(e) {

            var dateField = $(this);

            var options = {
                date: new Date(),
                mode: 'date'
            };
            function onSuccess(date) {
                //alert('Selected date: ' + ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear());
                dateField.val( ('0' + date.getDate()).slice(-2) + '/' + ('0' + (date.getMonth() + 1)).slice(-2) + '/' + date.getFullYear() );
            }
            Keyboard.hide();
            datePicker.show(options, onSuccess);

        })
        .on( "click", '#submitFilter', function(e) {
            e.preventDefault();
            var start = moment( $('#filter_start_date').val(), 'DD-MM-YYYY' );
            start = start.format('YYYY-MM-DD');
            var end = moment( $('#filter_end_date').val(), 'DD-MM-YYYY' );
            end = end.format('YYYY-MM-DD');

            var reportQ = "SELECT userName, clientName, reportDate, reportTime, reportID, COUNT (obsItem) AS obss FROM report INNER JOIN client ON clientID = reportClient LEFT JOIN obs ON reportID = obsReport INNER JOIN user ON userID = reportUser WHERE reportDeleted='0' AND reportDate BETWEEN '"+start+"' AND '"+end+"'";
            var client = $('#clientID').val();
            if( client != '-1' ) {
                reportQ = reportQ + " AND clientID='"+client+"'";
            }
            reportQ = reportQ + " GROUP BY reportID ORDER BY reportDate DESC, reportTime DESC";
            //console.log( reportQ );
            var db = open_db();
            db.transaction(
                function (tx) {
                    var output = '';
                    tx.executeSql(reportQ, [], function (tx, rs) {
                        var i;
                        for (i = 0; i < rs.rows.length; i += 1 ) {
                            var obsOut = rs.rows.item(i).obss + ' Observations';
                            output += '<a href="report-detail.html" data-loc="report-detail" class="list-group-item view-report" rel="' + rs.rows.item(i).reportID + '"><h4 class="list-group-item-heading">' + rs.rows.item(i).clientName + '</h4><i class="pull-right glyphicon glyphicon-chevron-right"></i><p class="list-group-item-text">Produced ' + rs.rows.item(i).reportDate + ' at ' + rs.rows.item(i).reportTime + ' by ' + rs.rows.item(i).userName + ' - ' + obsOut + '</p></a>';
                        }
                        if( rs.rows.length === 0 ) {
                           output += '<a href="#" class="list-group-item "><h4 class="list-group-item-heading">No Reports Found</h4><i class="pull-right glyphicon glyphicon-chevron-right"></i><p class="list-group-item-text">Please amend filters and try again</p></a>';
                        }
                        $("#reportList").html(output);
                    }, function() {});
                }, function() {});
        })
        .on( "submit", '#filterForm', function(e) {
            e.preventDefault();
            return false;
        })
        .on("click", ".sync", function (event) {
            event.preventDefault();
            $('.rem-icon').remove();
            get_users();
            get_clients();
            get_reports();
            get_contracts();
            get_obs();

            var checkDownloads = setInterval( function() {
                if( syncClients + syncContracts + syncObs + syncReports + syncUsers == 5 ) {
                    send_data();
                    syncClients = 0,
                    syncUsers = 0,
                    syncContracts = 0,
                    syncReports = 0,
                    syncObs = 0;
                    clearInterval(checkDownloads);
                }
            }, 1000);

        })
                .on("click", ".syncup", function (event) {
                    event.preventDefault();
                    $('.rem-icon').remove();
                    send_data();

                    })
        .on("click", ".clearsig", function (event) {
            event.preventDefault();
            sigCapture.clear();
        })
        .on("click", ".clearsig2", function (event) {
            event.preventDefault();
            sigCapture2.clear();
        })
        .on("click", ".logout", function (event) {
            event.preventDefault();
            navigator.notification.confirm(
                'Are you sure you want to logout?', // message
                function (buttonIndex) {

                    if (buttonIndex === 1) {
                        localStorage.clear();
                        localStorage.db_ver = db_ver;
                        get_users();
                        $('.login-modal').modal({
                            backdrop: 'static',
                            keyboard: false,
                            show: true
                        });
                        usersCheck = setInterval( function() {
                                var db = open_db();
                                db.transaction( function(tx) {
                                        tx.executeSql("SELECT * FROM user", [], function(tx, rs) {
                                            $("#loginusers").html('There are '+rs.rows.length+' users in the database');
                                        });
                                    }, function(err) {
                                        console.log(err);
                                    }
                                );
                            }, 1000);
                    }
                }, // callback to invoke with index of button pressed
                'Confirmation', // title
            ['Logout', 'Cancel'] // buttonLabels
            );

        })
        .on("submit", ".login-modal form", function (event) {
            event.preventDefault();

            var user = $('#usernamelogin').val();
            var pass = $('#passwordlogin').val();

            login(user, pass);
        })
        .on("click", ".topbarhome", function(e) {
            e.preventDefault();

            var thislink = $(this);
            var link = thislink.attr('href');
            var title = thislink.attr('title');

            if( page == 'new-report' ) {
                navigator.notification.confirm(
                    'Are you sure, this report will be lost?',
                     function( buttonIndex) {
                         if( buttonIndex == 1 ) {
                            $("#ajaxdata").remove();
                            $("#main").load( link );
                         }
                     },
                    'You Will Lose Data!',
                    ['Go Home','Stay Here']
                );
            } else {
                $("#ajaxdata").remove();
                $("#main").load( link );
            }
        })
        .on("click", ".topbarback", function(e) {
            e.preventDefault();

            $('.input-group.date').datepicker({
                    format: "dd/mm/yyyy",
                    todayBtn: "linked",
                    clearBtn: true,
                    autoclose: true,
                    endDate: "0d"
                });

            var thislink = $(this);
            var link = thislink.attr('href');
            var title = thislink.attr('title');
            if( page == 'new-report' ) {
                navigator.notification.confirm(
                    'Are you sure, this report will be lost?',
                     function( buttonIndex) {
                         if( buttonIndex == 1 ) {
                            $("#ajaxdata").remove();
                            $("#main").load( link );
                         }
                     },
                    'You Will Lose Data!',
                    ['Go Home','Stay Here']
                );
            } else if( link != 'dashboard.html' && page == "report-detail" ) {

                link = 'reports.html';
                title = 'Reports';
                $("#ajaxdata").remove();
                $("#main").load( link );
                $('.topbarback').attr( 'href', 'dashboard.html' );
                $('.topbarback').attr( 'title', 'Dashboard' );
                var db = open_db();

                    $('.input-group.date').datepicker({
                        format: "dd/mm/yyyy",
                        todayBtn: "linked",
                        clearBtn: true,
                        autoclose: true,
                        endDate: "0d"
                    });

                    var start = moment();
                        start = start.subtract(30, 'days');
                        start = start.format('DD-MM-YYYY');
                    var today = moment().format('DD-MM-YYYY');

                    var dbstart = moment().subtract(30, 'days').format('YYYY-MM-DD');
                    var dbend = moment().format('YYYY-MM-DD');
                    //alert(dbstart);
                    $('#filter_start_date').val(start);
                    $('#filter_end_date').val(today);
                    var reportQ = "SELECT userName, clientName, reportDate, reportTime, reportID, COUNT(obs.obsItem) AS obss FROM report INNER JOIN client ON clientID = reportClient LEFT JOIN obs ON reportID = obsReport INNER JOIN user ON userID = reportUser WHERE reportDeleted='0' AND reportDate BETWEEN '"+dbstart+"' AND '"+dbend+"' GROUP BY reportID ORDER BY reportDate DESC, reportTime DESC";
                    var clientQ = "SELECT * FROM client ORDER BY clientName ASC";
                    //console.log(reportQ);
                    db.transaction(
                        function (tx) {
                            var output = '<option selected value="-1">All Clients</option>';
                            tx.executeSql(clientQ, [], function (tx, rs) {
                                var i;
                                for (i = 0; i < rs.rows.length; i+=1) {
                                    output += '<option value="' + rs.rows.item(i).clientID + '">' + rs.rows.item(i).clientName + '</option>';
                                }
                                $("#clientID").html(output);
                            }, function() {});
                        }, function() {});

                    //console.log( reportQ );
                    db.transaction(
                        function (tx) {
                            var output = '';
                            tx.executeSql(reportQ, [], function (tx, rs) {
                                var i;
                                for (i = 0; i < rs.rows.length; i+=1) {
                                    var obsOut = rs.rows.item(i).obss + ' Observations';
                                    output += '<a href="report-detail.html" data-loc="report-detail" class="list-group-item view-report" rel="' + rs.rows.item(i).reportID + '"><h4 class="list-group-item-heading">' + rs.rows.item(i).clientName + '</h4><i class="pull-right glyphicon glyphicon-chevron-right"></i><p class="list-group-item-text">Produced ' + rs.rows.item(i).reportDate + ' at ' + rs.rows.item(i).reportTime + ' by ' + rs.rows.item(i).userName + ' - ' + obsOut + '</p></a>';
                                }
                                $("#reportList").html(output);
                            }, function() {});
                        }, function() {});

            } else {
                $("#ajaxdata").remove();
                $("#main").load( link );
            }
            $('.navbar-fixed-top .navbar-brand').html(title);
        })
        .on("click", ".innerlink", function (e) {
            e.preventDefault();

            var thislink = $(this);
            page = thislink.data('loc');
            //console.log(page);
            var link = $(this).attr('href');

            //alert( window.location.pathname );
            $('.innerlink').removeClass('active');
            $(this).addClass('active');
            var title = $(this).attr('title');
            $("#ajaxdata").remove();

            $("#main").load(link, function () {
                $('.navbar-fixed-top .navbar-brand').html(title);

                if (link == 'reports.html') {
                    var db = open_db();

                    $('.input-group.date').datepicker({
                        format: "dd/mm/yyyy",
                        todayBtn: "linked",
                        clearBtn: true,
                        autoclose: true,
                        endDate: "0d"
                    });

                    var start = moment();
                        start = start.subtract(30, 'days');
                        start = start.format('DD-MM-YYYY');
                    var today = moment().format('DD-MM-YYYY');

                    var dbstart = moment().subtract(30, 'days').format('YYYY-MM-DD');
                    var dbend = moment().format('YYYY-MM-DD');
                    //alert(dbstart);
                    $('#filter_start_date').val(start);
                    $('#filter_end_date').val(today);
                    var reportQ = "SELECT userName, clientName, reportDate, reportTime, reportID, COUNT(obs.obsItem) AS obss FROM report INNER JOIN client ON clientID = reportClient LEFT JOIN obs ON reportID = obsReport INNER JOIN user ON userID = reportUser WHERE reportDeleted='0' AND reportDate BETWEEN '"+dbstart+"' AND '"+dbend+"' GROUP BY reportID ORDER BY reportDate DESC, reportTime DESC";
                    var clientQ = "SELECT * FROM client ORDER BY clientName ASC";
                    //console.log(reportQ);
                    db.transaction(
                        function (tx) {
                            var output = '<option selected value="-1">All Clients</option>';
                            tx.executeSql(clientQ, [], function (tx, rs) {
                                var i;
                                for (i = 0; i < rs.rows.length; i+=1) {
                                    output += '<option value="' + rs.rows.item(i).clientID + '">' + rs.rows.item(i).clientName + '</option>';
                                }
                                $("#clientID").html(output);
                            }, function() {});
                        }, function() {});

                    //console.log( reportQ );
                    db.transaction(
                        function (tx) {
                            var output = '';
                            tx.executeSql(reportQ, [], function (tx, rs) {
                                var i;
                                for (i = 0; i < rs.rows.length; i+=1) {
                                    var obsOut = rs.rows.item(i).obss + ' Observations';
                                    output += '<a href="report-detail.html" data-loc="report-detail" class="list-group-item view-report" rel="' + rs.rows.item(i).reportID + '"><h4 class="list-group-item-heading">' + rs.rows.item(i).clientName + '</h4><i class="pull-right glyphicon glyphicon-chevron-right"></i><p class="list-group-item-text">Produced ' + rs.rows.item(i).reportDate + ' at ' + rs.rows.item(i).reportTime + ' by ' + rs.rows.item(i).userName + ' - ' + obsOut + '</p></a>';
                                }
                                $("#reportList").html(output);
                            }, function() {});
                        }, function() {});

                } else if (link == 'new-report.html' || link == 'admin.html') {
                    //console.log( loadtest );


                    if (loadtest == 0) {
                        reportPage();
                        loadtest++;
                    } else {
                        var obsArray = {};
                        actionsOpen = [];
                        console.log(JSON.stringify(actionsOpen));
                        setup_report_page();
                    }
                } else if (link == 'debug.html') {
                    debugPage();
                }
                $('.navbar-fixed-top .navbar-brand').html(title);
            });
        })
        .on("click", ".view-report", function (event) {
            event.preventDefault();
            var reportID = $(this).attr('rel');
            //console.log(reportID);
            page = $(this).data('loc');
            $('.topbarback').attr( 'href', 'reports.html' );
            //console.log(page);
            $('.navbar-fixed-top .navbar-brand').html('Report Detail');
            $("#main").load("report-detail.html #ajaxdata", function (event) {
                reportDetail(reportID);
            });
        });
}

function debugPage() {
    var db = open_db();

    var userQuery = "SELECT * FROM user ORDER BY userName ASC";
    db.transaction(
        function (tx) {
            tx.executeSql(userQuery, [], function (tx, rs) {
                if (rs.rows.length !== 0) {
                    var i;
                    for (i = 0; i < rs.rows.length; i+=1) {
                        $('#debug_users tbody').append('<tr><td>' + rs.rows.item(i).userID + '</td><td>' + rs.rows.item(i).userName + '</td><td>' + rs.rows.item(i).userEmail + '</td><td>' + rs.rows.item(i).userPass + '</td><td>' + rs.rows.item(i).userType + '</td><td>' + rs.rows.item(i).userActive + '</td><td>' + rs.rows.item(i).userCreated + '</td><td>' + rs.rows.item(i).userUpdated + '</td></tr>');
                    }
                } else {
                    $('#debug_users tbody').append('<tr><td colspan="8">No records found</td></tr>');
                }
            }, function() {});
        }, function() {}
    );



    var reportQuery = "SELECT * FROM report JOIN client ON clientID = reportClient ORDER BY reportDate DESC";
    //console.log( reportQuery );
    db.transaction(
        function (tx) {
            tx.executeSql(reportQuery, [], function (tx, rs) {
                if (rs.rows.length !== 0) {
                    var i;
                    for (i = 0; i < rs.rows.length; i+=1) {
                        $('#debug_reports tbody').append('<tr><td>' + rs.rows.item(i).reportID + '</td><td>' + rs.rows.item(i).clientName + '</th><td>' + rs.rows.item(i).reportContract + '</th><td>' + rs.rows.item(i).reportWork + '</th><td>' + rs.rows.item(i).reportDate + '</th><td>' + rs.rows.item(i).reportTime + '</th><td>' + rs.rows.item(i).reportTick + '</th><td>' + rs.rows.item(i).userName + '</th><td>' + rs.rows.item(i).reportClientSig + '</th><td>' + rs.rows.item(i).reportUserSig + '</th><td>' + rs.rows.item(i).reportCreated + '</th><td>' + rs.rows.item(i).reportUpdated + '</th></tr>');
                    }
                } else {
                    $('#debug_reports tbody').append('<tr><td colspan="12">No records found</td></tr>');
                }
            }, function() {});
        }, function() {}
    );

    var clientQuery = "SELECT * FROM client ORDER BY clientName ASC";
    //console.log( clientQuery );
    db.transaction(
        function (tx) {
            tx.executeSql(clientQuery, [], function (tx, rs) {
                if (rs.rows.length !== 0) {
                    var i;
                    for (i = 0; i < rs.rows.length; i+=1) {
                        $('#debug_client tbody').append('<tr><td>' + rs.rows.item(i).clientID + '</td><td>' + rs.rows.item(i).clientName + '</td><td>' + rs.rows.item(i).clientEmail + '</td><td>' + rs.rows.item(i).clientLogo + '</td><td>' + rs.rows.item(i).clientActive + '</td><td>' + rs.rows.item(i).clientCreated + '</td><td>' + rs.rows.item(i).clientUpdated + '</td></tr>');
                    }
                } else {
                    $('#debug_client tbody').append('<tr><td colspan="7">No records found</td></tr>');
                }
            }, function() {});
        }, function() {}
    );

    var contractsQuery = "SELECT * FROM contract JOIN client ON clientID = contractClient ORDER BY clientName ASC";
    //console.log( reportQuery );
    db.transaction(
        function (tx) {
            tx.executeSql(contractsQuery, [], function (tx, rs) {
                if (rs.rows.length !== 0) {
                    var i;
                    for (i = 0; i < rs.rows.length; i+=1) {
                        $('#debug_contracts tbody').append('<tr><td>' + rs.rows.item(i).contractID + '</td><td>' + rs.rows.item(i).clientName + '</th><td>' + rs.rows.item(i).contractNumber + '</th><td>' + rs.rows.item(i).contractLocation + '</th><td>' + rs.rows.item(i).contractActive + '</th><td>' + rs.rows.item(i).contractCreated + '</th><td>' + rs.rows.item(i).contractUpdated + '</th></tr>');
                    }
                } else {
                    $('#debug_contracts tbody').append('<tr><td colspan="12">No records found</td></tr>');
                }
            }, function() {});
        }, function() {}
    );

}

function reportDetail(reportID) {
    var db = open_db();
    var query = 'SELECT * FROM report LEFT JOIN client ON clientID = reportClient LEFT JOIN contract ON contractID = reportContract INNER JOIN user ON reportUser = userID WHERE reportID="' + reportID + '"';
    console.log(query);
    db.transaction(
        function (tx) {
            tx.executeSql(query, [], function (tx, rs) {

                //console.log(rs.rows.item(0));
                $('#clientID').val(rs.rows.item(0).clientName);
                //console.log(rs.rows.item(0).reportContract);
                if( rs.rows.item(0).reportContract != '' && rs.rows.item(0).reportContract != null ) {
                    $('#clientContract').val(rs.rows.item(0).contractNumber);
                } else {
                    $('#clientContract').val('Not Set');
                }
                $('#clientWork').val(rs.rows.item(0).reportWork);
                $('#reportDate').val(rs.rows.item(0).reportDate);
                $('#reportTime').val(rs.rows.item(0).reportTime);
                $('#reportComments').val(rs.rows.item(0).reportComments);
                $("#clientName").html(rs.rows.item(0).clientName);
                $("#userName").html(rs.rows.item(0).userName);
                if ( rs.rows.item(0).reportClientSig != '' ) {
                    $('#clientSig').html('<img src="'+rs.rows.item(0).reportClientSig+'" class="img-responsive" >');
                } else {
                    $('#clientSig').html('<p>No signature required.</p>');
                }
                if ( rs.rows.item(0).reportUserSig != '' ) {
                    $('#userSig').html('<img src="'+rs.rows.item(0).reportUserSig+'" class="img-responsive" >');
                } else {
                    $('#userSig').html('<p>No signature required.</p>');
                }
                $('.createpdf').attr('rel', rs.rows.item(0).reportID);
                $('.resendemail').attr('rel', rs.rows.item(0).reportID);
                $('.printform').attr('rel', rs.rows.item(0).reportID);
                //console.log(rs.rows.item(0).reportTick);
                var ticksString = rs.rows.item(0).reportTick.replace(',}', '}');
                ticksString = ticksString.replace(/\\/g, '');
                //console.log( ticksString);
                var ticks = jQuery.parseJSON(ticksString);
                //console.log(ticks);
                var x;
                for (x = 1; x <= 33; x+=1) {
                    if (ticks[x] > 0) {
                        $(".totalBox" + x).html(ticks[x]).css('background-color', 'red');
                    }
                }
            }, function() {});
        }
    );
    $(document)
        .on("click", '.resendemail', function (e) {

            var report = $(this).attr('rel');
            var button = $(this);
            button.attr("disabled", "disabled");
            var title = button.html();

            button.html("Sending...");
            send_data();

            setTimeout( function() {
                ajax_notify( report );
                button.html("Sent!").removeClass("btn-default").addClass("btn-success");
                setTimeout(function() {
                    button.html(title).removeClass("btn-success").addClass("btn-default").removeAttr("disabled")
                }, 3000)
            }, 5000);


        })
        .on("click", '.createpdf', function (e) {
            var fileTransfer = new FileTransfer();
            var report = $(this).attr('rel');
            var uri = encodeURI("http://swsreports.com/" + report + ".pdf");
            var fileURL = cordova.file.documentsDirectory + "/" + report + ".pdf";
            
            var targetRect = $('.createpdf')[0].getBoundingClientRect(),
            targetBounds = targetRect.left + ',' + targetRect.top + ',' + targetRect.width + ',' + targetRect.height;
            
            window.plugins.socialsharing.setIPadPopupCoordinates(targetBounds);
            
            fileTransfer.download(
                uri,
                fileURL,
                function (entry) {
                    //console.log("download complete: " + entry.toURL());



                    //cordova.InAppBrowser.open(entry.toURL(), '_blank', 'location=no');
                                  
                                  
                                  
                      var options = {
                      message: 'PDF Report', // not supported on some apps (Facebook, Instagram)
                      subject: 'PDF Report for Printing', // fi. for email
                      files: [entry.toURL()], // an array of filenames either locally or remotely
                      url: entry.toURL(),
                      chooserTitle: 'Pick an app' // Android only, you can override the default share sheet title
                      }
                      
                      var onSuccess2 = function(result) {
                      console.log("Share completed? " + result.completed); // On Android apps mostly return false even while it's true
                      console.log("Shared to app: " + result.app); // On Android result.app is currently empty. On iOS it's empty when sharing is cancelled (result.completed=false)
                      }
                      
                      var onError2 = function(msg) {
                      console.log("Sharing failed with message: " + msg);
                      }
                      
                      window.plugins.socialsharing.shareWithOptions(options, onSuccess2, onError2);
                                  
                    //window.open(entry.toURL(), '_blank', 'location=no,closebuttoncaption=Close,enableViewportScale=yes');
                },
                function (error) {
                    //console.log("download error source " + error.source);
                    //console.log("download error target " + error.target);
                    //console.log("upload error code" + error.code);
                },
                false
            );
        })
        .on("click", ".printform", function (event) {
            event.preventDefault();
            //var page = $("html").html();

            var fileTransfer = new FileTransfer();
            var report = $(this).attr('rel');
            var uri = encodeURI("http://swsreports.com/" + report + ".pdf");
            cordova.plugins.printer.print(uri, "Report");
//            var fileURL = cordova.file.documentsDirectory + "/" + report + ".pdf";
//
//            fileTransfer.download(
//                uri,
//                fileURL,
//                function (entry) {
//                    //console.log("download complete: " + entry.toURL());
//                    //console.log('In print');
//                    cordova.plugins.printer.print(entry.toURL(), {
//                        name: 'Report Detail',
//                        greyscale: true
//                    }, function () {
//                        //console.log('printing finished or canceled')
//                    });
//                },
//                function (error) {
//                    //console.log("download error source " + error.source);
//                    //console.log("download error target " + error.target);
//                    //console.log("upload error code" + error.code);
//                },
//                false
//            );


        });
    var obsQuery = 'SELECT *, COUNT( imageID ) as images FROM obs LEFT JOIN image ON imageObs = obsID WHERE obsReport="' + reportID + '" GROUP BY obsID ORDER BY obsItem ASC';
    //console.log(obsQuery);
    db.transaction(
        function (tx) {
            tx.executeSql(obsQuery, [], function (tx, rs) {
                if (rs.rows.length > 0) {
                    $('.swstable tr.nodata').remove();
                }
                var i;
                for (i = 0; i < rs.rows.length; i+=1) {
                    $('.swstable').append('<tr><td>' + rs.rows.item(i).obsItem + '</td><td>' + rs.rows.item(i).obsObs + '</td><td>' + rs.rows.item(i).obsPriority + '</td><td>' + rs.rows.item(i).images + '</td></tr>');
                }
            }, function() {});
        }, function() {}
    );

}

var sigCapture = null;
var sigCapture2 = null;
var reportID = null;


function setup_report_page() {

    $(".bsswitch").bootstrapSwitch({
        onText: 'Yes',
        offText: 'No',
        onColor: 'success',
        offColor: 'danger'
    });

    var db = open_db();
    var d = new Date();
    var h = d.getHours();
    var m = d.getMinutes() < 10 ? '0' : '';
    m += d.getMinutes();
    var day = d.getDate();
    if (day < 10) {
        day = "0" + day;
    }
    var month = d.getMonth() + 1;
    if (month < 10) {
        month = "0" + month;
    }
    var year = d.getFullYear();

    var clientQ = "SELECT * FROM client ORDER BY clientName ASC";
    db.transaction(
        function (tx) {
            var output = '<option readonly disabled selected>Select Client</option>';
            tx.executeSql(clientQ, [], function (tx, rs) {
                var i;
                for (i = 0; i < rs.rows.length; i+=1) {
                    output += '<option value="' + rs.rows.item(i).clientID + '">' + rs.rows.item(i).clientName + '</option>';
                }
                $("#clientID").html(output);
            }, function() {});
        }, function() {});


    $('#date').val(day + '/' + month + '/' + year);
    $('#time').val(h + ':' + m );

    sigCapture = null;
    sigCapture = new SignatureCapture("signature");

    sigCapture2 = null;
    sigCapture2 = new SignatureCapture("signature2");

    reportID = generateUUID();
    actionsOpen = [];
    console.log(JSON.stringify(actionsOpen));
}

function reportPage() {
    var db = open_db();
    var obsArray = {};
    actionsOpen = [];
    var actionsClosed = [];
    var totalObs = 0;

    setup_report_page();
    console.log(JSON.stringify(actionsOpen));
    sigCapture = new SignatureCapture("signature");
    sigCapture2 = new SignatureCapture("signature2");
    $(document).ready(function (e) {

        sigCapture = new SignatureCapture("signature");
        sigCapture2 = new SignatureCapture("signature2");
    });

    $('.signature1').on('hidden.bs.modal', function (e) {
      // Show signature2
      $('.subreport').removeAttr('disabled');
    });

    $(document)
     /*.on( "click", '#prev_action_date', function(e) {

        var dateField = $(this);

        var options = {
            date: new Date(),
            mode: 'date'
        };
        function onSuccess(date) {
            //alert('Selected date: ' + ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear());
            dateField.val( ('0' + date.getDate()).slice(-2) + '/' + ('0' + (date.getMonth() + 1)).slice(-2) + '/' + date.getFullYear() );
        }
        Keyboard.hide();
        datePicker.show(options, onSuccess);

     })*/
     .on("click", ".prev_action_submit", function(e) {

        var temp = {
            "item": $('#prev_action_item').val(),
            "text": $('#prev_action_detail').val(),
            "date": $('#prev_action_date').val(),
            "initials": $('#prev_action_initial').val()
        };

        var output = '<tr id=""><td>'+ $('#prev_action_item').val()+ '</td><td>'+ $('#prev_action_detail').val() +'</td><td>'+ $('#prev_action_date').val() +'</td><td>'+ $('#prev_action_initial').val() +'</td><td></td></tr>';

        if($('#prev_action_type').val() == 'open') {
            actionsOpen.push(temp);
            $('#prev_report_open_table .nodata').remove();
            $('#prev_report_open_table').append(output);
        } else {
            actionsClosed.push(temp);
            $('#prev_report_closed_table .nodata').remove();
            $('#prev_report_closed_table').append(output);
        }

        $('#prev_report_actions').modal('hide');
        $('#prev_report_actions_form').trigger('reset');

        console.log(JSON.stringify(actionsOpen));

        //console.log(JSON.stringify(actionsClosed));

     })
     .on("click", ".client_sig_not_req", function(e) {
        //console.log($(this).val());
        if ( $('.client_sig_not_req .active input').val() == 1 ) {
            //console.log('In 1');
            sigCapture.clear();
            $('#clientSigBox').show();
            //$('#clientSigBox canvas').show();
        } else {
            //console.log('In 0');
            $('#clientSigBox').hide();
            sigCapture.clear();
            //$('#clientSigBox canvas').hide();
        }
    })
    .on("click", ".insp_sig_not_req", function(e) {
        //console.log($(this).val());
        if ( $('.insp_sig_not_req .active input').val() == 1 ) {
            //console.log('In 1');
            sigCapture2.clear();
            $('#inspSigBox').show();
            //$('#clientSigBox canvas').show();
        } else {
            //console.log('In 0');
            $('#inspSigBox').hide();
            sigCapture2.clear();
            //$('#clientSigBox canvas').hide();
        }
    })
    .on("click", ".clearsig1", function (event) {
        event.preventDefault();
        sigCapture.clear();
    })

    .on("click", ".clearsig2", function (event) {
        event.preventDefault();
        sigCapture2.clear();
    })

    .on("change", "#reportform #clientID", function () {
            var clientID = $(this).val();
            var clientQ = "SELECT * FROM contract WHERE contractClient='" + clientID + "' AND contractActive=1 ORDER BY contractNumber ASC";
            db.transaction(
                function (tx) {
                    var output = '<option readonly disabled selected>Select Contract</option>';
                    tx.executeSql(clientQ, [], function (tx, rs) {
                        if (rs.rows.length > 0) {
                            var i;
                            for (i = 0; i < rs.rows.length; i+=1) {
                                output += '<option data-location="' + rs.rows.item(i).contractLocation + '" value="' + rs.rows.item(i).contractID + '">' + rs.rows.item(i).contractNumber + ' (' + rs.rows.item(i).contractLocation + ')</option>';
                            }
                        } else {
                            output += '<option readonly disabled selected>No contracts for this client</option>';
                        }
                        $("#clientContract").html(output);
                    }, function() {});
                }, function() {});

        })
        .on("change", '#reportform #clientContract', function () {
            var contractLoc = $(this).find(':selected').attr('data-location');
            //console.log(contractLoc);
            $('#clientWork').val(contractLoc);
        })
        .on("click", ".newrep_nextstep", function (e) {
            e.preventDefault();
            var errors = 0;
            if ($('#reportform #clientID').val() === '-1') {
                errors++;
            }
            if ($('#reportform #clientContract').val() === '') {
                errors++;
            }
            if ($('#reportform #clientWork').val() === '') {
                errors++;
            }
            if (typeof ($('#reportform input[name=clientPrevAvail]:checked:first').val()) === 'undefined') {
                errors++;
                //console.log('avail error');
            }
            if (typeof ($('#reportform input[name=clientPrevAction]:checked:first').val()) === 'undefined') {
                errors++;
                //console.log('action error');
            }
            if ($('#reportform #reportDate').val() === '') {
                errors++;
            }
            if ($('#reportform #reportTime').val() === '') {
                errors++;
            }
            if (errors === 0) {
                //console.log('no errors');
                $(this).remove();
                $('.step2').hide().removeClass('hidden').show();
            }
        })
        .on("click", ".printform", function (event) {
            event.preventDefault();
            //var page = $("html").html();

            var fileTransfer = new FileTransfer();
            var report = $(this).attr('rel');
            var uri = encodeURI("http://swsreports.com/" + report + ".pdf");
            var fileURL = cordova.file.documentsDirectory + "/" + report + ".pdf";

            fileTransfer.download(
                uri,
                fileURL,
                function (entry) {
                    //console.log("download complete: " + entry.toURL());
                    //console.log('In print');
                    cordova.plugins.printer.print(page, {
                        name: 'Report Detail',
                        greyscale: true
                    }, function () {
                        //console.log('printing finished or canceled')
                    });
                },
                function (error) {
                    //console.log("download error source " + error.source);
                    //console.log("download error target " + error.target);
                    //console.log("upload error code" + error.code);
                },
                false
            );


        })
        .on("click", ".addsig1", function (event) {
            event.preventDefault();
            //console.log("Required: " + $('.client_sig_not_req .active input').val());
            if ( $('.client_sig_not_req .active input').val() == 0 ) {
                $('.signature1').modal('hide');
                //$('#reportform').submit();
                $('.signature2').modal('show');

            } else if ( document.getElementById('signature').toDataURL() !== document.getElementById('blank').toDataURL()) {
                $('.signature1').modal('hide');
                //$('#reportform').submit();
                $('.signature2').modal('show');
                //$('.sigimg').attr('src', 'data:image/png; base64,' + sigCapture.toString());
            } else {
                navigator.notification.alert(
                    'You have not signed the form.',
                    function () {},
                    'Error',
                    'Try Again'
                );
            }

        })
        .on("click", ".addsig2", function (event) {
            event.preventDefault();
            //console.log("Required: " + $('.insp_sig_not_req .active input').val());
            if ( $('.insp_sig_not_req .active input').val() == 0 ) {
                $('.signature2').modal('hide');
                $('#reportform').submit();
                sigCapture.clear();
                sigCapture2.clear();
            } else if ( document.getElementById('signature2').toDataURL() !== document.getElementById('blank2').toDataURL()) {
                $('.signature2').modal('hide');
                $('#reportform').submit();
                //$('.sigimg2').attr('src', 'data:image/png; base64,' + sigCapture2.toString());
                sigCapture.clear();
                sigCapture2.clear();
            } else {
                navigator.notification.alert(
                    'You have not signed the form.',
                    function () {},
                    'Error',
                    'Try Again'
                );
            }

        })

    .on("click", '.subreport', function (e) {
            e.preventDefault();
            $(this).attr('disabled', 'disabled');
            $(".sigReportID").val(reportID);
            $('.signature1').modal('show');

            //$('#reportform').submit();
        })
        .on("submit", "#reportform", function (event) {
            event.preventDefault();
            var result = {};
            $.each($(this).serializeArray(), function () {
                result[this.name] = this.value;
            });
            if ( $('.client_sig_not_req .active input').val() == 0 ) {
                result.sig = '';
            } else {
                result.sig = 'data:image/png;base64,' + sigCapture.toString();
            }

            if ( $('.insp_sig_not_req .active input').val() == 0 ) {
                result.sig2 = '';
            } else {
                result.sig2 = 'data:image/png;base64,' + sigCapture2.toString();
            }
            result.report = $('.sigReportID').val();

            result.ticked = '{';
            var i;
            for (i = 1; i <= 33; i+=1) {
                if (i === 33) {
                    result.ticked += '"' + i + '":"' + result["inlineCheckbox[" + i + "]"] + '"';
                } else {
                    result.ticked += '"' + i + '":"' + result["inlineCheckbox[" + i + "]"] + '",';
                }
            }
            result.ticked += '}';
            result.user = window.localStorage.userID;
            result.actionsOpen = JSON.stringify(actionsOpen);
            console.log(JSON.stringify(actionsOpen));
            console.log(result.actionsOpen);

            var query = "INSERT INTO report (reportID, reportClient, reportWork, reportContract, reportDate, reportTime, reportTick, reportUser, reportPrevAvail, reportPrevAction, reportClientSig, reportUserSig, reportComments, actionsOpen ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            db.transaction(
                function (tx) {
                    //console.log(result);
                    //var reportDate = new Date(result.reportDate);
                    //ar saveDate = reportDate.getFullYear()+'-'+reportDate.getMonth()+'-'+reportDate.getDate();
                    var tempDate = result.reportDate.replace(/(\d{2})\/(\d{2})\/(\d{4})/, "$3-$2-$1");
                    result.reportDate = tempDate;
                    tx.executeSql(query, [result.report, result.reportClient, result.reportWork, result.reportContract, result.reportDate, result.reportTime, result.ticked, result.user, result.clientPrevAvail, result.clientPrevAction, result.sig, result.sig2, result.reportComments, result.actionsOpen], function (tx, rs) {

                        if (online === 1) {

                            // Get observations from db and upload
                            var obsQuery   = "SELECT * FROM obs WHERE obsReport='"+result.report+"'";
                            var obsID;
                            tx.executeSql(obsQuery, [], function(tx, rs) {
                                for (i = 0; i < rs.rows.length; i += 1) {
                                    ajax_insert_obs(result.report, rs.rows.item(i).obsID, rs.rows.item(i).obsItem, rs.rows.item(i).obsObs, rs.rows.item(i).obsPriority, rs.rows.item(i).obsName);

                                    var imageQuery = "SELECT * FROM image WHERE imageObs='"+rs.rows.item(i).obsID+"'";
                                    tx.executeSql(imageQuery, [], function(tx2, rs2) {
                                        for (j = 0; j < rs2.rows.length; j += 1) {
                                            ajax_insert_image(rs2.rows.item(j).imageID, rs2.rows.item(j).imageObs, rs2.rows.item(j).imageData);
                                        }
                                    });
                                }
                            });

                            ajax_insert_new_report( result );
                        }
                        actionsOpen.length = 0
                        actionsOpen = [];
                        //console.log("inserted report:" + result.report);

                    }, function(err) {
                        //console.log(query);
                        console.log('report transaction failed' + JSON.stringify(err));
                    });
                }, function(err) {
                    //console.log(query);
                    console.log('transaction failed' + JSON.stringify(err));
                }, function() {
                    console.log('transaction ok');
                });

            var title = $('a.dashboard').attr('title');
            $("#main").load('dashboard.html', function () {
                $('.navbar-fixed-top .navbar-brand').html(title);
            });
            $('.signature1 form').trigger('reset');
            if (online === 1) {
                ajax_notify(result.report);
                    navigator.notification.alert(
                    'Your report was successfully added.',
                    function () {
                    //console.log(result.report);

                    },
                    'Report Added',
                    'OK'
                );
            } else {
                //console.log(result.report);
                add_to_queue( result.report );

                navigator.notification.alert(
                    'Your report was saved locally, the report will sync automatically next time you use the app online.',
                    function () {
                    //console.log(result.report);

                    },
                    'Report Saved',
                    'OK'
                );
            }

            $('.subreport').removeAttr('disabled');

        })
        .on("click", ".prev_report_actions_add", function(e) {

            var form = $('#prev_report_actions_form');

            if ( $(this).data('type') == 'open' ) {
                $('#prev_report_actions_form .modal-title').html('Add New Open Action');
            } else {
                $('#prev_report_actions_form .modal-title').html('Add New Closed Action');
            }

            $('#prev_report_actions_form #prev_action_type').val($(this).data('type'));

            $('#prev_report_actions').modal({
                backdrop: 'static',
                keyboard: false,
                show: true
            });

        })
        .on("click", ".tickboxes .addObsButton", function (e) {
            e.preventDefault();
            var arrayid = generateUUID();
            $("#newObs .obsID").val(arrayid);
            var obsID = $("#newObs .obsID").val();
            //obsArray[obsID] = {};
            var str = $(this).attr('rel');
            var str2 = $(this).attr('data-title');

            if( str2 == "" ) {
                $(".newobs #obsName").attr('readonly', false);
            } else {
                $(".newobs #obsName").attr('readonly', true);
            }

            $(".newobs #obsItem").val(str);
            $(".newobs #obsName").val(str2);
            $('#newObs input[text]').val('');
            $('#newObs textarea').val('');
            $("#newObs .obsImages").val('0');
            $('.media div').remove();
            $('#newObs .obsPriority').val('null');
            $('.newobs').modal({
                backdrop: 'static',
                keyboard: false,
                show: true
            });
            //$(".newobs").modal("show");
            $('.addobssub').removeAttr('disabled');
        })
        .on("click", ".addobssub", function (e) {
            $(this).attr('disabled', 'disabled');
            $('#newObs').submit();
        })
        .on("submit", "#newObs", function (e) {
            e.preventDefault();
            $(".nodata").remove();
            var result = {};
            result.images = [];
            var error = 0;
            $.each($(this).serializeArray(), function () {
                if (this.name === 'obsImage[]') {
                    var imageData = {
                        imageID: generateUUID(),
                        image: this.value
                    };
                    //console.log('Unique ID: '+generateUUID())
                    result.images.push(this.value);
                    //console.log(this.value);
                } else {
                    console.log(this);
                    result[this.name] = this.value;
                }
            });
            if ( result.obsPriority === 'null' ) {
                //alert("Please select a priority");
                error++;
            }
            if (result.obsObs == "") {
                //alert("Please enter an observation");
                error++;
            }
            if (error == 0) {
                var obsID = result.obsID;
                if (typeof obsArray === 'undefined') {
                    obsArray = {};
                    //obsArray[obsID] = [];
                }
                //obsArray[obsID] = {};
                //obsArray[obsID] = result;

                save_obs( result );

                //obsArray[obsID].push(result);
                //console.log(obsArray[obsID]);
                var priorityClass = result.obsPriority.split(" ");
                priorityClass = 'priority_' + priorityClass[0];
                var output = '<tr id=' + obsID + '><td>' + result.obsItem + '</td><td>' + result.obsObs + '</td><td class="' + priorityClass + '">' + result.obsPriority + '</td><td>' + result.images.length + '</td><td><button type="button" rel="' + obsID + '" class="btn btn-primary btn-xs editObs"><span class="glyphicon glyphicon-pencil"></span></button> <button type="button" rel="' + obsID + '" data-item="' + result.obsItem + '" class="btn btn-danger btn-xs delObs"><span class="glyphicon glyphicon-trash"></span></button></td></tr>';
                $('.swstable').append(output);
                var curr = $('.totalBox' + result.obsItem).html();
                $('.totalBox' + result.obsItem).html(+curr + 1).css('background-color', 'red');
                if( result.obsItem == 14 || result.obsItem == 23 ) {
                    //console.log( 'button[rel='+result.obsItem+']' );
                    $('.itemName'+ result.obsItem).html(result.obsName);
                    $('button[rel='+result.obsItem+']').attr('data-title', result.obsName);
                }
                $('#inlineCheckbox' + result.obsItem).val(+curr + 1);
                $(".newobs").modal("hide");
                $('#newObs input[text]').val('');
                $('#newObs textarea').val('');
                $("#newObs .obsImages").val('0');
                $(".media").html('');
                //$('.media div').remove();
                $('#newObs .obsPriority').val('select');
                $('#newObs').trigger("reset");
                totalObs = totalObs + 1;
                //console.log('end');
            }
            $('.addobssub').removeAttr('disabled');
            //console.log(obsArray);
        })
        .on( 'click', '.delObs', function(e) {
            var delObs = $(this);
            var obsKey = delObs.attr('rel');
            var obsItem = delObs.data( 'item' );
            navigator.notification.confirm(
                'Are you sure, this is non-reversible.',
                 function( buttonIndex) {
                     if( buttonIndex == 1 ) {
                        //console.log( 'Deleting ' + obsItem );

                        //delete obsArray[obsKey];

                        // Delete obs from db
                        var db = open_db();
                        db.transaction(
                            function (tx) {
                                tx.executeSql( "DELETE FROM obs WHERE obsID=?", [obsKey], function() {
                                    $( 'tr#'+obsKey ).remove();
                                    var curr = $('.totalBox' + obsItem).html();
                                    $('.totalBox' + obsItem).html(+curr - 1);
                                    if( $( '.totalbox'+ obsItem ).html() == 0 ) {
                                        $(this).css('background-color', 'black');
                                    }
                                    $('#inlineCheckbox' + obsItem).val(+curr - 1);
                                }, function(err) {
                                        console.log('report delete transaction failed' + JSON.stringify(err));
                                    });
                            });


                        //console.log( 'Deleted ' + obsKey );
                     }
                 },
                'Confirm',
                ['Yes','No']
            );
            e.preventDefault();
        })
        .on("click", ".editObs", function (e) {
            e.preventDefault();
            var editObs = $(this);
            var obsKey = editObs.attr('rel');
            var obs = {};
            var images = [];
            //console.log( obsArray[obsKey] );

            var query = "SELECT * FROM obs WHERE obsID='"+obsKey+"'";
            var query2 = "SELECT * FROM image WHERE imageObs='"+obsKey+"'";

            //var db = open_db();

            db.transaction(
                function (tx) {

                    tx.executeSql(query, [], function (tx, rs) {
                        if (rs.rows.length > 0) {
                            //console.log(rs.rows.item(0));
                            obs = {
                                obsID: rs.rows.item(0).obsID,
                                obsItem: rs.rows.item(0).obsItem,
                                obsName: rs.rows.item(0).obsName,
                                obsPriority: rs.rows.item(0).obsPriority,
                                obsObs: rs.rows.item(0).obsObs
                            };

                            $('#editobsItem').val(obs.obsItem);
                            $('#editobsName').val(obs.obsName);
                            $('#editobsPriority').val(obs.obsPriority);
                            $('#editobsObs').text(obs.obsObs);
                            $('#editobsID').val(obs.obsID);
                            $("#editmedia").html('');

                            $('.editobs').modal({
                                backdrop: 'static',
                                keyboard: false,
                                show: true
                            });
                            //console.log( query2 );
                            tx.executeSql(query2, [], function (tx, rs2) {
                                if (rs2.rows.length > 0) {
                                    var n;
                                    for (n = 0; n < rs2.rows.length; n+=1) {
                                        //console.log( rs2.rows.item(n) );
                                        images.push(rs2.rows.item(n).imageData);
                                    }
                                    console.log(images);
                                    $.each(images, function (i, image) {
                                        //console.log( i );
                                        //console.log( image );
                                        $("#editmedia").append('<div class="col-sm-2"><a href="#" class="removemedia"><span class="glyphicon glyphicon-remove"></span></a><img src="' + image + '" /><input type="hidden" name="obsImage[]" value="' + image + '" /></div>');
                                    });

                                }

                            });
                        }


                    });
                }
            );

            //$(".editobs").modal("show");
        })
        .on("click", ".editobssub", function (e) {
            $(this).attr('disabled', 'disabled');
            $('#editObs').submit();
        })
        .on("submit", "#editObs", function (e) {
            e.preventDefault();
            var result = {};
            result.images = [];
            var error = 0;
            $.each($(this).serializeArray(), function () {
                if (this.name === 'obsImage[]') {
                    result.images.push(this.value);
                    //console.log(this.value);
                } else {
                    var key = this.name.replace('edit', '');
                    result[key] = this.value;
                }
            });
            //console.log( result );
            if (typeof result.obsPriority === 'undefined') {
                //alert("Please select a priority");
                error++;
            }
            if (result.obsObs == "") {
                //alert("Please enter an observation");
                error++;
            }
            if (error == 0) {
                var obsID = result.obsID;
                //obsArray[obsID] = {};
                //obsArray[obsID] = result;
                save_obs( result );
                //obsArray[obsID].push(result);
                //console.log(result.images.length);
                var priorityClass = result.obsPriority.split(" ");
                priorityClass = 'priority_' + priorityClass[0];
                var output = '<tr id="' + obsID + '"><td>' + result.obsItem + '</td><td>' + result.obsObs + '</td><td class="' + priorityClass + '">' + result.obsPriority + '</td><td>' + result.images.length + '</td><td><button type="button" rel="' + obsID + '" class="btn btn-primary btn-xs editObs"><span class="glyphicon glyphicon-pencil"></span></button> <button class="btn btn-danger btn-xs"><span class="glyphicon glyphicon-trash"></span></button></td></tr>';

                $('.swstable tr#' + obsID).replaceWith(output);
                $(".editobs").modal("hide");
                $('#editObs').trigger("reset");
                $("#newObs .obsImages").val('0');
                $('.media div').remove();
                $('.media').html('');
                //console.log('end');
            }
            $('.editobssub').removeAttr('disabled');
            //console.log(obsArray);
        })
        .on("click", ".addmedia", function (e) {
            e.preventDefault();
            navigator.camera.getPicture(onSuccess, onFail, {
                destinationType: Camera.DestinationType.DATA_URL,
                sourceType: Camera.PictureSourceType.CAMERA,
                encodingType: Camera.EncodingType.JPEG,
                quality: 30,
                targetWidth: 800,
                targetHeight: 600,
                correctOrientation: true,
                saveToPhotoAlbum: true
            });
        })

        .on("click", ".addmedialib", function (e) {
            //console.log("medias")
            e.preventDefault();
            navigator.camera.getPicture(onSuccess, onFail, {
                destinationType: Camera.DestinationType.DATA_URL,
                sourceType: Camera.PictureSourceType.PHOTOLIBRARY,
                encodingType: Camera.EncodingType.JPEG,
                quality: 30,
                targetWidth: 800,
                targetHeight: 600,
                correctOrientation: true
            });
        })


    .on("click", ".removemedia", function (e) {
            e.preventDefault();
            $(this).parent().fadeOut(1000).remove();
        })
        .on('click', '.dropdown-menu.dropdown-menu-form', function (e) {
            e.stopPropagation();
        });

    function deleteImages( obsID ) {

        var db = open_db();
        db.transaction(
            function (tx) {
                tx.executeSql( "DELETE FROM image WHERE imageObs=?", [obsID], function() {
                    $.post('http://swsreports.com/ajax/delete-images.php', { obsID: obsID });
                }, function(err) {
                    console.log('image delete transaction failed ' + JSON.stringify(err));
                });
            }
        );

    }

    function save_obs( result ) {
        //console.log('reportID: '+reportID);
        var db = open_db();
        db.transaction(
            function (tx) {
                var obsID = result.obsID;
                var obsItem = result.obsItem;
                var obsObs = result.obsObs;
                var obsPriority = result.obsPriority;
                var obsName = result.obsName;

                //deleteImages( obsID );

                var obsQuery = "INSERT OR REPLACE INTO obs (obsReport, obsID, obsItem, obsObs, obsPriority, obsName) VALUES (?,?,?,?,?,?)";
                //for( var key in result.images ) {
                tx.executeSql( "DELETE FROM image WHERE imageObs=?", [obsID], function() {
                    $.each(result.images,  function(index, data) {
                        var imageID = generateUUID();
                        var imageData = data;
                        var imageQuery = "INSERT INTO image (imageID, imageObs, imageData) VALUES ('" + imageID + "','" + obsID + "','" + imageData + "')";
                        tx.executeSql(imageQuery, [], function (tx, rs) {
                            if (online === 1) {
                                //console.log('Attempting image upload: '+imageID)
                                //ajax_insert_image(imageID, obsID, imageData);
                            }
                            //console.log('inserted image:' + imageID);
                        }, function() {});
                    });
                });
                //console.log ( obsQuery );
                //var obvsID = generateUUID();

                tx.executeSql(obsQuery, [reportID, obsID, obsItem, obsObs, obsPriority, obsName], function (tx, rs) {
                    if (online === 1) {
                        //ajax_insert_obs(reportID, obsID, obsItem, obsObs, obsPriority);
                    }
                    console.log('inserted obs:' + obsID);
                }, function(err) {
                    //console.log(query);
                    console.log('obs transaction failed: ' + JSON.stringify(err));
                    //console.log(obsQuery);
                });
            });

        //});
    }



    function add_to_queue( reportID ) {

        var db = open_db();
        db.transaction(
            function(tx) {
                tx.executeSql("INSERT INTO queue (queueReport) VALUES (?)", [ reportID ], function(tx, rs) {}, function(err) {} );
            }
        )

    }

    function onSuccess(imageData) {
        var i = $("#newObs .obsImages").val();
        $(".media").append('<div class="col-sm-2"><a href="#" class="removemedia"><span class="glyphicon glyphicon-remove"></span></a><img src="data:image/jpeg;base64,' + imageData + '" /><input type="hidden" name="obsImage[]" value="data:image/jpeg;base64,' + imageData + '" /></div>');
        i = +i + 1;
        $("#newObs .obsImages").val(i);
    }

    function edionSuccess(imageData) {
        var i = $("#newObs .obsImages").val();
        $("#editmedia").append('<div class="col-sm-2"><a href="#" class="removemedia"><span class="glyphicon glyphicon-remove"></span></a><img src="data:image/jpeg;base64,' + imageData + '" /><input type="hidden" name="obsImage[]" value="data:image/jpeg;base64,' + imageData + '" /></div>');
        i = +i + 1;
        $("#newObs .obsImages").val(i);
    }



    function onFail(message) {
        alert('Failed because: ' + message);
    }

}
