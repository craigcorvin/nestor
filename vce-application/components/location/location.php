<?php

class Location extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Location',
			'description' => 'Assign a URL to access sub-components associated with this component.',
			'category' => 'url',
			'recipe_fields' => array('auto_create','title',array('url' => 'required'),'template','order_by','order_direction','children_sequencer')

		);
	}

	public function find_sub_components($requested_component, $vce, $components, $sub_components) {

		if (isset($requested_component->sub_recipe)) {

			foreach ($requested_component->sub_recipe as $key=>$value) {
				// check no url has been set within the recipe
				if (!isset($value['url'])) {
				
					// list of recipe attributes to roll forward
					$recipe_attributes = array('order_by', 'order_direction', 'children_sequencer');
					
					foreach ($recipe_attributes as $each_attribute) {
		
						if (isset($requested_component->$each_attribute)) {
			
							$attribute = isset($requested_component->$each_attribute) ? $requested_component->$each_attribute : null;

							// move order values forward to work around access component placement
							$requested_component->sub_recipe[$key][$each_attribute] = $attribute;
		
						}
						
					}
					
				}
				
			}
	
		}
		
		return true;
	}
	
	
	public function as_content($each_component, $vce) {
		if ($each_component->component_id == $vce->requested_id) {
			// set the page title
			$vce->title = $each_component->title;
		}
	}
	
}