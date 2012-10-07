<?php

/* Define whether to enable script debuging */
define('EVENTCALENDAR_DEBUG', false);
define('EVENTCALENDAR_DEBUG_STRICT', false);

/* IF EVENTCALENDAR_DEBUG is set to true, Wordpress Error Reporting will be enabled. */
if (EVENTCALENDAR_DEBUG) {
	if (!defined('WP_DEBUG')) {
		define('WP_DEBUG', true);
	}
	
	if (!defined('WP_DEBUG')) {
		define('SCRIPT_DEBUG', true);
	}
	
	if (EVENTCALENDAR_DEBUG_STRICT) {
		error_reporting(E_ALL);
	 	ini_set("display_errors", 1);
	}
}

/* Define where Wordpress should clear it's URL writing cache.
 * When creating new custom post types, it is important to flush 
 * the URL re-writing rules to ensure these post types have rewrite
 * rules associated with them.
 */
define('EVENTCALENDAR_FLUSH_URLS', true);

/* Error log */
define('EVENTCALENDAR_DEBUG_LOG', '/tmp/eventcalendar-debug.log');

// start sessions if needed
if (!session_id()) {
    session_start();
}

?>