<?php

class Notifier extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Notifier',
			'description' => 'Component to send notifications',
			'category' => 'Notifier',
			'recipe_fields' => false
		);
	}

	/**
	 * things to do when this component is preloaded
	 */
	public function preload_component() {
		
		$content_hook = array (
		'site_hook_initiation' => 'Notifier::require_once_notifiertype',
		'components_notify_method' => 'Notifier::components_notify_method',
		'user_settings_as_content' => 'Notifier::user_settings_as_content'
		);

		return $content_hook;

	}

	/**
	 * loads the NotificationsType parent class before the children classes are loaded
	 */
	public static function require_once_notifiertype($vce) {
	
		// path to mediatype.php
		require_once(dirname(__FILE__) . '/notifiertype/notifiertype.php');	

	}
	

	/**
	 * adds notifications options within user settings
	 */
	public static function user_settings_as_content() {
	
		global $vce;
		
		// get all activated_components
		$activated_components = json_decode($vce->site->activated_components, true);
		
		if (!empty($activated_components)) {
			ksort($activated_components);
		}
		
		$enabled_notifiertype = json_decode($vce->site->enabled_notifiertype, true);
		
		// disabled
		if (empty($enabled_notifiertype)) {
			return;
		}
		
		ksort($enabled_notifiertype);
		
		// add javascript
		$vce->site->add_script(dirname(__FILE__) . '/js/script.js', 'jquery');
		
		// add stylesheet
		$vce->site->add_style(dirname(__FILE__) . '/css/style.css','notifier-style');
		
		// get current user notifier options
		$notifier_options = isset($vce->user->notifier_options) ? json_decode($vce->user->notifier_options, true) : null;
		
		foreach ($enabled_notifiertype as $notifier_name=>$notifier_path) {

			// require each
			require_once(BASEPATH . $notifier_path);
	
			// instantiate and add to array of notifiers
			$notifiertype[$notifier_name] = new $notifier_name();
			
			// add description
			$notifiertype[$notifier_name]->description = $notifiertype[$notifier_name]->component_info()['description'];

			// add a place for content
			$notifiertype[$notifier_name]->notifier_content = '
			<div class="notifier-type">
			<div class="notifier-type-title">' . Notifier::language($notifiertype[$notifier_name]->description) . '</div>';

			$name = $notifier_name;

			$selected = 'on';

			// set selected option if one exists
			if (isset($notifier_options[$notifier_name]) && !is_array($notifier_options[$notifier_name])) {
				$selected = $notifier_options[$notifier_name];
			}

			// radio button
			$input = array(
				'type' => 'radio',
				'name' => $name,
				'data' => array(
				'class' => 'notifier-type-toggle'
				),
				'options' => array(
					array(
						'name' => $name,
						'value' => 'on',
						'label' => Notifier::language(' On '),
						'selected' => (($selected == 'on') ? true : false)
					),
					array(
						'name' => $name,
						'value' => 'off',
						'label' => Notifier::language(' Off '),
						'selected' => (($selected == 'off') ? true : false)
					)
				)
			);			

			$notifiertype[$notifier_name]->notifier_content .= '<div class="notifier-type-item"><div class="notifier-type-input">' . $vce->content->input_element($input) . '</div><div class="notifier-type-text">'. Notifier::language('All notifications of this type') . '</div></div>';

			$notifiertype[$notifier_name]->notifier_content .= '<div class="notifier-type-options notifier-type-options-' . $selected . '">';

			$notifiertype[$notifier_name]->notifier_content .= '<div class="notifier-type-specific">' . Notifier::language('Select specific notifications of this type') . '</div>';

		}
				 
		$notifier_content = null;
		
		// dossier for sort up and down
		$dossier = array(
		'type' => 'Notifier',
		'procedure' => 'settings'
		);
	
		// generate dossier
		$dossier_for_notifier = $vce->generate_dossier($dossier);
		
		$notifier_content .= <<<EOF
<form id="update" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_notifier">
EOF;

		foreach ($activated_components as $each_type=>$each_path) {
		
			// require each
			require_once(BASEPATH . $each_path);
			
			// instantiate and add to array of notifiers
			$notifiers[$each_type] = new $each_type();
			
			if (method_exists($notifiers[$each_type], 'notify')) {
			
				$options = $notifiers[$each_type]->notify();
				
				// we have options. 
				if (!empty($options)) {
				
					foreach ($notifiertype as $notifier_name=>$notifier_object) {
									
						// get comonent information
						$component_info = $notifiers[$each_type]->component_info();

						// 
						$notifiertype[$notifier_name]->notifier_content .= '<div class="notifier-type-component">' . Notifier::language($component_info['name']) . '</div>';
					
						foreach ($options as  $option_name=>$option_text) {
						
							$name = $notifier_name . '|' . $each_type . '|' . $option_name;

							$selected = 'on';

							// set selected option if one exists
							if (isset($notifier_options[$notifier_name][$each_type][$option_name])) {
								$selected = $notifier_options[$notifier_name][$each_type][$option_name];
							}

							// radio button
							$input = array(
								'type' => 'radio',
								'name' => $name,
								'options' => array(
									array(
										'name' => $name,
										'value' => 'on',
										'label' => Notifier::language(' On '),
										'selected' => (($selected == 'on') ? true : false)
									),
									array(
										'name' => $name,
										'value' => 'off',
										'label' => Notifier::language(' Off '),
										'selected' => (($selected == 'off') ? true : false)
									)
								)
							);

							$notifiertype[$notifier_name]->notifier_content .= '<div class="notifier-type-item"><div class="notifier-type-input">' . $vce->content->input_element($input) . '</div><div class="notifier-type-text">' . Notifier::language($option_text) . '</div></div>';
			
						}
					
					}
				
				}
			
			}
		
		}
		
		
		// add content now that it has been sorted
		foreach ($notifiertype as $notifier_name=>$notifier_object) {
			$notifier_content .= $notifiertype[$notifier_name]->notifier_content;
			
			// close <div class="notifier-type-options">
			$notifier_content .= '</div>';
			
			// close <div class="notifier-type">
			$notifier_content .= '</div>';
		}

		$lang_update = Notifier::language('Update');
		$lang_cancel = Notifier::language('Cancel');

		$notifier_content .= <<<EOF
<input type="submit" value={$lang_update}>
<button class="link-button cancel-button">{$lang_cancel}</button>
</form>
EOF;
	
		$content = $vce->content->accordion(Notifier::language('Notification Settings'), $notifier_content, true, true);

		$vce->content->add('main', $content);

	}
	
	/*
	 * This method can be called directly to send out any email using the EmailNotifier minion
	 * 
	 * $proclamation[] = array(
	 * 'type' => 'EmailNotifier',
	 * 'recipient' => array(array('email' => *email*, 'name' => *name*)),
	 * 'subject' => '*email-subject*',
	 * 'event' => array(), // needs to be provide 
	 * 'content' => html_entity_decode('*email-body*')
	 * );
	 */
	public static function components_notify_method($proclamation = array()) {
	
		global $vce;

		$notify_types = json_decode($vce->site->enabled_notifiertype, true);
		
		// cycle through all enabled notify types
		foreach ($notify_types as $notify_type=>$notify_path) {
		
			// require each
			require_once(BASEPATH . $notify_path);
			
			// instantiate and add to array of notifiers
			$notifiers[$notify_type] = new $notify_type();
		
		}
		
		// check for multiple proclamations
		foreach ($proclamation as $each_proclamation) {

			// $each_proclamation = array(
			// // component type is needed if you are allowing for notifications options
			//'component' => get_class($this),
			// // add NotifierType if you want to only trigger that type of notification
			// 'type' => 'SiteNotifier',
			// // announcement is needed if you are allowing for notifications options
			//'announcement' => 'notify_on_reply',
			// //recipents can be provided as array if email address are provided or comma delinated user_id
			//'recipient' => user_id,
			// or
			//'recipient' => array(array('email' => *email*, 'name' => *name*)),
			// //event is required, but you can supply an empty arrray()
			// // // $event = array(
			// // // 'actor' => 'name of user that did action',
			// // // 'action' => 'delete',
			// // // 'verb' => 'deleted',
			// // // 'object' => 'a comment'
			// // // );
			//'event' => $event_array,
			// //the url to view where the event took place
			//'link' => $link,
			// //a subject can be supplied
			//'subject' => 'Our Notification'
			// //content will override everything in the event array
			//'content' => 'testing'
			// );
	
			// recipient and event are both required to notify
			if (!isset($each_proclamation['recipient']) || !isset($each_proclamation['event'])) {
				continue;
			}
			
			// normalize the event into an array of array
			if (!isset($each_proclamation['event'][0])) {
				$each_proclamation['event'] = array($each_proclamation['event']);
			}
			
			$recipent = $each_proclamation['recipient'];
			
			$find = false;
			
			// looking for either array(array('email')) or comma delinated list such as 1,2,4,6
			if (is_array($recipent)) {
				if (is_object($recipent[0])) {
					// and object is an object
					$find_users = $recipent;
				} elseif (is_array($recipent[0])) {
					// we are expecting an object later so cast is as that
					foreach ($recipent as $each_recipent) {
						$find_users[] = (object) $each_recipent;
					}
				}
			} else {
				// test if this is a proper
				// check if value is an array
				if (is_numeric($recipent)) {
					$find = true;
				} else {
					$explode =  explode(',', $recipent);
					if (is_array($explode)) {
						$numeric = true;
						foreach ($explode as $each_explode) {
							if (!is_numeric($each_explode)) {
								$numeric = false;
								break;
							}
						}
						if ($numeric) {
							$find = true;
						}
					}
				}
				
				// find users based on user_id
				if ($find) {
					$find_users = $vce->user->find_users(array('users_id' => $recipent));
				}
				
			}
			
			// safety check
			if (empty($find_users)) {
				return false;
			}

			if (!empty($notifiers)) {
				// cycle notifiers
				foreach ($notifiers as $notifier_name=>$notifier_object) {
	
					// check if an NotifierType has been specifically referenced
					if (isset($each_proclamation['type']) && $notifier_name != $each_proclamation['type']) {
						continue;
					}

					foreach($find_users as $each_user) {
			
						if (isset($each_user->notifier_options)) {
								
							// $user->notifier_options will look like the following
							//
							// $notify_options = array(
							// '*notifier type*' => array(
							// '*component type*' => array(
							// '*announcement' => 'off'
							// )
							// )
							// );
					
							$notify_option = json_decode($each_user->notifier_options, true);
						
							if ($notifier_object->opt_method() == 'out') {
						
								if (isset($notify_option[$notifier_name])) {
				
									// type level
									$current = $notify_option[$notifier_name];
						
									// if notify_option has the entire nofifier turned off, continue
									if (!is_array($current) && $current == 'off') {
										continue;
									}
						
									if (isset($current[$each_proclamation['component']])) {
						
										// component level
										$component = $current[$each_proclamation['component']];
						
										// if component is set to off, continue
										if (!is_array($component) && $component == 'off') {
											continue;
										}
							
										if (isset($each_proclamation['announcement'])) {
							
											$announcement = $each_proclamation['announcement'];
							
											if (isset($component[$announcement]) && $component[$announcement] == 'off') {
												continue;
											}
							
										}
							
									}
							
								}
							
							} elseif ($notifier_object->opt_method() == 'in') {
						
								$prevent = true;
						
								// build this out later when needed
							
								if ($prevent) {
									continue;
								}
							
							}

				
						}

						// who of what?
						$notifier_object->notification($each_user, $each_proclamation);
			
					}
				
				}
				// end cycle
			}
		
		}
	
	}
	

	/**
	 * add / update enabled_notifiertype for user
	 */
	public function settings($input) {
	
		$vce = $this->vce;
	
		// clean-up
		unset($input['type']);
		
		$notify_types = json_decode($vce->site->enabled_notifiertype, true);
		
		// cycle through all enabled notify types
		foreach ($notify_types as $notify_type=>$notify_path) {
		
			// require each
			require_once(BASEPATH . $notify_path);
			
			// instantiate and add to array of notifiers
			$notifiers[$notify_type] = new $notify_type();
			
			// add the opt method, which will be either out or in
			$notifiers[$notify_type]->opt_method = $notifiers[$notify_type]->opt_method();
		
		}
		
		$options = array();
		
		foreach ($input as $name=>$value) {
		
			// NotifierType | Component Type | Option
			$map = explode('|', $name);
			
			// if out and off
			if ($notifiers[$map[0]]->opt_method == 'out' && $value == 'off') {
				if (isset($map[2])) {
					if (empty($options[$map[0]]) || is_array($options[$map[0]])) {
						// add to options array
						$options[$map[0]][$map[1]][$map[2]] = $value;
					}
				} else {
					if (isset($map[1])) {
						$options[$map[0]][$map[1]] = $value;
					} else {
						$options[$map[0]] = $value;
					}
				}
			} elseif ($notifiers[$map[0]]->opt_method == 'in' && $value == 'on') {
				// add to options array
				$options[$map[0]][$map[1]][$map[2]] = $value;
			}
			
		}
		
		if (!empty($options)) {
			
			$notifier_options = json_encode($options);
			
			$vce->user->add_attributes('notifier_options', $notifier_options, true);

		} else {
		
			if (isset($vce->user->notifier_options)) {
			
				$vce->user->remove_attributes('notifier_options', true);
			
			}
		
		}
		
		echo json_encode(array('response' => 'success', 'message' => 'Notifications Settings Updated'));
		return;
	
	}

}