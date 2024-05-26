<?php

class Upload extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
	
		$upload_max_filesize = ini_get("upload_max_filesize");
		$post_max_size =ini_get("post_max_size");
		$max_execution_time = ini_get("max_execution_time");
		$max_input_time = ini_get("max_input_time");
		$memory_limit = ini_get("memory_limit");
		$max_file_uploads = ini_get("max_file_uploads");
		
		$phpinfo = <<<EOF
<div>
<p>php.ini values</p>
upload_max_filesize: $upload_max_filesize (30M is recommended)<br>
post_max_size: $post_max_size (30M is recommended)<br>
max_execution_time: $max_execution_time (900 is recommended)<br>
max_input_time: $max_input_time (-1 is recommended)<br>
memory_limit: $memory_limit (256M is recommended)<br>
max_file_uploads: $max_file_uploads (100 is recommended)<br>
</div>
EOF;
	
		return array(
			'name' => 'Upload',
			'description' => 'Asynchronous upload endpoint' . $phpinfo,
			'category' => 'uploaders',
			'recipe_fields' => false
		);
	}

	/**
	 * things to do when this component is preloaded
	 */
	public function preload_component() {
		
		$content_hook = array (
			'page_requested_url' => 'Upload::file_upload_method',
			'media_add_file_uploader' => 'upload::add_file_uploader'
		);

		return $content_hook;

	}

	/**
	 * method of page_requested_url hook to upload file
	 */
	public static function file_upload_method($requested_url, $vce) {

		// add the path to upload
		$vce->media_upload_path = defined('MEDIA_UPLOAD_PATH') ? $vce->site->site_url . '/' . MEDIA_UPLOAD_PATH : $vce->site->site_url . '/upload';

		if ((!defined('MEDIA_UPLOAD_PATH') && strpos($requested_url, 'upload') !== false && strlen($requested_url) == 6) || (defined('MEDIA_UPLOAD_PATH') && strpos($requested_url, MEDIA_UPLOAD_PATH) !== false) && strlen($requested_url) == strlen(MEDIA_UPLOAD_PATH)) {
			
			// check if the size of the file is empty
			if ($_FILES["file"]["size"] == 0) {
 				die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: File size is zero. <div class="link-button cancel-button">Try Again</div>')));
			}
			
			// get mimetype supplied by plupload
			// if one is not supplied, then create a special one for verification
			$mimetype = !empty($_REQUEST['mimetype']) ? $_REQUEST['mimetype'] : 'application/' . $_REQUEST['extention'];
			
			// cycle through mediatypes that were passed through from functions media_type()
			foreach (json_decode($_REQUEST['mediatypes'], true) as $each_mediatype) {

				// check for subtype wildcard
				if (preg_match('/\.\*$/', $each_mediatype['mimetype'])) {

					// match primaray type
					if (explode('/', $each_mediatype['mimetype'])[0] == explode('/', $mimetype)[0]) {

						// class name of media player
						$mimename = $each_mediatype['mimename'];
			
						break;
	
					}

				} else {

					// match full
					if ($each_mediatype['mimetype'] == $mimetype) {

						$mimename = $each_mediatype['mimename'];
			
						break;
	
					}
	
				}

			}
			
			// no mimename name match was found.
			if (!isset($mimename)) {
				// should delete file, but for now leave it for error detection
				die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Mime type not acceptable. <div class="link-button cancel-button">Try Again</div>')));
			}
			
			// $_REQUEST['extention'] cannot really be trusted since it can be overloaded
			// get extention from the file name that is provided by the javascript uploader
			$file_name_provided = $_REQUEST['name'];
			$file_name_extention = pathinfo($file_name_provided, PATHINFO_EXTENSION);
		
			// make sure that the file extension of the file name agrees with the javascript uploader extention.
			if ($file_name_extention != $_REQUEST['extention']) {
				die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: File extention mismatch <div class="link-button cancel-button">Try Again</div>')));
			}

			// check to make sure file extention is approved
			$enabled_mediatype = json_decode($vce->site->enabled_mediatype, true);
			
			if (isset($enabled_mediatype[$mimename])) {
			
				// make sure we can load this
				if (file_exists(BASEPATH .  $enabled_mediatype[$mimename])) {
			
					// require our component file
					require_once(BASEPATH . $enabled_mediatype[$mimename]);
				
					$file_extensions = $mimename::file_extensions();
				
					$extensions = explode(',', $file_extensions['extensions']);
					
					// this is the problem
					if (!in_array($file_name_extention, $extensions)) {
						die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: File type not allowed / File extention not acceptable. <div class="link-button cancel-button">Try Again</div>')));
					}
					
					// redundant check for allowed mimetype
					
					// mimetype validation
					$mime_info = $mimename::mime_info();
					
					// get mimetype supplied by plupload
					// if one is not supplied, then create a special one for verification
					$mimetype = !empty($_REQUEST['mimetype']) ? $_REQUEST['mimetype'] : 'application/' . $file_name_extention;
				
					// cycle through mediatypes that were passed through from functions media_type()
					foreach ($mime_info as $each_mediatype=>$each_classname) {

						// check for subtype wildcard
						if (preg_match('/\.\*$/', $each_mediatype)) {
	
							// match primaray type
							if (explode('/', $each_mediatype)[0] == explode('/', $mimetype)[0]) {
	
								// class name of media player
								$mime_validation = $each_mediatype;
				
								break;
		
							}
	
						} else {

							// match full
							if ($each_mediatype == $mimetype) {
	
								$mime_validation = $each_mediatype;
				
								break;
		
							}
		
						}
	
					}

					// no mimename name match was found.
					if (!isset($mime_validation)) {
						// should delete file, but for now leave it for error detection
						die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: File type not allowed / Mime type not acceptable. <div class="link-button cancel-button">Try Again</div>')));
					}


				} else {
				
					die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: ' . $mimename . ' Media Type Component Not Found. <div class="link-button cancel-button">Try Again</div>')));

				}
			
			} else {
				die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: ' . $mimename . ' Media Type Component Does Not Exist. <div class="link-button cancel-button">Try Again</div>')));
			}
			
			// hook that can be used to hijack this method
			// upload_file_upload_method
			if (isset($vce->site->hooks['upload_file_upload_method'])) {
				foreach($vce->site->hooks['upload_file_upload_method'] as $hook) {
					call_user_func($hook, $requested_url, $vce);
				}
			}

			// php script for jQuery-File-Upload

			// 15 minutes execution time
			if (ini_get("max_execution_time") < 900) {
				ini_set('max_execution_time', 900);
			}
			
			// 256M memory limit
			if (rtrim(ini_get("memory_limit"),'M') < '256') {
				ini_set('memory_limit','256M');
			}

			header('Vary: Accept');
			if (isset($_SERVER['HTTP_ACCEPT']) &&
				(strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
				header('Content-type: application/json');
			} else {
				header('Content-type: text/plain');
			}

			// No cache
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");

			header("Access-Control-Allow-Headers: Content-Type,Content-Range,Content-Disposition");

			$chunks = isset($_SERVER['HTTP_CONTENT_RANGE']) ? true : false;

			if ($chunks) {
				// Parse the Content-Range header, which has the following form:
				// Content-Range: bytes 0-524287/2000000
				$content_range = preg_split('/[^0-9]+/', $_SERVER['HTTP_CONTENT_RANGE']);
	
				$start_range =  $content_range ? $content_range[1] : null;
				$end_range =  $content_range ? $content_range[2] : null;
				$size_range =  $content_range ? $content_range[3] : null;
		
				// is this the first chunk?
				$first_chunk = ($start_range == 0) ? true : false;
		
				// is this the last chunk?
				$last_chunk = (($end_range + 1) == $size_range) ? true : false;
			}
	
			// first time through upload
			if (!$chunks || $first_chunk) {

				// if no dossier is set, forward to homepage
				if (!isset($_REQUEST['dossier'])) {
					// echo json_encode(array('response' => 'error','message' => 'File Uploader Error: Dossier does not exist <div class="link-button cancel-button">Try Again</div>','action' => ''));
					header("Location: " . $vce->site->site_url);
					exit();
				}

				// decryption of dossier
				$dossier = json_decode($vce->user->decryption($_REQUEST['dossier'], $vce->user->session_vector));

				// check that component is a property of $dossier, json object test
				if (!isset($dossier->type) || !isset($dossier->procedure)) {
					echo json_encode(array('response' => 'error','message' => 'File Uploader Error: Dossier is not valid <div class="link-button cancel-button">Try Again</div>','action' => ''));
					exit();
				}
	
			}

			// Settings for the location where files are uploaded to
			if (defined('INSTANCE_BASEPATH')) {
				// this is the full server path to uploads and does not automatically add BASEPATH
				$upload_path = INSTANCE_BASEPATH . PATH_TO_UPLOADS;
			} else {
				if (defined('PATH_TO_UPLOADS')) {
					// use BASEPATH
					$upload_path = BASEPATH . PATH_TO_UPLOADS;
				} else {
					// default location for uploads
					// die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to create uploads directory. <div class="link-button cancel-button">Try Again</div>')));
					$upload_path = BASEPATH . 'vce-content/uploads';
				}
			}

			// If the directory doesn't exist, create it
			if (!is_dir($upload_path)) {
				if (!mkdir($upload_path, 0775, TRUE)) {
					die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to create uploads directory. <div class="link-button cancel-button">Try Again</div>')));
				}
			}

			if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
				// error can mean that the UPLOAD_SIZE_LIMIT is set too high, or that upload_max_filesize and post_max_size are too high
				die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: File size exceeds upload_max_filesize / post_max_size in php.ini  <div class="link-button cancel-button">Try Again</div>')));
			}

			// Get a file name
			if (isset($_REQUEST["name"])) {
				// extention name was pathinfo($_REQUEST["name"])['extension']
				$file_name = $_REQUEST["created_by"] . '_' . $_REQUEST["timestamp"] . '.' . $file_name_extention;
			} else {
				die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: File name not set.  <div class="link-button cancel-button">Try Again</div>')));
			}

			// the path to the file
			$file_path = $upload_path . '/' . $file_name;

			// This is here in case you need to write out to the log.txt file for debugging purposes
			// file_put_contents(BASEPATH . 'log.txt', $chunk . PHP_EOL, FILE_APPEND);

			// This error message should never be thrown, but is here to cover any and all possibilities,
			// opendir($upload_path)
			if (!$dir = opendir($upload_path)) {
				die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to open uploads directory.  <div class="link-button cancel-button">Try Again</div>')));
			}

			while (($file = readdir($dir)) !== false) {
				$temporary_file_path = $upload_path . '/' . $file;

				// If temp file is current file proceed to the next
				if ($temporary_file_path == "{$file_path}.part") {
					continue;
				}

				// Remove temp file if older than the max age and is not the current file
				if (preg_match('/\.part$/', $file) && (filemtime($temporary_file_path) < (time() - 3600))) {
					@unlink($temporary_file_path);
				}
			}
	
			closedir($dir);

			// Open temp file
			if (!$out = @fopen("{$file_path}.part", $chunks ? "ab" : "wb")) {
				die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to open output stream.  <div class="link-button cancel-button">Try Again</div>')));
			}

			if (!empty($_FILES)) {

				// error thrown by php
				if ($_FILES["file"]["error"]) {
					$message = array(
					0 => 'There is no error, the file uploaded with success',
					1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
					2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
					3 => 'The uploaded file was only partially uploaded',
					4 => 'No file was uploaded',
					6 => 'Missing a temporary folder',
					7 => 'Failed to write file to disk.',
					8 => 'A PHP extension stopped the file upload.',
					);
					die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: ' . $message[$_FILES["file"]["error"]] . ' <div class="link-button cancel-button">Try Again</div>')));
				}
	
				// Tells whether the file was uploaded via HTTP POST
				if (!is_uploaded_file($_FILES["file"]["tmp_name"])) {
					die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to move uploaded file. <div class="link-button cancel-button">Try Again</div>')));
				}

				// Read binary input stream and append it to temp file
				if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
					die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to open output stream. <div class="link-button cancel-button">Try Again</div>')));
				}
	
			} else {	
				if (!$in = @fopen("php://input", "rb")) {
					die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to open input stream. <div class="link-button cancel-button">Try Again</div>')));
				}
			}

			while ($buff = fread($in, 4096)) {
				fwrite($out, $buff);
			}

			@fclose($out);
			@fclose($in);


			// Check if file has been uploaded
			if (!$chunks || $last_chunk) {

				// If no post data was sent, delete file part and return error message
				if (!isset($_REQUEST['mimetype'])) {
		
					$temporary_file_path = "{$file_path}.part";
	
					// delete the temporary file
					@unlink($temporary_file_path);
	
					// Return an error
					die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Upload Size Limit Too Large. <div class="link-button cancel-button">Try Again</div>')));
		
				}

				if (!defined('BASEPATH')) {
					// Define BASEPATH as this file's directory
					// define('BASEPATH', str_replace('/vce-application/components/media','', dirname(__FILE__)) . '/');

					echo json_encode(array('response' => 'error','message' => 'File Uploader Error: No BASEPATH has been set <div class="link-button cancel-button">Try Again</div>','action' => ''));
					exit();
		
				}
					
				// This is here in case you need to write out to the log.txt file for debugging purposes
				// file_put_contents(BASEPATH . 'log.txt', json_encode($_POST) . PHP_EOL, FILE_APPEND);
				// file_put_contents(BASEPATH . 'log.txt','- - - - -' . PHP_EOL, FILE_APPEND);
				// file_put_contents(BASEPATH . 'log.txt', $_REQUEST['postnames'] . PHP_EOL, FILE_APPEND);
				// file_put_contents(BASEPATH . 'log.txt','- - - - -' . PHP_EOL, FILE_APPEND);
	
				if (!isset($dossier)) {
					$dossier = json_decode($vce->user->decryption($_REQUEST['dossier'], $vce->user->session_vector));
				}

				// unset what is not passed on
				unset($_POST['dossier'],$_POST['extention'],$_POST['mimetype'],$_POST['timestamp'],$_POST['mediatypes']);
	
				// rekey $_POST key=>value to $post
				foreach ($_POST as $post_key=>$post_value) {
					// prevent value overloading
					if (!isset($dossier->$post_key)) {
						$dossier->$post_key = $post_value;
					}
				}
				
				// validate created_by
				if (empty($dossier->created_by)) {
					echo json_encode(array('response' => 'error','message' => 'File Uploader Error: created_by is missing <div class="link-button cancel-button">Try Again</div>','action' => ''));
					exit();
				}
	
				// create user directory if it does not exist
				if (!file_exists($upload_path .  '/'  . $dossier->created_by)) {
					mkdir($upload_path .  '/'  . $dossier->created_by, 0775, TRUE);
				}
	
				$source_file_name = "{$file_path}.part";

				// create the new file name
				//  the extention value was pathinfo($file_path)['extension']
				$path = $dossier->created_by . '_' . time() . '.' . $file_name_extention;	

				$destination_file_name = $upload_path .  '/'  . $dossier->created_by . '/'  . $path;

				rename($source_file_name, $destination_file_name);

				// keeping this in case we decide it's needed at some point
				// $post['mime_type'] = $mimetype;
				$dossier->media_type = $mimename;
				$dossier->path = $path;

				// regenerate the dossier
				$post['dossier'] =  $vce->generate_dossier($dossier);

			}

			if (isset($post)) {
				die(json_encode($post));
			}

			// Return Success JSON-RPC response
			die(json_encode(array('status' => 'success', 'message' => 'File has uploaded.')));

		}
		
		// attempting to fix the UPLOAD_ERR_PARTIAL issue
		header("Connection: close");

	}
	
	/**
	 * File uploader method
	 */
	public static function add_file_uploader($recipe_component, $vce) {

		// path to image
		$path = $vce->site->path_to_url(dirname(__FILE__));

		// add a property to page to indicate the the uploader has been added
		$vce->file_uploader = true;
		
		// add javascript for fileupload
		$vce->site->add_script(dirname(__FILE__) . '/js/jquery.fileupload.js', 'jquery jquery-ui');
	
		// add javascript to page
		$vce->site->add_script(dirname(__FILE__) . '/js/script.js');
		
		// add style
		$vce->site->add_style(dirname(__FILE__) . '/css/style.css', 'media-style');

		// this may change to owner_id
		$user_id = $vce->user->user_id;

		$parent_id = $recipe_component->parent_id;
	
		if (defined('UPLOAD_SIZE_LIMIT')) {
			$chunk_size = $vce->convert_to_bytes(UPLOAD_SIZE_LIMIT);
		} else {
			// dividing this value by half to try and prevent errors.
			$chunk_size = min($vce->convert_to_bytes(ini_get('upload_max_filesize')),$vce->convert_to_bytes(ini_get('post_max_size'))) / 2;
		}

		// allow components to set chunk size.
		if (isset($vce->site->hooks['media_uploader_chunk_size'])) {
			foreach($vce->site->hooks['media_uploader_chunk_size'] as $hook) {
				$chunk_size = call_user_func($hook, $chunk_size);
			}
		}
	
		if (defined('MAX_FILE_LIMIT')) {
			$file_size_limit = $vce->convert_to_bytes(MAX_FILE_LIMIT);
		} else {
			$file_size_limit = $vce->convert_to_bytes('4G');
		}

		$cancel = Upload::language('Cancel');
		$verifying_file_text = Upload::language('Verifying File');
		$upload_completed_text = Upload::language('Upload Completed');
		$upload_complete_text = Upload::language('Upload Complete!');
		$queued_to_upload_text = Upload::language('Queued To Upload, Please Wait');


		// <div class="uploader-container">
		$content_media = <<<EOF
	<div class="progressbar-container">
		<div class="progressbar-title">Upload In Progress</div>
		<div class="progressbar-block">
			<div class="progressbar-block-left">
				<div class="progressbar">
					<div class="progress-chunks" style="position:absolute;padding-left:5px;"></div>
				</div>
			</div>
			<div class="progressbar-block-right"><a class="cancel-upload link-button" href="">$cancel</a></div>
		</div>
		<div class="progress-label" timestamp=0>0%</div>
	</div>
	<div class="verifybar-container">
		<div class="verifybar-title">$upload_completed_text</div>
		<div class="verifybar"><div id="verify-chunks" style="position:absolute;padding-left:5px;"></div></div>
		<div class="verifybar-label">$verifying_file_text</div>
	</div>
	<div class="progressbar-error"></div>
	<div class="progressbar-success">$upload_complete_text</div>
	<div class="progressbar-queued">$queued_to_upload_text</div>
	<div class="upload-browse" style="margin-bottom:-5px;">
EOF;

		// file input type
		$file_input = <<<EOF
		<input class="fileupload" type="file" name="file" path="$vce->media_upload_path" accept="" file_size_limit="$file_size_limit" chunk_size="$chunk_size">
		<button class="file-upload-cancel cancel-button link-button">$cancel</button>
EOF;

		$content_media .= $vce->content->create_input($file_input,Upload::language('Select A File To Upload'), null, 'input-padding');

		$content_media .= <<<EOF
	</div>
	<div class="upload-form" style="display:none;">
EOF;

		// the upload file form

		$upload_file = <<<EOF
		<input class="action" type="hidden" value="$vce->input_path">
		<input class="dossier" type="hidden" name="dossier" value="$recipe_component->dossier_for_create">
		<input class="inputtypes" type="hidden" name="inputtypes" value="[]">
		<input class="created_by" type="hidden" name="created_by" value="$user_id">
		<input class="parent_id" type="hidden" name="parent_id" value="$parent_id">
		<input class="mediatypes" type="hidden" name="mediatypes" value="">
EOF;
	
		// add title input
		$input = array(
		'type' => 'text',
		'name' => 'title',
		'data' => array('tag' => 'required','class' => 'resource-name')
		);
		
		$upload_file .= $vce->content->create_input($input, Upload::language('Title'), Upload::language('Enter a Title'));

		// load hooks
		// media_file_uploader
		if (isset($vce->site->hooks['media_file_uploader'])) {
			foreach($vce->site->hooks['media_file_uploader'] as $hook) {
				$upload_file .= call_user_func($hook, $recipe_component, $vce);
			}
		}
		
		$upload = Upload::language('Upload');
		$cancel = Upload::language('Cancel');

		$upload_file .= <<<EOF
		<button class="start-upload link-button" href="javascript:;">$upload</button> <button class="cancel-upload link-button cancel-button">$cancel</button>
EOF;

		$content_media .= $vce->content->accordion(Upload::language('Upload File'),$upload_file,true,true);

		// </div> of <div class="uploader-container">
		$content_media .= <<<EOF
</div>
EOF;

		return $content_media;
	
	}

}