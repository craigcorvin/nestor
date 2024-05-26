<?php

class YouTube extends MediaType {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'YouTube (Media Type)',
			'description' => 'Adds YouTube embedding to Media',
			'category' => 'media'
		);
	}    
	/**
	 * 
	 */
	public function display($each_component, $vce) {

		// add stylesheet to page
		$vce->site->add_style(dirname(__FILE__) . '/css/style.css','youtube-style');
		$vce->site->add_script(dirname(__FILE__) . '/js/script.js','youtube-script');

		// https://www.youtube.com/watch?v=dUOQ5vv4byQ
   		//  https://youtu.be/dUOQ5vv4byQ
   		
   		// &feature=youtu.be
   		$link_parts = preg_split('/&{1}/', $each_component->link);
   
		preg_match('/[^\/=]+$/',$link_parts[0],$link);
		// $vce->log("youtube link for " . $each_component->component_id . " " . $each_component->link . "link: " . $link[0]);
		// the instructions to pass through the form
		
		$dossier = array(
			'type' => 'YouTube',
			'procedure' => 'update',
			'component_id' => $each_component->component_id,
			'created_at' => $each_component->created_at,
			'media_type' => $each_component->media_type
		);
		$content_initiate_player = "";

		if (!isset($vce->youtube_javascript)) {

			$vce->youtube_javascript = true;
			
			$content_initiate_player .= 
<<<EOF
<script>
//load the IFrame Player API code asynchronously.
loadYTAPI();
</script>
EOF;
		}

		//<div> with id of player will get replaced with iframe, youtube player instantiated when script run	
		$content_initiate_player .= 
<<<EOF
<div class="vidbox" player="player-$each_component->component_id">
<button class="vidbox-click-control"></button>
	<div class="vidbox-content">
		<button class="vidbox-content-close">X</button>
		<div class="vidbox-content-area"></div>
	</div>
	
	
	<iframe id="player-$each_component->component_id"
        src="https://www.youtube.com/embed/$link[0]?enablejsapi=1&rel=0&controls=1"
        frameborder="0"
		style="border: solid 4px #37474F"
		class="player"
	></iframe>

</div>
EOF;

    	$vce->content->add('main', $content_initiate_player);
		
		//add script to page
		$vce->site->add_script(dirname(__FILE__) . '/js/script.js');

    }
    
	/**
	 * 
	 */    
    public static function add($recipe_component, $vce) {
    
		$input = array(
		'type' => 'text',
		'name' => 'link',
		'data' => array(
		'tag' => 'required'
		)
		);
	
		$link_input = $vce->content->create_input($input,'YouTube Video Link','Enter YouTube Video Link');

		$form_content = 
<<<EOF
<form id="create_media" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$recipe_component->dossier_for_create">
<input type="hidden" name="media_type" value="YouTube">
<input type="hidden" name="title" value="YouTube Video">
$link_input
<input type="submit" value="Create">
</form>
EOF;

		return $vce->content->accordion('Add A YouTube Video', $form_content);
    
    }
	
	/**
	 * 
	 */    
    public static function edit($each_component, $vce) {

		$input = array(
		'type' => 'text',
		'name' => 'title',
		'value' => $each_component->title,
		'data' => array(
		'tag' => 'required'
		)
		);
	
		$title_input = $vce->content->create_input($input, 'Title' , 'Enter a title');
	
		$input = array(
		'type' => 'text',
		'name' => 'link',
		'value' => $each_component->link,
		'data' => array(
		'tag' => 'required'
		)
		);
	
		$link_input = $vce->content->create_input($input, 'YouTube URL' , 'Enter YouTube URL');
	
		$input = array(
		'type' => 'text',
		'name' => 'sequence',
		'value' => $each_component->sequence,
		'data' => array(
		'tag' => 'required'
		)
		);
	
		$sequence_input = $vce->content->create_input($input, 'Order Number' , 'Enter an Order Number');

		$content_mediatype =
<<<EOF
<div class="media-edit-container">
<button class="no-style media-edit-open" title="edit">Edit</button>
<div class="media-edit-form">
<form id="update_$each_component->component_id" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$each_component->dossier_for_edit">
$title_input
$link_input
$sequence_input
<input type="submit" value="Update">
<button class="media-edit-cancel">Cancel</button>
</form>
<form id="delete_$each_component->component_id" class="float-right-form delete-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$each_component->dossier_for_delete">
<input type="submit" value="Delete">
</form>
</div>
</div>
EOF;

			return $content_mediatype;			
		
	}

}