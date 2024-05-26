<?php

class Item extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Item',
			'description' => 'Allows users to create a URL specific component',
			'category' => 'url',
			'recipe_fields' => array(
			'title',
			'potence',
			'template',
			'url_configurable' => array(
				'label' => array('message' => 'URL Configurable'),
				'type' => 'checkbox',
				'name' => 'url_configurable',
				'selected' => isset($recipe['url_configurable']) ? $recipe['url_configurable'] : null,
				'flags' => array (
				'label_tag_wrap' => 'true'
				),
				'options' => array(
				'label' => 'URL Configurable', 'value' => 'true'
				)
			),
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
			),
			'filtering_enabled' => array(
				'label' => array('message' => 'Filtering Enabled'),
				'type' => 'checkbox',
				'name' => 'filtering_enabled',
				'selected' => isset($recipe['filtering_enabled']) ? $recipe['filtering_enabled'] : null,
				'flags' => array (
				'label_tag_wrap' => 'true'
				),
				'options' => array(
				'label' => 'Enable display filtering', 'value' => 'true'
				)
			)
			)
		);
	}
	

	/**
	 * 
	 */
	public function recipe_manifestation($each_recipe_component, $vce) {
	
		if (isset($each_recipe_component->filtering_enabled)) {
		
			$vce->item_filters = array();

			// add javascript to page
			$vce->site->add_script(dirname(__FILE__) . '/js/script.js', 'jquery');
		
			// add stylesheet to page
			$vce->site->add_style(dirname(__FILE__) . '/css/style.css','item-style');
			
			$vce->content->add('main','<div class="item-container"><div class="item-links">');
	
		}
	
	}
	
	/**
	 * 
	 */
	public function recipe_manifestation_finish($each_recipe_component, $vce) {
	
		if (isset($each_recipe_component->filtering_enabled)) {
		
			$query_string = json_decode($vce->query_string, true);
		
			$options = array(array('name' => '','value' => ''));
		
			asort($vce->item_filters);
			
			foreach ($vce->item_filters as $key=>$value) {
			
				$filter = array(
					'name' => $value,
					'value' => $key
				);
				
				if (isset($query_string['filter']) && $query_string['filter'] == $key) {
				
					$filter['selected'] = true;
				
				}
				
				$options[] = $filter;
			
			}
			
			// email input
			$input = array(
			'type' => 'select',
			'name' => 'filter',
			'options' => $options,
			'data' => array(
			'class' => 'filter-select'
			)
			);
			
	
			$filter = $vce->content->create_input($input,'Filter ' . $each_recipe_component->title . 's');
	
			$vce->content->add('main','</div><div class="item-filter">' . $filter . '</div></div>');
		
		}
	
	}

	public function as_link($each_component, $vce) {
	
		$display_link = true;
	
		$title = isset($each_component->title) ? $each_component->title : get_class($this);
		$class_name = strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/',"-$1", get_class($this))) . '-link';
		$class = 'link-container ' . $class_name . ' anchor-tag-' . $each_component->sequence;
		
		$query_string = json_decode($vce->query_string, true);
		
		if (!empty($query_string['filter'])) {
			$display_link = false;
		}
		
		if (isset($each_component->filter_by)) {
		
			foreach (explode(',', $each_component->filter_by) as $each_filter) {
			
			
				$each_filter_working = preg_replace('/\s+/', '-', strtolower(trim($each_filter)));
				$each_filter_item = preg_replace('/[^a-zA-Z0-9\-]/', '', $each_filter_working);
				
				if (isset($vce->item_filters) && !isset($vce->item_filters[$each_filter_item])) {
					$vce->item_filters[$each_filter_item] = $each_filter;
				}
			
				if (isset($query_string['filter']) && $query_string['filter'] == $each_filter_item) {
					$display_link = true;
				}
				
			}
		
		}
		
		if ($display_link) {
		
			$vce->content->add('main','<div class="' . $class . '"><a href="' . $vce->site->site_url . '/' . $each_component->url . '" title="' . $title . '">' . $title . '</a></div>'  . PHP_EOL);
	
		}
		
	}

	/**
	 *
	 */
	public function as_content($each_component, $vce) {

		// content
		if ($each_component->component_id == $vce->requested_id) {
	
			$vce->title = $each_component->title;
	
		} else {
		
			$vce->content->add('main','<div class="item">');
		
		}
	
	}

	/**
	 *
	 */
	public function as_content_finish($each_component, $vce) {
	
		if ($each_component->component_id != $vce->requested_id) {

			$vce->content->add('main','</div>');
		
		}
	
	}
	
	/**
	 *
	 */
	public function edit_component($each_component, $vce) {
	
		if ($vce->page->can_edit($each_component)) {
		
			// bringing in the checkurl library and nothing else
			$vce->site->add_script(null, 'jquery checkurl');

			// the instructions to pass through the form
			$dossier = array(
			'type' => $each_component->type,
			'procedure' => 'update',
			'component_id' => $each_component->component_id,
			'created_at' => $each_component->created_at
			);

			// generate dossier
			$dossier_for_update = $vce->generate_dossier($dossier);
			
			// create dossier for checkurl functionality
			$dossier = array(
			'type' => $each_component->type,
			'procedure' => 'checkurl',
			'current_url' => $each_component->url
			);

			// add dossier, which is an encrypted json object of details uses in the form
			$dossier_for_checkurl = $vce->generate_dossier($dossier);

			$input = array(
			'type' => 'text',
			'name' => 'title',
			'value' => $each_component->title,
			'class' => 'prevent-check-url',
			'data' => array(
			'tag' => 'required'
			)
			);
	
			$title_input = $vce->content->create_input($input, 'Title', 'Enter a Title');

			$input = array(
			'type' => (isset($each_component->recipe['url_editable']) ? 'text' : 'hidden'),
			'name' => 'url',
			'value' => $each_component->url,
			'data' => array(
			'tag' => 'required',
			'dossier' => $dossier_for_checkurl,
			'parent_url' => $each_component->parent->url . '/',
			'class' => 'check-url'
			)
			);
	
			if (isset($each_component->recipe['url_editable'])) {
				$url_input = $vce->content->create_input($input, 'URL', 'Enter a URL');
			} else {
				$url_input = $vce->content->input_element($input);
			}
			
			if (isset($each_component->recipe['filtering_enabled'])) {
			
				$input = array(
				'type' => 'text',
				'name' => 'filter_by',
				'value' => $each_component->filter_by
				);

				$filter_by_input = $vce->content->create_input($input, 'Filter by values (comma delineate)');
			
			} else {
			
				$filter_by_input = null;
			
			}
			
			if (isset($each_component->recipe['sequence_editable'])) {

				$input = array(
				'type' => 'text',
				'name' => 'sequence',
				'value' => $each_component->sequence,
				'data' => array(
				'tag' => 'required'
				)
				);
	
				$sequence_input = $vce->content->create_input($input, 'Order Number', 'Enter an Order Number');
			
			} else {
			
				$sequence_input = null;
			
			}

			$content = <<<EOF
<form id="update_$each_component->component_id" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_update">
$title_input
$url_input
$filter_by_input
$sequence_input
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
			
					$content .= <<<EOF
<form id="delete_$each_component->component_id" class="delete-form float-right-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="submit" value="Delete">
</form>
EOF;

				}

			$vce->content->add('admin', $vce->content->accordion('Edit ' . $each_component->title, $content));
		
		}
	
	}
	
	
	/**
	 *
	 */
	public function add_component($recipe_component, $vce) {

		// using 'potence' recipe field to control if component type can be created when accessing the component.
		if (isset($recipe_component->component_id) && !isset($recipe_component->recipe['potence'])) {
			return;
		}
		
		// bringing in the checkurl library and nothing else
		$vce->site->add_script(null, 'jquery checkurl');
	
		// create dossier
		$dossier_for_create = $vce->generate_dossier($recipe_component->dossier);
	
		// create dossier for checkurl functionality
		$dossier = array(
		'type' => $recipe_component->type,
		'procedure' => 'checkurl'
		);

		// add dossier, which is an encrypted json object of details uses in the form
		$dossier_for_checkurl = $vce->generate_dossier($dossier);
		
		$inputs = null;

		$input = array(
		'type' => 'text',
		'name' => 'title',
		'data' => array(
		'tag' => 'required'
		)
		);
	
		$inputs .= $vce->content->create_input($input, 'Title', 'Enter a Title');

		$input = array(
		'type' => (isset($recipe_component->url_configurable) ? 'text' : 'hidden'),
		'name' => 'url',
		'data' => array(
		'tag' => 'required',
		'dossier' => $dossier_for_checkurl,
		'parent_url' => isset($recipe_component->parent_url) ? $recipe_component->parent_url . '/' : $vce->requested_url . '/',
		'class' => 'check-url'
		)
		);
	
		if (isset($recipe_component->url_configurable)) {
			$inputs .= $vce->content->create_input($input, 'URL', 'Enter a URL');
		} else {
			$inputs .= $vce->content->input_element($input);
		}
		
		if (isset($recipe_component->filtering_enabled)) {
		
			$input = array(
			'type' => 'text',
			'name' => 'filter_by'
			);

			$inputs .=  $vce->content->create_input($input, 'Filter by values (comma delineate)');
		
		}

		$content = <<<EOF
<form id="create_items" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_create">
$inputs
<input type="submit" value="Create">
<button class="link-button cancel-button">Cancel</button>
</form>
EOF;
		$vce->content->add('admin', $vce->content->accordion('Add A New ' . $recipe_component->title, $content));

	}
	

}