<?php

class PDF extends MediaType {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'PDF (Media Type)',
			'description' => 'Adds PDF to Media',
			'category' => 'media'
		);
	}
	

	/**
	 * 
	 */
	public function display($each_component, $vce) {
	
		$prevent_inline_media = false;
	
		// prevent inline media items if parent requests it
		if (isset($each_component->parent->prevent_inline_media) && $each_component->parent->prevent_inline_media == 'on') {
			$prevent_inline_media = true;
		}
		
		if (!$prevent_inline_media) {

			// expires = how many seconds from now?
			// path = $each_component->created_by . '/' . $each_component->path
			// name = the name given to the media item
			// user_id = $user->user_id check the user id of the current user. 
			// disposition=attachment/inline
			// here's a list of content disposition values
			// http://www.iana.org/assignments/cont-disp/cont-disp.xhtml
			$fileinfo = array(
			'name' => $each_component->title,
			'expires' => 300,
			'path' => $each_component->created_by . '/' . $each_component->path,
			'component' => $each_component,
			'mime_type' => 'application/pdf'
			);
		
			$display_inline = false;
		
			if ((isset($this->configuration['browser_pdf_viewer']) && $this->configuration['browser_pdf_viewer'] == 'on')) {
			
				$pdf = $vce->site->media_link($fileinfo);
			
				$head = array_change_key_case(get_headers($pdf, 1));
				
				// check if size is too large
				if (!empty($head['content-length'])) {
								
					$filesize = $head['content-length'];

					if ($filesize < 4000000) {
						$display_inline = true;
					}
					
				}
				
				// check if 200 http header message
				// if  (isset($http_headers[0]) && ( strpos($http_headers[0], '200') || strpos($http_headers[0], '303') ) ) {
				//	$display_inline = true;
				// } else {
				//	$display_inline = false;
				// }
				
				// check if SAMEORIGIN is set
				// if (isset($head['X-Frame-Options']) && $head['X-Frame-Options'] == 'SAMEORIGIN') {
				// 	$display_inline = false;
				// }
				
				if ($display_inline) {
			
					$user_agent = $_SERVER['HTTP_USER_AGENT']; 

					// Safari is not happy with the idea of displaying a pdf as embedded base64, but will display despite the mimetype issue that Chrome has.
					if (stripos($user_agent, 'Safari') !== false && stripos($user_agent, 'Chrome') === false) {

						$contents = '<mamediapdf><div style="height:calc(100vw * .602);"><embed src="' .  $pdf . '" type="application/pdf" style="width:100%;height:100%;"></embed></div></mamediapdf>';

					} else {
			
						$file_contents = file_get_contents($pdf);
	
						$base64 = base64_encode($file_contents);
	
						$contents = '<mamediapdf><div style="height:calc(100vw * .602);"><embed src="data:application/pdf;base64,' .  $base64 . '" type="application/pdf" style="width:100%;height:100%;"></embed></div></mamediapdf>';
						
					}
					
					$vce->content->add('main', $contents);
		
				} else {

					$media_viewer_link = $vce->site->media_viewer_link($fileinfo);
		
					if (!empty($media_viewer_link)) {

						// create contents
						// $contents = '<mamediapdf><div style="height:calc(100vw * .602);" id="container-id"><iframe id="media-iframe" src="' . $media_viewer_link . '" style="width:100%;height:100%;" frameborder="0"></iframe></div></mamediapdf>';
						$contents = 'This PDF is too large to be displayed inline. You can download the PDF to view it.';

						$vce->content->add('main', $contents);
					
						// display title
						if (isset($each_component->recipe['display_title']) && $each_component->recipe['display_title'] == 'on') {
							$vce->content->add('main','<div class="media-title">' . $each_component->title . '</div>');
						}
		
					}
		
				}
				
			} else {
			
				// using external media viewer
				$media_viewer_link = $vce->site->media_viewer_link($fileinfo);
	
				if (!empty($media_viewer_link)) {

					// create contents
					$contents = '<mamediapdf><div style="height:calc(100vw * .602);" id="container-id"><iframe id="media-iframe" src="' . $media_viewer_link . '" style="width:100%;height:100%;" frameborder="0"></iframe></div></mamediapdf>';

					$vce->content->add('main', $contents);
				
				}
				
			}
    	
    	} else {
    	
    		// prevent inline display
    		// only show title and download ability
    	
    		$contents = '<div class="media-title">' . $each_component->title . '</div>';
    		
    		$vce->content->add('main', $contents);
    	
    	}
    	
    	$fileinfo_for_download = array(
    	'name' => $each_component->title,
    	'expires' => 300,
    	'path' => $each_component->created_by . '/' . $each_component->path,
    	'disposition' => 'attachment',
		'component' => $each_component,
		'mime_type' => 'application/pdf'
    	);
    	
    	$contents_for_download = '<mamediapdfdownload><p><div class="download-button"><a class="link-button download-button-pdf" href="' . $vce->site->media_link($fileinfo_for_download) . '"><p class="download-text">' . $each_component->title . '</p>Download this PDF to your computer</a></div></p></mamediapdfdownload>';
    	
     	$vce->content->add('main', $contents_for_download);

    }
    
    
    /**
     * file uploader needed
     */
   	public static function file_upload() {
	 	return true;
	}


	/**
	 * a way to pass file extensions to the plupload to limit file selection
	 */
	public static function file_extensions() {
		//{title:'Image files',extensions:'gif,png,jpg,jpeg'};
		return array('title' => 'PDF files','extensions' => 'pdf');
	}
	 
	 
	 /**
	  * a way to pass the mimetype and mimename to vce-upload.php
	  * the minename is the class name of the mediaplayer.
	  * mimetype can have a wildcard for subtype, included after slash by adding .*
	  * https://www.sitepoint.com/mime-types-complete-list/
	  */
	public static function mime_info() {
		return array(
		'application/pdf' => get_class()
		);
	}
	
	
	/**
	 * add config info for this component
	 */
	public function component_configuration() {
	
		global $vce;
		
		$input = array(
		'type' => 'checkbox',
		'name' => 'browser_pdf_viewer',
		'options' => array(
		'value' => 'on',
		'selected' => ((isset($this->configuration['browser_pdf_viewer']) && $this->configuration['browser_pdf_viewer'] == 'on') ? true :  false),
		'label' => 'Use built-in browser viewer for pdf files'
		)
		);
		
		return $vce->content->create_input($input,'Viewer for PDF');
	
	}

}