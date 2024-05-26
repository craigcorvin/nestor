<?php

class NotifierType {

	/**
	 * using the constructor to add configuration and who knows what else
	 */
	public function __construct() {

		// add configuration values if they exist
		$this->configuration = $this->get_component_configuration();

	}

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Notifier Type',
			'description' => 'Base class for all Notifier Type Components',
			'category' => 'Notifier',
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
	 * By default all notifications are setup as opt-out to preserve space within the user_meta table
	 * Are you 'in' or are you 'out'
	 */
	public function opt_method() {
		return 'out';
	}
	
	/**
	 * notify who of what
	 *
	 * @param object $each_user
	 * @param array $each_proclamation
	 *
	 */
	public function notification($each_user, $each_proclamation) {
	}	

	/**
	 * Get configuration fields for component and add to $vce object
	 * @return 
	 */
    public function get_component_configuration() {
    
        global $vce;
        $n = get_class($this);
        
        if (isset($vce->site->{$n})) {
        
			$value = $vce->site->{$n};
			$vector = $vce->site->{$n . '_minutia'};
			return json_decode($vce->site->decryption($value, $vector), true);
			
        }
        
        return false;
        
    }


	/**
	 * hide from ManageRecipe
	 */
	public function recipe_fields($recipe) {
		return false;
	}

}