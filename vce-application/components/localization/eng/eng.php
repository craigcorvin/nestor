<?php

class Eng extends LocalizationType {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'English',
			'description' => 'English Language Localization Type',
			'category' => 'localization'
		);
	}
	
	/**
	 * method that contains our lexicon for language type
	 */
	public function lexicon() {
		return array(
			'Create' => 'Create',
			'Created' => 'Created',
			'Cancel' => 'Cancel',
			'Delete' => 'Delete',
			'Error' => 'Error',
			'Language' => 'Language',
			'Select a Language' => 'Select a Language',
		);
	}

}