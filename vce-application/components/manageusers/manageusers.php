<?php

class ManageUsers extends Component {

    /**
     * basic info about the component
     */
    public function component_info() {
        return array(
            'name' => 'Manage Users',
            'description' => 'Add, edit and delete site users. You can also masquerade as them using this component.',
            'category' => 'admin',
            'permissions' => array(
                array(
                    'name' => 'create_users',
                    'description' => 'Role can create new users',
                ),
                array(
                    'name' => 'edit_users',
                    'description' => 'Role can delete users',
                ),
                array(
                    'name' => 'delete_users',
                    'description' => 'Role can delete users',
                ),
             	array(
                    'name' => 'merge_users',
                    'description' => 'Role can merge users',
                ),
                array(
                    'name' => 'masquerade_users',
                    'description' => 'Role can masquerade as users',
                ),
                array(
                    'name' => 'suspend_users',
                    'description' => 'Role can suspend users',
                )
            ),
            'recipe_fields' => array('auto_create','title',array('url' => 'required'))
        );
    }

    /**
     *
     */
    public function as_content($each_component, $vce) {

        // add javascript to page
        $vce->site->add_script(dirname(__FILE__) . '/js/script.js');

        $vce->site->add_style(dirname(__FILE__) . '/css/style.css');

        // minimal user attributers
        $default_attributes = array(
            'user_id' => array(
                'title' => 'User Id',
                'sortable' => 1,
            ),
            'role_id' => array(
                'title' => 'Role Id',
                'sortable' => 1,
            ),
            'email' => array(
                'title' => 'Email',
                'required' => 1,
                'type' => 'text',
                'sortable' => 1,
            )
        );

        $user_attributes = json_decode($vce->site->user_attributes, true);

        $attributes = array_merge($default_attributes, $user_attributes);

        $filter_by = array();

        foreach ($vce as $key => $value) {
            if (strpos($key, 'filter_by_') !== FALSE) {
                $filter_by[str_replace('filter_by_', '', $key)] = $value;
            }
        }

        // manage_users_attributes_filter_by
        if (isset($vce->site->hooks['manage_users_attributes_filter_by'])) {
            foreach ($vce->site->hooks['manage_users_attributes_filter_by'] as $hook) {
                $filter_by = call_user_func($hook, $filter_by, $vce);
            }
        }

        // check if edit_user is within the page object, which means we want to edit this user
        $edit_user = isset($vce->edit_user) ? $vce->edit_user : null;

        // get roles
        $roles = json_decode($vce->site->roles, true);

        // get roles in hierarchical order
        $roles_hierarchical = json_decode($vce->site->site_roles, true);

        // create var for content
        $content = null;

        // variables
        $sort_by = isset($vce->sort_by) ? $vce->sort_by : 'email';
        $sort_direction = isset($vce->sort_direction) ? $vce->sort_direction : 'ASC';
        $display_users = true;
        $pagination = true;
        $pagination_current = isset($vce->pagination_current) ? $vce->pagination_current : 1;
        $pagination_length = isset($vce->pagination_length) ? $vce->pagination_length : 100;

        // create search in values
        $role_id_in = array();
        foreach ($roles_hierarchical as $roles_each) {
            foreach ($roles_each as $key => $value) {
                if ($value['role_hierarchy'] >= $roles[$vce->user->role_id]['role_hierarchy']) {
                    // add to role array
                    $role_id_in[] = $key;
                }
            }
        }

        // get total count of users
        $query = "SELECT count(*) as count FROM " . TABLE_PREFIX . "users WHERE role_id IN (" . implode(',', $role_id_in) . ")";

        $count_data = $vce->db->get_data_object($query);
        // set variable
        $pagination_count = $count_data[0]->count;

        $number_of_pages = ceil($pagination_count / $pagination_length);

        // prevent errors if input number is bad
        if ($pagination_current > $number_of_pages) {
            $pagination_current = $number_of_pages;
        } else if ($pagination_current < 1) {
            $pagination_current = 1;
        }

        $pagination_offset = ($pagination_current != 1) ? ($pagination_length * ($pagination_current - 1)) : 0;

        // search results
        if (isset($vce->user_search_results) && !empty($vce->user_search_results)) {
        
			$users = $vce->user->find_users(array('user_ids' => implode(',', json_decode($vce->user_search_results, true))), true, true, false);
			$pagination = false;
        	$sort_by = null;
        	
        	$users_list = array_keys($users);
        	
		} else if (isset($edit_user)) {
		
			$users = $vce->user->find_users(array('user_ids' => $edit_user), true, true, false);
			$pagination = false;
       		$sort_by = null;
       		
       		$users_list = array_keys($users);
       		
       		 
		} else {
			
            // towards the standard way
            // with role_id filter
            if (!empty($filter_by)) {
                $query = "SELECT * FROM " . TABLE_PREFIX . "users";
                $pagination = false;
                $sort_by = null;
            } else if ($sort_by == 'user_id' || $sort_by == 'role_id') {
                // if user_id or role_id is the sort
                $query = "SELECT user_id FROM " . TABLE_PREFIX . "users WHERE role_id IN (" . implode(',', $role_id_in) . ") ORDER BY $sort_by " . $sort_direction . " LIMIT " . $pagination_length . " OFFSET " . $pagination_offset;
            } else {
                // the standard way
                $query = "SELECT " . TABLE_PREFIX . "users.user_id FROM " . TABLE_PREFIX . "users_meta INNER JOIN " . TABLE_PREFIX . "users ON " . TABLE_PREFIX . "users_meta.user_id = " . TABLE_PREFIX . "users.user_id WHERE " . TABLE_PREFIX . "users.role_id IN (" . implode(',', $role_id_in) . ") AND " . TABLE_PREFIX . "users_meta.meta_key='" . $sort_by . "' GROUP BY " . TABLE_PREFIX . "users_meta.user_id, " . TABLE_PREFIX . "users_meta.minutia ORDER BY " . TABLE_PREFIX . "users_meta.minutia " . $sort_direction . " LIMIT " . $pagination_length . " OFFSET " . $pagination_offset;
            }
            
            $current_list = $vce->db->get_data_object($query);
            
            foreach ($current_list as $each_item) {
            	$users_list[] = $each_item->user_id;
            }
            
            $users = $vce->user->find_users(array('user_ids' => implode(',', $users_list)), true, true, false);
		
		}
		
		
        /* start temporary normalization of created_at and updated_at, added on 5/15/2020 */
        
        $normalize = false;
        
        foreach ($users as $each_user_record) {
			if (!isset($each_user_record['created_at'])) {
				 $normalize = true;
				 break;
			}
        }
        
        if ($normalize) {
        
			// the instructions to pass through the form
			$dossier = array(
				'type' => 'ManageUsers',
				'procedure' => 'normalize',
			);

			// add dossier, which is an encrypted json object of details uses in the form
			$dossier_for_normalize = $vce->generate_dossier($dossier);	
		
			$normaize = <<<EOF
<p>
<form id="normalize-users" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_normalize">
<input type="submit" value="Click here to update all users with created_at and updated_at records">
</form>
</p>
EOF;

			$vce->content->add('main', $normaize);
		
		}
		
		/* start temporary normalization */

        /* start user edit */

        // we want to edit this user
        // check permissions for edit users
        if (isset($edit_user) && $this->check_permissions('edit_users')) {

            // get user info and cast as an object
            $user = (object) $users[$edit_user];
    
    		$user_email = $user->email;
    		$user_role_id = $user->role_id;
        
       		$user_edit = null;
       		$update_password = null;
       		
            if (!isset($user_attributes['password']) || !isset($user_attributes['password']['type']) || $user_attributes['password']['type'] != 'conceal') {	

				$dossier = array(
				'type' => 'ManageUsers',
				'procedure' => 'update',
				'user_id' => $edit_user,
				'email' => $user_email,
				'role_id' => $user_role_id
				);

				// create a special dossier
				$dossier_for_password = $vce->generate_dossier($dossier);	
				
               	// create the dossier
                $dossier_for_generate_password = $vce->generate_dossier(array('type' => 'ManageUsers', 'procedure' => 'generate_password'));

				// password input
				$input = array(
				'type' => 'text',
				'name' => 'password',
				'class' => 'password-input',
				'data' => array(
					'tag' => 'required',
					'placeholder' => 'Enter Password'
				)
				);
		
				$password_input = $vce->content->create_input($input,'Enter A New Password','Enter Your Password');

				// password input
				$input = array(
				'type' => 'text',
				'name' => 'password2',
				'class' => 'password-input',
				'data' => array(
					'tag' => 'required',
					'placeholder' => 'Repeat Password'
				)
				);
		
				$password2_input = $vce->content->create_input($input,'Repeat New Password','Repeat Password');

				$password_update = <<<EOF
<form id="password" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_password">
$password_input
$password2_input
<input type="submit" value="Update"> <div id="generate-password" class="link-button"  dossier="$dossier_for_generate_password" action="$vce->input_path">Generate Password</div> <div class="link-button cancel-button">Cancel</div>
</form>
EOF;
       		
       		
       			$update_password = '<p>' . $vce->content->accordion('Update Password For This User', $password_update) . '</p>';
       		
       		
       		}
       		
            // create the dossier
            $dossier_for_update = $vce->generate_dossier(array('type' => 'ManageUsers', 'procedure' => 'update', 'user_id' => $edit_user));

			$email = $vce->content->create_input($user_email,'Email');

            $user_edit .= <<<EOF
$update_password
<form id="form" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_update">
$email
EOF;

            // attributes
			$user_edit .= user::user_attributes_fields($user, $vce);

            // load hooks
            if (isset($vce->site->hooks['manage_users_attributes'])) {
                foreach ($vce->site->hooks['manage_users_attributes'] as $hook) {
                     $user_edit .= call_user_func($hook, $user);
                }
            }
            
            // site roles
			$role_options = array();
			foreach ($roles_hierarchical as $roles_each) {
				foreach ($roles_each as $key => $value) {
					if ($value['role_hierarchy'] >= $roles[$vce->user->role_id]['role_hierarchy']) {
						$role_options[] = array('name' => $value['role_name'],'value' => $key );
					}
				}
			}
			
			// email input
			$input = array(
			'type' => 'select',
			'name' => 'role_id',
			'value' => $user->role_id,
			'data' => array(
				'tag' => 'required',
			),
			'options' => $role_options
			);
	
			$role_id = $vce->content->create_input($input,'Site Role','Enter A Site Role');

            $user_edit .= <<<EOF
$role_id
<input type="submit" value="Update User">
<div class="link-button cancel-button">Cancel</div>
</form>
EOF;

		$content .= $vce->content->accordion('Update An Existing User', $user_edit, true, true);

            /* end user edit */
        } else {
            /* start of new user */

            // check permissions for create users
            if ($this->check_permissions('create_users')) {
            
            	$create_users = null;

                // create the dossier
                $dossier_for_create = $vce->generate_dossier(array('type' => 'ManageUsers', 'procedure' => 'create'));

                // create the dossier
                $dossier_for_generate_password = $vce->generate_dossier(array('type' => 'ManageUsers', 'procedure' => 'generate_password'));

				// email input
				$input = array(
				'type' => 'text',
				'name' => 'email',
				'data' => array(
					'tag' => 'required'
				)
				);
		
				$email_input = $vce->content->create_input($input,'Email','Enter Email');

                $create_users .= <<<EOF
<form id="form" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_create">
$email_input
EOF;

                if (isset($user_attributes['password']['type']) && $user_attributes['password']['type'] == 'conceal') {

                    $password = $vce->user->generate_password();

                    $create_users .= <<<EOF
<input type="hidden" name="password" value="$password">
EOF;

                } else {
                
					// email input
					$input = array(
					'type' => 'text',
					'name' => 'password',
					'data' => array(
						'tag' => 'required'
					)
					);
		
					$password_input = $vce->content->create_input($input,'Password','Enter Password');                

                    // the standard user create form with password input

                    $create_users .= <<<EOF
$password_input
EOF;

                }
                
                // attributes
				$create_users .= user::user_attributes_fields(null, $vce, false);

                // load hooks
                if (isset($vce->site->hooks['manage_users_attributes'])) {
                    foreach ($vce->site->hooks['manage_users_attributes'] as $hook) {
                        $create_users .= call_user_func($hook, $content);
                    }
                }
                
                $role_options = array();
                foreach ($roles_hierarchical as $roles_each) {
                    foreach ($roles_each as $key => $value) {
                        if ($value['role_hierarchy'] >= $roles[$vce->user->role_id]['role_hierarchy']) {
                        	$role_options[] = array('name' => $value['role_name'],'value' => $key );
                        }
                    }
                }
                
				// role input
				$input = array(
				'type' => 'select',
				'name' => 'role_id',
				'data' => array(
					'tag' => 'required',
				),
				'options' => $role_options
				);
		
				$role_id = $vce->content->create_input($input,'Site Role','Enter A Site Role');
			
                $create_users .= <<<EOF
$role_id
<input type="submit" value="Create User">
EOF;

                if (!isset($user_attributes['password']) || !isset($user_attributes['password']['type']) || $user_attributes['password']['type'] != 'conceal') {

                    $create_users .= <<<EOF
<div id="generate-password" class="link-button" dossier="$dossier_for_generate_password" action="$vce->input_path">Generate Password</div>
EOF;

                }

                $create_users .= <<<EOF
<div class="link-button cancel-button">Cancel</div>
</form>
EOF;

				$content .= $vce->content->accordion('Create A New User', $create_users);


            }
            
            /* end of new user */
            
            /* start merge user */
            
            // check permissions for create users
            if ($this->check_permissions('merge_users')) {
            
				// the instructions to pass through the form
				$dossier = array(
				'type' => $each_component->type,
				'procedure' => 'merge'
				);

				// generate dossier
				$dossier_for_merge = $vce->generate_dossier($dossier);
			
				$form_elements = null;
			
				$input = array(
				'type' => 'text',
				'name' => 'merge_from',
				'data' => array(  
					'tag' => 'required',
				),
				'placeholder' => 'From'
				);
			
				$form_elements .= $vce->content->create_input($input,'Merge From User_id','Enter Merge From User_id');

			
				$input = array(
				'type' => 'text',
				'name' => 'merge_to',
				'data' => array(  
					'tag' => 'required',
				),
				'placeholder' => 'To'
				);
	
				$form_elements .= $vce->content->create_input($input,'Merge To User_id','Enter Merge To User_id');

				$form = <<<EOF
<form id="create_items" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_merge">
$form_elements
<input type="submit" value="Merge">
<button class="link-button cancel-button">Cancel</button>
</form>
EOF;
            
            	$content .= $vce->content->accordion('Merge User Accounts', $form);
             
            }
             
            /* end of merge user */ 

            /* start filtering */

            // the instructions to pass through the form
            $dossier = array(
                'type' => 'ManageUsers',
                'procedure' => 'filter',
            );

            // add dossier, which is an encrypted json object of details uses in the form
            $dossier_for_filter = $vce->generate_dossier($dossier);

			$filter = null;
			
	            $role_options = array();
	            $role_options[] = array('name' => null,'value' => null);
                foreach ($roles_hierarchical as $roles_each) {
                    foreach ($roles_each as $key => $value) {
                        if ($value['role_hierarchy'] >= $roles[$vce->user->role_id]['role_hierarchy']) {
                        	$role_options[] = array('name' => $value['role_name'],'value' => $key );
                        }
                    }
                }
			
				// role input
				$input = array(
				'type' => 'select',
				'name' => 'role_id',
				'value' => (isset($vce->filter_by_role_id) ? $vce->filter_by_role_id : null),
				'data' => array(
				'class' => 'filter-form'
				),
				'options' => $role_options
				);
		
				$role_id = $vce->content->create_input($input,'Filter By Site Roles');
			

            $filter .= <<<EOF
$role_id
EOF;

            // load hooks
            if (isset($vce->site->hooks['manage_users_attributes_filter'])) {
                foreach ($vce->site->hooks['manage_users_attributes_filter'] as $hook) {
                    $filter .= call_user_func($hook, $filter_by, $content, $vce);
                }
            }

            $filter .= <<<EOF
<div class="filter-form-submit link-button" dossier="$dossier_for_filter" action="$vce->input_path" pagination="1">Filter</div>
<button class="link-button cancel-button">Cancel</button>
EOF;

			$content .= $vce->content->accordion('Filter', $filter, (!empty($vce->filter_by_role_id) ? true : (!empty($filter_by) ? true : false)));


            /* end filtering */
            
            
            /* start search */

            // dossier for search
            $dossier = array(
            	'type' => 'ManageUsers',
                'procedure' => 'search',
            );

            // generate dossier
            $dossier_for_search = $vce->generate_dossier($dossier);

            $input_value = isset($vce->search_value) ? $vce->search_value : null;

			$search_for_users = null;

            if (isset($vce->user_search_results) && empty($vce->user_search_results)) {

                $search_for_users .= <<<EOF
<div class="form-message form-error">No Matches Found</div>
EOF;

            }
            
			// email input
			$input = array(
			'type' => 'text',
			'name' => 'search',
			'value' => $input_value,
			'data' => array(
				'tag' => 'required',
			)
			);
	
			$search_input = $vce->content->create_input($input,'Search For Users (3 Character Minimum)','Searching For Someone?');
    
            $search_for_users .= <<<EOF
<form id="search-users" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_search">
$search_input
<input type="submit" value="Search">
<button class="link-button cancel-button">Cancel</button>
</form>
EOF;


			$content .= $vce->content->accordion('Search For Users', $search_for_users, (!empty($input_value) ? true : false));


            /* end search */
            

        }

        // check if display_users is true
        if ($display_users) {
        
			$user_list = null;

            // the instructions to pass through the form
            $dossier = array(
                'type' => 'ManageUsers',
                'procedure' => 'pagination',
            );

            // add dossier, which is an encrypted json object of details uses in the form
            $dossier_for_pagination = $vce->generate_dossier($dossier);

            $pagination_previous = ($pagination_current > 1) ? $pagination_current - 1 : 1;
            $pagination_next = ($pagination_current < $number_of_pages) ? $pagination_current + 1 : $number_of_pages;

            if ($pagination) {

                $user_list .= <<<EOF
<div class="pagination">
<div class="pagination-controls">
<div class="pagination-button link-button" pagination="1" sort="$sort_by" direction="$sort_direction" dossier="$dossier_for_pagination" action="$vce->input_path">&#124;&#65124;</div>
<div class="pagination-button link-button" pagination="$pagination_previous" sort="$sort_by" direction="$sort_direction" dossier="$dossier_for_pagination" action="$vce->input_path">&#65124;</div>
<div class="pagination-tracker">
Page <input class="pagination-input no-label" type="text" name="pagination" value="$pagination_current" sort="$sort_by" direction="$sort_direction" dossier="$dossier_for_pagination" action="$vce->input_path"> of $number_of_pages
</div>
<div class="pagination-button link-button" pagination="$pagination_next" sort="$sort_by" direction="$sort_direction" dossier="$dossier_for_pagination" action="$vce->input_path">&#65125;</div>
<div class="pagination-button link-button" pagination="$number_of_pages" sort="$sort_by" direction="$sort_direction" dossier="$dossier_for_pagination" action="$vce->input_path">&#65125;&#124;</div>
</div>
</div>
EOF;

            }

            $user_list .= <<<EOF
<table class="table-style">
<thead>
<tr>
<th></th>
EOF;

            // load hooks
            if (isset($vce->site->hooks['manage_users_attributes_list'])) {
                $user_attributes_list = array();
                foreach ($vce->site->hooks['manage_users_attributes_list'] as $hook) {
                    $user_attributes_list = call_user_func($hook, $user_attributes_list);
                }
                foreach ($user_attributes_list as $each_attribute_key => $each_attribute_value) {
                    if (!is_array($each_attribute_value)) {
                        $attributes[$each_attribute_value] = array(
                            'title' => $each_attribute_value,
                            'sortable' => 1,
                        );
                    } else {
                        $attributes[$each_attribute_key] = $each_attribute_value;
                    }
                }
            }

            foreach ($attributes as $each_attribute_key => $each_attribute_value) {

                // if conceal is set, as in the case of password, skip to next
                if (isset($each_attribute_value['type']) && $each_attribute_value['type'] == 'conceal') {
                    continue;
                }

                $nice_attribute_title = ucwords(str_replace('_', ' ', $each_attribute_key));

                if ($each_attribute_key == $sort_by) {
                    if ($sort_direction == 'ASC') {
                        $sort_class = 'sort-icon sort-active sort-asc';
                        $direction = 'DESC';
                    } else {
                        $sort_class = 'sort-icon sort-active sort-desc';
                        $direction = 'ASC';
                    }
                    $th_class = 'current-sort';
                } else {
                    $sort_class = 'sort-icon sort-inactive';
                    $direction = 'ASC';
                    $th_class = '';
                }

                // dossier for search
                $dossier = array(
                    'type' => 'ManageUsers',
                    'procedure' => 'pagination',
                );

                // generate dossier
                $dossier_for_sort = $vce->generate_dossier($dossier);

                $user_list .= <<<EOF
<th class="$th_class">
$nice_attribute_title
EOF;

                // check if this is a sortable attribute
                if (isset($each_attribute_value['sortable']) && $each_attribute_value['sortable']) {

                    $user_list .= <<<EOF
<div class="$sort_class" dossier="$dossier_for_sort" sort="$each_attribute_key" direction="$direction" action="$vce->input_path" title="Sort By $nice_attribute_title"></div>
EOF;

                } else {

                    $user_list .= <<<EOF
<div class="sort-icon"></div>
EOF;

                }

                $user_list .= <<<EOF
</th>
EOF;

            }

            $user_list .= <<<EOF
</tr>
</thead>
<tbody>
EOF;

            // check permissions and assign values
            $edit_users = $this->check_permissions('edit_users') ? true : false;
            $masquerade_users = $this->check_permissions('masquerade_users') ? true : false;
            $suspend_users = $this->check_permissions('suspend_users') ? true : false;
            $delete_users = $this->check_permissions('delete_users') ? true : false;

            // prepare for filtering of roles limited by hierarchy
            if (!empty($filter_by)) {
                $role_hierarchy = array();
                // create a lookup array from role_name to role_hierarchy
                foreach ($roles as $roles_key => $roles_value) {
                    $role_hierarchy[$roles_key] = $roles_value['role_hierarchy'];
                }
            }
            
            // loop through users
            foreach ($users_list as $each_user) {

                // check if filtering is happening
                if (!empty($filter_by)) {
                    // loop through filters and check if any user fields are a match
                    foreach ($filter_by as $filter_key => $filter_value) {
                        // prevent roles hierarchy above this from displaying
                        if ($role_hierarchy[$users[$each_user]['role_id']] < $role_hierarchy[$vce->user->role_id]) {
                            continue 2;
                        }

                        if ($filter_key == "role_id") {
                            // make title of role
                            //    $filter_value = $roles[$filter_value]['role_name'];
                            if ($users[$each_user]['role_id'] != $filter_value) {
                                continue 2;
                            }

                            continue;
                        }
                        // check if $filter_value is an array
                        if (is_array($filter_value)) {
                            // check that meta_key exists for this user
                            if (!isset($users[$each_user][$filter_key])) {
                                continue 2;
                            }
                            // check if not in the array
                            if (!in_array($users[$each_user][$filter_key], $filter_value)) {
                                // continue foreach before this foreach
                                continue 2;
                            }
                        } else {
                            // doesn't match so continue
                            if (isset($users[$each_user][$filter_key])) {
                                if ($users[$each_user][$filter_key] != $filter_value) {
                                    // continue foreach before this foreach
                                    continue 2;
                                }
                            } else {
                                continue 2;
                            }
                        }
                    }
                }

                $user_list .= '<tr>';

                //$dossier_for_edit = $vce->user->encryption(json_encode(array('type' => 'ManageUsers','procedure' => 'edit','user_id' => $each_user)),$vce->user->session_vector);
                $dossier_for_edit = $vce->generate_dossier(array('type' => 'ManageUsers', 'procedure' => 'edit', 'user_id' => $each_user));

                //$dossier_for_masquerade = $vce->user->encryption(json_encode(array('type' => 'ManageUsers','procedure' => 'masquerade','user_id' => $each_user)),$vce->user->session_vector);
                $dossier_for_masquerade = $vce->generate_dossier(array('type' => 'ManageUsers', 'procedure' => 'masquerade', 'user_id' => $each_user));

                //$dossier_for_masquerade = $vce->user->encryption(json_encode(array('type' => 'ManageUsers','procedure' => 'masquerade','user_id' => $each_user)),$vce->user->session_vector);
                $dossier_for_status = $vce->generate_dossier(array('type' => 'ManageUsers', 'procedure' => 'account_status', 'user_id' => $each_user));

                //$dossier_for_delete = $vce->user->encryption(json_encode(array('type' => 'ManageUsers','procedure' => 'delete','user_id' => $each_user)),$vce->user->session_vector);
                $dossier_for_delete = $vce->generate_dossier(array('type' => 'ManageUsers', 'procedure' => 'delete', 'user_id' => $each_user));

                $user_list .= <<<EOF
<td class="align-center no-word-break">
EOF;

                if ($edit_users) {

                    $user_list .= <<<EOF
<form class="inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_edit">
<input type="hidden" name="sort_by" value="$sort_by">
<input type="hidden" name="sort_direction" value="$sort_direction">
<input type="hidden" name="pagination_current" value="$pagination_current">
<input type="submit" value="Edit">
</form>
EOF;

                }

                if ($masquerade_users) {

                    $user_list .= <<<EOF
<form class="inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_masquerade">
<input type="submit" value="Masquerade">
</form>
EOF;

                }
                
                if ($suspend_users) { 
                
                	$value = 'Suspend Account';
                	
                	if (isset($users[$each_user]['suspended'])) {
                	
                		$value = 'Activate  Account';
                	}
                
                    $user_list .= <<<EOF
<form class="inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_status">
<input type="submit" value="$value">
</form>
EOF;
                }

                if ($delete_users) {

                    $user_list .= <<<EOF
<form class="delete-form inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="submit" value="Delete">
</form>
EOF;

                }

                $user_list .= <<<EOF
</td>
EOF;

                foreach ($attributes as $each_attribute_key => $each_attribute_value) {
                

                    // exception for role_id, change to role_name
                    if ($each_attribute_key == 'role_id') {
                        $each_attribute_key = 'role_name';
                    }

                    // if conceal is set, as in the case of password, skip to next
                    if (isset($each_attribute_value['type']) && $each_attribute_value['type'] == 'conceal') {
                        continue;
                    }

                    // prevent error if not set
                    $attribute_value = isset($users[$each_user][$each_attribute_key]) ? $users[$each_user][$each_attribute_key] : null;

                    if (isset($each_attribute_value['datalist'])) {

                        if (isset($datalist_cache[$attribute_value])) {

                            // user saved value
                            $attribute_name = $datalist_cache[$attribute_value];

                        } else {

                            $datalist = $vce->get_datalist_items(array('item_id' => $attribute_value));

                            $attribute_name = isset($datalist['items'][$attribute_value]['name']) ? $datalist['items'][$attribute_value]['name'] : null;

                            // save it so we dont need to look up again
                            $datalist_cache[$attribute_value] = $attribute_name;

                        }

                        $attribute_value = $attribute_name;

                    }

                    $user_list .= '<td>' . stripslashes($attribute_value) . '</td>';

                }

                $user_list .= '</tr>';

            }

            $user_list .= <<<EOF
</tbody>
</table>
EOF;

			$content .= $vce->content->accordion('Users', $user_list, true, true, 'no-padding');

        }

        $vce->content->add('main', $content);

    }

