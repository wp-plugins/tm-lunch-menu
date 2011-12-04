jQuery(document).ready( function($){

    $(function() {
		$( "#datepicker" ).datepicker({
            showOn: "both",
			buttonImage: tmDateTime.pluginURL + "images/calendar.gif",
			buttonImageOnly: true,
            buttonText: 'Choose a date...',
            minDate: new Date()
        });
	});

} );
