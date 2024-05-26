<?php
/**
 * Gets components information and builds page content
 */
class Page {

	// display get calls
	static $display_get_calls = false;

	// display build calls
	static $display_build_calls = false;
	
	/**
	 * Builds component tree from recipe
	 * Takes the URL from the $site object, finds all components in the recipe related to that URL
	 * First working backwards to the base of the recipe, and then forward to get sub components
	 * and calls them to add their data to the $content object
	 */
	public function __construct($vce, $build = true) {
	
		// add to global vce object
		$vce->page = $this;
		
		if ($build) {

			$this->construct_content($vce);

			// check that template and theme exist	
			// the theme file is loaded in the class.site.php at 275 and this should be updated to reflect that	
			if (isset($this->template)) {
				// normal theme template output
				if (file_exists(BASEPATH .'vce-content/themes/' . $vce->site->site_theme . '/' . $this->template)) {
					$vce->template_file_path = BASEPATH .'vce-content/themes/' . $vce->site->site_theme . '/' . $this->template;
				} elseif (file_exists(BASEPATH .'vce-content/themes/' . $vce->site->site_theme . '/index.php')) {
					// no valid value has been set for $this->template so default to index.php
					$vce->add_errors($this->template . ' template cannot be found in ' . $vce->site->site_theme . ' theme', $vce);
					$vce->template_file_path = BASEPATH .'vce-content/themes/' . $vce->site->site_theme . '/index.php';
				}
			} else {
				// default to index.php 
				if (file_exists(BASEPATH .'vce-content/themes/' . $vce->site->site_theme . '/index.php')) {
					$vce->template_file_path = BASEPATH .'vce-content/themes/' . $vce->site->site_theme . '/index.php';
				}
			}
		
			// if theme_page has not been set, then we have theme issues
			if (!isset($vce->template_file_path)) {
				// check if theme exists
				if (file_exists(BASEPATH .'vce-content/themes/' . $vce->site->site_theme)) {
					$vce->add_errors($vce->site->site_theme . ' theme does not contain index.php', $vce);
				} else {
					$vce->add_errors($vce->site->site_theme . ' theme does not exist', $vce);
				}
				// as a last resort use defalt vce theme in vce-application
				$vce->template_file_path = BASEPATH . 'vce-application/themes/vce/index.php';
			}
		
		}
		
	}
	
	/**
	 * Constructs the content part of the page.
	 * This is also called independantly from Endpoint
	 *
	 * @param [type] $vce
	 * @return void
	 */
	public function construct_content($vce) {

		// adding a few helpful duplicates
		$requested_url = $vce->site->requested_url;
		$this->post_variables = $vce->site->post_variables;
		$this->query_string = $vce->site->query_string;
		
		// push out attributes into vce object that have been saved into session
		// this must be done here because the user class is loaded after the site class
		$vce->site->obtrude_attributes($vce);

		// hook to work with the requested_url before page object get or build happens
		if (isset($vce->site->hooks['page_requested_url'])) {
			foreach($vce->site->hooks['page_requested_url'] as $hook) {
				call_user_func($hook, $requested_url, $vce);
			}
		}
	
		// get component_id that is associated with the requested url
		$requested_component = $this->get_requested_url($vce, $requested_url);

		// load hooks
		// page_construct_object
		// to redirect to another component_id, set both component_id and parent_id
		if (isset($vce->site->hooks['page_construct_object'])) {
			foreach($vce->site->hooks['page_construct_object'] as $hook) {
				call_user_func($hook, $requested_component, $vce);
			}
		}
	
		// add basics to object
		$vce->requested_id = $this->requested_id = $requested_component->component_id;
		$vce->requested_url = $this->requested_url = $requested_component->url;		
	
		// start building page object components
		// $page_id, $requested_id, array('requested_location' => $requested_component)
		// requested_location is used to so that the recursive get_components method knows this is the first time
		$this->components = $this->get_components($vce, $requested_component->component_id, $requested_component->component_id, array('requested_component' => $requested_component));
	
		// read recipe
		$recipe = (isset($this->recipe)) ? $this->recipe : array();
		
		// build page content from components
		$this->build_content($vce, $this->components, $recipe, $requested_component->component_id);

	}

	/**
	 * Gets component associated with requested_url
	 * @param string $requested_url
	 * @return object $requested_component 
	 */
	 public function get_requested_url($vce, $requested_url, $default_to_homepage = true) {
	 
	 	$requested_component = null;
	 
	 	if (!empty($requested_url)) {
	 
	 		// check to see if a component_id has been requested, which is done by using the following syntax
			// tilde and component_id
			// ~123
			if (preg_match('/~(\d+)/',$requested_url,$requested_id)) {
				
				// fetch requested component by component_id
				$query = "SELECT * FROM  " . TABLE_PREFIX . "components AS a INNER JOIN " . TABLE_PREFIX . "components_meta AS b ON a.component_id = b.component_id WHERE a.component_id='" . $requested_id[1] . "'";

			// otherwise fetch by url
			} else {

				// fetch requested component by url
				$query = "SELECT * FROM  " . TABLE_PREFIX . "components AS a INNER JOIN " . TABLE_PREFIX . "components_meta AS b ON a.component_id = b.component_id WHERE a.url='" . $requested_url . "'";
		
			}

			// call to database, grab first array item because there should only be one
			$requested_component_data = $vce->db->get_data_object($query, false);
			
		}
		
		if ($default_to_homepage) {
		
			// if url is not found, return / for homepage
			if (empty($requested_component_data)) {
		
				// get homepage
				$query = "SELECT * FROM  " . TABLE_PREFIX . "components AS a INNER JOIN " . TABLE_PREFIX . "components_meta AS b ON a.component_id = b.component_id WHERE a.url='/'";
				$requested_component_data = $vce->db->get_data_object($query, false);
			
				// if no homepage has been set, then direct to message
				if (empty($requested_component_data)) {
					require_once(BASEPATH . 'vce-application/html/index.html');
					exit();
				}

			}

			$requested_component = $this->assemble_component_objects($requested_component_data, $vce)[0];
		
		}
		
		return $requested_component;
	 
	 }
	