    /**
     * Create a new user
     */
    public function create($input) {
    
    	$vce = $this->vce;
    	
    	// overloading prevention for role_id
    	$roles = json_decode($vce->site->roles, true);
    	
    	$current_user_role_hierarchy = $roles[$vce->user->role_id]['role_hierarchy'];
    	
    	// if the role_id does not exist, then retun an error
    	if (!isset($roles[$input['role_id']])) {
			echo json_encode(array('response' => 'error','procedure' => 'create','message' => "Invalid Role Id", 'form' => 'create'));
			return;
    	}
    	
    	$new_user_role_hierachy = $roles[$input['role_id']]['role_hierarchy'];
    	
    	// if the new role has a lower role_hierachy, return error
    	if ($new_user_role_hierachy < $current_user_role_hierarchy) {
			echo json_encode(array('response' => 'error','procedure' => 'create','message' => "Invalid Role Id", 'form' => 'create'));
			return;
    	}
    	
    	$response = $vce->user->create($input);
    	
    	$response['form'] = 'create';
    	
    	echo json_encode($response);
    	
    	return;
    }

    /**
     * edit user
     */
    public function edit($input) {

        // add attributes to page object for next page load using session
        $vce = $this->vce;

        $vce->site->add_attributes('edit_user', $input['user_id']);

        $pagination_current = filter_var($input['pagination_current'], FILTER_SANITIZE_NUMBER_INT);

        if ($pagination_current < 1) {
            $pagination_current = 1;
        }

        $vce->site->add_attributes('sort_by', $input['sort_by']);
        $vce->site->add_attributes('sort_direction', $input['sort_direction']);
        $vce->site->add_attributes('pagination_current', $pagination_current);

        echo json_encode(array('response' => 'success', 'message' => 'session data saved', 'form' => 'edit'));
        return;

    }

