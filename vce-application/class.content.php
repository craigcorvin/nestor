<?php
/**
 * Object in which HTML produced by Components is stored and for output to browser.
 * 
 * This object can contain properties such as $premain, $main and $postmain
 * These properties are then used to send content to the browser.
 */
 
class Content {

	/**
	 * Add content to vce object
	 *
	 */
    public function __construct($vce) {
    
    	// add to vce object
		$vce->content = $this;
		
		// this is for the optional storage bucket feature.  See below.
		// This sets to default to maintain current behavior.
		$this->store = array();
		$this->set_storage("default");
    	
    }
	
	/*
	 * Set the internal storage to a bucket named $key.  Any subsequent page build
	 * data will go into this bucket.
	 *
	 * @param string $key
	 * @return string the old key
	 *
	 * Code below is for an optional feature to store page build data into named fragments for use outside
	 * of the default page build process. This is used to create fragments of the page build to be used
	 * in locations outside of the default location.  Here is an example:
	 * 
	 * 		// Set storage to capture content fragment.
	 *		$current_storage_key = $vce->content->set_storage("fragment");
	 *
	 *		// Add content as usual, but it will now go into fragment
	 *		$vce->content->add('main', 'content goes here as usual');
	 *
	 *		// Output the fragment to the tab.
	 *		$content .= $vce->content->output(array('admin', 'premain', 'main', 'postmain'), true);
	 *
	 *		// Reset the storage to the previous key, most likely the default bucket.
	 *		$vce->content->current_storage_key = $current_storage_key; 
	 *
	 */
	public function set_storage($key) {
		$old_key = isset($this->current_storage_key) ? $this->current_storage_key : $key;
		$this->current_storage_key = $key;
		if (!isset($this->store[$key])) {
			$this->store[$key] = new stdClass();
		}
		return $old_key;
	}

	/**
	 * Return the current bucket containing page build data.
	 *
	 * @param string $key
	 * @return stdClass the bucket named $key
	 */
	public function get_storage($key) {
		return $this->store[$key];
	}

	/**
	 * Private internal function to return the current storage bucket.
	 */
	private function storage() {
		return $this->store[$this->current_storage_key];
	}

    // add, a legacy method that places content into a generic container for component
    public function add($selectors = null, $content = null, $conditions = null) {
		return self::crud($selectors, $content, $conditions, 'add');
	}
    
	public function create($selectors = null, $content = null, $conditions = null) {
		return self::crud($selectors, $content, $conditions, 'create');
	}
	
	public function update($selectors = null, $content = null, $conditions = null) {
		return self::crud($selectors, $content, $conditions, 'update');
	}

	public function read($selectors = null, $content = null, $conditions = null) {
		return self::crud($selectors, $content, $conditions, 'read');
	}
	
	public function delete($selectors = null, $content = null, $conditions = null) {
		return self::crud($selectors, $content, $conditions, 'delete');
	}


