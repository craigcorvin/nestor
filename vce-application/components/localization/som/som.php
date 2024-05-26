<?php

class Som extends LocalizationType {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Af-Soomaali',
			'description' => 'Somali Language Localization Type',
			'category' => 'localization'
		);
	}
	
	/**
	 * method that contains our lexicon for language type
	 */
	public function lexicon() {
		return array(
			'Add' => 'Kudar',
			'Cancel' => 'Baji',
			'Delete' => 'Tirtir',
			'Create' => 'Sameey',
			'Created' => 'La sameyay',
			'Error' => 'Qalad',
			'Title' => 'Cinwaan',
			// 'Add' => 'Ku dar',
			'Update' => 'Cusboonaysii',
			'Clear' => 'Tirtir',
			'Edit' => 'Diyaari',
		);
	}

}