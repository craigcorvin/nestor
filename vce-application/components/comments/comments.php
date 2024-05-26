<?php

class Comments extends Component {

    /**
     * basic info about the component
     */
    public function component_info() {
        return array(
            'name' => 'Comments',
            'description' => 'Allows for comments to be added.',
            'category' => 'site',
            'recipe_fields' => array('title')
        );
    }

    /**
     * things to do when this component is preloaded
     */
    public function preload_component() {

        $content_hook = array(
            'page_build_content_callback' => 'Comments::page_build_content_callback',
        );

        return $content_hook;

    }

	/**
	 * Reorder comments based on video timestamp
	 *
	 * @param [array] $sub_components
	 * @param [object] $each_component
	 * @param [object] $vce
	 * @return the ordered components
	 */
    public static function page_build_content_callback($sub_components, $each_component, $vce) {
    
    	$key = 'created_at';
    	
    	// look for a special case where there are timestamps related to the video media parent
    	foreach ($sub_components as $each_component) {
    		if (isset($each_component->timestamp)) {
    			$key = 'timestamp';
    			break;
    		}
    	}

		foreach ($sub_components as $each_component) {
			if ($each_component->type == "Comments") {
				$sub_components = $vce->sorter($sub_components, $key, 'asc', 'integer');
				break;
			}
		}

        return $sub_components;
    }

