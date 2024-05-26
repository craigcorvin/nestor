<?php

class ManageRecipes extends Component {

    /**
     * basic info about the component
     */
    public function component_info() {
        return array(
            'name' => 'Manage Recipes',
            'description' => 'Create, edit, and delete recipes of different components (The power behind the throne).',
            'category' => 'admin',
            'recipe_fields' => array('auto_create','title',array('url' => 'required'))
        );
    }

    /**
     * display content specific to this component
     */
    public function as_content($each_component, $vce) {

        // check if value is in page object / check to see if we want to edit this recipe
        $component_id = isset($vce->component_id) ? $vce->component_id : null;

		// $component_id = '39975';

        $top_clickbar = isset($component_id) ? ' clickbar-open' : '';
        $bottom_clickbar = isset($component_id) ? '' : ' clickbar-closed';

        // nestable jquery plugin this is all based on
        // http://dbushell.github.io/Nestable/
        // https://github.com/dbushell/Nestable

        // add javascript to page
        $vce->site->add_script(dirname(__FILE__) . '/js/jquery-nestable.js', 'jquery tablesorter');

        // add javascript to page
        $vce->site->add_script(dirname(__FILE__) . '/js/script.js', 'select2');

        // add javascript to page
        $vce->site->add_style(dirname(__FILE__) . '/css/jquery-nestable.css', 'jquery-nestable-style');

        // add javascript to page
        $vce->site->add_style(dirname(__FILE__) . '/css/style.css', 'manage-users-style');

		$content = null;

        $accordion = <<<EOF
<div class="sort-block left-block">
<div class="sort-block-title">Components</div>

<div class="dd" id="nestable">
<ol class="dd-list">
EOF;

        $categories = array();

        // cycle through installed components
        foreach (json_decode($vce->site->activated_components, true) as $key => $value) {

            $component_path = BASEPATH . $value;

            if (file_exists($component_path)) {

                // create an instance of the class
                $access = $vce->page->instantiate_component(array('type' => $key), $vce);

                // get info for component
                //$info = $access->component_info();
                $info = (object) $access->component_info();

                // set recipes fields
                $recipe_fields = $access->recipe_fields(array('type' => $key));

                // do not display if $recipe_fields returned false
                if ($recipe_fields) {

                    $categories[$info->category] = true;

                    $accordion .= <<<EOF
<li class="dd-item $info->category-component all-components" referrer="$key" data-type="$key" unique-id="$key">
<div class="dd-handle dd3-handle">&nbsp;</div>
<div class="dd-content"><div class="dd-title">$info->name</div>
<div class="dd-toggle"></div>
<div class="dd-content-extended $key-extended">$recipe_fields
<label><div class="input-padding">$info->description</div></label>
<button class="remove-button" data-action="remove" type="button">Remove</button>
</div></div></li>
EOF;

                }

            }

        }

        $accordion .= <<<EOF
</ol>
EOF;

		$compcat = null;

        // alpha sort of categories
        ksort($categories);

        //
        foreach ($categories as $category_key => $category_value) {
            $compcat .= '<button class="category-display';
            if ($category_key == 'site') {
                $compcat .= ' highlight';
            }
            $compcat .= '" category="' . $category_key . '">' . $category_key . '</button>';
        }

		$accordion .= $vce->content->accordion('Display By Category', $compcat, true, true);

		$accordion .= <<<EOF
</div>
</div>
<div class="sort-block right-block">
<div class="sort-block-title">Recipe</div>
<div class="dd" id="nestable2">
EOF;

        if (isset($component_id)) {

            // start update

			$accordion .= <<<EOF
<ol class="dd-list">
EOF;
            // get recipe
            $query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE component_id='" . $component_id . "' AND meta_key ='recipe'";
            $recipe_value = $vce->db->get_data_object($query);

            $recipe_object = json_decode($recipe_value[0]->meta_value, true);

            $recipe = $recipe_object['recipe'];
            $recipe_name = $recipe_object['recipe_name'];
            $full_object = isset($recipe_object['full_object']) ? $recipe_object['full_object'] : null;

            // adding this component_id to the recipe object as an initial value
            $recipe[0]['component_id'] = $component_id;
        
            // call to recursive function
            $accordion .= self::cycle_though_recipe($vce, $recipe);

            // create dossier, which is an encrypted json object of details uses in the form
            $dossier = $vce->generate_dossier(array('type' => 'ManageRecipes', 'procedure' => 'update'));

			$input = array(
			'type' => 'text',
			'name' => 'recipe_name',
			'value' => $recipe_name,
			'data' => array(
				'autocapitalize' => 'none',
				'tag' => 'required',
				'placeholder' => 'Enter A Recipe Name'
			)
			);
		
			$recipe_name = $vce->content->create_input($input,'Recipe Name','Enter A Recipe Name');

            $accordion .= <<<EOF
</ol>
</div>
<form id="create_sets" class="recipe-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier">
<div class="recipe-info" style="clear:both">
$recipe_name
EOF;

            // load hooks
            if (isset($site->hooks['recipe_attributes'])) {
                foreach ($site->hooks['recipe_attributes'] as $hook) {
                    $content .= call_user_func($hook, $vce->user);
                }
            }
            
			$input = array(
			'type' => 'checkbox',
			'name' => 'full_object',
			'options' => array('label' => 'Generate Full Page Object','value' => 'true','selected' => $full_object)
			);
		
			$full_object_input = $vce->content->create_input($input,'Full Page Object');
			
			// $full_object

            $advanced_options = <<<EOF
$full_object_input
EOF;

			$accordion .= $vce->content->accordion('Advanced Options', $advanced_options);
			
			$accordion .= <<<EOF
<br>
<input type="submit" value="Update This Recipe">
</div>
</form>
</div>
EOF;

			$content .= $vce->content->accordion('Update Recipe', $accordion, true, true);

            // end update
        } else {

            // create dossier, which is an encrypted json object of details uses in the form
            $dossier = $vce->generate_dossier(array('type' => 'ManageRecipes', 'procedure' => 'create'));


			$input = array(
			'type' => 'text',
			'name' => 'recipe_name',
			'data' => array(
				'autocapitalize' => 'none',
				'tag' => 'required',
				'placeholder' => 'Enter A Recipe Name'
			)
			);
		
			$recipe_name = $vce->content->create_input($input,'Recipe Name','Enter A Recipe Name');

// start of create
            $accordion .= <<<EOF
<div class="dd-empty"></div>
</div>
<form id="create_sets" class="recipe-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier">
<div class="recipe-info" style="clear:both">
$recipe_name
EOF;

            // load hooks
            if (isset($site->hooks['recipe_attributes'])) {
                foreach ($site->hooks['recipe_attributes'] as $hook) {
                    $content->main .= call_user_func($hook, $vce->user);
                }
            }
            
			$input = array(
			'type' => 'checkbox',
			'name' => 'full_object',
			'options' => array('label' => 'Generate Full Page Object','value' => 'true')
			);
		
			$full_object_input = $vce->content->create_input($input,'Full Page Object');
   
			$input = array(
			'type' => 'textarea',
			'name' => 'json_text',
			'data' => array(
		 		'rows' => '10'
		 	)
			);
		
			$json_text_input = $vce->content->create_input($input,'Create from JSON');

            $advanced_options = <<<EOF
$full_object_input
$json_text_input
EOF;

			$accordion .= $vce->content->accordion('Advanced Options', $advanced_options);

			$accordion .= <<<EOF
<div><input type="submit" value="Save This Recipe"></div>
</div>

</form>
</div>
EOF;

			$content .= $vce->content->accordion('Create A New Recipe', $accordion);

            // end of create
        }


        // fetch all recipes
        $query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE meta_key='recipe' GROUP BY component_id, id ORDER BY meta_value ASC";
        $recipes = $vce->db->get_data_object($query);
        
		// a temporary solution to sort to hold us over until this comonent can be rewritten
        foreach ($recipes as $key=>$each_recipe) {
        	$recipe_object = json_decode($each_recipe->meta_value);
        	$recipes[$key]->recipe_name = $recipe_object->recipe_name;
        }
        $recipes = $vce->sorter($recipes,'recipe_name','asc','string');

        $accordion = <<<EOF
<table id="existing-recipes" class="tablesorter">
<thead>
<tr>
<th></th>
<th></th>
<th>Name</th>
<th>URL</th>
<th>Base Id</th>
<th></th>
</tr>
</thead>
EOF;

        foreach ($recipes as $key=>$each_recipe) {
        
            $get_url = function ($parent_id) use (&$get_url) {

                global $db;

                // fetch all recipes
                $query = "SELECT * FROM " . TABLE_PREFIX . "components WHERE parent_id='" . $parent_id . "'";
                $component = $db->get_data_object($query);

                if (!empty($component[0]->url)) {
                    return $component[0]->url;
                } else {

                    if (isset($component[0])) {
                        return $get_url($component[0]->component_id);
                    }
                }

            };

            // fetch all recipes
            $query = "SELECT * FROM " . TABLE_PREFIX . "components WHERE component_id='" . $each_recipe->component_id . "'";
            $component = $vce->db->get_data_object($query);

            $recipe_url = "";

            if (!empty($component[0]->url)) {

                $recipe_url = $component[0]->url;

            } else {

                $recipe_url = $get_url($component[0]->component_id);

            }

            // fetch all recipes
            $query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE meta_key='created_at' AND component_id='" . $each_recipe->component_id . "'";
            $meta_value = $vce->db->get_data_object($query);

            $recipe_object = json_decode($each_recipe->meta_value, true);

            $recipe_name = $recipe_object['recipe_name'];
            $component_id = $each_recipe->component_id;

            // create dossier, which is an encrypted json object of details uses in the form

            $dossier_edit = array(
                'type' => 'ManageRecipes',
                'procedure' => 'edit',
                'component_id' => $each_recipe->component_id,
                'created_at' => $meta_value[0]->meta_value,
            );

            $dossier_for_edit = $vce->generate_dossier($dossier_edit);

            $dossier_delete = array(
                'type' => 'ManageRecipes',
                'procedure' => 'delete',
                'component_id' => $each_recipe->component_id,
                'created_at' => $meta_value[0]->meta_value,
            );

            $dossier_for_delete = $vce->generate_dossier($dossier_delete);

            $dossier_update_json = array(
                'type' => 'ManageRecipes',
                'procedure' => 'update_json',
                'component_id' => $each_recipe->component_id,
                'created_at' => $meta_value[0]->meta_value,
            );

            $dossier_for_update_json = $vce->generate_dossier($dossier_update_json);

            $accordion .= <<<EOF
<tr>
<td class="align-center">
<form id="edit-$each_recipe->component_id" class="inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_edit">
<input type="submit" value="Edit">
</form>
</td>
<td class="align-center">
<button class="view-recipe-object" component_id="$each_recipe->component_id">View Object</button>
</td>
<td>
$recipe_name
</td>
<td>
<a href="$vce->site_url/$recipe_url">$recipe_url</a>
</td>
<td>
$component_id
</td>
<td class="align-center">
EOF;

		// prevent delete of a recipe that is not stored on the base
		if ($component[0]->parent_id == '0') {

			$accordion .= <<<EOF
<form id="delete-$each_recipe->component_id" class="delete-form inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="submit" value="Delete">
</form>
EOF;

		}

		$accordion .= <<<EOF
</td>
</tr>
<tr class="recipe-object recipe-object-$each_recipe->component_id">
<td colspan=5>
<form id="update-json-$each_recipe->component_id" class="update-json-form inline-form asynchronous-form" method="post" action="$vce->input_path">
<textarea name="recipe_json" rows="10" cols="90">$each_recipe->meta_value</textarea>
<input type="hidden" name="dossier" value="$dossier_for_update_json">
<td>
<input type="submit" value="Update JSON">
</td>
</form>
</td>
</tr>
EOF;

        }

        $accordion .= <<<EOF
</table>
EOF;

		$content .= $vce->content->accordion('Existing Recipes', $accordion, true, true, 'no-padding');

        $vce->content->add('main', $content);

    }

