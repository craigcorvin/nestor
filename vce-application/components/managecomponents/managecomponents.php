<?php
/**
 * ManageComponents Component.
 *
 * @package Components
 */

/**
 * ManageComponents Class.
 */
class ManageComponents extends Component {

    /**
     * basic info about the component
     */
    public function component_info() {
        return array(
            'name' => 'Manage Components',
            'description' => 'Activate, disable and remove componets.',
            'category' => 'admin',
            'recipe_fields' => array('auto_create','title',array('url' => 'required'))
        );
    }
    
    /**
     *
     */
    public function as_content($each_component, $vce) {

        // add javascript to page
        $vce->site->add_script(dirname(__FILE__) . '/js/script.js', 'jquery-ui');

        // add javascript to page
        $vce->site->add_style(dirname(__FILE__) . '/css/style.css', 'manage-components-style');

        // get currently installed components
        $installed_components = json_decode($vce->site->installed_components, true);
		
		// variable to check if a component has disapeared from the server but is still in the database
		$installed_checker = $installed_components;
		$minions = null;
		
		$warning_message = null;

        // get currently activated components
        $activated_components = json_decode($vce->site->activated_components, true);
        
        // get currently activated components
        $preloaded_components = json_decode($vce->site->preloaded_components, true);

        $preloaded_checker = array();

        // get path_routing
        $path_routing = isset($vce->site->path_routing) ? json_decode($vce->site->path_routing, true) : array(null);
        
        $routing_checker = array();
        
        $routing_counter = 0;

        // array for sorting
        $categories = array();
        
        $content = null;
        
        $content .= '<div class="list-container"><div class="component-list">';

        // create dossier values for edit and delete
        $dossier_for_edit = $vce->generate_dossier(array('type' => 'ManageComponents', 'procedure' => 'update'));

        $dossier_for_uninstall = $vce->generate_dossier(array('type' => 'ManageComponents', 'procedure' => 'uninstall'));

        $dossier_for_delete = $vce->generate_dossier(array('type' => 'ManageComponents', 'procedure' => 'delete'));

        $components_list = array();

        // get all installed components
        foreach (array('vce-content', 'vce-application') as $components_dir) {

            $directory_itor = new RecursiveDirectoryIterator(BASEPATH . $components_dir . '/' .'components' . '/');
            $filter_itor = new RecursiveCallbackFilterIterator($directory_itor, function ($current, $key, $iterator) {

                // Skip hidden files and directories.
                if ($current->getFilename()[0] === '.') {
                    return FALSE;
                }
				if ($current->isDir()) {
					// do not decend into folders that start with a ~ or librar...
					// more could be added
					// css|js|image.*
					if (preg_match("/^(~.+|librar.*)/i",$current->getFilename())) {
						return false;
					}
					return true;
                } else {
                    // Only consume .php files that are in a directory of the same name.
                    $ok = fnmatch("*.php", $current->getFilename());

                    // WINDOWS fix.  use all /
                    $path = str_replace('\\', '/', $current->getPathname());
                    $dirs = explode('/',$path);
                    $ok = $ok && (($dirs[count($dirs) - 2] . '.php') === $current->getFilename());
                    return $ok;
                }
            });
            $itor = new RecursiveIteratorIterator($filter_itor);

            foreach ($itor as $each_component) {

                // Strip BASEPATH from this full path, since we add BASEPATH back in code below.
                $component_path = str_replace(BASEPATH, "", $each_component->getPathname());

                // WINDOWS fix.  use all /
                $component_path = str_replace('\\', '/', $component_path);

                // get the file content to search for Child Class name
                // file_get_contents(): Passing null to parameter #2 ($use_include_path) of type bool is deprecated 
                $component_text = file_get_contents(BASEPATH . $component_path, false, null, 0, 2000);

                // looking for Child Class name
                $pattern = "/class\s+([([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+)\s+extends\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+)\s+{/m";

                // continue if child class not found
                if (!preg_match($pattern, $component_text, $matches)) {
                    continue;
                }
                
                if (isset($matches[1]) && isset($matches[2])) {

                    // Class name for component
                    $type = $matches[1];
                    $parent = $matches[2];

                    // check if class already exists, and if it does, then skip ahead
                    if (isset($components_list[$type])) {
                        continue;
                    }
                    
                    if ($parent != "Component") {

						// if a minion, then check if the Component they are associated with is active
                    	if (strpos($parent,'Type') || strpos($parent,'type')) {
                    	
							// prevent errors is parent class listed in extends is not loaded
							if (!class_exists($parent)) {
								continue;
							}
                    	
							$parent = str_replace('Type','',$parent);
							if (!isset($activated_components[$parent])) {
								unset($installed_checker[$type]);
								continue;
							}
                    	}
                    }
                    
                    
                    // what type of methods does this class have?
                    $method_info = null;
                    
                    // create an instance of the Class
            		$current_component = $vce->page->instantiate_component(array('type' => $type), $vce, $component_path);  

					if (isset($current_component->type)) {
					
						$class_methods = new ReflectionClass($current_component);
					
						foreach ($class_methods->getMethods() as $each_method) {

							$constructor = new ReflectionMethod($current_component, $each_method->name);
					
							if ($constructor->class != 'Component') {
							
								/*
								
								// here is the code to alert the preload_component change in the future
								if ($each_method->name == 'preload_component') {
									$preload_component = $current_component->preload_component();
									if (!empty($preload_component)) {
										foreach ($preload_component as $each_preload_component) {
											if (!is_array($each_preload_component) && strpos($each_preload_component, '::') !== false)  {
												$warning_message .= '<div>UPDATE ' . $current_component->type . '->preload_component() FROM  class::method TO array(class,method)</div>';
												break;
											} 
										}
									}
								}
								*/
					
								$parameters = $constructor->getParameters();
								if (isset($parameters[0]->name) && $parameters[0]->name == 'input') {
								
									if ($each_method->name == 'create') {
										$method_info = '<div style="color:#900;">create: ' . $current_component->type . '->' . $each_method->name . '</div>';
										//$warning_message .= '<div>UPDATE STATIC CALL: self::create_component($input) to $this->create_component($input) IN ' . $current_component->type . '->' . $each_method->name . '</div>';
									}
									
									if ($each_method->name == 'update') {
										$method_info = '<div style="color:#900;">update: ' . $current_component->type . '->' . $each_method->name . '</div>';
										// $warning_message .= '<div>UPDATE STATIC CALL: self::update_component($input) to $this->update_component($input) IN ' . $current_component->type . '->' . $each_method->name . '</div>';
									}
									
									if ($each_method->name == 'delete') {
										$method_info = '<div style="color:#900;">delete: ' . $current_component->type . '->' . $each_method->name . '</div>';
										//$warning_message .= '<div>UPDATE STATIC CALL: self::create_component($input) to $this->create_component($input) IN ' . $current_component->type . '->' . $each_method->name . '</div>';
									}
									
									if ($each_method->name == 'create_component') {
										$method_info = '<div style="color:#900;">create_component: ' . $current_component->type . '->' . $each_method->name . '</div>';
										//$warning_message .= '<div>*UPDATE STATIC CALL: create_component($input) PUBLIC IN ' . $current_component->type . '->' . $each_method->name . '</div>';
									}
					 
									if ($constructor->isStatic()) {
										$method_info .= '<div>Static: ' . $current_component->type . '->' . $each_method->name . '</div>';
										// $warning_message .= '<div>Static: ' . $current_component->type . '->' . $each_method->name . '</div>';
									}
						
									if ($constructor->isProtected()) {
										$method_info .= '<div>Protected: ' . $current_component->type . '->' . $each_method->name . '</div>';
										// $warning_message .= '<div>UPDATE METHOD TO PUBLIC : Protected: ' . $current_component->type . '->' . $each_method->name . '</div>';
									}
						
									if ($constructor->isPrivate()) {
										$method_info .= '<div>Private: ' . $current_component->type . '->' . $each_method->name . '</div>';
										//$warning_message .= '<div>UPDATE METHOD TO PUBLIC : Private: ' . $current_component->type . '->' . $each_method->name . '</div>';
									}
									
									if ($constructor->isPublic()) {
										$method_info .= '<div>Public: ' . $current_component->type . '->' . $each_method->name . '</div>';
										//$warning_message .= '<div>UPDATE METHOD TO PUBLIC : Private: ' . $current_component->type . '->' . $each_method->name . '</div>';
									}
					
								}
					
							}
					
						}

					}
					
					if (!empty($method_info)) {
	                    $method_info = '<div style="text-decoration:underline;">Procedural Methods using ($input)</div>' . $method_info;
					}
					
					
                    // add type to list to check against later
                    $components_list[$type] = true;

                    // get compontent info, such as name and description
                    $info = $current_component->component_info();
                    
                    // check preload_component
                    if (is_subclass_of($current_component,'Component')) {
                    
						$preload_component = $current_component->preload_component();
				   
						if (!empty($preload_component)) {
							$method_info .= '<br><div><div style="text-decoration:underline;">preload_component()</div><pre>' . print_r($preload_component, true) . '</pre></div>';
						}
                    
                    }

					// $category = str_replace(' ' , '-', strtolower($info['category']));
					
					$category = preg_replace('/[^A-Za-z0-9]/', '-', strtolower($info['category']));

                    // add category to array for sorting
                    $categories[$category] = $info['category'];

                    $content .= '<div class="all-components each-component ' . $category . '-component" type="' . $type . '" parent="' . $parent . '" url="' . $component_path . '" state="';

                    if (isset($activated_components[$type])) {
                        $content .= 'activated';
                    } else {
                        $content .= 'disabled';
                    }

                    $content .= '">';

                    $content .= '<div class="each-component-switch"><div style="height:5px;width:100%;background-color:#' . substr(dechex(crc32($type)),- 6) . '"></div><div class="switch activated';

                    if (isset($activated_components[$type])) {
                        $content .= ' highlight';
                    }

                    if (!isset($installed_components[$type])) {
                        $content .= ' install';
                    }

                    $content .= '">';

                    if (!isset($installed_components[$type])) {
                        $content .= 'Install';
                    } else {
                        $content .= 'Activated';
                    }

                    $content .= '</div><div class="switch disabled';

                    if (!isset($activated_components[$type])) {
                        $content .= ' highlight';
                    }

                    $content .= '">Disabled</div>';
                    
                    if (!isset($activated_components[$type]) && isset($installed_components[$type])) {
                    
                    	$content .= <<<EOF
<form id="$type-uninstall" class="asynchronous-form uninstall-component" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_uninstall">
<input type="hidden" name="class" value="$type">
<input type="hidden" name="parent" value="$parent">
<input type="submit" value="Uninstall">
</form>
EOF;
                    
                    }
                    
                    // if ASSETS_URL has been set, hide delete because site is using a shared vce
                    if (!isset($activated_components[$type]) && !defined('ASSETS_URL')) { 
                    
                    	// hide delete if component is located in vce-application/components
                     	if (strpos($component_path, 'vce-application') === false) {
                    
                    		$component_path = str_replace('\\', '/', $component_path);

                			$content .= <<<EOF
<form id="$type-remove" class="asynchronous-form delete-component delete-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="hidden" name="location" value="$components_dir">
<input type="hidden" name="class" value="$type">
<input type="hidden" name="parent" value="$parent">
<input type="hidden" name="component_path" value="$component_path">
<input type="submit" value="Delete">
</form>
EOF;

						}

                    }

                    $content .= '</div><div class="each-componet-name">' . $info['name'] . '</div><div class="each-componet-description">' . $info['description'] . '</div><div class="each-componet-description">' . $method_info . '</div>';

                    if (isset($activated_components[$type]) && $fields = $current_component->component_configuration()) {

                        $dossier_for_configure = $vce->generate_dossier(array('type' => 'ManageComponents', 'procedure' => 'configure', 'component' => $type));

                        $accordion = <<<EOF
<form id="$type-configuration" class="configure-component" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_configure">
$fields
<input type="submit" value="Save">
</form>
EOF;
						// place config into accordion
						$content .= $vce->content->accordion('Configure', $accordion);
											

                    }
                    
                    // check that components that have a preloaded_component method have been added to that column in site_meta table
                    if (isset($activated_components[$type])) {
                    	if ($current_component->preload_component() !== false) {
							if (!isset($preloaded_components[$type])) {
								$preloaded_checker[$type] = $component_path;

							}
                    	}
                    }
                    
                    // path routing checker
                    if (method_exists($current_component, 'path_routing' )) {
						if (isset($activated_components[$type]) && $current_component->path_routing() !== false) {
							if (!empty($path_routing)) {
							
						    	$routing = $current_component->path_routing();
						    	
						    	foreach ($routing as $path=>$routing) {
						    		$routing_counter++;
									if (isset($path_routing[$path])) {
										if ($path_routing[$path]['component'] != $routing[0] || $path_routing[$path]['method'] != $routing[1]) {    	
											$routing_checker[] = $type;
										}
									} else {
										$routing_checker[] = $type;
									}
						    	}
		
						    } else {
						    	$routing_checker[] = $type;
						    }
						}
					}

                    // permissions exist in the component
                    if (isset($info['permissions'])) {

                        $dossier_for_permissions = $vce->generate_dossier(array('type' => 'ManageComponents', 'procedure' => 'permissions', 'component' => $type));
                        
                        // get roles in hierarchical order
        				$roles_hierarchical = json_decode($vce->site->site_roles, true);

						$accordion = <<<EOF
<form id="$type-permissions" class="configure-component" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_permissions">
<table class="permissions-table">
EOF;

                        $accordion .= '<tr><td class="empty-cell"></td>';

                        // cycle through component permissions
                        foreach ($info['permissions'] as $each_permission) {

                            $description = isset($each_permission['description']) ? $each_permission['description'] : null;

                            $accordion .= '<td class="permissions-name"><div class="label-text">' . $each_permission['name'] . '<div class="tooltip-icon"><div class="tooltip-content">' . $description . '</div></div></div></td>';

                        }

                        $accordion .= '</tr>';
                        
						foreach ($roles_hierarchical as $site_roles) {
						
                        	foreach ($site_roles as $role_id => $role_info) {

								if (is_array($role_info)) {

									$accordion .= '<tr>';

									$accordion .= '<td>' . $role_info['role_name'] . '</td>';

									foreach ($info['permissions'] as $each_permission) {

										$accordion .= '<td><label class="ignore">';

										if (isset($each_permission['type']) && $each_permission['type'] == 'singular') {

											$accordion .= '<input type="radio" name="' . $each_permission['name'] . '" value="' . $role_id . '"';

										} else {

											$accordion .= '<input type="checkbox" name="' . $each_permission['name'] . '_' . $role_id . '" value="' . $role_id . '"';

										}

										if (isset($role_info['permissions'][$type]) && in_array($each_permission['name'], explode(',', $role_info['permissions'][$type]))) {

											$accordion .= ' checked';

										}

										$accordion .= '></label></td>';

									}

									$accordion .= '</tr>';

								}

							}

                        }

                        $accordion .= <<<EOF
</table>
<input type="submit" value="Save">
</form>
EOF;
			
						$content .= $vce->content->accordion('Permissions', $accordion);
													


                    }
				
                    $content .= '</div>';

                } else {

                    // add category to array for sorting
                    $categories['error'] = true;

                    $content .= '<div class="all-components each-component error-component">' . $matches[1] . ' can not be loaded because ' . $matches[2] . ' class does not exist.<br>' . $component_path . '</div>';

                }
                
				// clean-up for any components that are referenced in site_meta but do not exist on the server			
				if (isset($installed_components[$type]) && str_replace(BASEPATH, "", $each_component->getPathname()) == $installed_components[$type]) {
					unset($installed_checker[$type]);
				}
				
				if ($parent != 'Component') {
					$minions[$parent] = $parent;
				}

            }

        }
        
		if (!empty($preloaded_checker)) {
			
            $dossier_edit = array(
                'type' => 'ManageComponents',
                'procedure' => 'repair',
                'preloaded_components' => $preloaded_checker
            );

            $dossier_for_repair = $vce->generate_dossier($dossier_edit);
            
            $display = null;
            
            foreach ($preloaded_checker as $key=>$value) {
            	 $display .= $key .'<br>';
            }
            
            $preloaded_checker = implode(',',$preloaded_checker);

        	$issue = <<<EOF
<div class="vce-error-message">
<p>Activated Components found that have preload_component method, but are not listed in preloaded_components database entry</p>
<p>$display</p>
<form id="repair" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_repair">
<input type="submit" value="Click Here To Repair">
</form>
</div>
EOF;
            
            	$vce->content->add('premain', $issue);

		}
		
		if (!empty($warning_message)) {
		
			$vce->content->add('premain', '<div class="vce-error-message"><div>PLEASE UPDATE THE FOLLOWING ITEMS</div><br><br>' . $warning_message . '</div>');
		
		}
        
        // error message is a component is missing from the server but in the database
        if (!empty($installed_checker)) {
            
            foreach ($installed_checker as $name=>$path) {
           		$list[] = $name;
            }
            
            
            $dossier_edit = array(
                'type' => 'ManageComponents',
                'procedure' => 'repair',
                'remove_components' => $list,
                'minions' => $minions
            );

            $dossier_for_repair = $vce->generate_dossier($dossier_edit);
            
            $display = null;
            
            foreach ($list as $key) {
            	 $display .= $key .'<br>';
            }
            
        
        	$issue = <<<EOF
<div class="vce-error-message">
<p>Components are missing from the server, but listed within installed_components database entry</p>
<p>$display</p>
<form id="repair" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_repair">
<input type="submit" value="Click Here To Repair">
</form>
</div>
EOF;
            
            	$vce->content->add('premain', $issue);
            
         }
         
		if (!empty($routing_checker) || count($path_routing) != $routing_counter) {
		
            $dossier_edit = array(
                'type' => 'ManageComponents',
                'procedure' => 'repair',
                'path_routing' => 'update'
            );

            $dossier_for_repair = $vce->generate_dossier($dossier_edit);
            
            $display = null;
            
            foreach ($routing_checker as $value) {
            	 $display .= $value .'<br>';
            }
        
        	$issue = <<<EOF
<div class="vce-error-message">
<p>Activated Components containing updated Path Routing have been found.</p>
<p>$display</p>
<form id="repair" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_repair">
<input type="submit" value="Click Here To Repair">
</form>
</div>
EOF;
            
            	$vce->content->add('premain', $issue);
		
		}
         

        $content .= '</div>';

        $content .= <<<EOF
<form id="update" class="components-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_edit ">
<input type="submit" value="Update Components">
</form>
EOF;

        // alpha sort of categories, and then write to screen for sorting
        
		ksort($categories);
       
        $content .= '<div class="category-display-buttons">';
        foreach ($categories as $category_key => $category_value) {
            $content .= '<button class="category-display';
            if ($category_key == 'site') {
                $content .= ' highlight';
            }
            $content .= '" category="' . $category_key . '">' . $category_value . '</button>';
        }
        $content .= '</div></div>';

        $vce->content->add('main', $content);

    }


