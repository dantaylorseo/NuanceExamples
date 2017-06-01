jQuery(document).ready(function($) {

	if( $("#gtw-redirect").length ) {
		if( $("#gtw-redirect").val() == 'true' ) {
			//alert($("#gtw-redirect").val());
			$('#gtw-submit').click();
		}
	}
});