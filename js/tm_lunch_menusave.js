/**
 * Author: David Wood
 * Part of the TM Lunch Menu WP plugin
**/
jQuery(document).ready(function($) {
    $('#post').submit(function() {
        $('#tm_post_title').val('Week of '+ $('#datepicker').val() );
    });
});