    /**
     * Update components
     */
    public  function update($input) {

        $vce = $this->vce;
        
        if (empty($input['json'])) {
			echo json_encode(array('response' => 'error', 'procedure' => 'update', 'message' => 'No data sent'));
			return;
        }
        
        $components = json_decode($input['json'], true);

		// create an associate array from the json object of components
		foreach ($components  as $key => $value) {

			// components that are minions of another Component, such as media types
			if ($value['parent'] != 'Component') {
				$active_minions['enabled_' . strtolower($value['parent']) . 'type'][$value['type']] = $value['url'];
			}

			$components_list[$value['type']] = $value['url'];

		}

		$installed_components = json_decode($vce->site->installed_components, true);
		$install_items = array();

		$activated_components = json_decode($vce->site->activated_components, true);
		$activate_items = array();

		$preloaded_components = json_decode($vce->site->preloaded_components, true);
		
		$minion_components = array();
		
		// minions
		foreach ($vce->site as $meta_key => $meta_value) {
			if (preg_match('/^enabled_/', $meta_key)) {
				$minion_components[$meta_key] = json_decode($vce->site->$meta_key, true);
			}
		}

		$disable_items = array();

		// find newly disabled components
		foreach ($activated_components as $type => $path) {
			if (!isset($components_list[$type])) {

				$disable_items[$type] = $path;

				// remove component from activated list
				unset($activated_components[$type]);

				// remove component from preloaded list
				unset($preloaded_components[$type]);

			}

		}
		
		// find newly activated components
		foreach ($components_list as $type => $path) {

			if (!isset($activated_components[$type])) {

				$activate_items[$type] = $path;

				// add component to activated_components
				$activated_components[$type] = $path;

				if (!isset($installed_components[$type])) {

					// add component to array to check for installed function after database record is updated
					$install_items[$type] = $path;

					// add component to installed_components
					$installed_components[$type] = $path;

				}

			}

		}
		
		// add empty minion records into active list so that they can be updated
		foreach ($minion_components as $this_key=>$this_value) {
			if (!isset($active_minions[$this_key])) {
				// adding an empty value
				$active_minions[$this_key] = array();
			}
		}
		
		// deal with updating minions
		foreach ($active_minions as $this_key=>$this_value) {
		
			if (isset($minion_components[$this_key])) {
			
				// check that there is a difference from both directions
				if (array_diff_assoc($minion_components[$this_key], $this_value) || array_diff_assoc($this_value , $minion_components[$this_key])) {
				
					// update enabled_*
					$update = array('meta_value' => json_encode($this_value, JSON_UNESCAPED_SLASHES));
					$update_where = array('meta_key' => $this_key);
					$vce->db->update('site_meta', $update, $update_where);
				
				}
			
			} else {
			
				$records = array();
				
				// add a new enabled_* to site_mete
				$records[] = array(
					'meta_key' => $this_key,
					'meta_value' => json_encode($this_value, JSON_UNESCAPED_SLASHES),
					'minutia' => null,
				);
				
				$vce->db->insert('site_meta', $records);
							
			}
			
		}

		$update = array('meta_value' => json_encode($installed_components, JSON_UNESCAPED_SLASHES));
		$update_where = array('meta_key' => 'installed_components');
		$vce->db->update('site_meta', $update, $update_where);

		// cycle though newly installed items
		foreach ($install_items as $type => $path) {
			
			// load class
			$activated = $vce->page->instantiate_component(array('type' => $type), $vce, $path);

			// fire installed function
			$activated->installed();

		}

		// using the $components_list object to update this
		$update = array('meta_value' => json_encode($activated_components, JSON_UNESCAPED_SLASHES));
		$update_where = array('meta_key' => 'activated_components');
		$vce->db->update('site_meta', $update, $update_where);

		// cycle though newly activated items
		foreach ($activate_items as $type => $path) {
			
			$activated = $vce->page->instantiate_component(array('type' => $type), $vce, $path);

			// fire installed function
			$activated->activated();

			if ($activated->preload_component() !== false) {
				$preloaded_components[$type] = $path;
			}

		}

		$update = array('meta_value' => json_encode($preloaded_components, JSON_UNESCAPED_SLASHES));
		$update_where = array('meta_key' => 'preloaded_components');
		$vce->db->update('site_meta', $update, $update_where);

		// cycle though newly activated items
		foreach ($disable_items as $type => $path) {
		
			$call_disabled = true;
			
			// need to prevent this from firing off if this is a minion
			// this is because an error is thrown if the component that has minions been disabled
			foreach ($minion_components as $each_minion_type=>$each_minion_components) {
				if (isset($each_minion_components[$type])) {
				
					preg_match('/^enabled_(.+)type$/', $each_minion_type, $match);
				
					if (isset($match[1])) {
						foreach($activated_components as $activated_key=>$activated_value) {
							if (strtolower($activated_key) == $match[1]) {
								$call_disabled = false;
							}
						}
					}		
				}
			}
			
			if ($call_disabled) {
			
				$disabled = $vce->page->instantiate_component(array('type' => $type), $vce, $path);

				// fire installed function
				$disabled->disabled();
			
			}

		}

		echo json_encode(array('response' => 'success', 'procedure' => 'update', 'action' => 'reload', 'message' => 'Updated'));
		return;

    }
    
