<?php

class Word extends MediaType {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Word (Media Type)',
			'description' => 'Adds Microsoft Word to Media',
			'category' => 'media'
		);
	}
	

	/**
	 * 
	 */
	public function display($each_component, $vce) {
    	
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
		'component' => $each_component
    	);
    	
		$media_viewer_link = $vce->site->media_viewer_link($fileinfo);
	
		if (!empty($media_viewer_link)) {

			// create contents
			$contents = '<mamediaword><div style="height:calc(100vw * .602);" id="container-id"><iframe id="media-iframe" src="' . $media_viewer_link . '" style="width:100%;height:100%;" frameborder="0"></iframe></div></mamediaword>';

			$vce->content->add('main', $contents);
	
		}
			
    	$fileinfo_for_download = array(
    	'name' => $each_component->title,
    	'expires' => 300,
    	'path' => $each_component->created_by . '/' . $each_component->path,
    	'disposition' => 'attachment',
		'component' => $each_component
    	);
    	
    	$contents_for_download = '<p><div class="download-button"><a class="link-button download-button-word" href="' . $vce->site->media_link($fileinfo_for_download) . '"><p class="download-text">Download</p>Download this Word doc to your computer</a></div></p>';
    	
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
	 	return array('title' => 'Word Files','extensions' => 'doc,dot,docx,dotx,rtf');
	 }
	 
	 
	 /**
	  * a way to pass the mimetype and mimename to vce-upload.php
	  * the minename is the class name of the mediaplayer.
	  * mimetype can have a wildcard for subtype, included after slash by adding .*
	  * https://www.sitepoint.com/mime-types-complete-list/
	  */
		public static function mime_info() {
	 	return array(
	 	'application/msword' => get_class(),
	 	'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => get_class(),
	 	'application/vnd.openxmlformats-officedocument.wordprocessingml.template' => get_class(),
	 	'text/rtf' =>  get_class(),
	 	'application/rtf' =>  get_class()
	 	);
	 }

}