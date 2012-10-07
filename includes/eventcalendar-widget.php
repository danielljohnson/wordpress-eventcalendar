<?php

class EventCalendarWidget extends WP_Widget {
	
	private $yearToday;
	private $monthToday;
	private $dayToday;
    
    private $year;
    private $month;
	
	public function EventCalendarWidget() {
		parent::WP_Widget(false, $name = 'Event Calendar', $widget_options = array('description' => 'Event Calendar'));
		
		$this->yearToday = date('Y');
		$this->monthToday = date('m');
		$this->dayToday = date('j');
        
        // add actions for last and next month links
        add_action('wp_ajax_nopriv_test_ajax', array($this, 'test_ajax'));
        add_action('wp_ajax_test_ajax', array($this, 'test_ajax'));
	}
    
    public function test_ajax() {
        $url = explode('?', $_POST['url']);
        
		parse_str($url[1], $vals);
        
        echo $this->build_calendar($vals['cyear'], $vals['cmonth']);
    
        exit();
    }
    
    public function events_query($query) {
        $year = is_null($this->year) ? date('Y') : $this->year;
        $month = is_null($this->month) ? date('Y') : $this->month;
        
        $first_day_in_month = mktime(0, 0, 0, $month, 1, $year);
        $last_day_in_month = mktime(0, 0, 0, $month+1, 0, $year);
        
        $meta = array(
            array(
                'key' => 'event_start_date',
                'value' => array($first_day_in_month, $last_day_in_month),
                'type' => 'numeric',
                'compare' => 'BETWEEN'
            )
        );
        
        $query->set('meta_query',$meta);
        
        return $query;
    }
	
	/**
	 * Gets single and multi-day events and puts them into an array with their post id
	 *
	 * @param string $year 
	 * @param string $month 
	 * @return array An array containing event dates and post ids.
	 */
	private function get_events($year, $month) {
        $this->year = $year;
        $this->month = $month;
        
        add_filter('pre_get_posts', array($this, 'events_query'));
        
		$posts = get_posts(array('numberposts' => -1, 'post_type' => 'event', 'suppress_filters' => FALSE));
        
        remove_filter('pre_get_posts', array($this, 'events_query'));
        
        //var_dump($posts);
		
		$events = array();
		
		foreach($posts as $post) {
			$start_date = get_post_meta($post->ID, 'event_start_date', true);
			$end_date = get_post_meta($post->ID, 'event_end_date', true);
			
			array_push($events, array('post_id' => $post->ID, 'date' => $start_date));
			
			// if there is an end date, find dates between the start date up to and including the end date
			if ($end_date != '') {
                $num_days = abs($start_date - $end_date)/60/60/24;
                
                for ($i = 1; $i <= $num_days; $i++) {
                    array_push($events, array('post_id' => $post->ID, 'date' => strtotime("+{$i} day", $start_date)));
                }
			}
		}
		
		return $events;
	}
	
	/**
	 * Builds the URL for the previous month based on whether there is an existing
	 * query string or not.
	 *
	 * @param string $month 
	 * @param string $year 
	 * @return string The new query string.
	 */
	private function cal_previous($month, $year) {
		if ($month == 1) {
			$month = 12;
			$year--;
		} else {
			$month--;
		}
		
		if (intval($month) < 10) {
			$month = '0' . $month;
		}
		
		if (empty($_SERVER['QUERY_STRING'])) {
			$previous = $_SERVER['REQUEST_URI'] . '?cmonth=' . $month . '&cyear=' . $year;
		} else {
			$query_string = $_SERVER['QUERY_STRING'];
			parse_str($query_string, $vals);
			$vals['cmonth'] = $month;
			$vals['cyear'] = $year;
			
			$new_query_string = http_build_query($vals);
			
			$previous = $_SERVER['PHP_SELF'] . '?' . $new_query_string;
		}

		return $previous;
	}
	