    /**
     * update user
     */
    public function update($input) {
    
    	$vce = $this->vce;
    	
    	// overloading prevention for role_id
    	$roles = json_decode($vce->site->roles, true);
    	
    	$current_user_role_hierarchy = $roles[$vce->user->role_id]['role_hierarchy'];
    	
    	// if the role_id does not exist, then retun an error
    	if (!isset($roles[$input['role_id']])) {
			echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Invalid Role Id", 'form' => 'create'));
			return;
    	}
    	
    	$new_user_role_hierachy = $roles[$input['role_id']]['role_hierarchy'];
    	
    	// if the new role has a lower role_hierachy, unset it to prevent setting
    	if ($new_user_role_hierachy < $current_user_role_hierarchy) {
    		unset($input['role_id']);
    	}
    	
    	$response = $vce->user->update($input);

    	$response['form'] = 'create';
    	
    	echo json_encode($response);
    	
    	return;

    }

    /**
     * Masquerade as user
     */
    public function masquerade($input) {

        $vce = $this->vce;

        // pass user id to masquerade as
        $vce->user->make_user_object($input['user_id']);

        echo json_encode(array('response' => 'success', 'message' => 'User masquerade', 'form' => 'masquerade', 'action' => $vce->site->site_url));
        return;

    }
    
    
    /**
     * Masquerade as user
     */
    public function account_status($input) {

        $vce = $this->vce;
        
		$user_data = $vce->user->find_users(array('user_ids' => $input['user_id']));
		
		if (isset($user_data[0])) {
			if (isset($user_data[0]->suspended)) {
				// if suspended, then remove meta_data
				$vce->user->remove_attributes('suspended', true, $input['user_id']);
				$message = 'User Activated';
			} else {
				// add suspended to meta_data
				$vce->user->add_attributes('suspended', 'true', true, $input['user_id']);
				$message = 'User Suspended';
			}
		}

        echo json_encode(array('response' => 'success', 'message' => $message, 'form' => 'create'));
        return;

    }