	/**
	 * Gets components and associated meta data
	 * this is done from the component which is being accessed by url backwards to the start of the recipe
	 * Called by __construct(), takes the $requested_id and returns all associated components
	 * @global object $vce
	 * @param int $page_id
	 * @param int $requested_id
	 * @param array $components
	 * @return adds components to class-wide array of components
	 */
	public function get_components($vce, $page_id, $requested_id, $components) {
		
		// not first time
		if (!isset($components['requested_component'])) {

			// get children of current_id
			$query = "SELECT * FROM  " . TABLE_PREFIX . "components INNER JOIN " . TABLE_PREFIX . "components_meta ON " . TABLE_PREFIX . "components.component_id = " . TABLE_PREFIX . "components_meta.component_id WHERE " . TABLE_PREFIX . "components.component_id='" . $page_id . "'";
			$requested_component_data = $vce->db->get_data_object($query, false);
			
			// hook that can be used to alter database query results
			if (isset($vce->site->hooks['page_requested_components'])) {
				foreach($vce->site->hooks['page_requested_components'] as $hook) {
					$requested_component_data = call_user_func($hook, $requested_component_data, func_get_args());
				}
			}
			
			$requested_component = $this->assemble_component_objects($requested_component_data, $vce)[0];
		
		// first time so no need to get data this time around
		} else {
		
			// add value from previous function
			$requested_component = $components['requested_component'];
			// clean-up
			unset($components['requested_component']);
			
		}
		

		// get recipe and add to base of object
		if (isset($requested_component->recipe) && $requested_component->parent_id == '0') {

			// decode json object of recipe
			$recipe = json_decode($requested_component->recipe, true)['recipe'];
			
			// load hooks
			if (isset($vce->site->hooks['page_add_recipe'])) {
				foreach($vce->site->hooks['page_add_recipe'] as $hook) {
					$recipe = call_user_func($hook, $this->recipe, $recipe);
				}
			}
			
			// set recipe property of page object
			$this->recipe = $recipe;
			
			// clean-up
			unset($requested_component->recipe);
		
		}	
		
		// load hooks
		if (isset($vce->site->hooks['page_get_components'])) {
			foreach($vce->site->hooks['page_get_components'] as $hook) {
				call_user_func($hook,$requested_component,$components,$vce);
			}
		}
		
		// to add a parent property onto each component, we do so here
		if (!empty($components)) {
		 	$components[0]->parent = $requested_component;
		}
		
		// prepend to begining of array to make parents first
		array_unshift($components, $requested_component);
		

		// if component has parent id, recursive call to this function
		if (isset($requested_component->parent_id) && $requested_component->parent_id != 0) {

			// recursive call
			return self::get_components($vce, $requested_component->parent_id, $requested_id, $components);

		// check that access is allowed for sub components
		} else {
		
			// to check find_sub_components returned value, get end component
			$end_component = end($components);
			
			// copy the recipe
			$recipe_tree = $this->recipe;
			$recipe_level = 1;
			
			// cycle though the components and walk the recipe tree
			foreach ($components as $key=>$each_component) {

				// only one recipe item on this level
				if (!empty($recipe_tree) && count($recipe_tree) == 1) {
					$this_recipe = $recipe_tree[0];
					$recipe_level_location = 0;
				} else {
				
					$component_recipe_key = isset($each_component->recipe_key) ? $each_component->recipe_key : 0;

					$this_recipe = isset($recipe_tree[$component_recipe_key]) ? $recipe_tree[$component_recipe_key] : $recipe_tree[0];
					$recipe_level_location = $component_recipe_key;
					
					// if no recipe_key was set and there are multiple recipe items at this level
					if (!isset($each_component->recipe_key)) {
					
						$recipe_type_tracker = array();

						// when we "lumberjack" the recipe tree trunk up a level, it might be empty
						if (isset($recipe_tree)) {
							foreach ($recipe_tree as $recipe_key=>$recipe_value) {
					
								if (!isset($recipe_type_tracker[$recipe_value['type']])) {
									$recipe_type_tracker[$recipe_value['type']][0] = true;
								} else {
									$recipe_type_tracker[$recipe_value['type']][count($recipe_type_tracker[$recipe_value['type']])] = true;
								}
				
								if ($recipe_value['type'] == $each_component->type && isset($recipe_type_tracker[$recipe_value['type']][$component_recipe_key])) {
							
									$this_recipe = $recipe_value;
									$recipe_level_location = $recipe_key;
						
									break;
		
								}
					
							}
						}
					
					}
					
				}
				
				$recipe_sub_tree = null;
				
				if (isset($recipe_tree[$recipe_level_location]['components'])) {
				
					// the list of attributes that cascade forward within recipes
					$attributes = array();
					
					// look for attribute within current sub_recipe level
					foreach (array('role_access','content_access','content_create','content_edit','content_delete') as $each_attribute) {
					
						// ['components'][0]
						if (isset($recipe_tree[$recipe_level_location][$each_attribute])) {
							
							// ['components'][0]
							$attributes[$each_attribute] = $recipe_tree[$recipe_level_location][$each_attribute];
					
						}
					
					}
						
					// [0]['components']
					if (!empty($attributes) && isset($recipe_tree[$recipe_level_location]['components'])) {
						
						// cyle though next leve sub_recipe items
						//[ 0]['components']
						foreach ($recipe_tree[$recipe_level_location]['components'] as $each_sub_key=>$each_sub_value) {
					
							// cyle through any attibute values set
							foreach ($attributes as $attribute_key=>$attribute_value) {
								
								// make sure not to overwrite a value that is set
								if (!isset($each_sub_value[$attribute_key])) {
					
									// add attributes to next sub_recipe level
									// [0]['components']
									$recipe_tree[$recipe_level_location]['components'][$each_sub_key][$attribute_key] = $attribute_value;
					
								}
					
							}
				
					
						}
					
					}
				
					// set value for sub_recipe
					$recipe_sub_tree = $recipe_tree[$recipe_level_location]['components'];
				
				}

				// recipe that is not at the root
				if (isset($each_component->recipe)) {
					
					$recipe_meta_data = json_decode($each_component->recipe, true)['recipe'];
					
					unset($each_component->recipe);
				
					$recipe_sub_tree = $recipe_meta_data[0]['components'];

					$this_recipe = $recipe_meta_data[0];
	
				}
				
				// provide a way for the parent to change the sub_recipe items
				$each_component->sub_recipe = $each_component->included_sub_recipe($vce, $recipe_sub_tree);

				// we don't need the whole recipe
				unset($this_recipe['components']);
			
				// add the recipe for this component
				$each_component->recipe = $this_recipe;
				
				// "lumberjack" the recipe tree trunk up a level
				$recipe_tree = $recipe_sub_tree;
			
			}
				
			// call to each component that is within the parent trunk before moving on
			// the reason for this call is to allow the page object to be altered 
			// before moving forward with getting sub components
			foreach ($components as $each_parent_component) {
				$each_parent_component->trunk_component($each_parent_component, $components, $vce);
				
				// at somepoint should consider standarizing to:
				// included_sub_components
				
				// $requested_components would be children components of $each_parent_component
				
				// $recipe_items would be the sub recipe of $each_parent_component
				
				// $components would be the trunk, up to $each_parent_component
				
				// $each_parent_component->included_sub_components($vce, $requested_components, $recipe_items, $components, $sub_components);
		
				// the last $each_parent_component would be called again within get_sub_components, so that would need to be prevents

			}
			
			// to check find_sub_components returned value, get end component
			$end_component = end($components);
			
			// get returned value from component for find_sub_components method
			// by default returns true from method in components.class
			// true from components continues getting sub components
			$find_sub_components = $end_component->find_sub_components($end_component, $vce, $components, $sub_components = array());

			// if the type of the component has been changed, in either the hook or find sub components, then re-instantiate the object
			if (get_class($end_component) != $end_component->type) {
				$components[count($components) -1] = self::instantiate_component((array) $end_component, $vce);
			}
			
			// check if find_sub_components is true
			if ($find_sub_components) {
			
				// get sub-components
				// $vce, $recipe_tree, $current_id, $parent_recipe, $components
				$nested_components = self::get_sub_components($vce, $end_component->sub_recipe, $requested_id, $end_component, $components);
						
				// add sub_components to components list
				if (!empty($nested_components)) {
					$components[(count($components)-1)]->components = $nested_components;
				}
			}
			
			// add components to object
			return $components;

		}
	
	}
	

