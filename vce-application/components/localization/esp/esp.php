<?php

class Esp extends LocalizationType {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Español',
			'description' => 'Spanish Language Localization Type',
			'category' => 'localization'
		);
	}
	
	/**
	 * method that contains our lexicon for language type
	 */
	public function lexicon() {
		return array(
			'Created' => 'Creado',
			'Cancel' => 'Cancelar',
			'Delete' => 'Borrar',
			'Create' => 'Crear',
			'Language' => 'Idioma',
			'Select a Language' => 'Select a Language',
		);
	}

}