    /**
     * uninstall a component
     */
    public function uninstall_component($input) {
    
    	global $vce;

		$installed_components = json_decode($vce->site->installed_components, true);
		unset($installed_components[$input['class']]);

		$update = array('meta_value' => json_encode($installed_components, JSON_UNESCAPED_SLASHES));
		$update_where = array('meta_key' => 'installed_components');
		$vce->db->update('site_meta', $update, $update_where);

		$activated_components = json_decode($vce->site->activated_components, true);
		unset($activated_components[$input['class']]);

		$update = array('meta_value' => json_encode($activated_components, JSON_UNESCAPED_SLASHES));
		$update_where = array('meta_key' => 'activated_components');
		$vce->db->update('site_meta', $update, $update_where);

		$preloaded_components = json_decode($vce->site->preloaded_components, true);
		unset($preloaded_components[$input['class']]);

		$update = array('meta_value' => json_encode($preloaded_components, JSON_UNESCAPED_SLASHES));
		$update_where = array('meta_key' => 'preloaded_components');
		$vce->db->update('site_meta', $update, $update_where);

		// remove from enabled minions list
		if ($input['parent'] != "Components") {

			$minions = 'enabled_' . strtolower($input['parent']) . 'type';

			$enabled_minions = json_decode($vce->site->$minions, true);
			unset($enabled_minions[$input['class']]);

			if (!empty($enabled_minions)) {

				$update = array('meta_value' => json_encode($enabled_minions, JSON_UNESCAPED_SLASHES));
				$update_where = array('meta_key' => $minions);
				$vce->db->update('site_meta', $update, $update_where);

			} else {

				// delete if empty
				$where = array('meta_key' => $minions);
				$vce->db->delete('site_meta', $where);

			}

		}

		// delete configuration record if it exists
		$where = array('meta_key' => $input['class']);
		$vce->db->delete('site_meta', $where);

    }
    
