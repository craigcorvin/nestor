<?php

/**
* Components (the basic building blocks of VCE).
* This is the parent class which is extended by all components.
*/

class Component {

	/**
	 * Site class in __construct() at around 148 - preload hooks with no meta_data
	 * Page class in instantiate_component() at around 1761 - should have meta_data
	 */
	public function __construct($attributes = array(), $vce = null) {

		if (!empty($attributes)) {
			foreach ($attributes as $key=>$value) {
				// prevent empty key error
				if (!empty($key)) {
					$this->$key = $value;
				}
			}
		}
		
		// add configuration values if they exist
		$configuration = $this->get_component_configuration();
		if (!empty($configuration) && empty($this->configuration)) {
			$this->configuration = $configuration;
		}
		
	}

	/**
	 * Basic info about the component.
	 */
	public function component_info() {
		return array(
			'name' => ltrim(preg_replace('/[A-Z]/', ' $0', get_class($this))),
			'description' => '&nbsp;',
			'category' => 'component'
		);
	}

	/**
	 * Method to call when Component has been installed.
	 */
	public function installed() {
	}

	/**
	 * Component has been activated.
	 */
	public function activated() {
	}
	
	/**
	 * Component has been disabled.
	 */
	public function disabled() {
	}
	
	/**
	 * Component has been removed, as in deleted.
	 */
	public function removed() {
	}

	/**
	 * This method can be used to route a url path to a specific component method. 
	 *
	 * $path_routing = array(
	 * 	'*path*' => array('*component_class_name*','*component_method_name*')
	 * );
	 * return $path_routing;
	 *
	 * @return bool
	 */
	public function path_routing() {
		return false;
	}

	/**
	 * This method can be used to access application hooks.
	 *
	 * $content_hook = array(
	 * 	'*vce_hook_name*' => '*component_class_name*::*component_method_name*'
	 * );
	 * return $content_hook;
	 *
	 * You can also control the order in which hook events are fired off by using a priority value. A lower or negative priority value goes first, with positive numbers after.
	 *
	 * $content_hook = array(
	 * 	'*vce_hook_name*' => ['function' => '*component_class_name*::*component_method_name*', 'priority' => -100]
	 * );
	 * return $content_hook;
	 *
	 * @return bool
	 */
	public function preload_component() {
		return false;
	}
	
	/**
	 * each parent component that is part of the trunk of the page object
	 * called from get_components
	 * @param object $each_parent_component
	 * @param object $vce
	 * @param object $components
	 * @param object $sub_components
	 */
	public function trunk_component($each_parent_component,$vce, $components) {
	}
	
	/**
	 * check if get_sub_components method should be called.
	 * this occures before components structure is added to the page object
	 * and is checked in both get_components and get_sub_components, which is why both variables are available
	 * @param object $requested_component
	 * @param object $vce
	 * @param object $components
	 * @param object $sub_components
	 * @return bool
	 */
	public function find_sub_components($requested_component, $vce, $components, $sub_components) {
		return true;
	}
	
	/**
	 * provide an opportunity for a parent compoent to modify included sub_recipes
	 * this occures before components structure is added to the page object
	 * and is checked in both get_components and get_sub_components
	 * @param object $vce
	 * @param array $recipe_tree
	 * @return array $recipe_tree
	 */
	public function included_sub_recipe($vce, $recipe_tree) {
		return $recipe_tree;
	}
	
	/**
	 * provides an opportunity for a parent component to modifiy included sub_components
	 * this occures within class.page.php in get_sub_components()
	 * @param object $vce
	 * @param object $requested_components
	 * @param object $recipe_items
	 * @param object $components
	 * @param object $sub_components
	 * @return object $requested_components
	 */
	public function included_sub_components($vce, $requested_components, $recipe_items, $components, $sub_components) {
		return $requested_components;
	}
	
	/**
	 * check if component should be added to the page object during the get process
	 * this occures within class.page.php in get_sub_components()
	 * @param object $requested_component
	 * @param object $vce
	 * @param object $components
	 * @param object $sub_components
	 * @return bool
	 */
	public function include_component($requested_component, $vce, $components, $sub_components) {
		return true;
	}
	
	/**
	 * call to sub component listed within the recipe during the get process
	 * this occures within class.page.php in get_sub_components()
	 * @param object $requested_component
	 * @param object $vce
	 * @param object $components
	 * @param object $sub_components
	 * @return array
	 */
	public function recipe_item_reference($each_component, $vce, $components, $sub_components, $requested_component_data) {
		return $requested_component_data;
	}

	/**
	 * Checks to see if this component should be displayed.
	 * This is fired within class.page.php in build_content()
	 * also, if you would like to add something to the $page object that can be used
	 * by sub_components, this would be where to add that sort of thing $vce->something = "like this".
	 * @param object $each_component
	 * @param object $page
	 * @return bool
	 */
	public function check_access($each_component, $vce) {
		return true;
	}
	
	/**
	 * Checks to see if user can add a component to the page
	 * @param object $vce
	 * @return bool
	 */
	public function can_add($vce) {
	
		// user is a site admin
		if ($vce->user->role_id == "1") {
			return true;
		}
	
		// user role_id is contained within content_create
		if (isset($this->content_create)) {
			if (in_array($vce->user->role_id,explode('|',$this->content_create))) {
				return true;
			}
		} else {
			// content_create not set, so allow add for any user, including public
			return true;
		}
		
		// in recipe, user role_id is contained within content_create, but this time it's contained within recipe
		if (isset($this->recipe['content_create'])) {
			if (in_array($vce->user->role_id,explode('|',$this->recipe['content_create']))) {
				return true;
			}
		}
		
		return false;
	
	}
	

