<?php

class Localization extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Localization',
			'description' => 'Localization',
			'category' => 'localization',
			'recipe_fields' => false
		);
	}

	/**
	 * things to do when this component is preloaded
	 */
	public function preload_component() {
		
		$content_hook = array (
		
		'site_hook_initiation' => 'Localization::require_once_localizationtype',
		'page_requested_url' => 'Localization::add_site_language',
		'managesite_site_settings' => 'Localization::managesite_site_settings',
		'managesite_user_attibutes' => 'Localization::managesite_user_attibutes',
		'user_user_attributes_fields' => 'Localization::user_user_attributes_fields',
		'usersettings_update_settings' => 'Localization::usersettings_update_settings',
		'managemenus_form_elements' => 'Localization::managemenus_form_elements',
		'component_recipe_fields' => 'Localization::component_recipe_fields',
		'content_insert_menu_links' => 'Localization::content_insert_menu_links',
		'add_component_title' => 'Localization::recipe_title',
		'breadcrumbs_title' => 'Localization::recipe_title',
		'translate_config' => 'Localization::translate_config',
		'translate_email_body_input' => 'Localization::translate_email_body_input',
		'translate_email_subject_input' => 'Localization::translate_email_subject_input',
		);

		return $content_hook;

	}


	/**
	 * loads the NotificationsType parent class before the children classes are loaded
	 */
	public static function require_once_localizationtype($vce) {
	
		// path to mediatype.php
		require_once(dirname(__FILE__) . '/localizationtype/localizationtype.php');	

	}

	/**
	 * assign the site language
	 */
	public static function add_site_language($vce) {
	
		global $vce;
		
		$localization = json_decode($vce->site->enabled_localizationtype, true);
		
		$site_language = 'Eng';
		$site_path = dirname(__FILE__) . '/eng/eng.php';
		
		// if set
		if (!empty($vce->site->site_language) && isset($localization[$vce->site->site_language])) {
			
			$site_language = $vce->site->site_language;
			$site_path = BASEPATH . $localization[$site_language];
			
		} else {
		
			// if site_language had not been enabled
			if (!isset($localization[$site_language]) && !empty($localization)) {
				// default to first
				reset($localization);
				$site_language = key($localization);
				$site_path = BASEPATH . $localization[$site_language];
				$vce->site->site_language = $site_language ;
			}
			
		}
		
		$vce->site->l10n = array();
		
		require_once($site_path);
	
		$language = new $site_language();
		
		$vce->site->l10n[$site_language] = $language->lexicon();
		
		// add the user language
		if (!empty($vce->user->language_selected) && $vce->user->language_selected != $site_language) {
			if (isset($localization[$vce->user->language_selected])) {
				
				$site_language = $vce->user->language_selected;
				
				require_once(BASEPATH . $localization[$site_language]);
				
				$language = new $site_language();
				
				$vce->site->l10n[$site_language] = $language->lexicon();
				
				
			}
		}
		
		
		$dossier = array(
		'type' => 'Localization',
		'procedure' => 'localization_selection'
		);

		// generate dossier
		$dossier = $vce->generate_dossier($dossier);
		
		// select menu
		$input = array(
			'type' => 'select',
			'name' => 'language',
			'data' => array(
			'dossier' => $dossier,
			'action' => $vce->input_path,
			'class' => 'lanagauge-options'
			)
		);
		
		ksort($localization);
		
		foreach ($localization as $key=>$value) {
		
			$selected = false;
			
			if (isset($vce->user->language_selected)) {
				if ($vce->user->language_selected == $key) {
					$selected = true;
				}
			} else if (isset($vce->site->site_language)) {
				if ($vce->site->site_language == $key) {
					$selected = true;
				}
			}
			
			
		
			$input['options'][] = array(
				'name' =>  $key,
				'value' => $key,
				'selected' => $selected
			);

		}
		
		$select = $vce->content->form_input($input);
		
		$content = '	
<script>

$(document).ready(function() {

	$(".lanagauge-options").on("change", function(e) {
	
		var dossier = $(this).attr("dossier");
		var action = $(this).attr("action")

		postdata = [];
		postdata.push(
			{name: "dossier", value: dossier},
			{name: "inputtypes", value: "[]"},
			{name: "langauge", value: $(this).val()}
		);
		$.post(action, postdata, function(data) {
			window.location.reload(true);
		}, "json")
		.fail(function(response) {
			console.log("Error: Response was not a json object");
		});

	});

});

</script>
<div class="localization-selection">
	<span class="ocalization-icon">&#127760;</span>
	' . $select['input'] . '
</div>';
		
		$vce->content->language_selection = $content;


	}
	

	public static function managesite_site_settings($vce) {
	
		$localization = json_decode($vce->site->enabled_localizationtype, true);
		
		if (!empty($localization)) {
		
			$input = array(
			'type' => 'select',
			'name' => 'site_language',
			'data' => array(
			'tabindex' => '0'
			)
			);
			
			
			$input['options'][] = array(
			'name' => '',
			'value' => ''
			);
		
			foreach ($localization as $key=>$value) {
		
				require_once(BASEPATH . $value);
			
				$language = new $key();
			
				$component_info = $language->component_info();
			
				$selected = false;
		
				if ($key == $vce->site->site_language) {
					$selected = true;
				}
		
				$input['options'][] = array(
				'name' => $component_info['name'],
				'value' => $key,
				'selected' => $selected
				);
	
			}
			
			return $vce->content->create_input($input,'Site Language','Select a Site Language');
		
		}
	
	}
	
	
	public static function managesite_user_attibutes($user_attribute_value, $vce) {
	
		$localization = json_decode($vce->site->enabled_localizationtype, true);
		
		$input_elements = null;
		
		if (isset($localization)) {
			foreach ($localization as $key=>$value) {
		
				require_once(BASEPATH . $value);
				
				$language = new $key();
			
				$component_info = $language->component_info();
				$name = 'title_' . strtolower($key);
			
				$input = array(
				'type' => 'text',
				'name' => $name,
				'value' => (isset($user_attribute_value[$name]) ? $user_attribute_value[$name] : null),
				);
		
				$input_elements .= $vce->content->create_input($input, $component_info['name'] . ' Attribute Title (Localization)','Enter a ' . $component_info['name'] . ' Title');
		
			}
		}
	
		return $input_elements;
	
	}
	
	
	
	public static function user_user_attributes_fields($user_attributes_value, $vce) {
	
		$localization = json_decode($vce->site->enabled_localizationtype, true);
	
		$language_selected = 'title';
	
		// add the user language
		if (!empty($vce->user->language_selected)) {
			if (isset($localization[$vce->user->language_selected])) {
			
				$language_selected = 'title_' . strtolower($vce->user->language_selected);
								
			}
		} elseif (!empty($vce->site->site_language)) {
		
			$language_selected = 'title_' . strtolower($vce->site->site_language);

		}
		
		$title = !empty($user_attributes_value[$language_selected]) ? $user_attributes_value[$language_selected] : $user_attributes_value['title'];
		
		return $title;
	
	}
	
	
	public static function usersettings_update_settings($vce) {
	
		$localization = json_decode($vce->site->enabled_localizationtype, true);
		
		if (!empty($localization)) {
		
			$input = array(
			'type' => 'select',
			'name' => 'language_selected'
			);
			
			
			$input['options'][] = array(
			'name' => '',
			'value' => ''
			);
		
	
			foreach ($localization as $key=>$value) {
		
				require_once(BASEPATH . $value);
			
				$language = new $key();
			
				$component_info = $language->component_info();
			
				$selected = false;
		
				if ($key == $vce->user->language_selected) {
					$selected = true;
				}
		
				$input['options'][] = array(
				'name' => $component_info['name'],
				'value' => $key,
				'selected' => $selected
				);
	
			}
			return $vce->content->create_input($input, Localization::language('Language'), Localization::language('Select a Language'));
		
		}
	
	}
	
	public static function managemenus_form_elements($each_url, $role_access, $site_roles, $vce) {

		$localization = json_decode($vce->site->enabled_localizationtype, true);
		
		$input_elements = null;
		
		foreach ($localization as $key=>$value) {
		
			require_once(BASEPATH . $value);
				
			$language = new $key();
			
			$component_info = $language->component_info();
			
			$name = 'title_' . strtolower($key);
			
			$input = array(
			'type' => 'text',
			'name' => $name,
			'value' => (isset($each_url->$name) ? $each_url->$name : null),
			);
			$input_elements .= $vce->content->create_input($input, $component_info['name'] . ' Title (Localization)','Enter a ' . $component_info['name'] . ' Title');
		
		}
		
		
		return $input_elements;
		
	}
	
	
	
	public static function component_recipe_fields($recipe, $vce) {
	
		$localization = json_decode($vce->site->enabled_localizationtype, true);
		
		$input_elements = null;
		
		if (!empty($localization)) {
		
			foreach ($localization as $key=>$value) {
		
				require_once(BASEPATH . $value);
				
				$language = new $key();
			
				$component_info = $language->component_info();
			
				$name = 'title_' . strtolower($key);
			
				$input = array(
				'type' => 'text',
				'name' => $name,
				'value' => (isset($recipe[$name]) ? $recipe[$name] : null),
				);
		
				$input_elements .= $vce->content->create_input($input, $component_info['name'] . ' Title (Localization)','Enter a ' . $component_info['name'] . ' Title');
		
			}
		
		}
	
		return $input_elements;
	
	}
	

	public static function content_insert_menu_links($menu_item, $vce) {
	
		$localization = json_decode($vce->site->enabled_localizationtype, true);
	
		// default value
		$menu_item_title = $menu_item['title'];
		
		if (!empty($vce->user->language_selected) && isset($localization[$vce->user->language_selected])) {
		
			$title_language_selected = 'title_' . strtolower($vce->user->language_selected);
		 
		 	if (!empty($menu_item[$title_language_selected])) {
		 	
		 		$menu_item_title = $menu_item[$title_language_selected];
		 	
		 	}
		
		} elseif (isset($vce->site->site_language) && isset($localization[$vce->site->site_language])) {

			$title_language_selected = 'title_' . strtolower($vce->site->site_language);
	 
			if (!empty($menu_item[$title_language_selected])) {
		
				$menu_item_title = $menu_item[$title_language_selected];
		
			}
		
		}
		
		return $menu_item_title;
		
	}
	
	
	public static function recipe_title($each_component, $vce) {
	
		$localization = json_decode($vce->site->enabled_localizationtype, true);
		
		$title = $each_component->title;
		
		if (!empty($vce->user->language_selected) && isset($localization[$vce->user->language_selected])) {
		
			$title_language_selected = 'title_' . strtolower($vce->user->language_selected);
		 
		 	if (!empty($each_component->$title_language_selected)) {
		 	
		 		$title = $each_component->$title_language_selected;
		 	
		 	}
		
		} elseif (isset($vce->site->site_language) && isset($localization[$vce->site->site_language])) {

			$title_language_selected = 'title_' . strtolower($vce->site->site_language);
	 
			if (!empty($each_component->$title_language_selected)) {
		
				$title = $each_component->$title_language_selected;
		
			}
		
		}
		
		return $title;
	
	}
	
	public function localization_selection($input) {
	
		$vce = $this->vce;
		
		// set persistance to false for non-users
		$persistance = $vce->user->role_id != 'x' ? true : false;
		
		$vce->user->add_attributes('language_selected', $input['langauge'], $persistance);
		
		// cookie code
		
		// SameSite value default is Strict
		$same_site = 'Strict';
	
		// check for https within site_url
		if (parse_url($vce->site->site_url, PHP_URL_SCHEME) == "https") {
			$cookie_secure = "; Secure";
			$same_site = 'None';
			// SameSite=None
		} else {
			$cookie_secure = null;
			//  SameSite=Strict
		}
		
		// get url path
		$url_path = parse_url($vce->site->site_url, PHP_URL_PATH);
		// if this has a value, set cookie_path
		if (empty($url_path)) {
			$url_path = '/';
		}

		// get a vector value
		$langauge = $input['langauge'];
		
		$cookie = "_l10n=" . $langauge . "; Path=" . $url_path . $cookie_secure . "; HttpOnly; SameSite=" . $same_site;
	
		// false flag allows for multiples of this header
        header("Set-Cookie: " . $cookie, false);
        
        // to retrieve
        // $langauge = $_COOKIE['_l10n'];
	
		echo json_encode(array('response' => 'success','message' => json_encode($input)));
		return;
	
	}

	// updates the config variable used in email functions, if user language is not english, update the $config file 
	public static function translate_config($config, $vce){
		
		// check if $vce->users isset to bypass error when viewing manage components
		if (isset($vce->user)){
			$new_config = $config;

			// replace $value with the corresponding languages $value obtained from manage_Components translation
			if ($vce->user->language_selected !== 'Eng'){
				foreach($config as $word => $value){
					$lang_word = $word . "_" . strtolower($vce->user->language_selected);
					if (isset($config[$lang_word])){
						$new_config[$word] = $config[$lang_word];
					}
				}
			}
	
			return $new_config;
		}
		else {
			return false;
		}
	}

	// create the text input field to accept translation for the email subject line
	public static function translate_email_subject_input($word, $subject_name, $config, $vce){
	
		$localization = json_decode($vce->site->enabled_localizationtype, true);
	
		$input_elements = null;
		
		foreach ($localization as $key=>$value) {
	
			if (strtolower($key) != 'eng'){

				require_once(BASEPATH . $value);
				
				$language = new $key();
				
				$component_info = $language->component_info();
				
				// create unique name, that ends with language code (e.g. _esp), which can be used later when deciding which translation to use 
				$name = $subject_name . '_' . strtolower($key);

				$input = array(
				'type' => 'text',
				'name' => $name,
				'value' => isset($config[$name]) ? $config[$name] : null,
				);
			
				$input_elements .= $vce->content->create_input($input, $word .  ' (' . $component_info['name'] . ' Localization)');
		

			}
			
		}
	
		return $input_elements;
	}

	// create textarea input field to accept translation for the email body
	public static function translate_email_body_input($email_body, $body_name, $config, $vce){
	
		$localization = json_decode($vce->site->enabled_localizationtype, true);
	
		$input_elements = null;
		
		foreach ($localization as $key=>$value) {
	
			if (strtolower($key) != 'eng'){

				require_once(BASEPATH . $value);
				
				$language = new $key();
				
				$component_info = $language->component_info();
				
				$name = $body_name . '_' . strtolower($key);
				
				$input = array(
					'type' => 'textarea',
					'name' => $name,
					'value' => isset($config[$name]) ? stripcslashes($config[$name]) : null,
					'data' => array(
						'rows' => '10'
						)	
				);
			
				$input_elements .= $vce->content->create_input($input, $body_name .  ' (' . $component_info['name'] . ' Localization)');

			}
			
		}
	
		return $input_elements;
	}

}