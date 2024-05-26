<?php

class ManageMenus extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Manage Menus',
			'description' => 'Create, edit, and delete menues for site navigation',
			'category' => 'admin',
			'recipe_fields' => array('auto_create','title', array('url' => 'required'))
		);
	}
	
	
	/**
	 *
	 */
	public function as_content($each_component, $vce) {
	
// 		$site_menus = json_decode($vce->site->site_menus,true);
// 		$vce->dump($site_menus);
	
		$menu_name = isset($_POST['menu_name']) ? $_POST['menu_name'] : null;
		
		// nestable jquery plugin this is all based on
		// http://dbushell.github.io/Nestable/
		// https://github.com/dbushell/Nestable

		// add javascript to page
		$vce->site->add_script(dirname(__FILE__) . '/js/jquery-nestable.js', 'jquery tablesorter');
	
		// add javascript to page
		$vce->site->add_script(dirname(__FILE__) . '/js/script.js');
		
		// add javascript to page
		$vce->site->add_style(dirname(__FILE__) . '/css/jquery-nestable.css', 'jquery-nestable-style');
	
		// add javascript to page
		$vce->site->add_style(dirname(__FILE__) . '/css/style.css', 'manage-menu-style');

		$dossier = $vce->generate_dossier(array('type' => 'ManageMenus','procedure' => 'create'));
		
		$dossier_for_edit = $vce->generate_dossier(array('type' => 'ManageMenus','procedure' => 'update'));

		$dossier_for_delete  = $vce->generate_dossier(array('type' => 'ManageMenus','procedure' => 'delete'));
		
		$block = <<<EOF
<div class="sort-block left-block">
<div class="sort-block-title">Pages</div>
<div class="dd" id="nestable">
<ol class="dd-list">
EOF;

		// get installed components
		$activated_components = json_decode($vce->site->activated_components, true);
		
		ksort($activated_components);
		
		$in_values = array();
		
		foreach ($activated_components as $component_type=>$component_path) {
		
			$add = false;
			
			// create an instance of the class
            $check = $vce->page->instantiate_component(array('type' => $component_type), $vce);
			
			$component_info = $check->component_info();
			
			if (!empty($component_info['recipe_fields'])) {
				
				foreach ($component_info['recipe_fields'] as $each_field) {
				
					if ($each_field == 'url') {
						$add = true;
					}
					
					if (is_array($each_field) && array_key_exists('url', $each_field)) {
						$add = true;
					}
				
				}

			}
			
			if ($add) {
			
				$in_values[] = "'" . $component_type . "'";
			
			}
			
		}
		
		// get all urls
		$query = "SELECT component_id, url FROM " . TABLE_PREFIX . "components WHERE component_id IN (SELECT component_id FROM " . TABLE_PREFIX . "components_meta WHERE meta_value IN (" . implode(',', $in_values) . "))";
		$urls = $vce->db->get_data_object($query);

		// get installed components
		// $activated_components = json_decode($site->activated_components, true);

		foreach ($urls as $each_url) {
		
			// FIX: needed to hack this now and limit to only two / /  in order to prevent huge load time.	
			
			// get the url depth
			$url_depth = ($each_url->url != "/") ? substr_count($each_url->url,'/') : 0;
				
			if ($url_depth > 2) {
				continue;
			}
			
			// was component created on recipe save? If so, then show in Pages
			if (isset($each_url->url)) {

				// get the component title
				$query = "SELECT meta_value AS title FROM " . TABLE_PREFIX . "components_meta WHERE component_id='" . $each_url->component_id . "' AND meta_key='title'";
				$title = $vce->db->get_data_object($query);

				$each_url->title = isset($title[0]->title) ? $title[0]->title : null;
			
				// Anonymous function to get role access
				$get_role_access = function($id) use (&$get_role_access) {
			
					global $db;
			
					// get role_access
					$query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE component_id='" . $id . "' AND meta_key='role_access'";
					$access = $db->get_data_object($query);
		
					if (isset($access[0]->meta_value)) {
				
						// found role access and returning value
						return $access[0]->meta_value;
			
					} else {
				
						// get parent id of current component
						$query = "SELECT parent_id FROM " . TABLE_PREFIX . "components WHERE component_id='" . $id . "'";
						$parent = $db->get_data_object($query);
			
						if (isset($parent[0]->parent_id)) {
							// recursive call to anonymous function
							return $get_role_access($parent[0]->parent_id);
						} else {
							// as a default, if no role_access is found, return all roles
							global $vce;
							return implode('|', array_keys(json_decode($vce->site->roles, true)));
						}
			
					}
			
				};
			
				$role_access = $get_role_access($each_url->component_id);
			
				$each_url->role_access = $role_access;
				
				// get the url depth
				$url_depth = ($each_url->url != "/") ? substr_count($each_url->url,'/') : 0;
				
				$depth[$url_depth] = true;
				
				$site_roles = json_decode($vce->site->site_roles, true);
		
				$site_roles[][0] = array(
				'role_name' => 'Public',
				'role_id' => 'x'
				);
				
				// call to get form elements
				$form_elements = self::form_elements($each_url, $role_access, $site_roles, $vce);


				$block .= <<<EOF
<li class="dd-item depth_all depth_$url_depth" referrer="$each_url->component_id" unique-id="$each_url->component_id" data-id="$each_url->component_id" data-url="$each_url->url" data-title="$each_url->title" data-role_access="$each_url->role_access">
<div class="dd-handle dd3-handle">&nbsp;</div><div class="dd-content"><div class="dd-title">$each_url->title</div><div class="dd-toggle"></div>
<div class="dd-content-extended">
$form_elements
<button data-action="remove" type="button">Remove</button>
</div></div>
</li>
EOF;
		
			}
		
		}

	
		$block .= <<<EOF
</ol>
EOF;

		// alpha sort of categories
		ksort($depth);
		
		$accordion = null;
		
		//
		foreach ($depth as $depth_key=>$depth_value) {
			$accordion .= '<button class="depth-display';
			if ($depth_key == 0) {
				$accordion .= ' highlight';
			}
			$accordion .= '" category="' . $depth_key . '">' . $depth_key . '</button>';
		}

		$block .= $vce->content->accordion('Display By Depth', $accordion, true, true);

		$block .= <<<EOF
</div>
</div>
<div class="sort-block right-block">
<div class="sort-block-title">Menu</div>
<div class="dd" id="nestable2">
EOF;

		if (isset($menu_name)) {

			$site_menus = json_decode($vce->site->site_menus,true);

			// call to recursive function
			$block .= '<ol class="dd-list">';
			$block .= self::cycle_though_recipe($menu_name, $site_menus[$menu_name]);
			$block .= '</ol>';

		} else {

			// empty
			$block .= '<div class="dd-empty"></div>';

		}

		// input for menu name
		$input = array(
		'type' => 'text',
		'name' => 'menu_name',
		'value' => $menu_name,
		'data' => array(
		'tag' => 'required'
		)
		);
		
		$menu_name_inpt = $vce->content->create_input($input, 'Menu Name', 'Enter a Menu Name');

		$block .= <<<EOF
</div>
<form id="create_sets" class="recipe-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier">
<div class="recipe-info" style="clear:both">
$menu_name_inpt
<input type="submit" value="Save This Menu">
EOF;

		if (isset($menu_name)) {

			$block .= <<<EOF
<button class="link-button cancel-button">Cancel</button>
EOF;

		}

		$block .= <<<EOF
</div>
</form>
</div>
EOF;

		if (!isset($menu_name)) {
			$accordion_title = 'Create A New Menu';
		} else {
			$accordion_title = 'Edit Menu';
		}


		$content = $vce->content->accordion($accordion_title, $block , true, true);

		$accordion = <<<EOF
<table id="existing-menus" class="tablesorter">
<thead>
<tr>
<th></th>
<th>Name</th>
<th>Code For Theme</th>
<th></th>
</tr>
</thead>
EOF;
		
		foreach (json_decode($vce->site->site_menus, true) as $key=>$value) {
		
			$accordion .= <<<EOF
<tr>
<td class="align-center">
<form method="post" action="$vce->site_url/$vce->requested_url">
<input type="hidden" name="action" value="edit">
<input type="hidden" name="menu_name" value="$key">
<input type="submit" value="Edit">
</form>
</td>
<td>
$key
</td>
<td>
&lt;?php &#36;content->menu('$key'); ?&gt;
</td>
<td class="align-center">
<form id="menu_" class="delete-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="hidden" name="menu_name" value="$key">
<input type="submit" value="Delete">
</form>
</td>
</tr>
EOF;
		
		}
		
		$accordion .= <<<EOF
</table>
EOF;

		$content .= $vce->content->accordion('Existing Menus', $accordion, true, true, 'no-padding');

		$vce->content->add('main', $content);
	
	}
	

	
	/**
	 * recursive function
	 */
	private function cycle_though_recipe($menu_name, $site_menus) {
	
		global $vce;

		$content = "";
	
		foreach ($site_menus as $each_item) {
	
			// create a copy
			$each_url = (object) $each_item;
				
			// unset components
			unset($each_url->components);
			
			// clean up role_access by remove duplicates
			$role_access = implode('|', array_unique(explode('|',$each_url->role_access)));
			
			$site_roles = json_decode($vce->site->site_roles, true);
		
			$site_roles[][0] = array(
			'role_name' => 'Public',
			'role_id' => 'x'
			);
		
			// call to get form elements
			$form_elements = self::form_elements($each_url, $role_access, $site_roles, $vce);
			
			$content .= <<<EOF
<li class="dd-item" referrer="$each_url->id" unique-id="$each_url->id" data-id="$each_url->id" data-url="$each_url->url" data-title="$each_url->title" data-role_access="$role_access">
<div class="dd-handle dd3-handle">&nbsp;</div><div class="dd-content"><div class="dd-title">$each_url->title</div><div class="dd-toggle"></div>
<div class="dd-content-extended">
$form_elements
<button data-action="remove" type="button">Remove</button>
</div></div>
EOF;


			if (isset($each_item['components'])) {
	
				$content .= '<ol class="dd-list">';
	
				$content .= self::cycle_though_recipe($menu_name, $each_item['components']);
	
				$content .= '</ol></li>';
	
			} 
	
		}
	
		return $content;
	
	}
	
	
	/**
	 * create form elements
	 */
	private function form_elements($each_url, $role_access, $site_roles, $vce) {
	
		$input_elements = null;

		$input = array(
		'type' => 'text',
		'name' => 'title',
		'value' => $each_url->title,
		'data' => array(
		'tag' => 'required'
		)
		);
		
		$input_elements .= $vce->content->create_input($input, 'Title','Enter a Title');
		
		$input = array(
		'type' => 'text',
		'name' => 'url',
		'value' => $each_url->url,
		'data' => array(
		'tag' => 'required'
		)
		);
		
		$input_elements .= $vce->content->create_input($input, 'URL','Enter a URL');

		$input = array(
		'type' => 'checkbox',
		'name' => 'role_access',
		'flags' => array(
		'label_tag_wrap' => true
		)
		);
		
		foreach ($site_roles as $each_role) {
			
			$role = array_values($each_role)[0];
			
			$selected = false;
			
			if (in_array($role['role_id'],array_unique(explode('|',$each_url->role_access)))) {
				$selected = true;
			}
		
			$input['options'][] = array (
			'name' => 'role_access',
			'value' => $role['role_id'],
			'label' => ' ' . $role['role_name'],
			'selected' => $selected
			);
			
		}
		
		$input_elements .= $vce->content->create_input($input, 'Who Can View This?');
		
		$selected = false;
		
		if (isset($each_url->target) && $each_url->target == 'new_window') {
			$selected = true;
		}

		// note: because we are making a copy of this in JS we need to wrap the checkbox in a label
		$input = array(
		'type' => 'checkbox',
		'name' => 'target',
		'value' => 'new_window',
		'label' => ' Open in a new window',
		'selected' => $selected,
		'flags' => array(
			'label_tag_wrap' => true
		)
		);
		
		$input_elements .= $vce->content->create_input($input, 'Target');
		
		
		$input = array(
		'type' => 'text',
		'name' => 'class',
		'value' => (isset($each_url->class) ? $each_url->class : null)
		);
		
		$input_elements .= $vce->content->create_input($input, 'CSS Class (Optional)','Enter a CSS Class');
	
		// hook to allow addition menu meta data
		if (isset($vce->site->hooks['managemenus_form_elements'])) {
			foreach($vce->site->hooks['managemenus_form_elements'] as $hook) {
				$input_elements .= call_user_func($hook, $each_url, $role_access, $site_roles, $vce);
			}
		}

		return $input_elements;
	
	}

	
	/**
	 * Create a new recipe
	 */
	public function create($input) {
	
		$vce = $this->vce;
		
		$site_menus = isset($vce->site->site_menus) ? json_decode($vce->site->site_menus,true) : array();
	
		$name = $input['menu_name'];
		
		// could prevent saving over a current menu_name: if (!isset($site_menus[$name])) {
		
			// create an associate array from the json object of recipe
			$menu_items = json_decode(htmlentities($input['json'], ENT_NOQUOTES), true);
		
			$site_menus[$name] = $menu_items;
		
			$update = array('meta_value' => json_encode($site_menus, JSON_UNESCAPED_SLASHES));
			$update_where = array('meta_key' => 'site_menus');
			$vce->db->update('site_meta', $update, $update_where);
		
			echo json_encode(array('response' => 'success','message' => 'Menu saved','action' => ''));
			return;
	
		//}

	
	}
	
	
	
	/**
	 * Create a new recipe
	 */
	public function delete($input) {
	
		$vce = $this->vce;
	
		$main_name = $input['menu_name'];
	
		$site_menus = isset($vce->site->site_menus) ? json_decode($vce->site->site_menus,true) : array();
	
		unset($site_menus[$main_name]);
	
		$update = array('meta_value' => json_encode($site_menus, JSON_UNESCAPED_SLASHES));
		$update_where = array('meta_key' => 'site_menus');
		$vce->db->update('site_meta', $update, $update_where);
	
		echo json_encode(array('response' => 'success','message' => 'deleted'));
		return;
	
	}
	
}