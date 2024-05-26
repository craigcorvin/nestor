<?php
/**
 * Creates session and login information
 * when User class is initiated, start session and check for user object.
 * If not found, check for persistent login cookie.
 */
class User {

    /**
     * Starts session and checks for User login.
     * Takes care of the basic login and session creation, checking for a cookie if the user session is nonexistant.
     */
    public function __construct($vce, $load_session = true) {
    
    	//$vce->dump('User __construct');

        // global $vce;
        // $this->vce = $vce;
        $vce->user = $this;
        
        // instantiate the class without triggering a user session
        if ($load_session == false) {
            return;
        }

        // start session
        $user_session = self::start_session($vce);

        // check is session for user info exists
        // if (isset($_SESSION['user'])) {
        if (!empty($user_session)) {

            // set user info values
            // foreach ($user_object as $key => $value)
            foreach ($user_session as $key => $value) {
                $this->$key = $value;
            }

            // a good session, return to exit this method
            return true;

            // if session doesn't exits, check if persistent login cookie exists
        } else {
            
            /* alternative login */

            // load login hook
            if (isset($vce->site->hooks['user_alternative_login'])) {
                foreach ($vce->site->hooks['user_alternative_login'] as $hook) {
                    $user_session = call_user_func($hook, $vce);
                }
            }

            /* end alternative login */

        }

        // if no session or persistent login, then create a non logged-in user session

        // check is session for user info exists
        if (empty($user_session)) {
        
        	//$vce->dump('User __construct x user');

            // user is not logged-in, role_id is set to x, because x is fun and "I want to believe."
            $this->role_id = "x";

            // add a session vector
            $this->session_vector = $vce->site->create_vector();

            // set session
            //$_SESSION['user'] = $this;
            self::store_session($this, $vce);

        }

    }
    
    
    /**
     * Starts session
     * @return sets ini values
     */
    private function start_session($vce) {
    
    	//$vce->dump('User start_session');
    
    	// setting to null so that we can check later and provide a fail-safe situation in case no session component has been activated.
    	$user_session = null;

        // hook that can be used to override sessions
        if (isset($vce->site->hooks['user_start_session_override'])) {
            foreach ($vce->site->hooks['user_start_session_override'] as $hook) {
            	$user_session = call_user_func($hook, $vce);
            	if (is_null($user_session)) {
            		$vce->add_errors('Components using user_start_session_override hook must return something, at the very least FALSE', $vce);
            	}
            }
        }
        
        // we are looking for a user_session to be returned. If not, the following code will provide site access at admin level so that this can be configured.
        
        if (is_null($user_session)) {
        
        	// no user session component has been enabled.
        
        	// display error message
        	$vce->add_errors('No user session component is enabled. The PHP Sessions component will now be automatically activated.', $vce);
        	
        	// add PHP Sessions to site_meta data to active it.
        	
        	if (file_exists(BASEPATH . 'vce-application/components/php_sessions/php_sessions.php')) {
        	
				foreach (array('installed_components','activated_components','preloaded_components') as $meta_key) {
			
					$current_meta_key = json_decode($vce->site->$meta_key, true);
			
					$current_meta_key['PHPSessions'] = 'vce-application/components/php_sessions/php_sessions.php';
			
					$update = array('meta_value' => json_encode($current_meta_key, JSON_UNESCAPED_SLASHES));
					$update_where = array('meta_key' => $meta_key);
					$vce->db->update('site_meta', $update, $update_where);
			
				}
			
			} else {
				
				// serious problem has occured, and this backup has been 
			
				// display error message
        		$vce->add_errors('The PHP Sessions component was not found and a temporary session has been activated. Please enable a user session component as soon as possible.', $vce);
        		
				// set a cookie that can store a vector value that will provide access to enable user session component
				if (isset($_COOKIE['_sv'])) {
			
					$session_vector = $_COOKIE['_sv'];
				
				} else {

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
					$session_vector = $vce->site->create_vector();
		
					$cookie = "_sv=" . $session_vector . "; Path=" . $url_path . $cookie_secure . "; HttpOnly; SameSite=" . $same_site;
		
					// false flag allows for multiples of this header
					header("Set-Cookie: " . $cookie, false);

				}
			
			
			
				$user_session = array(
					// return the highest hierarchical site_role which should allow for site administration
					'role_id' => array_keys(json_decode($vce->site->site_roles, true)[0])[0],
					'user_id' => '0',
					'session_vector' => $session_vector
				);
			
			}
        
        }
        
		return $user_session;
    

    }
    
    
    /**
     * Starts session
     * @global object $db
     * @param object $user_object
     * @return bool true
     */
    private function store_session($user_object, $vce) {
    
    	//$vce->dump('User store_session');

        // generate a new session id key
        // session_regenerate_id(true);

        // hook
        if (isset($vce->site->hooks['user_store_session_override'])) {
            foreach ($vce->site->hooks['user_store_session_override'] as $hook) {
                call_user_func($hook, $user_object, $vce);
            }
        }
        
    }
    
    /**
     * Creates user object from user_id
     * @global object $db
     * @global object $site
     * @param string $user_id
     * @return call to self::store_session()
     *
     * note: if the previous value of $vce->user is needed, then it should be a clone of the object
     * $user = clone $vce->user
     *
     */
    public function make_user_object($user_id) {
    
        global $vce;
        
        // clear user object properties
        foreach ($this as $key => $value) {
            unset($this->$key);
        }

        $user_object = $this->load_user_object($user_id);

        if (!empty($user_object)) {

            // rekey user object
            foreach ($user_object as $key => $value) {
                $this->$key = $value;
            }

            // hook
            if (isset($vce->site->hooks['user_make_user_object'])) {
                foreach ($vce->site->hooks['user_make_user_object'] as $hook) {
                    call_user_func($hook, $this, $vce);
                }
            }
	
            return self::store_session($this, $vce);

        } else {

            // user is not logged-in, role_id is set to x, because x is fun and "I want to believe."
            $this->role_id = "x";
            $this->session_vector = $vce->site->create_vector();

            return self::store_session($this, $vce);

        }

    }


    /**
     * Loads the user_object array with data based on the user_id
     *
     * @param [type] $user_id
     * @return array user_object
     */
    public function load_user_object($user_id) {

        global $vce;

        $user_object = null;

        // get site user attributes
		$user_attributes = json_decode($vce->site->user_attributes, true);
		
		// add some additional attributes
		$user_attributes['created_at'] = array('expose' => true);
		$user_attributes['updated_at'] = array('expose' => true);

        // get user_id,role_id, and vector
        $query = "SELECT user_id,vector,role_id FROM  " . TABLE_PREFIX . "users WHERE user_id='" . $user_id . "' LIMIT 1";
        $results = $vce->db->get_data_object($query);

        if ($results) {

            $user_object = [];

            // loop through results
            foreach ($results[0] as $key => $value) {
                //add values to user object
                $user_object[$key] = $value;
            }

            // grab all user meta_data
            $query = "SELECT meta_key,meta_value FROM  " . TABLE_PREFIX . "users_meta WHERE user_id='" . $user_object['user_id'] . "'";
            $metadata = $vce->db->get_data_object($query);

            if ($metadata) {

                // look through metadata
                foreach ($metadata as $array_key=>$each_metadata) {
                
                	$decrypt = true;
                	
                	// if expose, then do not decrypt
                	if (!empty($user_attributes[$each_metadata->meta_key]['expose'])) {
                		$decrypt = false;
                	}

					if ($decrypt) {
                   		// decrypt the values using vi/vector for decrypting user meta data
                    	$value = $vce->db->clean(self::decryption($each_metadata->meta_value, $user_object['vector']));
                    } else {
                    	// value without decryption
                    	$value = $vce->db->clean($each_metadata->meta_value);
                    }

                    // add the values into the user object
                    $user_object[$each_metadata->meta_key] = stripslashes($value);
                }

                // we can then remove vector from the user object
                unset($user_object['vector'], $user_object['lookup'], $user_object['pseudonym'], $user_object['persistent_login']);

                // add user meta data specific to site roles.
                $roles = json_decode($vce->site->roles, true);

                // check if role associated info is an array
                if (is_array($roles[$user_object['role_id']])) {
                    // add key=>value to user object if they don't already exist.
                    // user_meta key=>value takes precidence over role key=>value
                    // this allows for user specific granulation of permissions, et cetera
                    foreach ($roles[$user_object['role_id']] as $role_meta_key => $role_meta_value) {
                        // check if the value is an array
                        if (is_array($role_meta_value)) {
                            $suffix = '_' . $role_meta_key;
                            foreach ($role_meta_value as $sub_meta_key => $sub_meta_value) {
                                // add simple key=>value to user object
                                if (!isset($user_object[$sub_meta_key . $suffix])) {
                                    $user_object[$sub_meta_key . $suffix] = $sub_meta_value;
                                } else {
                                    // add on to existing
                                    $user_object[$sub_meta_key . $suffix] .= ',' . $sub_meta_value;
                                }
                            }
                        } else {
                            // add simple key=>value to user object
                            if (!isset($user_object[$role_meta_key])) {
                                $user_object[$role_meta_key] = $role_meta_value;
                            } else {
                                $user_object[$role_meta_key] .= ',' . $role_meta_value;
                            }
                        }
                    }
                }

                // create a session vector
                // this is used to create an edit / delete token for components
                $user_object['session_vector'] = self::create_vector();

            }
        }
        
		if (isset($vce->site->hooks['user_load_user_object'])) {
			foreach ($vce->site->hooks['user_load_user_object'] as $hook) {
				call_user_func($hook, $user_object, $vce);
			}
        }
        

        return $user_object;
    }

