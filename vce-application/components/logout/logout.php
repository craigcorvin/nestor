<?php

class Logout extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Logout',
			'description' => 'Component for creating a logout link',
			'category' => 'site',
			'recipe_fields' => array('auto_create','title',array('url' => 'required'))
		);
	}

	/**
	 * calls to logout function and then forwards to site url
	 */
	public function as_content($each_component, $vce) {

		// call to logout function
		$vce->user->logout();
		
		// to front of site
		header('location: ' . $vce->site->site_url);
		
	}
	
}