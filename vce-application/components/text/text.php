<?php

class Text extends MediaType {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Text (Media Type)',
			'description' => 'Adds Text to Media',
			'category' => 'media'
		);
	}    
	/**
	 * Display the text block
	 */
	public function display($each_component, $vce) {
	
		// adding repeat of input component hook for xss sanitization
		if (isset($vce->site->hooks['input_sanitize_textarea'])) {
			foreach($vce->site->hooks['input_sanitize_textarea'] as $hook) {
				$each_component->text = call_user_func($hook, $each_component->text);
			}
		}
    		
    	$vce->content->add('main','<div class="media-text-block">' . nl2br($each_component->text) . '</div>');

    }
    
	/**
	 * Add form for text block
	 */    
    public static function add($recipe_component, $vce) {
    
    	$input = array(
    	'type' => 'textarea',
    	'name' => 'text',
    	'data' => array(
    	'tag' => 'required',
    	'rows' => '10'
    	)
    	);
    	
    	$textarea_input = $vce->content->create_input($input, Text::language('Text Block Content'), Text::language('Enter Text Block Content'));

		$create_text = Text::language('Create');

		$accordion = <<<EOF
<form id="create_media" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$recipe_component->dossier_for_create">
<input type="hidden" name="media_type" value="Text">
<input type="hidden" name="title" value="Text Block">
$textarea_input
<input type="hidden" name="sequence" value="$recipe_component->sequence">
<input type="submit" value="{$create_text}">
</form>
EOF;

		return '<div id="text-block-container add-container">' . $vce->content->accordion(Text::language('Add A Text Block'), $accordion) . '</div>';
    
    }
    
    
	/**
	 * Edit form for text block
	 */    
    public static function edit($each_component, $vce) {
    
    	$input = array(
    	'type' => 'textarea',
    	'name' => 'text',
    	'value' => $each_component->text,
    	'data' => array(
    	'tag' => 'required',
    	'rows' => 10
    	)
    	);
    	
    	$textarea_input = $vce->content->create_input($input,Text::language('Text Block Content'), Text::language('Enter Text Block Content'));

     	$input = array(
    	'type' => 'text',
    	'name' => 'sequence',
    	'value' => $each_component->sequence,
    	'data' => array(
    	'tag' => 'required'
    	)
    	);
    	
    	$select_input = $vce->content->create_input($input,Text::language('Order Number'), Text::language('Enter an Order Number'));

		$edit_text = Text::language('Edit');
		$update_text = Text::language('Update');
		$cancel_text = Text::language('Cancel');
		$delete_text = Text::language('Delete');

		$content_mediatype = <<<EOF
<mamediatext>
	<div class="media-edit-container">
		<button class="no-style media-edit-open" title="edit">{$edit_text}</button>
		<div class="media-edit-form">
			<form id="update_$each_component->component_id" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
			<input type="hidden" name="dossier" value="$each_component->dossier_for_edit">
			<input type="hidden" name="title" value="$each_component->title">
			$textarea_input
			$select_input
			<input type="submit" value="{$update_text}">
			<button class="media-edit-cancel">{$cancel_text}</button>
			</form>
EOF;

		if ($each_component->can_delete($vce)) {
			$content_mediatype .= <<<EOF
			<form id="delete_$each_component->component_id" class="float-right-form delete-form asynchronous-form" method="post" action="$vce->input_path">
				<input type="hidden" name="dossier" value="$each_component->dossier_for_delete">
				<input type="submit" value="{$delete_text}">
			</form>
EOF;
		}


		$content_mediatype .= <<<EOF
		</div>
	</div>
</mamediatext>
EOF;

		return $content_mediatype;
        
    }

}