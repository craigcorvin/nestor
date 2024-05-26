<?php

class ManageSite extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Manage Site',
			'description' => 'Site info and user roles',
			'category' => 'admin',
			'recipe_fields' => array('auto_create','title',array('url' => 'required'))
		);
	}

	/**
	 * display content for ManageStie
	 */
	public function as_content($each_component, $vce) {

		// done for ease
 		$site = $vce->site;
		
		$themes_list = self::get_themes();
			
		$dossier_for_update = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'update'));

		$content = null;

		$accordion = null;
		
		$input_fields = null;

		$input = array(
		'type' => 'text',
		'name' => 'site_title',
		'value' => $site->site_title,
		'data' => array(
			'tag' => 'required'
		)
		);
		
		$input_fields .= $vce->content->create_input($input,'Site Title','Enter a Title');

		$input = array(
		'type' => 'text',
		'name' => 'site_description',
		'value' => $site->site_description,
		'data' => array(
			'tag' => 'required'
		)
		);
		
		$input_fields .= $vce->content->create_input($input,'Site Description','Enter a Site Description');

		$input = array(
		'type' => 'text',
		'name' => 'site_url',
		'value' => $site->site_url,
		'data' => array(
			'tag' => 'required'
		)
		);
		
		$input_fields .= $vce->content->create_input($input,'Site URL','Enter a Site URL');

		$input = array(
		'type' => 'text',
		'name' => 'site_email',
		'value' => $site->site_email,
		'data' => array(
			'tag' => 'required'
		)
		);
		
		$input_fields .= $vce->content->create_input($input,'Site Email','Enter a Site Email');

		$input = array(
		'type' => 'text',
		'name' => 'site_contact_email',
		'value' => $site->site_contact_email,
		'data' => array(
			'tag' => 'required'
		)
		);
		
		$input_fields .= $vce->content->create_input($input,'Site Contact Email','Enter a Site Contact Email');

		$input = array(
		'type' => 'select',
		'name' => 'site_theme',
		'data' => array(
			'tag' => 'required'
		)
		);
		
		foreach ($themes_list as $key=>$meta_name) {
		
			$selected = false;
		
			if ($themes_list[$key]['path'] == $site->site_theme) {
				$selected = true;
			}
		
			$input['options'][] = array(
			'name' => $themes_list[$key]['name'],
			'value' => $themes_list[$key]['path'],
			'selected' => $selected
			);
	
		}
		
		$input_fields .= $vce->content->create_input($input,'Site Theme','Select a Site Theme');

		$input = array(
		'type' => 'select',
		'name' => 'timezone'
		);
		
		// check if timezone has been selected
		$default_timezone = !empty($vce->site->timezone) ? $vce->site->timezone : date_default_timezone_get();
		$timestamp = time();
		
		foreach(timezone_identifiers_list() as $key => $zone) {

			date_default_timezone_set($zone);

			$current = ' ' . date("h:ia", $timestamp) . ' ';
			$difference = ' (UTC/GMT ' . date('P', $timestamp) . ')';

			$selected = false;

			if ($zone == $default_timezone) {
				$selected = true;
			}

			$input['options'][] = array(
			'name' => $zone . $current . $difference,
			'value' => $zone,
			'selected' => $selected
			);

		}
		
		// reset to proper timezone
		date_default_timezone_set($default_timezone);
		
		$input_fields .= $vce->content->create_input($input,'Site Timezone','Select Site Timezone');
		
        // load hooks
        // managesite_site_settings
        if (isset($vce->site->hooks['managesite_site_settings'])) {
            foreach ($vce->site->hooks['managesite_site_settings'] as $hook) {
                $input_fields .=call_user_func($hook, $vce);
            }
        }

		$accordion .= <<<EOF
<form id="update" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_update">
$input_fields
<input type="submit" value="Update"> <input type="reset">
</form>
EOF;

		$content .= $vce->content->accordion('Site Settings', $accordion, true, true);

		// cache value used for JS and CSS

		$dossier_for_cache_set = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'cache','action' => 'set'));
		$dossier_for_cache_clear = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'cache','action' => 'clear'));

		$timestamp = !empty($vce->site->cache) ? 'Current: ' . date('F jS, Y \a\t g:i:s A', $vce->site->cache) : ' - ';

		$accordion = <<<EOF