    /**
     * Delete a user
     */
    public function delete($input) {

		user::delete_user($input['user_id']);

        echo json_encode(array('response' => 'success', 'message' => 'User has been deleted', 'form' => 'delete', 'user_id' => $input['user_id'], 'action' => ''));
        return;

    }
    
     /**
     * Masquerade as user
     */
    public function merge($input) {
    
    	global $vce;
    	
    	$invalid_users = false;
    	
    	if (!empty($input['merge_from'])) {
    	 
    		$from_user = $vce->user->find_users(array('user_ids' => $input['merge_from']), false, true, true);
    		
    		if (empty($from_user) || count($from_user) > 1) {
    			$invalid_users = true;
    		}
    	
    	} else {
    		$invalid_users = true;
    	}

    	if (!empty($input['merge_to'])) {
    	 
    		$to_user = $vce->user->find_users(array('user_ids' => $input['merge_to']), false, true, true);
    		
    		if (empty($to_user)|| count($to_user) > 1) {
    			$invalid_users = true;
    		}
    	
    	} else {
    		$invalid_users = true;
    	}
    	
    	// if not valid users 
    	if ($invalid_users) {
			echo json_encode(array('response' => 'error','procedure' => 'merge','message' => "Invalid Users", 'form' => 'create'));
			return;
    	}
    	
		$attributes = null;    	

		foreach ($from_user[0] as $key=>$value) {
			if (!in_array($key, array('user_id','role_id','role_hierarchy','role_name','vector','hash','email','created_at','updated_at'))) {
				if (!empty($value) && empty($to_user[0]->$key)) {
					$attributes[$key] = $value;
				}
			}		
		}
	
		// merge user attributes
		if (!empty($attributes)) {
			$vce->user->add_attributes($attributes, true, $input['merge_to']);
		}
    	
		$query = "SELECT * FROM " . TABLE_PREFIX . "users_meta WHERE user_id=" . $input['merge_from'] . " AND meta_key='lookup'";
		$pseudonym = $vce->db->get_data_object($query);
		
		if (!empty($pseudonym)) {
		
			$query = "SELECT * FROM " . TABLE_PREFIX . "users_meta WHERE user_id='" . $input['merge_to'] . "' AND meta_key='pseudonym' AND minutia='" . $pseudonym[0]->meta_value . "'";
			$pseudonym_check = $vce->db->get_data_object($query);

			if (empty($pseudonym_check)) {
		
				// create encryption for from user email
				$pseudonym_email = $vce->site->encryption($from_user[0]->email, $to_user[0]->vector);
		
				$records[] = array(
				'user_id' => $input['merge_to'],
				'meta_key' => 'pseudonym',
				'meta_value' => $pseudonym_email,
				'minutia' => $pseudonym[0]->meta_value
				);
			
				$vce->db->insert('users_meta', $records);
			
			}

		}
		
		// merge all the components
		
		$query = "SELECT id FROM " . TABLE_PREFIX . "components_meta WHERE meta_key='created_by' AND meta_value='" . $input['merge_from'] . "'";
		$created_by = $vce->db->get_data_object($query);
		
		
		if (!empty($created_by)) {
			foreach ($created_by as $key=>$value) {
				$query = "UPDATE vce_components_meta SET meta_value='" . $input['merge_to'] . "' WHERE id='" . $value->id . "'";
				$vce->db->query($query);
			}
		}
		
		$search = '%|' . $input['merge_from'] . '|%';
		$query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE meta_value LIKE '" . $search . "'";
		$members = $vce->db->get_data_object($query);
		
		if (!empty($members)) {
			foreach ($members as $key=>$value) {
		
				$members_list = $value->meta_value;
				$members_list = str_replace('|' . $input['merge_from'] . '|','|' . $input['merge_to'] . '|', $members_list);

				// merging members lists
				$update = array('meta_value' => $members_list);
				$update_where = array('id' => $value->id);
				$vce->db->update('components_meta', $update, $update_where);
				
				$query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE component_id='" . $value->component_id . "' AND meta_key='pbc_roles'";
				$pbc_roles = $vce->db->get_data_object($query);
				
				if (!empty($pbc_roles)) {
					
					$pbc = json_decode($pbc_roles[0]->meta_value, true);
					
					if (isset($pbc[$input['merge_from']])) {
					
						$pbc[$input['merge_to']] = $pbc[$input['merge_from']];
						unset($pbc[$input['merge_from']]);
						
						// merging members lists
						$update = array('meta_value' => json_encode($pbc));
						$update_where = array('id' => $pbc_roles[0]->id);
						$vce->db->update('components_meta', $update, $update_where);
						
					}
				
				
				}

			}
		}
		
		$query = "SELECT * FROM " . TABLE_PREFIX . "datalists WHERE user_id=" . $input['merge_from'];
		$datalists = $vce->db->get_data_object($query);
		
		$members_from = array();
		$members_datalist_from = array();
		
		if (!empty($datalists)) {
			foreach ($datalists as $key=>$value) {
		
				$datalist_value = $vce->get_datalist(array('datalist_id' => $value->datalist_id));
				
				//$vce->dump('merge_from');
				//$vce->dump($datalist_value);
			
				if (!empty($datalist_value)) {
					foreach ($datalist_value as $each) {
						if (!empty($each['members'])) {
							// make members into an array
							$members_from[$each['datalist']] = explode('|', trim($each['members'],'|'));
						}
						$members_datalist_from[$each['datalist']] = $each['datalist_id'];
					}
				}
		
			}
		}
		
		$query = "SELECT * FROM " . TABLE_PREFIX . "datalists WHERE user_id=" . $input['merge_to'];
		$datalists = $vce->db->get_data_object($query);
		
		$members_to = array();
		$members_datalist_to = array();
		
		if (!empty($datalists)) {
			foreach ($datalists as $key=>$value) {
		
				$datalist_value = $vce->get_datalist(array('datalist_id' => $value->datalist_id));

				//$vce->dump('merge_to');
				//$vce->dump($datalist_value);
			
				if (!empty($datalist_value)) {
					foreach ($datalist_value as $each) {
						if (!empty($each['members'])) {
							// make members into an array
							$members_to[$each['datalist']] = explode('|', trim($each['members'],'|'));
						}
						$members_datalist_to[$each['datalist']] = $each['datalist_id'];
					}
				}
			
			}
		
		}
		
		if (!empty($members_datalist_from)) {
			foreach ($members_datalist_from as $key=>$value) {
			
				//check for members value in from record
				if (isset($members_from[$key])) {
		
					// does it exist in the to list?
					if (isset($members_datalist_to[$key])) {
					
						if (!empty($members_from[$key])) {
					
							if (!empty($members_to[$key])) {

								$member_ids = array_merge($members_from[$key], $members_to[$key]);
						
							} else {
						
								$member_ids = $members_from[$key];
						
							}
							
							
							sort($member_ids);
							$members = '|' . implode('|', array_unique($member_ids)) . '|';

							$attributes = array(
							'datalist_id' => $members_datalist_to[$key],
							'meta_data' => array('members' => $members)
							);
						
							$vce->update_datalist($attributes);
						
						}

					} 
		
				} else {
					// non-members records
					
					// if it doesn't exist, then change user_id in datalist
					if (!isset($members_datalist_to[$key])) {
					
						// $vce->dump($members_datalist_from[$key]);
						
						$attributes = array(
						'datalist_id' => $members_datalist_from[$key],
						'relational_data' => array('user_id' => $input['merge_to'])
						);
						
						//$vce->dump($attributes);
						
						$vce->update_datalist($attributes);
						
					}
					
				}
	
			}
		}
		
		// finally, delete the from user
		user::delete_user($input['merge_from']);
		
        echo json_encode(array('response' => 'success', 'message' =>'Merge complete from ' . $from_user[0]->email . ' to ' . $to_user[0]->email, 'form' => 'create'));
        return;
		
    }
    
    
    
