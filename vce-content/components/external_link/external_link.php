<?php

class ExternalLink extends MediaType {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'External Link (Media Type)',
			'description' => 'Adds an external link',
			'category' => 'media'
		);
	}
	
	/**
	 * display this website in an iframe and/or with a link button
	 */
	public function display($each_component, $vce) {
	
		// add stylesheet to page
		$vce->site->add_style(dirname(__FILE__) . '/css/style.css','external-link-style');
		
		$display = true;
		
		$contents = null;

		// make sure there is a http in front of the link
		if (strpos($each_component->link,'http') === false) {
			if (strpos($vce->site_url,'https') !== false) {
				$each_component->link = 'https://' . $each_component->link;
			} else {
				$each_component->link = 'http://' . $each_component->link;
			}
		}

		// all to get http header info
		$http_headers = @get_headers($each_component->link);
		
		// Use condition to check the existence of URL 
		if ($http_headers && (strpos($http_headers[0], '200') || strpos($http_headers[0], '303'))) { 

			// check for some non display options
			if (!empty($http_headers)) {
				foreach ($http_headers as $each_header) {
					if ($each_header == 'Content-Length: 0') {
						$display = false;
						break;
					}	
					if ($each_header == 'X-Frame-Options: SAMEORIGIN') {
						$display = false;
						break;
					}
				}
			} else {
				$display = false;
			}
		
			// check if the site is secure, but the iframe url is not
			if (strpos($vce->site_url,'https') !== false) {
				if (strpos($each_component->link,'https') === false) {
					$display = false;
				}
			}
		
			if ($display === true) {	
				$contents ='<div class="link-container"><iframe width="560" height="315" src="' . $each_component->link . '" frameborder="0" allowfullscreen></iframe></div>';
			} else {
				$contents ='<div>The linked website must be viewed in a separate window</div>';
			}
		
			$contents .= '<p><div class="download-button"><a class="link-button download-button-externallink" href="' . $each_component->link . '" target="_blank"><p class="download-text">Click here to open website<br>' . $each_component->title . '<br>' . $each_component->link . '</p></a></div></p>';
		
			$vce->content->add('main', $contents);
     	
     	} else {
     	
     		$contents = 'This external URL cannot be verified.';
     	
			$contents .= '<p><div class="download-button"><a class="link-button download-button-externallink" href="' . $each_component->link . '" target="_blank"><p class="download-text">Click here to open website<br>' . $each_component->title . '<br>' . $each_component->link . '</p></a></div></p>';

     		$vce->content->add('main', $contents);
     	
     	}
     	
     	
    }
    
	/**
	 * add 
	 */    
    public static function add($recipe_component, $vce) {

    	$input = array(
    	'type' => 'text',
    	'name' => 'title',
    	'data' => array(
    	'tag' => 'required'
    	)
    	);
    	
    	$textarea_input = $vce->content->create_input($input,'Title','Enter Title');

    	$input = array(
    	'type' => 'text',
    	'name' => 'link',
    	'data' => array(
    	'tag' => 'required'
    	)
    	);
    	
    	$link_input = $vce->content->create_input($input,'External Link URL','Enter External Link URL');

		$accordion = <<<EOF
<form id="create_media" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$recipe_component->dossier_for_create">
<input type="hidden" name="media_type" value="ExternalLink">
$textarea_input
$link_input
EOF;

		// load hooks
		// mediatype_add
		if (isset($vce->site->hooks['mediatype_add'])) {
			foreach($vce->site->hooks['mediatype_add'] as $hook) {
				$accordion .= call_user_func($hook, $recipe_component, $vce);
			}
		}

		$accordion .= <<<EOF
<input type="hidden" name="sequence" value="$recipe_component->sequence">
<input type="submit" value="Create">
</form>
EOF;

		return '<div id="text-block-container add-container">' . $vce->content->accordion('Add An External Link', $accordion) . '</div>';
    
    }
    
    
	/**
	 * edit
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
    	
    	$textarea_input = $vce->content->create_input($input,'Title','Enter Title');

    	$input = array(
    	'type' => 'text',
    	'name' => 'link',
    	'value' => $each_component->link,
    	'data' => array(
    	'tag' => 'required'
    	)
    	);
    	
    	$link_input = $vce->content->create_input($input,'External Link URL','Enter External Link URL');

     	$input = array(
    	'type' => 'text',
    	'name' => 'sequence',
    	'value' => $each_component->sequence,
    	'data' => array(
    	'tag' => 'required'
    	)
    	);
    	
    	$select_input = $vce->content->create_input($input,'Order Number','Enter an Order Number');


		$content_mediatype = <<<EOF
<div class="media-edit-container">
<button class="no-style media-edit-open" title="edit">Edit</button>
<div class="media-edit-form">
<form id="update_$each_component->component_id" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$each_component->dossier_for_edit">
$textarea_input
$link_input
$select_input
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