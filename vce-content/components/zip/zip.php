<?php

class Zip extends MediaType {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Zip (Media Type)',
			'description' => 'Adds Zip to Media',
			'category' => 'media'
		);
	}
    
	/**
	 * 
	 */
	public function display($each_component, $vce) {

    	$fileinfo_for_download = array(
    	'name' => $each_component->title,
    	'expires' => 300,
    	'path' => $each_component->created_by . '/' . $each_component->path,
    	'disposition' => 'attachment',
		'component' => $each_component
    	);
    	
    	$contents_for_download = '<p><div class="download-button"><a class="link-button download-button-zip" href="' . $vce->site->media_link($fileinfo_for_download) . '"><p class="download-text">' . $each_component->title . '</p>Download this Zip File to your computer</a></div></p>';
    	
     	$vce->content->add('main', $contents_for_download);
    
    
    
    }
    


	/**
	* Deals with asynchronous form input 
	* This is called from input portal forward onto class and function of component
	* @param array $input
	* @return calls component's procedure or echos an error message
	*/
	public function form_input($input) {

		// save these two, so we can unset to clean up $input before sending it onward
		//$type = trim($input['type']);
		$procedure = trim($input['procedure']);
		// unset component and procedure
		unset($input['procedure']);
		// check that protected function exists
		if (method_exists($this, $procedure)) {
		// call to class and function
		return $this->$procedure($input);
		}
		echo json_encode(array('response' => 'error','message' => 'Unknown Procedure'));
		return;
	}




    /**
     * file uploader needed
     */
    public static function file_upload() {
	 	return true;
	}

	/**
     * file download
     */
    public static function file_download($input) {

		global $vce;
		
		// get path for file
		$srcfile = $input['file_path'];
		//define where files will be downloaded
		$dst_folder = BASEPATH . 'downloads';
		
		// make download directory if it doesn't exist
		if (!file_exists($dst_folder)) {
			mkdir($dst_folder, 0775, true);
		
			// add empty index.php file
			$content = "/* empty file */";
			$fp = fopen($dst_folder . "/index.php","wb");
			fwrite($fp,$content);
			fclose($fp);			

		}
		
		//remove whitespaces from title
		$file_title = str_replace(' ', '_', $input['file_title']);
		$uid = str_replace(' ', '-', $input['file_title']);
		
		//create time-stamped name for file
		$dst_file = '/' . $file_title . '_df_' . time() . '.zip';
		//get full path for destination (downloadable) file
		$dst_file_full_path = $dst_folder . $dst_file;
		//get url for downloadable file
		$dst_url = $vce->site->site_url . '/downloads' . $dst_file;

		//tell $vce that when the page reloads, we will download this file
		$vce->site->add_attributes('download_file_path',$dst_file_full_path);
		$vce->site->add_attributes('download_file_url', $dst_url);
		
		//copy file to download folder
		copy($srcfile, $dst_folder . $dst_file);

		//erase any download file which is more than 1000 seconds old
		$files_in_download = glob($dst_folder . '/*.zip'); // get all file names
			foreach($files_in_download as $file){ // iterate files
				if(is_file($file)) {
					preg_match('/df_([0-9]+)\.zip/', $file, $file_timestamp);
					if ($file_timestamp[1] < time() - 1000) {
						unlink($file);
					}
				}
			}

		// reload page to download file
		echo json_encode(array('response' => 'success', 'message' => 'Downloading', 'form' => 'create', 'action' => 'reload', 'url' => $dst_url, 'uid' => $uid));

		return true;
   }

	/**
	 * a way to pass file extensions to the plupload to limit file selection
	 */
	public static function file_extensions() {
		//{title:'Image files',extensions:'gif,png,jpg,jpeg'};
		return array('title' => 'Zip files','extensions' => 'zip');
	}
	 
	 
	  /**
	  * a way to pass the mimetype and mimename to vce-upload.php
	  * the minename is the class name of the mediaplayer.
	  * mimetype can have a wildcard for subtype, included after slash by adding .*
	  * http://www.iana.org/assignments/media-types/media-types.xhtml
	  */
	public static function mime_info() {
		return array(
	 	'application/zip' => get_class(),
	 	'application/x-zip' => get_class(),
	 	'application/octet-stream' => get_class(),
	 	'application/x-zip-compressed' => get_class()
	 	);
	}
	 


}