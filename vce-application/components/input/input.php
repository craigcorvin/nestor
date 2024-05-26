<?php

class Input extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Input',
			'description' => 'Asynchronous form input portal',
			'category' => 'admin',
			'recipe_fields' => false
		);
	}

	/**
	 * things to do when this component is preloaded
	 */
	public function preload_component() {
		
		$content_hook = array (
		'page_requested_url' => 'Input::page_requested_url'
		);

		return $content_hook;

	}

	/**
	 * method for page_requested_url hook
	 */
	public static function page_requested_url($requested_url, $vce) {
		
	    //add javascript
        $vce->site->add_script(dirname(__FILE__) . '/js/script.js','jquery');

		// add the path to input for form action value
		$vce->input_path = defined('INPUT_PATH') ? $vce->site->site_url . '/' . INPUT_PATH : $vce->site->site_url . '/input';

		if ((!defined('INPUT_PATH') && strpos($requested_url, 'input') !== false && strlen($requested_url) == 5) || (defined('INPUT_PATH') && strpos($requested_url, INPUT_PATH) !== false) && strlen($requested_url) == strlen(INPUT_PATH)) {
		
			self::input_handler($requested_url, $vce);
		
		}
		
	}
	
	
	public static function input_handler($requested_url, $vce) {

		// if no dossier is set, forward to homepage
		if (!isset($_POST['dossier'])) {
			header("Location: " . $vce->site->site_url);
			exit();
		}
		
		// if not originating from JS
		// if (!isset($_POST['inputtypes'])) {
		//	$vce->content->add('main','<div class="form-message form-error">You must enable JavaScript in order to use this site. Note: inputtypes is missing from submission.</div>');
		// 	return;
		// }
		
		// decryption of dossier and cast json_decode as an array, mostly to keep the $_POST array concept alive
		// continues through to procedures where $input is worked with as an array
		$dossier = json_decode($vce->user->decryption($_POST['dossier'], $vce->user->session_vector), true);

		// check that component type is a property of $dossier, json object test
		if (!isset($dossier['type']) || !isset($dossier['procedure'])) {
			//echo json_encode(array('response' => 'error','message' => 'Session has expired. <a class="link-button" href="">Reload Page</a>','action' => ''));
			echo json_encode(array('response' => 'warning','message' => 'Your login session has expired. <a class="link-button" href="">Click here to login again</a>'));
			exit();
			
		}
		
		// hook to capture any input attempt
		if (isset($vce->site->hooks['input_handler'])) {
			foreach($vce->site->hooks['input_handler'] as $hook) {
				call_user_func($hook, $dossier, $_POST);
			}
		}

		
		// which component type to send this input data to
		$type = preg_replace("/[^A-Za-z0-9_]+/", '', trim($dossier['type']));

		// list of input types as json object
		// we could use this to sanitize different input types
		// $_POST['inputtypes'];
		// json can be set as the input type when using the asynchronous-form path by adding schema="json" within the input element

		$inputtypes = array();
		if (isset($_POST['inputtypes'])) {
			$inputtypes_decode = json_decode($_POST['inputtypes'],true);
			if (!empty($inputtypes_decode)) {
				foreach ($inputtypes_decode as $each_input) {
					if (isset($each_input['name'])) {
						$inputtypes[$each_input['name']] = $each_input['type'];
					}
				}
			}
		}

		// unset what is not needed and prevent component type and component procedure from being changed
		unset($_POST['type'],$_POST['procedure'], $_POST['dossier'], $_POST['inputtypes']);

		// create array to pass on
		$input = array();

		// add dossier values first
		foreach ($dossier as $key=>$value) {
			$input[$key] = $value;
		}

		// sanitize and rekey
		foreach ($_POST as $key=>$value) {
			// prevent overloading
			if (!empty($input[$key])) {
				continue;
			}
			// select input elements with multiple
			// note: name of input element needs to have a [] at the end ( test[] ) to tell PHP to place contents into array
			if (is_array($value)) {
				$sanitized = array();
				foreach ($value as $each_value) {
					// textarea default, replacement for FILTER_SANITIZE_STRING
					$sanitized[] = str_replace(["'", '"'], ['&#39;', '&#34;'], preg_replace('/\x00|<[^>]*>?/', '', $each_value));
				}
				$input[$key] = $sanitized;
				continue;
			}
			// else everything else
			$value = trim($value);
			if (isset($inputtypes[$key])) {
				if ($inputtypes[$key] == 'json') {
					// make sure that the json object is valid
					// value will be passed as a json object into the procedure
					json_decode($value);
					if (json_last_error() == JSON_ERROR_NONE) {
						$input[$key] = $value;
					} else {
						// json error reporting here
						$input[$key] = 'json object error';
					}
				} elseif ($inputtypes[$key] == 'textarea') {
					// load hooks
					if (isset($vce->site->hooks['input_sanitize_textarea'])) {
						foreach($vce->site->hooks['input_sanitize_textarea'] as $hook) {
							$value = call_user_func($hook, $value);
						}
					} else {
						// textarea default, replacement for FILTER_SANITIZE_STRING
						$value = str_replace(["'", '"'], ['&#39;', '&#34;'], preg_replace('/\x00|<[^>]*>?/', '', $value));
					}
					// remove line returns if input contains html
					if ($value != strip_tags($value)) {
						$value = str_replace(array("\r", "\n"), '', $value);
					}
					// add to input
					$input[$key] = $vce->db->sanitize($value);
				} else {
					// default filtering
					$input[$key] = $vce->db->sanitize($value);	
				}
			} else {
				// this will be updated when manange recipes and menus is updated
				if ($key == 'json') {
					// make sure that the json object is valid
					// value will be passed as a json object into the procedure
					json_decode($value);
					if (json_last_error() == JSON_ERROR_NONE) {
						$input[$key] = $value;
					} else {
						// json error reporting here
						$input[$key] = 'json object error';
					}
				} else {
					// default filtering
					$input[$key] = $vce->db->sanitize($value);
				}
			}
		}

		// load base components class
		require_once(BASEPATH .'vce-application/class.component.php');

		// create array of installed components
		$activated_components = json_decode($vce->site->activated_components, true);
		
		// check that component type exists
		if (isset($activated_components[$type])) {
			
			// require component type class
			// require_once(BASEPATH . $activated_components[$type]);
			
			// initialize component type object
			// $this_component = new $type();
			
			$meta_data = array();
			
			if (!empty($input['component_id'])) {
			
				$query = "SELECT * FROM " . TABLE_PREFIX . "components_meta AS a JOIN " . TABLE_PREFIX . "components AS b ON b.component_id=a.component_id WHERE a.component_id='" . $input['component_id'] . "'"; 
				$component_data = $vce->db->get_data_object($query, false);
		
				foreach ($component_data as $each_data) {
	
					if (!isset($meta_data['component_id'])) {
					
						// create object and add component table data
						$meta_data['component_id'] = $each_data['component_id'];
						$meta_data['parent_id'] = $each_data['parent_id'];
						$meta_data['sequence'] = $each_data['sequence'];

						// found a url so make sub_url = true
						if (!empty($each_data['url'])) {
							$meta_data['url'] = $each_data['url'];
						}

					}

					// create a var from meta_key
					$key = $each_data['meta_key'];

					// add meta_value
					$meta_data[$key] = (($key != 'recipe') ? $vce->db->clean($each_data['meta_value']) : $each_data['meta_value']);
		
					// adding minutia if it exists within database table
					if (!empty($each_data['minutia'])) {
						$key .= "_minutia";
						$meta_data[$key] = $each_data['minutia'];
					}

				}
			
			}
			
			$meta_data['type'] = $type;
			
			// create an instance of the class
			$this_component = $vce->page->instantiate_component($meta_data, $vce);

			// adding vce object as component property
			$this_component->vce = $vce;
			
			// call to procedure method on type class
			$this_component->form_input($input);
			
			exit();
		
		/*
		
		// for now you can call to method on parent like this
		// $each_component->parent->form_input(array('procedure' => 'test'));
		
		// keep this to use after protected is changed to public, and Component->form_input is removed
		
		if (isset($activated_components[$type])) {
			// require component type class
			require_once(BASEPATH . $activated_components[$type]);
			
			// initialize component type object
			$this_component = new $type();
			
			$procedure = $input['procedure'];
			
			// unset component and procedure
			unset($input['procedure']);
			
			if (method_exists($this_component, $procedure)) {
			
				// adding vce object as component property
				$this_component->vce = $vce;
				
				// call to procedure method on type class
				$this_component->$procedure($input);
				
				exit();
				
			} else {
				if (VCE_DEBUG) {
					// error message
					echo json_encode(array('response' => 'error','message' => 'Procedure not found'));
					exit();
				}
			}
			
		*/
		
		} elseif (isset($type)) {
			// component type called out in form input does not exist
			if (VCE_DEBUG) {
				echo json_encode(array('response' => 'error','message' => 'Component not found'));
				exit();
			}
		}

	}


}