	/**
	 * Gets list of sub-components and associated meta data
	 * Takes the id of the component being process and queries for all components ordered under it
	 * @global object $db
	 * @param int $current_id
	 * @param array $parent_recipe
	 * @param array $sub_components
	 * @param string $sub_url
	 * @param array $options => array('full_object' = false, 'orphan' = true, 'exclude' => component->type, 'arrest' => 'component->type)
	 *
	 * @return array of subcomponents
	 */
	public function get_sub_components($vce, $recipe_tree, $current_id, $parent = array(), $components = array(), $sub_components = array(), $sub_url = array(), $options = array()) {

		//prevent empty value for current_id
		if (empty($current_id)) {
			return null;
		}
		
		// basic get children of current_id value query, order first by sequence and then by component_id
		$query = "SELECT * FROM " . TABLE_PREFIX . "components AS a JOIN " . TABLE_PREFIX . "components_meta AS b ON a.component_id = b.component_id WHERE a.parent_id='" . $current_id . "' ORDER BY a.sequence ASC, a.component_id ASC";

		if (isset($parent->recipe['order_by']) || isset($parent->recipe['pagination_limit'])) {
		
			// defining order values and also adding them to the parent component as properties
			$order_by = $parent->order_by = isset($parent->recipe['order_by']) ? $parent->recipe['order_by'] : 'component_id';
			$order_direction = $parent->order_direction = (isset($parent->recipe['order_direction']) && $parent->recipe['order_direction'] == strtolower('desc')) ? 'DESC' : 'ASC';

			if (isset($parent->recipe['pagination_limit'])) {

				// first we need to get the total count
				$count_query = "SELECT count(*) AS count FROM " . TABLE_PREFIX . "components WHERE parent_id='" . $current_id . "'";
				$count = $vce->db->get_data_object($count_query, false);
				
				// defining pagination values and also adding them to the parent component as properties
				$count = $parent->pagination_count = isset($count[0]['count']) ? $count[0]['count'] : 0;
				$pagination_limit = $parent->pagination_limit = ($parent->recipe['pagination_limit'] < $count) ? $parent->recipe['pagination_limit'] : $count;
				$pagination_offset = $parent->pagination_offset = isset($vce->pagination_limit) ? $vce->pagination_limit : 0;
				

				$query = "
SELECT a.*, e.* 
FROM vce_components AS a
JOIN (
SELECT b.component_id FROM " . TABLE_PREFIX . "components b
LEFT JOIN " . TABLE_PREFIX . "components_meta AS c ON b.component_id = c.component_id AND c.meta_key='" . $order_by . "' 
WHERE b.parent_id=" . $current_id . " 
ORDER BY c.meta_value IS NULL " . $order_direction . ", c.meta_value " . $order_direction . " LIMIT " . $pagination_limit . " OFFSET " . $pagination_offset . "
) d ON a.component_id IN (d.component_id)
LEFT JOIN " . TABLE_PREFIX . "components_meta AS e ON a.component_id = e.component_id
";
			} else {

				$query = "
SELECT a.*, c.* FROM " . TABLE_PREFIX . "components AS a 
LEFT JOIN " . TABLE_PREFIX . "components_meta AS b ON a.component_id = b.component_id AND b.meta_key = '" . $order_by . "' 
LEFT JOIN " . TABLE_PREFIX . "components_meta AS c ON a.component_id = c.component_id 
WHERE a.parent_id=" . $current_id . " 
ORDER BY b.meta_value IS NULL " . $order_direction . ", b.meta_value " . $order_direction . "
";
		
			}

		}
		
		// get children of current_id
		$requested_component_data = $vce->db->get_data_object($query, false);
		
		// assemble and instantiate
		$requested_components = $this->assemble_component_objects($requested_component_data, $vce);

		$recipe_items = array();
		
		if (!empty($parent)) {
			// provide opportunity for parent component to modify included sub_components
			$recipe_tree = $parent->included_sub_recipe($vce, $recipe_tree);
		}

		if (!empty($recipe_tree) && is_array($recipe_tree)) {
		
			// cycle through recipe items at this level and create a lookup table
			foreach($recipe_tree as $each_recipe_key=>$each_recipe_value) {
				if (isset($each_recipe_value['type'])) {
			
					if (!isset($each_recipe_value['recipe_key'])) {
						// increment
						if (!isset($recipe_items[$each_recipe_value['type']])) {
							// first key is zero
							$recipe_items[$each_recipe_value['type']][0] = $each_recipe_value;
						} else {
							// additional keys are the key the recipe has in the level
							$recipe_items[$each_recipe_value['type']][$each_recipe_key] = $each_recipe_value;
						}
					} else {
						// allow for a recipe_key value to be added to recipe item
						$recipe_items[$each_recipe_value['type']][$each_recipe_value['recipe_key']] = $each_recipe_value;
					}
					
					// remove sub components
					unset($each_recipe_value['components']);
					
					// instantiate
					$each_recipe_component = $this->instantiate_component($each_recipe_value, $vce);
					
					// returns requested_components
					$requested_components = $each_recipe_component->recipe_item_reference($each_recipe_component, $vce, $components, $sub_components, $requested_components);
					
				}
			}
		}
		
		if (!empty($parent)) {
			// provide opportunity for parent component to modify included sub_components
			$requested_components = $parent->included_sub_components($vce, $requested_components, $recipe_items, $components, $sub_components);
		}

		if (!empty($requested_components)) {
		
			// load hooks
			// page_get_sub_components
			if (isset($vce->site->hooks['page_get_sub_components'])) {
				foreach($vce->site->hooks['page_get_sub_components'] as $hook) {
					$requested_components = call_user_func($hook,$requested_components,$sub_components,$vce);
				}
			}
			
			$recursive_check = array();
			
			// check find_sub_components
			foreach ($requested_components as $each_key=>$each_component) {
			
				// exclude components and prevent any sub_components afterwards
				if (!empty($options['exclude'])) {
					if (is_array($options['exclude'])) {
						if (in_array($each_component->type, $options['exclude'])) {
							unset($requested_components[$each_key]);
							continue;
						}
					} else {
						if (in_array($each_component->type, explode(',', str_replace(', ',',',$options['exclude'])))) {
							unset($requested_components[$each_key]);
							continue;
						}
					}
				}
			
				// add parent unless $options['orphan'] = true
				if  (empty($options['orphan']) && !empty($parent)) {
					// add parent to each_component
					$each_component->parent = $parent;
 				}
	
				// used to prevent component from being included
 				if (!$each_component->include_component($each_component, $vce, $components, $sub_components)) {
 					continue;
 				}

				// a recipe match has been found
				if (isset($recipe_items[$each_component->type])) {
				
					$recipe_key = 0;
					
					// change value if recipe_key has been set in component data
					if (isset($each_component->recipe_key) && isset($recipe_items[$each_component->type][$each_component->recipe_key])) {
						$recipe_key = $each_component->recipe_key;
					}
					
					// set recipe first
					$this_recipe = isset($recipe_items[$each_component->type][$recipe_key]) ? $recipe_items[$each_component->type][$recipe_key] : null;
					
					// clean up beforehand
					unset($this_recipe['components']);
					
					$each_component->recipe = $this_recipe;
					
					// check that there is a sub recipe
					if (isset($recipe_items[$each_component->type][$recipe_key]['components'])) {
					
						$sub_recipe = $recipe_items[$each_component->type][$recipe_key]['components'];
					
						// search for previous value and then add at this  list of attributes that cascade forward within recipes
						foreach (array('role_access','content_access','content_create','content_edit','content_delete') as $each_attribute) {
							// check each attribute
							if (isset($this_recipe[$each_attribute])) {
								foreach ($sub_recipe as $each_recipe_key=>$each_recipe_item) {
									// if there is not a value already, cascase the previous value forward
									if (!isset($each_recipe_item[$each_attribute])) {
										$sub_recipe[$each_recipe_key][$each_attribute] = $this_recipe[$each_attribute];
									}
								}
							}
						}
					
						$each_component->sub_recipe = $sub_recipe;
					
					}
				
				}
				
				// check that component allows sub_components to be built in page object
				$recursive_check[$each_component->component_id] = $each_component->find_sub_components($each_component, $vce, $components, $sub_components);

				// if the type of the component has been changed, in either the hook or find sub components, then re-instantiate the object
				if (get_class($each_component) != $each_component->type) {
					$requested_components[$each_key] = $this->instantiate_component((array) $each_component, $vce);
				}

			}
			
			// anonymous function to place requested components into a multidimensional array of sub components
			// $each_sub_component->components, $requested_components
			$build_components_tree = function($sub_components, $requested_components) use (&$build_components_tree, $vce) {

				// take requested_components and associate parent_id with sub_component id
				// get parent_id associated with this level of requested page
				$parent_id = $requested_components[0]->parent_id;
			
				foreach ($sub_components as $key=>$each_sub_component) {
				
					// current matches parent
					if ($each_sub_component->component_id == $parent_id) {
					
						// found parent and returning value
						$each_sub_component->components = $requested_components;

						// break out of foreach and then use the return at the end of this fuction
						break;
			
					}

					if (isset($each_sub_component->components)) {
						// up to next level, recursive call back to anonymous function
						$sub_components[$key]->components = $build_components_tree($each_sub_component->components,$requested_components);
					}
				
				}
				
				// one and only return out of this funtion
				return $sub_components;
				
			};
			

			if (!empty($sub_components)) {
				if (!empty($requested_components)) {
					// subsequent times, call to anonymous recursive function
					$sub_components = $build_components_tree($sub_components,$requested_components);
				}
			} else {
				// first time through
				$sub_components = $requested_components;
			}
		
			// check for sub components
			foreach ($requested_components as $each_key=>$each_component) {

				// using empty to check for no value or false
				if (isset($each_component->url) && empty($options['full_object'])) {
					$sub_url[$each_component->component_id] = true;
				}
								
				// if $recursive is true then recursive call back to get_sub_components for next component
				$recursive = false;

				// check that component allows sub_components to be built in page object
				if (isset($recursive_check[$each_component->component_id])) {
					$recursive = $recursive_check[$each_component->component_id];
				}
		
				// if find_sub_components returned true for current component, then check for the following
				if ($recursive) {
					// check for sub_url
					// the purpose of this is if you have several branches of different depths
					// where sub_url (the next url) might be at a deeper level
					if (isset($sub_url[$each_component->parent_id])) {
						$recursive = false;
					}
				}
				
				// if full_object is true, then overide recursive value and call back to get_sub_components
				if (!empty($options['full_object']) || isset($this->recipe[0]['full_object'])) {
					$recursive = true;
				}
				
				// arrest branch build and prevent any sub_components afterwards
				if (!empty($options['arrest'])) {
					if (is_array($options['arrest'])) {
						if (in_array($each_component->type, $options['arrest'])) {
							$recursive = false;
						}
					} else {
						if (in_array($each_component->type, explode(',', str_replace(', ',',',$options['arrest'])))) {
							$recursive = false;
						}
					}
				}
				
				
				
				// send call back to this function
				if ($recursive) {
					// our recursive call
					// get_sub_components($vce, $recipe_tree, $current_id, $parent, $components, $sub_components = array(), $sub_url = array(), $options = array())
					self::get_sub_components($vce, $each_component->sub_recipe, $each_component->component_id, $each_component, $components, $sub_components, $sub_url, $options);
				}	

			}
		}
		
		// return nested components
		return $sub_components;

	}

	
	/**
	 * Builds content from component tree created in get_components and get_subcomponents
	 * 	 
	 *  Order of component methods
	 * - - - - - - - - - - - - - - - -
	 * 
	 * as_content <- parent component
	 * 
	 * 	recipe_manifestation <- component type A
	 * 	add_component 
	 * 	as_content <-- first of component type A
	 * 		<- children component
	 * 	as_content_finish
	 * 	as_content <-- second of component type A
	 * 	as_content_finish
	 * 	add_component_finish
	 * 	recipe_manifestation_finish
	 * 		
	 * 	recipe_manifestation <- component type B
	 * 	add_component 
	 * 	as_content <-- first of component type B
	 * 	as_content_finish
	 * 	add_component_finish
	 * 	recipe_manifestation_finish
	 *
	 * 	recipe_manifestation <- component type C
	 * 	add_component 
	 * 	as_link <-- link to type C
	 * 	add_component_finish
	 * 	recipe_manifestation_finish
	 *
	 * as_content_finish <- parent component
	 * 
	 * @global object $vce
	 * @param array $components
	 * @param array $recipe
	 * @param int $requested_id
	 * @param bool $linked
	 * @param array $recipe_tracker
	 * @return components have added their content to the $content object
	 */
	public function build_content($vce, $components, $recipe, $requested_id, $linked = false, $instantiated_recipe_items = array()) {

		// "marbled crawfish" abilities to be created while within the component itself
		$process_leaf_component = function($each_component) use ($vce) {
		
			// call to current add component method associted with current component
			// if (isset($each_component->recipe) && isset($each_component->recipe['potence']) && $each_component->recipe['potence'] == 'self' && $vce->page->can_add($each_component)) {
			if (isset($each_component->recipe)) {

				// grab the parent component
				$parent_component = isset($vce->page->components[(count($vce->page->components) - 2)]) ? $vce->page->components[(count($vce->page->components) - 2)] : $vce->page->components[(count($vce->page->components) - 1)];
			
				// make a copy to pass to add_component
				$current_component = $each_component;
				
				// check if this should be added or not
				$auto_create_for_current = null;;
				
				if (isset($each_component->sub_recipe)) {
					foreach ($each_component->sub_recipe as $each_item) {
						if (isset($each_item['auto_create'])) {
							$auto_create_for_current = $each_component->sub_recipe;
						}
					}
				}
				
				if (isset($parent_component->url)) {
					$current_component->parent_url = $parent_component->url;
				}
				
				$dossier = array(
				'type' => $each_component->type,
				'procedure' => 'create',
				'parent_id' => $each_component->parent_id,
				'parent_url' =>(isset($parent_component->url) ? $parent_component->url : null),
				'sequence' => ($each_component->sequence + 1)
				);

				if (!empty($each_component->recipe_key)) {
					$dossier['recipe_key'] = $each_component->recipe_key;
				}
			
				if (!empty($parent_component->template)) {
					$dossier['template'] = $parent_component->template;
				}
			
				if (!empty($auto_create_for_current)) {
					$dossier['auto_create'] = $auto_create_for_current;
				}

				$current_component->dossier = $dossier;
				
				return $current_component;
			
			}
			
			return (isset($current_component) ? $current_component : null);
		
		};

		// function to process sub_recipe components
		$process_recipe_items = function($each_component, $auto_create = null) use (&$process_recipe_items, $vce) {

			$sequencer = null;
			$current_sequence = 1;
			$type_checker = array();

			// sequence generator: get sequence for next item.
			if (isset($each_component->components)) {
			
				// look at siblings
				foreach ($each_component->components as $each_sibling) {
				
					$current_recipe_key = 0;
					// commenting this check out for now. It may need to be refactored at another time.
					// if this component exists, then we need to skip adding it.
					// $type_checker[$each_sibling->type] = true;
				
					// if no recipe_key has been set, then this is the first instance of the type and the key is the key
					if (!isset($each_sibling->recipe_key) && isset($each_component->sub_recipe)) {
				
						foreach ($each_component->sub_recipe as $key=>$value) {
					
							if ($value['type'] == $each_sibling->type) {
						
								$current_recipe_key = $key;
							
								break;
						
							}
					
						}
					
					} else {
					
						// the key is the recipe key
						$current_recipe_key = $each_sibling->recipe_key;
					
					}
													
					if (isset($sequencer[$each_sibling->type][$current_recipe_key])) {
					
						$sequencer[$each_sibling->type][$current_recipe_key] = ($each_sibling->sequence > $sequencer[$each_sibling->type][$current_recipe_key]) ? $each_sibling->sequence : $sequencer[$each_sibling->type][$current_recipe_key];
					
					} else {
					
						$sequencer[$each_sibling->type][$current_recipe_key] = $each_sibling->sequence;
					
					}
					
					$current_sequence = ($each_sibling->sequence > count($each_component->components)) ? $each_sibling->sequence  : $current_sequence;

				}
		
			}

			if (isset($each_component->sub_recipe)) {

				// cycle through sub components
				foreach ($each_component->sub_recipe as $key=>$each_recipe_component) {
				
					$auto_create = null;
					
					// auto_create == reverse
					if (isset($each_recipe_component['auto_create']) && $each_recipe_component['auto_create'] == "reverse") {
					
						// if this component exists, then we need to skip adding it.
						if (!isset($type_checker[$each_recipe_component['type']])) {
						
							$look_forward = $each_recipe_component;
				
							// move up one level past the auto_create == "reverse" and make that the component to create
							if (isset($look_forward['components'])) {
								// find next component in sub_recipe after auto_create

								$look_forward = $look_forward['components'][0];
					
								// the auto_create component is the parent
								$this_component = $each_recipe_component;
								unset($this_component['components']);
								// instantiate the parent
								$parent_component = $this->instantiate_component($this_component, $vce);
					
								// add the auto_create == backwards component as parent to look_forward
								$look_forward['parent'] = $parent_component;

							}
									
							$each_recipe_component = $look_forward;
					
							$auto_create[] = $each_component->sub_recipe[$key];
							
						}
							
					}
							
					// instantiate component within recipe
					$this_component = $this->instantiate_component($each_recipe_component, $vce);
				
					$instantiated[] = $this_component;

					// recipe_key is a meta_key that is used when the same component occures multipe time at a recipe level
					// otherwise there is no way of knowing which a compoment belongs to. It's a complicated imperfect world.
					$recipe_key = null;
					if (count($each_component->sub_recipe) > 1) {
				
						// check if array is set
						if (!isset($sub_recipe_components)) {
							$sub_recipe_components = array();
							// create an array of sub_recipe items to check if type occures multiple times
							foreach ($each_component->sub_recipe as $sub_recipe_key=>$sub_recipe_value) {
								// create associate array
								if (isset($sub_recipe_components[$sub_recipe_value['type']])) {
									$sub_recipe_components[$sub_recipe_value['type']]++;
								} else {
									$sub_recipe_components[$sub_recipe_value['type']] = 1;
								}
							}
						}
				
						// if more than one occurance of this component type in recipe at this level, then add recipe_key to help with build
						if (isset($sub_recipe_components[$each_recipe_component['type']]) && $sub_recipe_components[$each_recipe_component['type']] > 1) {
							
							// $vce->dump('recipe_key ' . $key . ' being added to ' . $each_recipe_component['type']);
							
							// add recipe_helper
							$each_recipe_component['recipe_key'] = $key;
							$recipe_key = $key;
						
						}					

					}
									
					if (isset($each_component->sub_recipe[$key]['components'])) {			
					
						foreach ($each_component->sub_recipe[$key]['components'] as $each_sub_recipe) {
					
							if (isset($each_sub_recipe['auto_create']) && $each_sub_recipe['auto_create'] == 'forward') {
						
								// add any auto_create == forward
								$auto_create[] = $each_sub_recipe;
						
							}
					
						}

					}
				
					// add parent_url
					$this_component->parent_url = isset($each_component->url) ? $each_component->url : null;
			
					// add parent
					$this_component->parent_id = $each_component->component_id;
				
					// parent type
					$this_component->parent_type = $each_component->type;
					
					// addition of parent object if one is not currently set by an instance with auto_create = reverse
					if (!isset($this_component->parent)) {
						$this_component->parent = $each_component;
					} else {
						// parent type
						$this_component->parent_type = $this_component->parent->type;
					}
					
					// if children_sequencer == flat, then sequence should be one more than the previous
					if (isset($each_component->children_sequencer) && $each_component->children_sequencer == 'flat') {
					
						$flat_sequence = isset($each_component->components) ? count($each_component->components) : 0;
					
						// for flat, ignore type block and simply add 1 to count
						$this_component->sequence = $flat_sequence + 1;
						
					} else {
					
						// $sequencer_key = isset($each_recipe_component['recipe_key']) ? $each_recipe_component['recipe_key'] : 0;
						$sequencer_key = isset($this_component->recipe_key) ? $this_component->recipe_key : 0;
		
						
						// if $sequencer is not set, then create a sequence up one level
						if (!isset($sequencer) || !isset($sequencer[$this_component->type][$sequencer_key])) {
					
							$this_component->sequence = ($key * 100) + 1;
					
						} else {

							$this_component->sequence = $sequencer[$this_component->type][$sequencer_key] + 1;
							
						}
					
					}
					
				
				
					// add template which is supplied by the 
					// $this_component->template = isset($each_recipe_component['template']) ? $each_recipe_component['template'] : null;
					$this_component->template = isset($this_component->template) ? $this_component->template : null;

					// page_requested_components hook
					if (isset($vce->site->hooks['page_requested_components'])) {
						foreach($vce->site->hooks['page_requested_components'] as $hook) {
							call_user_func($hook, $this_component);
						}
					}

					// check if user role can create this component
					//if ($vce->page->can_add((object) $each_recipe_component)) {
					if ($vce->page->can_add((object) $this_component)) {
					
						// the instructions to pass through the form with specifics
						// 'parent_url' => $each_recipe_component['parent_url']
						// 'current_url' => $each_recipe_component['current_url']
						
						$dossier = array(
						'type' => $this_component->type,
						'procedure' => 'create',
						'parent_id' => $this_component->parent_id,
						'sequence' => $this_component->sequence
						);
						
						if (!empty($recipe_key)) {
							$dossier['recipe_key'] = $recipe_key;
						}
					
						if (!empty($this_component->template)) {
							$dossier['template'] = $this_component->template;
						}
					
						if (!empty($auto_create)) {
							$dossier['auto_create'] = $auto_create;
						}
						
						// look for dossier value that have been added to sub_recipe using find_sub_components method of parent
						// $requested_component->sub_recipe[0]['dossier']['foo'] = 'bar';
						if (isset($this_component->dossier)) {
							foreach ($this_component->dossier as $each_dossier_key=>$each_dossier_value) {
								// overwrite protection
								if (!isset($dossier[$each_dossier_key])) {	
									$dossier[$each_dossier_key] = $each_dossier_value;
								}
							}
						}
						
						// add dossier
						$this_component->dossier = $dossier;
						
					}
				
				}

			}
			
			return (isset($instantiated) ? $instantiated : array());
		
		};
		
		// add_componts for recipe items when they do not yet exist at this level
		$process_absent_components = function($location, $ending_location, $instantiated_recipe_items, $recipe_item_start) use ($vce) {

			while ($location < $ending_location) {

				if (!isset($recipe_item_start[$location])) {
				
					// set a placeholder value for auto_create detection
					$auto_create_reverse = false;
					
					// is this an auto_create_reverse?
					if (isset($instantiated_recipe_items[$location]->parent)) {
						if (isset($instantiated_recipe_items[$location]->parent->auto_create) && $instantiated_recipe_items[$location]->parent->auto_create == 'reverse') {
							$auto_create_reverse = true;
						}
					}
					
					// isset($instantiated_recipe_items[$location]->parent
					// if a parent component has been set by auto_create == reverse, then call recipe_manifestation
					if ($auto_create_reverse) {

						if (self::$display_build_calls) {
							$vce->dump('(auto-create reverse recipe item) recipe_manifestation ' . $instantiated_recipe_items[$location]->parent->type, substr(dechex($vce->ilkyo($instantiated_recipe_items[$location]->parent->type)),- 6));
						}
					
						// call to recipe_manifestation, which is called first and without any access restrictions
						$instantiated_recipe_items[$location]->parent->recipe_manifestation($instantiated_recipe_items[$location]->parent, $vce);
					
					}

					if (self::$display_build_calls) {
						$vce->dump('(recipe item) recipe_manifestation ' . $instantiated_recipe_items[$location]->type, substr(dechex($vce->ilkyo($instantiated_recipe_items[$location]->type)),- 6));
					}

					// call to recipe_manifestation, which is called first and without any access restrictions
					$instantiated_recipe_items[$location]->recipe_manifestation($instantiated_recipe_items[$location], $vce);

					// check if user role can create this component
					if ($vce->page->can_add($instantiated_recipe_items[$location])) {
	
						if (self::$display_build_calls) {
							$vce->dump('(recipe item) add_component ' . $instantiated_recipe_items[$location]->type, substr(dechex($vce->ilkyo($instantiated_recipe_items[$location]->type)),- 6));
						}
						
						// access add_component for current component
						$instantiated_recipe_items[$location]->add_component($instantiated_recipe_items[$location], $vce);
				
						if (self::$display_build_calls) {
							$vce->dump('(recipe item) add_component_finish ' . $instantiated_recipe_items[$location]->type, substr(dechex($vce->ilkyo($instantiated_recipe_items[$location]->type)),- 6));
						}
		
						// access add_component for current component
						$instantiated_recipe_items[$location]->add_component_finish($instantiated_recipe_items[$location], $vce);
	
					}
				
					if (self::$display_build_calls) {
						$vce->dump('(recipe item) recipe_manifestation_finish ' . $instantiated_recipe_items[$location]->type, substr(dechex($vce->ilkyo($instantiated_recipe_items[$location]->type)),- 6));
					}
					
					// call to recipe_manifestation, which is called first and without any access restrictions
					$instantiated_recipe_items[$location]->recipe_manifestation_finish($instantiated_recipe_items[$location], $vce);

					// if a parent component has been set by auto_create == reverse, then call recipe_manifestation_finish
					if ($auto_create_reverse) {
					
						if (self::$display_build_calls) {
							$vce->dump('(auto-create reverse recipe item) recipe_manifestation_finish ' . $instantiated_recipe_items[$location]->parent->type, substr(dechex($vce->ilkyo($instantiated_recipe_items[$location]->parent->type)),- 6));
						}
					
						// call to recipe_manifestation, which is called first and without any access restrictions
						$instantiated_recipe_items[$location]->parent->recipe_manifestation_finish($instantiated_recipe_items[$location]->parent, $vce);
					
					}

					// set to return
					$recipe_item_start[$location] = true;

				}
			
				$location++;

			}
			
			return $recipe_item_start;

		};

		// START CREATE LEVEL MAP
		$type_tracker = null;
		$level_map = null;
		// flat or sort
		$children_sequencer = 'sort';
		
		if (!empty($instantiated_recipe_items)) {
			
			// cycle through instantiated_recipe_items to create level_map which includes recipe.
			foreach ($instantiated_recipe_items as $each_recipe_key=>$each_recipe_item) {
			 
			 	$level_map[$each_recipe_key]['recipe'] = $each_recipe_item;
			 	
				// keep track of each type of component within sub_recipes
				// $type_tracker[*type*][*occurraence*] = *key postion within $instantiated_recipe_items*
				if (isset($each_recipe_item->type)) {
					if (!isset($type_tracker[$each_recipe_item->type])) {
						$type_tracker[$each_recipe_item->type][0] = $each_recipe_key;
					} else {
						//$type_tracker[$each_recipe_item->type][count($type_tracker[$each_recipe_item->type])] = $each_recipe_key;
						$type_tracker[$each_recipe_item->type][$each_recipe_key] = $each_recipe_key;
					}
				}
			 
			 }
		
		}
		
		// cycle through components and add add them to the level_map
		foreach ($components as $each_component_key=>$each_component) {
		
			// check if this should be built as a flat sequence
			if (isset($each_component->parent)) {

				// if children_sequencer value is saved in the component
				if (isset($each_component->parent->children_sequencer)) {
					$children_sequencer = $each_component->parent->children_sequencer;
				}
			
				// if children_sequencer is in the recipe 
				if (isset($each_component->parent->recipe) && isset($each_component->parent->recipe['children_sequencer'])) {
					$children_sequencer = $each_component->parent->recipe['children_sequencer'];
				}
				
				// check if sequencer is flat
				if ($children_sequencer == "flat") {
					// add all components to the first level_map component so that they are dispayed in order
					$level_map[0]['components'][] = $each_component;
					continue;
				
				}
			
			}
			
			$component_recipe_key = isset($each_component->recipe_key) ? $each_component->recipe_key : 0;
	
			if (isset($type_tracker)) {
				
				// default value
				$key = 0;
			
				if (isset($type_tracker[$each_component->type][$component_recipe_key])) {
				
					$key = $type_tracker[$each_component->type][$component_recipe_key];
					
				} else {
					// if a recipe_key has been set but is not valid, and if the type exists then use the first instance of it
					if (isset($type_tracker[$each_component->type])) {
					
						$key = $type_tracker[$each_component->type][0];
					
					}
		
				}
				
				// $key = isset($type_tracker[$each_component->type][$component_recipe_key]) ? $type_tracker[$each_component->type][$component_recipe_key] : 0;
		
				$level_map[$key]['components'][] = $each_component;
		
			} else {
			
				$level_map[$each_component_key]['components'][] = $each_component;
			
			}
		
		}
		// END CREATE LEVEL MAP
		
		// START LOOP THROUGH THIS LEVEL
		// components that are part of "the trunk," aka before the requested url will be the first time through
		if (!empty($level_map)) {
			foreach ($level_map as $each_level_key=>$each_level_item) {

				if (isset($each_level_item['recipe'])) {
			
					if ($children_sequencer  == 'sort') {
			
						if (self::$display_build_calls) {
							$vce->dump('(recipe item ' . $each_level_key . ' / start ' . $each_level_item['recipe']->type . ') recipe_manifestation ' . $each_level_item['recipe']->type, substr(dechex($vce->ilkyo($each_level_item['recipe']->type)),- 6));
						}

						// call to recipe_manifestation, which is called first and without any access restrictions
						$each_level_item['recipe']->recipe_manifestation($each_level_item['recipe'], $vce);

						// disabling this but saving in case the approach below doesn't work
						// $allow_sub_components = true;
						// if (isset($each_level_item['components'][0]) && isset($each_level_item['components'][0]->parent)) {
						//	$allow_sub_components = $each_level_item['components'][0]->parent->allow_sub_components($each_level_item['components'][0]->parent, $vce);
						// }
					
						$allow_sub_components = $each_level_item['recipe']->parent->allow_sub_components($each_level_item['recipe']->parent, $vce);

						// check if user role can create this component
						if ($vce->page->can_add($each_level_item['recipe']) && $allow_sub_components === true) {
					
							if (self::$display_build_calls) {
								$vce->dump('(recipe item ' . $each_level_key . ' / start ' . $each_level_item['recipe']->type . ') add_component ' . $each_level_item['recipe']->type, substr(dechex($vce->ilkyo($each_level_item['recipe']->type)),- 6));
							}
							
							// access add_component for current component
							$each_level_item['recipe']->add_component($each_level_item['recipe'], $vce);

						} else {
					
							// prevent any sub recipe items from being processed if allow_sub_components returns false
							if (isset($level_map[$each_level_key]['components'])) {
						
								foreach ($level_map[$each_level_key]['components'] as $match_key=>$match_value) {
							
									if ($match_value->type == $each_level_item['recipe']->type) {

										if (!$level_map[$each_level_key]['components'][$match_key]->allow_sub_components($level_map[$each_level_key]['components'][$match_key], $vce)) {
								
											// if allow_sub_components is false, unset the sub_recipe to clean up.
											unset($level_map[$each_level_key]['components'][$match_key]->sub_recipe);
									
										}
								
									}
							
								}
							
							}
				
						}
				
					} else if ($children_sequencer  == 'flat' && !isset($flat_processed)) {
				
						foreach ($level_map as $each_flat_item) {
					
							if (self::$display_build_calls) {
								$vce->dump('(flat ' . ' / ' . $each_flat_item['recipe']->type . ') recipe_manifestation ' . $each_flat_item['recipe']->type, substr(dechex($vce->ilkyo($each_flat_item['recipe']->type)),- 6));
							}

							// call to recipe_manifestation, which is called first and without any access restrictions
							$each_flat_item['recipe']->recipe_manifestation($each_flat_item['recipe'], $vce);

							// check if user role can create this component
							if ($vce->page->can_add($each_flat_item['recipe'])) {

								if (self::$display_build_calls) {
									$vce->dump('(flat ' . ' / ' . $each_flat_item['recipe']->type . ') add_component ' . $each_flat_item['recipe']->type, substr(dechex($vce->ilkyo($each_flat_item['recipe']->type)),- 6));
								}
							
								// access add_component for current component
								$each_flat_item['recipe']->add_component($each_flat_item['recipe'], $vce);

							}
					
						}
					
						$flat_processed = true;
				
					}

				}
		
				if (!empty($each_level_item['components'])) {
			
					foreach ($each_level_item['components'] as $each_component_key=>$each_component) {
	
						if (self::$display_build_calls) {
							$vce->dump('(component) start ' . $each_component->component_id . ' / ' . $each_component->type, substr(dechex($vce->ilkyo($each_component->type)),- 6));
						}
					
						// page_build_content hook
						if (isset($vce->site->hooks['page_build_content'])) {
							foreach($vce->site->hooks['page_build_content'] as $hook) {
								call_user_func($hook, $each_component, $linked);
							}
						}
					
						// work on "the trunk" first, which is the first time through this recursive method 
						// and before $instantiated_recipe_items has a value
						if (empty($instantiated_recipe_items) && $linked === false) {

							// if component has a template value set as meta_data or in the recipe, use it	
							if (isset($each_component->template) || isset($each_component->recipe['template'])) {
								$template = isset($each_component->template) ? $each_component->template : $each_component->recipe['template'];
								if (!isset($this->template) || $this->template != $template) {
									if (is_file(BASEPATH .'vce-content/themes/' . $vce->site->site_theme . '/' . $template)) {
										$this->template = $template;
									}
								}
							}
						
							// set title
							if (isset($each_component->title)) {
								$this->title = $each_component->title;
							}

							// if check_access returns false, continue to the next element
							if (!$each_component->check_access($each_component, $vce)) {
							
								// if repudiated_url is found in recipe, forward to that location
								if (isset($each_component->recipe['repudiated_url'])) {
							
									header("location: " . $vce->site->site_url . '/' . $each_component->recipe['repudiated_url']);
									exit();
			
								}
						
								// should probably set the template
								break 2;
							}
				
							// store component to close afterwards
							$primary_component[] = $each_component;

						
							// the "marbled crawfish" opener
							if ($each_component->component_id == $requested_id) {

								$leaf_component = $process_leaf_component($each_component);
					
								if (isset($leaf_component)) {
					
									if (self::$display_build_calls) {
										$vce->dump('(leaf) recipe_manifestation ' . $leaf_component->type, substr(dechex($vce->ilkyo($leaf_component->type)),- 6));
									}
								
									// call to recipe_manifestation, which is called first and without any access restrictions
									$leaf_component->recipe_manifestation($leaf_component, $vce);
					
									// check if user can create this component
									if ($vce->page->can_add($leaf_component)) {
			
										if (self::$display_build_calls) {
											$vce->dump('(leaf) add_component ' . $leaf_component->type, substr(dechex($vce->ilkyo($leaf_component->type)),- 6));
										}
									
										// access add_component for current component
										$leaf_component->add_component($leaf_component, $vce);

									}
					
								}
					
							}	
					
						} else {
					
							// if check_access returns false, continue to the next element
							if (!$each_component->check_access($each_component, $vce)) {
								// should probably set the template
								continue;
							}
					
						}
			
						$as_link = null;
						$as_content = null;
			
						// does component have a url assigned?
						if (isset($each_component->url) && $linked !== false) {
							// check to see if this component is the requested id

							if (self::$display_build_calls) {
								$vce->dump('as_link ' . $each_component->component_id . ' / ' . $each_component->type, substr(dechex($vce->ilkyo($each_component->type)),- 6));
							}
						
							// last component was the requested id, so generate links for this component
							$each_component->as_link($each_component, $vce);
					
							$as_link = true;
		
						} else {
							// as content

							if (self::$display_build_calls) {
								$vce->dump('as_content ' . $each_component->component_id . ' / ' . $each_component->type, substr(dechex($vce->ilkyo($each_component->type)),- 6));
							}
						
							$each_component->as_content($each_component, $vce);

							$as_content = true;
						
							// prevent_editing can be used to skip the call to the component's edit_component method
							if (isset($leaf_component) && (!isset($each_component->prevent_editing) || $each_component->prevent_editing === false)) {

								if (self::$display_build_calls) {
									$vce->dump('(leaf) edit_component ' . $each_component->component_id . ' / ' . $each_component->type, substr(dechex($vce->ilkyo($each_component->type)),- 6));
								}
							
								// Need to check all components before implementing this
								//if ($vce->page->can_edit($each_component)) {
									$each_component->edit_component($each_component, $vce);
								//}
					
							}
						
						}
					
						$process_sub_components = true;
					
						if (isset($each_component->components)) {
						// START RECURSIVE CALL
			
							// page_build_content_callback hook
							// allows for sub components to be filtered
							if (isset($vce->site->hooks['page_build_content_callback'])) {
								foreach($vce->site->hooks['page_build_content_callback'] as $hook) {
									$each_component->components = call_user_func($hook, $each_component->components, $each_component, $vce);
								}
							}
				
							if (isset($each_component->url) && $linked === false) {
								// check to see if this component is the requested id
								if ($each_component->component_id == $requested_id) {
									// change value to true 
									$linked = true;
								}
							}
				
							// check if build_sub_components is true
							if ($each_component->build_sub_components($each_component, $vce) && !isset($as_link)) {
				
								$process_sub_components = false;
				
								if (self::$display_build_calls) {
									$vce->dump('next hierachical level / Recursive Call to build_content '  . $each_component->component_id . ' / ' . $each_component->type, 'dedede');
								}
							
								// process_recipe_items
								$next_level_recipe = $process_recipe_items($each_component);
							
								// recursive call for sub component
								self::build_content($vce, $each_component->components, $recipe, $requested_id, $linked, $next_level_recipe);
			
							}
				
						// END RECURSIVE CALL
						} 
					
						if ($process_sub_components) {
						
							if (isset($each_component->sub_recipe) && $each_component->allow_sub_components($each_component, $vce)) {
	
								// or is $leaf_component with sub_recipe item
								if ((!empty($instantiated_recipe_items) && !isset($each_component->url)) || isset($leaf_component)) {

									if (self::$display_build_calls) {
										$vce->dump('next hierachical level / processing sub_recipe items of ' . $each_component->component_id . ' / ' . $each_component->type, 'dedede');
									}
								
									// get sub_recipe items
									$instantiated_sub_recipe_items = $process_recipe_items($each_component);
						
									// go back to any recipe items that have no associated components
									$recipe_item_start = $process_absent_components(0, count($instantiated_sub_recipe_items), $instantiated_sub_recipe_items, null);
							
								}
						
							}
						
						}

						// after the requested url and when not a link
						// if ($linked !== false && !isset($each_component->url)) {
						if (isset($as_content) && empty($primary_component)) {

							if (self::$display_build_calls) {
								$vce->dump('as_content_finish ' . $each_component->component_id . ' / ' . $each_component->type, substr(dechex($vce->ilkyo($each_component->type)),- 6));
							}

							$each_component->as_content_finish($each_component, $vce);

						}
					
					}
							
				}


				if (isset($each_level_item['recipe'])) {
			
					if ($children_sequencer  == 'sort') {
				
						// check if user role can create this component
						if ($vce->page->can_add($each_level_item['recipe']) && $allow_sub_components === true) {

							if (self::$display_build_calls) {
								$vce->dump('(Recipe '  . $each_level_key . '  Finish / ' .  $each_level_item['recipe']->type . ') add_component_finish ' . $each_level_item['recipe']->type, substr(dechex($vce->ilkyo($each_level_item['recipe']->type)),- 6));
							}
						
							// access add_component for current component
							$each_level_item['recipe']->add_component_finish($each_level_item['recipe'], $vce);

						}

					
						if (self::$display_build_calls) {
							$vce->dump('(Recipe '  . $each_level_key . '  Finish / ' .  $each_level_item['recipe']->type . ') recipe_manifestation_finish ' . $each_level_item['recipe']->type, substr(dechex($vce->ilkyo($each_level_item['recipe']->type)),- 6));
						}

						// call to recipe_manifestation, which is called first and without any access restrictions
						$each_level_item['recipe']->recipe_manifestation_finish($each_level_item['recipe'], $vce);


					}
			
				}
		
			}
		}
		
		if ($children_sequencer  == 'flat' && !isset($flat_processed_finish)) {
		
			foreach ($level_map as $each_flat_item) {
			
				if (isset($each_flat_item['recipe'])) {
			
					// check if user role can create this component
					if ($vce->page->can_add($each_flat_item['recipe'])) {

						if (self::$display_build_calls) {
							$vce->dump('(flat ' . ' / ' . $each_flat_item['recipe']->type . ') add_component_finish ' . $each_flat_item['recipe']->type, substr(dechex($vce->ilkyo($each_flat_item['recipe']->type)),- 6));
						}

						// access add_component for current component
						$each_flat_item['recipe']->add_component_finish($each_flat_item['recipe'], $vce);

					}
			
					if (self::$display_build_calls) {
						$vce->dump('(flat ' . ' / ' . $each_flat_item['recipe']->type . ') recipe_manifestation_finish ' . $each_flat_item['recipe']->type, substr(dechex($vce->ilkyo($each_flat_item['recipe']->type)),- 6));
					}
				
					// call to recipe_manifestation, which is called first and without any access restrictions
					$each_flat_item['recipe']->recipe_manifestation_finish($each_flat_item['recipe'], $vce);

				}

			}
			
			$flat_processed_finish = true;
		
		}
		
		
		// execute top component finish
		if (!empty($primary_component)) {
		
			foreach (array_reverse($primary_component) as $parent_component) {

				if (self::$display_build_calls) {
					$vce->dump('(trunk) as_content_finish ' . $parent_component->component_id . ' / ' . $parent_component->type, substr(dechex($vce->ilkyo($parent_component->type)),- 6));
				}
				
				$parent_component->as_content_finish($parent_component, $vce);
				
				// the "marbled crawfish" closer
				if (isset($leaf_component)) {
				
					// check if user can create this component
					if ($vce->page->can_add($leaf_component)) {
			
						if (self::$display_build_calls) {
							$vce->dump('(leaf) add_component_finish ' . $leaf_component->type, substr(dechex($vce->ilkyo($leaf_component->type)),- 6));
						}
						
						// access add_component for current component
						$leaf_component->add_component_finish($leaf_component, $vce);

					}

					if (self::$display_build_calls) {
						$vce->dump('(leaf) recipe_manifestation_finish ' . $leaf_component->type, substr(dechex($vce->ilkyo($leaf_component->type)),- 6));
					}
					
					// call to recipe_manifestation, which is called first and without any access restrictions
					$leaf_component->recipe_manifestation_finish($leaf_component, $vce);

					unset($leaf_component);
				
				}

			}
	
		}

	}
	
