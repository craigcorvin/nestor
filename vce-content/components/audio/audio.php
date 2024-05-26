<?php

class Audio extends MediaType {

	public function component_info() {
		return array(
			'name' => 'Audio (Media Type)',
			'description' => 'Adds Audio to Media',
			'category' => 'media'
		);
	}

	/**
	 * 
	 */
	public function display($each_component, $vce) {
    	
    	$fileinfo = array(
    	'expires' => 300,
    	'path' => $each_component->created_by . '/' . $each_component->path
    	);
    	
    	// saving previous in case of later-ons
    	// <video controls="" autoplay="" name="media"><source src="" type="audio/mpeg"></video>
        		
     	$vce->content->add('main', '<audio controls><source src="' . $vce->site->media_link($fileinfo) . '" type="audio/mpeg">Your browser does not support the audio element.</audio>');
    	
    	// $vce->content->add('main', '<video controls="" autoplay="" name="media"><source src="' . $vce->site->media_link($fileinfo) . '" type="audio/mpeg"></video>');

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
	 
		//aac,mp4,m4a,mp1,mp2,mp3,mpg,mpeg,oga,ogg,wav,webm
	 
	 	return array('title' => 'Audio files','extensions' => 'm4a,mp3,aif,aiff,wav');
	 }
	 
	 /**
	  * a way to pass the mimetype and mimename to vce-upload.php
	  * the minename is the class name of the mediaplayer.
	  * mimetype can have a wildcard for subtype, included after slash by adding .*
	  */
	 public static function mime_info() {
	 	return array(
	 	'audio/x-m4a' => get_class(),
	 	'audio/mpeg' => get_class(),
	 	'audio/x-aiff' => get_class(),
	 	'audio/vnd.wav' => get_class()
	 	);
	 }

}