    /**
     * Creates the password_hash and sends it to make_user_object
     * @param array $input  contains email and password
     * @return boolean 
     */
    public function login($input) {

        // is the user already logged in?
        if (!isset($this->user_id)) {

            global $vce;
            
            // validate and check for pseudonym
			$user_validated = $vce->user->email_resolver($input['email']);
            
            if (empty($user_validated)) {
            	return false;
            }
            
            // here is where we will need to validate again
            $hash = $this->generate_hash($user_validated['email'], $input['password']);

            // get user_id,role_id, and hash by crypt value
            $query = "SELECT user_id FROM " . TABLE_PREFIX . "users WHERE hash='" . $hash . "' LIMIT 1";
            $user_id = $vce->db->get_data_object($query);

            if (!empty($user_id)) {

                $this->make_user_object($user_id[0]->user_id);

                // load login hook
                if (isset($vce->site->hooks['at_user_login'])) {
                    foreach ($vce->site->hooks['at_user_login'] as $hook) {
                        call_user_func($hook, $user_id[0]->user_id);
                    }
                }

                return true;

            } else {

                return false;

            }

        }

        // return true if already logged in
        return true;
    }

    /**
     * Logs user out.
     * @return user is logged out
     */
    public function logout() {

        // user is logged in
        if (isset($this->user_id)) {

            global $vce;

            // save it for later, your legs give way, you hit the ground
            // fyi, $this = $vce->user
            $user_id = $this->user_id;

            // clear user object properties and
            foreach ($this as $key => $value) {
                unset($this->$key);
            }
            
            /*

            // delete user session
            if (isset($_SESSION)) {
                unset($_SESSION['user']);

                // Destroy all data registered to session
                session_destroy();
            }
            
            */

            // load logout hook
            if (isset($vce->site->hooks['user_logout_complete'])) {
                foreach ($vce->site->hooks['user_logout_complete'] as $hook) {
                    call_user_func($hook, $user_id);
                }
            }

            // load logout hook
            if (isset($vce->site->hooks['user_logout_override'])) {
                foreach ($vce->site->hooks['user_logout_override'] as $hook) {
                    call_user_func($hook, $user_id);
                }
            }

        }

    } 

 
    /**
     * Adds attributes that will be added to the user object.
     * sudo multiple dispatch, something like:
     * add_attributes($meta_key, $meta_value (optional), $persistent = false, $user_id = null)
     * @param string or array $meta_key
     * @param string $meta_value (optional) if $meta_key is an array, then this value should not be used
     * @param boolean $persistent (optional)
     * @param int $user_id (optional)
     * @return
     */
    public function add_attributes() {
    
        global $vce;
        
        // check that we have at least one argument
        if (func_num_args() == 0) {
        	return $vce->add_errors('$vce->user->add_attributes() method requires at least one argument', $vce);
        }
        
        // default values for this method
        $meta_data = array();
        $arg_pointer = 0;
        $persistent = false;
    	$user_id = $vce->user->user_id;
        
        // check if first argument into method is an array
        if (is_array(func_get_arg(0))) {
        
        	// add value
       		$meta_data = func_get_arg(0);
       		
       		// advance pointer
       		$arg_pointer++;
       		
       		// if a developer makes a mistake
       		if (func_num_args() > 1 && !is_bool(func_get_arg(1))) {
        		return $vce->add_errors('$vce->user->add_attributes() method requires the second argument to be a boolean when first argument is an array', $vce);
       		}
        
        
        } else {
        	
        	// check that a meta_value has been sent to this method
        	if (func_num_args() < 2) {
        		return $vce->add_errors('$vce->user->add_attributes() method requires a second argument when the first argument is not an array', $vce);
        	}
        
        	// add args to meta_data array
        	$meta_data[func_get_arg(0)] = func_get_arg(1);
        	
        	// advance pointer two places
        	$arg_pointer = $arg_pointer + 2;
        
        }
        

        // checking if persistent flag has been set
        if (func_num_args() > $arg_pointer && is_bool(func_get_arg($arg_pointer))) {

			// add persistent value
        	$persistent = func_get_arg($arg_pointer);
        	
        	// advance pointer
        	$arg_pointer++;
        
			// checking to see if a user_id has been passed
        	if (func_num_args() > $arg_pointer && is_numeric(func_get_arg($arg_pointer))) {
			
				// add persistent value
				$user_id = func_get_arg($arg_pointer);
				
        	}

        }
        
        // write values to user_meta
        if ($persistent) {
        
			$current_attributes = $this->find_users(array('user_ids' => $user_id), false, true);
			
			if (empty($current_attributes)) {
        		return $vce->add_errors('$vce->user->add_attributes() method could not find user_id', $vce);
			}
			
			// get site user attributes
			$user_attributes = json_decode($vce->site->user_attributes, true);
			
			// add some additional attributes
			$user_attributes['created_at'] = array('expose' => true);
			$user_attributes['updated_at'] = array('expose' => true);
			
			// get user vector
			$vector = $current_attributes[0]->vector;
			
			// loop through to look for checkbox type input
			foreach ($meta_data as $input_key=>$input_value) {
				// for checkbox inputs
				if (preg_match('/_\d+$/', $input_key, $matches)) {
					// strip _1 off to find input value for checkbox
					$new_input = str_replace($matches[0], '', $input_key);
					// decode previous json object value for input variable
					$new_value = isset($meta_data[$new_input]) ? json_decode($meta_data[$new_input], true) : array();
					// add new value to array
					$new_value[] = $input_value;
					// remove the _1
					unset($meta_data[$input_key]);
					// reset the input with json object
					$meta_data[$new_input] = json_encode($new_value);
				}
			}
			
			foreach ($meta_data as $key=>$value) {
			
				if (in_array($key,array('type','procedure','lookup','password','user_id','role_id','role_hierarchy','session_vector'))) {
					continue;
				}
				
				// by default encrypted and no order preserving hash (oph)
				$encrypt = true;
				$oph = false;
				
				if (isset($user_attributes[$key])) {
				
					// if expose, then do not encrypt
					if (!empty($user_attributes[$key]['expose'])) {
						$encrypt = false;
					}
				
					// if sortable, then add order preserving hash
					if (!empty($user_attributes[$key]['sortable'])) {
						$oph = true;
					}	
				
				}
				
				// encrypt attribute before storing
				if ($encrypt) {
					// encode user data
					$meta_value = $vce->site->encryption($value, $vector);
				} else {
					// encode user data
					$meta_value = $value;
				}
				
				// create order preserving hash
				if ($oph) {
					$minutia = user::order_preserving_hash($value);
				} else {
					$minutia = null;
				}

				if (isset($current_attributes[0]->$key)) {

					// prevent updating of attributes
					if (in_array($key,array('email'))) {
						continue;
					}			
	
					// update
					$update = array('meta_value' => $meta_value, 'minutia' => $minutia);
					$update_where = array('user_id' => $user_id, 'meta_key' => $key);
					$vce->db->update('users_meta', $update, $update_where);

				} else {	
					// add 
					$vce->db->insert('users_meta', array('user_id' => $user_id,'meta_key' => $key,'meta_value' => $meta_value,'minutia' => $minutia));
				}

			}

        }
        
        // are we updating the currently logged in user?
        if ($user_id == $vce->user->user_id) {
        
        	foreach ($meta_data as $key=>$value) {
        	
        		// not allowed
				if (in_array($key,array('user_id','role_id','password','role_hierarchy','session_vector'))) {
					continue;
				}
        		// add to user object
				$vce->user->{$key} = stripslashes($value);
        	}
        	
			// hook
			if (isset($vce->site->hooks['user_make_user_object'])) {
				foreach ($vce->site->hooks['user_make_user_object'] as $hook) {
					call_user_func($hook, $this, $vce);
				}
			}

			return self::store_session($this, $vce);
        
        }
        
		return true;
        
    }
    
