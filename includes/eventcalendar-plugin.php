<?php

require_once('eventcalendar-config.php');
require_once('eventcalendar-functions.php');
require_once('eventcalendar-widget.php');

/**
 * The Event Calendar Wordpress Plugin class.
 * 
 * @author djohnson
 */
class EventCalendarPlugin {

    public function __construct() {
        
        /* Register the custom-post types */
        add_action('init', array('EventCalendarPlugin', 'register_post_types'));
        
        /* Register the custom-post meta */
        add_action('add_meta_boxes', array('EventCalendarPlugin', 'add_event_meta'));
        
        /* Register the custom-post save action */
        add_action('save_post', array('EventCalendarPlugin', 'event_save_postdata'));
        
        /* Register the custom-post colums and column data for list page */
        add_filter("manage_edit-event_columns", array('EventCalendarPlugin', 'event_edit_columns')); 
        add_action("manage_posts_custom_column",  array('EventCalendarPlugin', 'event_custom_columns'));
        
        // add admin scripts
        add_action('admin_enqueue_scripts', array('EventCalendarPlugin', 'admin_scripts'));

        // add calendar widget scripts
        add_action('wp_enqueue_scripts', array('EventCalendarPlugin', 'widget_scripts'));
        
        /* Register the deactivate function */
        register_deactivation_hook(WP_PLUGIN_URL.'/wordpress-eventcalendar/wordpress-eventcalendar.php', array('EventCalendarPlugin', 'eventcalendar_deactivate'));
    }
    
    public function admin_scripts() {
        wp_register_style('jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.11/themes/ui-lightness/jquery-ui.css');
        
        wp_enqueue_style('jquery-ui-css');
            
        wp_register_script('event-calendar-admin-js', WP_PLUGIN_URL.'/wordpress-eventcalendar/js/admin.js', array('jquery-ui-core', 'jquery-ui-datepicker'));
        
        wp_enqueue_script('event-calendar-admin-js');
    }
    