<span></span>
<form id="cache" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_cache_set">
<input type="submit" value="Set Cache Timestamp">
<div class="link-button">$timestamp</div>
</form>
<form id="cache" class="delete-form float-right-form asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_cache_clear">
<input type="submit" value="Clear Cache">
</form>
EOF;
		
		
		$content .= $vce->content->accordion('Cache JS and CSS', $accordion, true, true);

		// role edit / delete / update

		// fetch user count
		$query = "SELECT role_id, count(role_id) as count FROM " . TABLE_PREFIX . "users GROUP BY role_id";
		$role_count = $vce->db->get_data_object($query);
		
		$count = array();
		foreach($role_count as $each_role_count) {
			
			$count[$each_role_count->role_id] = $each_role_count->count;
		
		}
		
		$accordion = null;

		$site_roles = json_decode($site->roles, true);
		
		// get roles in hierarchical order
        $roles_hierarchical = json_decode($vce->site->site_roles, true);
        
		foreach ($roles_hierarchical as $site_roles) {
			foreach ($site_roles as $key=>$value) {
		
				$key_count = isset($count[$key]) ? $count[$key] : 'No';
		
				$dossier_for_updaterole = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'updaterole','role_id' => $key));
			
				$dossier_for_deleterole = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'deleterole','role_id' => $key));

				$role_name = $value['role_name'];
				$role_hierarchy = $value['role_hierarchy'];
		
				$input = array(
				'type' => 'text',
				'name' => 'role_name',
				'value' => $role_name,
				'data' => array(
					'tag' => 'required'
				)
				);
		
				$role_name_input = $vce->content->create_input($input,'Site Role Name','Enter a Site Role Name');

				$input = array(
				'type' => 'select',
				'name' => 'role_hierarchy',
				'data' => array(
					'tag' => 'required'
				)
				);
		
				// create options
				for ($x = 0;$x <= count($roles_hierarchical);$x++) {
		
					$selected = false;
		
					if ($x == $role_hierarchy) {
						$selected = true;
					}
		
					$input['options'][] = array(
					'name' => $x,
					'value' => $x,
					'selected' => $selected
					);
	
				}

				$role_hierarchy_input = $vce->content->create_input($input,'Role Hierarchy','Enter Role Hierarchy');

				$each_role = <<<EOF
<form id="update_$key" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_updaterole">

$role_name_input
$role_hierarchy_input

<input type="submit" value="Update">
<input type="reset" value="Reset">
</form>
<form id="delete_$key" class="delete-form float-right-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_deleterole">
<input type="submit" value="Delete">
</form>
EOF;


				$accordion .= $vce->content->accordion($role_name . ' / ' . $key_count . ' Users', $each_role);

			}
		}

		$dossier_for_addrole = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'addrole'));

		$input = array(
		'type' => 'text',
		'name' => 'role_name',
		'data' => array(
			'tag' => 'required'
		)
		);

		$role_name_input = $vce->content->create_input($input,'Site Role Name','Enter a Site Role Name');

		$input = array(
		'type' => 'select',
		'name' => 'role_hierarchy',
		'data' => array(
			'tag' => 'required'
		)
		);

		// create options
		for ($x = 0;$x <= count($roles_hierarchical);$x++) {

			$selected = false;

			if ($x == count($roles_hierarchical)) {
				$selected = true;
			}

			$input['options'][] = array(
			'name' => $x,
			'value' => $x,
			'selected' => $selected
			);

		}

		$role_hierarchy_input = $vce->content->create_input($input,'Role Hierarchy','Enter Role Hierarchy');

		$new_role = <<<EOF