    /**
     * uninstall a component
     */
    public function uninstall($input) {

		$this->uninstall_component($input);

		echo json_encode(array('response' => 'success', 'procedure' => 'uninstall', 'action' => 'reload', 'message' => 'Component Uninstalled'));
		return;
    
    }

    /**
     * delete a component
     */
    public function delete($input) {

        // $input['type'] is added in class.component.php in form_input
        // it returns ManageComponents...
        // we don't want that, but instead want the class name that is contained in $input['class']

      	$vce = $this->vce;

		// get path to component
		$installed_components = json_decode($vce->site->installed_components, true);

		if (isset($installed_components[$input['class']])) {

			$path = $installed_components[$input['class']];

			// load class
			// require_once BASEPATH . $path;
			// $component = new $input['class']();
			
			$component = $vce->page->instantiate_component(array('type' => $input['class']), $vce, $path);

			// fire removed function
			$removed = $component->removed();

		} else {

			// create path
			// $path = $input['location'] .  '/components/' . strtolower($input['class']) . '/' . strtolower($input['class']) . '.php';

			// get path from input value
			$path = $input['component_path'];

		}

		// fullpath
		$dirPath = BASEPATH . dirname($path);

		// delete component directory
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
			$path->isDir() && !$path->isLink() ? rmdir($path->getPathname()) : unlink($path->getPathname());
		}
		rmdir($dirPath);