    /**
     * recursive function to display current recipes
     */
    private function cycle_though_recipe($vce, $current_recipe, $parent_id = 0, $parent_exists = true) {

        // get global site object and grab components list
        $activated_components = json_decode($vce->site->activated_components, true);

        $content = "";
        
        if ($parent_exists) {
        
			// if component_id is set, then this is the base of the recipe and the component exists
			if (!empty($current_recipe[0]['component_id'])) {
				// get existing components
				$query = "SELECT * FROM " . TABLE_PREFIX . "components AS a LEFT JOIN " . TABLE_PREFIX . "components_meta AS b ON a.component_id=b.component_id";
				$query .= " WHERE a.component_id='" . $current_recipe[0]['component_id'] . "' ORDER BY a.sequence ASC";
				$requested_component_data = $vce->db->get_data_object($query, false);
			} elseif (!empty($parent_id))  {
				// get existing components
				$query = "SELECT * FROM " . TABLE_PREFIX . "components AS a LEFT JOIN " . TABLE_PREFIX . "components_meta AS b ON a.component_id=b.component_id";
				$query .= " WHERE a.parent_id='" . $parent_id . "' ORDER BY a.sequence ASC";
				$requested_component_data = $vce->db->get_data_object($query, false);
			}


			if (!empty($requested_component_data)) {
				$assembled_components = $vce->page->assemble_component_objects($requested_component_data, $vce);
				// $vce->dump($assembled_components);
			}
		
		}
        
        foreach ($current_recipe as $counter=>$each_item) {

        	// $vce->dump($each_item['type']);

            // set to true when dd-nodrag should be added to component that exists and should not be deleted
            $nodrag = false;

            $this_recipe = $each_item;

            unset($this_recipe['components']);

            // check if component has been activated
            if (isset($activated_components[$each_item['type']])) {

                // load component class
                require_once BASEPATH . '/' . $activated_components[$each_item['type']];

                $access = new $each_item['type'];

                // get info for component
                $info = $access->component_info();

            } else {

                $access = new Component();

                // get info for component
                $info = $access->component_info();

                // write over $info['name'] with $each_item['type']
                $info['name'] = $each_item['type'] . ' (Component Disabled)';

            }

            $content .= '<li class="dd-item" referrer="' . $each_item['type'] . '" data-type="' . $each_item['type'] . '" unique-id="' . $each_item['type'] . '"';

            // set var for component exists check
            $nodrag = false;

            // prevent auto_create components from being static if they are deeper in the recipe
            $parent_id = null;
            
            $parent_exists = false;

			// this is an auto_create = forward, and parent componet is also an auto_create = forward
            if (isset($each_item['auto_create']) && $each_item['auto_create'] == "forward") {
            
            	// $vce->dump($each_item['type'],'9c3');
            
				if (!empty($assembled_components)) {
				
					$current_component = null;
					
					foreach ($assembled_components as $key=>$value) {
					
						if ($value->type == $each_item['type']) {
							
							$current_component = $value;
							
							unset($assembled_components[$key]);
							
							break;
						}
					
					}
					
					// prevent errors
					$url = isset($each_item['url']) ? $each_item['url'] : "/";
					
					$nodrag = true;
					$content .= ' data-component_id="' . $current_component->component_id . '"';

					// if component_id was added to the recipe
					$parent_id = $current_component->component_id;
					
					if (isset($each_item['components'])) {
						foreach ($each_item['components'] as $sub_component) {
					
							if (isset($sub_component['auto_create']) && $sub_component['auto_create'] == "forward") {
						
								$parent_exists = true;
						
							}
					
						}
					}
					
				}
					
            }

            $content .= '>';

            $content .= '<div class="dd-handle dd3-handle';

            if ($nodrag) {
                $content .= ' dd-nodrag';
            }
            
            // for another time, when fun can be done, and the weight of everything here is not being carried
            // background-color:#' . substr(dechex(crc32($each_item['type'])),- 6)

            $content .= '">&nbsp;</div>';

            $content .= '<div class="dd-content"><div class="dd-title">';

            if ($nodrag) {
                $key_match = false;
                $content .= '<select name="type" class="select-component-type">';
                // cycle through installed components to create select menu
                $activated_components = json_decode($vce->site->activated_components, true);
                ksort($activated_components);
                foreach ($activated_components as $key => $value) {

					if (file_exists(BASEPATH . $value)) {
                    	// load component class to get name
                    	require_once(BASEPATH . $value);
                    } else {
                    	continue;
                    }

                    // instance
                    $each_component = new $key;

                    // get info for component
                    $component_info = $each_component->component_info();

                    $content .= '<option value="' . $key . '"';
                    if ($key == $each_item['type']) {
                        $key_match = true;
                        $content .= ' selected';
                    }
                    $content .= '>' . $component_info['name'] . '</option>';
                }

                // if a component has been disabled, add option onto end with message
                if (!$key_match) {
                    $content .= '<option value="' . $each_item['type'] . '" selected>' . $info['name'] . '</option>';
                }

                $content .= '</select>';

            } else {

                $content .= $info['name'];

            }

            $content .= '</div><div class="dd-toggle"></div>';

            $content .= '<div class="dd-content-extended">' . $access->recipe_fields($this_recipe);

            $content .= '<label><div class="input-padding">';
            
			$content .=  $info['description'];
			
            if (!empty($current_component)) {
            
            	$content .=  '<div>Component Id: ' . $current_component->component_id . '</div>';
            	$content .=  '<div>Parent Id: ' . $current_component->parent_id . '</div>';
            
            }
            
            $content .= '</div></label>';

            if (!$nodrag) {
               
                $content .= '<button class="remove-button" data-action="remove" type="button">Remove</button>';
            
            } else {
            
            	if (!empty($current_component)) {
            
					// the instructions to pass through the form
					$dossier = array(
					'type' => 'ManageRecipes',
					'procedure' => 'delete',
					'component_id' => $current_component->component_id,
					'created_at' => $current_component->created_at,
					'created_by' => $current_component->created_by
					);
				
					// generate dossie
					$dossier_for_delete = $vce->generate_dossier($dossier);
	
					$content .= <<<EOF
<button class="remove-button" data-action="delete" dossier="$dossier_for_delete" action="$vce->input_path">Delete</button>
EOF;
            	
            	}
            
            }

            $content .= '</div></div>';

            if (isset($each_item['components'])) {

                $content .= '<ol class="dd-list">';

				$content .= self::cycle_though_recipe($vce, $each_item['components'], $parent_id, $parent_exists);
				
                $content .= '</ol></li>';

            }
        }

        return $content;

    }

    
    /**
     * Create a new recipe
     */
    public function create($input) {

        $recipe_name = $input['recipe_name'];

        $recipe_array = [];

        // see if admin is passing in json recipe json
        $recipe = isset($input['json_text']) && $input['json_text'] != '' ? json_decode(stripcslashes(html_entity_decode($input['json_text'])), true) : null;

        if ($recipe) {
        
            $recipe_array = $recipe['recipe'];
        
        } else {

            // create an associate array from the json object of recipe
            $recipe_array = isset($input['json']) ? json_decode($input['json'], true) : null;
            $recipe['recipe'] = $recipe_array;
        }

        // no recipe created
        if (!count($recipe_array)) {
            echo json_encode(array('response' => 'error', 'message' => 'Add a component'));
            return;
        }

        // more than one first level component
        if (count($recipe_array) > 1) {
            echo json_encode(array('response' => 'error', 'message' => 'Only one first level component is allowed.'));
            return;
        }

        // first level component must be one that auto_creates
        if (!isset($recipe_array[0]['auto_create'])) {
            echo json_encode(array('response' => 'error', 'message' => $recipe_array[0]['type'] . ' cannot be a first level component'));
            return;
        }

        // remove type so that first recipe component is not effected
        unset($input['type']);

        // adds additional meta_data to recipe from hooks, et cetera
        foreach ($input as $key => $value) {
            if ($key != 'json' && $key != 'json_text') {
                $recipe[$key] = $value;
            }
        }

        // call to recursive funtion to process and create components that need to be created on save
        self::process_recipe($recipe_array, $recipe);

        echo json_encode(array('response' => 'success', 'message' => 'Recipe Created!'));
        return;

    }

