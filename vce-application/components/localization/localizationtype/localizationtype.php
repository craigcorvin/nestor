<?php

class LocalizationType {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Localization Type',
			'description' => 'Base class for all localization types',
			'category' => 'localization',
			'typename' => null
		);
	}

	/**
	 * component has been installed
	 */
	public function installed() {
	}

	/**
	 * component has been activated
	 */
	public function activated() {
	}
	
	/**
	 * component has been disabled
	 */
	public function disabled() {
	}
	
	/**
	 * component has been removed, as in deleted
	 */
	public function removed() {
	}
	
	/**
	 * this will hopefully prevent an error when a component has been disabled
	 */
	public function preload_component() {
		return false;
	}
	
	/**
	 * hide configuration for component
	 */
	public function component_configuration() {
		return false;
	}
	
	/**
	 * method that contains our lexicon for language type
	 */
	public function lexicon() {
		return array();
	}
	
	/**
	 * hide from ManageRecipe
	 */
	public function recipe_fields($recipe) {
		return false;
	}

}