    public function widget_scripts() {
        wp_register_style('event-calendar-css', WP_PLUGIN_URL.'/wordpress-eventcalendar/css/main.css');
        
        wp_enqueue_style('event-calendar-css');
        
        wp_register_script('event-calendar-js', WP_PLUGIN_URL.'/wordpress-eventcalendar/js/main.js', array('jquery'));
        
        wp_enqueue_script('event-calendar-js');
        
        wp_localize_script('event-calendar-js', 'EventCalendarAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    /* Registers the custom post types with WordPress core */
    public function register_post_types() {
        
        $labels = array(
            'name' => _x('Events', 'post type general name'),
            'singular_name' => _x('Event', 'post type singular name'),
            'add_new' => _x('Add New', 'event'),
            'add_new_item' => __('Add New Event'),
            'edit_item' => __('Edit Event'),
            'new_item' => __('New Event'),
            'view_item' => __('View Event'),
            'search_items' => __('Search Events'),
            'not_found' =>  __('No Events found'),
            'not_found_in_trash' => __('No events found in Trash'), 
            'parent_item_colon' => '',
            'menu_name' => 'Events'
        );
        
        register_post_type('event', array(
            'labels' => $labels,
            'public'  => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'menu_position' => 5,
            'rewrite' => array('slug'=>'event'), 
            'supports' => array(
                'title', 'editor','thumbnail'
            )
        ));
        
        /* Flush rewrite rules */
        if (EVENTCALENDAR_FLUSH_URLS) {
            flush_rewrite_rules();
        }
        
        /* Add javascript to plugin pages */
        wp_enqueue_script('jquery-ui');
        wp_enqueue_script('EventCalendarJs');
        
        /* Add css to plugin pages */
        wp_enqueue_style('jquery-ui-css');
        wp_enqueue_style('EventCalendarCSS');
    
    }
    
    /* Adds custom event meta box */
    public function add_event_meta() {
        add_meta_box(
            'event-meta',
            'Event Dates',
            array('EventCalendarPlugin', 'add_event_meta_content'),
            'event',
            'normal'
        );
    }
    
    /* Adds custom event fields */
    public function add_event_meta_content() {
        global $post;
        $custom = get_post_custom($post->ID);
        
        if (isset($_SESSION['eventcalendar_error']['start_date'])) {
            echo '<p class="error">'.$_SESSION['eventcalendar_error']['start_date'].'</p>';
        }
        
        if ($custom['event_start_date'][0] == '') {
            $start_date = '';
        } else {
            $start_date = date('m/d/Y', $custom['event_start_date'][0]);
        }

        echo '<p><label for="event_start_date">Start Date</label> ';
        echo '<input type="text" class="date_field" id="event_start_date" name="event_start_date" value="'.$start_date.'" /></p>';
        
        if (isset($_SESSION['eventcalendar_error']['end_date'])) {
            echo '<p class="error">'.$_SESSION['eventcalendar_error']['end_date'].'</p>';
        }
        
        if ($custom['event_end_date'][0] == '') {
            $end_date = '';
        } else {
            $end_date = date('m/d/Y', $custom['event_end_date'][0]);
        }
        
        echo '<p><label for="event_end_date">End Date</label> ';
        echo '<input type="text" class="date_field" id="event_end_date" name="event_end_date" value="'.$end_date.'" /></p>';
        
        unset($_SESSION['eventcalendar_error']);    
    }
    
    /* Updates or adds meta data on save */
    public function event_save_postdata($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (is_null($_POST['event_start_date']) && is_null($_POST['event_end_date'])) {
            unset($_SESSION['eventcalendar_error']);
            return;
        }
        
        $valid_start = true;
        $valid_end = true;
        
        // check for valid start date format
        if ($_POST['event_start_date'] !== '' && !preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $_POST['event_start_date'])) { 
            $_SESSION['eventcalendar_error']['start_date'] = 'Start date has the wrong format!';
            $valid_start = false;
        }
        
        // make sure the end date isn't before the start date and has valid format        
        if ($_POST['event_end_date'] !== '' && !preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $_POST['event_end_date'])) {
            $_SESSION['eventcalendar_error']['end_date'] = 'End date has the wrong format!';
            $valid_end = false;
        } else if ($_POST['event_end_date'] !== '' && strtotime($_POST['event_end_date']) <= strtotime($_POST['event_start_date'])) {
            $_SESSION['eventcalendar_error']['end_date'] = 'End date must come after the start date!';
            $valid_end = false;
        }
        
        // save start date if valid
        if ($valid_start) {
            update_post_meta($post_id, 'event_start_date', strtotime($_POST['event_start_date']));
        }
        
        // save end date if valid
        if ($valid_end) {
            update_post_meta($post_id, 'event_end_date', strtotime($_POST['event_end_date']));
        }
    }
    
    /* Adds event columns to list page */
    public function event_edit_columns($columns) {
        $columns = array(  
            'cb' => '<input type="checkbox" />',  
            'title' => 'Title',
            'event_start_date' => 'Start Date',
            'event_end_date' => 'End Date',
        );

        return $columns; 
    }
    
    /* Adds event column values to list page */
    public function event_custom_columns($column) { 
        global $post;  
        
        switch ($column)  {  
            case 'event_start_date':
            $custom = get_post_custom($post->ID);
            if ($custom['event_start_date'][0] !== '') {
                echo date('m/d/Y', $custom['event_start_date'][0]);
            }
            break;
        
            case 'event_end_date':
            $custom = get_post_custom();
            if ($custom['event_end_date'][0] !== '') {
                echo date('m/d/Y', $custom['event_end_date'][0]);
            }
            break;
        }
    }
    
    // clean up on deactivate
    public function eventcalendar_deactivate() {
        // delete posts
        $posts = get_posts(array('numberposts' => -1, 'post_type' => 'event'));
        
        foreach($posts as $post) {
            wp_delete_post($post->ID, true);
        }
    }
}

$EventCalendarPlugin = new EventCalendarPlugin();
    
?>