	/**
	 *
	 * @param string $selectors
	 * @param string $content
	 * @param string $conditions
	 *
	 * $selectors = array(
	 *	'block' => '*string*', // name for content bucket
	 *	'level' => 'pre|medial|post', // position within block, pre | medial (default) | post  -or-  numberic array key
	 * 	'component' => '*component-type*', // contributing component
	 *  'instance' => '*count-of-same-component-type*'
	 * )
	 *
	 * $content = ''
	 *
	 * $conditions = array(
	 * 'place' => 'first|before|after|last',
	 * 'position' => ''
	 * )
	 *
	 */
	public function crud($selectors = null, $content = null, $conditions = null, $action = null) {

		global $vce;
		
		// check if array
		if (!is_array($selectors)) {

			$block = $selectors;
			$level = 1;

		} else {

			// provide error message
			if (!isset($selectors['block'])) {
			
				$vce->add_errors('Error: block missing in keys of array sent from ' . $component_type . ' to ' . $action, $vce);
				return false;
				
			}
		
			$block = $selectors['block'];
			
			// add level
			if (isset($selectors['level'])) {
			
				// check if number or string value for level
				if (!is_numeric($selectors['level'])) {
				
					// list of string name options and corresponding key value
					$level_names = array(
					'pre' => 0,'medial' => 1,'post' => 2,
					'before' => 0,'normal' => 1,'after' => 2
					);
					
					// check if string is key in array
					if (isset($level_names[$selectors['level']])) {
						$level = $level_names[$selectors['level']];
					} else {
						$level = 2;
					}
					
				} else {
					// assign number value
					$level = $selectors['level'];
				}
				
			} else {
				// medial / center value by default 
				$level = 1;
			}
			
		}
		
		// which component is adding content?
		if (empty($selectors['component'])) {
		
			if ($action == 'add') {
				
				// if add, then use only one generic component bucket
				$component = $block;
				
			} else {
				
				// use debug_backtrace to get the type of the contributing component
				$component = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[2]['class'];

			}
			
		} else {
			
			// use passed value
			$component = $selectors['component'];
		
		}
		
		
		// check if empty
		if (!empty($this->storage()->contributing_components[$block])) {
			
			// if component does not match what is at the end of the contributing_components or instance has been passed
			if (end($this->storage()->contributing_components[$block]) != $component || isset($selectors['instance'])) {
			
				// if component is not found in contributing_components array
				if (!in_array($component, $this->storage()->contributing_components[$block])) {
			
					// add component to array
					$this->storage()->contributing_components[$block][] = $component;
			
				} else {
				
					// we need to search for component type in a list of contributing components
					foreach ($this->storage()->contributing_components[$block] as $each_key=>$each_component) {
					
						$instance = isset($selectors['instance']) ? $selectors['instance'] : 1;
					
						// keep a count of the number of times a contribuing component tupe was enountered
						$contributor[$each_component] = isset($contributor[$each_component]) ? $contributor[$each_component] + 1 : 1;
					
						// look for a match
						if ($each_component == $component) {
							
							if ($contributor[$each_component] == $instance) {
						
								// set key to foreach key value
								$key = $each_key;
				
								// end foreach loop
								break;
						
							}
						
						}
					
					}
					
					// if not found create key for this component instance
					if (!isset($key)) {
					
						// add component to array
						$this->storage()->contributing_components[$block][] = $component;
						
					}
				
				}
				
			}
			
			// default value for $key is the end of the array
			if (!isset($key)) {
				$key = count($this->storage()->contributing_components[$block]) - 1;
			}
		
		} else {
		
			// first time, created array
			if (empty($this->storage()->contributing_components)) {
				// prevent error
				$this->storage()->contributing_components = array();
			}
			// create current block key in array
			$this->storage()->contributing_components[$block] = array();
			
			// since this is the first time, add the current component to our list
			$this->storage()->contributing_components[$block][] = $component;
			
			// this is the first time, so set key to 0
			$key = 0;
			
		}
		
		
		// non-create 
		if (!in_array($action, array('create','add'))) {

			$position_counter = 0;
			
			if (!isset($conditions['position']) || !is_numeric($conditions['position'])) {
				$vce->add_errors('Error: ' . $action .' content function call from ' . $component . ' where position condition is not a number', $vce);
				return false;
			}

			// check 
			if (isset($key) && isset($this->storage()->$block[$level][$key])) {

				// sort first
				ksort($this->storage()->$block[$level][$key]);
				
				// cycle through
				foreach ($this->storage()->$block[$level][$key] as $each_key=>$each_value) {

					ksort($each_value);
					$offset_counter = 0;
					
					foreach ($each_value as $each_position) {

						$position_counter++;
						$offset_counter++;

						if ($position_counter == $conditions['position']) {

							if ($action == 'update') {
				
								$this->storage()->$block[$level][$key][$each_key][($offset_counter - 1)] = $content;
			
								return true;
								
							}
							
							if ($action == 'read') {

								return $this->storage()->$block[$level][$key][$each_key][($offset_counter - 1)];
			
							}
							
							if ($action == 'delete') {
					
								array_splice($this->storage()->$block[$level][$key][$each_key],($offset_counter - 1), 1);
								
								return true;
							
							}
							
							
						}

					}
	
				}

			}
			
			// prevent none create action from moving forward
			return false;

		}


		// create object property
		if (empty($this->storage()->$block)) {
			$this->storage()->$block = array();
		}
		
		// do this to prevent errors
		if (empty($this->storage()->$block[$level][$key])) {
			$this->storage()->$block[$level][$key] = array();
		}

		/*
		place = first / before / after / last
		position = position of the content, starting at 1
		*/
		
		if (empty($conditions)) {

			// add default value for position and direction
			// first = 0, prepend = 1, append = 2, last = 3
			$position = 2;
			$direction = 1;

		} else {
		
			// position
			$condition_position = 2;
			
			if (is_array($conditions)) {
			
				// place attibute within conditions
				// first / before / after / last
				if (isset($conditions['place'])) {
				
					// what place?
					if ($conditions['place'] == "first") {
					
						$condition_position = 0;
						$direction = -1; 
						
					}  else if ($conditions['place'] == "last") {
					
						$condition_position = 3;
						
					} else if ($conditions['place'] == "before" || $conditions['place'] == "after") {
					
						if (!isset($conditions['position'])) {
						
							if ($conditions['place'] == "before") {
							
								$condition_position = 1;
								$direction = -1;
								
							}
							
						} else {
						
							$position_counter = 0;

							foreach ($this->storage()->$block[$level][$key] as $each_key=>$each_value) {
							
								$offset_counter = 0;

								foreach ($each_value as $each_position) {
								
									$position_counter++;
									$offset_counter++;

									if ($position_counter == $conditions['position']) {
									
										// before or after
										$switch = ($conditions['place'] == "before") ? 1 : 0;
									
										// only difference is the - 1 or not
										$offset = ($offset_counter - $switch);

										array_splice($each_value, $offset, 0, $content);
	
										$this->storage()->$block[$level][$key][$each_key] = $each_value;

										return array($block,$level,$key,$each_key,$position_counter);

									}

								}

							}
						
						}
							
					}
					
				}
				
			} else {
			
				if ($conditions == "first") {
					$condition_position = 0;
					$direction = -1;
				} else if ($conditions == "last") {
					$condition_position = 3;
				}
				
			}
			
			// default values
			$position = $condition_position;
			$direction = 1;
			
		}
		
		// do this to prevent errors
		if (empty($this->storage()->$block[$level][$key][$position])) {
			$this->storage()->$block[$level][$key][$position] = array();
		}
		
		// get the current subkey
		$subkey = (count($this->storage()->$block[$level][$key][$position]) * (1 * $direction));
		
		// for add / create, here is were we finally add content
		$this->storage()->$block[$level][$key][$position][$subkey] = $content;
		
		return array($block,$level,$key,$position,$subkey);

	}
	
	
	/**
	 * Method to remove an entire block of content
	 *
	 * @param string/array $block
	 */
	public function extirpate($block = null) {
	
		if (is_array($block)) {
			foreach ($block as $each_block) {
				if (isset($this->storage()->$each_block)) {
					unset($this->storage()->$each_block);
				}
			}
			return true;
		} else {
			if (isset($this->storage()->$block)) {
				unset($this->storage()->$block);
				return true;
			}
		}
		return false;
	}
	

	/**
	 * Combines parts and echos the whole of body contents.
	 * Echos rather than returns the output
	 *
	 * @param string $blocks
	 */
	public function output($blocks = null, $return = false) {
		
		$blocks = (!empty($blocks) && is_array($blocks)) ? $blocks : array($blocks);
	
		global $vce;
		$content = null;
		
		foreach ($blocks as $block_key=>$each_block) {
			// check to see if the content bucket has content
			if (!empty($this->storage()->$each_block)) {
				if (is_array($this->storage()->$each_block)) {
					// sort values
					ksort($this->storage()->$each_block);
					foreach ($this->storage()->$each_block as $levels) {
						// sort values
						ksort($levels);
						foreach ($levels as $each_level) {
							// sort values
							ksort($each_level);
							foreach ($each_level as $each_position) {
								// sort values
								ksort($each_position);
								foreach ($each_position as $each_type) {
									$content .= print_r($each_type, true);
								}
							}
						}
					}
				} else {
					// add string value, which would probably not happen in this situation
					$content .= $this->storage()->$each_block;
				}
			} else {
				if (isset($this->$each_block) && !is_array($this->$each_block)) {
					// add string value
					$content .= $this->$each_block;
				}
			}
		}
		
		// return value instead of echo
		if ($return) {
			return $content;
		}
		
		echo $content;
	}
	
