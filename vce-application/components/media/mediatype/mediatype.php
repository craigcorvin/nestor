<?php

class MediaType {

	/**
	 * using the constructor to add configuration and who knows what else
	 */
	public function __construct() {

		// add configuration values if they exist
		$this->configuration = $this->get_component_configuration();

	}

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Media Type',
			'description' => 'Base class for all media types, such as Image.',
			'category' => 'media',
			'typename' => null
		);
	}

	/**
	 * component has been installed
	 */
	public function installed() {
	}

	/**
	 * component has been activated
	 */
	public function activated() {
	}
	
	/**
	 * component has been disabled
	 */
	public function disabled() {
	}
	
	/**
	 * component has been removed, as in deleted
	 */
	public function removed() {
	}
	
	/**
	 * this will hopefully prevent an error when a component has been disabled
	 */
	public function preload_component() {
		return false;
	}

	/**
	 * display media
	 */
	public function display($each_component, $vce) {
	
		if (!isset(json_decode($vce->site->enabled_mediatype, true)[$each_component->media_type])) {
		
			$delete_button = null;

			if ($each_component->created_by == $vce->user->user_id ) {

				// the instructions to pass through the form
				$dossier = array(
				'type' => $each_component->type,
				'procedure' => 'delete',
				'component_id' => $each_component->component_id,
				'created_at' => $each_component->created_at
				);

				// generate dossier
				$dossier_for_delete = $vce->generate_dossier($dossier);
			

			
				$delete_button = <<<EOF
<form id="delete_$each_component->component_id" class="delete-form inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="submit" value="Delete File">
</form>
EOF;

			}
		
			$vce->content->add('main','<div class="form-message form-error">' . $each_component->media_type . ' This unsupported file type cannot be displayed  ' . $delete_button . '</div>');
			
		}
		
	}
	
	/**
	 * add media
	 */
	public static function add($each_component, $vce) {
	}
	
	/**
	 * edit media, called from edit_media_component() in media component
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
		'name' => 'sequence',
		'value' => $each_component->sequence,
		'data' => array(
		'tag' => 'required'
		)
		);
	
		$sequence_input = $vce->content->create_input($input, 'Order Number' , 'Enter an Order Number');

		$content_mediatype = <<<EOF
<div class="media-edit-container">
	<button class="no-style media-edit-open" title="edit">Edit</button>
	<div class="media-edit-form">
		<form id="update_$each_component->component_id" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
			<input type="hidden" name="dossier" value="$each_component->dossier_for_edit">
			$title_input
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

	/**
	 * check if the media will require the file uploader or not
	 */
	public static function file_upload() {
		return false;
	}
 
	/**
	 * a way to pass file extensions to the plupload to limit file selection
	 */
	public static function file_extensions() {
		/*
		{title : "Image files", extensions : "gif,png,jpg,jpeg"},
		{title : "PDF files", extensions : "pdf"},
		{title : "Office files", extensions : "doc,docx,ppt,pptx,xls,xlsx"},
		{title : "Audio files", extensions : "mp3"},
		{title : "Video files", extensions : "mpg,mpeg,mov,mp4,m4v,wmv,avi,asx,asf"}
		*/
	 	return array('title' => '','extensions' => '');
	}

	 /**
	  * a way to pass the mimetype and mimename to vce-upload.php
	  * the minename is the class name of the mediaplayer.
	  * mimetype can have a wildcard for subtype, included after slash by adding .*
	  * http://www.iana.org/assignments/media-types/media-types.xhtml
	  */
	public static function media_type() {
		/*
		return array(
		'image/.*' => 'Image'
		);
		*/
		return null;
	}
	
	
	/**
	 * Language localization method for static situations
	 *
	 * @param string $phrase // if not found this method will return the value provided
	 * @param string $destination // can specify which langauge folder within the component directory to select from
	 * @return string
	 *
	 * $this->lang('*array_key_for_phrase*');
	 */
    public static function language($phrase, $destination = null) {
		
			$class = static::class;

			return self::localization($class, $phrase, $destination = null);
    }
	
	/**
	 * Language localization method for static situations
	 *
	 * @param string $phrase // if not found this method will return the value provided
	 * @param string $destination // can specify which langauge folder within the component directory to select from
	 * @return string
	 *
	 * $this->lang('*array_key_for_phrase*');
	 */
    public function lang($phrase, $destination = null) {
		
		$class = get_class($this);
		
		return self::localization($class, $phrase, $destination = null);

    }
    
	/**
	 * Language localization method
	 * l10n = numeronyn for localization
	 * ISO 639-3 Language Codes are used
	 *
	 * @param string $class
	 * @param string $phrase // if not found this method will return the value provided
	 * @param string $destination // can specify which langauge folder within the component directory to select from
	 * @return string
	 */
   public static function localization($class, $phrase, $destination = null) {
    
    	global $vce;
    	
    	// ISO 639-3 Language Code, defaults to english
    	$site_language = !empty($vce->site->site_language) ? $vce->site->site_language : 'Eng';
    
    	// from user object $vce->user->language = 'so;
    	$user_language = (!empty($vce->user->language_selected) && isset($vce->site->l10n[$vce->user->language_selected])) ? $vce->user->language_selected : $site_language;

    	// set in site class
    	// $vce->site->localization property where localization will be stored
    	if (!isset($vce->site->l10n)) {
    		$vce->site->l10n = array();
		}

		$activated_components = json_decode($vce->site->activated_components, true);
		
		if (isset($activated_components[$class])) {
		
			preg_match('/(.+)\/.+\.php$/', $activated_components[$class], $matches);
			
			if (isset($matches[1])) {
				// path to this class
				$component_path = BASEPATH . $matches[1];
			} else {
				// fall back
				$component_path = dirname(__FILE__);
			}
			
		}
		
    	// have we tried to load this before?
    	if (empty($vce->site->l10n[$class])) {
    	
			// full file path
			$file_path = $component_path . '/lang/' . strtolower($user_language) . '.php';
	
			// load the file into the site propery
			if (file_exists($file_path)) {
		
				// get file contents
				$lexicon = require($file_path);
	
				// add file contents to localization property on site object
				$vce->site->l10n[$class] = $lexicon;

			}
		
    	} else {
    	
    		$lexicon = $vce->site->l10n[$class];
    	
    	}
    	
    	// if destination has been set
    	if (isset($destination) && !isset($vce->site->l10n[$class][$destination])) {

			// full file path
			$file_path = $component_path . '/lang/' . strtolower($destination) . '.php';
	
			// load the file into the site propery
			if (file_exists($file_path)) {
		
				// get file contents
				$destination_lexicon = require($file_path);
	
				// add file contents to localization property on site object
				$lexicon[$destination] = $vce->site->l10n[$class][$destination] = $destination_lexicon;

			}
				
    	}
    	
    	// search within base localization
		if (!isset($lexicon[$phrase])) {
			// search phrase within base lexicon
			if (isset($vce->site->l10n[$user_language][$phrase])) {
				// get from base lexicon
				$lexicon[$phrase] = $vce->site->l10n[$class][$phrase] = $vce->site->l10n[$user_language][$phrase];			
			} else {
				// if the user language is different than the site language
				if ($site_language != $user_language) {
					if (isset($vce->site->l10n[$site_language][$phrase])) {
						// get from base lexicon
						$lexicon[$phrase] = $vce->site->l10n[$class][$phrase] = $vce->site->l10n[$site_language][$phrase];
					}
				}
			}
		}
    	
    	// if there is a localization propery, but the phrase cannot be found, try the site language file
    	if (!isset($lexicon[$phrase])) {
    	
			// search for value in the file associated with the site_language
			$file_path = $component_path . '/lang/' . strtolower($site_language) . '.php';

			// load the file into the site property
			if (file_exists($file_path)) {

				// get file contents
				$old_lexicon = require($file_path);
		
				// merge with existing existing localization property
				$lexicon = !empty($vce->site->l10n[$class]) ? array_merge($old_lexicon, $vce->site->l10n[$class]) : $old_lexicon;

				// add file contents to localization property on site object
				$vce->site->l10n[$class] = $lexicon;

			}
			
			// if we still cannot find a match for the phrase, then pass back the value
			if (!isset($lexicon[$phrase])) {
				$lexicon[$phrase] = $phrase;
				$vce->site->l10n[$class][$phrase] = $phrase;
			}
		
		}
		
		// if destination has been set
		if (isset($destination)) {
			if (isset($lexicon[$destination][$phrase])) {
				$lexicon[$phrase] = $lexicon[$destination][$phrase];
			} else {
				$vce->site->l10n[$class][$destination][$phrase] = $phrase;
				$lexicon[$phrase] = $phrase;
			}
		} 
		
		return $lexicon[$phrase];
    
    }
	 
	/**
	 * Get configuration fields for component and add to $vce object
	 * @return 
	 */
    public function get_component_configuration() {
    
        global $vce;
        $n = get_class($this);
        
        if (isset($vce->site->{$n})) {
        
			$value = $vce->site->{$n};
			$vector = $vce->site->{$n . '_minutia'};
			return json_decode($vce->site->decryption($value, $vector), true);
			
        }
        
    	return false;
        
	}
	
	/**
	 * hide configuration for component
	 */
	public function component_configuration() {
		return false;
	}

	/**
	 * hide from ManageRecipe
	 */
	public function recipe_fields($recipe) {
		return false;
	}
}