<form id="update" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_addrole">
$role_name_input
$role_hierarchy_input
<input type="submit" value="Add New Site Role">
</form>
EOF;

		$accordion .= $vce->content->accordion('Add New Site Role', $new_role);

		$content .= $vce->content->accordion('Site Roles', $accordion, true, true);

		/* start of user attributes */

		$accordion = null;

		$user_attributes = json_decode($vce->site->user_attributes, true);

		// adding to allow sorting
		$attributes_count = count($user_attributes);
		$place_counter = 0;

		foreach ($user_attributes as $user_attribute_key=>$user_attribute_value) {
		
			$place_counter++;
			
			$user_attribute = null;
			
			// dossier to recalibrate attribute, concerning sortable and encoded.
			$dossier_for_recalibrate = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'recalibrate_attribute','attribute' => $user_attribute_key));

			$user_attribute .= <<<EOF
<p>
<form class="recalibrate-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_recalibrate">
<input type="submit" value="Recalibrate this attribute for all users">
</form>
</p>
EOF;

			// create the dossier
			$dossier_for_attribute_update = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'update_attribute','attribute' => $user_attribute_key));

			$user_attribute .= <<<EOF
<form id="$user_attribute_key" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_attribute_update">
EOF;

			$input = array(
			'type' => 'select',
			'name' => 'order',
			'data' => array(
				'tag' => 'required'
			)
			);
		
			for ($x = 1;$x <= $attributes_count;$x++) {
		
				$input['options'][] = array(
				'name' => $x,
				'value' => $x,
				'selected' => (($x == $place_counter) ? true : false)
				);
	
			}
		
			$order_theme = $vce->content->create_input($input,'Order','Order');

			$user_attribute .= $order_theme;

			/* start title */
			$title = isset($user_attribute_value['title']) ? $user_attribute_value['title'] : $user_attribute_key;

			$input = array(
			'type' => 'text',
			'name' => 'title',
			'value' => $title,
			'data' => array(
				'tag' => 'required'
			)
			);
		
			$title_input = $vce->content->create_input($input,'Attribute Title','Enter an Attribute Title');

			$user_attribute .= $title_input;
			
			
			// hook to allow addition user attributes
			if (isset($vce->site->hooks['managesite_user_attibutes'])) {
				foreach($vce->site->hooks['managesite_user_attibutes'] as $hook) {
					$user_attribute .= call_user_func($hook, $user_attribute_value, $vce);
				}
			}
	
/* end title */

/* start types */

			$input = array(
			'type' => 'radio',
			'data' => array(
				'tag' => 'required'
			)
			);
			
			// list of types
			$types = array('text','select','radio','checkbox','conceal');
			
			foreach ($types as $each_type) {
			
				$input['options'][] = array(
				'name' => 'attribute_type',
				'value' => $each_type,
				'label' => ' ' . $each_type,
				'selected' => (($each_type == $user_attribute_value['type']) ? true : false)
				);
			
			}

			$type_input = $vce->content->create_input($input,'Type','Enter Type');

			$user_attribute .= $type_input;

/* end types */

/* start default */
			$input = array(
			'type' => 'text',
			'name' => 'default',
			'value' => (isset($user_attribute_value['default']) ? $user_attribute_value['default'] : null),
			);
		
			$default_input = $vce->content->create_input($input,'Default Value','Enter A Default Value');	

			$user_attribute .= $default_input;
/* end default */

/* start attributes */
			$input = array(
			'type' => 'text',
			'name' => 'html_attributes',
			'value' => (isset($user_attribute_value['html_attributes']) ? $user_attribute_value['html_attributes'] : null),
			);
		
			$default_input = $vce->content->create_input($input,'Extra HTML Attributes Value','Enter Extra HTML Attributes Value');	

			$user_attribute .= $default_input;
/* end default */

/* start datalist */

			$input = array(
			'type' => 'checkbox',
			'name' => 'datalist_checkbox',
			'value' => 1,
			'label' => ' Datalist',
			'selected' => (isset($user_attribute_value['datalist']) ? true : false),
			'data' => array(
			'disabled' => 'disabled'
			)
			);
		
			$datalist_checkbox_input = $vce->content->create_input($input,'Datalist','Enter Datalist');

			if (isset($user_attribute_value['datalist'])) {
				$datalist_checkbox_input .= '<input type="hidden" name="datalist" value="' . $user_attribute_value['datalist']['datalist'] . '">';
			}

			$user_attribute .= $datalist_checkbox_input;