    /**
     *
     */
    public function as_content($each_component, $vce) {

        //add javascript
        $vce->site->add_script(dirname(__FILE__) . '/js/script.js','jquery');

        //add stylesheet
        $vce->site->add_style(dirname(__FILE__) . '/css/style.css', 'comments-style');

        $created_at = date("F j, Y, g:i a", $each_component->created_at);

        $site_url = defined('ASSETS_URL') ? ASSETS_URL : $vce->site->site_url;

        if (!empty($each_component->created_by)) {

            // send to function for user meta_data
            $user_info = $vce->user->get_users(array('user_ids' => $each_component->created_by));

			if (!empty($user_info)) {
            	$name = $user_info[0]->first_name . ' ' . $user_info[0]->last_name;
            } else {
           		$name = "Anonymous";
            }

            $user_image = $site_url . '/vce-application/images/user_' . ($each_component->created_by % 5) . '.png';

        } else {

            $name = "Anonymous";

            $user_image = $site_url . '/vce-application/images/user_1.png';

        }
        
        // id and parent for macomment tag to help with mobile app layout
        $macomment_tag = ' id="' . $each_component->component_id . '"';
        
        if ($each_component->parent->type == "Comments") {
			$macomment_tag .= ' parent="' . $each_component->parent_id . '"';
        }
        
        // convert line breaks
        $comment_text = nl2br($each_component->text, false);
        $comment_edit_text = $each_component->text;
        $timestamp = isset($each_component->timestamp) ? $each_component->timestamp : null;

        $content = <<<EOF
<macomment $macomment_tag>
<div id="comment-$each_component->component_id" class="indent-comment comment-id-$each_component->component_id">
<div class="comment-group-container">
<div class="comment-row">
<div class="user-image-comment"><img src="$user_image"></div>
<div class="comment-row-content arrow-box">
<p><span class="user-name-comment">$name</span><span class="comment-date">$created_at</span></p>
EOF;

        if ($timestamp) {

            $milliseconds = $timestamp;
            $seconds_full = floor($milliseconds / 1000);
            $seconds = sprintf("%02d", $seconds_full % 60);
            $minutes = str_replace('60', '00', sprintf("%02d", floor($seconds_full / 60)));
            $hours = floor($seconds_full / (60 * 60));

            $nice_timestamp = $hours . ':' . $minutes . ':' . $seconds;

            $content .= <<<EOF
<button class="comment-timestamp" timestamp="$timestamp">&#9654; $nice_timestamp</button>
EOF;

        }

        $content .= <<<EOF
<p class="comment-text">$comment_text</p>
EOF;

        if ($each_component->created_by == $vce->user->user_id || $vce->user->role_id == 1) {
            // normally this would be if ($page->can_edit($each_component)) {

            // the instructions to pass through the form with specifics
            $dossier = array(
                'type' => 'Comments',
                'procedure' => 'update',
                'component_id' => $each_component->component_id,
                'created_at' => $each_component->created_at,
                'title' => 'comment',
            );

            // add dossier, which is an encrypted json object of details uses in the form
            $dossier_for_update = $vce->generate_dossier($dossier);

			$input = array(
			'type' => 'textarea',
			'name' => 'text',
			'value' => $comment_edit_text,
			'data' => array(
			'class' => 'textarea-input comment-update-input',
			'tag' => 'required',
			'placeholder' => $this->lang('Enter Your Comment')
			)
			);
		
			$text_input = $vce->content->create_input($input, $this->lang('Comment'), $this->lang('Add A Comment'));

            $content .= <<<EOF
<div class="update-form">
<form id="add-comments-$each_component->component_id" class="asynchronous-form comment-update-$each_component->component_id comment-id-$each_component->component_id" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_update">
$text_input
<input type="submit" value="{$this->lang('Update Comment')}">
<button class="link-button update-form-cancel">{$this->lang('Cancel')}</button>
</form>
</div>
EOF;

        }

        // the instructions to pass through the form with specifics
        $dossier = array(
            'type' => 'Comments',
            'procedure' => 'create',
            'parent_id' => $each_component->component_id,
            'title' => 'comment',
        );

        // add dossier, which is an encrypted json object of details uses in the form
        $dossier_for_create = $vce->generate_dossier($dossier);
        
		$input = array(
		'type' => 'textarea',
		'name' => 'text',
		'data' => array(
		'class' => 'textarea-input comment-input comment-reply-input',
		'tag' => 'required',
		'placeholder' => $this->lang('Enter Your Comment')
		)
		);
		
		$text_input = $vce->content->create_input($input, $this->lang('Comment'), $this->lang('Add A Comment'));

        $content .= <<<EOF
<div class="reply-form">
<form id="reply-comments-$each_component->component_id" class="asynchronous-form comment-save-$each_component->component_id comment-id-$each_component->component_id" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_create">
$text_input
<input type="submit" value="{$this->lang('Save Comment')}">
<button class="link-button reply-form-cancel">{$this->lang('Cancel')}</button>
</form>
</div>
<div class="comment-reply-delete">
EOF;

        if (!isset($page->prevent_sub_components)) {
        
			if (isset($each_component->recipe['content_create']) && in_array($vce->user->role_id,explode('|', $each_component->recipe['content_create']))) {

            	$content .= <<<EOF
<button class="no-style link-inline reply-form-link">{$this->lang('Reply')}</button>
EOF;

			}
	
            if ((!empty($each_component->created_by) && $each_component->created_by == $vce->user->user_id) || $vce->user->role_id == 1) {
                // normally this would be if ($page->can_delete($each_component)) {

                // the instructions to pass through the form with specifics
                $dossier = array(
                    'type' => 'Comments',
                    'procedure' => 'delete',
                    'component_id' => $each_component->component_id,
                    'created_at' => $each_component->created_at,
                    'parent_url' => $vce->requested_url,
                );

                // add dossier, which is an encrypted json object of details uses in the form
                $dossier_for_delete = $vce->generate_dossier($dossier);

                $content .= <<<EOF
<div class="pipe"></div>
<button class="no-style link-inline edit-form-link">{$this->lang('Edit')}</button>
<div class="pipe"></div>
<button class="no-style link-inline delete-comment comment-delete-$each_component->component_id comment-id-$each_component->component_id" comment="$each_component->component_id" action="$vce->input_path" dossier="$dossier_for_delete">{$this->lang('Delete')}</button>
EOF;

            }

        }

        $content .= <<<EOF
</div>
</div>
<div class="comment-group-container indent-comment">
</div>
</div>
</div>
</macomment>
EOF;

        // $each_component->parent_id
        $vce->content->add('main', $content);

    }