	/**
	 * Builds the URL for the previous month based on whether there is an existing
	 * query string or not.
	 *
	 * @param string $month 
	 * @param string $year 
	 * @return string The new query string.
	 */
	private function cal_next($month, $year) {
		if ($month == 12) {
			$month = 1;
			$year++;
		} else {
			$month++;
		}
		
		if (intval($month) < 10) {
			$month = '0' . $month;
		}
		
		if (empty($_SERVER['QUERY_STRING'])) {
			$next = $_SERVER['REQUEST_URI'] . '?cmonth=' . $month . '&cyear=' . $year;
		} else {
			$query_string = $_SERVER['QUERY_STRING'];
			
			parse_str($query_string, $vals);
			$vals['cmonth'] = $month;
			$vals['cyear'] = $year;
			
			$new_query_string = http_build_query($vals);
			
			$next = $_SERVER['PHP_SELF'] . '?' . $new_query_string;
		}
		
		return $next;
	}
	
	/**
	 * Builds the calendar HTML to be output in the widget
	 *
	 * @param string $year 
	 * @param string $month 
	 * @return string The HTML for the calendar.
	 */
	private function build_calendar($year, $month) {
		$daysInMonth = date("t", mktime(0,0,0,$month,1,$year));
		$firstDay = date("w", mktime(0,0,0,$month,1,$year));
		$tempDays = $firstDay + $daysInMonth;
		$weeksInMonth = ceil($tempDays/7);

		$counter = 0;

		for ($j=0; $j<$weeksInMonth; $j++) {
	    	for ($i=0; $i<7; $i++) {
	        	$counter++;
	        	$week[$j][$i] = $counter;
	        	$week[$j][$i] -= $firstDay;
	        	if (($week[$j][$i] < 1) || ($week[$j][$i] > $daysInMonth)) {    
	            	$week[$j][$i] = "";
	        	}
	    	}
		}

		$output = '<table id="event_calendar">';
		$output .= '<tr><th class="header" colspan="7"><a id="left_arrow" href="'.$this->cal_previous($month, $year).'"><<</a>';
		$output .= date('F', mktime(0,0,0,$month,1,$year)) . ' ' . $year;
		$output .= '<a id="right_arrow" href="'.$this->cal_next($month, $year).'">>></a></th></tr>';
		$output .= '<tr><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thur</th><th>Fri</th><th>Sat</th></tr>';
		
		$events = $this->get_events($year, $month);

		foreach ($week as $key => $val) {
			
			$output .= "<tr>";
            
			for ($i = 0; $i < 7; $i++) {
				
				$event_id = null;
				
				// test for event
				foreach($events as $event) {
					// get the date as a timestamp
                    $date = mktime(0, 0, 0, $month, intval($val[$i]), $year);
					
					// test to see if it exits in the $events array
					if ($event['date'] == $date) {
						$event_id = $event['post_id'];
					}
				}
				
				if ($val[$i] == "") {
					$output .= "<td class='blank'>" . $val[$i] . "</td>";
				} else if ($event_id !== null) {
					$output .= "<td class='event'><a href=\"".get_permalink($event_id)."\">" . $val[$i] . "</a></td>";
				} else if ($val[$i] == date('j') && $month == date('m') && $year == date('Y')) {
					$output .= "<td class='today'>" . $val[$i] . "</td>";
				} else {
					$output .= "<td class='day'>" . $val[$i] . "</td>";
				}
			}
			$output .= "</tr>";
		}

		$output .= '</table>';

		return $output;
	}

	public function form($instance) {
		
		$title = strip_tags($instance['title']);

		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>">Title:</label> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></p>
		
		<?php
	}

	public function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		
		return $instance;
	}

	public function widget($args, $instance) {		
		extract($args);
        
		$title = apply_filters('widget_title', $instance['title']);
 		
		echo $before_widget;
		
		if ($title) {
			echo $before_title . $title . $after_title;
		}
		
		// start content
		
        echo '<div id="event_calendar_wrap">';
        
		if (isset($_GET['cmonth'])) {
			echo $this->build_calendar($_GET['cyear'], $_GET['cmonth']);
		} else {
			echo $this->build_calendar($this->yearToday, $this->monthToday);
		}
        
        echo '</div>';
		
		// end content
		
		echo $after_widget;
    }	

}

add_action('widgets_init', create_function('', 'return register_widget("EventCalendarWidget");'));

?>