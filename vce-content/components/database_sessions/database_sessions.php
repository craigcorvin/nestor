<?php

class DatabaseSessions extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Database Sessions',
			'description' => 'Component to store sessions in the database.',
			'category' => 'Admin User Sessions',
			'recipe_fields' => false
		);
	}
	
	// enable debugging
	static $debug = false;
	
	// default values
	// number of time units
	static $time_unit_increment = 1;
	// time unit: minutes, hours
	static $time_unit = 'hours';
	
	/**
	 * create component specific database table when installed
	 */
	public function installed() {
		global $vce;
		$sql = "CREATE TABLE " . TABLE_PREFIX . "sessions (session_id varchar(255) COLLATE utf8_unicode_ci NOT NULL,session_expires datetime NOT NULL,session_data TEXT COLLATE utf8_unicode_ci, PRIMARY KEY (session_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		$vce->db->query($sql);
	}

	/**
	 * delete component specific database table when removed
	 */
	public function removed() {
		global $vce;
		$sql = "DROP TABLE IF EXISTS " . TABLE_PREFIX . "sessions;";
		$vce->db->query($sql);
	}
	
	/**
	 * clear component specific database table when disabled
	 */
	public function disabled() {
		
		// drop and then recreated
		
		global $vce;
		$sql = "DROP TABLE IF EXISTS " . TABLE_PREFIX . "sessions;";
		$vce->db->query($sql);
		
		$sql = "CREATE TABLE " . TABLE_PREFIX . "sessions (session_id varchar(255) COLLATE utf8_unicode_ci NOT NULL,session_expires datetime NOT NULL,session_data TEXT COLLATE utf8_unicode_ci, PRIMARY KEY (session_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		$vce->db->query($sql);

	}

	/**
	 * things to do when this component is preloaded
	 */
	public function preload_component() {
		
		$content_hook = array (
		'user_start_session_override' => array('function' => 'DatabaseSessions::user_start_session_override','priority' => '0'),
		'user_store_session_override' => array('function' => 'DatabaseSessions::user_store_session_override','priority' => '-1'),
		'user_logout_override' => 'DatabaseSessions::user_logout_override',
		'site_obtrude_attributes' => 'DatabaseSessions::site_obtrude_attributes',
		'site_add_attributes' => 'DatabaseSessions::site_add_attributes',
		'site_retrieve_attributes' => 'DatabaseSessions::site_retrieve_attributes',
		'site_remove_attributes' => 'DatabaseSessions::site_remove_attributes',
		'user_add_attributes' => 'DatabaseSessions::user_add_attributes',
		'user_remove_attributes' => 'DatabaseSessions::user_remove_attributes'		
		);

		return $content_hook;

	}

	/**
	 * start session
	 */
	public static function user_start_session_override($vce) {
	
		if (self::$debug) {
			$vce->add_errors('DatabaseSessions::user_start_session_override', $vce);
		}
	
		// handles the creation of the cookie, or retrieving session data
		
		// If no user session exists, the following order happens
		// User __construct, which calls...
		// User start_session => user_start_session_override hook, which returns an empty value for $user_session
		// User __construct creates x user value for $user_session, which calls...
		// User store_session => user_store_session_override hook
		// If no cookie is found, then create_cookie which returns session_is and create_session writes DB entry for session

		if (isset($_COOKIE['_dbs'])) {
			// cookie has already been set, so use value from session id to retrieve session data
			
			if (self::$debug) {
				$vce->add_errors('DatabaseSessions::user_start_session_override cookie found with value of ' . $_COOKIE['_dbs'], $vce);
			}
			
			$session_id = $_COOKIE['_dbs'];

			$query = "SELECT * FROM " . TABLE_PREFIX . "sessions WHERE session_id = '" . $session_id . "'";
			$results = $vce->db->get_data_object($query);
			
			// a DB session exists
			if (!empty($results)) {
			
				if (self::$debug) {
					$vce->add_errors('DatabaseSessions::user_start_session_override DB session id found with value of ' . $results[0]->session_id, $vce);
					$vce->add_errors('DatabaseSessions::user_start_session_override DB session expires at ' . $results[0]->session_expires, $vce);			
					$vce->add_errors('DatabaseSessions::user_start_session_override Current server time is ' . date("Y-m-d H:i:s"), $vce);
				}
			
				$expires = strtotime($results[0]->session_expires) - time();
				
				// adn the DB session has not expired
				if ($expires > 0) {
				
					if (self::$debug) {
						$vce->add_errors('DatabaseSessions::user_start_session_override DB session has remaining time of ' . $expires, $vce);
					}
				
					$session_data_decryption = $vce->site->decryption($results[0]->session_data, $results[0]->session_id);
				
					$session_data = json_decode($session_data_decryption);
					
					if (self::$debug) {
						$vce->add_errors('DatabaseSessions::user_start_session_override DB session data:', $vce, $vce);
						$vce->add_errors('<pre>' . print_r($session_data, true) . '</pre>', $vce);
					}
					
					$configuration = self::get_config($vce);
					
					// here is where we control the cycling
					
					$cycle = true;

					// use configuration values cycle_time.
					if (isset($configuration['cycle_time_unit_increment']) && $configuration['cycle_time_unit']) {
			
						$cycle_timer = strtotime(date('Y-m-d H:i:s') . '+ ' . $configuration['cycle_time_unit_increment'] .  ' ' . $configuration['cycle_time_unit']) - time();

						if (self::$debug) {
							$vce->add_errors('DatabaseSessions::user_start_session_override Cycle timer value is ' . $cycle_timer, $vce);
						}

						if ($expires > $cycle_timer) {
							$cycle = false;
						}
			
					} else {
					
						$cycle = false;
					
						if (self::$debug) {
							$vce->add_errors('DatabaseSessions::user_start_session_override No values have been configured for cycling, then cycle will occure on each page load', $vce);
						}
					
					}
					
					// if no values have been configured for cycling, then cycle will occure on each page load
					
					if ($cycle) {
					
						if (self::$debug) {
							$vce->add_errors('DatabaseSessions::user_start_session_override Cycle timer has expired, calling create_session()', $vce);
						}
					
						self::create_session($session_id, $session_data, $vce);
					} else {
						// add javascript timer for the already exisitng session
						self::javascript_timer((strtotime($results[0]->session_expires) - time()), $vce);
					}
					
					if (!empty($session_data->user)) {
						return $session_data->user;
					}
				
				} else {
				
					if (self::$debug) {
						$vce->add_errors('DatabaseSessions::user_start_session_override DB session has expired', $vce);
					}
				
				}
				
			} else {


				if (self::$debug) {
					$vce->add_errors('DatabaseSessions::user_start_session_override no matching session found in database', $vce);
				}
		
			}
			
		}
		
		if (self::$debug) {
			$vce->add_errors('DatabaseSessions::user_start_session_override Returning false', $vce);
		}

		return false;

	}


	/**
	 * this method will fire off 
	 */
	public static function user_store_session_override($user_object, $vce) {
	
		if (self::$debug) {
			$vce->add_errors('DatabaseSessions::user_store_session_override', $vce);
		}

		$session_id = null;
		
		$session_data = array('user' => $user_object);

		// if a cookie is found, check to see if a valid DB session is associated with it
		if (isset($_COOKIE['_dbs'])) {
			
			$query = "SELECT * FROM " . TABLE_PREFIX . "sessions WHERE session_id = '" . $_COOKIE['_dbs'] . "'";
			$results = $vce->db->get_data_object($query);
			
			// this is a valid session within the DB
			if (!empty($results) && strtotime($results[0]->session_expires) > time()) {
				$session_id = $_COOKIE['_dbs'];
			}
			
			if (self::$debug) {
				$vce->add_errors('DatabaseSessions::user_store_session_override cookie found with value of ' . $_COOKIE['_dbs'], $vce);
			}

		}

		// otherwise if no cookie is found, or a cookie is found, but no DB session is associate with it, create a new session_id

		if (empty($session_id)) {
		
			// we should probably search for any other components that use the user_logout_override hook, and then fire off those methods
			// foreach ($vce->site->hooks['user_logout_override'] as $each_hook) {
			// 	// prevent this component from being fired off
			// 	if (strpos($each_hook, 'DatabaseSessions') === false) {
			// 		call_user_func($each_hook, $vce->user->user_id);
			// 	}
			// }
		
			$session_id = self::create_cookie($vce);
		}
		
		self::create_session($session_id, $session_data, $vce);
		
		// garbage collection
		
		$configuration = self::get_config($vce);
		
		$unit_increment = isset($configuration['time_unit_increment']) ?  $configuration['time_unit_increment'] : self::$time_unit_increment;
		$unit = isset($configuration['time_unit']) ?  $configuration['time_unit'] : self::$time_unit;

		// set Logan's Run Carousel age for database entries, which currently is set for 2 times the current unit_increment 
		$gc_date = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' - ' . ($unit_increment * 2) . ' ' . $unit));
		
		// garbage collection for old session entries in database, and ignoring the current one
		$vce->db->query("DELETE FROM " . TABLE_PREFIX . "sessions WHERE session_id != '" . $session_id . "' AND session_expires <= '" . $gc_date . "'");

		// return the session_id, either that is in the cookie or the newly created one.

		$vce->user->session_id = $session_id;

		return $session_id;
	
	}
	
	
	/**
	 * method to create session
	 */
	private static function create_session($session_id, $session_data, $vce) {
	
		if (self::$debug) {
			$vce->add_errors('DatabaseSessions::create_session', $vce);
			$vce->add_errors('DatabaseSessions::create_session Session id is ' . $session_id, $vce);
			if (isset($_COOKIE['_dbs'])) {
				$vce->add_errors('DatabaseSessions::create_session Cookie value is ' . $_COOKIE['_dbs'], $vce);
			} else {
				$vce->add_errors('DatabaseSessions::create_session No readable cookie yet...', $vce);
			}
		}
		
		// getting expiration configuration
		$configuration = self::get_config($vce);
		
		$unit_increment = isset($configuration['time_unit_increment']) ?  $configuration['time_unit_increment'] : self::$time_unit_increment;
		$unit = isset($configuration['time_unit']) ?  $configuration['time_unit'] : self::$time_unit;

		$expires = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . '+ ' . $unit_increment .  ' ' . $unit));
		
		$session_data_value = json_encode($session_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

		$session_data_encryption = $vce->site->encryption($session_data_value, $session_id);
		
		// write this to the database

		$user_data = array(
			'session_id' => $session_id,
			'session_expires' => $expires,
			'session_data' => $session_data_encryption
		);
		
		$vce->db->insert('sessions', $user_data);
		
		self::javascript_timer((strtotime($expires) - time()), $vce);

		return $session_id;
	
	}
	
	
	/**
	 *
	 */
	private static function create_cookie($vce) {
	
		if (self::$debug) {
			$vce->add_errors('DatabaseSessions::create_cookie', $vce);
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
		
		// get url path
		$url_path = parse_url($vce->site->site_url, PHP_URL_PATH);
		// if this has a value, set cookie_path
		if (empty($url_path)) {
			$url_path = '/';
		}

		// get a vector value
		$session_id = $vce->site->create_vector();
		
		$cookie = "_dbs=" . $session_id . "; Path=" . $url_path . $cookie_secure . "; HttpOnly; SameSite=" . $same_site;
		
		if (self::$debug) {
			$vce->add_errors('DatabaseSessions::create_cookie Write Cookie', $vce);
			$vce->add_errors($cookie, $vce);
		}
		
		// false flag allows for multiples of this header
        header("Set-Cookie: " . $cookie, false);
		
		return $session_id;
	
	}


	/**
	 * delete cookie
	 */
	private static function destroy_session_cookie($vce) {
	
		$same_site = 'Strict';
	
		// check for https within site_url
		if (parse_url($vce->site->site_url, PHP_URL_SCHEME) == "https") {
			$cookie_secure = "; Secure";
			$same_site = 'None';
		} else {
			$cookie_secure = null;
		}
		
		// get url path
		$url_path = parse_url($vce->site->site_url, PHP_URL_PATH);
		// if this has a value, set cookie_path
		if (empty($url_path)) {
			$url_path = '/';
		}
		
		// 7 days in the past
		$expires_time = time() - 604800;
	
		// Wed, 25 Mar 2020 16:46:58 GMT
		$cookie_expires = gmdate('D, d M Y H:i:s', $expires_time) . ' GMT';
		
		$cookie_value = '';
		
		// Max-Age=0 should expire the cookie immediately.
		
		$cookie = "_dbs=" . $cookie_value . "; Expires=" . $cookie_expires . "; Max-Age=0; Path=" . $url_path . $cookie_secure . "; HttpOnly; SameSite=" . $same_site;

		// false flag allows for multiples of this header
		header("Set-Cookie: " . $cookie, false);

	}
	

	/**
	 * delete
	 */
	public static function user_logout_override($user_id) {
	
		if (isset($_COOKIE['_dbs'])) {
		
			global $vce;
			
			$session_id = $_COOKIE['_dbs'];
			
			// delete the db session
			$where = array('session_id' => $session_id);
			$vce->db->delete('sessions', $where);
		
			self::destroy_session_cookie($vce);
        }
	
	}

	
	/**
	 * site_obtrude_attributes
	 */
	public static function site_obtrude_attributes($vce) {

		global $vce;

		// get our session data
		$session = self::get_session_data($vce);

		if (!empty($session)) {
		
			if (isset($session['session_data']['add_attributes'])) {
				foreach ($session['session_data']['add_attributes'] as $key=>$value) {
					// if there is a persistent value set
					if ($key == 'persistent') {
						$persistent = $value;
						foreach ($persistent as $persistent_key=>$persistent_value) {
							$vce->$persistent_key = $persistent_value;
							$vce->site->$persistent_key = $persistent_value;
						}
					} else {
						// normal value
						$vce->$key = $value;
						$vce->site->$key = $value;
					}
				}
 
			}
			
			// clear it
			unset($session['session_data']['add_attributes']);
			
			// rewrite if persistent value had been set
			if (isset($persistent)) {
				$session['session_data']['add_attributes'] = array('persistent' => $persistent);
			}
			
			
			// put session data into database
			self::put_session_data($session, $vce);
			
		}
	
	}
	
	
    /**
     * Adds attributes that will be added to the page object on next page load.
     * If persistent, then attribute will stay until deleted or session has ended.
     * @param string / array $key (if array, then $value becomes boolean for $persistent)
     * @param string $value
     * @param bool $persistent
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
		
		// get our session data
		$session = self::get_session_data($vce);
		
		if (!empty($session)) {
		
			foreach ($pairs as $each_key=>$each_value) {
		
				if ($persistent) {
					// add to persistent sub array
					$session['session_data']['add_attributes']['persistent'][$each_key] = $each_value;
				} else {
					// add as normal
					$session['session_data']['add_attributes'][$each_key] = $each_value;
				}
			
				// add attribute to site object
				$vce->$each_key = $each_value;
			
			}

			// put session data into database
			self::put_session_data($session, $vce);
			
			return;
		
		}
	
	}
		
		
	/**
	 * retrieve_attributes
	 */
	public static function site_retrieve_attributes($key) {
	
		global $vce;
		
		$attribute_value = null;
		
		// get our session data
		$session = self::get_session_data($vce);
		
		if (!empty($session)) {
			if (isset($session['session_data']['add_attributes']['persistent'][$key])) {
				$attribute_value =  $session['session_data']['add_attributes']['persistent'][$key];
			}
			if (isset($session['session_data']['add_attributes'][$key])) {
				$attribute_value =  $session['session_data']['add_attributes'][$key];
			}
		}
		
		return $attribute_value;
		
	}
	
	
	/**
	 * site_remove_attributes
	 */
	public static function site_remove_attributes($key, $on_user = false) {
	
		global $vce;
		
		// get our session data
		$session = self::get_session_data($vce);
		
		if (!empty($session)) {
		
			if ($on_user) {
				if (is_array($key)) {
					foreach ($key as $each_key) {
						unset($session['session_data']['user'][$each_key]);
					}
				} else {
					unset($session['session_data']['user'][$key]);
				}
			} else {
				if (is_array($key)) {
					foreach ($key as $each_key) {
 						unset($session['session_data']['add_attributes']['persistent'][$each_key], $session['session_data']['add_attributes'][$each_key]);
					}
				} else {
 					unset($session['session_data']['add_attributes']['persistent'][$key], $session['session_data']['add_attributes'][$key]);
				}
			}

			// put session data into database
			self::put_session_data($session, $vce);
			
			return;
		
		}
	
	}

	
	/**
	 * get session data
	 */
	private static function get_session_data($vce) {

		if (isset($_COOKIE['_dbs'])) {

			$session_id = $_COOKIE['_dbs'];

			$query = "SELECT * FROM " . TABLE_PREFIX . "sessions WHERE session_id = '" . $session_id . "'";
			$results = $vce->db->get_data_object($query);
	
			if (!empty($results)) {
			
				$session_id = $results[0]->session_id;
			
				$session_expires = $results[0]->session_expires;
			
				// get and decrypt session data
				$session_data_decryption = $vce->site->decryption($results[0]->session_data, $results[0]->session_id);

				$session_data = json_decode($session_data_decryption, true);
						
				return array('session_id' => $session_id, 'session_expires' => $session_expires ,'session_data' => $session_data);
				
			}
		}
		
		return null;
	}
	
	
	/**
	 * put session data back into the database
	 */
	private static function put_session_data($session, $vce) {

		$session_data_value = json_encode($session['session_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
		
		$session_data_encryption = $vce->site->encryption($session_data_value, $session['session_id']);
		
		// update record
		$update = array('session_data' => $session_data_encryption);
		$update_where = array('session_id' => $session['session_id']);
		$vce->db->update('sessions', $update, $update_where);
	
	}
	
	/**
	 *
	 */
	private static function javascript_timer($expires, $vce) {

		$content = '<script>
var current = new Date();
var expires =  (new Date(current.getTime() + (' . $expires . ' * 1000))).getTime();
$(function() {
	var count = setInterval(
		function() {
			var current = new Date();
			if (current.getTime() > expires) {
				clearInterval(count);
				var $div = $("<div />").prependTo("body");
				$div.attr("class", "session-expired");
				$div.html("<span>Your session has expired.<br><br><a class=\"link-button\" href=\"\">Click here to login</a></span>");
			}
		}, 1000
	);
});
</script>
<style>

.session-expired {
position: fixed;
display: block;
top: 0px;
left: 0px;
width: 100%;
height: 100%;
background-color:rgba(0, 0, 0, 0.6);
z-index: 1000;
}

.session-expired span {
position: absolute;
display: block;
top: 50%;
left: calc(50% - 140px);
width: 280px;
background-color: #fff;
color: #333;
padding: 50px;
border-radius: 10px;
text-align: center;
}

</style>';

// debug
// $("span.countdown").html(seconds);
// <span class="countdown"></span>

		$vce->content->add('main', $content);
	
	}
	
	
	/**
	 * add config info for this component
	 */
	public function component_configuration() {
	
		global $vce;
		$configuration = null;
		
		$time_unit_increment_value = isset($this->configuration['time_unit_increment']) ? $this->configuration['time_unit_increment'] : self::$time_unit_increment;
		
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