   /**
     * generate_password
     */
    public function generate_password($input) {

 		$vce = $this->vce;
 		
 		$generate_password = $vce->user->generate_password();
 		
        echo json_encode(array('response' => 'success', 'message' => $generate_password));
        return;

    }

    /**
     * Filter
     */
    public function filter($input) {

 		$vce = $this->vce;

        foreach ($input as $key => $value) {
            if (strpos($key, 'filter_by_') !== FALSE) {
                $vce->site->add_attributes($key, $value);
            }
        }

        $vce->site->add_attributes('pagination_current', $input['pagination_current']);

        echo json_encode(array('response' => 'success', 'message' => 'Filter'));
        return;

    }

    /**
     * pagination users
     */
    public function pagination($input) {

        // add attributes to page object for next page load using session
 		$vce = $this->vce;

        $pagination_current = filter_var($input['pagination_current'], FILTER_SANITIZE_NUMBER_INT);

        if ($pagination_current < 1) {
            $pagination_current = 1;
        }

        $vce->site->add_attributes('sort_by', $input['sort_by']);
        $vce->site->add_attributes('sort_direction', $input['sort_direction']);
        $vce->site->add_attributes('pagination_current', $pagination_current);

        echo json_encode(array('response' => 'success', 'message' => 'pagination'));
        return;

    }