	/**
	 * Output javascripts components have added during page build
	 * $content->javascript(array('exclude' => array('all'), 'except' => array('core'));
	 *
	 * @param string $javascript
	 */
	public function javascript($attributes = array()) {
	
		global $vce;
		
		// prevent caching
        $version = '?v=' . (!empty($vce->site->cache) ? $vce->site->cache : time());
        
        $javascript = null;
        
		foreach (array('exclude','except') as $each) {
			if (isset($attributes[$each])) {
				if (!is_array($attributes[$each])) {
					$attributes[$each] = array($attributes[$each]);
				}
			} else {
				$attributes[$each] = array();
			}
		}
	
		
        // create a shortcut for expect that includes core files
        if (isset($attributes['except'][0]) && $attributes['except'][0] == 'core') {
       		$attributes['except'] = array('site-script','input-script','theme-script','accessibility-content-utility-script');
        }
        
        
        foreach ($this->javascript_paths as $key=>$value) {
        
        	$summary = $this->javascript_summary[$key];

			$classes = array(
				strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/',"-$1", $summary['class'])) . '-script'
			);
        
			foreach ($attributes['exclude'] as $each_exclude) {
			
				// if all then continue
				if ($each_exclude == 'all') {
					$skip = true;
					foreach ($attributes['except'] as $each_except) {
						if (in_array($each_except, $classes)) {
							$skip = false;
							break;
						}
					}
					if ($skip) {
						continue 2;
					}
				}
			
				if (in_array($each_exclude, $classes)) {
					continue 2;
				}
        
        	}
        	
        	$javascript .= '<script type="text/javascript" class="' . implode(' ', $classes) . '" src="' . $value . $version . '"></script>' . PHP_EOL;

		}
		
		echo $javascript;
	
	}
	
	/**
	 * Output stylesheets components have added during page build
	 * $content->stylesheet(array('exclude' => array('all'), 'except' => array('core'));
	 *
	 * @param string $stylesheet
	 */
	public function stylesheet($attributes = array()) {

		global $vce;
		
        // prevent caching
        $version = '?v=' . (!empty($vce->site->cache) ? $vce->site->cache : time());
        
        $stylesheet = null;

		foreach (array('exclude','except') as $each) {
			if (isset($attributes[$each])) {
				if (!is_array($attributes[$each])) {
					$attributes[$each] = array($attributes[$each]);
				}
			} else {
				$attributes[$each] = array();
			}
		}

        // create a shortcut for expect that includes core files
        if (isset($attributes['except'][0]) && $attributes['except'][0] == 'core') {
       		$attributes['except'] = array('site-style','theme-style','accessibility-content-utility-style');
        }
        
		foreach ($this->stylesheet_paths as $key=>$value) {
		
			$summary = $this->stylesheet_summary[$key];

			$classes = array(
				$summary['name'],
				strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/',"-$1", $summary['class'])) . '-style'
			);
			
			foreach ($attributes['exclude'] as $each_exclude) {
			
				// if all then continue
				if ($each_exclude == 'all') {
					$skip = true;
					foreach ($attributes['except'] as $each_except) {
						if (in_array($each_except, $classes)) {
							$skip = false;
							break;
						}
					}
					if ($skip) {
						continue 2;
					}
				}
			
				if (in_array($each_exclude, $classes)) {
					continue 2;
				}
			}
			

			$stylesheet .= '<link rel="stylesheet" type="text/css" class="' . implode(' ', $classes) . '" href="' . $value . $version . '" type="text/css" media="' . $summary['media'] . '">' . PHP_EOL;

		}
		
		echo $stylesheet;
	
	}
	