    /**
     *
     */
    public function as_content_finish($each_component, $vce) {

        // $each_component->parent_id
        $vce->content->add('main', '</div>');

    }

    /**
     *
     */
    public function add_component($recipe_component, $vce) {

    	// starting container for comments-container
    	$vce->content->add('main', '<div class="comments-container">');
    	
    	// If there are multiple comment components at the same level in this recipe, add an additional recipe_key value to bucket name
    	$multiple = isset($recipe_component->dossier['recipe_key']) ? '_' . $recipe_component->dossier['recipe_key'] : null;

        $location = 'main_' . $recipe_component->parent_id . $multiple ;

        // create dossier
        $dossier_for_create = $vce->generate_dossier($recipe_component->dossier);

        // add javascript to page
        $vce->site->add_script(dirname(__FILE__) . '/js/script.js');

        //add stylesheet
        $vce->site->add_style(dirname(__FILE__) . '/css/style.css', 'comments-style');

        // user name
        $user_name = $vce->user->first_name . ' ' . $vce->user->last_name;

        $site_url = defined('ASSETS_URL') ? ASSETS_URL : $vce->site->site_url;

        // user image
        $user_image = $site_url . '/vce-application/images/user_' . ($vce->user->user_id % 5) . '.png';

		$content = null; 

		$input = array(
		'type' => 'textarea',
		'name' => 'text',
		'data' => array(
		'class' => 'textarea-input comment-input comment-create-input',
		'tag' => 'required',
		'placeholder' => $this->lang('Enter Your Comment')
		)
		);
		
		// each needs a unique css id
		
		$text_input_first = $vce->content->create_input($input, $this->lang('Comment'), $this->lang('Add A Comment'));
		
		$text_input_seocond = $vce->content->create_input($input, $this->lang('Comment'), $this->lang('Add A Comment'));
		
		$text_input_three = $vce->content->create_input($input, $this->lang('Comment'), $this->lang('Add A Comment'));
		
		$input = array(
		'type' => 'textarea',
		'name' => 'text',
		'value' => '{update-text}',
		'data' => array(
		'class' => 'textarea-input comment-input comment-update-input',
		'tag' => 'required',
		'placeholder' => $this->lang('Enter Your Comment')
		)
		);
		
		$update_input = $vce->content->create_input($input, $this->lang('Comment'), $this->lang('Add A Comment'));
		
		$combar = 'combar-' . $recipe_component->parent_id . $multiple;

        $content .= <<<EOF
<form id="add-comments-$combar" class="asynchronous-comment-form" method="post" action="$vce->input_path" combar="$combar" autocomplete="off">
	<input type="hidden" name="dossier" value="$dossier_for_create">
	<input type="hidden" name="title" value="comment">
	$text_input_first 
	<input type="submit" value="{$this->lang('Add Comment')}">
</form>
<maignore>
<div id="comments-asynchronous-content" class="asynchronous-content" style="display:none">
	<div id="comment-{component-id}" class="indent-comment">
		<div class="comment-group-container">
			<div class="comment-row">
				<div class="user-image-comment"><img src="$user_image"></div>
				<div class="comment-row-content arrow-box">
					<p><span class="user-name-comment">$user_name</span><span class="comment-date">{created-at}</span></p>
					<button class="comment-timestamp" timestamp="{timestamp}">&#9654; {nice-timestamp}</button>
					<p class="comment-text">{text}</p>
					<div class="reply-form">
						<form id="asynchronous-comments-$combar" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
							<input type="hidden" name="dossier" value="{dossier-for-create}">
							$text_input_seocond
							<input type="submit" value="{$this->lang('Save Comment')}">
							<button class="link-button reply-form-cancel">{$this->lang('Cancel')}</button>
						</form>
					</div>
					<div class="update-form">
						<form class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
							<input type="hidden" name="dossier" value="{dossier-for-update}">
							$update_input
							<input type="submit" value="{$this->lang('Update Comment')}"> <button class="link-button update-form-cancel">Cancel</button>
						</form>
					</div>
					<div class="comment-reply-delete">
						<button class="no-style link-inline reply-form-link">{$this->lang('Reply')}</button>
						<div class="pipe"></div>
						<button class="no-style link-inline edit-form-link">{$this->lang('Edit')}</button>
						<div class="pipe"></div>
						<button class="no-style link-inline delete-comment" comment="{component-id}" action="$vce->input_path" dossier="{dossier-for-delete}">{$this->lang('Delete')}</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</maignore>
<div class="comment-remote-container remote-container">
EOF;

		$accordion = <<<EOF
<form id="add-comments-remote-$combar" class="asynchronous-comment-form" method="post" action="$vce->input_path" combar="$combar" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_create">
<input type="hidden" name="title" value="comment">
$text_input_three
<input type="submit" value="$this->lang('Save Comment')">
</form>
EOF;
	
		$content .= $vce->content->accordion($this->lang('Add Comments'), $accordion, true, true);

		$content .= <<<EOF
</div>
EOF;

		// $this->lang('Add Comments')
		//  $recipe_component->title
		$accordion = $vce->content->accordion($this->lang('Add Comments'), $content, false, false, $combar . ' clickbar-container add-container ignore-admin-toggle');
		
        $vce->content->add($location, $accordion);

    }
    
    
    public function add_component_finish($each_component, $vce) {
    
    	// If there are multiple comment components at the same level in this recipe, add an additional recipe_key value to bucket name
    	$multiple = isset($each_component->dossier['recipe_key']) ? '_' . $each_component->dossier['recipe_key'] : null;

        // check for special property main_*parent_id*
        $add_component = 'main_' . (isset($each_component->component_id) ?  $each_component->component_id : $each_component->parent_id . $multiple);
        
        $content = $vce->content->output(array($add_component),true);
        
        // If there are exiting comments at this level, then "Add Comments" would be placed under comments

        if (!empty($content)) {
        
			// get specific content
        	// $content = $vce->content->$add_component[1][0][2][0];
        	$content = $vce->content->output(array($add_component),true);
        
            $vce->content->add('main', $content);
            
            // delete the temporary block of content
            $vce->content->extirpate($add_component);
            
        }
        
        // closing container for comments-container
		$vce->content->add('main', '</div>');
    }
    

