<?php

class SiteNotifier extends NotifierType {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Site Notifier (Notifier Type)',
			'description' => 'Notify by Site Message',
			'category' => 'Notifier'
		);
	}
	
	/**
	 * things to do when this component is preloaded
	 */
	public function preload_component() {

		$content_hook = array(
			'content_call_add_functions' => 'SiteNotifier::content_call_add_functions'
		);

		return $content_hook;
	}
	
	public static function content_call_add_functions($vce) {

		/**
		 * display site notifications to the screen
		 *
		 * @param num $limit - set a limit number to be displayed.
		 * @param boolean $viewed - if true, notifications will be marked as viewed
		 * @param boolean $clear - if true, then display a clear button 
		 **/
		$vce->content->notifications = function($limit = null, $viewed = false, $clear = false) {
		
			global $vce;
			
			$content = null;
		
			$base_datalist = $vce->get_datalist_items(array('datalist' => 'notifications_datalist', 'user_id' => $vce->user->user_id));

			if (empty($base_datalist['items'])) {
		
				$no_notifications_text = Notifier::language('No Notifications');

				$content .= <<<EOF
<div class="each-notification-block">
<div class="each-notification-content no-notification-content">
$no_notifications_text
</div>
</div>
EOF;
		
			} else {

				// sort by created_at is desc order
				$items = $vce->sorter($base_datalist['items'], 'created_at', 'desc', 'timestamp');
				
				$counter = 0;
				foreach ($items as $each_notification) {
			
					$counter++;
			
					$link = isset($each_notification['link']) ? $each_notification['link'] : null;
					$subject = isset($each_notification['subject']) ? $each_notification['subject'] : null;
					$subject = Notifier::language($subject);
					$message = isset($each_notification['message']) ? $each_notification['message'] : null;
					$created_at = isset($each_notification['created_at']) ? date("F j, Y, g:i a", $each_notification['created_at']) : null;
					$status = ($each_notification['viewed'] == 'false') ? 'each-notification-unseen' : 'each-notification-viewed';

					$content .= <<<EOF
<div class="each-notification-block $status">
<a class="each-notification-link" href="$link">
<div class="each-notification-content">
<div class="each-notification-date">$created_at</div>
<div class="each-notification-subject">$subject</div>
<div class="each-notification-message">$message</div>
</div>
</a>
</div>
EOF;
					
					// if limit has been set.
					if (isset($limit) && $counter >= $limit) {
						break;
					}
					
					// if viewed is set to true
					if ($viewed && $each_notification['viewed'] == 'false') {
						// set all notifications as read
						$update = array('meta_value' => 'true');
						$update_where = array('item_id' => $each_notification['item_id'],'meta_key' => 'viewed');
						$vce->db->update('datalists_items_meta', $update, $update_where );
					}
		
				}
				
				// if limit has been set.
				if (isset($limit) && $counter >= $limit) {

					$component_name = 'SiteNotifier';
		
					if (isset($vce->site->$component_name)) {
						// get component configuration inforamtion from site object
						$value = $vce->site->$component_name;
						$minutia = $component_name . '_minutia';
						$vector = $vce->site->$minutia;
						$config = json_decode($vce->site->decryption($value, $vector), true);
            
						if (isset($config['site_notifier_url'])) {
	
							$notifications_link = $config['site_notifier_url'];
			
							$view_notifications_text = Notifier::language('View all your notifications');

							$content .= <<<EOF
<div class="each-notification-block">
<a class="each-notification-link" href="$notifications_link">
<div class="each-notification-content">
$view_notifications_text
</div>
</a>
</div>
EOF;

						}
					
					}
		
		
				}
				
				
				if ($clear) {
			
					$dossier_for_clear = $vce->generate_dossier(array('type' => 'SiteNotifier','procedure' => 'clear_notifications','user_id' => $vce->user->user_id));		

					$clear_notifications_text = Notifier::language('Clear Notifications');

					$content .= <<<EOF
<div class="each-notification-block">
<div class="each-notification-content">
<form id="clear-notifications-form" class="asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_clear">
<input class="clear-notifications-button" type="submit" value="$clear_notifications_text">
</form>
</div>
</div>
EOF;

				}
		
			}

			return $content;
		};
	
	}
	
	/**
	 * notify who of what
	 *
	 * @param object $each_user
	 * @param array $each_proclamation
	 *
	 */
	public function notification($each_user, $each_proclamation) {
	
		global $vce;

		// if no user_id has been provided, return
		if (empty($each_user->user_id)) {
			return;
		}
		
		if (isset($each_proclamation['subject'])) {
			$subject = $each_proclamation['subject'];
		} else {
			$subject = 'Notification';
		}
		
		$message = null;
		if (isset($each_proclamation['content'])) {
			$message = $each_proclamation['content'];
		} else {
			foreach ($each_proclamation['event'] as $each_event) {
				// add actor name only once
				if (empty($message)) {
					$message .= $each_event['actor'] . ' has';
				}
				
				$message .= ' ' . $each_event['verb'] . ' ' . $each_event['object'];
			
			}

		}
		
		// add link
		$link = isset($each_proclamation['link']) ? $each_proclamation['link'] : $vce->site->site_url;

		$base_datalist = $vce->get_datalist_items(array('datalist' => 'notifications_datalist','user_id' => $each_user->user_id));

		// check if a datalist has been created for this user, if not create one
		if (empty($base_datalist)) {
		
			$create_attributes = array(
			'datalist' => 'notifications_datalist',
			'user_id' => $each_user->user_id
			);

			$datalist_id = $vce->create_datalist($create_attributes);
		
		} else {
		
			$datalist_id = $base_datalist['datalist_id'];
		
		}
		
		$attributes = array(
		'datalist_id' => $datalist_id,
		'subject' => $subject,
		'message' => $message,
		'link' => $link,
		'created_at' => time(),
		'viewed' => 'false'
		);
		
		$vce->add_datalist_item($attributes);

	}
	
	
	/**
	 * Deals with asynchronous form input 
	 * This is called from input portal forward onto class and function of component
	 * @param array $input
	 * @return calls component's procedure or echos an error message
	 */
	public function form_input($input) {

		// save these two, so we can unset to clean up $input before sending it onward
		//$type = trim($input['type']);
		$procedure = trim($input['procedure']);
		
		// unset component and procedure
		unset($input['procedure']);
		
		// check that protected function exists
		if (method_exists($this, $procedure)) {
			// call to class and function
			return $this->$procedure($input);
		}
		
		echo json_encode(array('response' => 'error','message' => 'Unknown Procedure'));
		return;
	}
	
	
	/**
	 * procedure to see if any new notifications have been created
	 */
	public function check_notification($input) {
	
		if (!isset($input['user_id'])) {
			echo json_encode(array('response' => 'error','procedure' => 'notifications','message' => 'no user id'));
			return;
		}
	
		global $vce;
		
		$base_datalist = $vce->site->get_datalist_items(array('datalist' => 'notifications_datalist', 'user_id' => $input['user_id']));
		
		$new_notifications = 0;
		
		if (isset($base_datalist['items'])) {
			foreach ($base_datalist['items'] as $each_item) {
		
				if ($each_item['viewed'] == 'false') {
		
					$new_notifications++;
		
				}
		
			}
		}
		
	
		echo json_encode(array('response' => 'success','procedure' => 'notifications','message' => $new_notifications));
		return;
	
	}
	
	/**
	 * procedure to clear out notifications from datalist
	 */
	public function clear_notifications($input) {
	
		global $vce;
	
		if (!isset($input['user_id'])) {
			echo json_encode(array('response' => 'error','procedure' => 'clear','message' => 'no user id'));
			return;
		}
		
		$base_datalist = $vce->get_datalist_items(array('datalist' => 'notifications_datalist', 'user_id' => $input['user_id']));
		
		if (isset($base_datalist['items'])) {
			foreach ($base_datalist['items'] as $each_item) {
				
				if (isset($each_item['item_id'])) {
			
					// delete from vce_datalists_items
					$where = array('item_id' => $each_item['item_id']);
					$vce->db->delete('datalists_items', $where);
		
					// delete from vce_datalists_items_meta
					$where = array('item_id' => $each_item['item_id']);
					$vce->db->delete('datalists_items_meta', $where);
				
				}
		
			}
		}

		echo json_encode(array('response' => 'success'));
		return;

	}
	
	
	/**
	 * component_configuration
	 */
	public function component_configuration() {
	
        global $vce;

        $config = $this->configuration;
        
        $elements = null;

        $input = array(
        'type' => 'text',
        'name' => 'site_notifier_url',
        'value' => (isset($config['site_notifier_url']) ? $config['site_notifier_url'] : null),
        );
        
    	$elements .= $vce->content->create_input($input, 'Site Notifier Url');

        return $elements;
        
    }


}