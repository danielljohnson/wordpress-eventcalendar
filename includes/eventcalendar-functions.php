<?php

/**
 * Outputs the specified debugging message if EVENTCALENDAR_DEBUG is true.
 * 
 * @param string $message The debugging message.
 */
function eventcalendar_debug($message='') {
    if (EVENTCALENDAR_DEBUG) {
        $log = fopen(EVENTCALENDAR_DEBUG_LOG, 'a');
        
        if (is_resource($log)) {
            fwrite($log, $message . "\r\n");
            fclose($log);
        } else {
            error_log('DEBUG: ' . $message);
        }
    }
}

?>