    /**
     * Creates component
     * @param array $input
     * @return calls component's procedure or echos an error message
     */
    public function create($input) {
    
    	$vce = $this->vce;

		$created_at = time();
	
        // call to create_component, which returns the newly created component_id
        $component_id = $this->create_component($input);

        if ($component_id) {
        
			// the instructions to pass through the form with specifics
			$dossier = array(
			'type' => 'Comments',
			'procedure' => 'create',
			'parent_id' => $component_id,
			'title' => 'comment',
			);

			// add dossier, which is an encrypted json object of details uses in the form
			$dossier_for_create = $vce->generate_dossier($dossier);

		   	// the instructions to pass through the form with specifics
			$dossier = array(
			'type' => 'Comments',
			'procedure' => 'update',
			'component_id' => $component_id,
			'created_at' => $created_at,
			'title' => 'comment'
			);

			// add dossier, which is an encrypted json object of details uses in the form
			$dossier_for_update = $vce->generate_dossier($dossier);

			// the instructions to pass through the form with specifics
			$dossier = array(
			'type' => 'Comments',
			'procedure' => 'delete',
			'component_id' => $component_id,
			'created_at' => $created_at
			);

			// add dossier, which is an encrypted json object of details uses in the form
			$dossier_for_delete = $vce->generate_dossier($dossier);

            echo json_encode(array('response' => 'success', 'procedure' => 'create', 'action' => 'reload', 'message' => 'Created', 'component_id' => $component_id, 'dossier_for_create' => $dossier_for_create, 'dossier_for_update' => $dossier_for_update, 'dossier_for_delete' => $dossier_for_delete));
            return;

        }

        echo json_encode(array('response' => 'error', 'procedure' => 'update', 'message' => "Error"));
        return;

    }
    