/* end datalist */

/* start required */

			$input = array(
			'type' => 'checkbox',
			'name' => 'required',
			'value' => 1,
			'label' => ' User Attribute Required',
			'selected' => (isset($user_attribute_value['required']) ? true : false),
			);
		
			$required_input = $vce->content->create_input($input,'Required');

			$user_attribute .= $required_input;

/* end required */

/* start sortable */

			$input = array(
			'type' => 'checkbox',
			'name' => 'sortable',
			'value' => 1,
			'label' => ' User Attribute Sortable',
			'selected' => (isset($user_attribute_value['sortable']) ? true : false),
			);
		
			$sortable_input = $vce->content->create_input($input,'Sortable');

			$user_attribute .= $sortable_input;


/* end sortable */

/* start editable */

			$input = array(
			'type' => 'checkbox',
			'name' => 'editable',
			'value' => 1,
			'label' => ' Attribute Editable By User',
			'selected' => (isset($user_attribute_value['editable']) ? true : false),
			);
		
			$editable_input = $vce->content->create_input($input,'Editable');

			$user_attribute .= $editable_input;

/* end editable */


/* start expose */

			$input = array(
			'type' => 'checkbox',
			'name' => 'expose',
			'value' => 1,
			'label' => ' Attribute value is not encoded when stored in database',
			'selected' => (isset($user_attribute_value['expose']) ? true : false),
			);
		
			$editable_input = $vce->content->create_input($input,'Expose');

			$user_attribute .= $editable_input;

/* end expose */

			$user_attribute .= <<<EOF
<input type="submit" value="Update">
<div class="link-button cancel-button">Cancel</div>
</form>
EOF;

			// create the dossier
			$dossier_for_attribute_delete = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'delete_attribute','attribute'  => $user_attribute_key));

			$user_attribute .= <<<EOF
<form class="delete-form float-right-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_attribute_delete">
<input type="submit" value="Delete">
</form>
EOF;

			$accordion .= $vce->content->accordion($title . ' (' . $user_attribute_key . ')', $user_attribute);

		}

		// create the dossier
		$dossier_for_attribute_create = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'create_attribute'));

			$input = array(
			'type' => 'text',
			'name' => 'title',
			'data' => array(
				'tag' => 'required'
			)
			);
		
			$title_input = $vce->content->create_input($input,'Attribute Title','Enter an Attribute Title');

			$input = array(
			'type' => 'radio',
			'data' => array(
				'tag' => 'required'
			)
			);
			
			// list of types
			$types = array('text','select','radio','checkbox','conceal');
			
			foreach ($types as $each_type) {
			
				$input['options'][] = array(
				'name' => 'attribute_type',
				'value' => $each_type,
				'label' => ' ' . $each_type,
				'selected' => (($each_type == 'text') ? true : false)
				);
			
			}

			$type_input = $vce->content->create_input($input,'Type','Enter Type');
			
			$input = array(
			'type' => 'checkbox',
			'name' => 'datalist_checkbox',
			'value' => 1,
			'label' => ' Datalist'
			);
		
			$datalist_checkbox_input = $vce->content->create_input($input,'Datalist','Enter Datalist');
			
			$input = array(
			'type' => 'checkbox',
			'name' => 'datalist_checkbox',
			'value' => 1,
			'label' => ' Datalist',
			);
			
			// disabled for current user properties, but not for create
			// $input['data']['disabled'] = 'disabled';
		
			$datalist_checkbox_input = $vce->content->create_input($input,'Datalist','Enter Datalist');

			$input = array(
			'type' => 'checkbox',
			'name' => 'required',
			'value' => 1,
			'label' => ' User Attribute Required',
			);
		
			$required_input = $vce->content->create_input($input,'Required');

			$input = array(
			'type' => 'checkbox',
			'name' => 'sortable',
			'value' => 1,
			'label' => ' User Attribute Sortable',
			);
		
			$sortable_input = $vce->content->create_input($input,'Sortable');

			$input = array(
			'type' => 'checkbox',
			'name' => 'editable',
			'value' => 1,
			'label' => ' Attribute Editable By User',
			);
		
			$editable_input = $vce->content->create_input($input,'Editable');

			$input = array(
			'type' => 'checkbox',
			'name' => 'expose',
			'value' => 1,
			'label' => ' Attribute value is not encoded when stored in database'
			);
		
			$expose_input = $vce->content->create_input($input,'Expose');

		$create_attribute = <<<EOF
