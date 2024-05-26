<?php

class UserSettings extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'User Settings',
			'description' => 'Allows users to update their account',
			'category' => 'user',
			'recipe_fields' => array('auto_create','title','url','template')
		);
	}
	
	
	/**
	 *
	 */
	public function as_content($each_component, $vce) {
	
		$user = $vce->user;
		
		// add javascript to page
		$vce->site->add_script(dirname(__FILE__) . '/js/script.js');
		
		$first_name = isset($user->first_name) ? $user->first_name : null;
		$last_name = isset($user->last_name) ? $user->last_name : null;
		
		$site_roles = json_decode($vce->site->roles, true);
		
		// get site user attributes
		$user_attributes = json_decode($vce->site->user_attributes, true);
		
		foreach ($user_attributes as $key=>$value) {

			if ((isset($value['required']) && isset($value['editable'])) && strlen($user->$key) == 0) {
			
				$content = <<<EOF
<script>
$(document).ready(function() {
	$('#update').submit();
});
</script>
<div class="form-message form-warning">{$this->lang('warning')}</div>
EOF;

				$vce->content->add('main', $content);
		
				break;
			
			}
		}
		
		// allow both simple and complex role definitions
		$user_role = is_array($site_roles[$user->role_id]) ? $site_roles[$user->role_id]['role_name'] : $site_roles[$user->role_id];
		
		// create a special dossier
		$dossier_for_password = $vce->generate_dossier(array('type' => 'UserSettings','procedure' => 'update','user_id' => $vce->user->user_id, 'email' => $vce->user->email));		
		$dossier_for_update = $vce->generate_dossier(array('type' => 'UserSettings','procedure' => 'update','user_id' => $vce->user->user_id));		

		$content = null;
		
		// check if configuration has been set to hide password update
		if (!isset($this->configuration['disable_password_update']) || (isset($this->configuration['disable_password_update']) && $this->configuration['disable_password_update'] != 'on')) {
			
			// update password
			if (!isset($user_attributes['password']) || !isset($user_attributes['password']['type']) || $user_attributes['password']['type'] != 'conceal') {

				// password input
				$input = array(
				'type' => 'password',
				'name' => 'password',
				'class' => 'password-input',
				'data' => array(
					'tag' => 'required',
					'placeholder' => $this->lang('Enter Password')
				)
				);
		
				$password_input = $vce->content->create_input($input, $this->lang('Enter A New Password'), $this->lang('Enter Your Password'));

				// password input
				$input = array(
				'type' => 'password',
				'name' => 'password2',
				'class' => 'password-input',
				'data' => array(
					'tag' => 'required',
					'placeholder' => $this->lang('Repeat Password')
				)
				);
		
				$password2_input = $vce->content->create_input($input,$this->lang('Repeat New Password'), $this->lang('Repeat Password'));

				$password_update = <<<EOF
<form id="password" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_password">
$password_input
$password2_input
<input type="submit" value="{$this->lang('Update')}">
<label class="ignore" style="color:#666;"><input class="show-password-input" type="checkbox" name="show-password"> {$this->lang('Show Password')}</label>
</form>
EOF;

				$content .= $vce->content->accordion($this->lang('Update Your Password'), $password_update);

			}
		
		}

		$user_settings = <<<EOF
<form id="update-attributes" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_update">
EOF;

		// check if configuration has been set to hide password update
		if (!isset($this->configuration['disable_email_update']) || (isset($this->configuration['disable_email_update']) && $this->configuration['disable_email_update'] != 'on')) {

			if (!isset($user_attributes['password']) || !isset($user_attributes['password']['type']) || $user_attributes['password']['type'] != 'conceal') {

				$input = array(
				'type' => 'text',
				'name' => 'email',
				'value' => $user->email,
				'data' => array(
					'tag' => 'required',
					'current' => $user->email
				)
				);
		
				$email_input = $vce->content->create_input($input, $this->lang('Email'), $this->lang('Enter your Email'));

				$input = array(
				'type' => 'password',
				'name' => 'password',
				'id' => 'password-required-input',
				'class' => 'password-input',
				'data' => array(
					'current' => $user->email
				)
				);
		
				$password_required_input = $vce->content->create_input($input,$this->lang('Enter Current Password'), $this->lang('Enter your Current Password'));


				$user_settings .= <<<EOF
$email_input
<div id="password-required" style="display:none;">
$password_required_input
</div>
EOF;

			} else {

				$email_input = $vce->content->create_input($user->email, 'Email');

				$user_settings .= <<<EOF
$email_input
EOF;
		
			}
		
		} else {

			$user_settings .= $vce->content->create_input($user->email,'Email',null,'input-padding');

		}
		
		// attributes
		$user_settings .= user::user_attributes_fields($vce->user, $vce);

		// add hooks
		if (isset($vce->site->hooks['usersettings_update_settings'])) {
			foreach($vce->site->hooks['usersettings_update_settings'] as $hook) {
				$user_settings .= call_user_func($hook, $vce);
			}
		}
		
		$user_settings .= $vce->content->create_input($user_role, $this->lang('Role'),null,'input-padding');


		$user_settings .= <<<EOF
<input type="submit" value="{$this->lang('Update')}">
<button class="link-button cancel-button">{$this->lang('Cancel')}</button>
</form>
EOF;


		$content .= $vce->content->accordion($this->lang('Update Your User Settings'), $user_settings, true, true);

		$vce->content->add('main', $content);
		
		// hook for allowing multiple types of notifications
		if (isset($vce->site->hooks['user_settings_as_content'])) {
			foreach($vce->site->hooks['user_settings_as_content'] as $hook) {
				call_user_func($hook);
			}
		}
	
	}


	
	/**
	 *
	 */
	public function check_access($each_component, $vce) {
	
		if (isset($vce->user->user_id)) {
			return true;
		}
		
		return false;
		
		// in the event that a user is not logged in, redirect to top of site

		// to front of site
		// header('location: ' . $vce->site->site_url);

	}
	
	
	/**
	 *
	 */
	public function update($input) {

    	$vce = $this->vce;
    	
    	// prevent any overloading attempt of adding role_id as a form input element
    	unset($input['role_id']);
    	
    	$response = $vce->user->update($input);

    	$response['form'] = 'update';
    	
    	echo json_encode($response);
    	
    	return;
    	
    }
    
    
    /**
	 * add config info for this component
	 */
	public function component_configuration() {
	
		global $vce;
		
		$options = null;
		
		$input = array(
		'type' => 'checkbox',
		'name' => 'disable_password_update',
		'options' => array(
		'value' => 'on',
		'selected' => ((isset($this->configuration['disable_password_update']) && $this->configuration['disable_password_update'] == 'on') ? true :  false),
		'label' => 'Disable the ability for users to update their password'
		)
		);
		
		$options .= $vce->content->create_input($input,'Disable Password Update');
		
		$input = array(
		'type' => 'checkbox',
		'name' => 'disable_email_update',
		'options' => array(
		'value' => 'on',
		'selected' => ((isset($this->configuration['disable_email_update']) && $this->configuration['disable_email_update'] == 'on') ? true :  false),
		'label' => 'Disable the ability for users to update their email address'
		)
		);
		
		$options .= $vce->content->create_input($input,'Disable Email Update');
		
		return $options;
	
	}

}