    /**
     * add the event to pass on to notify
     */
	public static function notification($component_id = null, $action = null) {

		global $vce;
		
		// get actor name
		$actor = $vce->user->email;
		if (isset($vce->user->first_name) || isset($vce->user->last_name)) {
			$actor = '';
			if (isset($vce->user->first_name)) {
				$actor .= $vce->user->first_name;
			}
			if (isset($vce->user->last_name)) {
				$actor .= ' ' . $vce->user->last_name;
			}
			$actor = trim($actor);
		}
		
		if ($action == 'create') {
		
			$event = array(
				'actor' => $actor,
				'action' => 'create',
				'verb' => 'added',
				'object' => 'a comment'
			);
		
		}
		
		if ($action == 'update') {
		
			$event = array(
				'actor' => $actor,
				'action' => 'update',
				'verb' => 'updated',
				'object' => 'a comment'
			);
		
		}
		
		if ($action == 'delete') {
		
			$event = array(
				'actor' => $actor,
				'action' => 'delete',
				'verb' => 'deleted',
				'object' => 'a comment'
			);
		
		}
		
		/**
		 * The following block of code will always be needed in any notification to trigger the calls to parents of this component
		 **/
		
		// get parents
		$parents = $vce->page->get_parents($component_id);

		// call to notify method on parents in reverse order
		if (!empty($parents) && !empty($event)) {
		
			end($parents)->notification_event = $event;
		
			// work backwards
			for ($x = (count($parents) - 1);$x > -1;$x--) {
				// notify($parents)
				$result = $parents[$x]->notify($parents);
		
				// if boolean and true is returned by one of the parent components, then we end our search
				if (is_bool($result) && $result) {
					// end notify calls
					break;
				}
			}
		}
	
		return true;
	}
	
	
	/**
	 * This function is called to from notification
	 * @param array $parents
	 */
	public function notify($parents = null) {
	
		global $vce;
		
		// need a way to know what options are available for pick-and-choose
		// use a subtractive approach to minimize what is stored in user_meta
		$announcements = array(
		'notify_on_reply' => 'Notify me when someone has replied to my comment'
		);
		
		if (!$parents || !is_array($parents)) {
			return $announcements;
		}

		// is the parent of this event a comment?
		if ($parents[count($parents) - 2]->type != 'Comments') {
			return false;
		}
		
		$event = end($parents)->notification_event;
		
		$link = null;
		
		// work backwards to find link
		for ($x = (count($parents) - 1);$x > -1;$x--) {
			if (isset($parents[$x]->url)) {
				$link = $vce->site->site_url . '/' . $parents[$x]->url;
				break;
			}
		}
		
		$proclamation = array();
		
		// remember to provide the specific announcement
		if ($event['action'] == 'create') {
		
			$event['verb'] = 'replied to';
			$event['object'] = 'your comment';
		
			// convert to array of array in order to add addtional events
			$event_array = array($event);
			
			// allow for multipe proclamations by placing in a sub array
			$proclamation[] = array(
				// component type is needed if you are allowing for notifications options
				'component' => get_class($this),
				// add NotifierType if you want to only trigger that type of notification
				// 'type' => 'SiteNotifier',
				// announcement is needed if you are allowing for notifications options
				'announcement' => 'notify_on_reply',
				'recipient' => $parents[count($parents) - 2]->created_by,
				'event' => $event_array,
				'link' => $link,
				'subject' => 'Comment Notification'
				// content will override everything in the event array
				//'content' => 'testing'
			);
			
		}
		
		// this is a pattern that should be used to prevent any errors
		if (!empty($proclamation)) {
		
			// hook for allowing multiple types of notifications
			if (isset($vce->site->hooks['components_notify_method'])) {
				foreach($vce->site->hooks['components_notify_method'] as $hook) {
					call_user_func($hook, $proclamation);
				}
			}

			return true;
		
		}
		
		return false;
	}

