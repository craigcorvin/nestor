<?php

class AccessibilityDefaultStyle extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Accessibility Default Style',
			'description' => 'Add CSS and JS to style Content for Accessibility',
			'category' => 'accessibility',
			'recipe_fields' => false
		);
	}

	/**
	 * things to do when this component is preloaded
	 */
	public function preload_component() {

		$content_hook = array(
			'page_construct_object' => 'AccessibilityDefaultStyle::page_construct_object'
		);

		return $content_hook;
	}


	public static function page_construct_object($requested_component, $vce) {

		// add javascript to page
		$vce->site->add_script(dirname(__FILE__) . '/js/accordion.js', 'jquery');
		$vce->site->add_style(dirname(__FILE__) . '/css/accordion.css', 'accessibility-accordion-style');
		$vce->site->add_script(dirname(__FILE__) . '/js/forms.js', 'jquery jquery-ui');
		$vce->site->add_style(dirname(__FILE__) . '/css/forms.css', 'accessibility-forms-style');
		$vce->site->add_script(dirname(__FILE__) . '/js/tooltips.js', 'jquery');
		$vce->site->add_style(dirname(__FILE__) . '/css/tooltips.css', 'accessibility-tooltips-style');

		// legacy
		$vce->site->add_script(dirname(__FILE__) . '/legacy/js/script.js', 'jquery');
		$vce->site->add_style(dirname(__FILE__) . '/legacy/css/style.css', 'legacy-input-style');
	}

}