	/**
	 * Creates menu when called.
	 * Reads stored menu from the $site object, then builds a menu into $requested_menu
	 *
	 * @param string $title
	 * @param array $args
	 * @property object $vce
	 * @return echo string $requested_menu
	 */
	public function menu($title, $args = array(), $return = false) {

		global $vce;
	
		// allows for a simple way to add seporator between items
		if (!is_array($args)) {
			$separator = $args;
			$args = array('separator' => $separator);
		}
	
		$site_menus = json_decode($vce->site->site_menus, true);
		if (isset($site_menus[$title])) {
	
			// create ul 
	
			$ul = '<ul';
			if (isset($args['id'])) {
				$ul .=  ' id="' . $args['id'] .'"';
			}
			$ul .= ' class="menu-' .  $title;
			if (isset($args['class'])) {
				$ul .=  ' ' . $args['class'];
			}
			$ul .= '" role="menubar" aria-label="' . ucwords($title) . ' Menu">'  . PHP_EOL;
		
			$requested_menu = null;

			// anonymous function to create the menu structure and save the rewind values used to insert links in the insert_menu_links function
			$create_menu_structure = function($menu_item, $args, &$menu, &$rewind) use (&$create_menu_structure, $vce) {

				// keep track of current level
				$args['level'] = isset($args['level']) ? ($args['level'] + 1) : 1;

				$menu_item['level'] = isset($menu_item['level']) ? $menu_item['level'] : 1;
			
				$menu_item['key'] = isset($menu_item['key']) ? $menu_item['key'] : 1;
			
				// a zero value is added when at the end of the tree
				$menu_item['children'] = isset($menu_item['components']) ? count($menu_item['components']) : 0;

				// add this current level to the rewind array
				$rewind[] = $menu_item;
			
				// start with an li tag
				$menu .= '<li role="none">' . PHP_EOL;
			
				// add code that will be replaced with the link in the insert_menu_links function
				$menu .= '[' . $args['level'] . '|' . $menu_item['key'] . ']' . PHP_EOL;

				if (isset($menu_item['components'])) {
			
					// loop though first level menu items and their children
					foreach($menu_item['components'] as $menu_key=>$menu_sub) {
						// check that user role_id can view this page
						if (!in_array($vce->user->role_id,explode('|',$menu_sub['role_access']))) {
							// remove the non-displayed menu item
							unset($menu_sub['components'][$menu_key]);
						}
					}
				
					if (isset($menu_item['components'])) {
	
						// reindex
						$current_menus = array_values($menu_item['components']);
			
						foreach($current_menus as $menu_key=>$menu_sub) {
							// add for menu-item-first
							if ($menu_key == 0) {
								$menu_sub['position'] = 'first';
							}
							// add for menu-item-last or if only one, menu-item-single
							if ($menu_key == (count($menu_item['components']) - 1)) {
								if (!isset($menu_sub['position'])) {
									$menu_sub['position'] = 'last';
								} else {
									$menu_sub['position'] = 'single';
								}
							}

							// add a ul 
							$menu .= '<ul class="sub-menu" role="menu" aria-label="[aria|' . ($args['level'] + 1) . ']">' . PHP_EOL;
					
							// one level up
							$menu_sub['level'] = ($args['level'] + 1);
							$menu_sub['key'] = ($menu_key + 1);

							// recursive call back to this function
							$create_menu_structure($menu_sub, $args, $menu, $rewind);
					
							//close ul
							$menu .= '</ul>' . PHP_EOL;
						}
				
					}
				}
			
				//close li
				$menu .= '</li>' . PHP_EOL;
		
			};
		
			// anonymous function to insert menu link
			$insert_menu_links = function($menu_item, $args, &$menu, $rewind) use (&$insert_menu_links, $vce) {

				// create classes
				if (isset($menu_item['level'])) {
					$classes[] = 'menu-level-' . $menu_item['level'];
				} else {
					$classes[] = 'menu-level-0';
				}
			
				$classes[] = 'menu-item';
				$classes[] = 'menu-item-id-' . $menu_item['id'];

				if (isset($menu_item['position'])) {
					$classes[] = 'menu-item-' . $menu_item['position'];
				}

				// check for children
				if ($menu_item['children'] > 0) {
					$classes[] = 'menu-item-has-children';
				} else {
					$classes[] = 'menu-item-childless';
				}
		
				if (!empty($args['child']['classes'])) {
					// add parent
					if (in_array('current-menu-item',$args['child']['classes'])) {
						$classes[] = 'current-menu-parent';
						$classes[] = 'current-menu-ancestor';
					}
					// add ancestor
					if (in_array('current-menu-ancestor',$args['child']['classes'])) {
						$classes[] = 'current-menu-ancestor';
					}
				}
			
				// add any classes configured in ManageMenus
				if (isset($menu_item['class']))  {
					$classes[] = $menu_item['class'];
				}
			
				// the menu item is contantained in the requested url
				if (preg_match('#\/' . $menu_item['url'] . '\/#', '/' . $vce->requested_url)) {
					$classes[] = 'requested-url-ancestor';
				}
			
				// this menu item is the current page
				if ($menu_item['url'] == $vce->requested_url) {
					$classes[] = 'current-menu-item';
				}
			
				// a way to not have the aria tags added for dropdown menus
				$aria = null;
				if (!isset($args['dropdown']) || $args['dropdown'] != false) {
					if ($menu_item['children'] != 0) {
						$aria = ' aria-expanded="false" aria-haspopup="true"';
					}
				}
			
			
			
				// the filling for either a link or div
				$scf = ' class="' . implode(' ' ,$classes) . '" role="menuitem"' .  $aria . '>';
			
				$menu_item_title = $menu_item['title'];
			
				// hook to allow menu title to be updated
				if (isset($vce->site->hooks['content_insert_menu_links'])) {
					foreach($vce->site->hooks['content_insert_menu_links'] as $hook) {
						$menu_item_title = call_user_func($hook, $menu_item, $vce);
					}
				}
			
				$scf .= $menu_item_title;
							
				if (!empty($menu_item['url'])) {
					// check if target is set to open this link in a new window
					if (isset($menu_item['target']))  {
						$scf = ' target="_blank"' . $scf ;
					}
					// check for external urls links
					if (!preg_match("/^(http|mailto)/i", $menu_item['url'])) {
						if ($menu_item['url'] == "/") {
							$menu_item['url'] = $vce->site->site_url;
						} else {
							$menu_item['url'] = $vce->site->site_url . '/' . $menu_item['url'];
						}
					}
					$link = '<a href="' . $menu_item['url'] . '"' . $scf . '</a>';
				} else {
					// if no url create as a div
					$link = '<div'  . $scf . '</div>';
				}

				$search = '[' . $menu_item['level'] . '|' . $menu_item['key'] . ']';

				$menu = str_replace($search, $link, $menu);
			
				$search = '[aria|' . $menu_item['level'] . ']';
			
				$aria_label = $menu_item['title'];

				$menu = str_replace($search, $aria_label, $menu);

				// add this link to args as child so that parent component can know
				$args['child'] = array(		
					'menu_item' => $menu_item,
					'classes' => $classes
				);

				// get last array of rewind and remove it
				$last_rewind = array_pop($rewind);
		
				// unset sub components so that it will end up down here
				unset($last_rewind['components']);
		
				if (!empty($last_rewind)) {
					// recursive callback
					$insert_menu_links($last_rewind, $args, $menu, $rewind);
				}
		
			};
		
		
			// loop though first level menu items and their children
			foreach($site_menus[$title] as $menu_key=>$menu_item) {
				// check that user role_id can view this page
				if (!in_array($vce->user->role_id,explode('|',$menu_item['role_access']))) {
					// remove the non-displayed menu item
					unset($site_menus[$title][$menu_key]);
				}
			}
		
			if (isset($site_menus[$title])) {
		
				// reindex
				$current_menu = array_values($site_menus[$title]);
				$menu_items = array();
		
				// loop though first level menu items and their children
				foreach($current_menu as $menu_key=>$menu_item) {
		
					// adds menu-item-first to first menu item
					if ($menu_key == 0) {
						$menu_item['position'] = 'first';
					}
					// adds menu-item-last to last menu item
					if ($menu_key == (count($site_menus[$title]) - 1)) {
						if (!isset($menu_item['position'])) {
							$menu_item['position'] = 'last';
						} else {
							$menu_item['position'] = 'single';
						}
					}
	
					$sub_menu = null;
					$rewind = array();

					// call to anonymous function
					$create_menu_structure($menu_item, $args, $sub_menu, $rewind);

					// get the last rewind value to pass to anonymous function
					$last_rewind = array_pop($rewind);
			
					// call to anonymous function
					$insert_menu_links($last_rewind, $args, $sub_menu, $rewind);
				
					$menu_items[] = $sub_menu;
				}
		
			}
		
			$separator = isset($args['separator']) ? $args['separator'] : '';
		
			$requested_menu .= implode($separator, $menu_items);
		
			if (!empty($requested_menu)) {
		
				// build menu to return
				$return_menu = $ul . $requested_menu . '</ul>' . PHP_EOL;
		
				// return flag set, so return string
				if ($return) {
					return $return_menu;
				}
		
				// echo menu into template
				echo $return_menu;
		
			}
		
			// return an empty value to close.
			return null;

		}
	}

