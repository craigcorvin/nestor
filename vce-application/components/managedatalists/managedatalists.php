<?php

class ManageDatalists extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Manage Datalists',
			'description' => 'Add, edit and delete datalists',
			'category' => 'admin',
			'recipe_fields' => array('auto_create','title',array('url' => 'required'))
		);
	}
	

	/**
	 *
	 */
	public function as_content($each_component, $vce) {
	
		// add javascript to page
		$vce->site->add_script(dirname(__FILE__) . '/js/script.js', 'tablesorter');
		
		$content = "";
		$accordion = "";
				
		// datalist_id found in page object
		if (isset($vce->datalist_id)) {
		
			if (isset($vce->item_id)) {
			
				// get the name of the parent
				$query = "SELECT * FROM " . TABLE_PREFIX . "datalists WHERE item_id='"  . $vce->item_id . "'";
				$parent_info = $vce->db->get_data_object($query);
			
				// get the name of the parent
				$query = "SELECT * FROM " . TABLE_PREFIX . "datalists_items_meta WHERE item_id='"  . $vce->item_id . "' AND meta_key='name'";
				$parent_name = $vce->db->get_data_object($query);
				
			}
		
			$query = "SELECT meta_key, meta_value FROM " . TABLE_PREFIX . "datalists_meta WHERE datalist_id='"  . $vce->datalist_id . "'";
			$meta_data = $vce->db->get_data_object($query);
			
			// create datalist object with meta_data 
			$datalist = new StdClass();
			foreach ($meta_data as $each_meta_data) {		
				$key = $each_meta_data->meta_key;
				$datalist->$key = $each_meta_data->meta_value;
			}

			$query = "SELECT * FROM " . TABLE_PREFIX . "datalists_items WHERE datalist_id='" . $vce->datalist_id . "' ORDER BY sequence";
			$options = $vce->db->get_data_object($query);
			
			// starting value to prevent errrors
			$value = 1;

			foreach ($options as $value=>$each_option) {

				$query = "SELECT * FROM " . TABLE_PREFIX . "datalists_items_meta WHERE item_id='" . $each_option->item_id . "'";
				$meta_info = $vce->db->get_data_object($query);
				
					$meta_data = array();
					foreach ($meta_info as $meta_data_key=>$meta_data_value) {
						// get name of meta_key
						$this_key = $meta_data_value->meta_key;
						// add meta_value to this option
						$each_option->$this_key = $meta_data_value->meta_value;
					}
					
				// for sequence add one more to value
				$value = $value + 2;
				
				//
				$each_option->name = isset($each_option->name) ? $each_option->name : $each_option->datalist_id;

				// create dossier values
				$dossier_for_update = $vce->generate_dossier(array('type' => 'ManageDatalists','procedure' => 'update','item_id' => $each_option->item_id,'datalist_id' => $each_option->datalist_id));
				
				$dossier_for_delete_sub = $vce->generate_dossier(array('type' => 'ManageDatalists','procedure' => 'delete','item_id' => $each_option->item_id,'datalist_id' => $each_option->datalist_id));


				$input = array(
				'type' => 'text',
				'name' => 'name',
				'value' => $each_option->name,
				'data' => array(
				'tag' => 'required'
				)
				);
			
				$name = $vce->content->create_input($input,'Name','Enter Name');

				$input = array(
				'type' => 'text',
				'name' => 'sequence',
				'value' => $each_option->sequence,
				'data' => array(
				'tag' => 'required'
				)
				);
			
				$sequence = $vce->content->create_input($input,'Sequence','Enter Sequence');

				$accordion_content = <<<EOF
<form id="update-$each_option->item_id" class="asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_update">
$name
$sequence
<input type="submit" value="Update">
</form>
<form id="delete-$each_option->item_id" class="delete-form float-right-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_delete_sub">
<input type="submit" value="Delete">
</form>
EOF;

				if (isset($datalist->hierarchy)) {
	
					$query = "SELECT datalist_id FROM " . TABLE_PREFIX . "datalists WHERE item_id='"  . $each_option->item_id . "'";
					$child = $vce->db->get_data_object($query)[0];

					if (isset($child->datalist_id)) {
				
						// get name of first child
						$children_name = json_decode($datalist->hierarchy, true)[0];

						$dossier_for_edit_children = $vce->generate_dossier(array('type' => 'ManageDatalists','procedure' => 'edit','item_id' => $each_option->item_id, 'datalist_id' => $child->datalist_id));

$accordion_content .= <<<EOF
<p>
<form id="children-$each_option->datalist_id" class="asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_edit_children">
<input type="submit" value="Edit Child Datalist : $children_name">
</form>
</p>
EOF;
					// end if
					}
				
				// end if
				}
				
				
				if (isset($parent_info[0]->parent_id)) {
				
					$dossier_for_edit_parent = $vce->generate_dossier(array('type' => 'ManageDatalists','procedure' => 'edit','datalist_id' => $parent_info[0]->parent_id));

					$accordion_content .= <<<EOF
<p>
<form class="asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_edit_parent">
<input type="submit" value="Edit Parent Datalist">
</form>
</p>
EOF;

				}

	
				$accordion .=  $vce->content->accordion($each_option->name,$accordion_content);

			// end foreach
			}

			// make a nice name for the title
			// $datalist_name = isset($parent_name->meta_value) ? $parent_name->meta_value . ' / ' . $datalist->name : $datalist->name;

			$datalist_name = !empty($datalist->name) ? $datalist->name : "";
		
			$dossier_for_add = $vce->generate_dossier(array('type' => 'ManageDatalists','procedure' => 'add','datalist_id' => $vce->datalist_id));

			if (isset($parent_name)) {
		
				$datalist_name .= ' (' . $parent_name[0]->meta_value . ')';
		
			}
		
			$input = array(
			'type' => 'text',
			'name' => 'name',
			'value' => '',
			'data' => array(
			'tag' => 'required'
			)
			);
		
			$name = $vce->content->create_input($input,'Name','Enter Name');

			$input = array(
			'type' => 'text',
			'name' => 'sequence',
			'value' => '',
			'data' => array(
			'tag' => 'required'
			)
			);
		
			$sequence = $vce->content->create_input($input,'Sequence','Enter Sequence');

			$accordion_content = <<<EOF
<form id="add-id" class="asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_add">
$name
$sequence
<input type="submit" value="Add">
</form>
EOF;

			$accordion .=  $vce->content->accordion('+',$accordion_content);
	
			$accordion .=  '<button class="link-button cancel-button">Cancel</button>';

			$content .= $vce->content->accordion($datalist_name,$accordion, true, true);
		
		} else {
	
			$query = "SELECT * FROM " . TABLE_PREFIX . "datalists WHERE parent_id='0'";
			$datalists = $vce->db->get_data_object($query);
	
			$content .= <<<EOF
<table id="datalist" class="tablesorter">
<thead>
<tr>
<th></th>
<th>Name</th>
<th>Datalist</th>
<th>Type</th>
<th>Hierarchy</th>
<th>User Id</th>
<th>Component Id</th>
<th></th>
</tr>
</thead>
EOF;

		
			foreach ($datalists as $each_datalist) {
	
				$query = "SELECT meta_key, meta_value FROM " . TABLE_PREFIX . "datalists_meta WHERE datalist_id='" . $each_datalist->datalist_id . "'";
				$datalist_meta = $vce->db->get_data_object($query);
		
				$listinfo = array();	
	
				foreach ($datalist_meta as $each_meta) {
		
					$listinfo[$each_meta->meta_key ] = $each_meta->meta_value;
	
				}

				$dossier_for_edit = $vce->generate_dossier(array('type' => 'ManageDatalists','procedure' => 'edit','datalist_id' => $each_datalist->datalist_id));

				// edit
				$content .= <<<EOF
<tr>
<td class="align-center">
<form class="inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_edit">
<input type="submit" value="Edit">
</form>
</td>
EOF;

				$list_name = isset($listinfo['name']) ? $listinfo['name'] : null;
				$list_datalist = isset($listinfo['datalist']) ? $listinfo['datalist'] : null;
				$list_type = isset($listinfo['type']) ? $listinfo['type'] : null;

				$content .= '<td>' . $list_name . '</td>';
				$content .= '<td>' . $list_datalist . '</td>';
				$content .= '<td>' . $list_type . '</td>';
		
				if (isset($listinfo['hierarchy'])) {

					$content .= '<td>' . $listinfo['hierarchy'] . '</td>';
		
				} else {
		
					$content .= '<td></td>';
		
				}
		
				if ($each_datalist->user_id != 0) {

					$content .= '<td>' . $each_datalist->user_id . '</td>';
		
				} else {
			
					$content .= '<td></td>';
		
				}
		
				if ($each_datalist->component_id != 0) {
		
					$content .= '<td>' . $each_datalist->component_id . '</td>';
			
				} else {

					$content .= '<td></td>';	
		
				}

				$dossier_for_delete = $vce->generate_dossier(array('type' => 'ManageDatalists','procedure' => 'delete','item_id' => 'all','datalist_id' => $each_datalist->datalist_id));

				// delete
				$content .= <<<EOF
<td class="align-center">
<form class="delete-form inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="submit" value="Delete">
</form>
</td>
</tr>
EOF;
	
			}
	
		
			$content .= <<<EOF
</table>
EOF;

		}

		$accordion = $vce->content->accordion('Datalists', $content, true, true);

		$vce->content->add('main', $accordion);
	
	
	}

	
	/**
	 * Edit a datalist
	 */
	public function edit($input) {

		$vce = $this->vce;
		
		// add key value to page object on next load
		$vce->site->add_attributes('datalist_id',$input['datalist_id']);
		
		if (isset($input['item_id'])) {
			// add key value to page object on next load
			$vce->site->add_attributes('item_id',$input['item_id']);
		}
		
		echo json_encode(array('response' => 'success','procedure' => 'edit','action' => 'reload','delay' => '0', 'message' => 'session data saved'));
		return;
		
	}

	
	/**
	 * Create a new
	 */
	public function add($input) {

		$vce = $this->vce;
		
		// add key value to page object on next load
		$vce->site->add_attributes('datalist_id',$input['datalist_id']);
		
		$vce->add_datalist_item($input);

		echo json_encode(array('response' => 'success','procedure' => 'create','action' => 'reload','message' => 'Added'));
		return;
	}


	/**
	 * update datalist_item
	 */
	public function update($input) {
	
		$vce = $this->vce;
		
		// add key value to page object on next load
		$vce->site->add_attributes('datalist_id',$input['datalist_id']);
		
		$attributes = array (
		'item_id' => $input['item_id'],
		'relational_data' => array('sequence' => $input['sequence']),
		'meta_data' => array ('name' => $input['name'])
		);	
		
		// update item
		$vce->update_datalist_item($attributes);
				
		echo json_encode(array('response' => 'success','procedure' => 'update','action' => 'reload','message' => 'Updated'));
		return;
	
	}	


	/**
	 * Delete datalist
	 */
	public function delete($input) {
	
		$vce = $this->vce;
		
		// if item_id is set to delete all, then don't add attribute for reload
		if ($input['item_id'] != "all") {
			// add key value to page object on next load
			$vce->site->add_attributes('datalist_id',$input['datalist_id']);
		}
		
		$attributes = array (
		'item_id' => $input['item_id'], 
		'datalist_id' => $input['datalist_id']
		);
		
		// send to remove_datalist function
		$vce->remove_datalist($attributes);

		echo json_encode(array('response' => 'success','procedure' => 'delete','action' => 'reload','message' => 'Deleted'));
		return;
	
	}

}