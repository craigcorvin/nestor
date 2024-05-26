<?php

class Sort extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Sort',
			'description' => 'Sort sub components by specific meta key values.',
			'category' => 'site',
			'recipe_fields' => array(
			'auto_create',
			'title',
			array('url' => 'required'),
			'sort_by' => array(
				'label' => array('message' => 'Sort By (Meta Key)','error' => 'Enter a meta key to sort by'),
				'type' => 'text',
				'name' => 'sort_by'
			),
			'sort_order' => array(
				'label' => array('message' => 'Sort Order','error' => 'Select a Number'),
				'type' => 'select',
				'name' => 'sort_order',
				'options' => array(array('name' => 'Asc', 'value' => 'asc'),array('name' => 'Desc', 'value' => 'desc')),
				'data' => array('tag' => 'required')
			)
			)
		);
	}

	/**
	 * things to do when this component is preloaded
	 */
	public function preload_component() {
		
		$content_hook = array (
		'page_get_sub_components' => 'Sort::sort_sub_components'
		);

		return $content_hook;

	}


	/**
	 * sort assignments by open_data
	 */
	public static function sort_sub_components($requested_components,$sub_components,$vce) {
	
		foreach ($sub_components as $component_key=>$component_info) {
		
			if ($component_info->type == "Sort") {
			
				foreach ($requested_components as $requested_key=>$requested_info) {
					
					if ($requested_info->parent_id == $component_info->component_id) {
					
						$meta_key = $component_info->sort_by;
						$order = $component_info->sort_order;
						
						usort($requested_components, function($a, $b) use ($meta_key, $order) {
							if (isset($a->$meta_key) && isset($b->$meta_key)) {
								if ($order == "desc") {
									return $a->$meta_key > $b->$meta_key ? -1 : 1;
								} else {
									return $a->$meta_key > $b->$meta_key ? 1 : -1;
								}
							} else {
								return 1;
							}
						});
							
						return $requested_components;
							
					}

				}
		
			}
		
		}
	
		return $requested_components;
		
	}

}