	/**
	 * Checks to see if user can edit a component to the page
	 * @param object $vce
	 * @return bool
	 */
	public function can_edit($vce) {
		
		// user is a site admin
		if ($vce->user->role_id == "1") {
			return true;
		}

		// prevent_editing is set in component
		if (isset($this->prevent_editing) && $this->prevent_editing === true) {
			return false;
		}

		// user created this component
		if (isset($this->created_by) && $this->created_by == $vce->user->user_id) {
			return true;
		}
		
		// in recipe info
		if (isset($this->recipe['content_edit'])) {
			if (in_array($vce->user->role_id, explode('|', $this->recipe['content_edit']))) {
				return true;
			}
		}
		
		// in component meta_data
		if (isset($this->content_edit)) {
			if (in_array($vce->user->role_id, explode('|', $this->content_edit))) {
				return true;
			}
		}
		
		// legacy 
		// in recipe, content_edit = roles and user->role_id is in content_create
		if (isset($this->recipe['content_edit']) && isset($this->recipe['content_create'])) {
			// legacey
			if ($this->recipe['content_edit'] == "roles" && in_array($vce->user->role_id, explode('|', $this->recipe['content_create']))) {
				return true;
			}
		}

		// legacy
		// in component meta_data, content_edit = roles and user->role_id is in content_create
		if (isset($this->content_edit) && isset($this->content_create)) {
			if ($this->content_edit == "roles" && in_array($vce->user->role_id, explode('|', $this->content_create))) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks to see if user can delete a component to the page
	 * @param object $vce
	 * @return bool
	 */
	public function can_delete($vce) {
		
		// user is a site admin
		if ($vce->user->role_id == "1") {
			return true;
		}

		// if prevent_delete is true, then return false
		if (isset($this->prevent_delete)) {
			// if roles have not be specified, then no one can delete, except for admins
			if (!isset($this->prevent_delete_roles)) {
				return false;
				// specific roles
			} else if (in_array($vce->user->role_id, explode('|', $this->prevent_delete_roles))) {
				return false;
			}
		}

		// user created this component
		if (isset($this->created_by) && $this->created_by == $vce->user->user_id) {
			return true;
		}
		

		// in recipe info
		if (isset($this->recipe['content_delete'])) {
			if (in_array($vce->user->role_id, explode('|', $this->recipe['content_delete']))) {
				return true;
			}
		}
		
		// in component meta_data
		if (isset($this->content_delete)) {
			if (in_array($vce->user->role_id, explode('|', $this->content_delete))) {
				return true;
			}
		}		

		// legacy 
		// in recipe, content_edit = roles and user->role_id is in content_create
		if (isset($this->recipe['content_delete']) && isset($this->recipe['content_create'])) {
			if ($this->recipe['content_delete'] == "roles" && in_array($vce->user->role_id, explode('|', $this->recipe['content_create']))) {
				return true;
			}
		}

		// legacy 
		// in component meta_data, content_edit = roles and user->role_id is in content_create
		if (isset($this->content_delete) && isset($this->content_create)) {
			if ($this->content_delete == "roles" && in_array($vce->user->role_id, explode('|', $this->content_create))) {
				return true;
			}
		}

		return false;
	}

	/**
	 * check if page object should build sub_components for this component.
	 * @param object $each_component
	 * @param object $page
	 * @return bool
	 */
	public function build_sub_components($each_component, $vce) {
		return true;
	}

	/**
	 * Checks that sub_components listed in receipe are allowed to be created.
	 * @param object $each_component
	 * @param object $page
	 * @return bool
	 */
	public function allow_sub_components($each_component, $vce) {
		return true;
	}

	/**
	 * Generates links for this component that has a url. The previous component with a URL was the requested id, so generate links for this component
	 * The Last component was the requested id, and this creates the links for it. By default this is a simple html link.
	 * @param object $each_component
	 * @param object $page
	 * @return adds to content variable
	 */
	public function as_link($each_component, $vce) {
		$title = isset($each_component->title) ? $each_component->title : get_class($this);
		$class_name = strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/',"-$1", get_class($this))) . '-link';
		$class = 'link-container ' . $class_name . ' anchor-tag-' . $each_component->sequence;
		$vce->content->add('main','<div class="' . $class . '"><a href="' . $vce->site->site_url . '/' . $each_component->url . '" title="' . $title . '">' . $title . '</a></div>'  . PHP_EOL);
	}

	/**
	 * Defines the content section of the component
	 * @param object $each_component
	 * @param object $page
	 */
	public function as_content($each_component, $vce) {
	}
	
	/**
	 * Book end of as_content.
	 * @param object $each_component
	 * @param object $page
	 */
	public function as_content_finish($each_component, $vce) {
	}
	
	/**
	 * allows for a user to create component that is next in a recipe
	 * @param object $each_component
	 * @param object $page
	 */
	public function add_component($each_recipe_component, $vce) {
	}
	
	/**
	 * Book end of add_component
	 * @param object $each_component
	 * @param object $page
	 */
	public function add_component_finish($each_component, $vce) {
	}
	
	/**
	 * Allows for content to be displayed when component is contained within the recipe, regardless if a component was created.
	 * This is a ghostly apparation of a sub recipe item.
	 */
	public function recipe_manifestation($each_recipe_component, $vce) {
	}
	
	/**
	 * Closes the content to be displayed when component is contained within the recipe, regardless if a component was created.
	 * This is a ghostly apparation bookend for recipe_manifestation and occures after sub_component item
	 */
	public function recipe_manifestation_finish($each_recipe_component, $vce) {
	}

	/**
	 * method called from class.page.php which adds dossiers for edit and delete,
	 * then passes updated objects to edit_component
	 * this has been added for consistancy, so that it matches how recipe_component behaves when it passes the dossier.
	 * @param object $each_component
	 * @param object $page
	 */
	public function revise_component($each_component, $vce, $this_component) {
	
		// dossier used to edit component
		$dossier_to_edit = array(
		'type' => $each_component->type,
		'procedure' => 'update',
		'component_id' => $each_component->component_id,
		'created_at' => $each_component->created_at
		);
		
		// add dossier to component object
		$each_component->dossier_to_edit = $dossier_to_edit;
		
		// dossier used to delete component
		$dossier_to_delete = array(
		'type' => $each_component->type,
		'procedure' => 'delete',
		'component_id' => $each_component->component_id,
		'created_at' => $each_component->created_at
		);
	
		// add dossier to component object
		$each_component->dossier_to_delete = $dossier_to_delete;
		
		// call to edit_components with updated component object
		$this_component->edit_component($each_component, $vce);
	}

	/**
	 * called from revise_comonent.
	 * @param object $each_component
	 * @param object $page
	 */
	public function edit_component($each_component, $vce) {
	}


	/**
	 * Get configuration fields for component and add to $vce object
	 * @return 
	 */
    public function get_component_configuration($class = null) {
    
        global $vce;
        
        $component_name = isset($class) ? $class : get_class($this);

        if (isset($vce->site->$component_name)) {
        
			$value = $vce->site->$component_name;
			$vector = $vce->site->{$component_name . '_minutia'};
			return json_decode($vce->site->decryption($value, $vector), true);
			
        }
        
        return false;
        
    }

	
	/**
	 * Configuration fields for a component used in ManageComponents
	 * @param object $configuration
	 * @return false (as a default)
	 */
	public function component_configuration() {
		return false;
	}
	
	/**
	 * check_permissions
	 * @return 
	 */
    public function check_permissions($permission_name = null) {
    
       	global $vce;
       	
       	return $vce->check_permissions($permission_name, get_class($this));
        
    }

	/**
	 * Adds component fields used in ManageRecipes.
	 * @param object $recipe
	 * @return false (will prevent a component from being available to add to a recipe)
	 */
	public function recipe_fields($recipe) {
	
		if (empty($recipe)) {
			return;
		}
	
		$component_class = $recipe['type'];
		if (!class_exists($component_class)) {
			return "Class $component_class does not exist";
		}

		global $vce;
		
		$component_info = $component_class::component_info();
		
		// a false value will hide the component within ManageRecipes
		if (!isset($component_info['recipe_fields']) || ($component_info['recipe_fields'] === false)) {
			return false;
		}
		
		if (!is_array($component_info['recipe_fields'])) {
			die('Recipe Fields Error');
		}
	
		$elements = null;
		
		// templates array
		$template_names[] = array('name' => '','value' => '');
	
		foreach ($vce->site->get_template_names() as $key=>$value) {
			// create array of templates
			$template_names[] = array('name' => $key,'value' => $value);
		}
		
		// site roles
		$roles = json_decode($vce->site->site_roles, true);

		$roles[][0] = array(
		'role_name' => 'Public',
		'role_id' => 'x'
		);
		
		foreach ($roles as $each_role) {
			
			$role = array_values($each_role)[0];
			
			$site_roles[] = array(
				'value' => $role['role_id'],
				'label' => $role['role_name']
			);
			
		}
		
		// list of predefined recipe fields
		$predefined = array(
			'title' => array(
				'label' => array('message' => 'Title','error' => 'Enter a Title'),
				'type' => 'text',
				'name' => 'title',
				'value' => isset($recipe['title']) ? $recipe['title'] : $component_info['name'],
				'data' => array('tag' => 'required')
			),
			'description' => array(
				'label' => array('message' => 'Description','error' => 'Enter a Description'),
				'type' => 'textarea',
				'name' => 'description',
				'value' => isset($recipe['description']) ? $recipe['description'] : null,
				'data' => array(
					'rows' => '10',
					'placeholder' => 'description'
				)
			),
			'url' => array(
				'label' => array('message' => 'URL','error' => 'Enter a URL'),
				'type' => 'text',
				'name' => 'url',
				'value' => isset($recipe['url']) ? $recipe['url'] : null
			),
			'repudiated_url' => array(
				'label' => array('message' => 'Access Denied URL','error' => 'Enter a URL'),
				'type' => 'text',
				'name' => 'repudiated_url',
				'value' => isset($recipe['repudiated_url']) ? $recipe['repudiated_url'] : null,
			),
			'template' => array(
				'label' => array('message' => 'Template','error' => 'Enter a Template'),
				'type' => 'select',
				'name' => 'template',
				'value' => isset($recipe['template']) ? $recipe['template'] : null,
				'options' => $template_names
			),
			'potence' => array(
				// using 'potence' recipe field to control if component type allows a component to be created within itself
				//
				// public function add_component($recipe_component, $vce) {
				//
				// if (isset($recipe_component->component_id) && !isset($recipe_component->recipe['potence'])) {
				//		return;
				// }
				'label' => array('message' => 'Add Components'),
				'type' => 'checkbox',
				'name' => 'potence',
				'selected' => isset($recipe['potence']) ? $recipe['potence'] : null,
				'flags' => array (
				'label_tag_wrap' => 'true'
				),
				'options' => array(
				'label' => 'Allow component to be created within itself', 'value' => 'self'
				)
			),
			'role_selection' => array(
				// create a custom role selection
				// array('role_selection' => 'name_of_attribute')
				'label' =>  null,
				'type' => 'checkbox',
				'name' => null,
				'selected' => null,
				'disabled' => 1,
				'options' => $site_roles,
				'data' => array('tag' => 'required'),
				'flags' => array('label_tag_wrap' => true,'prevent_keying' => true)
			),
			'role_access' => array(
				'label' =>  array('message' => 'What Role Can View Content?','error' => 'Must have roles'),
				'type' => 'checkbox',
				'name' => 'role_access',
				'selected' => isset($recipe['role_access']) ? explode('|', $recipe['role_access']) : 1,
				'disabled' => 1,
				'options' => $site_roles,
				'data' => array('tag' => 'required'),
				'flags' => array('label_tag_wrap' => true,'prevent_keying' => true)
			),
			'content_access' => array(
				'label' =>  array('message' => 'What Role Can View Content?','error' => 'Must have roles'),
				'type' => 'checkbox',
				'name' => 'content_access',
				'selected' => isset($recipe['content_access']) ? explode('|', $recipe['content_access']) : 1,
				'disabled' => 1,
				'options' => $site_roles,
				'data' => array('tag' => 'required'),
				'flags' => array('label_tag_wrap' => true,'prevent_keying' => true)
			),
			'content_create' => array(
				'label' => array('message' => 'What Role Can Create Content?','error' => 'Must have roles'),
				'type' => 'checkbox',
				'name' => 'content_create',
				'selected' => isset($recipe['content_create']) ? explode('|', $recipe['content_create']) : 1,
				'disabled' => 1,
				'options' => $site_roles,
				'data' => array('tag' => 'required'),
				'flags' => array('label_tag_wrap' => true,'prevent_keying' => true)
			),
			'content_edit' => array(
				'label' => array('message' => 'In Addition To Creator, What Role Can Edit Content?','error' => 'Who Can Edit Created Content?'),
				'type' => 'checkbox',
				'name' => 'content_edit',
				'selected' => (isset($recipe['content_edit']) && strstr($recipe['content_edit'], '|')) ? explode('|', $recipe['content_edit']) : 1,
				'disabled' => 1,
				'options' => $site_roles,
				'data' => array('tag' => 'required'),
				'flags' => array('label_tag_wrap' => true,'prevent_keying' => true)
			),
			'content_delete' => array(
				'label' => array('message' => 'In Addition To Creator, What Role Can Delete Content?','error' => 'Who Can Delete Created Content?'),
				'type' => 'checkbox',
				'name' => 'content_delete',
				'selected' => (isset($recipe['content_delete']) && strstr($recipe['content_delete'], '|')) ? explode('|', $recipe['content_delete']) : 1,
				'disabled' => 1,
				'options' => $site_roles,
				'data' => array('tag' => 'required'),
				'flags' => array('label_tag_wrap' => true,'prevent_keying' => true)
			),
			'order_by' => array(
				'label' => array('message' => 'Order By (meta_key)'),
				'type' => 'text',
				'name' => 'order_by',
				'value' => isset($recipe['order_by']) ? $recipe['order_by'] : null,
			),
			'order_direction' => array(
				'label' => array('message' => 'Order Direction (ASC or DESC)'),
				'type' => 'text',
				'name' => 'order_direction',
				'value' => isset($recipe['order_direction']) ? $recipe['order_direction'] : null,
			),
			'children_sequencer' => array(
				'label' => array('message' => 'Children Sequencer'),
				'type' => 'select',
				'name' => 'children_sequencer',
				'value' => isset($recipe['children_sequencer']) ? $recipe['children_sequencer'] : null,
				'options' => array(array('name' => '','value' => ''), array('name' => 'Sort by component types','value' => 'sort'), array('name' => 'Flat display of component types','value' => 'flat'))
			),
			// checkbox creates a checkbox for on/off situations
			'checkbox' => array(
				'label' =>  null,
				'type' => 'checkbox',
				'name' => null,
				'selected' => null,
				'data' => array('tag' => 'required'),
				'flags' => array('label_tag_wrap' => true,'prevent_keying' => true)
			)
		);
		
		
		foreach ($component_info['recipe_fields'] as $recipe_field_key=>$recipe_field) {
		
			$recipe_field_value = null;
		
			// if array break into two pieces
			if (is_array($recipe_field)) {
			
				if (is_numeric($recipe_field_key)) {
					// option one , key is number and value is an array, specifically for required and auto_create => backwards
				
					$field_key = key($recipe_field);
					$recipe_field_value = $recipe_field[$field_key];
					$recipe_field = $field_key;
				
				} else {
					// option two , key is the field name and value is input values
				
					$recipe_field_value = $recipe_field;
					$recipe_field = $recipe_field_key;					
				
				}
				
			}
		
			// place auto created as hidden input first
			if ($recipe_field == 'auto_create') {
				// add auto_create as first element
				$elements = '<input type="hidden" name="auto_create" value="' . (isset($recipe_field_value) ? $recipe_field_value : 'forward') . '">' . $elements;
				continue;
			}
			
			// allows for custom recipe fields to be passed through
			if (!isset($predefined[$recipe_field])) {
				
				// make sure this is an array
				if (is_array($component_info['recipe_fields'][$recipe_field_key])) {
				
					if (isset($component_info['recipe_fields'][$recipe_field_key]['name'])) {
					
						// add this custom value to the array of predefined.
						$predefined[$component_info['recipe_fields'][$recipe_field_key]['name']] = $component_info['recipe_fields'][$recipe_field_key];
					
						if (isset($recipe[$recipe_field_key])) {
					
							if ($predefined[$recipe_field]['type'] == 'text') {
								$predefined[$recipe_field]['value'] = $recipe[$recipe_field_key];				
							} else {
								$predefined[$recipe_field]['selected'] = $recipe[$recipe_field_key];
							}

						}
					
					}
									
				}
				
			}
			
			// 
			if (isset($predefined[$recipe_field])) {
			
				if ($recipe_field_value == 'required') {
					$predefined[$recipe_field]['data'] = array('tag' => 'required');
				} else {
					if (empty($predefined[$recipe_field]['label'])) {
						$predefined[$recipe_field]['label'] = array('message' => ucwords(str_replace('_', ' ', $recipe_field_value)),'error' => 'Field Required');
						if (isset($predefined[$recipe_field]['options'])) {
							if (!empty($predefined[$recipe_field]['options']['value']) && empty($predefined[$recipe_field]['options']['label'])) {
								$predefined[$recipe_field]['options']['label'] = ucwords(str_replace('_', ' ', $recipe_field_value));
							}
						}
					}
					if (empty($predefined[$recipe_field]['name'])) {
						$predefined[$recipe_field]['name'] = $recipe_field_value;
					}
					if (empty($predefined[$recipe_field]['selected'])) {
						$predefined[$recipe_field]['selected'] = (!is_array($recipe_field_value) && isset($recipe[$recipe_field_value])) ? explode('|', $recipe[$recipe_field_value]) : 1;
					}
				}
				
				$message = isset($predefined[$recipe_field]['label']['message']) ? $predefined[$recipe_field]['label']['message'] : $recipe_field;
				
				$error = isset($predefined[$recipe_field]['label']['error']) ? $predefined[$recipe_field]['label']['error'] : 'Enter a ' . $recipe_field;
				
				// we are unsetting this as the defualt
				unset($predefined[$recipe_field]['label']);
								
				if (!isset($predefined[$recipe_field]['data']['tag'])) {
					$message .= ' (Optional)';
				}
				
				// special case for 
				if ($recipe_field == 'checkbox') {
					// adding a special label back
					$predefined[$recipe_field]['label'] = ' ' . $message;
					// check the box if the value exists in the recipe
					if (isset($recipe[$recipe_field_value])) {
						$predefined[$recipe_field]['value'] = isset($recipe[$recipe_field_value]) ? $recipe[$recipe_field_value] : null;
					}
				}
				
				$elements .= $vce->content->create_input($predefined[$recipe_field], $message, $error);
			
				continue;
			}
			
		}
		
		// add recipe fields by hook
		if (isset($vce->site->hooks['component_recipe_fields'])) {
			foreach($vce->site->hooks['component_recipe_fields'] as $hook) {
				$new_elements = call_user_func($hook, $recipe, $vce);
				$elements = $new_elements . $elements;
			}
		}
		
		return $elements;
		
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
	 * Creates component
	 * @param array $input
	 * @return calls component's procedure or echos an error message
	 */
	protected function create($input) {
	
		$vce = $this->vce;
	
		// call to create_component, which returns the newly created component_id
		$component_id = $this->create_component($input);
	
		if ($component_id) {
		
			$vce->site->add_attributes('message', $this->component_info()['name'] . ' Created');

			$query = "SELECT url FROM " . TABLE_PREFIX . "components WHERE component_id='" . $component_id . "'";
			$url = $vce->db->get_data_object($query);

			$url = isset($url[0]->url) ? $vce->site_url . '/' . $url[0]->url : null;
	
			echo json_encode(array('response' => 'success','procedure' => 'create','action' => 'reload','url' => $url, 'message' => 'Created','component_id' => $component_id));
			return;
		
		}
		
		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Error"));
		return;

	}
	
	/**
	 * Creates component from $input and also auto_create anything based on the recipe.
	 * This function can be updated later to allow for deeper level auto_create.
	 * @param array $input
	 * @return calls component's procedure or echos an error message
	 */
	public function create_component($input) {
	
		global $vce;

		// add created by and created at time_stamp if they are not found
		// $input['created_by'] = isset($input['created_by']) ? $input['created_by'] : $vce->user->user_id;
		// $input['created_at'] = isset($input['created_at']) ? $input['created_at'] : time();
		
		// legacy way this has been done up until now
		$input['created_by'] = $vce->user->user_id;
		$input['created_at'] = time();
		
		// make sure we have default values
		$input['title'] = isset($input['title']) ? $input['title'] : preg_replace('/[A-Z]/', ' $0', $input['type']);
		$input['parent_id'] = isset($input['parent_id']) ? $input['parent_id'] : null;

		// set $auto_create
		$auto_create = isset($input['auto_create']) ? $input['auto_create'] : null;
		unset($input['auto_create']);
				
		// anonymous function to create components
		$create_component = function($input) use (&$create_component, $vce) {

		 	// local version of $input, which should not be confused with the $input fed to the create_component method
		
			// create_component_before hook
			if (isset($vce->site->hooks['create_component_before'])) {
				foreach($vce->site->hooks['create_component_before'] as $hook) {
					$return = call_user_func($hook, $input);
					if (!empty($return)) {
						$input = $return;
					}
				}
			}
			
			// clean up url
			if (isset($input['url'])) {
				$input['url'] = $vce->site->url_checker($input['url']);
			}
			
			// create component data
			$parent_id = isset($input['parent_id']) ? $input['parent_id'] : null;
			$sequence = isset($input['sequence']) ? $input['sequence'] : 1;
			$url = isset($input['url']) ? stripslashes($input['url']) : '';
			// $current_url = isset($input['current_url']) ? $input['current_url'] : '';
		
			unset($input['parent_id'], $input['sequence'], $input['url'], $input['current_url']);
	
			$data = array(
			'parent_id' => $parent_id, 
			'sequence' => $sequence,
			'url' => $url
			);
		
			// insert into components table, which returns new component id
			$component_id = $vce->db->insert('components', $data);
			
			// set values associated with component access and creation
			// $role_access = isset($input['role_access']) ? $input['role_access'] : null;
			// $content_access = isset($input['content_access']) ? $input['content_access'] : null;
			// $content_create = isset($input['content_create']) ? $input['content_create'] : null;
			// $content_edit = isset($input['content_edit']) ? $input['content_edit'] : null;
			// $content_delete = isset($input['content_delete']) ? $input['content_delete'] : null;
		
			unset($input['role_access'],$input['content_access'],$input['content_create'],$input['content_edit'],$input['content_delete']);

			// now add meta data
			$records = array();

			// loop through other meta data
			foreach ($input as $key=>$value) {
			
				// no empty keys
				if (empty($key)) {
					continue;
				}
				// no empty values
				if (empty($value)) {
					if ($value === false) {
						// normalize false to 0 
						$value = 0;
					}
					if ($value != '0') {
						continue;
					}
				}

				// title
				$records[] = array(
				'component_id' => $component_id,
				'meta_key' => $key, 
				'meta_value' => $value,
				'minutia' => null
				);
		
			}

			$vce->db->insert('components_meta', $records);
			
			return $component_id;
			
		};
	
		// anonymous function to create auto_create components
		$auto_create_components = function($auto_create, $input, $direction) use (&$auto_create_components, $vce, $create_component) {

			if (!empty($auto_create)) {
				// set counter
				$counter = 0;
				foreach ($auto_create as $each_key=>$each_component) {
					
					if (!isset($each_component['auto_create'])) {
						continue;
					}
				
					if (isset($each_component['components'])) {
						$sub_auto_create = $each_component['components'];
					} else {
						$sub_auto_create = null;
					}
		
					if ($direction == "reverse" && $each_component['auto_create'] == "reverse") {
					
						// check that the component type that is being created is in the recipe as a sub-component of this reverse auto_create component
						if (!isset($each_component['components'][0]['type']) || ($each_component['components'][0]['type'] != $input['type'] && $input['type'] != 'Alias')) {
							// if not, then return the parent_id that was supplied within the $input array
							return $input['parent_id'];	
						}
					
						// add to counter
						$counter++;
						
						// unset sub components and auto_create 
						unset($auto_create[$each_key]['components'],$auto_create[$each_key]['auto_create']);
						
						$new_component = array();
						
						// update input from recipe
						foreach ($auto_create[$each_key] as $meta_key=>$meta_value) {
							$new_component[$meta_key] = $meta_value;
						}
						
						// create separate sequence space in case
						$new_component['sequence'] = $counter;
						
						// add required fields
						$new_component['parent_id'] = $input['parent_id'];
						$new_component['created_by'] = $input['created_by'];
						$new_component['created_at'] = $input['created_at'];
						
						// call and then return the component_id
						$new_component_id = $create_component($new_component);
									
						// check that component has not been disabled
						$activated_components = json_decode($vce->site->activated_components, true);

						// check that this component has been activated
						if (isset($activated_components[$new_component['type']])) {
							require_once(BASEPATH . $activated_components[$new_component['type']]);
						} else {
							// default to parent class
							$new_component['type'] = 'Component';
						}
		
						// add component_id to new_component
						$new_component['component_id'] = $new_component_id;
		
						//  add auto_create to new_component
						$new_component['auto_create'] = $each_component['auto_create'];
		
						// call to auto_created
						$new_component['type']::auto_created($new_component);
		
						return $new_component_id;
					
					}
					
					if ($direction == "forward" && $each_component['auto_create'] == "forward") {
						
						// add to counter, for use with sequence
						$counter++;
						
						// clear array and start again
						$new_component = array();
						
						// keep track of how many instances of the same component occur at this level, so that a recipe_key can be added if needed
						if (!isset($recipe_type)) {
							// loop through the first time to find multiples
							foreach ($auto_create as $recipe_component) {
								$recipe_type[$recipe_component['type']] = isset($recipe_type[$recipe_component['type']]) ? ($recipe_type[$recipe_component['type']] + 1) : 0;
							}
						}

						// if multipes have been found, add $recipe_key
						if ($recipe_type[$each_component['type']] > 0) {
							if (!isset($recipe_key[$each_component['type']])) {
								$recipe_key[$each_component['type']] = 0;
							} else {
								$recipe_key[$each_component['type']] = $recipe_key[$each_component['type']] + 1;
							}
							// add meta_key to each_sub_components
							$new_component['recipe_key'] = $recipe_key[$each_component['type']];
						}
						
				
						// create separate sequence space in case
						$new_component['sequence'] = $counter;

						// unset sub components and auto_create 
						unset($auto_create[$each_key]['components'],$auto_create[$each_key]['auto_create']);
						
						// update input from recipe
						foreach ($auto_create[$each_key] as $meta_key=>$meta_value) {
							// prevent overwriting
							if (!isset($new_component[$meta_key])) {
								$new_component[$meta_key] = $meta_value;
							}
						}
						
						// add required fields
						$new_component['parent_id'] = $input['parent_id'];
						$new_component['created_by'] = $input['created_by'];
						$new_component['created_at'] = $input['created_at'];
						
						// create a sub url
						if (isset($each_component['url']) && $each_component['url'] != "") {
							if (isset($input['url'])) {
								$url = $input['url'] . '/' . $each_component['url'];
							} else {
								$url = $each_component['url'];													
							}
							// save new extended url
							$new_component['url'] = $url;
						}

						// call and then return the component_id
						$component_id = $create_component($new_component);
						
						// check that component has not been disabled
						$activated_components = json_decode($vce->site->activated_components, true);

						// check that this component has been activated
						if (isset($activated_components[$new_component['type']])) {
							require_once(BASEPATH . $activated_components[$new_component['type']]);
						} else {
							// default to parent class
							$new_component['type'] = 'Component';
						}
		
						// add component_id to new_component
						$new_component['component_id'] = $component_id;
		
						//  add auto_create to new_component
						$new_component['auto_create'] = $each_component['auto_create'];
		
						// call to auto_created
						$new_component['type']::auto_created($new_component);
						
						// recursive call
						if (isset($sub_auto_create)) {
							// create a copy of input to add parent_id and send recersively 
							$new_input = $input;
							// update parent_id with the newly created component_id
							$new_input['parent_id'] = $component_id;
							// make call
							$auto_create_components($sub_auto_create, $new_input, $direction);
						}
					
					}
				}
	
				// if there is an auto_create == reverse and auto_create == forward at the same level as the component.
				if (isset($auto_create[0]['auto_create']) && $auto_create[0]['auto_create'] == "reverse") {

					// update parent_id with the reverse_parent_id value from before
					$input['parent_id'] = $input['reverse_parent_id'];

					// recursive call
					$auto_create_components($sub_auto_create, $input, $direction);

				}
			}
			
			return $input['parent_id'];
		
		};

		// check for auto_create == reverse
		$input['parent_id'] = $auto_create_components($auto_create, $input, "reverse");
		
		// save the parent_id of the reverse auto_create component
		$reverse_parent_id = $input['parent_id'];
		
		// create component
		$input['parent_id'] = $create_component($input);
		$component_id = $input['parent_id'];
		
		// add this value back
		$input['reverse_parent_id'] = $reverse_parent_id;
		
		// check for auto_create == forward
		$auto_create_components($auto_create, $input, "forward");
		
		// prevent any errors that might happen in certain situations
		if (class_exists($input['type'])) {
			// notification on create
			$input['type']::notification($component_id, 'create');
		}
		
		// return the current_id for the newly created component
		return $component_id;

	}

	/**
	 * This function is called after a component has been auto_created
	 * @param array $new_component
	 */
	public static function auto_created($new_component) {
	}
	

	/**
	 * Updates data
	 * @param array $input
	 * @return echo of json object beforehand
	 */
	protected function update($input) {
	
		if (self::update_component($input)) {
		
			global $vce;

			$vce->site->add_attributes('message',$this->component_info()['name'] . " Updated");
			
			// this is here in case the url has been updated
			$query = "SELECT url FROM " . TABLE_PREFIX . "components WHERE component_id='" . $input['component_id'] . "'";
			$url = $vce->db->get_data_object($query);

			// if a value is found provide that
			if (!empty($url[0]->url)) {
				$url = isset($url[0]->url) ? $vce->site_url . '/' . $url[0]->url : null;
			} else {
				// otherwise return null
				$url = null;
			}

			echo json_encode(array('response' => 'success','procedure' => 'update','action' => 'reload','url' => $url,'message' => "Updated"));
			return;
			
		}
		
		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Permission Error. No created_at value was sent for validation."));
		return;
	}

	/**
	 * Updates component
	 * @param array $input
	 * @return boolean // success or failure
	 */
	protected static function update_component($input) {

		global $vce;
		
		// update_component_before hook
		if (isset($site->hooks['update_component_before'])) {
			foreach($site->hooks['update_component_before'] as $hook) {
				$return = call_user_func($hook, $input);
				if (!empty($return)) {
					$input = $return;
				}
			}
		}
		
		$component_id = $input['component_id'];
		unset($input['users'], $input['component_id']);
		
		$query = "SELECT * FROM " . TABLE_PREFIX . "components AS a JOIN " . TABLE_PREFIX . "components_meta AS b ON a.component_id = b.component_id WHERE b.component_id='" . $component_id . "'";
		$components_meta = $vce->db->get_data_object($query);
		
		// for components_meta key => values
		$meta_data = array();	
	
		// key components_meta
		foreach ($components_meta as $each_meta) {
			if (!isset($meta_data['component_id'])) {
				$meta_data['component_id'] = $each_meta->component_id;
				$meta_data['sequence'] = $each_meta->sequence;
				$meta_data['url'] = $each_meta->url;
			}
			$key = $each_meta->meta_key;
			$meta_data[$key] = $each_meta->meta_value;
		}
		
		// check that created_at is the same
		if (isset($input['created_at']) && $meta_data['created_at'] == $input['created_at']) {

			$sequence = isset($input['sequence']) ? $input['sequence'] : $meta_data['sequence'];
			$url = isset($input['url']) ? stripslashes($input['url']) : $meta_data['url'];
			
			// clean-up url
        	$url = trim(strtolower(preg_replace("/[^\w\d\/]+/i", "-", $url)), '-/');
			
			unset($input['sequence'], $input['url']);
			
			$update = array('sequence' => $sequence, 'url' => $url);
			$update_where = array('component_id' => $component_id);
			$vce->db->update('components', $update, $update_where);
			
			// in case of an alias return true
			if (isset($meta_data['alias_id'])) {
				return true;
			}
			
			foreach ($input as $key=>$value) {
			
				// check if meta_data already exists, then update
				if (isset($meta_data[$key])) {
				
					// if a NULL value has been provided, then delete meta_key from databse for this component
					// this allows a method for deleting the meta_key from that database for this component
					if (is_null($value)) {
								
						$where = array('component_id' => $component_id,'meta_key' => $key);
						$vce->db->delete('components_meta', $where);
						
						// proceed to the next
						continue;
					}
				
					// add current to arrays for update
					$updates[] = array('meta_value' => $value);
					$updates_where[] = array('component_id' => $component_id, 'meta_key' => $key);			
					
				} else {
				// meta_data doesn't exists, so create it
				
					if (empty($key) || empty($value)) {
						continue;
					}
				
					// insert is expecting an associative array
					$records[] = array(
					'component_id' => $component_id,
					'meta_key' => $key, 
					'meta_value' => $value,
					'minutia' => null
					);

				}

			}
			
			// if $records exists, insert meta_data
			if (isset($updates) && isset($updates_where)) {
				$vce->db->update('components_meta', $updates, $updates_where);
			}
			
			// if $records exists, insert meta_data
			if (isset($records)) {
				$vce->db->insert('components_meta', $records);
			}
			
			// prevent any errors that might happen in certain situations
			if (class_exists($meta_data['type'])) {
				// notification on update
				$meta_data['type']::notification($component_id, 'update');
			}
			
			return true;
		
		}
		
		return false;

	}

	/**
	 * Deletes data
	 * @param array $input
	 * @return calls component's procedure or echos an error message
	 */
	protected function delete($input) {

		$parent_url = self::delete_component($input);

		if (isset($parent_url)) {
		
			// if a url has been passed, then reload that page
			if (isset($input['parent_url'])) {
				$parent_url	= $input['parent_url'];
			}
		
			// add a message that item has been deleted
			global $vce;
			$vce->site->add_attributes('message', $this->component_info()['name'] . " Deleted");

			echo json_encode(array('response' => 'success','procedure' => 'delete','action' => 'reload','url' => $parent_url, 'message' => "Deleted"));
			return;
		}

		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Error"));
		return;
	
	}
	
	/**
	 * Deletes component.
	 * Logic works this way: a user can delete a component they have created and then all sub components regardless who created them.
	 * @param array $input
	 * @return calls component's procedure or echos an error message
	 */
	protected static function delete_component($input) {
	
		global $vce;
		
		// recursive search for parent_url
		$find_parent_url = function($component_id) use (&$find_parent_url, $vce) {
		
			$query = "SELECT * FROM " . TABLE_PREFIX . "components WHERE component_id IN (SELECT parent_id FROM " . TABLE_PREFIX . "components WHERE component_id='" . $component_id . "')";
			$parent_component = $vce->db->get_data_object($query);
			
			if (isset($parent_component)) {
		
				if (!empty($parent_component[0]->url)) {
					return $parent_component[0]->url;
				}
			
				if (isset($parent_component[0]->parent_id) && $parent_component[0]->parent_id != '0') {
					return $find_parent_url($parent_component[0]->component_id);
				}
			
			}
			
			return false;

		};
		
		// prevent empty, null or zero value for component_id and return error message
		if (!isset($input['component_id']) || $input['component_id'] == "0") {
			echo json_encode(array('response' => 'error','procedure' => 'update','message' => "No component_id"));
			return;
		}
		
		if (!isset($input['parent_url'])) {
			$parent_url = $vce->site->site_url . '/' . $find_parent_url($input['component_id']);
		} else {
			$parent_url = $vce->site->site_url . '/' . $input['parent_url'];
		}
	
		$query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE component_id='" . $input['component_id'] . "'";
		$components_meta = $vce->db->get_data_object($query);
		
		// for components_meta key => values
		$meta_data = array('component_id' => $input['component_id']);	
	
		// key components_meta
		foreach ($components_meta as $each_meta) {
			$key = $each_meta->meta_key;
			$meta_data[$key] = $each_meta->meta_value;
		}
		
		// check that the created_at timestamp for the component matches the input value, which is done as an addtional security check
		if (isset($meta_data['created_at']) && $meta_data['created_at'] == $input['created_at']) {
		
			// delete_extirpate_component
			if (isset($vce->site->hooks['delete_extirpate_component'])) {
				foreach($vce->site->hooks['delete_extirpate_component'] as $hook) {
					$return = call_user_func($hook, $meta_data);
					if (!empty($return)) {
						$meta_data = $return;
					}
				}
			}
		
			// prevent any errors that might happen in certain situations
			if (class_exists($meta_data['type'])) {
				// notification of delete
				$meta_data['type']::notification($meta_data['component_id'], 'delete');
			}

			// call to recursive function to delete components and components_meta data
			self::extirpate_component($input['component_id']);
			
			return $parent_url;
			
		}
		
		return false;

	}
	
	/**
	 * Searches for sub components and deletes them.
	 * This is a recursive function.
	 * @param int $component_id
	 */
	protected static function extirpate_component($component_id) {
	
		// prevent empty, null or zero value for component_id
		if (!$component_id || is_null($component_id) || $component_id == "0") {
			return false;
		}
	
		global $vce;
	
		// check to see if a path to media exists
		$query = "SELECT * FROM " . TABLE_PREFIX . "components AS a JOIN " . TABLE_PREFIX . "components_meta AS b ON a.component_id=b.component_id WHERE a.component_id='" . $component_id . "'";
		$component_meta = $vce->db->get_data_object($query, false);
		
		// rekey component meta_data into component
		foreach ($component_meta as $meta_data) {
		
			if (!isset($component['component_id'])) {
				// create object and add component table data
				$component = array();
				$component['component_id'] = $meta_data['component_id'];
				$component['parent_id'] = $meta_data['parent_id'];
				$component['sequence'] = $meta_data['sequence'];

				// found a url so make sub_url = true
				if (!empty($meta_data['url'])) {
					$component['url'] = $meta_data['url'];
				}
	
			}

			// create a var from meta_key
			$key = $meta_data['meta_key'];

			// add meta_value
			$component[$key] = (($key != 'recipe') ? $vce->db->clean($meta_data['meta_value']) : $meta_data['meta_value']);
			
			// adding minutia if it exists within database table
			if (!empty($meta_data['minutia'])) {
				$key .= "_minutia";
				$component[$key] = $meta_data['minutia'];
			}

		}
		
		// specific Media call to delete media item
		if ($component['type'] == 'Media' && !empty($component['path'])) {
		
			$basepath = defined('INSTANCE_BASEPATH') ? INSTANCE_BASEPATH . PATH_TO_UPLOADS : BASEPATH . PATH_TO_UPLOADS;
		
			// path of file
			$unlink_path = $basepath .  '/' . $component['created_by'] . '/' . $component['path'];
			
			// make sure file exists before deleteing/unlinking it
			if (file_exists($unlink_path)) {
				unlink($unlink_path);
			}
			
			// load hooks
			// media_delete_component
			if (isset($vce->site->hooks['media_delete_component'])) {
				foreach($vce->site->hooks['media_delete_component'] as $hook) {
					$input = call_user_func($hook, $component);
				}
			}
	
		}
		
		$query = "SELECT * FROM " . TABLE_PREFIX . "datalists WHERE component_id='" . $component_id . "'";
		$datalists = $vce->db->get_data_object($query);			
			
		foreach ($datalists as $each_datalist) {
			
			$where = array('component_id' => $component_id);
			$vce->db->delete('datalists', $where);
				
			$where = array('datalist_id' => $each_datalist->datalist_id);
			$vce->db->delete('datalists_meta', $where);
			
			$query = "SELECT * FROM  " . TABLE_PREFIX . "datalists_items WHERE datalist_id='" . $each_datalist->datalist_id . "'";
			$items = $vce->db->get_data_object($query);
				
			$where = array('datalist_id' => $each_datalist->datalist_id);
			$vce->db->delete('datalists_items', $where);
				
			foreach ($items as $each_item) {

				$where = array('item_id' => $each_item->item_id);
				$vce->db->delete('datalists_items_meta', $where);
				
			}
			
		}
	
		// delete component
		$where = array('component_id' => $component_id);
		$vce->db->delete('components', $where);
		
		// delete component meta data
		$where = array('component_id' => $component_id);
		$vce->db->delete('components_meta', $where);
		
		// find all sub components for a given component
		$query = "SELECT * FROM " . TABLE_PREFIX . "components WHERE parent_id='" . $component_id . "'";
		$components = $vce->db->get_data_object($query);
	
		// go through sub components
		foreach ($components as $each_component) {
			
			//recursively call this function to delete sub components
			self::extirpate_component($each_component->component_id);
	
		}
	
	}
	
	/**
	 * return the existing children of a component that is within the trunk of the current page object
	 * @param object $vce
	 * @return object of children components
	 */
	public function get_children($vce) {
	
		$each_component = $this;
		
		$tree_trunk = end($vce->page->components);
		
		if ($each_component->component_id != $tree_trunk->component_id) {
		
			for ($key=0,$rekey=0;$key < (count($vce->page->components)-1);$key++) {
			
				if ($each_component->component_id == $vce->page->components[$key]->component_id) {
				
					$tree_fork[$rekey] = $vce->page->components[$key + 1];
					$rekey++;
					
				} elseif ($rekey > 0) {
				
					$tree_fork[$rekey] = $vce->page->components[$key + 1];
					$rekey++;
					
				}
			
			}
		
		} else {
			
			// if we found a match, then return the children components of the match
			$tree_fork = isset($tree_trunk->components) ? $tree_trunk->components : null;
		
		}
		
		// else return a null value if no match found
		return $tree_fork;
		
	}

	/**
	 * This function is called when something happened, like create or delete
	 * @param array $new_component
	 */
	public static function notification($component_id = null, $action = null) {
		return false;
	}
	
	/**
	 * This function is called to from notification
	 * @param array $parents
	 *
	 * // hook for allowing multiple types of notifications
	 * if (isset($vce->site->hooks['components_notify_method'])) {
	 *  foreach($vce->site->hooks['components_notify_method'] as $hook) {
	 * 	 call_user_func($hook, $proclamation);
	 *  }
	 * }
	 *
	 */
	public function notify($parents = null) {
		return false;
	}

	/**
	 * Checks that url has not already been assigned to another component.
	 * @param array $input
	 */
	protected function checkurl($input) {
	
		global $vce;
		$checked = $vce->site->url_checker($input['url']);
		
		echo json_encode(array('response' => 'success','procedure' => 'checkurl','url' => $checked));
		return;
	
	}

	/**
	 * Language localization method for static situations
	 *
	 * @param string $phrase // if not found this method will return the value provided
	 * @param string $destination // can specify which langauge folder within the component directory to select from
	 * @return string
	 *
	 * $this->lang('*array_key_for_phrase*');
	 */
    public static function language($phrase, $destination = null) {
		
			$class = static::class;

			return self::localization($class, $phrase, $destination = null);
    }
	
	/**
	 * Language localization method for static situations
	 *
	 * @param string $phrase // if not found this method will return the value provided
	 * @param string $destination // can specify which langauge folder within the component directory to select from
	 * @return string
	 *
	 * $this->lang('*array_key_for_phrase*');
	 */
    public function lang($phrase, $destination = null) {
		
		$class = get_class($this);
		
		return self::localization($class, $phrase, $destination = null);

    }
    
	/**
	 * Language localization method
	 * l10n = numeronyn for localization
	 * ISO 639-3 Language Codes are used
	 *
	 * @param string $class
	 * @param string $phrase // if not found this method will return the value provided
	 * @param string $destination // can specify which langauge folder within the component directory to select from
	 * @return string
	 */
   public static function localization($class, $phrase, $destination = null) {
    
    	global $vce;
    	
    	// ISO 639-3 Language Code, defaults to english
    	$site_language = !empty($vce->site->site_language) ? $vce->site->site_language : 'Eng';
    
    	// from user object $vce->user->language = 'so;
    	$user_language = (!empty($vce->user->language_selected) && isset($vce->site->l10n[$vce->user->language_selected])) ? $vce->user->language_selected : $site_language;

    	// set in site class
    	// $vce->site->localization property where localization will be stored
    	if (!isset($vce->site->l10n)) {
    		$vce->site->l10n = array();
		}

		$activated_components = json_decode($vce->site->activated_components, true);
		
		if (isset($activated_components[$class])) {
		
			preg_match('/(.+)\/.+\.php$/', $activated_components[$class], $matches);
			
			if (isset($matches[1])) {
				// path to this class
				$component_path = BASEPATH . $matches[1];
			} else {
				// fall back
				$component_path = dirname(__FILE__);
			}
			
		}
		
    	// have we tried to load this before?
    	if (empty($vce->site->l10n[$class])) {
    	
			// full file path
			$file_path = $component_path . '/lang/' . strtolower($user_language) . '.php';
	
			// load the file into the site propery
			if (file_exists($file_path)) {
		
				// get file contents
				$lexicon = require($file_path);
	
				// add file contents to localization property on site object
				$vce->site->l10n[$class] = $lexicon;

			}
		
    	} else {
    	
    		$lexicon = $vce->site->l10n[$class];
    	
    	}
    	
    	// if destination has been set
    	if (isset($destination) && !isset($vce->site->l10n[$class][$destination])) {

			// full file path
			$file_path = $component_path . '/lang/' . strtolower($destination) . '.php';
	
			// load the file into the site propery
			if (file_exists($file_path)) {
		
				// get file contents
				$destination_lexicon = require($file_path);
	
				// add file contents to localization property on site object
				$lexicon[$destination] = $vce->site->l10n[$class][$destination] = $destination_lexicon;

			}
				
    	}
    	
    	// search within base localization
		if (!isset($lexicon[$phrase])) {
			// search phrase within base lexicon
			if (isset($vce->site->l10n[$user_language][$phrase])) {
				// get from base lexicon
				$lexicon[$phrase] = $vce->site->l10n[$class][$phrase] = $vce->site->l10n[$user_language][$phrase];			
			} else {
				// if the user language is different than the site language
				if ($site_language != $user_language) {
					if (isset($vce->site->l10n[$site_language][$phrase])) {
						// get from base lexicon
						$lexicon[$phrase] = $vce->site->l10n[$class][$phrase] = $vce->site->l10n[$site_language][$phrase];
					}
				}
			}
		}
    	
    	// if there is a localization propery, but the phrase cannot be found, try the site language file
    	if (!isset($lexicon[$phrase])) {
    	
			// search for value in the file associated with the site_language
			$file_path = $component_path . '/lang/' . strtolower($site_language) . '.php';

			// load the file into the site property
			if (file_exists($file_path)) {

				// get file contents
				$old_lexicon = require($file_path);
		
				// merge with existing existing localization property
				$lexicon = !empty($vce->site->l10n[$class]) ? array_merge($old_lexicon, $vce->site->l10n[$class]) : $old_lexicon;

				// add file contents to localization property on site object
				$vce->site->l10n[$class] = $lexicon;

			}
			
			// if we still cannot find a match for the phrase, then pass back the value
			if (!isset($lexicon[$phrase])) {
				$lexicon[$phrase] = $phrase;
				$vce->site->l10n[$class][$phrase] = $phrase;
			}
		
		}
		
		// if destination has been set
		if (isset($destination)) {
			if (isset($lexicon[$destination][$phrase])) {
				$lexicon[$phrase] = $lexicon[$destination][$phrase];
			} else {
				$vce->site->l10n[$class][$destination][$phrase] = $phrase;
				$lexicon[$phrase] = $phrase;
			}
		} 
		
		return $lexicon[$phrase];
    
    }
	
	/**
	 * get component configuration
	 * @param array $vce
	 */
	public static function get_config($vce) {
	
		// convert to this
		// $class = null
	
		$component_name = get_called_class();
		
		if (isset($vce->site->$component_name)) {
			// get component configuration inforamtion from site object
			$value = $vce->site->$component_name;
			$minutia = $component_name . '_minutia';
			$vector = $vce->site->$minutia;
            $config = json_decode($vce->site->decryption($value, $vector), true);
            
            return $config;
		}
		
		return false;
	
	}

	/**
	 * Returns false instead of "Notice: Undefined property error" when reading data from inaccessible properties
	 */
	public function __get($var) {
		return false;
	}
	
}