<?php

class Alias extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Alias',
			'description' => 'Alias of another component',
			'category' => 'site',
			'recipe_fields' => false
		);
	}
	
	
	/**
	 * add hook - this has been disabled
	 */
	public function disabled_preload_component() {
		
		$content_hook = array (
		'delete_extirpate_component' => 'Alias::delete_extirpate_component'
		);

		return $content_hook;

	}
	
	/**
	 * delete anything that has an alias_id associated with the component
	 */
	public static function delete_extirpate_component($component_id, $components) {
	
		global $vce;
	
		// find all aliases of this component
		$query = "SELECT component_id FROM " . TABLE_PREFIX . "components_meta WHERE meta_key='alias_id' and meta_value='" . $component_id . "'";
		$alias_components = $vce->db->get_data_object($query);
		
		foreach ($alias_components as $key=>$value) {
		
			$query = "SELECT * FROM " . TABLE_PREFIX . "components WHERE component_id='" . $value->component_id. "'";
			$additional_components = $vce->db->get_data_object($query);
		
			// add to sub component list
			$components[] = $additional_components[0];
		
		}
		
		return $components;
		
	}

	
	public function include_component($requested_component, $vce, $components, $sub_components) {
		
		// check that an alias_id has been set.
		if (!isset($requested_component->alias_id)) {
		
			$requested_component->error_message = !empty($this->configuration['error_message_content']) ? $this->configuration['error_message_content'] : "Alias Error: An alias component (" . $requested_component->component_id . ") does not contain an alias_id";
		
		} else {
		
			// get alias components meta data
			$query = "SELECT * FROM  " . TABLE_PREFIX . "components_meta WHERE component_id='" . $requested_component->alias_id . "' ORDER BY meta_key";
			$component_meta = $vce->db->get_data_object($query, false);
		
			if (empty($component_meta)) {
			
				// error if no associted component was found
				$requested_component->error_message = !empty($this->configuration['error_message_content']) ? $this->configuration['error_message_content'] :  "Alias Error: This component points to another that no longer exists";		
			
			} else {
		
				foreach ($component_meta as $meta_data) {
				
					// create a var from meta_key
					$key = $meta_data['meta_key'];
		
					// prevent specific meta_data from overwriting
					// ,'created_by'
					if (in_array($key, array('created_at','title'))) {
					   continue;
					}

					// add meta_value
					$requested_component->$key = $vce->db->clean($meta_data['meta_value']);

					//adding minutia if it exists within database table
					if (!empty($meta_data['minutia'])) {
						$key .= "_minutia";
						$requested_component->$key = $meta_data['minutia'];
					}
			
				}

			}
		
		}
		
		return true;
	}
	
	
	public function as_content($each_component, $vce) {
	
		// if we make it here, then the alias_id component cannot be found
		
		// if there is an error, display message and a delete button
		if (isset($each_component->error_message)) {
			
			$content = '<div class="form-message form-error">' . $each_component->error_message . '&nbsp;&nbsp;';

			if ($vce->page->can_delete($each_component)) {

				// the instructions to pass through the form
				$dossier = array(
				'type' => $each_component->type,
				'procedure' => 'delete',
				'component_id' => $each_component->component_id,
				'created_at' => $each_component->created_at
				);

				// generate dossier
				$dossier_for_delete = $vce->generate_dossier($dossier);

				$content .= <<<EOF
<form id="delete_$each_component->component_id" class="delete-form inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="submit" value="Delete">
</form>
EOF;
			
			}
			
			$content .=  '</div>';

			$vce->content->add('main',$content);
			
		}
	
	}

	/**
	 * custom create component
	 */
	public function create($input) {
	
		$vce = $this->vce;
	
		// load hooks
		// alias_create_component
		if (isset($vce->site->hooks['alias_create_component'])) {
			foreach($vce->site->hooks['alias_create_component'] as $hook) {
				$input_returned = call_user_func($hook, $input);
				$input = isset($input_returned) ? $input_returned : $input;
			}
		}
	
		// call to create_component, which returns the newly created component_id
		$component_id = $this->create_component($input);

		if ($component_id) {
		
			$input['component_id'] = $component_id;
			
			$response = array(
			'response' => 'success',
			'procedure' => 'create',
			'message' => 'New Component Was Created'
			);

			// load hooks
			// alias_component_created
			if (isset($vce->site->hooks['alias_component_created'])) {
				foreach($vce->site->hooks['alias_component_created'] as $hook) {
					$response_returned = call_user_func($hook, $input, $response);
					$response = isset($response_returned) ? $response_returned : $response;
				}
			}
			
			$vce->site->add_attributes('message','Alias Created');
	
			echo json_encode($response);
			return;
		
		}
		
		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Error"));
		return;

	}
	
	
	/**
	 * add config info for this component
	 */
	public function component_configuration() {
	
		global $vce;
		
		$input = array(
		'type' => 'text',
		'name' => 'error_message_content',
		'value' => isset($this->configuration['error_message_content']) ? $this->configuration['error_message_content'] : '',
		);

		return $vce->content->create_input($input, 'Error Message Content');
	
	}

}