    /**
     * edit recipe
     */
    public function edit($input) {

        // add attributes to page object for next page load using session
        global $vce;

        $vce->site->add_attributes('component_id', $input['component_id']);

        echo json_encode(array('response' => 'success', 'message' => 'session data saved', 'form' => 'edit'));
        return;

    }

    /**
     * Update an existing recipe
     */
    public function update($input) {
    
    	global $vce;

        // create an associate array from the json object of recipe
        if (isset($input['json'])) {
            $recipe_array = json_decode($input['json'], true);
        } else {
            echo json_encode(array('response' => 'error', 'message' => 'json empty'));
            return;
        }

        //$component_id = $input['component_id'];
        $recipe_name = $input['recipe_name'];

        // more than one first level component
        if (count($recipe_array) > 1) {
            echo json_encode(array('response' => 'error', 'message' => 'Only one first level component is allowed.'));
            return;
        }

        // first level component must be one that auto_creates
        if (!isset($recipe_array[0]['auto_create'])) {
            echo json_encode(array('response' => 'error', 'message' => $recipe_array[0]['type'] . ' cannot be a first level component'));
            return;
        }

        // remove type so that first recipe component is not effected
        unset($input['type']);

        $recipe['recipe'] = $recipe_array;

        // adds additional meta_data to recipe from hooks, et cetera
        foreach ($input as $key => $value) {
            if ($key != 'json') {
                $recipe[$key] = $value;
            }
        }

        self::process_recipe($recipe_array, $recipe);
        
        echo json_encode(array('response' => 'updated', 'message' => 'Recipe has been updated'));
        return;

    }