	/**
	 * Renders an array of components into a temporary bucket then returns the result.
	 */
	public function render_components($components, $sub_recipe = null, $component_id = null)  {

		global $vce;
		
		// Set storage to capture components page fragment.
		$current_storage_key = $this->set_storage(time());
		$vce->page->display_components($components, $sub_recipe, $component_id);
		$c = $this->output(array('admin', 'premain', 'main', 'postmain'), true);
		$this->current_storage_key = $current_storage_key;

		return $c;
	}
	
	
	public function accordion($accordion_title, $accordion_content, $accordion_expanded = false, $accordion_disabled = false, $accordion_class = null, $tooltip = null) {

		/*
		<div class="accordion-container">
		<!--clickbar header has role of heading-->
		<div role="heading" aria-level="2">
		<!-- Clickbar itself has role of button so reader knows it's actionable.  Also, aria-expanded is toggled between "true" and "false"-->
		<!-- aria-controls contains id of element that appears when expanded-->
		<!--change type to button-->
		<button class="accordion-title accordion-closed" role="button" aria-expanded="false" aria-controls="accordion-content-$aria_integer" id="accordion-title-$aria_integer">
		<span>accordion title</span></button>
		</div>
		<!-- aria-labelledby contains id of element that controls expansion/contraction-->
		<div class="accordion-content" id="accordion-content-$aria_integer" role="region" aria-labelledby="accordion-title-$aria_integer">
		accordion content
		</div> <!--click bar content-->
		</div> <!--click bar container-->
		*/

		// create a unique id for id and aria tags
		$aria_integer = mt_rand(0, 1000);

		$container_classes = $accordion_expanded === true ? 'accordion-container accordion-open' : 'accordion-container accordion-closed';

		if (isset($accordion_class)) {
			$container_classes .= ' ' . $accordion_class;
		}

		$title_classes = $accordion_disabled === true ? 'accordion-title disabled' : 'accordion-title active';

		$aria_expanded = $accordion_expanded === true ? 'true' : 'false';

		$content = <<<EOF
<maaccordion accordion-title="$accordion_title">
<div class="$container_classes">
<div class="accordion-heading" role="heading" aria-level="2">
<button class="$title_classes" role="button" aria-expanded="$aria_expanded" aria-controls="accordion-content-$aria_integer" id="accordion-title-$aria_integer">
<span>$accordion_title</span>
</button>
</div>
<div class="accordion-content" id="accordion-content-$aria_integer" role="region" aria-labelledby="accordion-title-$aria_integer">
$accordion_content
</div>
</div>
</maaccordion>
EOF;
		return $content;
	}
	
	
	/**
	 * Samples for different input types
	 * - - - - - - - - - - - - - - - - - - - -
	 * // text input
	 *	$input = array(
	 *		'type' => 'text',
	 *		'name' => 'text_input_name',
	 *		'required' => 'true',
	 *		'placeholder' => 'enter something',
	 *		'data' => array(
	 *			// additional values
	 *			'autocapitalize' => 'none',
	 *			'tag' => 'required',
	 *		)
	 *	);
	 *
	 *  // select menu
	 *	$input = array(
	 *		'type' => 'select',
	 *		'name' => 'select_menu_name',
	 *		// html5 required tag
	 *		'required' => 'true',
	 *		'data' => array(
	 *			// vce.js required
	 *			'tag' => 'required'
	 *		),
	 *		'options' => array(
	 *			array(
	 *				// empty select menu item
	 *				'name' => '',
	 *				'value' => ''
	 *			),
	 *			array(
	 *				'name' => 'first_name',
	 *				'value' => 'first_value'
	 *				'disabled' => 'disabled'
	 *			),
	 *			array(
	 *				'name' => 'second_name',
	 *				'value' => 'second_value',
	 *				// select this item
	 *				'selected' => true,
	 *			)
	 *		)
	 *	);
	 *
	 *	// textarea
	 *	$input = array(
	 *		'type' => 'textarea',
	 *		'name' => 'notes',
	 *		//'value' => 'current value for this textarea',
	 *		//'required' => 'true',
	 *		'data' => array(
	 *			'rows' => '20',
	 *			'tag' => 'required',
	 *			'placeholder' => 'enter something'
	 *		)
	 *	);
	 *
	 *	// radio button
	 * 	$input = array(
	 *		'type' => 'radio',
	 *		'name' => 'radio_menu_name',
	 *		// if you want html5 required
	 *		//'required' => 'true',
	 *		'data' => array(
	 *			'tag' => 'required'
	 *		),
	 *		'options' => array(
	 *			array(
	 *				'value' => 'first_value',
	 *				'label' => ' label for radio button '
	 *			),
	 *			array(
	 *				'value' => 'second_value',
	 *				'label' => ' second label radio button ',
	 *				'selected' => true
	 *			)
	 *		)
	 *	);
	 *
	 * 
	 *	// checkbox
	 *	$input = array(
	 *		'type' => 'checkbox',
	 *		'name' => 'checkbox_name',
	 *		// if you want html5 required
	 *		//'required' => 'true',
	 *		'data' => array(
	 *			'tag' => 'required',
	 *		),
	 *		'options' => array(
	 *			array(
	 *				'value' => 'first_value',
	 *				'label' => ' label for checkbox',
	 *				'selected' => true
	 *			),
	 *			array(
	 *				'value' => 'second_value',
	 *				'label' => ' second label checkbox',
	 *			)
	 *		),
	 *		'flags' => array(
	 *			'default' => 'zero_value' // adds hidden input before the checkbox to provide deault value if not checked
	 *			'label_tag_wrap' => true // if you want a single checkbox 
	 *		),
	 *		'label' => 'add a label' // if you want a single checkbox 
	 *	);
	 *
	 */
	public function form_input($input) {
		
		global $vce;

		// default value to prevent errors
		$type = 'text';
		$for = null;

		// validate type
		if (isset($input['type']) && in_array($input['type'], array('text', 'date', 'number', 'hidden', 'password', 'search', 'checkbox', 'radio', 'select', 'textarea'))) {
			$type = $input['type'];
		}

		$name = isset($input['name']) ? $input['name'] : 'name';
		$required = isset($input['required']) ? $input['required'] : null;
		$autocomplete = isset($input['autocomplete']) ? $input['autocomplete'] : null;

		// normalize options array
		if (!isset($input['options']) || empty($input['options'])) {
			$input['options'] = array();
			$input['options']['value'] = isset($input['value']) ? $input['value'] : null;
			$input['options']['id'] = isset($input['id']) ? $input['id'] : null;
			$input['options']['class'] = isset($input['class']) ? $input['class'] : null;
			$input['options']['selected'] = isset($input['selected']) ? $input['selected'] : null;
			$input['options']['label'] = isset($input['label']) ? $input['label'] : null;
			$input['options']['placeholder'] = isset($input['placeholder']) ? $input['placeholder'] : null;
			if (isset($input['data']) && is_array($input['data'])) {
				$input['options']['data'] = $input['data'];
			}
			$input['options']['tag_attributes'] = isset($input['tag_attributes']) ? $input['tag_attributes'] : null;

		}

		// normalize as array of array
		if (!is_array(array_values($input['options'])[0])) {
			$options[] = $input['options'];
			// set back again.
			$input['options'] = $options;
		}

		// check if options have been provided in a simple array
		if (isset($input['options'][0][0])) {
			$options = array();
			foreach ($input['options'][0] as $each_key => $each_value) {
				$options[$each_key]['name'] = $each_value;
				$options[$each_key]['value'] = $each_value;
			}
			$input['options'] = $options;
		}

		$content = null;

		foreach ($input['options'] as $key => $value) {

			// prepend input tag with something
			if (!empty($input['flags']['prepend'])) {
				$content .= $input['flags']['prepend'];
			}

			if ($type == 'select') {
				// select menu

				if (empty($content)) {

					$content .= '<mainput type="' . $type . '" value="' . $value['value'] . '"';
					
					// add label information if we have it
					if (isset($input['input_label'])) {
						$content .= ' label="' . $input['input_label']['title'] . '" error="' . $input['input_label']['error'] . '"';
					}
					
					$content .= '>';

					$content .= '<select name="' . $name . '"';

					if (isset($input['id'])) {
						$input_id = $input['id'];
						$content .= ' id="' . $input_id . '"';
					} else {
						$input_id = $name;
						$content .= ' id="' . $input_id . '"';
					}

					$for = $input_id;

					// class
					if (isset($input['class'])) {
						$content .= ' class="' . $input['class'] . '"';
					}

					// data
					if (isset($input['data'])) {
						foreach ($input['data'] as $data_key => $data_value) {
							$content .= ' ' . $data_key . '="' . $data_value . '"';
						}
					}

					// required
					if (isset($required)) {
						$content .= ' required';
					}

					$content .= '>' . PHP_EOL;
				}

				// select menu options
				$content .= '<option';

				if (isset($value['value'])) {
					$content .= ' value="' . $value['value'] . '"';
				}

				// id
				// disable id="" from being added for now
				if (isset($value['id'])) {
					$input_id = $value['id'];
					// $content .= ' id="' . $input_id . '"';
					if (empty($for)) {
						$for = $input_id;
					}
				} else {
					if (isset($value['label'])) {
						$input_id = $name . '-' . $key;
						// $content .= ' id="' . $input_id . '"';
					} else {
						// $input_id = $name . '_' . $value['value'] . '_selections';
						$input_id = $name;
						// $content .= ' id="' . $input_id . '"';
						$for = $input_id;
					}
				}

				// class
				if (isset($value['class'])) {
					$content .= ' class="' . $value['class'] . '"';
				}

				// data
				if (isset($value['data'])) {
					foreach ($value['data'] as $data_key => $data_value) {
						$content .= ' ' . $data_key . '="' . $data_value . '"';
					}
				}

				// selected
				if (!empty($value['selected']) || (isset($input['value']) && $value['value'] == $input['value'])) {
					$content .= ' selected';
				} elseif (isset($input['selected'])) {
					if (is_array($input['selected'])) {
						foreach ($input['selected'] as $each_selected) {
							// mark as checked
							if ($each_selected == $value['value']) {
								$content .= ' selected"';
							}
						}
					} else {
						if ($value['value'] == $input['selected']) {
							$content .= ' selected';
						}
					}
				}
				
				// disabled
				//  || (isset($input['value']) && $value['value'] == $input['value'])
				if (!empty($value['disabled'])) {
					$content .= ' disabled';
				} elseif (isset($input['disabled'])) {
					if (is_array($input['disabled'])) {
						foreach ($input['disabled'] as $each_selected) {
							// mark as checked
							if ($each_selected == $value['value']) {
								$content .= ' disabled"';
							}
						}
					} else {
						if ($value['value'] == $input['disabled']) {
							$content .= ' disabled';
						}
					}
				}	

				$content .= '>';

				if (isset($value['name'])) {
					$content .= $value['name'];
				}

				$content .= '</option>' . PHP_EOL;

				if (is_numeric($key) && count($input['options']) == ($key + 1)) {
					$content .= '</select></mainput>';
				}
			} elseif ($type == 'textarea') {
				// textarea

				$content .= '<mainput type="' . $type . '" value="' . $value['value'] . '"';
						
				// add label information if we have it
				if (isset($input['input_label'])) {
					$content .= ' label="' . $input['input_label']['title'] . '" error="' . $input['input_label']['error'] . '"';
				}
						
				$content .= '>';
				
				$content .= '<textarea name="' . $name . '"';

				// id
				if (isset($value['id'])) {
					$content .= ' id="' . $value['id'] . '"';
					$for = $value['id'];
				} else {
					$unique = mt_rand(1000,9999);
					$content .= ' id="' . $name . '-selections-'  . $unique . '"';
					$for = $name . '-selections-' . $unique;
				}

				// class
				if (isset($value['class'])) {
					$content .= ' class="' . $value['class'] . '"';
				}

				// data
				if (isset($value['data'])) {
					foreach ($value['data'] as $data_key => $data_value) {
						$content .= ' ' . $data_key . '="' . $data_value . '"';
					}
				}

				// required
				if (isset($required)) {
					$content .= ' required';
				}

				$content .= '>';

				// value
				if (isset($value['value'])) {
					$content .= $value['value'];
				}

				$content .= '</textarea></mainput>';
			} else {
				// checkbox and radio buttons
			
				if (empty($content) && in_array($type, array('checkbox', 'radio'))) {
				
					// if a default value has been provided, then insert a hidden input before the checkbox with the default value
					if ($input['type'] == 'checkbox' && isset($input['flags']['default'])) {
						// default
						$input_name = $input['name'];
						// append with _0 if there are more than one option
						if (count($input['options']) > 1) {
							$input_name = $input['name'] . '_0';
						}

						$content .= '<mainput type="' . $type . '" value="' . $value['value'] . '"';
						
						// add label information if we have it
						if (isset($input['input_label'])) {
							$content .= ' label="' . $input['input_label']['title'] . '" error="' . $input['input_label']['error'] . '"';
						}
						
						$content .= '>';
						
						$content .= '<input type="hidden" name="' . $input_name . '" value="' . $input['flags']['default'] . '"></mainput>';
					}
					
					$content .= '<div class="input-padding"';
					//if (count($input['options']) > 1) {
					if (isset($input['id'])) {
						$content .= ' id="' . $input['id'] . '"';
						$for = $input['id'];
					} else {
						$content .= ' id="' . $name . '_selections"';
						$for = $name . '_selections';
					}

					$content .= '>' . PHP_EOL;
				}

				// prevent collision
				if (!empty($input['flags']['label_tag_wrap'])) {
					$content .= '<label>';
				}

				$content .= '<mainput type="' . $type . '" value="' . $value['value'] . '"';
						
				// add label information if we have it
				if (isset($input['input_label'])) {
					$content .= ' label="' . $input['input_label']['title'] . '" error="' . $input['input_label']['error'] . '"';
				}
						
				$content .= '>';
				
				$content .= '<input type="' . $type . '" options-group="' . $name . '"';
				
				if (isset($value['name'])) {
					$content .= ' name="' . $value['name'] . '"';
				} elseif (isset($input['name'])) {
					if (count($input['options']) == 1 || $type == 'radio' || !empty($input['flags']['prevent_keying'])) {
						$content .= ' name="' . $input['name'] . '"';
					} else {
						$content .= ' name="' . $input['name'] . '_' . ($key + 1) . '"';
					}
				} else {
					if (count($input['options']) == 1 || $type == 'radio') {
						$content .= ' name="' . $name . '"';
					} else {
						$content .= ' name="' . $name . '_' . ($key + 1) . '"';
					}
				}

				// value
				if (isset($value['value'])) {
					$content .= ' value="' . $value['value'] . '"';
				}

				// id
				if (isset($value['id'])) {
					$input_id = $value['id'];
					$content .= ' id="' . $input_id . '"';
					if (empty($for)) {
						$for = $input_id;
					}
				} else {
					if (isset($value['label'])) {
						// $input_id = $name . '_' . $key;
						$input_id = uniqid($name . '_', FALSE);
						$content .= ' id="' . $input_id . '"';
					} else {
						// $input_id = $name . '_selections_';
						$input_id = uniqid($name . '_selections_', FALSE);
						$content .= ' id="' . $input_id . '"';
						$for = $input_id;
					}
				}

				// class
				if (isset($input['class'])) {
					$content .= ' class="' . $input['class'] . '"';
				}

				// data
				if (isset($input['data'])) {
					foreach ($input['data'] as $data_key => $data_value) {
						$content .= ' ' . $data_key . '="' . $data_value . '"';
					}
				}

				// required
				if (isset($required)) {
					$content .= ' required';
				}

				// autocomplete
				if (isset($autocomplete)) {
					if ($autocomplete == 'on') {
						$content .= ' autocomplete="on"';
					} else {
						$content .= ' autocomplete="off"';
					}
				} else {
					if (in_array($type, array('text', 'password', 'search'))) {
						$content .= ' autocomplete="off"';
					}
				}

				// value
				if (isset($value['placeholder'])) {
					$content .= ' placeholder="' . $value['placeholder'] . '"';
				}

				// selected value included in options
				if (isset($value['selected']) && $value['selected'] === true) {
					$content .= ' checked="checked"';
				} elseif (isset($input['selected'])) {
					// selected value included in input

					if (is_array($input['selected'])) {

						foreach ($input['selected'] as $each_selected) {
							// mark as checked
							if ($each_selected == $value['value']) {
								$content .= ' checked="checked"';
							}
						}
					} else {
						// mark as checked						
						if ($input['selected'] == $value['value']) {
							$content .= ' checked="checked"';
						}
					}
				}

				// selected value included in options
				if (isset($value['disabled'])) {

					$content .= ' disabled="disabled"';
				} elseif (isset($input['disabled'])) {
					// selected value included in input

					if (is_array($input['disabled'])) {

						foreach ($input['disabled'] as $each_disabled) {
							// mark as checked
							if ($each_disabled == $value['value']) {
								$content .= ' disabled="disabled"';
							}
						}
					} else {
						// mark as checked						
						if ($input['disabled'] == $value['value']) {
							$content .= ' disabled="disabled"';
						}
					}
				}

				$content .= '></mainput>';

				// allows for clickable label to checkbox
				if (isset($value['label'])) {

					// prevent collision
					if (empty($input['flags']['label_tag_wrap'])) {
						$content .= '<label class="omit" for="' . $input_id . '">';
					}

					$content .= $value['label'] . '</label>';
				}

				// if no label was passeed, then use name
				if (isset($value['name']) && !isset($value['label'])) {
					$content .= $value['name'];
				}

				$separator = isset($input['flags']['label_separator']) ? $input['flags']['label_separator'] : ' / ';

				if (count($input['options']) > 1 && count($input['options']) != ($key + 1)) {

					if (isset($input['flags']['options_listed']) && $input['flags']['options_listed'] == true) {
						$content .= '<br>';
					} else {
						$content .= '<span>' . $separator . '</span>';
					}
					
				}

				if (count($input['options']) == ($key + 1) && in_array($type, array('checkbox', 'radio'))) {
					$content .= PHP_EOL . '</div>';
				}
			}
		}

		// prepend input tag with something
		if (!empty($input['flags']['append'])) {
			$content .= $input['flags']['append'];
		}

		return array('input' => $content, 'for' => $for);
	}
	
		
		