    /**
     * method to add the content necissary for when a comment is the sub_component of a component that is displayed asynchronously without a page load
     */
    public function add_sub_component($parent_component, $vce) {

        // add javascript to page
        $vce->site->add_script(dirname(__FILE__) . '/js/script.js');

        //add stylesheet
        $vce->site->add_style(dirname(__FILE__) . '/css/style.css', 'comments-style');

		// the instructions to pass through the form with specifics
		$dossier = array(
		'type' => 'Comments',
		'procedure' => 'create',
		'title' => 'comment'
		);

		// add dossier, which is an encrypted json object of details uses in the form
		$dossier_for_sub_comment = $vce->generate_dossier($dossier);

		$input = array(
		'type' => 'textarea',
		'name' => 'text',
		'data' => array(
		'class' => 'textarea-input comment-input comment-create-input',
		'tag' => 'required',
		'placeholder' => $this->lang('Enter Your Comment')
		)
		);
	
		$text_input = $vce->content->create_input($input,'Comment','Add A Comment');
	
		$input = array(
		'type' => 'textarea',
		'name' => 'text',
		'value' => '{update-text}',
		'data' => array(
		'class' => 'textarea-input comment-input comment-update-input',
		'tag' => 'required',
		'placeholder' => $this->lang('Enter Your Comment')
		)
		);
	
		$update_input = $vce->content->create_input($input, $this->lang('Comment'), $this->lang('Add A Comment'));

		$user_name = $vce->user->first_name . ' ' . $vce->user->last_name;
		
	 	$site_url = defined('ASSETS_URL') ? ASSETS_URL : $vce->site->site_url;

		// user image
		$user_image = $site_url . '/vce-application/images/user_' . ($vce->user->user_id % 5) . '.png';

		$content = <<<EOF
<div id="display-submission-{parent-id}" class="display-submission" style="display:none">
	<div id="comment-{component-id}" class="indent-comment">
		<div class="comment-group-container">
			<div class="comment-row">
				<div class="user-image-comment"><img src="$user_image"></div>
				<div class="comment-row-content arrow-box">
					<p><span class="user-name-comment">$user_name</span></p>
					 <p class="comment-text">{text}</p>
					<div class="reply-form">
						<form id="add_comments" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
							<input type="hidden" name="dossier" value="{dossier-for-create}">
							$text_input
							<input type="submit" value="{$this->lang('Save Comment')}">
							<button class="link-button reply-form-cancel">{$this->lang('Cancel')}</button>
						</form>
					</div>
					<div class="update-form">
						<form class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
							<input type="hidden" name="dossier" value="{dossier-for-update}">
							$update_input
							<input type="submit" value="{$this->lang('Update Comment')}"> <button class="link-button update-form-cancel">{$this->lang('Cancel')}</button>
						</form>
					</div>
					<div class="comment-reply-delete">
						<button class="no-style link-inline reply-form-link">{$this->lang('Reply')}</button>
						<div class="pipe"></div>
						<button class="no-style link-inline edit-form-link">{$this->lang('Edit')}</button>
						<div class="pipe"></div>
						<button class="no-style link-inline delete-comment" comment="{component-id}" action="$vce->input_path" dossier="{dossier-for-delete}">{$this->lang('Delete')}</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>	
<div class="accordion-container accordion-closed">
	<div class="accordion-heading" role="heading" aria-level="2">
		<button class="accordion-title active" role="button" aria-expanded="false" aria-controls="accordion-content-{parent-id}" id="accordion-title-{parent-id}">
			<span>{$this->lang('Add Comments')}</span>
		</button>
	</div>
	<div class="accordion-content" id="accordion-content-{parent-id}" role="region" aria-labelledby="accordion-title-{parent-id}" style="display:none;">
		<form class="sub-comment-form" method="post" action="$vce->input_path" combar="combar-{parent-id}" asyncontid="display-submission-{parent-id}" autocomplete="off">
			<input type="hidden" name="dossier" value="$dossier_for_sub_comment">
			<input type="hidden" name="parent_id" value="{parent-id}">
			<input type="hidden" name="inputtypes" value="[]">
			$text_input
			<input type="submit" value="{$this->lang('Add Comments')}">
		</form>
	</div>
</div>
EOF;

		return $content;
		
    }


}