    /**
     * process the current recipe, create / update components that need to be created on save (auto_create)
     */
    private function process_recipe($recipe, $recipe_object, $previous = '0', $level = 1) {

        global $vce;
        
        $checker = null;
        
        foreach ($recipe as $order => $each_recipe) {
        
        	// checker for creating or updating
        	if (isset($checker[$each_recipe['type']]) && !isset($each_recipe['recipe_key'])) {
        		$each_recipe['recipe_key'] = $checker[$each_recipe['type']];
        	}
        
        	$checker[$each_recipe['type']] = !isset($checker[$each_recipe['type']]) ? 1 : $checker[$each_recipe['type']] + 1;

            // sub_components to var before clean-up
            $sub_components = isset($each_recipe['components']) ? $each_recipe['components'] : null;

            // check to see a component_id is associated with this item - update instead of create
            if (isset($each_recipe['component_id'])) {

                // get component_id from recipe
                $component_id = $each_recipe['component_id'];

                // update the url within the components table
                if (isset($each_recipe['url'])) {
                    $update = array('url' => $each_recipe['url']);
                    $update_where = array('component_id' => $component_id);
                    $vce->db->update('components', $update, $update_where);
                }

                // clean up before creating meta_data records for component
				unset(
				$each_recipe['component_id'],
				$each_recipe['url'],
				$each_recipe['components'],
				$each_recipe['auto_create'],
				$each_recipe['full_object'],
				// access related meta_keys should be in recipe only
				$each_recipe['role_access'],
				$each_recipe['content_access'],
				$each_recipe['content_create'],
				$each_recipe['content_edit'],
				$each_recipe['content_delete'],
				$each_recipe['order_by'],
				$each_recipe['order_direction'],
				$each_recipe['children_sequencer'],
				$each_recipe['role_select']
				);

				// get old meta_data
				$query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE component_id='1'";
				$old_data = $vce->db->get_data_object($query);

				$old_meta_keys = array();
				foreach ($old_data as $each_old_data) {
					$old_meta_keys[$each_old_data->meta_key] = $each_old_data->meta_value;
				}
                
                //cycle through recipe keys
                foreach ($each_recipe as $key => $value) {

                    // check to see if key has already been set
                    $query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE component_id='" . $component_id . "' AND meta_key ='" . $key . "'";
                    $meta_data = $vce->db->get_data_object($query);

                    if (!empty($meta_data)) {

                        // key has been stored so update
                        $update = array('meta_value' => $value);
                        $update_where = array('component_id' => $component_id, 'meta_key' => $key);
                        $vce->db->update('components_meta', $update, $update_where);

                    } else {

                        // prepare data to write to components_meta table
                        $records[] = array(
                            'component_id' => $component_id,
                            'meta_key' => $key,
                            'meta_value' => $value,
                            'minutia' => null,
                        );

                        $vce->db->insert('components_meta', $records);

                    }

                    // remove set keys from old data list
                    unset($old_meta_keys[$key]);

                }

                // unset a few extra
                unset($old_meta_keys["created_by"], $old_meta_keys["created_at"], $old_meta_keys["recipe"]);

                // clean-up!
                foreach ($old_meta_keys as $old_meta_key=>$old_meta_value) {
                    // delete old component meta data that is not used
                    if (empty($old_meta_value)) {
                    	$where = array('component_id' => $component_id, 'meta_key' => $old_meta_key);
                    	$vce->db->delete('components_meta', $where);
                    }
                }

                // if this is the root component, then update the recipe here
                if ($previous == '0') {

                    // clean up at this point before saving.
                    // replace url from recipe
                    // '/"url":"[^\"]*"\,*/'

                    // remove component_id from recipe
                    $cleaners = array('/"component_id":\d*\,*/');

                    $clean_recipe = preg_replace($cleaners, '', json_encode($recipe_object));
                    
                    // add slashes to allow for line returns to be preserved 
                    $clean_recipe = addslashes($clean_recipe);
                    
                    $update = array('meta_value' => $clean_recipe);
                    $update_where = array('component_id' => $component_id, 'meta_key' => 'recipe');
                    $vce->db->update('components_meta', $update, $update_where);

                    // component_id for when page reloads
                    $vce->site->add_attributes('component_id', $component_id);

                }

            } else {
                // item doesn't exist, so create if auto_create is in recipe

                // should this component be created on save?
                if (isset($each_recipe['auto_create']) && $each_recipe['auto_create'] == "forward" && ($previous != "0" || $level == 1)) {

                    $data = array();
                    $data['parent_id'] = $previous;
                    $data['sequence'] = $order + 1;
                    $data['url'] = isset($each_recipe['url']) ? $each_recipe['url'] : '';
                    
                    // write data to components table
            		$component_id = $vce->db->insert('components', $data);

                    // unset url and next level up
                    unset(
                    $each_recipe['url'],
                    $each_recipe['components'],
                    $each_recipe['auto_create'],
                    $each_recipe['full_object'],
                    // access related meta_keys should be in recipe only
                    $each_recipe['role_access'],
                    $each_recipe['content_access'],
                    $each_recipe['content_create'],
                    $each_recipe['content_edit'],
                    $each_recipe['content_delete'],
					$each_recipe['order_by'],
					$each_recipe['order_direction'],
					$each_recipe['children_sequencer'],
					$each_recipe['role_select']
                    );

					// 'role_access','content_create','content_edit','content_delete'
					// $vce->log($each_recipe);

                    $records = array();

                    $records[] = array(
                        'component_id' => $component_id,
                        'meta_key' => 'created_by',
                        'meta_value' => $vce->user->user_id,
                        'minutia' => null,
                    );

                    $records[] = array(
                        'component_id' => $component_id,
                        'meta_key' => 'created_at',
                        'meta_value' => time(),
                        'minutia' => null,
                    );

                    // if this is the root component, then save the recipe here
                    if ($previous == '0') {
                        // recipe as a json_encode array
                        
                        $recipe_json = addslashes(json_encode($recipe_object));
                        
                        $records[] = array(
                            'component_id' => $component_id,
                            'meta_key' => 'recipe',
                            'meta_value' => $recipe_json,
                            'minutia' => null,
                        );

                        // component_id for when page reloads
                        $vce->site->add_attributes('component_id', $component_id);

                    }

                    foreach ($each_recipe as $key => $value) {
                        // component type
                        $records[] = array(
                            'component_id' => $component_id,
                            'meta_key' => $key,
                            'meta_value' => $value,
                            'minutia' => null,
                        );
                    }

                   $vce->db->insert('components_meta', $records);

                }

            }

            // if sub_components, recursve call back to this function with parent component id
            if ($sub_components) {

                // prevent any error
                $component_id = isset($component_id) ? $component_id : '0';

                $level++;

                self::process_recipe($sub_components, $recipe_object, $component_id, $level);

            }

        }

    }

    /**
     * Delete
     */
    public function delete($input) {

        $parent_url = self::delete_component($input);

        if (isset($parent_url)) {

            echo json_encode(array('response' => 'success', 'message' => 'Delete!', 'form' => 'delete'));
            return;
        }

        echo json_encode(array('response' => 'error', 'procedure' => 'update', 'message' => "Error"));
        return;

    }

    /**
     * Update JSON
     */
    public function update_json($input) {

        global $vce;
       
		// update the recipe json within the components_meta table
		if (isset($input['recipe_json']) && $input['recipe_json'] != '') {
			//remove the html entities which were inserted by input()
			$input['recipe_json'] = html_entity_decode($input['recipe_json']);
			$update = array('meta_value' => $input['recipe_json']);
			$update_where = array('component_id' => $input['component_id'], 'meta_key' => 'recipe');
			$update_result = $vce->db->update('components_meta', $update, $update_where);
		}

        if ($update_result == true) {

            echo json_encode(array('response' => 'success', 'message' => 'JSON altered!', 'form' => 'update_json'));
            return;
        }

        echo json_encode(array('response' => 'error', 'procedure' => 'update_json', 'message' => "Error"));
        return;

    }

}