	/*
	 * assembles component object from meta data
	 * @param array $requested_component_data
	 * @param object $vce
	 * @return instantiated objects of component type
	 */
	public function assemble_component_objects($requested_component_data, $vce) {
		//$vce->dump($requested_component_data,'9c3');
		if (empty($requested_component_data)) {
			return false;
		}
	
		$results = array();
		
		foreach ($requested_component_data as $meta_data) {
		
			if (!isset($components[$meta_data['component_id']])) {
				// create object and add component table data
				$components[$meta_data['component_id']] = array();
				$components[$meta_data['component_id']]['component_id'] = $meta_data['component_id'];
				$components[$meta_data['component_id']]['parent_id'] = $meta_data['parent_id'];
				$components[$meta_data['component_id']]['sequence'] = $meta_data['sequence'];

				// found a url so make sub_url = true
				if (!empty($meta_data['url'])) {
					$components[$meta_data['component_id']]['url'] = $meta_data['url'];
				}
	
			}

			// create a var from meta_key
			$key = $meta_data['meta_key'];

			// add meta_value
			$components[$meta_data['component_id']][$key] = (($key != 'recipe') ? $vce->db->clean($meta_data['meta_value']) : $meta_data['meta_value']);
			
			// adding minutia if it exists within database table
			if (!empty($meta_data['minutia'])) {
				$key .= "_minutia";
				$components[$meta_data['component_id']][$key] = $meta_data['minutia'];
			}

		}
		//$vce->dump($components,'9c3');
		foreach ($components as $each_component) {
		
			// add configuration moved to instantiate_component
			
			// load hooks
			// page_build_content_callback
			if (isset($vce->site->hooks['page_assemble_component_objects'])) {
				foreach($vce->site->hooks['page_assemble_component_objects'] as $hook) {
					call_user_func($hook, $each_component, $vce);
				}
			}
			
			// add to results array to return
			$instantiate[] = $this->instantiate_component($each_component, $vce);
		
		}
		
		// return array of components array if more than one component, otherwise just the one component array
		return $instantiate;
	
	}

