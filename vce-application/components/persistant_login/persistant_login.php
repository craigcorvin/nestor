<?php

class PersistantLogin extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Persistant Login',
			'description' => 'Component to add persistant login.',
			'category' => 'Admin User Sessions',
			'recipe_fields' => false
		);
	}

	// default values
	// number of time units
	static $time_unit_increment = 7;
	// time unit: minutes, hours
	static $time_unit = 'days';


	/**
	 * Component has been disabled, remove persistent_login records for all users
	 */
	public function disabled() {
	
		global $vce;
		
		$update = array('meta_value' => '[]');
		$update_where = array('meta_key' => 'persistent_login');
		$update = $vce->db->update('users_meta', $update, $update_where);
		
	}

	/**
	 * things to do when this component is preloaded
	 */
	public function preload_component() {
		
		$content_hook = array (
		'user_alternative_login' => 'PersistantLogin::user_alternative_login',
 		'user_store_session_override' => 'PersistantLogin::user_store_session_override',
 		'user_logout_override' => 'PersistantLogin::user_logout_override'
		);

		return $content_hook;

	}
	
	
	public static function user_alternative_login($vce) {
	
        // check if persistent login cookie exists
		// sometimes $_COOKIE['_pl'] returns "deleted" when using Safari
		if (isset($_COOKIE['_pl']) && strlen($_COOKIE['_pl']) > 10) {
		
			// get cookie data
			$cookie_value = hex2bin($_COOKIE['_pl']);

			$length = $vce->site->vector_length();

			$vector = base64_encode(substr($cookie_value, 0, $length));
			$encrypted = base64_encode(substr($cookie_value, $length));

			$decrypted = $vce->site->decryption($encrypted, $vector);

			// create hash value
			$pl_hash = hash('sha256', $decrypted);
			
			// save cookie hash to object so it can be deleted when new one is set
			$vce->pl_hash = $pl_hash;

			// search for persistent_login value that matches cookie value
			$query = "SELECT * FROM " . TABLE_PREFIX . "users_meta WHERE meta_key='persistent_login' AND meta_value LIKE '%" . $pl_hash . "%'";
			$persistent_login = $vce->db->get_data_object($query);

			// value has been found
			if (isset($persistent_login[0]->meta_value)) {

				$persistent_login_values = json_decode($persistent_login[0]->meta_value);

				// cycle though to make sure that the match is true
				foreach ($persistent_login_values as $each_time_stamp => $each_time_hash) {

					if ($each_time_hash == $pl_hash) {
					
						// we can check if $decrypted starts with the user_id as a double check, though that is probably not needed

						// create user object for the user_id of the persistent_login match
						$vce->user->make_user_object($persistent_login[0]->user_id);

						// load login hook
						// at_user_login
						if (isset($vce->site->hooks['user_at_login'])) {
							foreach ($vce->site->hooks['user_at_login'] as $hook) {
								call_user_func($hook, $persistent_login[0]->user_id);
							}
						}
						
						// a good persistent login, return to exit this method
						return $vce->user;

					}

				}

			}

		}
		
	}
	
	
	public static function user_store_session_override($user_object, $vce) {
	
		// if a session exists, then cycle to a new one when login happens
	
        // create persistent login cookie
        if (!empty($vce->user->user_id)) {
        
       		// create new cookie data
            
            // create random value and prepend it with user__id
            // $pl_value = $vce->user->user_id . '-' . bin2hex(random_bytes(5));
            // lower cost option
            $pl_value = $vce->user->user_id . '-' . time();

            $vector = $vce->site->create_vector();

            $encrypted = $vce->site->encryption($pl_value, $vector);

            // cookie value
            $cookie_value = bin2hex(base64_decode($vector) . base64_decode($encrypted));
            
            // SameSite value default is Strict
            $same_site = 'Strict';
            
            // check for https within site_url
            if (parse_url($vce->site->site_url, PHP_URL_SCHEME) == "https") {
				$cookie_secure = "; Secure";
				$same_site = 'None';
				// SameSite=None
            } else {
          	  	$cookie_secure = null;
				// SameSite=Strict
            }

            // get url path
            $url_path = parse_url($vce->site->site_url, PHP_URL_PATH);
            // if this has a value, set cookie_path
            if (empty($url_path)) {
                $url_path = '/';
            }
            
            $configuration = self::get_config($vce);
            
            // expires
            $expires = '+';
			$expires .= isset($configuration['time_unit_increment']) ? $configuration['time_unit_increment'] : self::$time_unit_increment;
            $expires .= ' ';
			$expires .= isset($configuration['time_unit']) ? $configuration['time_unit'] : self::$time_unit;
            
            $expires_time = strtotime($expires);
            
			// format date for cookie Expires
			// Wed, 25 Mar 2020 16:46:58 GMT
			$cookie_expires = gmdate('D, d M Y H:i:s', $expires_time) . ' GMT';
			
			$cookie = "_pl=" . $cookie_value . "; Expires=" . $cookie_expires . "; Path=" . $url_path . $cookie_secure . "; HttpOnly; SameSite=" . $same_site;
			
			// false flag allows for multiples of this header
            header("Set-Cookie: " . $cookie, false);

            // get hash value for time
            $pl_hash = hash('sha256', $pl_value);

            // search for persistent_login for this user_id
            $query = "SELECT * FROM " . TABLE_PREFIX . "users_meta WHERE meta_key='persistent_login' AND user_id='" . $vce->user->user_id . "'";
            $persistent_login = $vce->db->get_data_object($query);

            // update if record is found, else create a new record
            if (!empty($persistent_login[0]->meta_value)) {

                $persistent_login_values = json_decode($persistent_login[0]->meta_value, true);
                
                // expired by date
           		$current_time = time();

                // cycle through current persistent_login to remove old records
                foreach ($persistent_login_values as $each_time_stamp => $each_time_hash) {
                
                    if (!is_numeric($each_time_stamp)) {
                        // clean up the persistent_login by removing old records
                        unset($persistent_login_values[$each_time_stamp]);
                    }

                    if ($each_time_stamp < $current_time) {
                        // clean up the persistent_login by removing old records
                        unset($persistent_login_values[$each_time_stamp]);
                    }

                    if ($each_time_hash == $vce->pl_hash) {
                        // clean up the persistent_login by removing previous cookie value
                        unset($persistent_login_values[$each_time_stamp]);
                    }

                }

                // clean up object
                unset($vce->pl_hash);

                // add new persistent_login value
                $persistent_login_values[$expires_time] = $pl_hash;

                $update = array('meta_value' => json_encode($persistent_login_values));
                $update_where = array('user_id' => $vce->user->user_id, 'meta_key' => 'persistent_login');
                $update = $vce->db->update('users_meta', $update, $update_where, 1);

            } else {

                // add new persistent_login value
                $persistent_login_values[$expires_time] = $pl_hash;

                $user_data = array(
                    'user_id' => $vce->user->user_id,
                    'meta_key' => 'persistent_login',
                    'meta_value' => json_encode($persistent_login_values),
                    'minutia' => 'false',
                );
                $vce->db->insert('users_meta', $user_data);

            }
            
        }
        
        if (isset($vce->user->session_id)) {
        	return $vce->user->session_id;
        }
        	
        return false;
        
	}


	public static function user_logout_override($user_id) {
	
		global $vce;
	
	        // check if persistent login cookie exists
		if (isset($_COOKIE['_pl'])) {

			// get cookie data
			$cookie_value = hex2bin($_COOKIE['_pl']);

			$length = $vce->site->vector_length();

			$vector = base64_encode(substr($cookie_value, 0, $length));
			$encrypted = base64_encode(substr($cookie_value, $length));

			$decrypted = $vce->site->decryption($encrypted, $vector);

			//create hash value of time

			$time_hash = hash('sha256', $decrypted);

			// delete persistent login cookie
			unset($_COOKIE['_pl']);

			// check for https within site_url
			if (parse_url($vce->site->site_url, PHP_URL_SCHEME) == "https") {
				$cookie_secure = true;
			} else {
				$cookie_secure = false;
			}

			// get url path
			$url_path = parse_url($vce->site->site_url, PHP_URL_PATH);
			// if this has a value, set cookie_path
			if (empty($url_path)) {
				$url_path = '/';
			}
			
			// SameSite value default is Strict
			$same_site = 'Strict';
		
			// check for https within site_url
			if (parse_url($vce->site->site_url, PHP_URL_SCHEME) == "https") {
				$cookie_secure = "; Secure";
				$same_site = 'None';
				// SameSite=None
			} else {
				$cookie_secure = null;
				//  SameSite=Strict
			}
	
			// 7 days in the past
			$expires_time = time() - 604800;
		
			// Wed, 25 Mar 2020 16:46:58 GMT
			$cookie_expires = gmdate('D, d M Y H:i:s', $expires_time) . ' GMT';
			
			$cookie_value = '';
			
			// Max-Age=0 should expire the cookie immediately.
			
			$cookie = "_pl=" . $cookie_value . "; Expires=" . $cookie_expires . "; Max-Age=0; Path=" . $url_path . $cookie_secure . "; HttpOnly; SameSite=" .  $same_site;
			
			// false flag allows for multiples of this header
			header("Set-Cookie: " .  $cookie, false);

			// set cookie to clear
			// setcookie('_pl', '', time() - 42000, $url_path, '', $cookie_secure, true);

			// search for persistent_login for this user_id
			$query = "SELECT * FROM " . TABLE_PREFIX . "users_meta WHERE meta_key='persistent_login' AND user_id='" . $user_id . "'";
			$persistent_login = $vce->db->get_data_object($query);

			// update if record is found, else create a new record
			if (isset($persistent_login[0]->meta_value)) {

				$persistent_login_values = json_decode($persistent_login[0]->meta_value, true);

				// check that array has elements
				if (!empty($persistent_login_values)) {

					// cycle through current persistent_login to remove old records
					foreach ($persistent_login_values as $each_time_stamp => $each_time_hash) {

						if ($each_time_hash == $time_hash) {

							// clean up the persistent_login by removing previous cookie value
							unset($persistent_login_values[$each_time_stamp]);

						}

					}

					$update = array('meta_value' => json_encode($persistent_login_values));
					$update_where = array('user_id' => $user_id, 'meta_key' => 'persistent_login');
					$update = $vce->db->update('users_meta', $update, $update_where, 1);

				}

			}

		}
		
    }
    
    
	/**
	 * add config info for this component
	 */
	public function component_configuration() {
	
		global $vce;
		$configuration = null;
		
		$time_unit_increment_value = isset($this->configuration['time_unit_increment']) ? $this->configuration['time_unit_increment'] :  self::$time_unit_increment;
		
		$input = array(
		'type' => 'text',
		'name' => 'time_unit_increment',
		'value' => $time_unit_increment_value
		);
		
		$configuration .= $vce->content->create_input($input,'Time Unit Increment');

		$input = array(
		'type' => 'select',
		'name' => 'time_unit',
		);
		
		$time_unit_value = isset($this->configuration['time_unit']) ? $this->configuration['time_unit'] : self::$time_unit;
		
		foreach (array('seconds','minutes','hours','days') as $each_unit) {
			$input['options'][] = array(
				'name' => $each_unit,
				'value' => $each_unit,
				'selected' =>  $time_unit_value == $each_unit ? true : false
			);
		}
		
		$configuration .= $vce->content->create_input($input,'Time Unit');
		
		return $configuration;
	
	}
	
}