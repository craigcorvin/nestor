<?php

class Media extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Media',
			'description' => 'Allows for media to be uploaded and displayed.',
			'category' => 'media'
		);
	}

	/**
	 * add a hook that fires at initiation of site hooks
	 */
	public function preload_component() {
		$content_hook = array (
		'site_hook_initiation' => 'Media::require_once_mediatype'
		);
		return $content_hook;
	}

	/**
	 * loads the MediaType parent class before the children classes are loaded
	 */
	public static function require_once_mediatype($site) {
		// path to mediatype.php
		require_once(dirname(__FILE__) . '/mediatype/mediatype.php');
	}
	
	/**
	 *
	 */
	public function as_content($each_component, $vce) {
	
		// if display_originator is in recipe, show the user that added this media item, similar to how comments are formated
		if (isset($each_component->recipe['display_originator'])) {
		
			// add stylesheet to page
			$vce->site->add_style(dirname(__FILE__) . '/css/display.css','media-display-originator');
	
			$site_url = defined('ASSETS_URL') ? ASSETS_URL : $vce->site->site_url;
	
			if (!empty($each_component->created_by)) {

				// send to function for user meta_data
				$user_info = $vce->user->get_users(array('user_ids' => $each_component->created_by));

				$name = null;

				if (isset($user_info[0]->first_name)) {
					$name .= $user_info[0]->first_name . ' ';
				}
				
				if (isset($user_info[0]->last_name)) {
					$name .= $user_info[0]->last_name;
				}
				
				if (empty($name)) {
					$name = 'Site User ' . $each_component->created_by;
				}

				$user_image = $site_url . '/vce-application/images/user_' . ($each_component->created_by % 5) . '.png';

			} else {
			
				$name = "Anonymous";

				$user_image = $site_url . '/vce-application/images/user_1.png';

			}
        
			$created_at = date("F j, Y, g:i a", $each_component->created_at);
        
			$content = <<<EOF
<div id="media-$each_component->component_id" class="indent-media">
<div class="media-group-container">
<div class="media-row">
<div class="user-image-media"><img src="$user_image"></div>
<div class="media-row-content arrow-box">
<p><span class="user-name-media">$name</span>&nbsp;&nbsp;<span class="media-date">$created_at</span></p>
EOF;

			$vce->content->add('main', $content);

		}
		
		// opening tag for a media item
		// mamedia tag and the attributes that are passed
		// what would be helpful?
		$attributes = array(
			'media_component_id' => $each_component->component_id,
			'media_type' => $each_component->media_type,
			'media_title' => $each_component->title,
			'parent_component_id' => $each_component->parent_id,
			'parent_type' => (isset($each_component->parent->type) ? $each_component->parent->type : null),
			'parent_tile' => (isset($each_component->parent->title) ? $each_component->parent->title : null),
			'parent_url' => (isset($each_component->parent->url) ? $each_component->parent->url : null),
		);
		
		$data = null;
		
		// create a attributes list
		foreach ($attributes as $key=>$value) {
			$data .= ' ' . $key . '="' . $value . '"';
		}
		
		$content = <<<EOF
<mamedia{$data}>
<div class="media-item-container">
EOF;

		// closing div in as_content_finish
		$vce->content->add('main',$content);
		
		if ($vce->page->can_edit($each_component)) {
			$vce->content->add('main','<div class="media-edit-padding"></div>');
		}
	
		$each_type = $each_component->media_type;
		
		$media_players = json_decode($vce->site->enabled_mediatype, true);
		
		// check that player hasn't been disabled
		if (isset($media_players[$each_type])) {
		
			// load media player class by Type
			require_once(BASEPATH . $media_players[$each_type]);
			
			// call to the component class for Type
			$this_component = new $each_type();
			
		} else {
		
			// load parent class as backup
			$this_component = new MediaType();
		
		}
		
		$vce->content->add('main','<div class="media-item">');
		
		// load hooks
		// media_before_display
		if (isset($vce->site->hooks['media_before_display'])) {
			foreach($vce->site->hooks['media_before_display'] as $hook) {
				call_user_func($hook, $each_component, $vce);
			}
		}
		
		// load parent class to prevent errors
		$this_component->display($each_component, $vce);
		
		self::edit_media_component($each_component, $vce);
		
		// load hooks
		// media_after_display
		if (isset($vce->site->hooks['media_after_display'])) {
			foreach($vce->site->hooks['media_after_display'] as $hook) {
				call_user_func($hook, $each_component, $vce);
			}
		}
		
		// closing tag for media
		$vce->content->add('main','</div>');
		
	}
	
	/**
	 *
	 */
	public function edit_component($each_component, $vce) {
	}
	
	/**
	 *
	 */
	public function edit_media_component($each_component, $vce) {

		if ($vce->page->can_edit($each_component)) {
		
			// add javascript to page
			$vce->site->add_script(dirname(__FILE__) . '/js/edit.js');
		
			// add style
			$vce->site->add_style(dirname(__FILE__) . '/css/edit.css', 'media-edit-style');

			// get list of enabled_mediatype
			$media_players = json_decode($vce->site->enabled_mediatype, true);

			if (isset($media_players[$each_component->media_type])) {
	
				// require each
				require_once(BASEPATH . $media_players[$each_component->media_type]);
	
				// inst it
				$this_type = new $each_component->media_type();
			
			} else {
			
				// load parent class to prevent errors
				$this_type = new MediaType();
		
			}
			
			// the instructions to pass through the form
			$dossier = array(
			'type' => 'Media',
			'procedure' => 'update',
			'component_id' => $each_component->component_id,
			'created_at' => $each_component->created_at,
			'media_type' => $each_component->media_type
			);

			// generate dossier
			$each_component->dossier_for_edit = $vce->generate_dossier($dossier);
		
		
			// the instructions to pass through the form
			$dossier = array(
			'type' => 'Media',
			'procedure' => 'delete',
			'component_id' => $each_component->component_id,
			'created_at' => $each_component->created_at,
			'media_type' => $each_component->media_type,
			'parent_url' => $vce->requested_url
			);

			// generate dossier
			$each_component->dossier_for_delete = $vce->generate_dossier($dossier);
			
			if (!empty($each_component->prevent_editing)) {
				return;
			}
			
			// call to edit() in MediaType
			$add_content = $this_type->edit($each_component, $vce);

			// add edit form
			$vce->content->add('main', $add_content);
		
		}
		
	}

	/**
	 * add a closing div
	 */
	public function as_content_finish($each_component, $vce) {
	
		$content = <<<EOF
</div>
</mamedia>
EOF;
	
		$vce->content->add('main', $content);
		
		// if display_originator is in recipe, show the user that added this media item, similar to how comments are formated
		if (isset($each_component->recipe['display_originator'])) {
		
        	$content = <<<EOF
</div>
</div>
</div>
</div>
EOF;

			$vce->content->add('main', $content);
			
		}

	}
	
	/**
	 *
	 */
	public function add_component($recipe_component, $vce) {
	
		// limit can be used to control the number of media items that can be added.
		if (isset($recipe_component->limit)) {		
			if (isset($recipe_component->parent->components)) {
				$media_components = 0;
				// find how many media components have been created
				foreach ($recipe_component->parent->components as $each_component) {
					if ($each_component->type == "Media") {
						$media_components++;
					}
				}
				// we are at the limit so return false
				if ($media_components >= $recipe_component->limit) {
					return false;
				}
			}
		}

		$recipe_component->dossier_for_create = $vce->generate_dossier($recipe_component->dossier);
		
		$content = null;

		// get list of activated media_players
		$media_players = json_decode($vce->site->enabled_mediatype, true);

		if (isset($recipe_component->media_types)) {
		
			// cycle through list of media_types
			foreach(explode('|',$recipe_component->media_types) as $each_type) {
	
				// check that player hasn't been disabled
				if (isset($media_players[$each_type])) {
	
					// require each
					require_once(BASEPATH . $media_players[$each_type]);
	
					// inst it
					$this_type = new $each_type();
		
				} else {
		
					$this_type = new MediaType();
		
				}
			
				// test if the file uploader is needed to add this media type
				if ($this_type->file_upload()) {
			
					// check if the the file uploader has already been loaded
					if (!isset($vce->file_uploader)) {
					
						//$content .= self::add_file_uploader($recipe_component, $vce);
						
						if (isset($vce->site->hooks['media_add_file_uploader'])) {
							foreach($vce->site->hooks['media_add_file_uploader'] as $hook) {
								$content .= call_user_func($hook, $recipe_component, $vce);
							}
						}
						
					}
			
					// a way to pass file extensions to the plupload to limit file selection
					$file_extensions = $this_type->file_extensions();

					// a way to get content type for class and pass it to vce-upload.php
					// https://en.wikipedia.org/wiki/Media_type
					$media_type = $this_type->mime_info();

					// create list for accept attribute of input type file
					$accept = '.' . str_replace(',',',.', $file_extensions['extensions']);
					foreach ($media_type as $mime_type=>$class_name) {
						// if a wildcard is found in mime_type, then use it to extend accept for safari
						if (preg_match('/([a-z]*\/{1})\.\*$/',$mime_type, $match)) {
							foreach (explode(',', $file_extensions['extensions']) as $each_extention) {
								$accept .= ',' . $match[1] . $each_extention;
							}							
						} else {
							$accept .= ',' . $mime_type;
						}
					}
			
					// write a div with atttributes for plupload to use
					$content .=  '<div class="file-extensions" title="' . $file_extensions['title'] . '" extensions="' . $accept . '"></div>';
					
					// add div for each media type
					foreach ($media_type as $type=>$name) {
					
						$content .=  '<div class="media-types" mimetype="' . $type . '" mimename="' . $name . '"></div>';
					
					}
			
				} else {
		
					// add form
					$content .= $this_type->add($recipe_component, $vce);
				}
			
			}
			
		} else {
		
			$content .= "No Media Types Selected";
		
		}
		
		$clickbar_title = isset($recipe_component->description) ? $recipe_component->description : 'Add New ' . $recipe_component->title;

		// load hooks
		if (isset($vce->site->hooks['breadcrumbs_title'])) {
			foreach ($vce->site->hooks['breadcrumbs_title'] as $hook) {
				$clickbar_title = call_user_func($hook, $recipe_component, $vce);
			}
		}

		// add <div class="uploader-container"> for js binder
		// and include the the file-extensions and media-types within this div 
		$clickbar_content = <<<EOF
<div class="uploader-container">
	$content
</div>
EOF;

		$content_full = $vce->content->accordion($clickbar_title, $clickbar_content, false, false, 'media-add-component admin-container add-container ignore-admin-toggle');

		$content_location = isset($recipe_component->content_location) ? $recipe_component->content_location : 'main';
		
		// add to content object
		$vce->content->add($content_location, '<mafileuploader parent_id="' . $recipe_component->parent_id . '">' . $content_full . '</mafileuploader>');
		
		// clear file_uploader
		unset($vce->file_uploader);

	}
	
	
	/**
	 * Create a new Media
	 * these $input fields come from media/js/upload.js
	 * passing through vce-upload.php
	 */
	public function create($input) {
	
		global $vce;
		
		// load hooks
		// media_create_component
		if (isset($vce->site->hooks['media_create_component'])) {
			foreach($vce->site->hooks['media_create_component'] as $hook) {
				$input_returned = call_user_func($hook, $input);
				$input = isset($input_returned) ? $input_returned : $input;
			}
		}
		
		$input['component_id'] = self::create_component($input);
		
		$response = array(
		'component_id' => $input['component_id'],
		'response' => 'success',
		'procedure' => 'create',
		'message' => 'New Component Was Created'
		);
		
		// load hooks
		// media_component_created
		// was media_create_component_after
		if (isset($vce->site->hooks['media_component_created'])) {
			foreach($vce->site->hooks['media_component_created'] as $hook) {
				$response_returned = call_user_func($hook, $input, $response);
				$response = isset($response_returned) ? $response_returned : $response;
			}
		}
		
		echo json_encode($response);
		return;

	}

	/**
	 * update
	 */
	public function update($input) {
	
		global $vce;
		
		// load hooks
		// media_update_component
		if (isset($vce->site->hooks['media_update_component'])) {
			foreach($vce->site->hooks['media_update_component'] as $hook) {
				$input = call_user_func($hook, $input);
			}
		}
		
		if (self::update_component($input)) {
		
			echo json_encode(array('response' => 'success','procedure' => 'update','action' => 'reload','message' => "Updated"));
			return;
		}
		
		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Error"));
		return;
	
	}


	/**
	 * delete 
	 */
	public function delete($input) {
	
		global $vce;
		
		// load hooks
		// media_delete_component
		if (isset($vce->site->hooks['media_delete_component'])) {
			foreach($vce->site->hooks['media_delete_component'] as $hook) {
				$input = call_user_func($hook, $input);
			}
		}

		$parent_url = self::delete_component($input);

		if (isset($parent_url)) {

			echo json_encode(array('response' => 'success','procedure' => 'delete','action' => 'reload','url' => $parent_url, 'message' => "Deleted"));
			return;
		}

		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Error"));
		return;
	
	}
	
	
	public static function notification($component_id = null, $action = null) {

		global $vce;
		
		// get actor name
		$actor = $vce->user->email;
		if (isset($vce->user->first_name) || isset($vce->user->last_name)) {
			$actor = '';
			if (isset($vce->user->first_name)) {
				$actor .= $vce->user->first_name;
			}
			if (isset($vce->user->last_name)) {
				$actor .= ' ' . $vce->user->last_name;
			}
			$actor = trim($actor);
		}
		
		if ($action == 'create') {
		
			$event = array(
				'actor' => $actor,
				'action' => 'create',
				'verb' => 'uploaded',
				'object' => 'a media item'
			);
		
		}
		
		if ($action == 'update') {
		
			$event = array(
				'actor' => $actor,
				'action' => 'update',
				'verb' => 'updated',
				'object' => 'a media item'
			);
		
		}
		
		if ($action == 'delete') {
		
			$event = array(
				'actor' => $actor,
				'action' => 'delete',
				'verb' => 'deleted',
				'object' => 'a media item'
			);
		
		}
		
		/**
		 * The following block of code will always be needed in any notification to trigger the calls to parents of this component
		 **/
		
		// get parents
		$parents = $vce->page->get_parents($component_id);

		// call to notify method on parents in reverse order
		if (!empty($parents) && !empty($event)) {
		
			end($parents)->notification_event = $event;
		
			// work backwards
			for ($x = (count($parents) - 1);$x > -1;$x--) {
				// notify($parents)
				$result = $parents[$x]->notify($parents);
		
				// if boolean and true is returned by one of the parent components, then we end our search
				if (is_bool($result) && $result) {
					// end notify calls
					break;
				}
			}
		}
	
		return true;
	}


	/**
	 * A complex recipe_fields method
	 */
	public function recipe_fields($recipe) {
	
		global $vce;
		
		$elements = null;
		
		$input = array(
		'type' => 'text',
		'name' => 'title',
		'value' => isset($recipe['title']) ? $recipe['title'] : $this->component_info()['name'],
		'data' => array('tag' => 'required')
		);
		
		$elements .= $vce->content->create_input($input,'Title','Enter a Title');
		
		$input = array(
		'type' => 'text',
		'name' => 'description',
		'value' => isset($recipe['description']) ? $recipe['description'] : null,
		);
		
		$elements .= $vce->content->create_input($input,'Clickbar Description');
		
		$input = array(
		'type' => 'text',
		'name' => 'content_location',
		'value' => isset($recipe['content_location']) ? $recipe['content_location'] : null,
		);
		
		$elements .= $vce->content->create_input($input,'Content Location For Layout');
		
		$input = array(
		'type' => 'checkbox',
		'name' => 'display_title',
		'selected' => isset($recipe['display_title']) ? $recipe['display_title'] : null,
		'flags' => array (
		'label_tag_wrap' => 'true'
		),
		'options' => array(
		'label' => 'Display Title', 'value' => 'true'
		)
		);
		
		$elements .= $vce->content->create_input($input,'Display Title');	

		$input = array(
		'type' => 'checkbox',
		'name' => 'display_originator',
		'selected' => isset($recipe['display_originator']) ? $recipe['display_originator'] : null,
		'flags' => array (
		'label_tag_wrap' => 'true'
		),
		'options' => array(
		'label' => 'Display Originator', 'value' => 'true'
		)
		);
		
		$elements .= $vce->content->create_input($input,'Display Originator Information');	
	
		$enabled_mediatype = json_decode($vce->site->enabled_mediatype, true);

		$checkbox = array();
		foreach ($enabled_mediatype as $each_key=>$each_value) {
			$checkbox[] = array('label' => $each_key, 'value' => $each_key);
		}
		
		$input = array(
		'type' => 'checkbox',
		'name' => 'media_types',
		'options' => $checkbox,
		'selected' => isset($recipe['media_types']) ? explode('|', $recipe['media_types']) : null,
		'flags' => array(
		'label_tag_wrap' => true,
		'prevent_keying' => true
		)
		);
		
		$elements .= $vce->content->create_input($input,'Media Types','Must have a Media Type');
		
		// add recipe fields by hook
		if (isset($vce->site->hooks['component_recipe_fields'])) {
			foreach($vce->site->hooks['component_recipe_fields'] as $hook) {
				$new_elements = call_user_func($hook, $recipe, $vce);
				$elements = $new_elements . $elements;
			}
		}
		
		return $elements;
		
	}
	
	/**
	 * add a doc viewer url
	 */
	public function component_configuration() {

		global $vce;

		$config = $this->configuration;

		$input = array(
		'type' => 'text',
		'name' => 'doc_viewer',
		'value' => isset($config['doc_viewer']) ? $config['doc_viewer'] : '',
		);

		return $vce->content->create_input($input, 'Online Document Viewier URL');
	
	}

}