	/*
	 * loads component file from server and instantiate new object of component type
	 * @param array $component
	 * @param object $vce
	 * @param string $path
	 * @return instantiated objects of component type
	 */
	public function instantiate_component($component, $vce, $path = null) {

		$error = null;
	
		// check that type exists for this component
		$type = isset($component['type']) ? $component['type'] : null;
	
		// check if this component has already been loaded
		if (isset($type)) {
			if (!isset($vce->site->loaded_components[$type])) {
			
				// create loaded_components array if it doesn't exist yet
				if (!isset($vce->site->loaded_components)) {
					$vce->site->loaded_components = array();
				}

				// check that component has not been disabled
				$activated_components = json_decode($vce->site->activated_components, true);

				if (isset($activated_components[$type])) {
		
					if (file_exists(BASEPATH .  $activated_components[$type])) {
	
						// require our component file
						require_once(BASEPATH . $activated_components[$type]);
				
						// add the compoennt to the list
						$vce->site->loaded_components[$type] = true;
				
					} else {
		
						// component has not been installed or it was deleted without nestort knowing
						$error = $type . ' component cannot be found on this server.';
		
					}
	
				} else {
					
					// if a path has been provided, then require_once that, otherwise 
					if (!empty($path)) {
					
						if (file_exists(BASEPATH .  $path)) {
					
							// require our component file
							require_once(BASEPATH .  $path);
										
							// add the compoennt to the list
							$vce->site->loaded_components[$type] = true;
						
						} else {
						
							$error = $type . ' component cannot be found on this server.';
						
						}
					
					} else {
	
						// check that component has not been disabled
						$installed_components = json_decode($vce->site->installed_components, true);
	
						if (isset($installed_components[$type]) && file_exists(BASEPATH .  $installed_components[$type])) {
	
							$error = $type . ' component has not been activated, but is installed.';
			
						} elseif ($type != 'Component') {
		
							$error = $type . ' component cannot be found on this server.';

						}
					
					}
	
				}
	
			}
			
		} else {

			$error = 'type is missing from component meta data.<br><pre>' . print_r($component,true) . '</pre>';				
		}
		
		if (!empty($error)) {
			// exit and display error message
			$type = 'Component';
			$vce->add_errors($error, $vce);
		}

		// return a new instance of the component
		$instantiated_component = new $type($component, $vce);
		 
		// add to results array to return
		return $instantiated_component;
	
	}

	
	/**
	 * Gets parents of a component_id
	 * @param int $component_id
	 * @return object 
	 */
	public function get_parents($component_id, $include_recipes = false) {
	
		global $vce;
		
		// annonymous function for database calls
		$retrieve_component = function($component_id) use ($vce) {
		
			// get homepage
			$query = "SELECT * FROM  " . TABLE_PREFIX . "components as a INNER JOIN " . TABLE_PREFIX . "components_meta as b ON a.component_id = b.component_id WHERE a.component_id = '" . $component_id . "'";
			$requested_component_data = $vce->db->get_data_object($query, false);
			
			if (empty($requested_component_data)) {
				return false;
			}
			
			$each_component = $this->assemble_component_objects($requested_component_data, $vce);
		
			return $each_component[0];
		
		};
	
		// annonymous function to get parents
		$retrieve_parents = function ($component_id, $parent_id = null, $components = array()) use (&$retrieve_parents, $retrieve_component) {

			// if no parent id provided, start our search
			if (!isset($parent_id)) {

				$each_component = $retrieve_component($component_id);
				
				if (!empty($each_component)) {
					// add this component to the start of the components array
					array_unshift($components, $each_component);
				} else {
					// return $components if $each_component is empty, which means the component_id does not exist
					return $components;
				}
				
				// get our parent_id for the first time through
				$parent_id = $each_component->parent_id;
				
			}
			
			$each_component = $retrieve_component($parent_id);
			
			if (!empty($each_component)) {
				// add this component to the start of the components array
				array_unshift($components, $each_component);
			}
			
			if (isset($each_component->parent_id) && $each_component->parent_id != 0) {
				// recursive call to get next depth componetn
				return $retrieve_parents($component_id, $each_component->parent_id, $components);
			} else {
				// we have reached the end
				return $components;
			}
			
		};
		
		// retrieve parents
		$parents = $retrieve_parents($component_id);
		
		// if $include_recipes, add recipe and sub_recipe properties onto each parent
		if ($include_recipes) {
		
			$full_recipe = json_decode($parents[0]->recipe, true);
		
			$recipe = $full_recipe['recipe'];
			
			// work up the trunk
			foreach ($parents as $key=>$value) {
			
				$current_recipe = null;
				$sub_recipe = null;
			
				if (isset($each_item['components'])) {
					// looking for more than one item in recipe
					foreach ($recipe as $each_item) {
			
						if ($each_item['type'] == $value->type) {
			
							$current_recipe = $each_item;
							unset($current_recipe['components']);
							$recipe = $sub_recipe = $each_item['components'];
				
						}
			
					}
				}
				
				// add properties to components
				$parents[$key]->recipe = $current_recipe;
				$parents[$key]->sub_recipe = $sub_recipe;
		
			}
		
		}
		
		return $parents;

	}
	
	
	/**
	 * Gets requested component from id
	 */
	public function get_requested_component($component_id) {
			
		global $vce;
			
		// fetch requested component by component_id
		$query = "SELECT * FROM  " . TABLE_PREFIX . "components INNER JOIN " . TABLE_PREFIX . "components_meta ON " . TABLE_PREFIX . "components.component_id = " . TABLE_PREFIX . "components_meta.component_id WHERE " . TABLE_PREFIX . "components.component_id='" . $component_id. "'";
		$requested_component_data = $vce->db->get_data_object($query, false);
					
		return $this->assemble_component_objects($requested_component_data, $vce)[0];

	}
	
