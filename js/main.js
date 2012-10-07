jQuery(function($) {
    $('#event_calendar #left_arrow, #event_calendar #right_arrow').live('click', function() {
        data = {
            'action': 'test_ajax',
            'url': $(this).attr('href')
        }
        
        $.post(EventCalendarAjax.ajaxurl, data, function(data) {
            $('#event_calendar_wrap').html(data);
        });
        
        return false;
    });
});
