<?php

class Breadcrumbs extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Breadcrumbs',
			'description' => 'A breadcrumb trail for navigation.',
			'category' => 'site'
		);
	}

	/**
	 * things to do when this component is preloaded
	 */
	public function preload_component() {
		
		$content_hook = array (
		'page_build_content' => 'Breadcrumbs::add_breadcrumbs'
		);

		return $content_hook;

	}

	/**
	 * add breadcrumbs
	 */
	public static function add_breadcrumbs($each_component, $linked) {
	
		global $vce;
		
		// guarantee that there will always be a value for breadcrumbs
		if (!isset($vce->content->breadcrumb)) {
			$vce->content->breadcrumb = '<a href="' . $vce->site->site_url . '" class="breadcrumb-item breadcrumb-item-home"></a>';
		}
		
		if (isset($each_component->url) && !empty($each_component->url) && $linked === false) {
			$vce->content->breadcrumb .= '<a href="' . $vce->site->site_url . '/' . $each_component->url . '" class="breadcrumb-item">';
			if (isset($each_component->title)) {
				$vce->content->breadcrumb .= $each_component->title;
			} else {
				$vce->content->breadcrumb .= $each_component->url;
			}
			$vce->content->breadcrumb .= '</a>';
		}
		
	}

	
	/**
	 * hide this component from being added to a recipe
	 */
	public function recipe_fields($recipe) {
		return false;
	}

}