	/**
	 * Get a requested component tree, based on the component_id, with no parent information, and that includes a recipe for the full tree, and no individual component or sub_component recipe information 
	 * This is meant to be an object that can be converted to a json_object for storage or sharing
	 *
	 * If you need a component structure to dynamically build within a page load, do not use this method, use get_children()
	 *
	 * @param int $component_id
	 * @param array $arrest components of type from getting related sub_components
	 * @param array $exclude components of type from being added to build
	 * @param boolean $include_recipe
	 * @return object hierarchical component structure
	 */
	public function get_requested_component_tree($component_id, $arrest = array(), $exclude = array(), $include_recipe = false) {
	
		global $vce;
		
		$parent_components = $this->get_parents($component_id);
		
		$full_recipe = json_decode($parent_components[0]->recipe, true)['recipe'];
		
		// foreach ($parent_components as $each_component) {
		for ($x=0; $x <= (count($parent_components) - 1);$x++) {
			
			// find where we are at in the recipe 
			foreach ($full_recipe as $key=>$value) {
			
				if ($value['type'] == $parent_components[$x]->type) {
				
					$sub_recipe = $full_recipe[$key];
					$full_recipe = $full_recipe[$key]['components'];

					break;
				
				}
			
			}

		}
		
		$component = $parent_components[($x -1)];
		
		if ($include_recipe) {
			if (!isset($component->recipe)) {
				$component->recipe = json_encode(array('recipe' => array($sub_recipe), "recipe_name" => $component->type));
			}
		} else {
			unset($component->recipe);
		}
		
		// $options => array('full_object' = false, 'orphan' = true, 'exclude' => component->type, 'arrest' => 'component->type)
	
		$options = array(
			'orphan' => true,
			'full_object' => true
		);
		
		if (!empty($arrest)) {
			$options['arrest'] = $arrest;
		}
		
		if (!empty($exclude)) {
			$options['exclude'] = $exclude;
		}

		// $geneology is set to false to prevent parent being added to components
		$component->components = self::get_sub_components($vce, array(), $component->component_id, array(), array(), array(), array(), $options);
		
		return $component;
		
	}

