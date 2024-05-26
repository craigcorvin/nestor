<?php

class Selector extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			// Personal Learning Goal Setting
			'name' => 'Selector',
			'description' => 'Selector displays clickable blocks to create sub-recipe components',
			'category' => 'site',
			'recipe_fields' => array('auto_create','title')
		);
	}
	
	public function included_sub_components($vce, $requested_components, $recipe_items, $components, $sub_components) {
		
		foreach ($requested_components as $each_component) {
		
			$title = isset($each_component->title) ? $each_component->title : get_class($this);
			$class_name = strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/',"-$1", get_class($this))) . '-link';
			$class = 'link-container ' . $class_name . ' anchor-tag-' . $each_component->sequence;
			$vce->content->add('main','<div class="' . $class . '"><a href="' . $vce->site->site_url . '/' . $each_component->url . '" title="' . $title . '">' . $title . '</a></div>'  . PHP_EOL);
		
		}
		
		return $requested_components;
	}

	public function allow_sub_components($each_component, $vce) {
	
		// prevent this from being called when inside a sub component of Selector
		if (end($vce->page->components)->parent->type != 'Selector') {

			if (isset($each_component->sub_recipe) && !isset($each_component->selector_added)) {
		
				$content = null;
			
				// Set storage to capture content fragment, and have it return the current key
				$current_storage_key = $vce->content->set_storage("fragment");
	
				foreach ($each_component->sub_recipe as $each_sub_recipe) {

					$sub_component = $vce->page->instantiate_component($each_sub_recipe, $vce);
				
					$dossier = array(
					'type' => $each_sub_recipe['type'],
					'procedure' => 'create',
					'parent_id' => $each_component->component_id,
					'parent_url' => $each_component->url
					);
				
					$sub_component->dossier = $dossier;

					$sub_component->add_component($sub_component, $vce);

				}
			
				$content .= $vce->content->output(array('admin', 'premain', 'main', 'postmain'), true);
			
				// Reset the storage to the previous key, most likely the default bucket.
				$vce->content->current_storage_key = $current_storage_key;
			
				$foo = $vce->content->accordion($each_component->title, $content);
			
				$vce->content->add('main', $foo);
			
				// flag to make sure this only happens once
				$each_component->selector_added = true;
		
			}

		}
	
		return false;
	}


}




