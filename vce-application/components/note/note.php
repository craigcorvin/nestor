<?php

class Note extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			// Personal Learning Goal Setting
			'name' => 'Note',
			'description' => 'Allows for a note',
			'category' => 'site',
			'recipe_fields' => array('auto_create','title')
		);
	}


	public function check_access($each_component, $vce) {
	
		return true;
	}

	public function allow_sub_components($each_component, $vce) {
	
		if (!empty($each_component->notes)) {
			return true;
		}
	
		return false;
	}


	/**
	 *
	 */
	public function as_content($each_component, $vce) {
	
		$accordion_status = !empty($each_component->notes) ? 'accordion-open' : 'accordion-closed';
		$accordion_activated = !empty($each_component->notes) ? 'disabled' : 'active';
		$aria_expanded = !empty($each_component->notes) ? true : false;
		$submit_message = !empty($each_component->notes) ? 'Update' : 'Save';

		$accordion = <<<EOF
<div class="accordion-container $accordion_status">
<div class="accordion-heading" role="heading" aria-level="2">
<button class="accordion-title $accordion_activated" role="button" aria-expanded="$aria_expanded" aria-controls="accordion-content-$each_component->component_id" id="accordion-title-$each_component->component_id">
<span>Personal Learning Goal Setting</span>
</button>
</div>
<div class="accordion-content" id="accordion-content-$each_component->component_id" role="region" aria-labelledby="accordion-title-$each_component->component_id">
EOF;


		$vce->content->add('main', $accordion);
		
		$content = null;

		if ($each_component->created_by == $vce->user->user_id) {

			// create
			$dossier = array(
			'type' => $each_component->type,
			'procedure' => 'update',
			'component_id' => $each_component->component_id,
			'created_at' => $each_component->created_at
			);

			// generate dossier
			$dossier_for_update = $vce->generate_dossier($dossier);

			$content = <<<EOF
<form id="update" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_update">
EOF;

		}
		
		$input = array(
		'type' => 'textarea',
		'name' => 'notes',
		'value' => $each_component->notes,
		'data' => array(
		'rows' => '10'
		)
		);
		
		if ($each_component->created_by != $vce->user->user_id) {
			$input['data']['disabled'] = 'disabled';
		} else {
			$input['data']['placeholder'] = 'Enter your Personal Learning Goal';
		}
		
		$notes_input = $vce->content->create_input($input,'Personal Learning Goal');

		$content .= <<<EOF
$notes_input
EOF;

		if ($each_component->created_by == $vce->user->user_id) {

			$content .= <<<EOF
<input type="submit" value="$submit_message">
</form>
EOF;

		}

		// $content = $vce->content->accordion('Personal Learning Goal Setting', $input, (!empty($each_component->notes) ? true :  false), (!empty($each_component->notes) ? true :  false));
	
		$vce->content->add('main', $content);
	
	}
	
	
	/**
	 *
	 */
	public function as_content_finish($each_component, $vce) {
	
		$vce->content->add('main', '</div></div>');
	
	}

}




