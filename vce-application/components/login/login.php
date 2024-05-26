<?php

class Login extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Login',
			'description' => 'Simple login form using email and password. A User must login to view anything contained within this component.',
			'category' => 'site',
			'recipe_fields' => array('auto_create',
			'title',
			'template',
			'login_disabled' => array(
				'label' => array('message' => 'Login Disabled'),
				'type' => 'checkbox',
				'name' => 'login_disabled',
				'selected' => isset($recipe['login_disabled']) ? $recipe['login_disabled'] : null,
				'flags' => array (
				'label_tag_wrap' => 'true'
				),
				'options' => array(
				'label' => 'Login Disabled', 'value' => 'true'
				)
			)
			)
		);
	}

	/**
	 * check if get_sub_components should be called.
	 * @return bool
	 */
	public function find_sub_components($requested_component, $vce, $components, $sub_components) {
	
		// if user has not logged in, return false
		if (!isset($vce->user->user_id)) {
			return false;
		}
		
		// if the user is logged-in but accessing a Login component directly by tilde component_id, example ~16
		if (end($components)->type == 'Login') {
			$vce->content->add('main','You are accessing a Login component directly!');
			return false;
		}
	
		// return true if user has logged in
		return true;
	}
		
	/**
	 *
	 */
	public function check_access($each_component, $vce) {
		
		// allows for a way to disable a login component while keeping it in the recipe
		if (isset($each_component->recipe['login_disabled'])) {
			return true;
		}
		
		if (isset($vce->user->suspended)) {
		
			// which email address to use depends on what has been configured
			$contact_email = !empty($vce->site->site_contact_email) ? $vce->site->site_contact_email : $vce->site->site_email;

			$content = <<<EOF
{$this->lang('Account Suspended Start')} $contact_email {$this->lang('Account Suspended End')}
EOF;

			// add content
			$vce->content->add('main', $content);
			
			// log out this user
			$vce->user->logout();

			return false;
		}

		if (!isset($vce->user->user_id)) {
		
			// hook that can be used to extend this method
			// login_check_access_false
			if (isset($vce->site->hooks['login_check_access_false'])) {
				foreach($vce->site->hooks['login_check_access_false'] as $hook) {
					call_user_func($hook, $each_component, $vce);
				}
			}
			
			//add javascript
			$vce->site->add_script(dirname(__FILE__) . '/js/script.js', 'jquery');
			
			// the instructions to pass through the form with specifics
			$dossier = array(
			'type' => 'Login',
			'procedure' => 'form_input',
			'component_id' => $each_component->component_id,
			'requested_url' => rtrim($vce->requested_url,'/')
			);

			// add dossier, which is an encrypted json object of details uses in the form
			$each_component->dossier = $vce->user->encryption(json_encode($dossier),$vce->user->session_vector);

			// email input
			$input = array(
			'type' => 'text',
			'name' => 'email',
			'data' => array(
				'autocapitalize' => 'none',
				'tag' => 'required',
				'placeholder' => $this->lang('Enter Your Email Address')
			)
			);
		
			$email_input = $vce->content->create_input($input,$this->lang('Email'),$this->lang('Enter Your Email Address'));

			// password input
			$input = array(
			'type' => 'password',
			'name' => 'password',
			'class' => 'password-input',
			'data' => array(
				'tag' => 'required',
				'placeholder' => $this->lang('Enter Your Password')
			)
			);
		
			$password_input = $vce->content->create_input($input,$this->lang('Password'),$this->lang('Enter Your Password'));

			$content = <<<EOF
<form id="login_form" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$each_component->dossier">
$email_input
$password_input
<input type="submit" value="{$this->lang('Click here to sign in')}">
<label class="ignore" style="color:#666;"><input class="show-password-input" type="checkbox" name="show-password"> {$this->lang('Show Password')}</label>
</form>
EOF;

			$accordion_expanded = true;
			$accordion_disabled = true;

			if (isset($this->configuration['collapse_login_accordion']) && $this->configuration['collapse_login_accordion'] == 'on') {
				$accordion_expanded = false;
				$accordion_disabled = false;
			}


			$add_content = $vce->content->accordion($this->lang('Login'), $content, $accordion_expanded, $accordion_disabled);

			// add content
			$vce->content->add('main', $add_content);
	
			return false;
	
		} else {
		
			// login_check_access_true
			// method should return true of false
			if (isset($vce->site->hooks['login_check_access_true'])) {
				foreach($vce->site->hooks['login_check_access_true'] as $hook) {
					return call_user_func($hook, $each_component, $vce);
				}
			}
		
			return true;
		
		}
	}
	
	/**
	 * Instead of going all the way through form_input in class.component.php, we just do everything here in the child.
	 */
	public function form_input($input) {
	
		$vce = $this->vce;
		
		$input['email'] = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
		if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
			echo json_encode(array('response' => 'error','message' => Login::language('Not a valid email address'),'action' => 'clear'));
			return;
		}
		
		//$vce->page->requested_id = $input['component_id'];
		//$vce->page->requested_url = $input['requested_url'];
		
		//unset($vce->page->post_variables);
		
		// send array to user login
		$success = $vce->user->login($input);
		
		if ($success) {
			echo json_encode(array('response' => 'success','message' => Login::language('Welcome Back!'),'action' => 'reload','url' => $vce->site->site_url . '/' . $input['requested_url']));
			return;
		}
		
		// return error
		echo json_encode(array('response' => 'error','message' => Login::language('Invalid Username/Password'),'action' => 'clear'));
		return;
	}
	
	/**
	 * add config info for this component
	 */
	public function component_configuration() {
	
		global $vce;
		
		$input = array(
		'type' => 'checkbox',
		'name' => 'collapse_login_accordion',
		'options' => array(
		'value' => 'on',
		'selected' => ((isset($this->configuration['collapse_login_accordion']) && $this->configuration['collapse_login_accordion'] == 'on') ? true :  false),
		'label' => 'Collapse Login Accordion'
		)
		);
		
		return $vce->content->create_input($input,'Collapse Login Accordion');
	
	}

}