    /**
     * forwards to add_attributes()
     */
	public function update_attributes() {
	
		$func_get_args = func_get_args();
		
		for ($x = 0; $x < 5; $x++) {
			$argument[$x] = (func_num_args() > $x) ? $func_get_args[$x] : null;
		}
		
		// add_attributes($meta_key, $meta_value (optional), $persistent = false, $user_id = null)
		return $this->add_attributes($argument[0], $argument[1], $argument[2], $argument[3]);
	
	}

    /**
     * removes user object attribute the user object.
     * sudo multiple dispatch, something like:
     * remove_attributes($meta_key, $persistent = false, $user_id = null)
     * @param string or array $meta_key
     * @param boolean $persistent (optional)
     * @param int $user_id (optional)
     * @return
     */
    public function remove_attributes() {

        global $vce;
        
        // check that we have at least one argument
        if (func_num_args() == 0) {
        	return $vce->add_errors('$vce->user->remove_attributes() method requires at least one argument', $vce);
        }
        
        // default values for this method
        $meta_data = array();
        $persistent = false;
    	$user_id = $vce->user->user_id;        
        
        // check if first argument into method is an array
        if (is_array(func_get_arg(0))) {
        
        	// add value
       		$meta_data = func_get_arg(0);
       		
       	} else {
       	
       		$meta_data[] = func_get_arg(0);
       	
       	}
       	
        // checking if persistent flag has been set
        if (func_num_args() > 1 && is_bool(func_get_arg(func_get_arg(1)))) {

			// add persistent value
        	$persistent = func_get_arg(1);

			// checking to see if a user_id has been passed
        	if (func_num_args() > 2 && is_numeric(func_get_arg(2))) {
			
				// add persistent value
				$user_id = func_get_arg(2);
				
        	}

        }
        
        // remove values from user_meta
        if ($persistent) {
        
        	foreach ($meta_data as $meta_key) {
			
				if (in_array($meta_key,array('lookup','email'))) {
					continue;
				}
			
				$where = array('user_id' => $user_id,'meta_key' => $meta_key);
				$vce->db->delete('users_meta', $where);
			
			}
        
        }

        // are we updating the currently logged in user?
        if ($user_id == $vce->user->user_id) {
        
        	foreach ($meta_data as $meta_key) {
        	
        		// not allowed
				if (in_array($meta_key,array('user_id','role_id','role_hierarchy','session_vector','email'))) {
					continue;
				}
        		// remove from user object
				unset($vce->user->{$meta_key});
        
        	}
        	
        	// hook
			if (isset($vce->site->hooks['user_make_user_object'])) {
				foreach ($vce->site->hooks['user_make_user_object'] as $hook) {
					call_user_func($hook, $this, $vce);
				}
			}
        	
        	return self::store_session($this, $vce);
        	
        }

        return true;

    }



    /**
     * legacy
     */
	public function update_user_password($input) {
	
		return $this->update($input);
		
    }
    
    
    /* 
     ** utility functions
     */
    

    /**
     * legacy
     */
    public static function user_exists($email) {
    	global $vce;
		return $vce->user->find_id_by_email($email);
    }
    
    /**
     * legacy
     */
    public static function email_to_id($email) {
    	global $vce;
		return $vce->user->find_id_by_email($email);
    }

    /**
     * Takes an email address, searches lookup, and return a user id if user exists.
     * @param string $email
     * @return string $user_id or false
     */
    public function find_id_by_email($email) {

        global $vce;
        
        $sanitized_email = filter_var(strtolower($email), FILTER_SANITIZE_EMAIL);
        
     	if (!filter_var($sanitized_email, FILTER_VALIDATE_EMAIL)) {
       		return false;
        }

        // get lookup crypt for email
        $lookup = $this->generate_lookup($sanitized_email);
        
        // get value
        $query = "SELECT user_id FROM  " . TABLE_PREFIX . "users_meta WHERE meta_key='lookup' AND meta_value='" . $lookup . "'";
        $user = $vce->db->get_data_object($query);

        // if user_id exists, return it, otherwise null
        return isset($user[0]->user_id) ? $user[0]->user_id : false;

    }
    
    /**
	 * Method to validate email
     * @param string $email
     * @return array 
	 */
    public function validate_email($email) {
    
    	global $vce;

		$email = filter_var(strtolower($email), FILTER_SANITIZE_EMAIL);
		
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return array('response' => 'error','message' => 'Not a valid email address','value' => null);
		}

        // get lookup crypt for email
        $lookup = $this->generate_lookup($email);
        
        // get value
        $query = "SELECT user_id FROM  " . TABLE_PREFIX . "users_meta WHERE meta_key='lookup' AND meta_value='" . $lookup . "'";
        $user = $vce->db->get_data_object($query);
	
		if (!empty($user)) {
			return array('response' => 'error','message' => 'Email address already in use','value' => null);
		}
		
