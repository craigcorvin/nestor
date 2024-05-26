<?php

class Set extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Set',
			'description' => 'allows for sets of users to be selected who can view url and content contained within.',
			'category' => 'url',
			'recipe_fields' => array(
				'title',
				'template',
				'repudiated_url',
				array('role_selection' => 'set_authority'),
				'url_editable' => array(
					'label' => array('message' => 'URL Editable'),
					'type' => 'checkbox',
					'name' => 'url_editable',
					'selected' => isset($recipe['url_editable']) ? $recipe['url_editable'] : null,
					'flags' => array (
					'label_tag_wrap' => 'true'
					),
					'options' => array(
					'label' => 'URL editable', 'value' => 'true'
					)
				),
				'sequence_editable' => array(
					'label' => array('message' => 'Sequence Editable'),
					'type' => 'checkbox',
					'name' => 'sequence_editable',
					'selected' => isset($recipe['sequence_editable']) ? $recipe['sequence_editable'] : null,
					'flags' => array (
					'label_tag_wrap' => 'true'
					),
					'options' => array(
					'label' => 'Allow sequence to be editable', 'value' => 'true'
					)
				)
			)
		);
	}


	/**
	 * check access
	 */
	public function check_access($each_component, $vce) {
	
		if (isset($vce->user->user_id)) {
			// current user_id is in list of user_access or this user created it
			if (in_array($vce->user->user_id,explode('|', trim($each_component->members,'|'))) || $vce->page->can_edit($each_component)) {	
					return true;
			}
		}
		
		return false;
	}


	/**
	 * Show a link to if the user_id is within the user_access, or if user can edit
	 */
	public function as_link($each_component, $vce) {
		
		if (in_array($vce->user->user_id,explode('|', trim($each_component->members,'|'))) || $vce->page->can_edit($each_component)) {
			$vce->content->add('main', '<div class="sets-link"><a href="' . $vce->site->site_url . '/' . $each_component->url . '">' . $each_component->title . '</a></div>'  . PHP_EOL);
		}

	}
	
	
	/**
	 * Edit existing set
	 */
	public function edit_component($each_component, $vce) {
	
		if (!isset($each_component->recipe)) {
			return false;
		}
	
		if ($vce->page->can_edit($each_component)) {

			// the instructions to pass through the form
			$dossier = array(
			'type' => $each_component->type,
			'procedure' => 'update',
			'component_id' => $each_component->component_id,
			'created_at' => $each_component->created_at
			);

			// generate dossier
			$dossier_for_update = $vce->generate_dossier($dossier);

			// add javascript to page
			$vce->site->add_script(dirname(__FILE__) . '/js/script.js', 'jquery-ui, checkurl');
	
			// add stylesheet to page
			$vce->site->add_style(dirname(__FILE__) . '/css/style.css','set-style');
		
			$sequence = isset($each_component->sequence) ? $each_component->sequence : '0';
			$url = isset($each_component->url) ? $each_component->url : '';

			// get the list of current user_access
			$current_user_ids = explode('|', $each_component->user_access);
			$user_ids = $each_component->user_access;

			$accordion = <<<EOF
<form id="update_$each_component->component_id" class="asynchronous-form selector-container" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_update">
EOF;

			$accordion .= $this->form_elements($each_component,$vce);

			$accordion .= <<<EOF
<input type="submit" value="Update">
<button class="link-button cancel-button">Cancel</button>
</form>
EOF;

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

				$accordion .= <<<EOF
<form id="delete_$each_component->component_id" class="delete-form float-right-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="submit" value="Delete">
</form>
EOF;

			}

			$content = $vce->content->accordion('Edit this ' . $each_component->recipe['title'], $accordion);

			$vce->content->add('admin', $content);
		
		}
	
	}


	/**
	 * add a new set
	 */
	public function add_component($recipe_component, $vce) {

		// using 'potence' recipe field to control if component type can be created when accessing the component.
		if (isset($recipe_component->component_id) && !isset($recipe_component->recipe['potence'])) {
			return;
		}

		// create dossier
		$dossier_for_create = $vce->generate_dossier($recipe_component->dossier);
	
		// add javascript to page
		$vce->site->add_script(dirname(__FILE__) . '/js/script.js', 'jquery-ui, checkurl');
	
		// add stylesheet to page
		$vce->site->add_style(dirname(__FILE__) . '/css/style.css','set-style');
		
		$accordion = null;
		
		$accordion .= <<<EOF
<form id="create_sets" class="asynchronous-form selector-container" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_create">
EOF;

		$accordion .= $this->form_elements($recipe_component,$vce);


		$accordion .= <<<EOF
<input type="submit" value="Create">
<button class="link-button cancel-button">Cancel</button>
</form>
EOF;

		$content = $vce->content->accordion('Add a new ' . $recipe_component->title, $accordion);

		$vce->content->add('admin', $content);
		
	}
	
	/**
	 * form
	 */
	public function form_elements($each_component, $vce) {
	
		$form_elements = null;

		$input = array(
		'type' => 'text',
		'name' => 'title',
		'value' => ((isset($each_component->title) && isset($each_component->component_id)) ? $each_component->title : null),
		'data' => array(
		'tag' => 'required'
		)
		);
		
		if (isset($each_component->component_id)) {
			$input['class'] = 'prevent-check-url';
		}
		
		
		$form_elements .= $vce->content->create_input($input,'Name of ' . $each_component->title,'Enter a Title');

		// create dossier for checkurl functionality
		$dossier = array(
		'type' => 'Set',
		'procedure' => 'checkurl'
		);

		// add dossier, which is an encrypted json object of details uses in the form
		$dossier_for_checkurl = $vce->generate_dossier($dossier);
		
		$url_editable = false;
		
		if (isset($each_component->url_editable) || isset($each_component->recipe['url_editable'])) {
			$url_editable = true;
		}

		$input = array(
		'type' => (!empty($url_editable) ? 'text' : 'hidden'),
		'name' => 'url',
		'value' => (!empty($each_component->url) ? $each_component->url : null),
		'data' => array(
		'tag' => 'required',
		'dossier' => $dossier_for_checkurl,
		'parent_url' => isset($each_component->parent_url) ? $each_component->parent_url . '/' : $vce->requested_url . '/',
		'class' => 'check-url'
		)
		);
	
		if (!empty($url_editable)) {
			$form_elements .= $vce->content->create_input($input, 'URL', 'Enter a URL');
		} else {
			$form_elements .= $vce->content->input_element($input);
		}
		
		if (isset($each_component->component_id) &&isset( $each_component->recipe['sequence_editable'])) {
		
		
			$input = array(
			'type' => 'text',
			'name' => 'sequence',
			'value' => (isset($each_component->sequence) ? $each_component->sequence : null),
			'data' => array(
			'tag' => 'required'
			)
			);
		
			$form_elements .= $vce->content->create_input($input,'Order Number','Enter an Order Number');

		}
		
		$members_list = null;
		$members = array();
		
		if (isset($each_component->members)) {
			$members_list = trim($each_component->members, '|');
			$members = $vce->user->find_users(array('user_ids' => $members_list));
		}	

		$form_elements .= <<<EOF
<input class="selected-users" type="hidden" name="user_ids" value="$members_list">
EOF;

		$form_elements .= '<div class="user-selection-type all-users">';

		// search for anyone start
	
		$input = array(
		'type' => 'select',
		'name' => 'selected_user',
		'class' => 'selected-user',
		);
	
		$selected_user = $vce->content->create_input($input,'Results','Select a User');

		// dossier for search
		$dossier = array(
		'type' => 'Set',
		'procedure' => 'search'
		);

		// generate dossier
		$dossier_for_search = $vce->generate_dossier($dossier);

		$input = <<<EOF
<input class="search-input" type="text" name="search" value="" autocomplete="off" action="$vce->input_path" dossier="$dossier_for_search" placeholder="Enter A Name">
<div class="selected-user-container" class="input-padding" style="display:none;">
$selected_user
<input class="select-user-button link-button" type="button" value="Select This User"> <button class="link-button clear-button">Cancel</button>
</div>	
EOF;

		$form_elements .= $vce->content->create_input($input,'Search All Users (3 Character Minimum)','Searching For Someone?');	

		$form_elements .= '</div>';
		
		$form_elements .= <<<EOF
<div class="between-ul ">
<div class="small-arrow"></div>
</div>
EOF;

		$input = <<<EOF
<ul class="group-members">
EOF;

		if (!empty($members)) {	
			foreach ($members as $each_member) {
				if (isset($each_member->first_name)) {
					$name = $each_member->first_name . ' ' . $each_member->last_name;
				} else {
					$name = $each_member->email;
				}
				$input .= '<li class="ui-state-default accepted-members" user_id="' .  $each_member->user_id . '" tabindex="0" aria-grabbed="false" aria-haspopup="true" role="listitem">';
				
				if ($each_member->user_id != $each_component->created_by) {
					$input .= '<span class="remove-current-members" title="remove">x</span>';
				}
				
				$input .= $name . '</li>';
			}
		}

		$input .= <<<EOF
</ul>
EOF;
		$form_elements .= $vce->content->create_input($input, 'Selected Users', null, 'input-padding');

		return $form_elements;
		
	}

	/**
	 * search for a user
	 */
	public function search($input) {

		global $vce;
		
		if (!isset($input['search']) || strlen($input['search']) < 3) {
			// return a response, but without any results
			echo json_encode(array('response' => 'success','results' => null));
			return;
		}
		
		// call to search method in user class
       	$all_users = $vce->user->search($input['search'], array('first_name','last_name','email'));

		if (count($all_users)) {
			echo json_encode(array('response' => 'success','results' => json_encode($all_users)));
			return;
		}
		
		echo json_encode(array('response' => 'success','results' => 'empty'));
		return;
	
	}
	
	/**
	 * Create a new Set
	 */
	public function create($input) {
		
		$input['members'] = '|' . implode('|', array_unique(explode('|', $input['user_ids']))) . '|';
		
		unset($input['users'], $input['user_ids'], $input['search']);
		
		if ($this->create_component($input)) {
	
			echo json_encode(array('response' => 'success','procedure' => 'create','action' => 'reload','message' => 'Created'));
			return;
		
		}
		
		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Error"));
		return;

	}
	
	
	/**
	 * Update Set
	 */
	public function update($input) {
	
		$input['members'] = '|' . implode('|', array_unique(explode('|', $input['user_ids']))) . '|';
		
		unset($input['users'], $input['user_ids'], $input['search']);
	
		if (self::update_component($input)) {
		
			echo json_encode(array('response' => 'success','procedure' => 'update','action' => 'reload','message' => "Updated"));
			return;
		}
		
		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Error"));
		return;
	
	}

}