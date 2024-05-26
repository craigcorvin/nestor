<?php
/**
 * Creates object to hold site data.
 * Retrieves site meta data and calls the components listed there to create the login,
 * and other components relevant to the entire site.
 */

class Site {

 	/**
     * Instantiates site object
     */
    public function __construct($vce) {

        $vce->site = $this;

        $this->set_memory($vce);

        $this->load_site_meta_data($vce);
        
        $this->set_timezone($vce);

        $this->setup_dynamic_site_url($vce);

        // add site_url to vce object
        $vce->site_url = $this->site_url;

        $full_requested_url = $this->parse_requested_url($vce);

        $query_string = $this->parse_query_string($vce, $full_requested_url);

        $post_variables = $this->parse_post_variables($vce);

        $this->parse_path_routing($vce, $query_string, $post_variables);

		// adds hooks to site object
		$this->hooks = $this->get_hooks($vce);
		
		$this->add_cron_task($vce);
		
		$this->add_associated_files($vce);

    }

    /**
     * Set the timezone
     *
     * @param VCE $vce
     * @return void
     */
    public function set_timezone($vce) {

        // Set timezone
        if (!empty($this->timezone)) {
            // if timezone has been stored in site_meta
            date_default_timezone_set($this->timezone);
        } elseif (defined('TIMEZONE')) {
            // if TIMEZONE has been defined in vce-config.php
            date_default_timezone_set(TIMEZONE);
        } elseif (!ini_get('date.timezone')) {
            date_default_timezone_set('UTC');
        }

    }

    /**
     * Set the php memory
     *
     * @param VCE $vce
     * @return void
     */
    public function set_memory($vce) {

        // check that memory_limit is at least set to 40M
        if ($vce->convert_to_bytes(ini_get('memory_limit')) < 41943040) {
            @ini_set('memory_limit', '40M');
        }

    }