    	return array('response' => 'success','message' => 'Email address is valid','value' => $email);
    
    }
    
    /**
	 * Method to validate_password
     * @param string $password
     * @return array 
	 */
	public function validate_password($password) {
	
		if (strlen($password) < 6) {
			return array('response' => 'error','message' => 'Passwords must be least 6 characters in length');
		}
		
		if (!preg_match("#[0-9]+#", $password)) {
			return array('response' => 'error','message' => 'Password must include at least one number');
		}

		if (!preg_match("#[a-zA-Z]+#", $password)) {
			return array('response' => 'error','message' => 'Password must include at least one letter');
		}

		if (!preg_match("#\W+#", $password)) {
			return array('response' => 'error','message' => 'Password must include at least one symbol');
		}
		
		return array('response' => 'success','message' => 'Password is valid');

    }
    
	/**
	 * Method to create and return a random password
	 */
    public function generate_password() {

        // anonymous function to generate password
        $random_password = function ($password = null, $tracker = array()) use (&$random_password) {

            $charset = array('({[!@#$?%^&*+-]})','0123456789','abcdefghijklmnopqrstuxyvwz','ABCDEFGHIJKLMNOPQRSTUXYVWZ');
            
            $key = mt_rand(0,(count($charset) - 1));
            
            $newchar = substr($charset[$key], mt_rand(0, (strlen($charset[$key]) - 1)), 1);
            
            $tracker[$key] = !isset($tracker[$key]) ? 1 : $tracker[$key] + 1;
            
            if (isset($tracker[$key]) && $tracker[$key] > (count($charset) - 2)) {
            	return $random_password($password, $tracker);
            }
            
            if (strlen($password) >= ((count($charset) * 2) - 1)) {
            	if (count($tracker) == count($charset)) {
            		return $password . $newchar;
                }
            }
            
            return $random_password($password . $newchar, $tracker);
            
        };

        // get a new random password
        return $random_password();
    }



    /**
     * legacy
     */
    public static function create_hash($email, $password) {
		global $vce;
		return $vce->user->generate_hash($email, $password);
    }

    
    /**
     * Creates hash of $email and $password
     * @param string $email
     * @param string $password
     * @return string encrypted $email and $password
     */
    public function generate_hash($email, $password) {

        // SITE_KEY
        // this constant is created at install and stored in vce-config.php
        // bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));

        // get salt
        $user_salt = substr(hash('md5', str_replace('@', hex2bin(SITE_KEY), $email)), 0, 22);

        // combine credentials
        $credentials = $email . $password;

        // new hash value
        return crypt($credentials, '$2y$10$' . $user_salt . '$');

    }
    
    
    /**
     * legacy
     */
    public static function lookup($email) {
        global $vce;
		return $vce->user->generate_lookup($email);
    }

    /**
     * Encrypts $email
     * @param string $email
     * @return string encrypted $email
     */
    public function generate_lookup($email) {

        // get salt
        $user_salt = substr(hash('md5', str_replace('@', hex2bin(SITE_KEY), $email)), 0, 22);

        // create lookup
        return crypt($email, '$2y$10$' . $user_salt . '$');

    }
    
    /**
     * searches looking and pseudonym user meta_data and returns associated email
     * @param string $email
     * @return string $email
     */
    public function email_resolver($email) {
    
    	global $vce;

        $sanitized_email = filter_var(strtolower($email), FILTER_SANITIZE_EMAIL);
        
     	if (!filter_var($sanitized_email, FILTER_VALIDATE_EMAIL)) {
       		return false;
        }

        // get lookup crypt for email
        $lookup = $this->generate_lookup($sanitized_email);
        
        // get value
        $query = "SELECT * FROM  " . TABLE_PREFIX . "users_meta WHERE meta_key='lookup' AND meta_value='" . $lookup . "'";
        $user = $vce->db->get_data_object($query);
        
        if (!empty($user)) {
        	
        	return array('user_id' => $user[0]->user_id, 'email' => $sanitized_email);
        
        } else {
        
			// get value
			$query = "SELECT * FROM  " . TABLE_PREFIX . "users_meta WHERE meta_key='pseudonym' AND minutia='" . $lookup . "'";
			$user = $vce->db->get_data_object($query);
			
			if (!empty($user)) {
			
				$pseudonym = $this->find_users(array('user_ids' => $user[0]->user_id));
					
				if (!empty($pseudonym)) {
			
					return array('user_id' => $pseudonym[0]->user_id, 'email' => $pseudonym[0]->email);
			
				}
						
			}
        
        }
        
        return false;
    
    }
    
    
    /**
     * Create a new user
     *
     * @param string $attributes array of attributes and values.  Must include email. If no password it will be generated
     * @return integer the new user id, or error message
     *
     * notes: If you pass in a value for $attributes['lookup'] it will be saved as minutia in that users_meta row
     */
	public function create($attributes) {

		global $vce;
		
		// make sure these values are removed
    	unset($attributes['type'], $attributes['procedure']);
    	
    	$message = array();
		
		// validate email
		
        if (!isset($attributes['email'])) {
            return array('response' => 'error', 'message' => 'Email address must be provided');
        }
        
        if (isset($attributes['email2']) && $attributes['email2'] != $attributes['email']) {
        	return array('response' => 'error', 'message' => 'Email addresses do not match');
        }
    
        $attributes['email'] = filter_var(strtolower($attributes['email']), FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($attributes['email'], FILTER_VALIDATE_EMAIL)) {
            return array('response' => 'error', 'message' => 'Email address is not a valid email address');
        }

		// need to create a new method that combines user_ot_email and user_exists
        if ($this->find_id_by_email($attributes['email'])) {
            return array('response' => 'error', 'message' => 'Email address is already in use');
        }
        
        // validate password
        
		if (isset($attributes['password'])) {

			if (strlen($attributes['password']) < 6) {
				return array('response' => 'error', 'message' => 'Passwords must be least 6 characters in length');
			}
			
			if (!preg_match("#[0-9]+#", $attributes['password'])) {
				return array('response' => 'error', 'message' => 'Password must include at least one number');
			}

			if (!preg_match("#[a-zA-Z]+#", $attributes['password'])) {
				return array('response' => 'error', 'message' => 'Password must include at least one letter');
			}

			if (!preg_match("#\W+#", $attributes['password'])) {
				return array('response' => 'error', 'message' => 'Password must include at least one symbol');
			}	

			if (isset($attributes['password2']) && $attributes['password2'] != $attributes['password']) {
				return array('response' => 'error', 'message' => 'Passwords do not match');
			}

		} else {
		
       		// if no password has been provided, then generate one
            $attributes['password'] = $this->generate_password();
            
        }

        // get roles in hierarchical order
        $roles_hierarchical = json_decode($vce->site->site_roles, true);
        
        // validate role id, or if one is not provided, make this user the lowest hierachical role
       	foreach ($roles_hierarchical as $each_role) {
       	
       		foreach ($each_role as $key=>$value) {
       		
       			$role_id = $value['role_id'];
       			
       			// break out of foreach loop when check finds a match
       			if (isset($attributes['role_id']) && $attributes['role_id'] == $value['role_id']) {
       				break 2;
       			}

			}
       	
       	}
       	
       	// set role_id
       	$attributes['role_id'] = $role_id;
       	
       	// create a new vector
       	$vector = $vce->user->create_vector();
       
        // call to user class to create_hash function
        $hash = $this->generate_hash($attributes['email'], $attributes['password']);

		// create new row in users table
        $user_data = array(
            'vector' => $vector,
            'hash' => $hash,
            'role_id' => $role_id,
        );

        $new_user_id = $vce->db->insert('users', $user_data);

		$lookup = $this->generate_lookup($attributes['email']);
		
		// if a value has been set for lookup, then set minutia with that value
		if (isset($attributes['lookup'])) {
			$lookup_minutia = $attributes['lookup'];
			unset($attributes['lookup']);
		} else {
			$lookup_minutia = '';
		}
		
		// hook to change lookup_minutia
		if (isset($vce->site->hooks['user_create_lookup_minutia'])) {
			foreach ($vce->site->hooks['user_create_lookup_minutia'] as $hook) {
				$lookup_minutia = call_user_func($hook, $attributes, $vce);
			}
		}
		
        $records = array();
        
        // add a lookup
        $records[] = array(
            'user_id' => $new_user_id,
            'meta_key' => 'lookup',
            'meta_value' => $lookup,
            'minutia' => $lookup_minutia,
        );
        
		// encode email
		$encrypted = $vce->site->encryption($attributes['email'], $vector);
		$minutia = user::order_preserving_hash($attributes['email']);
        
        // add email
        $records[] = array(
            'user_id' => $new_user_id,
            'meta_key' => 'email',
            'meta_value' => $encrypted,
            'minutia' => $minutia
        );
        
        $vce->db->insert('users_meta', $records);
        
        // clean-up before sending to add_attributes
		unset($attributes['email'], $attributes['email2'], $attributes['password'], $attributes['password2'], $attributes['show-password']);
		
		// add created_at and updated_at
		$attributes['created_at'] = $attributes['updated_at'] = time();
		
		$this->add_attributes($attributes, true, $new_user_id);
		
		return array('response' => 'success', 'message' => 'New user has been created', 'user_id' => $new_user_id);
    
    }
    
    

    /**
     * legacy
     */
    public static function create_user($role_id, $attributes) {

		$attributes['role_id'] = $role_id;
		
		global $vce;
		return $vce->user->create($attributes);
       
    }
    
    /**
     * Update an existing user
     *
     * @param integer $user_id the user id.
     * @param string $attributes array of attributes and values.
     * @param string $role_id the role_id.
     * @return string return error message if there is an error, null if success
     *
     * notes: If you pass in a value for $attributes['lookup'] it will be saved as minutia in that users_meta row
     */
    public function update($attributes) {
    
    	global $vce;
    	
    	// make sure these values are removed
    	unset($attributes['type'], $attributes['procedure']);
    	
    	$message = array();

		// who are we updating?
		if (!empty($attributes['user_id'])) {
			$user_id = $attributes['user_id'];
		} else {
			// otherwise use the user_id of the current user
			$user_id = $vce->user->user_id;
		}
		
		// get current
		$current_attributes = $this->find_users(array('user_ids' => $user_id), false, true);
		
		$current_role = $current_attributes[0]->role_id;
		$current_hash = $current_attributes[0]->hash;
		$current_vector = $current_attributes[0]->vector;
		$current_email = $current_attributes[0]->email;
		
		if (!empty($attributes['password'])) {
		
			// is this a password update? 
			if (!empty($attributes['password2'])) {
			
				if (strlen($attributes['password']) < 6) {
					return array('response' => 'error', 'message' => 'Passwords must be least 6 characters in length');
				}
		
				if (!preg_match("#[0-9]+#", $attributes['password'])) {
					return array('response' => 'error', 'message' => 'Password must include at least one number');
				}

				if (!preg_match("#[a-zA-Z]+#", $attributes['password'])) {
					return array('response' => 'error', 'message' => 'Password must include at least one letter');
				}

				if (!preg_match("#\W+#", $attributes['password'])) {
					return array('response' => 'error', 'message' => 'Password must include at least one symbol');
				}	

				if ($attributes['password'] != $attributes['password2']) {
					return array('response' => 'error', 'message' => 'Passwords do not match');
				}
				
				// create new hash
				$hash = $this->generate_hash($current_email, $attributes['password']);

				// update hash
				$update = array('hash' => $hash);
				$update_where = array('user_id' => $user_id);
				$vce->db->update('users', $update, $update_where);
				
				// success
				$message[] = 'password';
			
			} else {
				
				// email update
				
				if (isset($attributes['email']) && $attributes['email'] != $current_email) {
			
					if (!empty($attributes['email2']) && $attributes['email2'] != $attributes['email']) {
						return array('response' => 'error', 'message' => 'Email addresses do not match');
					}
	
					$attributes['email'] = filter_var(strtolower($attributes['email']), FILTER_SANITIZE_EMAIL);
		
					if (!filter_var($attributes['email'], FILTER_VALIDATE_EMAIL)) {
						return array('response' => 'error', 'message' => 'Email address is not valid');
					}
			
					// search for a match
					$email_match = $this->find_id_by_email($attributes['email']);
				
					if ($email_match) {
						return array('response' => 'error', 'message' => 'Email address is already in use');
					}
			
					// call to user class to create_hash function
					$update_hash = $this->generate_hash($current_email, $attributes['password']);
			
					// check that this is correct
					if ($update_hash != $current_hash) {
						return array('response' => 'error', 'message' => 'Password is not correct');
					}
				
					// new hash
					$hash = $this->generate_hash($attributes['email'], $attributes['password']);
				
					// ...and that's a good bingo. Email can be updated
					
					// save a pseudonym
					$query = "SELECT * FROM " . TABLE_PREFIX . "users_meta WHERE user_id=" . $user_id . " AND meta_key='email'";
					$email_pseudonym = $vce->db->get_data_object($query);
					
					$query = "SELECT * FROM " . TABLE_PREFIX . "users_meta WHERE user_id=" . $user_id . " AND meta_key='lookup'";
					$lookup_pseudonym = $vce->db->get_data_object($query);
					
					// save pseudonym
					$records[] = array(
						'user_id' => $user_id,
						'meta_key' => 'pseudonym',
						'meta_value' => $email_pseudonym[0]->meta_value,
						'minutia' => $lookup_pseudonym[0]->meta_value
					);
		
					$vce->db->insert('users_meta', $records);	
				
					// update hash
					$update = array('hash' => $hash);
					$update_where = array('user_id' => $user_id);
					$vce->db->update('users', $update, $update_where);
				
					// create lookup
					$lookup = $this->generate_lookup($attributes['email']);
					
					// if a value has been set for lookup, then set minutia with that value
					if (isset($attributes['lookup'])) {
						$lookup_minutia = $attributes['lookup'];
						unset($attributes['lookup']);
					} else {
						$lookup_minutia = '';
					}
		
					// hook to change lookup_minutia
					if (isset($vce->site->hooks['user_create_lookup_minutia'])) {
						foreach ($vce->site->hooks['user_create_lookup_minutia'] as $hook) {
							$lookup_minutia = call_user_func($hook, $attributes, $vce);
						}
					}
		
					// hook to change lookup_minutia
					if (isset($vce->site->hooks['user_update_lookup_minutia'])) {
						foreach ($vce->site->hooks['user_update_lookup_minutia'] as $hook) {
							$lookup_minutia = call_user_func($hook, $attributes, $vce);
						}
					}
				
					// update lookup
					$update = array('meta_value' => $lookup, 'minutia' => $lookup_minutia);
					$update_where = array('user_id' => $user_id, 'meta_key' => 'lookup');
					$vce->db->update('users_meta', $update, $update_where);
				
					// encode email
					$encrypted = $vce->site->encryption($attributes['email'], $current_vector);
					$minutia = user::order_preserving_hash($attributes['email']);	

					// update email
					$update = array('meta_value' => $encrypted, 'minutia' => $minutia);
					$update_where = array('user_id' => $user_id, 'meta_key' => 'email');
					$vce->db->update('users_meta', $update, $update_where);
					
					// set value in user object so that change appears
					if ($user_id == $vce->user->user_id) {
						$vce->user->email = $attributes['email'];
					}
					
					// success
					$message[] = 'email address';
				
				}
			
			}

		}

        // update role_id    	
    	if (isset($attributes['role_id']) && $attributes['role_id'] != $current_role) {
    	
			// get roles in hierarchical order
			$roles_hierarchical = json_decode($vce->site->site_roles, true);
	
			// validate role id, or if a valid one is not provided, make this user the lowest hierachical role
			foreach ($roles_hierarchical as $each_role) {
	
				foreach ($each_role as $key=>$value) {
		
					$role_id = $value['role_id'];
			
					// break out of foreach loop when check finds a match
					if (isset($attributes['role_id']) && $attributes['role_id'] == $value['role_id']) {
						break 2;
					}

				}
	
			}
	
			// set role_id
			$attributes['role_id'] = $role_id;
		
			if (isset($attributes['user_id'])) {
		
				// update
				$update = array('role_id' => $attributes['role_id']);
				$update_where = array('user_id' => $attributes['user_id']);
				$vce->db->update('users', $update, $update_where);
			
				// success
				$message[] = 'role';

			}
				
       	}
       	
        // clean-up before sending to add_attributes
		unset($attributes['email'], $attributes['email2'], $attributes['password'], $attributes['password2'], $attributes['show-password']);

		// add created_at and updated_at
		$attributes['updated_at'] = time();

    	$this->add_attributes($attributes, true, $user_id);
    	
		return array('response' => 'success', 'message' => 'User ' . implode(", ", $message) . ' has been updated');

    }

    /**
     * Update an existing user
     *
     * @param integer $user_id the user id.
     * @param string $attributes array of attributes and values.
     * @param string $role_id the role_id.
     * @return string return error message if there is an error, null if success
     */
    public static function update_user($user_id, $attributes, $role_id = null) {

		$attributes['user_id'] = $user_id;
		$attributes['role_id'] = $role_id;
		
		global $vce;
		return $vce->user->update($attributes);

    }


    /**
     * Delete a user
     *
     * @param integer $user_id
     * @return void
     */
    public static function delete_user($user_id, $extirpate = false) {

        global $vce;

        // delete user from database
        $where = array('user_id' => $user_id);
        $vce->db->delete('users', $where);

        // delete user from database
        $where = array('user_id' => $user_id);
        $vce->db->delete('users_meta', $where);
        
        // delete datalists associated with this user
        $query = "SELECT * FROM " . TABLE_PREFIX . "datalists WHERE user_id=" . $user_id;
		$datalists = $vce->db->get_data_object($query);
		
		if (!empty($datalists)) {
			foreach ($datalists as $key=>$value) {
				// $vce->remove_datalist(array('datalist_id' => $value->datalist_id));
			}
		}
		
		// what to do with components created by this user?
		if ($extirpate) {
	
			$query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE meta_key='created_by' AND meta_value='" . $user_id . "'";
			$created_by = $vce->db->get_data_object($query);
		
			if (!empty($created_by)) {
				foreach ($created_by as $key=>$value) {
					// there are some logistical issues with this...
					// what if another user's componennt is a sub-component of the one we are deleting?
					// currently it will be deleted!
					Component::extirpate_component($value->component_id);
				}
			}
			
		}
		
    }


    /**
     * Creates vector.
     * @return string encrypted unique vector
     */
    public static function create_vector() {
    
    	/*
    
        if (OPENSSL_VERSION_NUMBER) {
            $vector = base64_encode(openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc')));
        } else {
             $vector = base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM));
        }
        
        // index:62  Binary:111110  char:+
        // + is being converted to a space in the stored cookie value in Chrome 80+, which results in the vector being only 15 bytes long, so create another without the +
		if (strpos($vector, '+') !== false) {
			return self::create_vector();
		}
		
		return $vector;
		
		*/
		
        global $vce;
        return $vce->site->create_vector();
        
    }

    /**
     * Get vector length
     * @return string vector length
     *
     * Note: If this changes, also update in vce-media.php
     */
    public static function vector_length() {
    	/*
        if (OPENSSL_VERSION_NUMBER) {
            return openssl_cipher_iv_length('aes-256-cbc');
        } else {
            return mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
        }
        */
        global $vce;
        return $vce->site->vector_length();
    }

    /**
     * Encrypts text with vector as salt
     * @param string $encode_text
     * @param string $vector
     * @return string
     */
    public static function encryption($encode_text, $vector) {
    	/*
        if (isset($vector) && !empty($vector)) {
            if (OPENSSL_VERSION_NUMBER) {
                return base64_encode(openssl_encrypt($encode_text, 'aes-256-cbc', hex2bin(SITE_KEY), OPENSSL_RAW_DATA, base64_decode($vector)));
            } else {
                return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, hex2bin(SITE_KEY), $encode_text, MCRYPT_MODE_CBC, base64_decode($vector)));
            }
        }
        */
        global $vce;
        return $vce->site->encryption($encode_text, $vector);
    }

    /**
     * Decrypts text with vector as salt
     * @param string $decode_text
     * @param string $vector
     * @return string
     *
     * Note: If this changes, also update in vce-media.php
     */
    public static function decryption($decode_text, $vector) {
    	/*
        if (isset($vector) && !empty($vector)) {
            if (OPENSSL_VERSION_NUMBER) {
                return trim(openssl_decrypt(base64_decode($decode_text), 'aes-256-cbc', hex2bin(SITE_KEY), OPENSSL_RAW_DATA, base64_decode($vector)));
            } else {
                return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, hex2bin(SITE_KEY), base64_decode($decode_text), MCRYPT_MODE_CBC, base64_decode($vector)));
            }
        }
        */
        global $vce;
        return $vce->site->decryption($decode_text, $vector);
        
    }
    
    
	public function search($search_phrase, $search_attributes = array(), $limit_by_hierarchy = true) {
	
		global $vce;

		$user_attributes = json_decode($vce->site->user_attributes, true);

        // break into array based on spaces
        $search_values = explode('|', preg_replace('/\s+/', '|', $search_phrase));

        // get roles in hierarchical order
        $roles_hierarchical = json_decode($vce->site->site_roles, true);
        
        $roles = array();

        // find roles that are equal to and lesser than current user
        foreach ($roles_hierarchical as $each_role) {
        	foreach ($each_role as $role_attributes) {
        		if ($role_attributes['role_id'] == $vce->user->role_id) {
        			$user_role_hierarchy = $role_attributes['role_hierarchy'];
        		}
        		if (isset($user_role_hierarchy) && $role_attributes['role_hierarchy'] >= $user_role_hierarchy) {
        			$roles[] = $role_attributes['role_id'];
        		}
        	}
        }
        
        if (empty($roles)) {
            echo json_encode(array('response' => 'success', 'results' => null));
            return;
        }
        
        // get all users of specific roles as an array
        $query = "SELECT a.user_id,a.vector,a.role_id,b.meta_key,b.meta_value FROM " . TABLE_PREFIX . "users AS a JOIN " . TABLE_PREFIX . "users_meta AS b ON a.user_id = b.user_id";
    	if ($limit_by_hierarchy) {
    		$query .=  " WHERE a.role_id IN (" . implode(",", $roles) . ")";
    	}
        
        if (!empty($search_attributes)) {
        	if (is_array($search_attributes)) {
       			$query .= " AND b.meta_key IN ('" . implode("','", $search_attributes) . "')";
       		} else {
       			$query .= " AND b.meta_key IN ('" . $search_attributes . "')";
       		}
        }
        // get results
        $users_meta_data = $vce->db->get_data_object($query, 0);
        
        foreach ($users_meta_data as $key=>$value) {
    
        	if (!isset($all_users[$value['user_id']])) {
        		$all_users[$value['user_id']] = array(
        		'user_id' => $value['user_id'],
        		'role_id' => $value['role_id']
        		);
        		$match[$value['user_id']] = 0;
        	}

            // find user_id
            for ($i = 0; $i < count($search_values); $i++) {
				if (is_numeric($search_values[$i]) && $value['user_id'] == $search_values[$i]) {
					$match[$value['user_id']] = 1;
				}
            }

            // skip a few meta_key that we don't want to allow searching in
            if ($value['meta_key'] == 'lookup' || $value['meta_key'] == 'persistent_login') {
                continue;
            }
            
            // decrypt the values
            if (isset($user_attributes[$value['meta_key']])) {
            	if (empty($user_attributes[$value['meta_key']]['expose'])) {
            		$all_users[$value['user_id']][$value['meta_key']] = User::decryption($value['meta_value'], $value['vector']);
            	} else {
					$all_users[$value['user_id']][$value['meta_key']] = $value['meta_value'];
            	}
            } else {
            	$all_users[$value['user_id']][$value['meta_key']] = User::decryption($value['meta_value'], $value['vector']);
            }
            
            // test multiples
            for ($i = 0; $i < count($search_values); $i++) {
            
                $pos = strrpos(strtolower($all_users[$value['user_id']][$value['meta_key']]), strtolower($search_values[$i]));

                if ($pos === false) {
                	continue;
                } else {
					if (!isset($counter[$value['user_id']][$i])) {
						// add to specific match
						if (!isset($match[$value['user_id']])) {
						$match[$value['user_id']] = 1;
						} else {
						$match[$value['user_id']]++;
						}
						// set a counter to prevent repeats
						$counter[$value['user_id']][$i] = true;
						// break so it only counts once for this value
						break;
					}
                }
            }
        }

        // cycle through match to see if the number is equal to count
        foreach ($match as $match_user_id => $match_user_value) {
            // unset vector
           	unset($all_users[$match_user_id]['vector']);
            // if there are fewer than count, then unset
            if ($match_user_value < count($search_values)) {
                // unset user info if the count is less than the total
            	unset($all_users[$match_user_id]);
            }
        }
        
        // hook to work with search results
        if (isset($vce->site->hooks['user_search'])) {
            foreach ($vce->site->hooks['user_search'] as $hook) {
                $all_users = call_user_func($hook, $all_users);
            }
        }

        return $all_users;
	
	}
    
    
    /**
     * Legacy
     */ 
    public static function get_users($users_info = array(), $key_by_user_id = false, $display_arcane = false) {

		global $vce;
		return $vce->user->find_users($users_info, $key_by_user_id, $display_arcane);

	}
	
	
	/**
     * shortcut to find_users
     * @param array $user_id
     * @return find_users results
     */
	public function find_user_by_id($user_id) {
		return $this->find_users(array('user_ids' => $user_id))[0];
	}


    /**
     * Gets users based on roles or user_ids and filters by meta_data
     * @param array $users_info
     * @return array $site_users
     */
    public function find_users($users_info = array(), $key_by_user_id = false, $display_arcane = false, $as_object = true) {
    
    	global $vce;
    	
        if (is_array($users_info)) {
			
			if (isset($users_info['user_ids']) || isset($users_info['roles'])) {

				// convert pipeline to comma, and trim any comma that are out of place
				$user_ids = isset($users_info['user_ids']) ? trim(str_replace('|', ',', $users_info['user_ids']), ',') : null;
				$roles = isset($users_info['roles']) ? trim(str_replace('|', ',', $users_info['roles']), ',') : null;

			} else {
			
				// simple array of user ids
				$user_ids = implode(',',$users_info);
				$roles = null;
			
			}

		} else {
			
			// add a single users_ids if not an array
			$user_ids = $users_info;
			$roles = null;
		
		}

        $site_users = array();
        
        if (isset($users_info['roles'])) {
            if ($users_info['roles'] == "all") {
                $query = "SELECT * FROM " . TABLE_PREFIX . "users";
            } else {
                if (preg_match('/[^\d,]+/', $roles, $matches)) {
        			return false;
        		}
                $query = "SELECT * FROM " . TABLE_PREFIX . "users WHERE role_id in (" . $roles . ")";
            }
        } else if (!empty($user_ids)) {
        	if (preg_match('/[^\d,]+/', $user_ids, $matches)) {
        		return false;
        	}
            $query = "SELECT * FROM " . TABLE_PREFIX . "users WHERE user_id in (" . $user_ids . ")";
        } else {
            // nothing to look for so return false
            return false;
        }

        $all_users = $vce->db->get_data_object($query);

        // return false if results are empty
        if (empty($all_users)) {
            return false;
        }
        
        // get roles in hierarchical order
        $roles_hierarchical = json_decode($vce->site->roles, true);

        // rekey userdata
        foreach ($all_users as $each_user) {
            $users[$each_user->user_id]['user_id'] = $each_user->user_id;
            $users[$each_user->user_id]['role_id'] = $each_user->role_id;
            $users[$each_user->user_id]['role_hierarchy'] = $roles_hierarchical[$each_user->role_id]['role_hierarchy'];
            $users[$each_user->user_id]['role_name'] = $roles_hierarchical[$each_user->role_id]['role_name'];
            if ($display_arcane) {
           		$users[$each_user->user_id]['vector'] = $each_user->vector;
           		$users[$each_user->user_id]['hash'] = $each_user->hash;
           	}
            $users_vector[$each_user->user_id] = $each_user->vector;
        }
        
        // get all meta_data for selected users
        $query = "SELECT user_id, meta_key, meta_value FROM  " . TABLE_PREFIX . "users_meta WHERE user_id IN (" . implode(',', array_keys($users)) . ")";
        $meta_data = $vce->db->get_data_object($query);
        
		// get site user attributes
		$user_attributes = json_decode($vce->site->user_attributes, true);
		
		// add some additional attributes
		$user_attributes['created_at'] = array('expose' => true);
		$user_attributes['updated_at'] = array('expose' => true);

        // add values to users array
        foreach ($meta_data as $meta_item) {
        
        	// skip
        	if (in_array($meta_item->meta_key, array('lookup','pseudonym','persistent_login'))) {
        		continue;
        	}
        	
        	// we are setting this up in case there are more conditions later
        	$decrypt = true;
       
			if (!empty($user_attributes[$meta_item->meta_key]['expose'])) {
				$decrypt = false;
			}
        
        	if ($decrypt) {
            	$users[$meta_item->user_id][$meta_item->meta_key] = $vce->db->clean(user::decryption($meta_item->meta_value, $users_vector[$meta_item->user_id]));
        	} else {
        		$users[$meta_item->user_id][$meta_item->meta_key] = $vce->db->clean($meta_item->meta_value);
        	}
        
        }

		// return this as an object
		foreach ($users as $each_user) {
			// if key_by_user_id is true, then key array by user_id
			if ($key_by_user_id) {
				$users_list[$each_user['user_id']] = ($as_object) ? (object) $each_user : $each_user;
			} else {
				$users_list[] = ($as_object) ? (object) $each_user : $each_user;
			}
		}

        return $users_list;

    }

    /*
     * create order preserving hash
     * @param string $sring
     * @return $hash
     */
    public static function order_preserving_hash($string) {

        // get cipher
        $cipher = self::oph_cipher();

        // call and return hash output for string
        return self::oph_output($string, $cipher);

    }

    /*
     * create order preserving hash cipher
     * @return array $cipher
     */
    public static function oph_cipher() {

        // set the range
        $range = array_merge(range('0', '9'), range('a', 'z'));

        // to do: allow for a hook to change the value of $range

        // A "modular exponentiation" function, with a numerical starting point based on a site specific key, to assign a numerical value from 0 - 100 for the range of the alphabet.
        $mef = function ($previous, $counter = 0, $additional = 0, $total = 0, $cipher = array()) use (&$mef, $range) {

            // modulo is set to prime number
            $modulo = 101;

            // calculate the value of current
            $current = ($previous * 4) % $modulo;

            // if the value of current equals zero due to the tabulated_key value, reduce modulo by one and try again
            while ($current == 0) {
                $current = ($previous * 4) % $modulo--;
            }

            // get bracket that each range item can be
            $bracket = (99 / count($range)) + $additional;

            // get an obfuscated value within a range
            $location = ceil(($bracket * $current) / 99);

            // additional to add to next time through
            $new_additional = $bracket - $location;

            // add to array which will be returned
            $cipher[$range[$counter]] = $total + $location;

            // keep track of values
            $total += $location;

            // advance counter
            $counter++;

            // check to see if we should do a recursive call back to this function
            if ($counter < count($range)) {

                return $mef($current, $counter, $new_additional, $total, $cipher);

            } else {

                return $cipher;

            }

        };

        $tabulated_key = 0;

        // get ascii total for site_key to use as $previous to start
        for ($i = 0, $j = 64, $tab = 0; $i < $j; $i++) {
            $tabulated_key += ord(SITE_KEY[$i]);
        }

        // call to the annonymous function to get cipher values and return them
        return $mef($tabulated_key);

    }

    /*
     * create order preserving hash cipher
     * @param string $string
     * @return array $cipher
     * @return array $hashed
     */
    public static function oph_output($string, $cipher) {

        // can swap
        $string = str_replace('.', 'a', $string);

        // if (strpos($string = htmlentities($string, ENT_QUOTES, 'UTF-8'), '&') !== false) {
        //    $string = html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|tilde|uml);~i', '$1', $string), ENT_QUOTES, 'UTF-8');
        // }

        // get only the letters in lowercase
        $string = preg_replace('/[^\da-z]/i', '', strtolower($string));

        if (strlen($string) == 0) {
            return null;
        }

        // split the string into an array of individual letters
        $letters = str_split($string);

        $decistring = "";

        foreach ($letters as $each_letter) {

            $position = $cipher[$each_letter];

            // add a zero if under 10
            if ($position < 10) {
                $decistring .= '0';
            }

            $decistring .= (string) $position;

        }

        $grab = 4;

        while (strlen($decistring) % $grab != 0) {

            $decistring .= '0';

        }

        $limit = 30;

        if (strlen($decistring) < $limit) {

            $decistring .= '00' . rand(0, 9) . rand(0, 9);

            do {

                $decistring .= rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);

            } while (strlen($decistring) < ($limit + 1));

        }

        // clip off anything extra
        $decistring = substr($decistring, 0, $limit);

        // split into grab values
        preg_match_all("/\d{" . $grab . "}/", $decistring, $grouplets);

        $full_value = "";

        $tabulated_key = 0;

        // get ascii total for site_key to use as $previous to start
        for ($i = 0, $j = 64, $tab = 0; $i < $j; $i++) {
            $tabulated_key += ord(SITE_KEY[$i]);
        }

        // 26 to the 4th power, minus the tabulated_key
        $field = 456976 - $tabulated_key;

        foreach ($grouplets[0] as $each_key => $each_value) {

            /*
            While it would be nice to skip every 2nd $grouplets to reduce the total length of the hashed value,
            there is an issue when dealing with decimal values resulting from aara and aarb, example:

            $names = array(
            'aara',
            'aarb'
            );

            values are too close together in the first group, so it can cause wrong sorting depending on site key value

            aara
            ->
            0404 <= 1st key
            6704 <= 2nd key
            18461.8304 <= 1
            30.6417990934 <= 2
            18492.4721991 <= total

            6704 <= 1st key
            0404 <= 2nd key
            306356.7104 <= 1
            1.846552332 <= 2
            306358.556952 <= total

            - - -

            aarb
            ->
            0404 <= 1st key
            6705 <= 2nd key
            18461.8304 <= 1
            30.6463697675 <= 2
            18492.4767698 <= total

            6705 <= 1st key
            0404 <= 2nd key
            306402.408 <= 1
            1.846552332 <= 2
            306404.254552 <= total

            // the skip every other would look like this
            if ($each_key % 2 != 0) {
            continue;
            }

             */

            // second_key is used to salt
            $second_key = ($each_key + 1) < count($grouplets[0]) ? $grouplets[0][$each_key + 1] : $grouplets[0][0];

            // echo $each_value . ' <= 1st key <br>';
            // echo $second_key . ' <= 2nd key<br>';
            //
            // echo ($field * ($each_value / 10000)) . ' <= 1<br>';
            // echo ($second_key / (9999 / ($field / 9999))) . ' <= 2<br>';

            $seccond_value = $second_key / (9999 / ($field / 9999));

            $number = ($field * ($each_value / 10000)) + $seccond_value;

            // echo $number . ' <= total<br><br>';

            $alphas = range('a', 'z');

            $divider = floor($number / 26);

            //echo $divider . '<br>';

            $primary = floor($divider / 676);

            //echo $primary . ' <= 1<br>';

            $secondary = floor($divider / 26) - ($primary * 26);

            //echo $secondary . ' <= 2<br>';

            // 676 = 26 * 26
            $tertiary = $divider - ($primary * 676) - ($secondary * 26);

            //echo $tertiary . ' <= 3<br>';

            // 17576 = 26 * 26 * 26
            $quaternary = $number - ($primary * 17576) - ($secondary * 676) - ($tertiary * 26);

            // echo $quaternary . ' <= 4<br>';
            // echo '- - -<br>';

            // prevent over extention on all of these
            $primary_number = ($primary < 26) ? $alphas[$primary] : $alphas[25];

            $secondary_number = ($secondary < 26) ? $alphas[$secondary] : $alphas[25];

            $tertiary_number = ($tertiary < 26) ? $alphas[$tertiary] : $alphas[25];

            $quaternary_number = ($quaternary < 26) ? $alphas[$quaternary] : $alphas[25];

            $quat = $primary_number . $secondary_number . $tertiary_number . $quaternary_number;

            //echo '=> ' . $quat . '<br>';

            $full_value .= $quat;

        }

        return $full_value;

    }
    
    
    /**
     * Gets users based on roles or user_ids and filters by meta_data
     * @param array $user
     * @param array $vce
     * @param boolean $required - overrides required, and is useful for admin functionality
	 * @param boolean $legerdemain - (leger de main == sleight of hand) overrides conceal and editable, and is specifically for admin funcationality
     * @return string
     */
    public static function user_attributes_fields($user, $vce, $required = true, $legerdemain = false) {
    	
    	// get site user attributes
		$user_attributes = json_decode($vce->site->user_attributes, true);

    	$content = null;
    
		foreach ($user_attributes as $user_attributes_key=>$user_attributes_value) {
		
			// skip if conceal
			if ($user_attributes_value['type'] == 'conceal' && !$legerdemain) {
				continue;
			}
		
            // nice title for this user attribute
            $title = isset($user_attributes_value['title']) ? ucwords(str_replace('_', ' ', $user_attributes_value['title'])) : ucwords(str_replace('_', ' ', $user_attributes_key));
			
			// if there is a default value 
			$default_value = isset($user_attributes_value['default']) ? $user_attributes_value['default'] : null;

			// attribute value
			$attribute_value = isset($user->$user_attributes_key) ? $user->$user_attributes_key : $default_value;

			$options = null;

			// if a datalist has been assigned
			if (isset($user_attributes_value['datalist'])) {

				if (!is_array($user_attributes_value['datalist'])) {
					$datalist_field = 'datalist';
					$datalist_value = $user_attributes_value['datalist'];
				} else {
					$datalist_field = array_keys($user_attributes_value['datalist'])[0];
					$datalist_value = $user_attributes_value['datalist'][$datalist_field];
				}

				$options_data = $vce->get_datalist_items(array($datalist_field => $datalist_value));

				if (!empty($options_data)) {
					
					// enabling this for all
					// if (empty($user_attributes_value['required']) || $required === false) {
						$options[] = array('name' => '','value' => '');
					// }

					foreach ($options_data['items'] as $option_key=>$option_value) {
						
						$options[] = array('name' => $option_value['name'],'value' => $option_key);
				
					}
				
				}

			}
			
			if (!empty($user_attributes_value['editable']) || $legerdemain) {
			
				$value = isset($user->$user_attributes_key) ? $user->$user_attributes_key : null;

				// email input
				$input = array(
				'type' => $user_attributes_value['type'],
				'name' => $user_attributes_key,
				'value' => $value,
				'data' => array(
					'autocapitalize' => 'none'
				)
				);
			
				// make required
				if (!empty($user_attributes_value['required']) && $required) {
					$input['data']['tag'] = 'required';
				}
				
				// make required
				if (!empty($options)) {
					$input['options'] = $options; 
				}
				
				// add any html attributes
				if (!empty($user_attributes_value['html_attributes'])) {
				
					$html_attributes = explode(',', $user_attributes_value['html_attributes']);
				
					foreach ($html_attributes as $each_html_attribute) {
			
						list($key,$value) = explode('=', trim($each_html_attribute));
					
						$input['data'][$key] = $value;
					
					}
			
				}
				
			
			} else {
			
				$input = isset($user->$user_attributes_key) ? $user->$user_attributes_key : '&nbsp;';
			
			}
			
			// hook to allow addition user attributes
			if (isset($vce->site->hooks['user_user_attributes_fields'])) {
				foreach($vce->site->hooks['user_user_attributes_fields'] as $hook) {
					$title = call_user_func($hook, $user_attributes_value, $vce);
				}
			}
			
			$required_text = $title . ' required';
			
			$content .= $vce->content->create_input($input, $title, $required_text);


		}
		
		
		return $content;
		
	}
    
    

    /**
     * Allows for calling object properties from template pages in theme and then return or print them.
     * @param string $name
     * @param array $args
     */
    public function __call($name, $args) {
        if (isset($this->$name)) {
            if ($args) {
                // return object property
                return $this->$name;
            } else {
                // print object property
                echo $this->$name;
            }
        } else {
            if (!VCE_DEBUG) {
                return false;
            } else {
                // print name of none existant component
                echo 'Call to non-existant property ' . '$' . strtolower(get_class()) . '->' . $name . '()' . ' in ' . debug_backtrace()[0]['file'] . ' on line ' . debug_backtrace()[0]['line'];
            }
        }
    }

    /**
     * Returns false instead of "Notice: Undefined property error" when reading data from inaccessible properties
     */
    public function __get($var) {
        return false;
    }

}