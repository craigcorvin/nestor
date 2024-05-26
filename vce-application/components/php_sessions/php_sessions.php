<?php

class PHPSessions extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'PHP Sessions',
			'description' => 'Component to store sessions in the standard PHP way.',
			'category' => 'Admin User Sessions',
			'recipe_fields' => false
		);
	}

	// default values
	// number of time units
	static $time_unit_increment = 1;
	// time unit: minutes, hours
	static $time_unit = 'hours';

	/**
	 * things to do when this component is preloaded
	 */
	public function preload_component() {
		
		$content_hook = array (
		'user_start_session_override' => array('function' => 'PHPSessions::user_start_session_override', 'priority' => '0'),
		'user_store_session_override' => array('function' => 'PHPSessions::user_store_session_override','priority' => '-1'),
		'user_make_user_object' => 'PHPSessions::user_make_user_object',
		'user_logout_override' => 'PHPSessions::user_logout_override',
		'site_obtrude_attributes' => 'PHPSessions::site_obtrude_attributes',
		'site_add_attributes' => 'PHPSessions::site_add_attributes',
		'site_retrieve_attributes' => 'PHPSessions::site_retrieve_attributes',
		'site_remove_attributes' => 'PHPSessions::site_remove_attributes',
		'user_add_attributes' => 'PHPSessions::user_add_attributes',
		'user_remove_attributes' => 'PHPSessions::user_remove_attributes'		
		);

		return $content_hook;

	}

	/**
	 * start session
	 */
	public static function user_start_session_override($vce) {
	
		// $vce->dump('PHPSessions::user_start_session_override');
	
        // set hash algorithm
        ini_set('session.hash_function', 'sha512');

        // send hash
        ini_set('session.hash_bits_per_character', 5);

        // set additional entropy
        ini_set('session.entropy_file', '/dev/urandom');

        // set additional entropy
        ini_set('session.entropy_length', 256);

        // prevents session module to use uninitialized session ID
        ini_set('session.use_strict_mode', true);

        // SESSION FIXATION PREVENTION

        // do not include the identifier in the URL, and not to read the URL for identifiers.
        ini_set('session.use_trans_sid', 0);

        // tells browsers not to store cookie to permanent storage
        ini_set('session.cookie_lifetime', 0);

        // force the session to only use cookies, not URL variables.
        ini_set('session.use_only_cookies', true);

        // make sure the session cookie is not accessible via javascript.
        ini_set('session.cookie_httponly', true);

        // check for https within site_url
        if (parse_url($vce->site->site_url, PHP_URL_SCHEME) == "https") {
            // set to true if using https
            ini_set('session.cookie_secure', true);
            // I have no way of testing this for samesite
        	// ini_set('session.cookie_samesite', 'None');
        } else {
            ini_set('session.cookie_secure', false);
            // Strict
        	// ini_set('session.cookie_samesite', 'Strict');
        }

        // get url path
        $url_path = parse_url($vce->site->site_url, PHP_URL_PATH);
        // if this has a value, set cookie_path
        if (!empty($url_path)) {
            ini_set('session.cookie_path', $url_path);
        }

        // chage session name
        session_name('_s');

        // start the session
        session_start();

        // get the user session
        $user_session = !empty($_SESSION['user']) ? $_SESSION['user'] : false;

		// get configuration values
		$configuration = self::get_config($vce);
		
		// use configuration values cycle_time.
		if (isset($configuration['cycle_time_unit_increment']) && $configuration['cycle_time_unit']) {
			$cycle_timer = strtotime(date('Y-m-d H:i:s') . '- ' . $configuration['cycle_time_unit_increment'] .  ' ' . $configuration['cycle_time_unit']);
		} else {
			$cycle_timer = strtotime(date('Y-m-d H:i:s') . '- ' . self::$time_unit_increment .  ' ' . self::$time_unit);
		}

		// check to see if it is time to cycle
        if (!empty($_SESSION['timestamp']) && $_SESSION['timestamp'] < $cycle_timer) {
			// generate a new session id key
			$_SESSION['timestamp'] = time();
			session_regenerate_id(true);
       	}

        // return the user session value
        return $user_session;

	}


	/**
	 * store session
	 */
	public static function user_store_session_override($user_object, $vce) {
	
        // check if php sessions are being used
        if (isset($_SESSION)) {
            // store standard php session
            $_SESSION['user'] = $user_object;
            // add a timestamp
            $_SESSION['timestamp'] = time();
        }
        
		$vce->user->session_id = true;

		return true;
	}
	
	
	public static function user_make_user_object($user_object, $vce) {
	
		// clear user session
		if (isset($_SESSION)) {
			unset($_SESSION['user']);
		}
	
	}

	/**
	 * delete
	 */
	public static function user_logout_override($user_id) {

		// delete user session
		if (isset($_SESSION)) {
			unset($_SESSION['user']);

			// Destroy all data registered to session
			session_destroy();
		}
	
	}

	
	/**
	 * site_obtrude_attributes
	 */
	public static function site_obtrude_attributes($vce) {

        if (isset($_SESSION)) {
            // check for session attributes saved previously
            if (isset($_SESSION['add_attributes'])) {
                foreach (json_decode($_SESSION['add_attributes'], true) as $key => $value) {
                    // if there is a persistent value set
                    if ($key == 'persistent') {
                        $persistent = $value;
                        foreach ($persistent as $persistent_key => $persistent_value) {
                            $vce->$persistent_key = $persistent_value;
                            $vce->site->$persistent_key = $persistent_value;
                        }
                    } else {
                        // normal value
                        $vce->$key = $value;
                        $vce->site->$key = $value;
                    }
                }
                // clear it
                unset($_SESSION['add_attributes']);
                // rewrite if persistent value had been set
                if (isset($persistent)) {
                    $_SESSION['add_attributes'] = json_encode(array('persistent' => $persistent));
                }
            }
        }
        
	}
	
	
	/**
	 *
	 */
	public static function site_add_attributes($key, $value, $persistent) {
	
		global $vce;

        // prepare for array
        $pairs = array();
        
        // convert to array
        if (!is_array($key)) {
			$pairs[$key] = $value;
        } else {
        	$pairs = $key;
        	$persistent = is_bool($value) ? $value : $persistent;
        }
        
		if (isset($_SESSION)) {
			// get current value of 'add_attributes'
			if (isset($_SESSION['add_attributes'])) {
				$add_attributes = json_decode($_SESSION['add_attributes'], true);
			} else {
				$add_attributes = array();
			}
			
			foreach ($pairs as $each_key=>$each_value) {
			
				if ($persistent) {
					// add to persistent sub array
					$add_attributes['persistent'][$each_key] = $each_value;
				} else {
					// add as normal
					$add_attributes[$each_key] = $each_value;
				}
			
				// add attribute to site object
				$vce->$each_key = $each_value;
			
			}
			
			$_SESSION['add_attributes'] = json_encode($add_attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
		}
	
	}
		
		
	/**
	 * retrieve_attributes
	 */
	public static function site_retrieve_attributes($key) {
	
		global $vce;
		
		// set to null 
		$attribute_value = null;
		
		if (isset($_SESSION)) {
			// check that add_attributes is in session
			if (isset($_SESSION['add_attributes'])) {
				// get array of keys
				$attributes = json_decode($_SESSION['add_attributes'],true);
				// if the key exists return it
				if (isset($attributes[$key])) {
					$attribute_value = $attributes[$key];
				}
				// if the persistent key exists return it
				if (isset($attributes['persistent'][$key])) {
					$attribute_value = $attributes['persistent'][$key];
				}
			}
		
		}
		
		return $attribute_value;

	}
	
	
	/**
	 * site_remove_attributes
	 */
	public static function site_remove_attributes($key, $on_user = false) {

        if (isset($_SESSION)) {
            if (isset($_SESSION['add_attributes'])) {
                $attributes = json_decode($_SESSION['add_attributes'], true);
                if (is_array($key)) {
                	foreach ($key as $each_key) {
                		unset($attributes[$each_key], $attributes['persistent'][$each_key]);
                	}
                } else {
                	unset($attributes[$key], $attributes['persistent'][$key]);
 				}            
                $_SESSION['add_attributes'] = json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            }
        }
	
	}

	/**
	 * add config info for this component
	 */
	public function component_configuration() {
	
		global $vce;
		$configuration = null;
		
		$input = array(
		'type' => 'text',
		'name' => 'cycle_time_unit_increment',
		'value' => isset($this->configuration['cycle_time_unit_increment']) ? $this->configuration['cycle_time_unit_increment'] :  NULL,
		);
		
		$configuration .= $vce->content->create_input($input,'Cycle Time Unit Increment (Optional)');

		$input = array(
		'type' => 'select',
		'name' => 'cycle_time_unit'
		);
		
		foreach (array('','seconds','minutes','hours','days') as $each_unit) {
			$input['options'][] = array(
				'name' => $each_unit,
				'value' => $each_unit,
				'selected' => (isset($this->configuration['cycle_time_unit']) && $this->configuration['cycle_time_unit'] == $each_unit) ? true : false
			);
		}
		
		$configuration .= $vce->content->create_input($input,'Cycle Time Unit (Optional)');
		
		return $configuration;
	
	}

}