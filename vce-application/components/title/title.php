<?php

class Title extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Title',
			'description' => 'Add a simple title / description to a place in a recipe',
			'category' => 'site',
			'recipe_fields' => array('title','description')
		);
	}
	
	public function recipe_manifestation($each_recipe_component, $vce) {

		$description = null;
		
		if (isset($each_recipe_component->description)) {
			$description = '<div class="title-component-description">' . nl2br($each_recipe_component->description) . '</div>';
		}
	
		$vce->content->add('main','<p class="title-component"><h1 class="title-component-title">' . $each_recipe_component->title . '</h1><p>' . $description . '</p></p>' );
		
	}

}