		$this->uninstall_component($input);

		echo json_encode(array('response' => 'success', 'procedure' => 'delete', 'action' => 'reload', 'message' => 'Component Deleted'));
		return;

    }

    /**
     * add configuration data for component to site_meta table
     */
    public function configure($input) {
    
        $vce = $this->vce;

    	// look for checkbox values and place them into a single variable
    	foreach ($input as $key => $value) {
			if (preg_match('/(.*)_\d+/', $key, $matches)) {
				$multivalues[$matches[1]][] = $value;
				unset($input[$key]);
			}
    	}
    	
    	if (isset($multivalues)) {
			// add values back into $input
			foreach ($multivalues as $key=>$value) {
				$input[$key] = implode('|', $value);
			}
    	}

        $component = $input['component'];
        unset($input['type'], $input['component']);
        
        // clean-up empty values
        foreach ($input as $key=>$value) {
			if (empty($value)) {
				unset($input[$key]);
			}
        }

        $user = $vce->user;
        $vector = $user->create_vector();
        $config = $user->encryption(json_encode($input), $vector);

        $site = $vce->site;

        $db = $vce->db;

        if (isset($site->$component)) {

            $update = array('meta_value' => $config, 'minutia' => $vector);
            $update_where = array('meta_key' => $component);
            $db->update('site_meta', $update, $update_where);

        } else {

            // created
            $records[] = array(
                'meta_key' => $component,
                'meta_value' => $config,
                'minutia' => $vector,
            );

            $db->insert('site_meta', $records);

        }

        echo json_encode(array('response' => 'success', 'procedure' => 'update', 'action' => 'reload', 'message' => 'Configuration Saved'));
        return;

    }

    /**
     * permissions for component
     */
    public function permissions($input) {

       	$vce = $this->vce;
        $db = $vce->db;
        $site = $vce->site;
        $user = $vce->user;

        $site_roles = json_decode($site->roles, true);

        $component = $input['component'];
        unset($input['type'], $input['component']);

        $permissions_list = array();
        // add permissions to this component
        foreach ($input as $each_key => $each_value) {
            // remove the underscore and number from checkbox name
            $permissions_list[$each_value][] = preg_replace('/_\d+$/', '', $each_key);
        }

        foreach ($site_roles as $role_id => $role_values) {

            // unset component permissions before adding them back
            if (isset($site_roles[$role_id]['permissions'][$component])) {
                // clear current permissions for this component
                unset($site_roles[$role_id]['permissions'][$component]);
            }

            if (isset($permissions_list[$role_id])) {
                $site_roles[$role_id]['permissions'][$component] = implode(',', $permissions_list[$role_id]);
            }

            // clean up if empty
            if (empty($site_roles[$role_id]['permissions'])) {
                unset($site_roles[$role_id]['permissions']);
            }

        }

        $roles = json_encode($site_roles);

        $update = array('meta_value' => $roles);
        $update_where = array('meta_key' => 'roles');
        $db->update('site_meta', $update, $update_where);

        // reset site object
        $site->roles = $roles;

        // pass user id to masquerade as
        $user->make_user_object($user->user_id);

        echo json_encode(array('response' => 'success', 'procedure' => 'update', 'action' => 'reload', 'message' => 'Permissions Saved'));
        return;

    }
    
    /**
     * missing_components removal method
     */
    public function repair($input) {

		$vce = $this->vce;
		
		if (isset($input['remove_components'])) {
		
			$installed_components = json_decode($vce->site->installed_components, true);
			$activated_components = json_decode($vce->site->activated_components, true);
			$preloaded_components = json_decode($vce->site->preloaded_components, true);
		
			foreach ($input['remove_components'] as $each_missing_component) {
				unset($installed_components[$each_missing_component]);
				unset($activated_components[$each_missing_component]);
				unset($preloaded_components[$each_missing_component]);
			
				foreach ($input['minions'] as $each_minion) {
		
					$meta_key = 'enabled_' . $each_minion;
			
					$meta_value = json_decode($vce->site->$meta_key, true);
				
					unset($meta_value[$each_missing_component]);
		
					$update = array('meta_value' => json_encode($meta_key, JSON_UNESCAPED_SLASHES));
					$update_where = array('meta_key' => $meta_value);
					$vce->db->update('site_meta', $update, $update_where);
		
				}		
	
			}
		
			$update = array('meta_value' => json_encode($installed_components, JSON_UNESCAPED_SLASHES));
			$update_where = array('meta_key' => 'installed_components');
			$vce->db->update('site_meta', $update, $update_where);
		
			$update = array('meta_value' => json_encode($activated_components, JSON_UNESCAPED_SLASHES));
			$update_where = array('meta_key' => 'activated_components');
			$vce->db->update('site_meta', $update, $update_where);
		
			$update = array('meta_value' => json_encode($preloaded_components, JSON_UNESCAPED_SLASHES));
			$update_where = array('meta_key' => 'preloaded_components');
			$vce->db->update('site_meta', $update, $update_where);

			echo json_encode(array('response' => 'success', 'procedure' => 'update', 'action' => 'reload', 'message' => 'Repaired'));
			return;
        
        }
        
		if (isset($input['preloaded_components'])) {
		
			$preloaded_components = json_decode($vce->site->preloaded_components, true);
			
			foreach ($input['preloaded_components'] as $key=>$value) {
				$preloaded_components[$key] = $value;
			}
		

			$update = array('meta_value' => json_encode($preloaded_components, JSON_UNESCAPED_SLASHES));
			$update_where = array('meta_key' => 'preloaded_components');
			$vce->db->update('site_meta', $update, $update_where);
		
			echo json_encode(array('response' => 'success', 'procedure' => 'update', 'action' => 'reload', 'message' => 'Repaired'));
			return;
		
		}
		
		if (isset($input['path_routing'])) {
		
			$activated_components = json_decode($vce->site->activated_components, true);
			
			// a fail safe that can be removed in the future, once all instances have been updated
			if (!isset($vce->site->path_routing)) {
				$vce->db->insert('site_meta', array('meta_key' => 'path_routing','meta_value' => '[]','minutia' => null));
			}
			
			$path_routing = array();
			
			foreach ($activated_components as $type=>$path) {
				
				// check that component exists and require it
				if (file_exists(BASEPATH . $path)) {
				
					require_once(BASEPATH . $path);
					
					$properties['type'] = $type;

					$routing_component = new $type($properties, $vce);
					
					// check that method exists
					if (method_exists($type, 'path_routing')) {
						
						// we could add $vce here
						$routing = $routing_component->path_routing();
						
						if ($routing !== false) {
						
							foreach ($routing as $routing_path=>$routing_data) {
							
								if (!empty($routing_data[0]) && !empty($routing_data[1])) {
							
									$path_routing[$routing_path] = array('component' => $routing_data[0], 'method' => $routing_data[1]);
								}
							
							}
						
						}
						
					}
				
				}
				
			}
			
			$update = array('meta_value' => json_encode($path_routing, JSON_UNESCAPED_SLASHES));
			$update_where = array('meta_key' => 'path_routing');
			$vce->db->update('site_meta', $update, $update_where);
		
			echo json_encode(array('response' => 'success', 'procedure' => 'update', 'action' => 'reload', 'message' => 'Repaired'));
			return;
		
		}    

	}
}
