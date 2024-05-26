<?php

class Access extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Access',
			'description' => 'Access and creation restriction by user role for sub-components. By default only the user that created sub-content can edit and delete it.',
			'category' => 'site',
			'recipe_fields' => array('auto_create','title','template','repudiated_url','role_access','content_create','content_edit','content_delete')
		);
	}	

	
	/**
	 * check if get_sub_components should be called.
	 * @return bool
	 */
	public function find_sub_components($requested_component, $vce, $components, $sub_components) {
	
		// if user role is in role_access, return tue
		if (isset($requested_component->recipe['role_access']) && in_array($vce->user->role_id,explode('|', $requested_component->recipe['role_access']))) {
			return true;
		}
		
		return false;
	}

	/**
	 *
	 */
	public function check_access($each_component, $vce) {
	
		if (isset($vce->user->role_id)) {
		
			// check if user_id is in role_access
			
			if (isset($each_component->recipe['role_access']) && !in_array($vce->user->role_id,explode('|', $each_component->recipe['role_access']))) {
			
				return false;
				
			}
		
		} else {
		// no user role.
			if (count(explode('|', $each_component->recipe['role_access']))) {
				return false;
			}
		}
		
		return true;
	}

	/**
	 * 
	 */
	public function as_content($each_component, $vce) {
	
		if ($each_component->component_id == $vce->requested_id) {
	
			$vce->title = $each_component->title;
	
		}
	}

}