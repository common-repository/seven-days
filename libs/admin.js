/*
Admin JS for Seven Days plugin
*/

jQuery(document).ready(function($) {

    $(".seven_days_checkbox .checkbox").live("click", function(){
		
		var related_checkbox = $(this).next(':checkbox');
		if( $(related_checkbox).attr('checked')){
			$(related_checkbox).attr('checked', false);
			$(this).removeClass('checked');
		}else{
			$(related_checkbox).attr('checked', true);
			$(this).addClass('checked');
		}
		
        var show_hide_array = ["0", "0", "0", "0", "0", "0", "0"];
        var cur_widget = $(this).parents('.widget');
        
        var active_checkbox_list = $('.seven_days_checkbox :checkbox:checked', cur_widget);

        for(var i=0; i<active_checkbox_list.length; i++){
        	show_hide_array[$(active_checkbox_list[i]).val()] = "1";
        }

        var show_hide_string = show_hide_array.join('');
        var widget_id = $(this).closest('form').children('input.widget-id').val();

        var data = {
    		action: 'seven_days_update_action',
    		wid: widget_id,
            day: show_hide_string
    	};
        $('.ajax-feedback', cur_widget).css('visibility', 'visible');
        jQuery.post(ajaxurl, data, function(response) {
            $('.ajax-feedback', cur_widget).css('visibility', 'hidden');
		});
	});
	
	

});