	/**
	 * create a form input element and return it as insertable content without needing to work the array
	 */
	public function input_element($input) {
		
		global $vce;
		
		$element = $vce->content->form_input($input);
		return $element['input'];
	}

	/**
	 * create a from input element with associated label tags
	 */
	public function create_input($input, $title = null, $error = null, $class = null, $tooltip = null) {
	
		global $vce;

		// check if input is required
		if (isset($input['data']['tag']) && $input['data']['tag'] == 'required') {
			$error = !empty($error) ? $error : $title . ' is required';
		}

		// check if array and send $input to form_input method
		if (is_array($input)) {
			// adding in label information for <mainput> tag
			$input['input_label']['title'] = htmlentities(trim($title), ENT_QUOTES);
			$input['input_label']['error'] = htmlentities(trim($error), ENT_QUOTES);
			// send to input
			$content = $vce->content->form_input($input);
			$input_content = $content['input'];
			$for = $content['for'];
		} else {
			// otherwise use sting value
			// input-padding
			$input_content = '<div class="static-content">' . $input . '</div>';
			$for = null;
		}

		/*
		<!--change label to div with label-style class-->
		<div class="label-style"> 
		<!--move label text to top-->
		<div class="label-text">
		<!-- change div with class label-message to an explicit label-->
		<label class="label-message" for="title">Title</label>
		<!--adding role="alert" lets AT watch this element for changes and announce them-->
		<div class="label-error" role="alert">Enter A Title</div>
		</div>
		<input type="text" name="title" id="title" class="resource-name" tag="required" autocomplete="off">
		</div>
		*/

		$input_label_class = 'input-label-style';
		if (!empty($class)) {
			$input_label_class .= ' ' . $class;
		}

		$tooltip = !empty($tooltip) ? $vce->content->tool_tip($tooltip, $for) : null;

		$content = <<<EOF
<div class="$input_label_class"> 
<div class="input-label-text">
EOF;

		if (isset($for)) {

			$content .= <<<EOF
$tooltip<label class="input-label-message" for="$for">$title</label>
EOF;
		} else {

			$content .= <<<EOF
$tooltip<label class="input-label-message">$title</label>
EOF;
		}

		if (isset($error)) {
			$content .= <<<EOF
<div class="input-label-error" role="alert">$error</div>
EOF;
		}

		$content .= <<<EOF
</div>
$input_content
</div>
EOF;


		return $content;
	}


