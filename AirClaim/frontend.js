jQuery(document).ready(function($) {

    $('.progress-bar').bind('inview', function(event, visible, visiblePartX, visiblePartY) {
        if (visible) {
            $(this).css('width', $(this).data('width') + '%');
            $(this).unbind('inview');
        }
    });

    $.fn.animateNumbers = function(stop, commas, duration, ease, currency) {
        return this.each(function() {
            var $this = $(this);
            var start = parseInt($this.text().replace(/,/g, ""));
            commas = (commas === undefined) ? true : commas;
            $({value: start}).animate({value: stop}, {
                duration: duration == undefined ? 1000 : duration,
                easing: ease == undefined ? "swing" : ease,
                step: function() {
                    $this.text(Math.floor(this.value));
                    if (commas) { $this.text($this.text().replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,")); }
                    if (currency) { $this.text("£"+$this.text()); }
                },
                complete: function() {
                    if (parseInt($this.text()) !== stop) {
                        $this.text(stop);
                        if (commas) { $this.text($this.text().replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,")); }
                        if (currency) { $this.text("£"+$this.text()); }
                    }
                }
            });
        });
    };

    $('.animated-number').bind('inview', function(event, visible, visiblePartX, visiblePartY) {
        var $this = $(this);
        if (visible) {
            var currency = $(this).data('currency') == undefined ? false : true;
            $this.animateNumbers($this.data('digit'), true, $this.data('duration'), 'swing', currency);
            $this.unbind('inview');
        }
    });

    new WOW().init();

    $('.sigPad').signaturePad({drawOnly:true, lineTop:180});

    $('.input-group.date').datepicker({
        format: "dd/mm/yyyy"
    });

    var $formCarousel = $('#form-carousel').carousel({
        interval: false
    });

    function gaTrack(path, title) {
        if ( typeof __gaTracker !== 'undefined' ) {
            __gaTracker('set', { page: path, title: title });
            __gaTracker('send', 'pageview');
        }
    }

    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window,document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');

    //fbq('init', '458617994477043'); // Removed Nics tracking code here temporary

    $(".fileinput").fileinput({
        'showPreview': false,
        'showUpload': false,
        'maxFileCount': 1,
        'showRemove' : false
    });
    $('#flightdate').mask("00/00/0000", {placeholder: "dd/mm/yyyy"});

    $(".formBack").click(function(e) {
        $formCarousel.carousel('prev');
    });

    var formData = new FormData();

    $('#formstep1').validate({
      debug: true,
      errorLabelContainer: "#errorBox",
      errorElement: "div",
      errorClass: "alert alert-danger",
      rules: {
        age: {
            required: true,
            digits: true
        },
        passengers: {
            required: true,
            digits: true
        },
        flightdate: {
          required: true,
        }
      },
      messages: {
        flightdate: {
            required: "You must provide a flight date",
        },
        departure: "You must provide your departure airport",
        arrival: "You must provide your arrival airport",
        airline: "You must specify which airline you travelled with",
        passName: "You must provide a passenger name",
        age: {
            required: "You must provide the passengers age",
            digits: "Age must be a digit"
        },
        passengers: {
            required: "You must provide the number of passengers",
            digits: "Number of passeners must be a digit"
        }
      },
      submitHandler: function(form) {

        formData.append('flightdate', $("#flightdate").val());
        formData.append('departure', $("#departureAirport").val());
        formData.append('arrival', $("#arrivalAirport").val());
        formData.append('flightnumber', $("#flightnumber").val());
        formData.append('passName', $("#passName").val());
        formData.append('passengers', $("#passengers").val());
        formData.append('age', $("#age").val());

        if ( $("#age").val() < 18 ) {
            $("#authText").html("I, as parent / legal guardian hereby appoint and authorise Airclaim to consider and pursue this claim for compensation due under the terms of EU 261 / 2004.  In addition Airclaim have my full authority to discuss, negotiate and settle any financial matters or offers pursuant to this claim, to full and final settlement.")
            $("#formstep3 h1").html("PARENT/LEGAL GUARDIAN DETAILS");
        }

        gaTrack('/claim-online/step-2/', 'Claim Online - Step 2');

        $('.form-cloud').removeClass('active');
        $("#form-step-2").addClass('active');
        $formCarousel.carousel('next');
      }
    });

    $('#formstep3').validate({
      debug: true,
      errorLabelContainer: "#errorBox2",
      errorElement: "div",
      errorClass: "alert alert-danger",
      rules: {
        email: {
          required: true,
          email: true
        },
        tel: {
          required: true,
          digits: true
        }
      },
      messages: {
        fname: "You must provide your first name",
        lname: "You must provide your last name",
        address1: "You must provide your street address",
        address2: "You must provide your town",
        postcode: "You must provide your postcode",
        tel: {
          required: "You must provide your telephone number",
          digits: "Only numbers are allowed for telephone number"
        },
        email: {
          required: "You must provide your email address",
          email: "Your email address must be in the format of name@domain.com"
        },
        auth2: "We need your consent to proceed with your claim"
      },
      submitHandler: function(form) {

        formData.append('fname', $("#fname").val());
        formData.append('lname', $("#lname").val());
        formData.append('address1', $("#address1").val());
        formData.append('address2', $("#address2").val());
        formData.append('postcode', $("#postcode").val());
        formData.append('tel', $("#tel").val());
        formData.append('email', $("#email").val());
        formData.append('auth2', $("#auth2").val());

        formData.append('boardingPass', $("#boarding_pass")[0].files[0]);
        formData.append('idDocument', $("#passport")[0].files[0]);

        gaTrack('/claim-online/step-4/', 'Claim Online - Step 4');

        $('.form-cloud').removeClass('active');
        $("#form-step-4").addClass('active');
        $formCarousel.carousel('next');
      }
    });

    $('#step1details').validate({
      debug: true,
      errorLabelContainer: "#errorBox3",
      errorElement: "div",
      errorClass: "alert alert-danger",
      rules: {
        age: {
            required: true,
            digits: true
        },
        passengers: {
            required: true,
            digits: true
        },
      },
      messages: {
        passName: "You must provide a passenger name",
        age: {
            required: "You must provide the passengers age",
            digits: "Age must be a digit"
        },
        passengers: {
            required: "You must provide the number of passengers",
            digits: "Number of passeners must be a digit"
        }
      },
      submitHandler: function(form) {

        formData.append('passName', $("#passName").val());
        formData.append('passengers', $("#passengers").val());
        formData.append('age', $("#age").val());
        formData.append('flightdate', $("#flightdateD").val());
        formData.append('departure', $("#departureAirportD").val());
        formData.append('arrival', $("#arrivalAirportD").val());
        formData.append('flightnumber', $("#flightnumberD").val());

        if ( $("#age").val() < 18 ) {
            $("#authText").html("I, as parent / legal guardian hereby appoint and authorise Airclaim to consider and pursue this claim for compensation due under the terms of EU 261 / 2004.  In addition Airclaim have my full authority to discuss, negotiate and settle any financial matters or offers pursuant to this claim, to full and final settlement.")
            $("#formstep3 h1").html("PARENT/LEGAL GUARDIAN DETAILS");
        }

        gaTrack('/claim-online/step-2/', 'Claim Online - Step 2');

        $('.form-cloud').removeClass('active');
        $("#form-step-2").addClass('active');
        $formCarousel.carousel('next');
      }

    });

    $('#form-step-2-button').click( function(e) {
        e.preventDefault();

        formData.append('reason', $('input[name=reason]:checked').val())
        $('.form-cloud').removeClass('active');
        $("#form-step-3").addClass('active');
        $formCarousel.carousel('next');

        gaTrack('/claim-online/step-3/', 'Claim Online - Step 3');

    });

    $('#form-step-4-button').click(function(e) {
        e.preventDefault();

        if( $("#output").val().length == 0 ) {
            $(".sigPad").after('<div class="alert alert-danger" role="alert"><strong>Signature Required!</strong> We need your signature to proceed with your claim.</div>');
        } else {

            $('.form-cloud').removeClass('active');
            $("#form-step-5").addClass('active');
            $formCarousel.carousel('next');

            formData.append('accept', 1);
            formData.append('action', 'newclaim_ajax');
            formData.append('output', $("#output").val());

            console.log(formData);

            $.ajax({
                url: ac_ajax.ajaxurl,
                method: 'post',
                data: formData,
                dataType: 'json',
                processData: false,
                contentType: false,
                success: function (data, textStatus, jqXHR) {
                    $('#thankyou').before('<div class="alert alert-success" role="alert"><strong>'+data.status+'!</strong> '+data.description+'</div>');
                    gaTrack('/claim-online/step-5/', 'Claim Online - Step 5');
                    //fbq('track', 'PageView'); // Removed for now, to be added back in when facebook ads go live
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    $('#thankyou').before('<div class="alert alert-danger" role="alert"><strong>Error!</strong> There was an error processing your request. Please refresh the page and try again.</div>');
                }
            });
        }
    });

    $('#basicBoard').flightboard({messages: ['DELAY'],
        lettersSize: [35, 57],
        maxLength: 5,
        lettersImage: '/wp-content/themes/airclaim_theme/images/flight-board.png',
        shadingImages: ['/wp-content/themes/airclaim_theme/images/flightBoardHighlight.png', '/wp-content/themes/airclaim_theme/images/flightBoardShadow.png']});

    function resize_masthead() {
        var windowHeight = $(window).height();
        var navHeight    = $('.navbar-default').first().height();
        var divHeight    = $('#splash').height();

        $("#home-top").css('min-height', windowHeight - navHeight - 10);

        var padding      = (windowHeight - divHeight)/2 - 60;
        $('#splash').css('padding-top', padding);

    }

    resize_masthead();

    $(window).resize(resize_masthead);

    $('.scroll').on('click', function(){

        var scrollTo = $(this.hash).offset().top - 5;

        if (navigator.userAgent.match(/(iPod|iPhone|iPad|Android)/)) {
            window.scrollTo(0, scrollTo) // first value for left offset, second value for top offset
        }else{
            $('html,body').animate({
                scrollTop: scrollTo
            }, 1000, function(){
                $('html,body').clearQueue();
            });
        }
        return false;
    });

    var airports = new Bloodhound({
      datumTokenizer: function(d) {
        return Bloodhound.tokenizers.whitespace(d.name);
      },
      matchAnyQueryToken: true,
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      prefetch: {
        url: '/wp-content/themes/airclaim_theme/airports.json',
        transform: function(countryArray) {
            return $.map(countryArray, function(country) {
                return {
                    name: country.name,
                    iata: country.iata
                };
            });
        }
      },
      cache: false
    });

    $('#departureAirport').typeahead({
          hint: true,
          highlight: true,
          minLength: 1
        },
        {
          name: 'dep',
          source: airports,
          display: function(data) {
            return data.name+" ["+data.iata+"]";
          },
          limit: 3
    });

    $('#arrivalAirport').typeahead({
          hint: true,
          highlight: true,
          minLength: 1
        },
        {
          name: 'arr',
          source: airports,
          display: function(data) {
            return data.name+" ["+data.iata+"]";
          },
          limit: 3
    });

    $('.slickslider').slick({
      infinite: true,
      slidesToShow: 3,
      slidesToScroll: 3,
      autoplay: true,
      arrows: false,
      autoplaySpeed: 2000,
      responsive: [
        {
          breakpoint: 768,
          settings: {
            arrows: false,
            centerMode: true,
            centerPadding: '40px',
            slidesToShow: 3
          }
        },
        {
          breakpoint: 480,
          settings: {
            arrows: false,
            centerMode: true,
            centerPadding: '40px',
            slidesToShow: 1
          }
        }
      ]
    });

    if (navigator.userAgent.match(/(iPod|iPhone|iPad|Android)/)) {
        $('input, .btn').removeClass('input-lg').removeClass('btn-lg');
    }

});