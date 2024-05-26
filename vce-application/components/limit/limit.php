<?php

class Limit extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Limit',
			'description' => 'Limit the number of sub components that can be created within this component',
			'category' => 'site',
			'recipe_fields' => array('auto_create','title',
			'components_limit' => array(
				'label' => array('message' => 'Sub-Components Limit','error' => 'Select a Number'),
				'type' => 'select',
				'name' => 'components_limit',
				'options' => array('1','2','3','4','5','6','7','8','9','10'),
				'data' => array('tag' => 'required')
			)
			)
		);
	}
	
	/**
	 *  limit
	 */
	public function allow_sub_components($each_component, $vce) {
	
		if (isset($each_component->components) && isset($each_component->components_limit)) {
			if (count($each_component->components) >= $each_component->components_limit) {
				return false;
			}
		}
		
		return true;
	}

}