    /**
     * Enable dynamic site url used for local debugging
     *
     * @param VCE $vce
     * @return void
     */
    public function setup_dynamic_site_url($vce) {

        // ignore database field and set site_url from $_SERVER Server and execution environment information
        if (defined('DYNAMIC_SITE_URL') && DYNAMIC_SITE_URL === true) {

            $ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
            $sp = strtolower($_SERVER['SERVER_PROTOCOL']);
            $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
            $port = $_SERVER['SERVER_PORT'];
            $port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
            $host = (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null);
            $host = isset($host) ? $host : $_SERVER['SERVER_NAME'] . $port;

            // are we installed in a sub-directory?
            $directory = null;
            if (!empty($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['SCRIPT_FILENAME'])) {
                // directory is the difference
                $directory = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('/index.php', '', $_SERVER['SCRIPT_FILENAME']));
            }

            // set site_url to dynamic version
            $this->site_url = $protocol . '://' . $host . $directory;

        }
    }

    /**
     * Load the meta data from the DB for the site object.
     * Also sets up cron jobs and roles
     *
     * @param VCE $vce
     * @return void
     */
    public function load_site_meta_data($vce) {

        $query = "SELECT * FROM " . TABLE_PREFIX . "site_meta";
        $components_meta = $vce->db->get_data_object($query);

        foreach ($components_meta as $each_meta) {

            // cron_task
            if ($each_meta->meta_key == "cron_task") {

                // timestamp of cron_task is older than current time
                if ($each_meta->minutia < time()) {
                
                	if (!isset($this->cron_task)) {
                		$this->cron_task = array();
                	}

                    // add to array using timestamp as key
                    $this->cron_task[$each_meta->minutia] = array(
                        'id' => $each_meta->id,
                        'value' => $each_meta->meta_value,
                    );

                }

                // skip to next
                continue;
            }

            // create hierarchical json object of roles and place into into $site->site_roles
            if ($each_meta->meta_key == "roles") {
                // get and decode roles that are stored in database
                $roles = json_decode($each_meta->meta_value, true);

                // create a hierarchical roles array
                foreach ($roles as $roles_key => $roles_value) {
                    // move the range up to tens
                    if (isset($roles_value['role_hierarchy'])) {
                        $current_hierarchy = ($roles_value['role_hierarchy'] * 100);
                        while (isset($roles_hierarchical[$current_hierarchy])) {
                            // if the current key exists, add one and see if that exists
                            $current_hierarchy++;
                        }
                        $roles_hierarchical[$current_hierarchy][$roles_key] = $roles[$roles_key];
                        $roles_hierarchical[$current_hierarchy][$roles_key]['role_id'] = $roles_key;
                    }
                }

                if (isset($roles_hierarchical)) {
                    // ksort by keys asc / krsort by keys desc
                    ksort($roles_hierarchical);
                    // rekey to make it look nice
                    $roles_hierarchical = array_values($roles_hierarchical);
                    // cast as object
                    $this->site_roles = json_encode((object) $roles_hierarchical);
                }
            }   

            $key = $each_meta->meta_key;

            $this->$key = $each_meta->meta_value;

            if (!empty($each_meta->minutia)) {
                $minutia = $key . "_minutia";
                $this->$minutia = $each_meta->minutia;
            } 

        }

    }

    /**
     * Parses the requested url from $_SERVER
     *
     * @param VCE $vce
     * @return void
     */
    public function parse_requested_url($vce, $request_uri = null) {
		
        if ($request_uri == NULL) {
            $request_uri = $_SERVER['REQUEST_URI'];
        }

		// Is this a browser? The reason for this workaround is related to mobile app issues
		if (!empty($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'application/js') === false)) {
		
			// check that http_host and PHP_URL_HOST match
			if ($_SERVER['HTTP_HOST'] != parse_url($vce->site->site_url, PHP_URL_HOST) && !parse_url($vce->site->site_url, PHP_URL_PORT)) {
				header('location: ' . parse_url($vce->site->site_url, PHP_URL_SCHEME) . '://' . parse_url($vce->site->site_url, PHP_URL_HOST) . $request_uri);
			}
        
       }
       
        // remove extra / at end of url
        if (preg_match('/\/{2,}$/',$request_uri)) {
            header('location: ' . parse_url($vce->site->site_url, PHP_URL_SCHEME) . '://' . $_SERVER['HTTP_HOST'] . rtrim($request_uri,'/') . '/');
        }

        // check for https within site_url only when it is set to https
        if (parse_url($this->site_url, PHP_URL_SCHEME) == "https") {
           // HTTPS server variables for both Apache and Nginx
           if (isset($_SERVER['HTTPS']) == 'on' || !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) == 'on') {
               // empty slot for when https is working.
           } else {
               // force https
               header('location: ' . 'https://' . $_SERVER['HTTP_HOST'] . $request_uri);
           }
        }

        // the url that has been requested
        $full_requested_url = $request_uri;
        $requested_url_array = explode('?', $full_requested_url);

        // url path without query string
        $requested_url = trim($requested_url_array[0], '/');
       
        // get the trimmed site url path
        $site_url = trim(parse_url($this->site_url, PHP_URL_PATH ), '/');
       
        // clean up the requested url by triming $requested_url slashes before and after, removing $site_url from $requested_url. 
        // # is used instead of / to prevent unknown modifier error
        $this->requested_url = trim(preg_replace("#^$site_url#i", '', $requested_url), '/');
        // $requested_url is now consistant with $site->site_url as base url for site
       
        return $full_requested_url;
    }

    /**
     * Parses the query string from the full requested url
     * 
     * @param VCE $vce
     * @return string query string
     */
    public function parse_query_string($vce, $full_requested_url) {
    
        $query_string = null;
        
        // the url that has been requested
		$requested_url_array = explode('?', $full_requested_url);
		
        // if a query string has been added to the requested URL, sanitize values and store in $page->query_string 
        if (defined('QUERY_STRING_INPUT')) {
        
        	$query_string = array();
        
            // check if a query string is included within URL
            if (isset($requested_url_array[1])) {
            	
            	// create array of key=>value pairs
            	$pairs = explode('&', $requested_url_array[1]);
            	
            	// cycle through pairs
            	foreach ($pairs as $each_pair) {
            	
            		// check to see if the pairs are pairs, viz key=value
            		if (!empty($each_pair) && strpos($each_pair, '=') !== false) {
            			
            			// create array of key=>values
						list($key,$value) = explode('=', $each_pair, 2);
						
						// if they have valued, do something with them
						if (!empty($key) && !empty($value)) {
						
							// sanitize, replacement for FILTER_SANITIZE_STRING
							$key = str_replace(["'", '"'], ['&#39;', '&#34;'], preg_replace('/\x00|<[^>]*>?/', '', $key));
							$value = str_replace(["'", '"'], ['&#39;', '&#34;'], preg_replace('/\x00|<[^>]*>?/', '', $value));

						 	if (!isset($query_string[$key])) {
								
								// add to array
								$query_string[$key] = $value;
								
							} else {
								
								// repeat key, check if array
								if (is_array($query_string[$key])) {
									// add additional values
									$query_string[$key][] = $value;
								} else {
									// create array of values
									$query_string[$key] = array($query_string[$key], $value);
								}
							}
							
            			}
            		
            		}
            	
            	}
            	
            	$this->query_string = $vce->query_string = json_encode($query_string);       
           
            }
            
        } else {
        	// strip off query string and reload page
            if (isset($requested_url_array[1])) {
               header('location: ' . $requested_url_array[0]);
            }
        }

        return $query_string;
    }

    /**
     * Parses the post varibales from $_POST
     *
     * @param VCE $vce
     * @return string post varibles
     */
    public function parse_post_variables($vce) {

        $post_variables = null;

        // if post variables have been sent to same url, sanitize values and store in $this->post_variables
        if (!empty($_POST)) {
            $post_variables = array();
            foreach ($_POST as $key=>$value) {
                if (is_array($value)) {
                    $sanitized = array();
                    foreach ($value as $each_value) {
                    	$sanitized[]= str_replace(["'", '"'], ['&#39;', '&#34;'], preg_replace('/\x00|<[^>]*>?/', '', $each_value));
                    }
                    $key = str_replace(["'", '"'], ['&#39;', '&#34;'], preg_replace('/\x00|<[^>]*>?/', '', $key));
                    $post_variables[$key] = $sanitized;
                    continue;
                }
             	$key = str_replace(["'", '"'], ['&#39;', '&#34;'], preg_replace('/\x00|<[^>]*>?/', '', $key));
             	$value = str_replace(["'", '"'], ['&#39;', '&#34;'], preg_replace('/\x00|<[^>]*>?/', '', $value));
                $post_variables[$key] = $value;
            }
            $this->post_variables = $vce->post_variables = json_encode($post_variables);
        }
       
        return $post_variables;
    }

    /**
     * Parses path routing to handle simplified routing mechanism
     *
     * @param VCE $vce  
     * @param string $query_string
     * @param string $post_variables
     * @return boolean true if path routing matched
     */
    public function parse_path_routing($vce, $query_string, $post_variables) {

        $route_found = false;

        // path_routing provides a the ability to route a requested path to a compoenent method
        if (!empty($this->path_routing)) {
        
            // decode path_routing
            $path_routing = json_decode($this->path_routing, true);
            
            foreach ($path_routing as $path=>$routing) {
        
                // check for a simple path match without {parameters}
                if ($path == $this->requested_url) {
                
                    // set $routing_match
                    $routing_match = $routing;
                    break;
        
                } elseif (strpos($path,'{') !== false) {	

                    $sub_paths = explode('/', $path);
                
                    $requested_sub_paths = explode('/', $this->requested_url);
                
                    // if count isn't the same, then we do not have a match
                    if (count($sub_paths) == count($requested_sub_paths)) {
                
                        foreach($sub_paths as $key=>$value) {
 
                            if (isset($requested_sub_paths[$key])) {
                
                                if ($requested_sub_paths[$key] == $sub_paths[$key]) {
                                
                                    continue;
                                
                                } elseif (strpos($value,'{') !== false) {
                            
                                    // strip the {} from before and after
                                    $variable = ltrim(rtrim($value ,'}'),'{');
                                
                                    // add this value to routing parameters
                                    $routing['parameters'][$variable] = $requested_sub_paths[$key];
                                
                                } else {
                            
                                    continue 2;
                                }
                
                            }
                
                        }
                    
                        // set routing match and break out
                        $routing_match = $routing;
                        break;
                
                    }
                
                }

            }
                    
            // check requested_url against the list of path_routing
            if (isset($routing_match)) {
        
                $component = $routing_match['component'];
                $method = $routing_match['method'];
        
                $activated_components = json_decode($this->activated_components, true);
        
                if (isset($activated_components[$component])) {
                
                    // check that component exists and require it
                    if (file_exists(BASEPATH . $activated_components[$component])) {
                    
                        require_once(BASEPATH . $activated_components[$component]);
                        
                        $properties['type'] = $component;
                        
                        if (isset($routing_match['parameters'])) {
                            $properties = array_merge($properties, $routing_match['parameters']);
                        }
                        
                        // adding sanitized query string variable
                        if (isset($query_string)) {
                            $properties['query_string'] = $query_string;
                        }
                        
                        // adding sanitized post variables
                        if (isset($post_variables)) {
                            $properties['post_variables'] = $post_variables;
                        }

                        $routing_component = new $component($properties, $vce);
                        
                        // check that method exists
                        if (method_exists($routing_component, $method)) {
                            
                            // we could add $vce here
                            $route_found = true;
                            $routing_component->$method();
                            
                        }
                
                    }

                }
    
            }
        
        }
        
        return $route_found;
    }

    /**
     * adds hooks to site object
     */
    public function get_hooks($vce) {
    
		// declare array for hooks
		$hooks = array();
		
		$preloaded_components = json_decode($this->preloaded_components, true);
		$activated_components = json_decode($this->activated_components, true);
		
		// preload components listed in $site->preload_components that have some sort of effect on layout et cetera
		foreach ($preloaded_components as $type=>$path) {
			// check that this component hasn't been disabled
			if (isset($activated_components[$type])) {
				// check that component exists and require it
				if (file_exists(BASEPATH . $path)) {
					require_once(BASEPATH . $path);
				} else {
					// else continue to the next
					continue;
				}
				$each_component = new $type(array('type' => $type), $vce);
				// call to preload function and cycle through returned array to add hooks to variable
				$preload_component = $each_component->preload_component();
				// error prevention if nothing is returned, and allows method to be used for other things besides hooks
				if (!empty($preload_component)) {
					foreach ($preload_component as $hook_name=>$instructions) {
					
						// check for and add value of the function
						$function_to_call = (isset($instructions['function'])) ? $instructions['function'] : $instructions;
								
						// if the hook calls "at_site_hook_initiation"	
						if ($hook_name == "site_hook_initiation") {
						 	$return_hooks = call_user_func($function_to_call, $hooks, $vce);
						 	// if a value is returned then make hooks that value. You can use this to add more hooks from a component.
						 	if (isset($return_hooks)) {
						 		$hooks = $return_hooks;
						 	}
						}
						
						// check if a priority has been set, otherwise make position a positive increment by setting 0
                    	$position = (isset($instructions['priority'])) ? $instructions['priority'] : 0;
                    	
						// perform the check only if a key already exists
                    	if (isset($hooks[$hook_name][$position])) {
                    		// check until an empty array key has been found
							while (isset($hooks[$hook_name][$position])) {
								// if position is negative then deduct one, otherwise add one
								$position = ($position < 0) ? $position - 1 : $position + 1;
							}
						}
						
                    	// add to hooks at postion
                   		$hooks[$hook_name][$position] = $function_to_call;

					}
				}
			}
		}

		// rekey hooks to correct array order so that are fired off in the intended order
		foreach ($hooks as $key=>$value) {
			// sort keys of value
			ksort($value);
			// save the newly sorted value back into the array position
			$hooks[$key] = $value;
		}

		// return hooks
		return $hooks;  
    
    }
    
    
    /**
     * adds cron_task to site object
     */
    public function add_cron_task($vce) {
    
    	// process cron_tasks
        if (!empty($this->cron_task)) {

            // set the number of cron tasks to process each time
            $cron_task_limit = defined('CRON_TASK_LIMIT') ? CRON_TASK_LIMIT : 1;

            while ($cron_task_limit > 0 && !empty($this->cron_task)) {

                // shift off the first array element
                $each_cron_task = array_shift($this->cron_task);

                // decode json object
                $cron_info = json_decode($each_cron_task['value'], true);

                // get list of activated components
                $activated_components = json_decode($this->activated_components, true);

                // check that the cron_task component is activated
                if (isset($activated_components[$cron_info['component']])) {

                    // path to component
                    $component_path = BASEPATH . $activated_components[$cron_info['component']];

                    if (file_exists($component_path)) {

                        // load this ccomponent class
                        require_once $component_path;

                        $class_to_call = $cron_info['component'];
                        $method_to_call = $cron_info['method'];

                        // call to a static method for the component class
                        $response = $class_to_call::$method_to_call($each_cron_task);

                        // to have cron_tasks be updatable before the site object is complete, a returned value is needed
                        // check that a response was returned
                        if (!empty($response)) {
                            // send the returned array from the class::method called to within the cron_task
                            $vce->manage_cron_task($response);
                        } elseif (defined('VCE_DEBUG') && VCE_DEBUG == true) {
                            // die with an error message
                            die('cron_task error: nothing returned to class.site.php when calling to ' . $cron_info['component'] . '::' . $cron_info['method'] . '(' . print_r($each_cron_task, true) . ')');
                        }

                    } else {

                        // delete this cron_task because component is not installed
                        if (defined('VCE_DEBUG') && VCE_DEBUG == true) {
                            // die with an error message
                            die('cron_task error: component does not exist for ' . $cron_info['component'] . '::' . $cron_info['method'] . '(' . print_r($each_cron_task, true) . ')');
                        } else {
                            // delete this cron_task
                            $vce->manage_cron_task(array('action' => 'delete', 'id' => $each_cron_task['id']));
                        }

                    }

                } else {

                    // delete this cron_task because component is not activated
                    if (defined('VCE_DEBUG') && VCE_DEBUG == true) {
                        // die with an error message
                        die('cron_task error: component has not been activated for ' . $cron_info['component'] . '::' . $cron_info['method'] . '(' . print_r($each_cron_task, true) . ')');
                    } else {
                        // delete this cron_task
                        $vce->manage_cron_task(array('action' => 'delete', 'id' => $each_cron_task['id']));
                    }

                }

                // deduct one from the counter
                $cron_task_limit--;
            }

        }
    
    }
    
    /**
     * adds add_associated_files to site object
     */
    public function add_associated_files($vce) {
    
        // check if ASSETS_URL has been defined in vce-config, otherwise use site_url
        $site_url = defined('ASSETS_URL') ? ASSETS_URL : $this->site_url;

        // site_object_construct

        // add theme path for templates
        $this->theme_path = $site_url . "/vce-content/themes/" . $this->site_theme;

        // load hooks
        // site_object_construct
        if (isset($this->hooks['site_object_construct'])) {
            foreach ($this->hooks['site_object_construct'] as $hook) {
                call_user_func($hook, $this);
            }
        }

        // list of javascript dependencies
        $this->javascript_dependencies = array(
            // list of javascript dependencies
            'scripts' => array(
                'jquery' => $site_url . '/vce-application/js/jquery/jquery.min.js',
                'jquery-ui' => $site_url . '/vce-application/js/jquery/jquery-ui.min.js',
                'select2' => $site_url . '/vce-application/js/jquery/select2.min.js',
                'tablesorter' => $site_url . '/vce-application/js/jquery/jquery.tablesorter.min.js',
                'tabletocard' => $site_url . '/vce-application/js/jquery/tabletocard.js',
                'checkurl' => $site_url . '/vce-application/js/jquery/checkurl.js'
            ),
            // list of css associated with dependencies
            'styles' => array(
                'jquery' => null,
                'jquery-ui' => $site_url . '/vce-application/css/jquery/jquery-ui.min.css',
                'select2' => $site_url . '/vce-application/css/jquery/select2.css',
                'tablesorter' => $site_url . '/vce-application/css/jquery/tablesorter.css',
                'tabletocard' => $site_url . '/vce-application/css/jquery/tabletocard.css',
                'checkurl' => null
            ),
        );

        // load hooks
        // site_javascript_dependencies
        if (isset($this->hooks['site_javascript_dependencies'])) {
            foreach ($this->hooks['site_javascript_dependencies'] as $hook) {
                $this->javascript_dependencies = call_user_func($hook, $this->javascript_dependencies, $vce);
            }
        }

        // optional constant in vce-config that allows for another location to be used for javascript
        if (defined('PATH_TO_BASE_JAVASCRIPT')) {
            // add vce javascript
            $this->add_script($site_url . PATH_TO_BASE_JAVASCRIPT, 'jquery');
        } else {
            // add vce javascript
            $this->add_script($site_url . '/vce-application/js/vce.js', 'jquery');
        }

        // optional constant in vce-config that allows for another location to be used for stylesheet
        if (defined('PATH_TO_BASE_STYLESHEET')) {
            // add vce javascript
            $this->add_style($site_url . PATH_TO_BASE_STYLESHEET, 'vce-style');
        } else {
            // add vce stylesheet
            $this->add_style($site_url . '/vce-application/css/vce.css', 'vce-style');
        }
        
        // load theme functions
        include_once(BASEPATH . 'vce-content/themes/' . $this->site_theme . '/theme.php');
	
	}

    /**
     * Creates an array of template names with file paths.
     * @return array
     */
    public function get_template_names() {

        $templates = array();

        // http://php.net/manual/en/class.directoryiterator.php
        foreach (new DirectoryIterator(BASEPATH . "vce-content/themes/" . $this->site_theme) as $key => $fileInfo) {

            // check for dot files and directories
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }

            // find .php files but exclude theme.php file
            if (preg_match('/^.*\.php$/i', $fileInfo->getFilename()) && $fileInfo->getFilename() != 'theme.php') {

                // full path
                $full_path = BASEPATH . "vce-content/themes/" . $this->site_theme . "/" . $fileInfo->getFilename();

                // search for template name in first 100 chars
                preg_match('/Template Name:(.*)$/mi', file_get_contents($full_path, false, null, 0, 100), $header);

                // if theme name is set
                if (isset($header['1'])) {
                    $templates[trim($header['1'])] = $fileInfo->getFilename();
                } else {
                    // otherwise file names
                    $templates[$fileInfo->getFilename()] = $fileInfo->getFilename();
                }

            }
        }

        return $templates;
    }

    /**
     * Adds javascript property to site object.
     * @param string $path
     * @param string $dependencies
     * @global object $content
     * @return adds script to object $content
     */
    public function add_script($path, $dependencies = null) {

        global $vce;

        // if this class is being called without other classes being loaded first
        if (!isset($vce->content)) {
            return;
        }

        // check if $path starts with http
        if (isset($path) && substr($path, 0, 4) != 'http') {

            // check if ASSETS_URL has been defined in vce-config, otherwise use site_url
            $site_url = defined('ASSETS_URL') ? ASSETS_URL : $this->site_url;

            // get the base path by using getcwd, or if that returns false, use BASEPATH
            $base = (getcwd() !== false && !defined('ASSETS_URL')) ? getcwd() : BASEPATH;

            // create the URI path to the document
            $path = $site_url . '/' . ltrim(str_replace('\\', '/', str_replace($base, '', $path)), '/');

        }
        
        $backtrace = debug_backtrace(2);
		
		$file = $backtrace[0]['file'];
		$class_name = isset($backtrace[1]['class']) ? $backtrace[1]['class'] : 'Theme';

        // list of javascript dependencies
        $scripts = $this->javascript_dependencies['scripts'];

        // list of css associated with dependencies
        $styles = $this->javascript_dependencies['styles'];
        
        // first time
        if (!isset($vce->content->javascript_paths)) {
			$vce->content->javascript_paths = array();
			$vce->content->javascript_summary = array();
		}
		

        if (!empty($dependencies)) {
            $dependent = preg_split("/[\s,]+/", trim($dependencies));
            foreach ($dependent as $each_dependent) {
                if (isset($scripts[$each_dependent]) && !in_array($scripts[$each_dependent], $vce->content->javascript_paths)) {
						if (!in_array($path, $vce->content->javascript_paths)) {
							$vce->content->javascript_paths[] = $scripts[$each_dependent];
							$vce->content->javascript_summary[] = array(
							'class' => 'Site'
							);
						}
                    if (isset($styles[$each_dependent])) {
                        self::add_style($styles[$each_dependent], $each_dependent . '-style');
                    }
                }
            }
        }
        
		if (isset($path) && !in_array($path, $vce->content->javascript_paths)) {
			$vce->content->javascript_paths[] = $path;
			$vce->content->javascript_summary[] = array(
			'class' => $class_name
			);
		}

    }

    /**
     * Adds stylesheet property to contents object.
     * @param string $path
     * @param string $name
     * @param string $media
     * @global object $content
     * @return adds CSS to object $content
     */
    public function add_style($path, $name = null, $media = 'all') {

        global $vce;

        // if this class is being called without other classes being loaded first
        if (!isset($vce->content)) {
            return;
        }

        // check if $path starts with http
        if (substr($path, 0, 4) != 'http') {

            // check if ASSETS_URL has been defined in vce-config, otherwise use site_url
            $site_url = defined('ASSETS_URL') ? ASSETS_URL : $this->site_url;

            // get the base path by using getcwd, or if that returns false, use BASEPATH
            $base = (getcwd() !== false && !defined('ASSETS_URL')) ? getcwd() : BASEPATH;

            // create the URI path to the document
            $path = $site_url . '/' . ltrim(str_replace('\\', '/', str_replace($base, '', $path)), '/');

        }
        
		$backtrace = debug_backtrace(2);
		
		$file = $backtrace[0]['file'];
		$class_name = isset($backtrace[1]['class']) ? $backtrace[1]['class'] : 'Theme';
		
		if (!isset($vce->content->stylesheet_paths)) {
			$vce->content->stylesheet_paths = array();
			$vce->content->stylesheet_summary = array();
		}
		
		if (!in_array($path, $vce->content->stylesheet_paths)) {
			$vce->content->stylesheet_paths[] = $path;
			$vce->content->stylesheet_summary[] = array(
			'class' => $class_name,
			'name' => $name,
			'media' => $media
			);
		}

    }

    /**
     * Generates the media link for file
     * @param array $fileinfo
     * @return string of media URL for link
     */
    public function media_link($fileinfo) {

        // check if ASSETS_URL has been defined in vce-config, otherwise use site_url
        $site_url = defined('ASSETS_URL') ? ASSETS_URL : $this->site_url;

        $path_to_uploads = defined('PATH_TO_UPLOADS') ? PATH_TO_UPLOADS : 'vce-content/uploads';

        // by default media_link points to upload location
        $media_link = $site_url . '/' . $path_to_uploads . '/' . $fileinfo['path'];

        // a hook to modify
        if (isset($this->hooks['site_media_link'])) {
            foreach ($this->hooks['site_media_link'] as $hook) {
                $media_link = call_user_func($hook, $fileinfo, $this);
            }
        }

        return $media_link;

    }
    
    
    /**
     * Generates the link to view the media file in a document viewer
     * @param array $fileinfo
     * @return string of media URL for document viewer
     */
    public function media_viewer_link($fileinfo) {

        // by default media_link is null
        $media_viewer_link = null;

        // a hook to modify
        if (isset($this->hooks['site_media_viewer_link'])) {
            foreach ($this->hooks['site_media_viewer_link'] as $hook) {
                $media_viewer_link = call_user_func($hook, $fileinfo, $this);
            }
        }

        return $media_viewer_link;

    }


    /**
     * push out attributes that have been saved into the $vce object
     */
    public function obtrude_attributes($vce) {

        // site_obtrude_attributes hook
        if (isset($vce->site->hooks['site_obtrude_attributes'])) {
            foreach ($vce->site->hooks['site_obtrude_attributes'] as $hook) {
                call_user_func($hook, $vce);
            }
        }

    }

    /**
     * Adds attributes that will be added to the page object on next page load.
     * If persistent, then attribute will stay until deleted or session has ended.
     * @param string / array $key (if array, then $value becomes boolean for $persistent)
     * @param string $value
     * @param bool $persistent
     * @return adds JSON object of attributes to add
     */
    public function add_attributes($key, $value = null, $persistent = false) {

        global $vce;

        // site_add_attributes hook
        if (isset($vce->site->hooks['site_add_attributes'])) {
            foreach ($vce->site->hooks['site_add_attributes'] as $hook) {
                call_user_func($hook, $key, $value, $persistent);
            }
        }
        
    }

	/**
	 * retrieve attributes by key
	 * @param string $key
	 * @return value for key
	 */
	public function retrieve_attributes($key) {
	
		global $vce;
		
		// site_add_attributes hook
		if (isset($vce->site->hooks['site_retrieve_attributes'])) {
			foreach ($vce->site->hooks['site_retrieve_attributes'] as $hook) {
				$attribute_value = call_user_func($hook, $key);
			}
		}
		
		return $attribute_value;
	
	}

    /**
     * removes attributes from next page load.
     * @param string $key
     * @return JSON object of attributes
     */
    public function remove_attributes($key) {

        global $vce;

        // site_add_attributes hook
        if (isset($vce->site->hooks['site_remove_attributes'])) {
            foreach ($vce->site->hooks['site_remove_attributes'] as $hook) {
                call_user_func($hook, $key);
            }
        }

    }

    /**
     * Checks if a url has already been assigned to another component
     * @param string $url
     * @return string of $clean_url
     */
    public function url_checker($url) {

        global $vce;

        // clean the url using preg_replace. This can also be done in the javascript by using:
        // .replace(/[^\w\d\/]+/gi,'-').toLowerCase();
        $clean_url = trim(strtolower(preg_replace("/[^\w\d\/]+/i", "-", $url)), '-/');

        // get component that has been assigned this url
        $query = "SELECT * FROM " . TABLE_PREFIX . "components WHERE url='" . $clean_url . "'";
        $existing_url = $vce->db->get_data_object($query);

        if (empty($existing_url)) {
            return $clean_url;
        }
        // recursive call back to self to check variation
        return self::url_checker($clean_url . '-2');
    }

    /**
     * Add new roles to the site
     * @param array $attributes
     * @global object $db
     * @global object $site
     *
     * $attributes = array (
     * array (
     * 'role_name' => '*new_role_name*',
     * 'role_hierarchy' => '*new_role_hierarchy*',
     * 'permissions' => '*component_specific_permisions*'
     * ),
     * array (
     * 'role_name' => '*new_role_name*'
     * )
     * );
     */
    public function add_site_roles($attributes) {

        global $vce;

        // find out which class is calling to this method
        $trace = debug_backtrace();
        // our calling class is:
        $calling_class = $trace[1]['class'];
        // cycle through current to create an array to check against for existing role_names
        $site_roles = json_decode($vce->site->roles, true);
        foreach ($site_roles as $each_current) {
            $current_roles[strtolower($each_current['role_name'])] = true;
        }
        // cycle through new
        foreach ($attributes as $each_addition) {
            if (isset($each_addition['role_name'])) {
                // check if role_name already exists
                if (!isset($current_roles[strtolower($each_addition['role_name'])])) {
                    // new array each time though
                    $new_role = array();
                    $new_role['role_name'] = $each_addition['role_name'];
                    // check if permissions and then add
                    $new_role['role_hierarchy'] = isset($each_addition['role_hierarchy']) ? $each_addition['role_hierarchy'] : 0;
                    if (isset($each_addition['permissions'])) {
                        $new_role['permissions'] = array(
                            $calling_class => $each_addition['permissions'],
                        );
                    }
                    // add new role to site_roles
                    $site_roles[] = $new_role;
                }
            }
        }
        // update site_roles in site_meta table
        $update = array('meta_value' => json_encode($site_roles));
        $update_where = array('meta_key' => 'roles');
        $vce->db->update('site_meta', $update, $update_where);

    }
    
    /**
     * Add a path_routing record to site_meta
     * @param string $path
     * @param string $component
     * @param string $method
     * @param string $request_method ($_SERVER['REQUEST_METHOD'])
     *   
     * @return boolean 
     */
    public function add_path_routing($path, $component, $method, $request_method = null) {
    
    	global $vce;
    	
    	// temporary code to make sure there is a path_routing entry in site_meta
    	if (!isset($vce->site->path_routing)) {
			$records[] = array(
			'meta_key' => 'path_routing', 
			'meta_value' => '',
			'minutia' => null
			);
			$vce->db->insert('site_meta', $records);
			$vce->site->path_routing = null;
    	}
    	
    	$path_routing = json_decode($vce->site->path_routing, true);
    	
    	$new_routing = array('component' => $component, 'method' => $method);
    	
    	if (isset($request_method)) {
    		$new_routing['request_method'] = $request_method;
    	}
    	
    	$path_routing[$path] = $new_routing;
    	
        // update site_roles in site_meta table
        $update = array('meta_value' => json_encode($path_routing));
        $update_where = array('meta_key' => 'path_routing');
        $vce->db->update('site_meta', $update, $update_where);
        
        return true;
    
    }

    /**
     * Creates a URN string from input.
     * @param string $input
     * @return string
     * @toDo actually this should be clean_path or something like that.
     */
    public function create_path($input) {
        return preg_replace('/[\W\s]+/mi', '-', str_replace('/', '-', strtolower($input)));
    }

    /**
     * Gets the url path to the component
     * @param string $filepath
     * @global object $site
     * @return string $path
     */
    public static function path_to_url($filepath) {
    
    	global $vce;

        // check if ASSETS_URL has been defined in vce-config, otherwise use site_url
        $site_url = defined('ASSETS_URL') ? ASSETS_URL : $vce->site->site_url;

        // get the base path by using getcwd, or if that returns false, use BASEPATH
        $base = (getcwd() !== false && !defined('ASSETS_URL')) ? getcwd() : BASEPATH;

        // create the URI path to the document
        $path = $site_url . '/' . ltrim(str_replace('\\', '/', str_replace($base, '', $filepath)), '/');
    
    	return $path;
    }
    
    /**
     * Creates vector.
     * @return string encrypted unique vector
     */
    public function create_vector() {
    
        if (OPENSSL_VERSION_NUMBER) {
            $vector = base64_encode(openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc')));
        } else {
             $vector = base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM));
        }
        
        // index:62  Binary:111110  char:+
        // + is being converted to a space in the stored cookie value in Chrome 80+, which results in the vector being only 15 bytes long, so create another without the +
		if (strpos($vector, '+') !== false) {
			return $this->create_vector();
		}
		
		return $vector;
        
    }
    
    /**
     * Get vector length
     * @return string vector length
     *
     * Note: If this changes, also update in vce-media.php
     */
    public function vector_length() {
        if (OPENSSL_VERSION_NUMBER) {
            return openssl_cipher_iv_length('aes-256-cbc');
        } else {
            return mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);

        }
    }
    
    /**
     * the master of all encrypt!
     * @param string $encode_text
     * @global string $vector
     * @return string
     */
    public function encryption($encode_text, $vector) {
        if (isset($vector) && !empty($vector)) {
            if (OPENSSL_VERSION_NUMBER) {
                return base64_encode(openssl_encrypt($encode_text, 'aes-256-cbc', hex2bin(SITE_KEY), OPENSSL_RAW_DATA, base64_decode($vector)));
            } else {
                return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, hex2bin(SITE_KEY), $encode_text, MCRYPT_MODE_CBC, base64_decode($vector)));
            }
        }
    }
    
    /**
     * the master of all decrypt!
     * @param string $decode_text
     * @global string $vector
     * @return string
     */
    public function decryption($decode_text, $vector) {
        if (isset($vector) && !empty($vector)) {
            if (OPENSSL_VERSION_NUMBER) {
                return trim(openssl_decrypt(base64_decode($decode_text), 'aes-256-cbc', hex2bin(SITE_KEY), OPENSSL_RAW_DATA, base64_decode($vector)));
            } else {
                return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, hex2bin(SITE_KEY), base64_decode($decode_text), MCRYPT_MODE_CBC, base64_decode($vector)));
            }
        }
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