	/**
	 * Clones an entire component tree and saves to the db.
	 * 
	 * @param int parent_id the starting parent id of the new components
	 * @param object component_tree the component to be cloned
	 * @param string url_path {parent_url}/{timestamp}/{user_id}/{parent_id} or {} to try using the same component urls provided
	 * @param boolean include_recipe
	 * @return component the cloned component id
	 */
	public function clone_component($parent_id, $component_tree, $url_path = null, $assign_ownership = false, $include_recipe = false) {
		
		global $vce;
		
		// check that the supplied component_tree is in fact an object
		if (!is_object($component_tree)) {
			$vce->add_errors('$vce->page->clone_component() method was sent a non-object as $component_tree' , $vce);
			return false;
		}
		
		$url_formula = array();
		
		if (!empty($url_path)) {
			// search for the first parent_url within parents 
			if (strpos($url_path, '{parent_url}') !== false) {
				
				$parent_url = null;
				// get our array of parents
				$parents = $this->get_parents($parent_id);
				// work backwards through list
				for ($x = (count($parents) -1); $x > -1; $x--) {
					// found one
					if (isset($parents[$x]->url)) {
						// set and break out of this for loop
						$parent_url = $parents[$x]->url;
						break;
					}
				}
				
				// set the value in in url formula
				$url_formula['parent_url'] = $parent_url;
				
			}
			
			// attempt to use the same component urls that have been provided.
			// this is good if you are trying to clone a component tree into a new site
			if ($url_path == '{}') {
				if (isset($component_tree->url)) {
					// make sure that the url does not exist on the site
					$url_path = $vce->site->url_checker($component_tree->url);
				}
			}
			
			// set other values in url formula
			$url_formula['parent_id'] = $parent_id;
			$url_formula['timestamp'] = time();
			$url_formula['user_id'] = $vce->user->user_id;
			$url_formula['map'] = explode('/', $url_path);
		}
		

		$do_clone_component = function ($parent_id, $component_tree) use (&$do_clone_component, $url_formula, $assign_ownership, $vce) {

			// We change the component url to make it unique by adding an extra timestamp.
			$data = array(
				'parent_id' => $parent_id,
				'sequence' => (isset($component_tree->sequence) ? $component_tree->sequence : 0)
			);
			
			// default empty value
			$data['url'] = '';
			
			if (isset($component_tree->url)) {
				// the new url
				$new_url = array();
				// cycle
				foreach (explode('/', $component_tree->url) as $key=>$value) {
					// match in the map
					if (isset($url_formula['map'][$key])) {
						// look for something to replace
						preg_match('/{(.*)}/', $url_formula['map'][$key], $matches);
						// if there is a match
						if (isset($matches[1])) {
							// $vce->dump($url_formula[$matches[1]],'ccc');
							$new_url[] = $url_formula[$matches[1]];
						} else {
							$new_url[] = $url_formula['map'][$key];
						}
					} else {
						// url chunk the same as the was within teh component tree, but with one expection
						if (!isset($url_formula['map'][0])) {
							$new_url[] = $url_formula['map'][0] = $value . '-' . $vce->user->user_id;
						} else {
							// same as what was supplied in the component tree
							$new_url[] = $value;
						}
					}
				}
				$data['url'] = implode('/', $new_url);
			}

			// insert into components table, which returns new component id
			$component_id = $vce->db->insert('components', $data);
			
			// update parent_id in url formula
			if (isset($url_formula['parent_id'])) {
				$url_formula['parent_id'] = $component_id;
			}

			// now add meta data
			$records = array();
			
			if ($assign_ownership) {
				$component_tree->created_at = time();
				$component_tree->created_by = $vce->user->user_id;
			}
			
			$alias = false;

			// loop through other meta data
			foreach ($component_tree as $key => $value) {
			
				if (empty($key) || (empty($value) && $value != 0) || is_array($value)) {
					continue;
				}
				
				// trigger alias creation
				if ($key == 'alias_id') {
					$alias = true;
				}

				if (!in_array($key, ['component_id', 'parent_id', 'sequence', 'url', 'components', 'configuration']) && !is_array($value)) {
					// check that this is a json object
					$json_check = json_decode($value);
					if (json_last_error() === JSON_ERROR_NONE) {
						$sanitized_value = $value;
					} else {
						$sanitized_value = $vce->db->sanitize($value);
					}
					$records[] = array(
						'component_id' => $component_id,
						'meta_key' => $key,
						'meta_value' => $sanitized_value,
						'minutia' => null
					);
				}
			}
			
			// alias sanitization
			if ($alias) {
			
				foreach ($records as $key=>$value) {
			
					// set type to Alias
					if ($value['meta_key'] == 'type') {
						$records[$key]['meta_value'] = 'Alias';
					}
			
					// clean-up any non-alias values
					if (!in_array($value['meta_key'], ['component_id', 'parent_id', 'url', 'type', 'title','created_by','created_at','alias_id'])) {
						unset($records[$key]);
					}
				
				}

			}

			$vce->db->insert('components_meta', $records);

			// clone children
			if (isset($component_tree->components)) {
				foreach ($component_tree->components as $child) {
					$do_clone_component($component_id, $child);
				}
			}

			return $component_id;
		};
		
		
		if ($include_recipe === false) {
			unset($component_tree->recipe);
		}

		return $do_clone_component($parent_id, $component_tree);
	}

