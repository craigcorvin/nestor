<?php

class EmailNotifier extends NotifierType {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Email Notifier (Notifier Type)',
			'description' => 'Notify by Email',
			'category' => 'Notifier'
		);
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

		$config = $this->configuration;
		
		// hook to check user->language, if not eng, update config with translated config instead
		if (isset($vce->site->hooks['translate_config'])) {
			foreach($vce->site->hooks['translate_config'] as $hook) {
				$config = call_user_func($hook, $config, $vce);
			}
		}

		// create email for invitation
		
		$site_email = $vce->site->site_email;
		$site_title = $vce->site->site_title;
		$user_email = $each_user->email;
		$user_name = $each_user->email;
		// if first_name and last_name have been provided
		if (isset($each_user->first_name) || isset($each_user->last_name)) {
			$user_name = '';
			if (isset($each_user->first_name)) {
				$user_name .= $each_user->first_name;
			}
			if (isset($each_user->last_name)) {
				$user_name .= ' ' . $each_user->last_name;
			}
			$user_name = trim($user_name);
		} elseif (isset($each_user->name)) {
			// if a name has been provided
			$user_name = trim($each_user->name);
		}
		if (isset($each_proclamation['subject'])) {
			$email_subject = $each_proclamation['subject'];
		} else {
			$email_subject = $vce->site->site_title . ' Notification';
		}
		
		// attributes to send to mail
		$attributes = array (
		'html' => true,
		'from' => array($site_email, $site_title),
		'to' => array($user_email, $user_name),
		'subject' => $email_subject
		);

		$header = isset($config['email_notifier_header']) ? $config['email_notifier_header'] : '';
		// content
		$body = PHP_EOL;
		if (isset($each_proclamation['content'])) {
			$body .= $each_proclamation['content'];
		} else {
			$content = null;
			foreach ($each_proclamation['event'] as $each_event) {
				// add actor name only once
				if (empty($content)) {
					$content .= $each_event['actor'] . ' has';
				}
				//  creates the wording: "xxx has created a xxx" (within your Coaching Partnership)
				$content .= ' ' . $each_event['verb'] . ' ' . $each_event['object'];
			
			}
			
			//TODO: to translate 'has created a', [object] - e.g shared goal, 'within your Coaching Partnership

			// get the body template
			$body .= isset($config['email_notifier_body']) ? $config['email_notifier_body'] : '{content}';
			
			$body = str_replace('{user}', $user_name, $body);
			$body = str_replace('{content}', $content, $body);
			
			$link = isset($each_proclamation['link']) ? $each_proclamation['link'] : $vce->site->site_url;
			$body = str_replace('{link}', $link, $body);

		}
		$body .= PHP_EOL;
		$footer = isset($config['email_notifier_footer']) ? $config['email_notifier_footer'] : '';

		$attributes['message'] = $header . $body . $footer;

		// send invitation email
		$vce->mail($attributes);
		
	}
	

	/**
	 * hide configuration for component
	 */
	public function component_configuration() {
	
        global $vce;

        $config = $this->configuration;
        
        $elements = null;

        $input = array(
        'type' => 'textarea',
        'name' => 'email_notifier_header',
        'value' => (isset($config['email_notifier_header']) ? str_replace(array('\r\n'), PHP_EOL, stripcslashes($config['email_notifier_header'])) : null),
        'data' => array(
        'rows' => '10'
        )
        );
        
    	$elements .= $vce->content->create_input($input, 'Email Notification Header');
    	
		$email_notifier_header = $config['email_notifier_header'];

		$notifier_email_body = isset($config['email_notifier_body']) ? str_replace(array('\r\n'), PHP_EOL, stripcslashes($config['email_notifier_body'])) : null;

        $input = array(
        'type' => 'textarea',
        'name' => 'email_notifier_body',
        'value' => $notifier_email_body,//(isset($config['email_notifier_body']) ? str_replace(array('\r\n'), PHP_EOL, stripcslashes($config['email_notifier_body'])) : null),
        'data' => array(
        'rows' => '10'
        )
        );
    	$elements .= $vce->content->create_input($input, 'Email Notification Body {user} AND {content}');   
    	 	
		// hook to allow a translated email body input field
		if (isset($vce->site->hooks['translate_email_body_input'])) {
			foreach($vce->site->hooks['translate_email_body_input'] as $hook) {
				$elements .= call_user_func($hook, $notifier_email_body, 'email_notifier_body', $config, $vce);
			}
		}

        $input = array(
        'type' => 'textarea',
        'name' => 'email_notifier_footer',
        'value' => (isset($config['email_notifier_footer']) ? str_replace(array('\r\n'), PHP_EOL, stripcslashes($config['email_notifier_footer'])) : null),
        'data' => array(
        'rows' => '10'
        )
        );
    	$elements .= $vce->content->create_input($input, 'Email Notification Footer');

        return $elements;
        
    }
	



}