    /**
     * search for a user
     */
    public static function search($input) {
    
    	// not an object at this location
        global $vce;
        
        if (!isset($input['search']) || strlen($input['search']) < 3) {
            // return a response, but without any results
            echo json_encode(array('response' => 'success', 'results' => null));
            return;
        }
        
       	$all_users = $vce->user->search($input['search']);
       	
       	if (empty($all_users)) {
            return;
       	}

        // hook to work with search results
        if (isset($vce->site->hooks['manage_users_attributes_search'])) {
            foreach ($vce->site->hooks['manage_users_attributes_search'] as $hook) {
            	$all_users = call_user_func($hook, $all_users);
            }
        }

        if (count($all_users)) {

            $user_keys = array_keys($all_users);

            $vce->site->add_attributes('search_value', $input['search']);
            $vce->site->add_attributes('user_search_results', json_encode($user_keys));

            echo json_encode(array('response' => 'success', 'form' => 'edit'));
            return;
        }

        $vce->site->add_attributes('search_value', $input['search']);
        $vce->site->add_attributes('user_search_results', null);
	
        echo json_encode(array('response' => 'success', 'form' => 'edit'));
        return;

    }
    
    /**
     * normalize
     */
    public function normalize($input) {
    
		$vce = $this->vce;
    
		// get the oldest created at record
		$query = "SELECT MIN(meta_value) as first FROM " . TABLE_PREFIX . "components_meta WHERE meta_key='created_at'";
		$earliest = $vce->db->get_data_object($query, false);
	
		// get all the users
		$query = "SELECT user_id FROM " . TABLE_PREFIX . "users";
		$all_users = $vce->db->get_data_object($query, false);
		
		// get any users that already have a created_at record
		$query = "SELECT * FROM " . TABLE_PREFIX . "users_meta WHERE meta_key='created_at'";
		$records = $vce->db->get_data_object($query, false);
		
		// rekey
		foreach ($records as $each) {
			$has_created_record[$each['user_id']] = $each['meta_value'];
		}
		
		// get any users that already have a created_at record
		$query = "SELECT * FROM " . TABLE_PREFIX . "users_meta WHERE meta_key='updated_at'";
		$records = $vce->db->get_data_object($query, false);
		
		// rekey
		foreach ($records as $each) {
			$has_updated_record[$each['user_id']] = $each['meta_value'];
		}
		
		// fetch requested component by component_id
		$query = "SELECT count(*) as count, MIN(a.meta_value) as timestamp, b.meta_value as user_id FROM " . TABLE_PREFIX . "components_meta as a LEFT JOIN " . TABLE_PREFIX . "components_meta as b ON a.component_id=b.component_id WHERE a.meta_key='created_at' AND b.meta_key='created_by' GROUP BY b.meta_value";
		$request = $vce->db->get_data_object($query, false);
		
		// rekey
		$users = array();
		foreach ($request as $each) {
			if (!empty($each['user_id'])) {
				$users[$each['user_id']] = $each['timestamp'];
			}
		}
		
		// a default to start off the process, which is the oldest created_at record found
		$previous = $earliest[0]['first'];
		$current = $earliest[0]['first'];
		$backfill = array();
		
		// cycle and fill in blanks with a previous record
		foreach ($all_users as $key_user=>$each_user) {
		
			if (isset($users[$each_user['user_id']])) {
		
				// $vce->dump($users[$each_user['user_id']]);
				$current = $users[$each_user['user_id']];
			
				foreach ($backfill as $each_record) {
					$users[$each_record] = ($previous < $current) ? $previous : $current;
				}
			
				$backfill = array();
				$previous = $current;
		
			} else {
		
				$backfill[] = $each_user['user_id'];
			
			}
			
		}
		
		// clean-up
		if (!empty($backfill)) {
			foreach ($backfill as $each_record) {
				$users[$each_record] = $current;
			}
		}
		
		// write the data
		
		foreach ($users as $user_id=>$created_at) {
		
		 	$records = array();
		
			// if this has a record, skip
			if (!isset($has_created_record[$user_id])) {

				// add created_at
				$records[] = array(
					'user_id' => $user_id,
					'meta_key' => 'created_at',
					'meta_value' => $created_at,
					'minutia' => null
				);

			}
			
			if (!isset($has_updated_record[$user_id])) {
			
				// add updated_at
				$records[] = array(
					'user_id' => $user_id,
					'meta_key' => 'updated_at',
					'meta_value' => $created_at,
					'minutia' => null
				);

			}

			if (!empty($records)) {
       			$vce->db->insert('users_meta', $records);
       		}		
			
		}
		
        echo json_encode(array('response' => 'success', 'message' => 'All users now have a created_at and updated_at record', 'form' => 'create'));
        return;
		
	}
    
}