	/**
	 * Gets children of a parent_id
	 * @param int $parent_id
	 * @return call self::get_sub_components()
	 */
	public function get_children($current_id, $parent = array(), $components = array(), $sub_components = array(), $sub_url = array(), $options = array()) {
		
		global $vce;
		
		// check that a value has been provided for $current_id that is a number
		if (!is_numeric($current_id) || empty($current_id)) {
			return;
		}
		
		// get parents
		$get_parents = $this->get_parents($current_id);
		
		// get recipe
		$recipe_tree = json_decode($get_parents[0]->recipe, true)['recipe'];
		
		// walk forward to find current recipe location
		foreach ($get_parents as $parent_key=>$parent_component) {
		

			foreach ($recipe_tree as $each_recipe_key=>$each_recipe_item) {
			
				if ($each_recipe_item['type'] == $parent_component->type && isset($each_recipe_item['components'])) {

					if (!isset($parent_component->recipe_key)) {

						$recipe_tree = $each_recipe_item['components'];
				
						break;


					} else {
					
						if ($each_recipe_key == $parent_component->recipe_key) {
			
							$recipe_tree = $each_recipe_item['components'];
				
							break;
						
						}
			
					}
				
				}
			
			}
			
			if ($parent_key > 0) {
				$parent_component->parent = $geneolgoy;
			}
			
			$geneolgoy = $parent_component;
			
		}
		
		$parent = !empty($get_parents) ? $get_parents[(count($get_parents) - 1)] : array(); 
		
		return self::get_sub_components($vce, $recipe_tree, $current_id, $parent, $components, $sub_components, $sub_url, $options);
	}
	
	
	/**
	 * Display Components
	 * This method allows for a component object to be sent to build_content
	 * Note: if you want to have recipe_components / add_component work for components sent to this method, you need to send along a requested_id with the recipe
	 * $requested_id should be the component_id from where you want the sub_recipe to allow add_content.
	 * The recipe should include the parent component of the requested_id
	 * Note: To display content in a layout, you should output to content beforehand: $vce->content->add('main', $content);
	 *
	 * @param int $components
	 * @return true
	 */
	public function display_components($components, $recipe = null, $requested_id = null, $linked = false, $instantiated_recipe_items = array()) {
		
		global $vce;

		// $components needs to be an array
		$components_array = !is_array($components) ? array($components) : $components;
		
		// create an empty value to pass into build_content
		if (count($instantiated_recipe_items) == 0) {
			$instantiated_recipe_items[] = null;
		}
		
		// call to build_content
		$this->build_content($vce, $components_array, $recipe, $requested_id, $linked, $instantiated_recipe_items);

		return;
	}
	
	
	/**
	 * Gets url that is associated with component, which is the first one encountered working backwards through parents
	 * @global object $db
	 * @global object $site
	 * @param int $component_id
	 */
	public function find_url($component_id) {
	
		global $vce;
	
		// get current_id
		$query = "SELECT * FROM  " . TABLE_PREFIX . "components WHERE component_id='" . $component_id . "'";
		$requested_component = $vce->db->get_data_object($query);
		
		if (isset($requested_component[0]->url) && strlen($requested_component[0]->url) > 0) {
			return $vce->site->site_url . '/' . $requested_component[0]->url;
	
		}
		
		// if there is a parent_id and that it's not equal to 0
		if (isset($requested_component[0]->parent_id) && $requested_component[0]->parent_id != 0) {
			// recursive call back to parent component searching for a url
			return self::find_url($requested_component[0]->parent_id);
		}
		
	}
	
	/**
	 * Checks to see if user can add a component to the page
	 * @param object $each_recipe_component
	 * @return bool
	 */
	public function can_add($each_component) {	
		global $vce;
		if (isset($each_component)) {
			return $each_component->can_add($vce);
		} else {
			return false;
		}
	}
	
	/**
	 * NOTE: can_edit and can_delete are moving to class.component.php.
	 */

	/**
	 * Checks to see if user can edit a component to the page
	 * @param object $each_component
	 * @return bool
	 */
	public function can_edit($each_component) {
		global $vce;
		if (isset($each_component)) {
			return $each_component->can_edit($vce);
		} else {
			return false;
		}
	}


	/**
	 * Checks to see if user can delete a component to the page
	 * @param object $each_component
	 * @return bool
	 */
	public function can_delete($each_component) {
		global $vce;
		return $each_component->can_delete($vce);	
	}
	
	/**
	 * Create encrypted dossier and return it as a string
	 *
	 * @param object $dossier_elements
	 * @return string
	 */
	public function generate_dossier($dossier_elements_input) {
	
		global $vce;
		
		// cast to array
		$dossier_elements = (array) $dossier_elements_input;
	
		// clean-up nulls and any empty array
		foreach ($dossier_elements as $dossier_name=>$dossier_value) {
			if (is_null($dossier_value) || (is_array($dossier_value) && empty($dossier_value))) {
				unset($dossier_elements[$dossier_name]);
			}
		}
		
		// encrypt dossier with session_vector for user
		return $vce->user->encryption(json_encode($dossier_elements),$vce->user->session_vector);
	
	}
	
	/**
	 * A method to check component specific permissions
	 *
	 * @param string $permission_name
	 * @param string $component_name
	 * @return bool
	 */
	public function check_permissions($permission_name, $component_name = null) {
		global $vce;
		// find the calling class by using debug_backtrace
		if (!$component_name) {
			$backtrace = debug_backtrace(false, 2);
			$component_name = $backtrace[1]['class'];
		}
		// add permissions onto the component name
		$component_permissions = $component_name . '_permissions';
		if (in_array($permission_name, explode(',', $vce->user->$component_permissions))) {
			return true;
		}
		return false;
	}

	/**
	 * ~sort an object or array by associative key
	 *
	 * @param object/array $data
	 * @param string $key
	 * @param string $order
	 * @param string $type
	 * @return object/array
	 */
	public static function sorter($data, $key='title', $order='asc', $type='string') {
	
		usort($data, function($a, $b) use ($key, $order, $type) {
			// check if this is an object or an array
			$a_sort = is_object($a) ? $a->$key : $a[$key];
			$b_sort = is_object($b) ? $b->$key : $b[$key];
			if (isset($a_sort) && isset($b_sort)) {
				// sort as string
				if ($type == 'string') {
					if ($order == "asc") {
						return (strcmp($a_sort, $b_sort) > 0) ? 1 : -1;
					} else {
						return (strcmp($a_sort, $b_sort) > 0) ? -1 : 1;
					}
				} else if ($type == 'time') {
					// sort as time
					if ($order == "asc") {
						return strtotime($a_sort) > strtotime($b_sort) ? 1 : -1;
					} else {
						return strtotime($a_sort) > strtotime($b_sort) ? -1 : 1;
					}
				} else {
					return 1;
				}
			} else {
				return 1;
			}
		});
		// return the sorted object/array
		return $data;
	}

	/**
	 * Allows for calling object properties from template pages in theme and then return or print them.
	 *
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
				echo 'Call to non-existant property ' . '$' . strtolower(get_class()) . '->' . $name . '()'  . ' in ' . debug_backtrace()[0]['file'] . ' on line ' . debug_backtrace()[0]['line'];
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