	public function tool_tip($tooltip_content, $tooltip_id = null) {

		$css_id = !empty($tooltip_id) ? $tooltip_id : "tooltip_" . mt_rand(0, 1000);

		$content = <<<EOF
<button class="tooltip" aria-describedby="$css_id" aria-label="tooltip"><div id="$css_id" class="tooltip-text" role="tooltip">$tooltip_content</div></button>
EOF;

		return $content;
	}
	
	
	
    /**
     * Allows components to add functions to the $content object dynamically.
     *
	 * @param $name
	 * @param $args
	 * @return string OR echo string
	 */
	public function __call($name, $args) {
	
		if (isset($this->$name)) {
			if (is_string($this->$name)) {
				echo $this->$name;
				return;
			} else {
                if ($args) {
                    return call_user_func_array($this->$name, $args);
                } else {
                    return call_user_func($this->$name);
                }
			}
		}
	
		global $vce;
	
        if (isset($vce->site->hooks['content_call_add_functions'])) {
            foreach ($vce->site->hooks['content_call_add_functions'] as $hook) {
                call_user_func($hook, $vce);
            }
        }
        
        if (isset($this->$name)) {
			return self::__call($name, $args);
        } else {
			if (!VCE_DEBUG) {
				return false;
			} else {
				// print name of none existant component
				echo '<div class="vce-error-message">Call to non-existant method/property ' . '$' . strtolower(get_class()) . '->' . $name . '()'  . ' in ' . debug_backtrace()[0]['file'] . ' on line ' . debug_backtrace()[0]['line'] .'</div>';
			}
		}
        
	}


    /**
     * Magic function to convert static function calls to non-static and use __call functionality above
     *
     * @param [type] $name
     * @param [type] $args
     * @return void
     */
    public static function __callStatic($name, $args) {

        global $vce;
        return $vce->__call($name, $args);
    }
	
	
	/**
	 * Handles errors 
	 * Reading data from inaccessible properties will return false instead of a Notice: Undefined property error.
	 *
	 * @param mixed $var  Can accept a parameter, but always returns false
	 */
	public function __get($var) {
		return false;
	}
	
}