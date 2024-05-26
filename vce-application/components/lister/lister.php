<?php

class Lister extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Lister',
			'description' => 'Lister displays links to sub-components based on user_ids within members meta_key',
			'category' => 'site',
			'recipe_fields' => array(
				'auto_create',
				'title',
				'template',
				'repudiated_url',
				'url',
				array('checkbox' => 'lister_authority_display'),
				array('role_selection' => 'lister_authority')
			)
		);
	}
	

	public function find_sub_components($requested_component, $vce, $components, $sub_components) {

		if (isset($requested_component->recipe['lister_authority'])) {
			// check if user role_id is have been given admin view access
			if (in_array($vce->user->role_id, explode('|', trim($requested_component->recipe['lister_authority'], '|')))) {
				$requested_component->sub_recipe[0]['sub_component_administration'] = true;
				// use as_link() in child component
				return true;
			}
		}
		
		// user in members
		if (isset($requested_component->members)) {
			// check if user role_id is have been given admin view access
			if (in_array($vce->user->user_id, explode('|', trim($requested_component->members, '|')))) {
				$requested_component->sub_recipe[0]['sub_component_administration'] = true;
				// use as_link() in child component
				return true;
			}
		}
		
		
		$search = '%|' . $vce->user->user_id . '|%';
		$query = "SELECT a.*, b.meta_value AS title, c.meta_value AS created_by FROM " . TABLE_PREFIX . "components AS a JOIN " . TABLE_PREFIX . "components_meta AS b ON a.component_id=b.component_id JOIN " . TABLE_PREFIX . "components_meta AS c ON a.component_id=c.component_id WHERE b.meta_key='title' AND c.meta_key='created_by' AND a.component_id IN (SELECT a.component_id FROM " . TABLE_PREFIX . "components_meta AS a JOIN " . TABLE_PREFIX . "components AS b ON a.component_id=b.component_id WHERE a.meta_key='members' AND a.meta_value LIKE '" . $search . "' AND b.parent_id='" . $requested_component->component_id . "') ORDER BY a.sequence";
		$access_data = $vce->db->get_data_object($query);
		
		if (!empty($access_data)) {
		
			// Save it for later, Don't run away and let me down
			// if only one is found, forward the user into it
			// if (count($access_data) == 1) {
			// 
			// 	if (!empty($access_data[0]->url)) {
			// 		// get location and forward
			// 		$location = $vce->site->site_url . '/' . $access_data[0]->url;
			// 		header('location: ' . $location);
			// 		exit();
			// 	}
			// 	
			// }

			// create links for all that user is a member in
			foreach ($access_data as $each_access_data) {

				if (!empty($each_access_data->url)) {
				
					$display_title = null;
					
					$title = $display_title = isset($each_access_data->title) ? $each_access_data->title : 'Link';
					
					if ($each_access_data->created_by != $vce->user->user_id) {
					
						$user = $vce->user->find_user_by_id($each_access_data->created_by);
						
						$display_title .= ' <span class="lister-link-user">' . $user->first_name . ' ' . $user->last_name . '</span>';
				
					} 
					
					if (isset($each_access_data->status)) {
						$display_title .= ' <span class="lister-link-status">' . ucfirst($each_component->status) . '<span';
					} else {
						$display_title .= ' <span class="lister-link-status"><span';
					}
					
					$class_name = strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/',"-$1", get_class($this))) . '-link';
					$class = 'link-container ' . $class_name . ' anchor-tag-' . $each_access_data->sequence;
					$vce->content->add('main','<div class="' . $class . '"><a href="' . $vce->site->site_url . '/' . $each_access_data->url . '" title="' . $title . '">' . $display_title . '</a></div>');
				}
				
			}
		}
		
		// return false as the last thing
		return false;
		
	}
	
	
	public function check_access($each_component, $vce) {

		if (isset($each_component->recipe['lister_authority'])) {
			// check if user role_id is have been given admin view access
			if (in_array($vce->user->role_id, explode('|', $each_component->recipe['lister_authority']))) {
				return true;
			}
		}
		
		// user in members
		if (isset($each_component->members)) {
			// check if user role_id is have been given admin view access
			if (in_array($vce->user->user_id, explode('|', trim($each_component->members, '|')))) {
				return true;
			}
		}
	
		// prevent viewing if user_id is not in members of sub-component
		if (end($vce->page->components)->type != 'Lister') {

			foreach ($vce->page->components as $each_key=>$each_component) {
		
				if ($each_component->type == 'Lister') {
			
					if (isset($vce->page->components[$each_key+1])) {
					
						// looking for members in the next component
						$next = $vce->page->components[$each_key+1];
				
						if (!empty($next->members)) {

							if (in_array($vce->user->user_id, explode('|', $next->members))) {
								return true;
							}
				
						}
					
					}
				
				}
		
			}
		
		}
		
		if (end($vce->page->components)->type == 'Lister') {
			return true;
		}
		
		return false;
	}
	
	public function as_content($each_component, $vce) {
	
		if (!empty($each_component->url) && end($vce->page->components)->type != 'Lister') {
			return;
		}
		
		if (isset($each_component->recipe['lister_authority'])) {
			// check if user role_id is have been given admin view access
			if (!in_array($vce->user->role_id, explode('|', $each_component->recipe['lister_authority']))) {
				return;
			}
		}
		
		// the != state is not needed, but done for clarity
		if (!isset($each_component->recipe['lister_authority_display']) || $each_component->recipe['lister_authority_display'] != 'on') {
			return;
		}
	
		if (in_array($vce->user->role_id, explode('|', $each_component->recipe['lister_authority']))) {

			$members_list = null;
			$members = array();
	
			if (isset($each_component->members)) {
				$members_list = trim($each_component->members, '|');
				$members = $vce->user->find_users(array('user_ids' => $members_list));
			}		

			// add javascript to page
			$vce->site->add_script(dirname(__FILE__) . '/js/script.js', 'jquery-ui');
		
			// add stylesheet to page
			$vce->site->add_style(dirname(__FILE__) . '/css/style.css','lister-style');

			$image_path = $vce->site->path_to_url(dirname(__FILE__));
			
			// dossier for invite
			$dossier = array(
			'type' => 'Lister',
			'procedure' => 'update_members',
			'component_id' => $each_component->component_id,
			'created_at' => $each_component->created_at
			);

			// generate dossier
			$dossier = $vce->generate_dossier($dossier);
			
			$item_members = null;

			$accordion = null;

			$accordion .= <<<EOF
<form id="edit-group" class="asynchronous-form selector-container" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier">
<input class="selected-users" type="hidden" name="user_ids" value="$members_list">
EOF;

			$accordion .= '<div class="user-selection-type all-users">';

			// search for anyone start
		
			$input = array(
			'type' => 'select',
			'name' => 'selected_user',
			'class' => 'selected-user',
			);
		
			$selected_user = $vce->content->create_input($input,'Results','Select a User');

			// dossier for search
			$dossier = array(
			'type' => 'Lister',
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

			$accordion .= $vce->content->create_input($input,'Search All Users (3 Character Minimum)','Searching For Someone?');	

			$accordion .= '</div>';
			
			$accordion .= <<<EOF
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

			$accordion .= $vce->content->create_input($input, 'Selected Users', null, 'input-padding');
			
			$accordion .= <<<EOF
<input type="submit" value="Update">
<button class="link-button cancel-button">Cancel</button>
</form>
EOF;

			$content = $vce->content->accordion('Select Administrators', $accordion);
		
			
			$vce->content->add('main', $content);

		}

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
	 * update_members
	 */
	public function update_members($input) {
	
		global $vce;
		
		if (!empty($input['component_id'])) {
		
			if (!empty($input['user_ids'])) {
			
				$members_array = explode('|', $input['user_ids']);
			
				// clean-up
				$members = '|' . implode('|', array_unique($members_array)) . '|';
			
			} else {
			
				$members = null;
			
			}
			
			$update['component_id'] = $input['component_id'];
			$update['created_at'] = $input['created_at'];
			$update['members'] = $members;
			
			return $this->update($update);
		
		}
	
	}

}