<form id="create_attribute" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_attribute_create">
$title_input
$type_input 
$datalist_checkbox_input
$required_input
$sortable_input
$editable_input
$expose_input
<input type="submit" value="Create">
<div class="link-button cancel-button">Cancel</div>
</form>
EOF;


		$accordion .= $vce->content->accordion('Add a new User Attribute', $create_attribute);
		
		$content .= $vce->content->accordion('User Attributes', $accordion, true, true);
		
		$data = null;
		
		$ini_get_all = ini_get_all();

        $data .= '<table border=1 style="overflow-wrap:anywhere;">
        <tr>
        <td>Options</td><td>Global Value</td><td>Local Value</td>
        </tr>';
		
		foreach (ini_get_all() as $key=>$value) {
			
			$data .= '<tr><td>' . $key . '</td><td>' . $value['global_value'] . '</td><td>' . $value['local_value'] . '</td></tr>';
		
		}
		
		$data .= '</table>';
		
		$content .= $vce->content->accordion('PHP: ini_get_all()', $data);

		$vce->content->add('main', $content);

	}
	
	
	
	/**
	 * create attribute
	 */
	public function create_attribute($input) {
	
		$vce = $this->vce;
		
		// set the type to the attribute_type
		$input['type'] = $input['attribute_type'];
		unset($input['attribute_type']);
		
		$user_attributes = json_decode($vce->site->user_attributes, true);
		
		$attribute = strtolower(preg_replace('/\s+/', '_', $input['title']));
		
		
		// this is a place to add a hook so that datalists can be moved into a utility component
		if (isset($input['datalist_checkbox'])) {
		
			$attributes = array (
			'datalist' => $attribute . '_datalist',
			'aspects' => array ('name' => $attribute)
			);
			
			$vce->create_datalist($attributes);
			
			unset($input['datalist_checkbox']);
			
			$input['datalist'] = array('datalist' => $attribute . '_datalist');
		
		}
		
		// add to existing user_attributes array
		foreach ($input as $key=>$value) {
			$user_attributes[$attribute][$key] = $value;
		}
		
		$update = array('meta_value' => json_encode($user_attributes));
		$update_where = array('meta_key' => 'user_attributes');
		$vce->db->update('site_meta', $update, $update_where);
		
	
		echo json_encode(array('response' => 'success','procedure' =>'create','action' => 'reload','message' => json_encode($user_attributes)));
		return;
	
	}
	
	
	/**
	 * update attribute
	 */
	public function update_attribute($input) {
	
		$vce = $this->vce;

		// set the type to the attribute_type
		$input['type'] = $input['attribute_type'];
		$attribute = $input['attribute'];
		$order = $input['order'];
		unset($input['attribute'],$input['attribute_type'],$input['order']);
		
		$user_attributes = json_decode($vce->site->user_attributes, true);
		
		// rekey
		$position = 1;
		foreach ($user_attributes as $key=>$value) {
			if ($key == $attribute) {
				// place before
				if ($order < $position) {
					$attributes[(($order * 2) - 1)][$key] = $value;
					continue;
				}
				// place after
				if ($order > $position) {
					$attributes[(($order * 2) + 1)][$key] = $value;
					continue;	
				}
			}
			// standard place
			$attributes[($position * 2)][$key] = $value;
			// add 
			$position++;
		}
		
		ksort($attributes);
		
		$updated_attributes = array();
		
		foreach ($attributes as $user_attributes) {
		
			foreach ($user_attributes as $key=>$value) {
				if ($key == $attribute) {
					foreach ($input as $input_key=>$input_value) {
					
						// allow for quotes
						$input_value = str_replace('\&quot;','',$input_value);
					
						// don't update datalist
						if ($input_key != "datalist") {
							$updated_attributes[$attribute][$input_key] = $input_value;
						} else {
							$updated_attributes[$attribute]['datalist'] = $user_attributes[$attribute]['datalist'];
						}
					}
				} else {
					$updated_attributes[$key] = $value;
				}
			}
		
		}
		
		$update = array('meta_value' => json_encode($updated_attributes));
		$update_where = array('meta_key' => 'user_attributes');
		$vce->db->update('site_meta', $update, $update_where);
	
		echo json_encode(array('response' => 'success','procedure' =>'create','action' => 'reload','message' => 'User Attribute Updated'));
		return;
	
	}


	/**
	 * update attribute
	 */
	public function delete_attribute($input) {

		$vce = $this->vce;

		// set the type to the attribute
		$attribute = $input['attribute'];
		unset($input['attribute']);
		
		$user_attributes = json_decode($vce->site->user_attributes, true);
		
		$updated_attributes = array();
		foreach ($user_attributes as $key=>$value) {
			if ($key == $attribute) {
				continue;
			} else {
				$updated_attributes[$key] = $value;
			}
		}
		
		$update = array('meta_value' => json_encode($updated_attributes));
		$update_where = array('meta_key' => 'user_attributes');
		$vce->db->update('site_meta', $update, $update_where);
		
		
		echo json_encode(array('response' => 'success','procedure' =>'create','action' => 'reload','message' => 'Deleted'));
		return;
		
	}
	
	/**
	 * Recalibrate Attribute
	 */
	public function recalibrate_attribute($input) {
	
		$vce = $this->vce;
		
		$user_attributes = json_decode($vce->site->user_attributes, true);
	
		if (isset($input['attribute']) && isset($user_attributes[$input['attribute']])) {
		
			$attribute = $user_attributes[$input['attribute']];
			
			$meta_key = $input['attribute'];
			
			$sortable = !empty($attribute['sortable']) ?  true : false;
			$expose = !empty($attribute['expose']) ?  true : false;
			
			// lets assume that the previous values are encrytped
			$encrytped = true;
			
			// get all meta_data for selected users
        	$query = "SELECT a.user_id, b.vector, a.meta_value, a.minutia FROM " . TABLE_PREFIX . "users_meta AS a JOIN " . TABLE_PREFIX . "users as b ON a.user_id = b.user_id WHERE a.meta_key='" . $meta_key . "'";
        	$response = $vce->db->get_data_object($query);
        	
        	foreach ($response as $meta_data) {
        	
        		$user_id = $meta_data->user_id;
        	
        		$meta_value = $meta_data->meta_value;
        		$minutia = null;
        		
        		$decryption = $vce->site->decryption($meta_data->meta_value, $meta_data->vector);

				if (!empty($decryption)) {
					$meta_value = $decryption;
				} else {
					// check against an empty value. This is a lot of work, but is preferable to corrupting user data
					if ($meta_data->meta_value == $vce->site->encryption(null, $meta_data->vector)) {
						// the decrytped vale was null
						$meta_value = null;
					} elseif ($meta_data->meta_value == $vce->site->encryption(0, $meta_data->vector)) {
						// the decrytped vale was zero
						$meta_value = 0;
					} else {
						// we found an error, and will leave the value what is was before decryption
						$meta_value = $meta_data->meta_value;
					}
				}
			
        		if ($sortable) {
        			if ($expose) {
        				// if expose then minutia is same value as meta_value
        				$minutia = $meta_value;
        			} else {
        				// create oph for meta_value
        				$minutia = user::order_preserving_hash($meta_value);
        			}
        		}
        		
				if ($expose) {
					// decryption
					$meta_value = $meta_value;
				} else {
					// encryption
					$meta_value = $vce->site->encryption($meta_value, $meta_data->vector);
				}

				// update database table
				$update = array('meta_value' => $meta_value, 'minutia' => $minutia);
				$update_where = array('user_id' => $user_id, 'meta_key' => $meta_key);
				$vce->db->update('users_meta', $update, $update_where);
        	
        	}

			echo json_encode(array('response' => 'success','procedure' =>'create','action' => 'reload','message' => 'Recalibrated'));
			return;			
		
		}
	
	}
	
	
	/**
	 * Update Site Meta
	 */
	public function update($input) {
	
		$vce = $this->vce;
		
		unset($input['type']);
		
		foreach ($input as $meta_key=>$meta_value) {
			
			// if the meta_key exists in site_meta table, then update
			if (isset($vce->site->$meta_key)) {
			
				// update hash
				$update = array('meta_value' => $meta_value);
				$update_where = array('meta_key' => $meta_key);
				$vce->db->update('site_meta', $update, $update_where);

			} else {
			
 				$user_data = array(
				'meta_key' => $meta_key, 
				'meta_value' => $meta_value,
				'minutia' => null
 				);

				$vce->db->insert('site_meta', $user_data);
			
			}
		
		}
	
		echo json_encode(array('response' => 'success','procedure' => 'update','action' => 'reload','message' => 'Updated'));
		return;
	
	}
	
	
	/**
	 * Add Role
	 */
	public function addrole($input) {
	
		$vce = $this->vce;
		
		// get current site roles
		$current_roles = json_decode($vce->site->roles, true);
		
		// new role name
		$role_name = trim($input['role_name']);

		// new role_hierarchy
		$role_hierarchy = trim($input['role_hierarchy']);

		// case insensitive check to see if role name is already in use
		foreach ($current_roles as $key=>$value) {
			if (strtolower($role_name) == strtolower($value['role_name'])) {
				echo json_encode(array('response' => 'error','procedure' => 'create','message' => $value['role_name'] . ' is already in use'));
				return;
			}
		}
		
		// set new role
		$current_roles[] = array(
		'role_name' => $role_name,
		'role_hierarchy' => $role_hierarchy
		);
		
		
		// update roles
		$update = array('meta_value' => json_encode($current_roles));
		$update_where = array('meta_key' => 'roles');
		$vce->db->update('site_meta', $update, $update_where);
	
	
		echo json_encode(array('response' => 'success','procedure' =>'create','action' => 'reload','message' => 'New Site Role Created'));
		return;
	
	}	
	

	/**
	 * Update Role
	 */
	public function updaterole($input) {
	
		$vce = $this->vce;
		
		// get current site roles
		$current_roles = json_decode($vce->site->roles, true);
		
		// current role id
		$role_id = trim($input['role_id']);	
		
		// new role name
		$role_name = trim($input['role_name']);
		
		// new role_hierarchy
		$role_hierarchy = trim($input['role_hierarchy']);

		// case insensitive check to see if role name is already in use
		foreach ($current_roles as $key=>$value) {
			if (isset($value['role_name']) && strtolower($value['role_name']) == strtolower($role_name) && $key != $role_id) {
				echo json_encode(array('response' => 'error','procedure' =>'update','action' => 'reload','message' => $value['role_name'] . ' is already in use'));
				return;
			}
		}
		
		// set new role
		// $current_roles[$role_id] = array('role_name' => $role_name);
		if (is_array($current_roles[$role_id])) {
			$current_roles[$role_id]['role_name'] = $role_name;
			$current_roles[$role_id]['role_hierarchy'] = $role_hierarchy;
		} else {
			// we can remove this once we know every instance has been updated
			$current_roles[$role_id] = array('role_name' => $role_name);
		}
		
		
		// update roles
		$update = array('meta_value' => json_encode($current_roles));
		$update_where = array('meta_key' => 'roles');
		$vce->db->update('site_meta', $update, $update_where);
	
		echo json_encode(array('response' => 'success','procedure' =>'update','action' => 'reload','message' => 'Role Updated'));
		return;
	
	}
	
	/**
	 * Update Role
	 */
	public function deleterole($input) {
	
		$vce = $this->vce;
		
		// get current site roles
		$current_roles = json_decode($vce->site->roles, true);
		
		// current role id
		$role_id = trim($input['role_id']);
		
		if ($role_id == "1") {
			echo json_encode(array('response' => 'error','procedure' =>'delete','message' => 'This role cannot be deleted'));
			return;
		}
		
		// fetch user count
		$query = "SELECT role_id FROM " . TABLE_PREFIX . "users WHERE role_id='" . $role_id . "'";
		$role_count = $vce->db->get_data_object($query);
		
		if (!empty($role_count)) {
		
			echo json_encode(array('response' => 'error','procedure' =>'delete','message' => 'There are ' . count($role_count) . ' users assigned to this role', 'form' => 'delete'));
			return;	
		
		}
			
		
		// remove role from array
		unset($current_roles[$role_id]);
		
		// update roles
		$update = array('meta_value' => json_encode($current_roles));
		$update_where = array('meta_key' => 'roles');
		$vce->db->update('site_meta', $update, $update_where );
	
		echo json_encode(array('response' => 'success','procedure' =>'delete','action' => 'reload','message' => 'Role Deleted'));
		return;
	
	}	

	/**
	 * create an array of themes names
	 */ 
	private function get_themes() {
	
		$themes = array();
		
		// http://php.net/manual/en/class.directoryiterator.php
		foreach (new DirectoryIterator(BASEPATH . "vce-content/themes/") as $key=>$fileInfo) {
   	 		
   	 		// check for dot files
   	 		if ($fileInfo->isDot()) {
   	 			continue;
   	 		}
   	 		
   	 		if ($fileInfo->isDir()) {
   	 		
   	 			// full path
   	 			$full_path = BASEPATH . "vce-content/themes/" . $fileInfo->getFilename() . "/theme.php";
   	 			
   	 			if (file_exists($full_path)) {
   	 			
   	 			   	 // set theme path
   	 				$themes[$key]['path'] = $fileInfo->getFilename();
   	 		
					// get theme name
					preg_match('/Theme Name:(.*)$/mi', file_get_contents($full_path), $header);
				
					// set theme name
					if (isset($header['1'])) {
						$themes[$key]['name'] = trim($header['1']);
					} else {
						$themes[$key]['name'] = $fileInfo->getFilename();
					}
   	 			
   	 			}
			}
		
		}
		
		return $themes;

	}
	
	
	/**
	 * create an array of template names with file paths
	 */ 
	public function get_template_names() {

		$vce = $this->vce;

		$path = BASEPATH . "vce-content/themes/" . $vce->site->site_theme;

		$files = scandir(BASEPATH . "vce-content/themes/" . $vce->site->site_theme);

		for ($x=0,$y=count($files);$x<$y;$x++) {
			if (!preg_match('/\.php/', $files[$x])) {
				unset($files[$x]);
			}
		}

		$template_names = array();

		foreach($files as $each_file) {

			$full_path = BASEPATH . "vce-content/themes/" . $vce->site->site_theme . '/' . $each_file;

			preg_match('/Template Name:(.*)$/mi', file_get_contents($full_path), $header);

			if (empty($header)) {
				$template_names["Default"] = $each_file;
			} else {
				$template_names[trim($header['1'])] = $each_file;
			}
		}

		return $template_names;

	}
	
	/**
	 * 
	 */
	public function cache($input) {
	
		$vce = $this->vce;
		
		$timestamp = time();
	
		if ($input['action'] == 'clear') {
			$timestamp = null;
		}
		
		if (isset($vce->site->cache)) {
		
			// update roles
			$update = array('meta_value' => $timestamp);
			$update_where = array('meta_key' => 'cache');
			$vce->db->update('site_meta', $update, $update_where);
			
		} else {

			$user_data = array(
			'meta_key' => 'cache', 
			'meta_value' => $timestamp
			);

			$vce->db->insert('site_meta', $user_data);
		}

		echo json_encode(array('response' => 'success','procedure' =>'cache','action' => 'reload','message' => 'Cache Updated'));
		return;
	
	}

}