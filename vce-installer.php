<?php

header("Cache-Control: no-cache,no-store,max-age=0,must-revalidate");
header("Pragma: no-cache");
header("Expires: -1");

/**
 * vce_installer: guides user through VCE installation process
 * the function "define_sql()" at the end of this file should be the contents of the latest mysql dump of the site, run through a parser to
 * remove comments. If the database defined is `vce` and the prefix is vce_, these will be replaced by this script.
 */
 
/**
 * Note: this installer needs to be rebuilt.
 */
 
// Report  errors [set to 0 for live site]
ini_set('error_reporting', E_ALL);

// Define BASEPATH as this file's path
define('BASEPATH', dirname(__FILE__) . '/');

// replaces backslashes with forward slashes to make windows paths the same as linux
$replacement = str_replace("\\", "/", BASEPATH);

// Define DOCROOT as this file's directory
define('DOCROOT', $_SERVER['DOCUMENT_ROOT'] . '/');

// Define DOCPATH as the path from the DOCROOT to the file
define('DOCPATH', '/'.str_replace(DOCROOT, '', $replacement));

// This is the variable which will contain all the HTML to display
$GLOBALS['content'] = '';

//begin session
if (session_status() == PHP_SESSION_ACTIVE && isset($_SESSION['installer_page']) && $_SESSION['installer_page'] = 0) {
	session_destroy();
}

if (session_status() == PHP_SESSION_NONE) {
	//  start_session();
    session_start();
    //record when the session started
    if (!isset($_SESSION['started'])) {
   		 $_SESSION['started'] = time();
	}
}


if (!isset($_SESSION['installer_page']) || $_SESSION['installer_page'] < 0) {
	$_SESSION['installer_page'] = 0;
}

// reset if vce-config.php is missing
if (defined('BASEPATH') && !file_exists(BASEPATH . 'vce-config.php')) {
	session_destroy();
	start_session();
	$_SESSION['installer_page'] = 0;
}

//for development, sets the page you are on
if (isset($_GET['installer_page'])) {
	$_SESSION['installer_page'] = $_GET['installer_page'];
	$_SESSION['carryon'] = 'continue';
}


//
if (!isset($_SESSION['carryon']) ) {
	$_SESSION['carryon'] = 'continue';
}

//reset to first page if more than an hour has passed
if (isset($_SESSION['started']) && $_SESSION['started'] + 3600  < time()) {
	session_destroy();
	start_session();
	$_SESSION['installer_page'] = 0;
}

//find page to display
set_page();

//write header and pre-content
$inst_css = installer_css();
$inst_js = form_validation_js();

// Page Header and pre-content
$GLOBALS['content'] .= <<<EOF
<!DOCTYPE html>
<html lang="en">
<meta charset="utf-8">
<title>VCE Installer</title>
<link rel="stylesheet" type="text/css" href="vce-application/css/vce.css">
$inst_css
$inst_js
</head>
<body>
<div id="wrapper">
<div id="content">
<div id="header" >
<div id="void" class="inner"><h1>Nestor Installer</h1></div>
</div>
<br>
<div class="inner">
EOF;


/**
 * Step-by step installer and content.
 * This is the road-map and output of the installer
 */
 

 
//Welcome to the installer
if (!isset($_SESSION['installer_page']) || $_SESSION['installer_page'] < 1) {
 	$step_title = 'Welcome to the Nestor installer.';
 	$step_description = 'This installer will walk you through the few steps necessary to configure your Nestor site.</br>If you are ready to begin, please click on &quot;continue&quot;';
	//Main content
	title_description($step_title, $step_description);
	$_SESSION['carryon'] = 'continue';
}


//Do extension check, report server configuration, back/continue
if ($_SESSION['installer_page'] == 1) {
 	$step_title = 'Server Compatibility Check';
 	$step_description = 'The installer is now checking the server version and installed modules';
	//Main content
	title_description($step_title, $step_description);
	//check to see if these modules are active on the server
	// removing the following from the extention_check because they are not needed
	// 'pdo', 
	// 'pdo_mysql',
	// 'mcrypt',
	$_SESSION['carryon'] = extension_check(array( 
		'curl',
		'dom', 
		'gd', 
		'hash',
		'iconv',
		'pcre',
		'simplexml'
		)
	);
	//edit .htaccess file
	check_htaccess_file();
	edit_htaccess_file();
	//edit vce-config.php
	edit_vce_config();
	
}


//Enter database information
if ($_SESSION['installer_page'] == 2) {

 	$step_title = 'Enter Database Information';
 	$step_description = 'You need to create a database for Nestor to use. When you have the database name, URL, and database admin credentials, enter them here.';
	
	//Main content
	title_description($step_title, $step_description);
	
	$_SESSION['carryon'] = check_database();
	
	$this_file = '';
	
	$dbhost = !empty($_POST['dbhost']) ? $_POST['dbhost'] : 'localhost';
	$dbname = !empty($_POST['dbname']) ? $_POST['dbname'] : '';
	$dbprefix = !empty($_POST['dbprefix']) ? $_POST['dbprefix'] : 'vce_';
	$dbuser= !empty($_POST['dbuser']) ? $_POST['dbuser'] : '';
	$dbpassword= !empty($_POST['dbpassword']) ? $_POST['dbpassword'] : '';
	$dbport= !empty($_POST['dbport']) ? $_POST['dbport'] : '3306';
	 
	 
	$GLOBALS['content'] .= <<<EOF
<div class="accordion-container accordion-open">
<div class="accordion-heading" role="heading">
<button class="accordion-title disabled" role="button">
<span>Configure Database Connection</span>
</button>
</div>
<div class="accordion-content">
<form class="installer-form" action="$this_file" method="post" autocomplete="off">
<input type="hidden" name="pagecheck" value="check">
<div class="input-label-style"> 
<div class="input-label-text">
<label class="input-label-message" for="dbname">Database Name</label>
<div class="input-label-error" role="alert">Enter Database Name</div>
</div>
<input type="text" options-group="dbname" name="dbname" id="dbname" value="$dbname" autocapitalize="none" tag="required" placeholder="Enter Database Name" autocomplete="off">
</div>
<div class="input-label-style"> 
<div class="input-label-text">
<label class="input-label-message" for="dbuser">Database User</label>
<div class="input-label-error" role="alert">Enter Database User</div>
</div>
<input type="text" options-group="dbuser" name="dbuser" id="dbuser" value="$dbuser" autocapitalize="none" tag="required" placeholder="Enter Database User" autocomplete="off">
</div>
<div class="input-label-style"> 
<div class="input-label-text">
<label class="input-label-message" for="dbpassword">Database User Password</label>
<div class="input-label-error" role="alert">Enter Database User Passwords</div>
</div>
<input type="text" options-group="dbpassword" name="dbpassword" id="dbpassword" value="$dbpassword" autocapitalize="none" tag="required" placeholder="Enter Database User Password" autocomplete="off">
</div>
<div class="accordion-container accordion-open">
<div class="accordion-heading" role="heading">
<button class="accordion-title disabled" role="button">
<span>Advanced Options</span>
</button>
</div>
<div class="accordion-content">
<div class="input-label-style"> 
<div class="input-label-text">
<label class="input-label-message" for="dbprefix">Database Prefix (&quot;vce_&quot; is the default)</label>
<div class="input-label-error" role="alert">Enter Database Prefix</div>
</div>
<input type="text" options-group="dbprefix" name="dbprefix" id="dbprefix" value="$dbprefix" autocapitalize="none" tag="required" placeholder="Enter Database Prefix" autocomplete="off">
</div>
<div class="input-label-style"> 
<div class="input-label-text">
<label class="input-label-message" for="dbhost">Database Host</label>
<div class="input-label-error" role="alert">Enter Database Host</div>
</div>
<input type="text" options-group="dbhost" name="dbhost" id="dbhost" value="$dbhost" autocapitalize="none" tag="required" placeholder="Enter Database Host" autocomplete="off">
</div>
<div class="input-label-style"> 
<div class="input-label-text">
<label class="input-label-message" for="dbport">Database Port</label>
<div class="input-label-error" role="alert">Enter Database Port</div>
</div>
<input type="text" options-group="dbport" name="dbport" id="dbport" value="$dbport" autocapitalize="none" tag="required" placeholder="Enter Database Port" autocomplete="off">
</div>
</div>
</div>
<br>
<input type="submit" value="Connect to Database">
</form>
</div>
</div>
EOF;

	
}


if ($_SESSION['installer_page'] == 3) {
	// Now that we have a database connection, link into the site classes for the next steps
	// configuration file 
	include_once(BASEPATH . 'vce-config.php');
	
	// create DB object
	include_once(BASEPATH . 'vce-application/class.db.php');
	$db = new DB();
	//run .sql file

	$prefix = check_config_value('TABLE_PREFIX');
	$database = check_config_value('DB_NAME');
	
	$db->prefix = $prefix;
	
	$database_sql = define_sql();
	$database_sql = str_replace('vce_', $prefix, $database_sql);
	$database_sql = str_replace('`vce`', $database, $database_sql);
	$database_sql = explode(';', $database_sql );
	
	try {
        $dbconnection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, defined('DB_PORT') ? DB_PORT : '3306');
            mysqli_report(MYSQLI_REPORT_STRICT);
            foreach($database_sql as $query) {

				if (!empty($query)) {
					$dbconnection->query($query);
				}
			}
			//change site_url in the site meta table
			$site_url = 'http://'.$_SERVER['HTTP_HOST'].rtrim(DOCPATH, "/");
			$query = 'UPDATE '.$prefix.'site_meta SET meta_value = "'.$site_url.'" WHERE meta_key = "site_url" ';	
			$db->query($query);
			
			$_SESSION['site_url_computed'] = $site_url;

        } catch (Exception $e) {
        	echo  $e->getMessage();
            die('Database connection failed');
        }
        
      //create site key
    create_site_key();  
    $GLOBALS['site_key'] = check_config_value('SITE_KEY');;
}


//Enter Site Admin information
if ($_SESSION['installer_page'] == 3) {

	$this_file = '';
	//if all fields are present, create admin
	if (isset($_POST['username']) && isset($_POST['pwd1']) && isset($_POST['first_name']) && isset($_POST['last_name'])) {
		$create_admin = site_admin($_POST['username'], $_POST['pwd1'],$_POST['first_name'], $_POST['last_name'], $db);
	}
	if (isset($create_admin['success'])) {
		$_SESSION['site_admin_name'] = $create_admin['site_admin_name'];
		$step_title = 'Site Administrator';
 		$step_description = $create_admin['success'];
		//Main content
		title_description($step_title, $step_description);
		$_SESSION['carryon'] = 'continue';
		// go to next step
		$_SESSION['installer_page'] = 4;
		
		
	}else{

	 	$step_title = 'Site Administrator';
	 	if (isset($create_admin['failure'])) {
			$GLOBALS['content'] .= '<span style="color:red;">'.$create_admin['failure'].'</span">';
		}
 		$step_description = 'Choose the name and password for the site administrator.';
		//Main content
		title_description($step_title, $step_description);
		$_SESSION['carryon'] = 'wait';


$GLOBALS['content'] .= <<<EOF
<div class="accordion-container accordion-open">
<div class="accordion-heading" role="heading">
<button class="accordion-title disabled" role="button">
<span>Site Admin</span>
</button>
</div>
<div class="accordion-content">
<form class="installer-form" action="$this_file" method="post" autocomplete="off">
<input type="hidden" name="pagecheck" value="check">
<div class="input-label-style"> 
<div class="input-label-text">
<label class="input-label-message" for="first_name">Site Admin First Name</label>
<div class="input-label-error" role="alert">Enter Site Admin First Name</div>
</div>
<input type="text" options-group="first_name" name="first_name" id="first_name" autocapitalize="none" tag="required" placeholder="Enter Site Admin First Name" autocomplete="off">
</div>
<div class="input-label-style"> 
<div class="input-label-text">
<label class="input-label-message" for="last_name">Site Admin Last Name</label>
<div class="input-label-error" role="alert">Enter Site Admin Last Name</div>
</div>
<input type="text" options-group="last_name" name="last_name" id="last_name" autocapitalize="none" tag="required" placeholder="Enter Site Admin Last Name" autocomplete="off">
</div>
<div class="input-label-style"> 
<div class="input-label-text">
<label class="input-label-message" for="username">Site Admin Email</label>
<div class="input-label-error" role="alert">Enter A Valid Email Address</div>
</div>
<input type="email" options-group="username" name="username" id="username" autocapitalize="none" tag="required" placeholder="Enter A Valid Email Address" autocomplete="off">
</div>
<div class="input-label-style"> 
<div class="input-label-text">
<label class="input-label-message" for="pwd1">Password (Use 6 or more characters including a capital letter and a number.)</label>
<div class="input-label-error" role="alert">Enter Password</div>
</div>
<input type="password" options-group="pwd1" name="pwd1" id="pwd1" autocapitalize="none" tag="required" placeholder="Enter Password" autocomplete="off">
</div>
<div class="input-label-style"> 
<div class="input-label-text">
<label class="input-label-message" for="pwd2">Password Confirmation</label>
<div class="input-label-error" role="alert">Enter Password Confirmation</div>
</div>
<input type="password" options-group="pwd2" name="pwd2" id="pwd2" autocapitalize="none" tag="required" placeholder="Enter Password Confirmation" autocomplete="off">
</div>
<input type="submit" value="Create Site Admin">
</form>
</div>
</div>
EOF;
	
	}
}

//Sitekey
//
// site_key($superadmin['email']);
if ($_SESSION['installer_page'] == 4) {
 	$step_title = 'Site Key';
 	$step_description = 'Your Site Key has been created.';
	//Main content
	title_description($step_title, $step_description);
	$site_key = check_config_value('SITE_KEY');
	$GLOBALS['content'] .= "
	<br>
	<span style='color:green;'>".$site_key."</span>
	<br><br>
	This is your site key. It is written into the vce-config file, but it is also important that you write this down and store it off-site.<br>
 	This is the key which is used to encrypt all user data in the system. If it becomes corrupted, you will lose all the user data which is stored in the database.";
	
	// send email
	// $msg = "Hello, \n
	// This is the VCE SITE_KEY for your site:\n
	// ".$site_key."\n
	// It is stored in the vce-config.php file at the root of your installation.\n
	// Please keep it in your records to use in the event of a corruption of that configuration file.\n
	// Thank you!";
	// mail($_SESSION['site_admin_name'],'VCE Installation',$msg);
	$_SESSION['carryon'] = 'continue';

}

//Personalize the site
if ($_SESSION['installer_page'] == 5) {
	// configuration file 
	include_once(BASEPATH . 'vce-config.php');
	
	// create DB object
	include_once(BASEPATH . 'vce-application/class.db.php');
	$db = new DB();

	$prefix = check_config_value('TABLE_PREFIX');
	$database = check_config_value('DB_NAME');
	
	$db->prefix = $prefix;

	$step_title = 'Personalize Your Installation';
 	$step_description = 'Enter the site\'s name and other specific information.<br>';
 	
	//Main content
	title_description($step_title, $step_description);
	$_SESSION['carryon'] = 'wait';

	//if all fields are present, personalize site
	if (!empty($_POST['site_url']) && !empty($_POST['site_name']) && !empty($_POST['site_description'])) {
		$_SESSION['carryon'] = personalize_site($_POST['site_url'], $_POST['site_name'], $_POST['site_description'], $db);
		$GLOBALS['content'] .= '<span style="color:green;">Currently, your site name is &quot;'.$_POST['site_name'].'&quot; and your site description is &quot;'.$_POST['site_description'].'&quot;.</span">';
	}
	
	$this_file = '';
	$site_url = !empty($_SESSION['site_url_computed']) ? $_SESSION['site_url_computed'] : '';
	$site_name = !empty($_POST['site_name']) ? $_POST['site_name'] : '';
	$site_description = !empty($_POST['site_description']) ? $_POST['site_description'] : '';


$GLOBALS['content'] .= <<<EOF
<div class="accordion-container accordion-open">
<div class="accordion-heading" role="heading">
<button class="accordion-title disabled" role="button">
<span>Site Personalization</span>
</button>
</div>
<div class="accordion-content">
<form class="installer-form" tag="required" action="$this_file" method="post" autocomplete="off">
<input type="hidden" name="pagecheck" value="check">
<div class="input-label-style"> 
<div class="input-label-text">
<label class="input-label-message" for="site_url">Site URL</label>
<div class="input-label-error" role="alert">Enter Site URL</div>
</div>
<input type="text" options-group="site_url" name="site_url" value="$site_url" id="site_url" autocapitalize="none" tag="required" placeholder="Enter Site URL" autocomplete="off">
</div>
<div class="input-label-style"> 
<div class="input-label-text">
<label class="input-label-message" for="site_name">Site Name</label>
<div class="input-label-error" role="alert">Enter Site Name</div>
</div>
<input type="text" options-group="site_name" name="site_name" value="$site_name" id="site_name" autocapitalize="none" tag="required" placeholder="Enter Site Name" autocomplete="off">
</div>
<div class="input-label-style"> 
<div class="input-label-text">
<label class="input-label-message" for="site_description">Site Description</label>
<div class="input-label-error" role="alert">Enter Site Description</div>
</div>
<input type="text" options-group="site_description" name="site_description" value="$site_description" id="site_description" autocapitalize="none" tag="required" placeholder="Enter Site Description" autocomplete="off">
</div>
<input type="submit" value="Submit">
</form>
</div>
</div>
EOF;

	
}


//Installation Complete
if ($_SESSION['installer_page'] == 6) {
 	$step_title = 'Installation Complete';
 	$step_description = 'Your Nestor installation is complete.<br> 
 	Your site admin user name is '.$_SESSION['site_admin'].' and your password is what you entered when you created the site admin.<br>
 	Please press &quot;continue&quot; to log in.';
	//Main content
	title_description($step_title, $step_description);
}


//Redirect to Admin Home Page at the end of the installation
if ($_SESSION['installer_page'] == 7) {
	session_unset();
	session_destroy();
	$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$url = str_replace(basename(__FILE__), '', $url);
	unlink(__FILE__);
	header('Location: '.$url);
}

/**
 * END: Step-by step installer and content.
 */

//write post-content and footer
$GLOBALS['content'] .= <<<EOF
</div>
</div>
<footer id="footer">
<div class="inner" >
<div class="copy"></div>
</div>
</footer>
</div></div>
</body>
</html>
EOF;

//navigation buttons
if (isset($_SESSION['carryon'])) {
	back_continue($_SESSION['carryon']);
}
 
echo $GLOBALS['content'];



/**
 * FUNCTIONS: here are all the functions called in the step-by-step
 */

	/**
	 * session start 
	 */
	function start_session() {

		// SESSION HIJACKING PREVENTION

		// set hash algorithm
		ini_set('session.hash_function', 'sha512');
	
		// send hash
		ini_set('session.hash_bits_per_character', 5);
   
		// set additional entropy
		ini_set('session.entropy_file', '/dev/urandom');
	
   		// set additional entropy	
		ini_set('session.entropy_length', 256);
	
		// prevents session module to use uninitialized session ID
		ini_set('session.use_strict_mode', true);
   
		// SESSION FIXATION PREVENTION
   
		// do not include the identifier in the URL, and not to read the URL for identifiers.
		ini_set('session.use_trans_sid', 0);
	
 		// tells browsers not to store cookie to permanent storage
 		ini_set('session.cookie_lifetime', 0);
 
		// force the session to only use cookies, not URL variables.
		ini_set('session.use_only_cookies', true);
   
		// make sure the session cookie is not accessible via javascript.
		ini_set('session.cookie_httponly', true);
   
		// set to true if using https   
		ini_set('session.cookie_secure', false);

		// chage session name
		session_name('_s');
		
		// set the cache expire to 30 minutes
		session_cache_expire(5);
	
		// start the session
		session_start();
	}
	
function set_page() {
	if (isset($_POST['pagecheck'])) {
		return;
	}

	if (isset($_POST['direction'])) {
		if ($_POST['direction'] == 'back') {
			if (isset($_SESSION['installer_page'])) {
				$_SESSION['installer_page']--;
			}else{
				$_SESSION['installer_page'] = 0;
			}
		}elseif ($_POST['direction'] == 'continue') {
			if (isset($_SESSION['installer_page'])) {
				$_SESSION['installer_page']++;
			}else{
				$_SESSION['installer_page'] = 0;
			}
		}
	}
}

/**
 * Creates the "Back" and "Continue" buttons for each step of the installation
 * @param 
 * @return HTML for the form buttons
 */
function back_continue($carryon) {
 $this_file = '';
$continue_message = ($carryon == 'wait' ? 'Reset' : 'Continue');
$continue_button = <<<EOF
<div class="inner">
<form onsubmit="return checkContinueForm(this);" class="inline-form asynchronous-form" method="post" action="$this_file">
<input type="hidden" name="direction" value="$carryon">
<input type="submit" value="$continue_message">
</form>
EOF;
$GLOBALS['content'] = str_replace('<div class="inner">', $continue_button, $GLOBALS['content']);
}
 

/**
 * Creates the title and description for each step of the installation
 * @param string $title
 * @param string $description
 * @return HTML for the title and description
 */ 
function title_description($step_title, $step_description){
	$GLOBALS['content'] .= '<h2>'.$step_title.'</h2>';
	$GLOBALS['content'] .= '<p>'.$step_description.'</p>';
}


/**
 * Prepares vce-config.php for the installation
 * @param string $title
 * @param string $description
 * @return HTML for the title and description
 */ 
function edit_vce_config(){
	if(file_exists(BASEPATH.'vce-config')){
		$GLOBALS['content'] .= '<p><strong><span style="color:red;">Caution!</span></strong><span style="color:red;">You have run this installer script previously.
		<br><strong>Running it again will overwrite your site key and erase your user data.</strong>
		<br>You are seeing this message because there is already a configuration file. If you wish to start a new installation, either start anew with newly unzipped contents, or erase the existing config file, upload this installer again from the zip file, and run it.
		<br>This installer has now been deleted to prevent this from happening again in the future.</p></span>';
		unlink(__FILE__);
		return;
	}
	if(!file_exists(BASEPATH.'vce-config.php')){
		touch(BASEPATH.'vce-config.php');
		$newfile = fopen(BASEPATH."vce-config.php", "w") or die("Cannot open vce-config.php file.");
		if(file_exists(BASEPATH.'vce-config-sample.php')){
			$content = file_get_contents(BASEPATH.'vce-config-sample.php');
		}else{
		
$content = <<<EOF
<?php

/* Site key - DO NOT CHANGE THIS */
define('SITE_KEY', 'installer_generated_site_key_here');

/* The name of the database */
define('DB_NAME', 'database_name_here');

/* MySQL database username */
define('DB_USER', 'username_here');

/* MySQL database password */
define('DB_PASSWORD', 'password_here');

/* MySQL hostname */
define('DB_HOST', 'localhost');

/* MySQL table_prefix */
define('TABLE_PREFIX', 'vce_');

/* Enable query string input */
define('QUERY_STRING_INPUT', true);

/* set the path to uploaded files */
define('PATH_TO_UPLOADS', 'vce-content/uploads');

/* display MySQL and PHP errors */
define('VCE_DEBUG', false);

EOF;
		}
		fwrite($newfile, $content);
		fclose($newfile);
	}
	
	$reading = fopen(BASEPATH.'vce-config.php', 'r');
	$writing = fopen(BASEPATH.'configTEMP.php', 'w');

	$replaced = false;

	while (!feof($reading)) {
  		$line = fgets($reading);
  		if (stristr($line, 'SITE_KEY')) {
  			$line = "define('SITE_KEY', '');".PHP_EOL;
   			$replaced = true;
 		}
 		fputs($writing, $line);
	}
	fclose($reading); fclose($writing);
	// might as well not overwrite the file if we didn't replace anything
	if ($replaced) 
	{
  		rename('configTEMP.php', 'vce-config.php');
	} else {
 		 unlink('configTEMP.php');
	}


}
 
 
 function site_admin($username, $pwd, $firstname, $lastname, $db) {
 	//clean form input
 		$username = $db->mysqli_escape($username);
 		$pwd = $db->mysqli_escape($pwd);
 		$firstname = $db->mysqli_escape($firstname);
 		$lastname = $db->mysqli_escape($lastname);
 		
 		$prefix = check_config_value('TABLE_PREFIX');
 		$sql = 'DELETE a, b FROM '.$prefix.'users as a, '.$prefix.'users_meta as b WHERE a.role_id = 1';
 		$db->query($sql);
 		
		$return = array();
		
		$lookup = lookup($username);
		
		// check if exists
		$query = "SELECT id FROM " . TABLE_PREFIX . "users_meta WHERE meta_key='lookup' and meta_value='" . $lookup . "'";
		$lookup_check = $db->get_data_object($query);
		
		if (!empty($lookup_check)) {
			$return['failure'] = '<span style="color:red;">Email is already in use; this user already exists.</span>';
			return $return;
		}
		
		// call to user class to create_hash function
		$hash = create_hash(strtolower($username),$pwd);
		
		// get a new vector for this user
		$vector = create_vector();
		
		//for use in mailing the SITE_KEY to the new site admin
		$_SESSION['site_admin'] = $username;

		$user_data = array(
		'vector' => $vector, 
		'hash' => $hash,
		'role_id' => 1
		);

		$db->insert( 'users', $user_data );
		$user_id = $db->lastid();
	
				
		// now add meta data

		$records = array();
				
		$lookup = lookup($username);
		
		$records[] = array(
		'user_id' => $user_id,
		'meta_key' => 'lookup',
		'meta_value' => $lookup,
		'minutia' => 'false'
		);
		
		
		$input = array('email'=>$username, 'first_name'=>$firstname, 'last_name'=>$lastname);
		
		foreach ($input as $key => $value) {

			// encode user data			
			$encrypted = encryption($value, $vector);
			
			$records[] = array(
			'user_id' => $user_id,
			'meta_key' => $key,
			'meta_value' => $encrypted,
			'minutia' => null
			);
			
		}		
		
		$db->insert('users_meta', $records);
		
		$return['site_admin_email'] = $username;
		$return['site_admin_name'] = $firstname.' '.$lastname;
		$return['success'] = '<span style="color:green;">Success: your Site Admin user has been created.</span">';
		
		
		return $return;
	

}
	/**
	 * take an email address and return the crypt
	 */
	function lookup($email) {

		// get salt
		$user_salt = substr(hash('md5', str_replace('@', hex2bin($GLOBALS['site_key']), $email)), 0, 22);

		// create lookup
		return crypt($email,'$2y$10$' . $user_salt . '$');
		
	}
	
	function create_hash($email, $password) {
	
		// get salt
		$user_salt = substr(hash('md5', str_replace('@', hex2bin($GLOBALS['site_key']), $email)), 0, 22);

		// combine credentials
		$credentials = $email . $password;

		// new hash value
		return crypt($credentials,'$2y$10$' . $user_salt . '$');
	
	}
	
	function create_vector() {
		if (OPENSSL_VERSION_NUMBER) {
			return base64_encode(openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc')));
		} else {
			return base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM));
		}
	}
	
	function encryption($encode_text,$vector) {
		if (OPENSSL_VERSION_NUMBER) {
			return base64_encode(openssl_encrypt($encode_text,'aes-256-cbc',hex2bin($GLOBALS['site_key']),OPENSSL_RAW_DATA,base64_decode($vector)));
		} else {
			return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, hex2bin($GLOBALS['site_key']), $encode_text, MCRYPT_MODE_CBC, base64_decode($vector)));
		}
	}


function create_site_key() {
	if (function_exists('random_bytes')) {
		$site_key = bin2hex(random_bytes(32));
	} else {
		$site_key = bin2hex(@mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
	}

	// if random_bytes is not available and mcrypt_create_iv has been depreciated, then do the following
	if (empty($site_key)) {
		die('An error occured when attempting to create a SITE_KEY');
	}
	
	define_config_value('SITE_KEY', $site_key);
}

/**
 * Checks to see if a site key has already been defined.
 * @param $superadmin_email to send the new key to the superadmin
 * @return creates a site key if necessary
 */
function site_key($siteadmin_email) {
		$site_key = check_config_value('SITE_KEY');
		$GLOBALS['content'] .= "Hello ".$siteadmin_email.", your new site key has been created<br>
		It is:<br>
		<span style='color:green;'>".$site_key."</span><br>
		Please keep this in your records.";
		$msg = "Hello, \n
		This is the VCE SITE_KEY for your site:\n
		".$site_key."\n
		It is stored in the vce-config.php file at the root of your installation.\n
		Please keep it in your records to use in the event of a corruption of that configuration file.\n
		Thank you!";
		mail($siteadmin_email,'VCE SITE_KEY',$msg);
		return;
}




function check_database() {
	$return_toggle = FALSE;
	if (isset($_POST['pagecheck']) && $_POST['pagecheck']=='check') {
		if (empty($_POST['dbhost'])) {
			$GLOBALS['content'] .= '<br><span style="color:red;">You have not specified a database host.</span><br>';
			$return_toggle = TRUE;
		}
		if (empty($_POST['dbname'])) {
			$GLOBALS['content'] .= '<br><span style="color:red;">You have not specified a database name.</span><br>';
			$return_toggle = TRUE;
		}
		if (empty($_POST['dbuser'])) {
			$GLOBALS['content'] .= '<br><span style="color:red;">You have not specified a database user.</span><br>';
			$return_toggle = TRUE;
		}
		if (empty($_POST['dbpassword'])) {
			$GLOBALS['content'] .= '<br><span style="color:red;">You have not specified a database user password.</span><br>';
			$return_toggle = TRUE;
		}
		if ($return_toggle == TRUE) {
			return 'wait';
		}
		define_config_value('DB_HOST', $_POST['dbhost']);
		define_config_value('DB_NAME', $_POST['dbname']);
		define_config_value('DB_USER', $_POST['dbuser']);
		define_config_value('DB_PASSWORD', $_POST['dbpassword']);
		define_config_value('TABLE_PREFIX', $_POST['dbprefix']);
		
		include_once(BASEPATH.'vce-config.php');
		try {
            $dbconnection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, defined('DB_PORT') ? DB_PORT : '3306');
//             mysqli_report(MYSQLI_REPORT_STRICT);
    		if ($dbconnection->connect_error) {
        		$GLOBALS['content'] .= '<br><span style="color:red;">The database connection has not been successful.<br>
        		The error: '.$dbconnection->connect_error.'</span><br>';
        		return 'wait';

    		} else {            
    			$GLOBALS['content'] .= '<br><span style="color:green;">You have connected successfully to the database "'.check_config_value('DB_NAME').'". Please click on &quot;continue&quot;.</span><br>';
				return 'continue';
			}
    
        } catch (Exception $e) {
        echo "";
        return 'wait';
//         	$GLOBALS['content'] .= $e->getMessage();
//			die('Database connection failed');
        }


	}
	return 'wait';
}



/**
 * Checks to see if all required modules and services are present on the server.
 * @param array $extensions names of extensions to check for
 * @return prints out success or failure notices for everything checked
 */
function extension_check($extensions) {
  	$fail = '';
	$pass = '';
	
	if (version_compare(phpversion(), '5.3.0', '<')) {
		$fail .= '<li>You need<strong> PHP 5.3.0</strong> (or greater)</li>';
	} else {
		$pass .='<li>Your version of PHP '.phpversion().' is greateer than PHP 5.3.0</li>';
	}
	if (!ini_get('safe_mode')) {
		$pass .='<li>Safe Mode is <strong>off</strong></li>';
// 		preg_match('/[0-9]\.[0-9]+\.[0-9]+/', mysqli_get_server_info(), $version);
// 		
// 		if (version_compare($version[0], '4.1.20', '<')) {
// 			$fail .= '<li>You need<strong> MySQL 4.1.20</strong> (or greater)</li>';
// 		} else {
// 			$pass .='<li>You have<strong> MySQL 4.1.20</strong> (or greater)</li>';
// 		}
	} else {
		$fail .= '<li>Safe Mode is <strong>on</strong></li>';
	}
	
	foreach($extensions as $extension) {
		if (!extension_loaded($extension)) {
			$fail .= '<li> You are missing the <strong>'.$extension.'</strong> extension</li>';
		} else {
			$pass .= '<li>You have the <strong>'.$extension.'</strong> extension</li>';
		}
	}
	
	// adding message about date.timezone
	if (!date_default_timezone_get()) {
		/*
		'Kwajalein',
		'Pacific/Midway',
		'Pacific/Honolulu',
		'America/Anchorage',
		'America/Los_Angeles',
		'America/Denver',
		'America/Tegucigalpa',
		'America/New_York',
		'America/Caracas',
		'America/Halifax',
		'America/St_Johns',
		'America/Argentina/Buenos_Aires',
		'America/Sao_Paulo',
		'Atlantic/South_Georgia',
		'Atlantic/Azores',
		'Europe/Dublin',
		'Europe/Belgrade',
		'Europe/Minsk',
		'Asia/Kuwait',
		'Asia/Tehran',
		'Asia/Muscat',
		'Asia/Yekaterinburg',
		'Asia/Kolkata',
		'Asia/Katmandu',
		'Asia/Dhaka',
		'Asia/Rangoon',
		'Asia/Krasnoyarsk',
		'Asia/Brunei',
		'Asia/Seoul',
		'Australia/Darwin',
		'Australia/Canberra',
		'Asia/Magadan',
		'Pacific/Fiji',
		'Pacific/Tongatapu'
		*/
		$pass .= '<li>date.timezone has not been set in php.ini and will default to America/Los_Angeles</li>';
	}
	
	$pass .= '<li>Your .htaccess file has been successfully updated.</li>';
	
	if ($fail) {
		$GLOBALS['content'] .= '<p><strong>Your server does not meet the following requirements in order to install Nestor.</strong>';
		$GLOBALS['content'] .= '<br>The following requirements failed, please contact your hosting provider in order to receive assistance with meeting the system requirements for Nestor:';
		$GLOBALS['content'] .= '<ul>'.$fail.'</ul></p>';
		$GLOBALS['content'] .= 'The following requirements were successfully met:';
		$GLOBALS['content'] .= '<ul>'.$pass.'</ul>';
		return 'wait';
	} else {
		$GLOBALS['content'] .= '<p><strong><span style="color:green;">Congratulations!</span></strong><span style="color:green;"> Your server meets the requirements for Nestor.</p></span>';
		$GLOBALS['content'] .= '<ul>'.$pass.'</ul>';
		return 'continue';

	}
}

/**
 * Decides to use existing config.php file or create one.
 * Checks to see if there is a config file, creates one if not, and uses the config_sample as a template if exists.
 * @return creates vce-config.php
 */
function check_config_file() {
	if (!file_exists(BASEPATH.'vce-config.php') && file_exists(BASEPATH.'vce-config-sample.php')) {
		$GLOBALS['using_config_sample'] = TRUE;
		touch(BASEPATH.'vce-config.php');
	} elseif (!file_exists(BASEPATH.'vce-config.php') && !file_exists(BASEPATH.'vce-config-sample.php')) {
		touch(BASEPATH.'vce-config.php');
	} else {
		$GLOBALS['config_file_exists'] = TRUE;
	}
}

/**
 * Decides to use existing .htaccess file or create one.
 * @return creates .htaccess
 */
function check_htaccess_file() {
	if (!file_exists(BASEPATH.'.htaccess')) {
		touch(BASEPATH.'.htaccess');
		
	}

}

/**
 * Edits htaccess file.
 * Goes through the .htaccess  file line by line, and replaces target directives with correct (or same) directives
 * or creates them.
 * @return new .htaccess file with corrected directives
 */
function edit_htaccess_file() {
	$reading = file_get_contents(BASEPATH.'.htaccess');
	$writing = fopen(BASEPATH.'.htaccessTEMP', 'w');

	$replaced = false;

$required_content = PHP_EOL.'RewriteEngine On'.PHP_EOL.'
RewriteBase '.DOCPATH.''.PHP_EOL.'
RewriteRule ^index\.php$ - [L]'.PHP_EOL.'
RewriteCond %{REQUEST_FILENAME} !-f'.PHP_EOL.'
RewriteCond %{REQUEST_FILENAME} !-d'.PHP_EOL.'
RewriteRule . '.DOCPATH.'index.php [L]'.PHP_EOL.'
RedirectMatch 301 '.DOCPATH.'vce-content/uploads/(.*) '.DOCPATH.PHP_EOL;

	preg_match('/.*<IfModule\s*mod_rewrite.c>(.*?)<\/IfModule>/ms', $reading, $matches);
	//to disable the parsing and simply wipe .htacces clean and add new content:
	if (1 == 2) {	
// if (isset($matches[1])) {
// echo '<br>';
// echo $matches[1];
	$matches[1] = str_replace($required_content, '', $matches[1]);
	$matches[1] = str_replace('###vce-directives', '', $matches[1]);
// 	$matches[1] = str_replace(PHP_EOL, '', $matches[1]);
	
// 	echo '<br>';
// echo $matches[1];
	$replacement = '<IfModule mod_rewrite.c>'.$matches[1].PHP_EOL.'###vce-directives'.$required_content.'###vce-directives'.PHP_EOL.'</IfModule>';
	$data = preg_replace('/<IfModule\s*mod_rewrite.c>(.*?)<\/IfModule>/ms', $replacement, $reading);
	$data2 = str_replace($data, '', $reading);
    
	fputs($writing, $data);
	
	$replaced = true;
} else {
	$insertion = '<IfModule mod_rewrite.c>'.PHP_EOL.'###vce-directives'.$required_content.'###vce-directives'.PHP_EOL.'</IfModule>';
	fputs($writing, $insertion);
	$replaced = true;
}

	//fclose($reading);
	 fclose($writing);
	// might as well not overwrite the file if we didn't replace anything
	if ($replaced) {
   		rename('.htaccessTEMP', '.htaccess');
	} else {
  		 unlink('.htaccessTEMP');
	}
}

/**
 * Edits constants in the config.php file.
 * Goes through the config.php file line by line, looking for the $constant_name to edit, and replaces
 * that whole line if it finds it. Otherwise writes the same line it has just read. Does NOT create the file
 * if it does not exist.
 * @param string $constant_name
 * @param string $constant_value
 * @return new config.php file with new constant value
 */
function define_config_value($constant_name, $constant_value) {
	$reading = fopen(BASEPATH.'vce-config.php', 'r');
	$writing = fopen(BASEPATH.'vce-configTEMP.php', 'w');
	$replaced = FALSE;

	while (!feof($reading)) {
  			$line = fgets($reading);
  		if (strstr($line, $constant_name)) { 	
  			if ($constant_value == 'true' || $constant_value == 'false') {
   				$line = "define('".$constant_name."', ".$constant_value.");".PHP_EOL;
   				$replaced = true;
 			} else {
 			   	$line = "define('".$constant_name."', '".$constant_value."');".PHP_EOL;
   			 	$replaced = true;  			 	
 			}
 		
 		}
 		fputs($writing, $line);
	}
	if ($replaced == FALSE && !empty($constant_name)) {
  			if ($constant_value == 'true' || $constant_value == 'false') {
   				$line = "define('".$constant_name."', ".$constant_value.");".PHP_EOL;
   				$replaced = true;
 			} else {
 			   	$line = "define('".$constant_name."', '".$constant_value."');".PHP_EOL;
   			 	$replaced = true;
 			}
		fputs($writing, $line); 
	}

	fclose($reading); fclose($writing);
	// might as well not overwrite the file if we didn't replace anything
	if ($replaced == true) {
// 		unlink(BASEPATH.'vce-config.php');
  		rename(BASEPATH.'vce-configTEMP.php', BASEPATH.'vce-config.php');
	} else {
 		unlink(BASEPATH.'vce-configTEMP.php');
	}
}


/**
 * Checks constant values in the config.php file.
 * Uses PHP's "token_get_all" to look at all the defined constants in vce_config.php
 * @param string $constant_name
 * @return mixed $constant_value
 */
function check_config_value($constant_name) {
	$defines = array();
	$state = 0;
	$key = '';
	$value = '';

	$file = file_get_contents(BASEPATH.'vce-config.php');
	$tokens = token_get_all($file);
	$token = reset($tokens);
	while ($token) {
    	if (is_array($token)) {
       	 if ($token[0] == T_WHITESPACE || $token[0] == T_COMMENT || $token[0] == T_DOC_COMMENT) {
           	 // do nothing
       	 } else if ($token[0] == T_STRING && strtolower($token[1]) == 'define') {
            $state = 1;
       	 } else if ($state == 2 && is_constant($token[0])) {
       	     $key = $token[1];
       	     $state = 3;
      	  } else if ($state == 4 && is_constant($token[0])) {
      	      $value = $token[1];
       	     $state = 5;
      	  }
   	 } else {
     	   $symbol = trim($token);
     	   if ($symbol == '(' && $state == 1) {
     	       $state = 2;
     	   } else if ($symbol == ',' && $state == 3) {
     	       $state = 4;
     	   } else if ($symbol == ')' && $state == 5) {
     	       $defines[strip($key)] = strip($value);
     	       $state = 0;
     	   }
   	 }
  	  $token = next($tokens);
	}
	//checks constant existance and returns value if exists
	foreach ($defines as $k => $v) {
		if ($constant_name == $k) {
//   	 	 	echo "'$k' => '$v'\n";
  	 	 	return $v;
  	 	 }
	}

}
/**
 * Checks if token is constant.
 * Called from check_config_value().
 * @param mixed $token
 * @return mixed $token
 */
function is_constant($token) {
    return $token == T_CONSTANT_ENCAPSED_STRING || $token == T_STRING ||
        $token == T_LNUMBER || $token == T_DNUMBER;
}


/**
 * Strips constant value.
 * Called from check_config_value().
 * @param mixed $value
 * @return mixed $value
 */
function strip($value) {
	  return preg_replace('!^([\'"])(.*)\1$!', '$2', $value);
}



function form_validation_js() {
$script = <<<EOF
<script src="vce-application/js/jquery/jquery.min.js"></script>
<script src="vce-application/js/jquery/jquery-ui.min.js"></script>
<script type='text/javascript'>
$(document).ready(function() {

// accordion
$(document).on('click','.accordion-title', function(e) {
	if ($(this).hasClass('disabled') !== true) {
		$(this).attr("aria-expanded",($(this).attr("aria-expanded") != "true"));
		if ($(this).closest('.accordion-container').hasClass('accordion-open')) {	
			// $(this).closest('.accordion-container').addClass('accordion-closed');
			$(this).closest('.accordion-container').find('.accordion-content').first().slideUp('slow', function() {
				$(this).closest('.accordion-container').removeClass('accordion-open').addClass('accordion-closed');
			});
		} else {
			$(this).closest('.accordion-container').addClass('accordion-open');
			$(this).closest('.accordion-container').find('.accordion-content').first().slideDown('slow', function() {
				$(this).closest('.accordion-container').removeClass('accordion-closed');
			});
		}
	}
	e.preventDefault();
});


$('.input-label-style').on('focus', 'textarea, input[type=text], input[type=email], input[type=password], select', function(e) {		
	$(this).closest('.input-label-style').removeClass('highlight-alert').addClass('highlight');
});

$('.input-label-style').on('change', 'input[type=checkbox], input[type=radio]', function(e) {		
	$(this).closest('.input-label-style').removeClass('highlight-alert').addClass('highlight');
});

$('.input-label-style').on('blur', 'textarea, input[type=text], input[type=email], input[type=password], select', function() {
	$(this).closest('.input-label-style').removeClass('highlight');
});


$('.installer-form').on('submit', function(e) {

	var formsubmitted = $(this);
	
	var submittable = true;
			
	var typetest = $(this).find('input[type=text]');
		typetest.each(function(index) {
			if ($(this).val() == "" && $(this).attr('tag') == 'required') {
				$(this).closest('.input-label-style').addClass('highlight-alert');
				submittable = false;
			}
		});
	
	var emailtest = $(this).find('input[type=email]');
		emailtest.each(function(index) {
			reg = /^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
			if (!reg.test($(this).val()) && $(this).attr('tag') == 'required') {
				$(this).closest('.input-label-style').addClass('highlight-alert');
				submittable = false;
			}
		});
		
	var passwordtest = $(this).find('input[type=password]');
		var test = [];
		passwordtest.each(function(index) {
		test[index] = $(this).val();
			if ($(this).val() == "" && $(this).attr('tag') == 'required') {
				console.log($(this.closest('.input-label-style')));
				$(this).closest('.input-label-text').find('.input-label-error').text('Enter A Password');
				$(this).closest('.input-label-style').addClass('highlight-alert');
				submittable = false;
			}
      		re = /[0-9]/;
      		if (!re.test($(this).val())) {
       			$(this).closest('.input-label-style').find('.input-label-error').text('Password must contain at least one number (0-9)');
				$(this).closest('.input-label-style').addClass('highlight-alert');
				submittable = false;
      		}
      		re = /[a-z]/;
      		if (!re.test($(this).val())) {
       			$(this).closest('.input-label-style').find('.input-label-error').text('Password must contain at least one lowercase letter (a-z)');
				$(this).closest('.input-label-style').addClass('highlight-alert');
				submittable = false;
      		}
      		re = /[A-Z]/;
      		if (!re.test($(this).val())) {
       			$(this).closest('.input-label-style').find('.input-label-error').text('Password must contain at least one uppercase letter (A-Z)');
				$(this).closest('.input-label-style').addClass('highlight-alert');
				submittable = false;
      		}
      		if (test[0] && test[1]) {
      			if (test[0] !== test[1]) {
       			$(this).closest('.input-label-style').find('.input-label-error').text('Passwords do not match');
				$(this).closest('.input-label-style').addClass('highlight-alert');
				submittable = false;
      			}
      		}
		});
		

	if (submittable) {
		return true;
	}
	
	return false;

});

function checkContinueForm(form)
  {
    if (form.direction.value == "wait") {
      alert("You must successfully submit the form below to continue!");
      form.username.focus();
      return false;
     }
     return true; 
  }

});
</script>
EOF;

return $script;
}

function installer_css(){

$style = <<<EOF
<style>

* {
font-family: sans-serif;
font-weight: 400;
font-size: 15px;
color: #333;
-webkit-tap-highlight-color: rgba(0,0,0,0);
}

html, body {
height: 100%;
margin: 0;
padding: 0;
-webkit-text-size-adjust: 100%;
-moz-text-size-adjust: 100%;
-ms-text-size-adjust: 100%;
}

#wrapper {
position: relative;
display: block;
width: 100%;
min-height: 100%;
margin: 0;
padding: 0;
background: #fff;
}

#content {
position: relative;
display: block;
padding: 0px 0px 100px 0px;
}

#decorative-bar {
position: relative;
display: block;
height: 15px;
background-color: #00A14B;
}

#header {
position: relative;
display: block;	
background-color: #005EAC;
height: 100px;
}

#header .inner {
height: 100px;
}

#header h1 {
font-size: 28px;
letter-spacing: 2px;
color: #FFF;
text-transform: uppercase;
text-align: center;
padding-top: 20px;
margin-top: 0px;
margin-bottom: 0px;
}

h1 {
font-size: 24px;
letter-spacing: 2px;
}

#info-bar {
height: 50px;
padding: 10px 0px 10px 0px;
background-color: #EEE8DA;
}

.inner {
width: 940px;
margin: 0 auto;
}

#info-bar-left {
display: block;
float: left;
max-width: 45%;
text-align: left;
}

#info-bar-right {
display: block;
float: right;
max-width: 45%;
text-align: right;
}

#welcome-text {
display: block;
padding: 0px 10px;
}


/* footer */
#footer {
position: absolute;
display: block;
width: 100%;
height: 60px;
bottom: 0px;
left: 0px;
color: #FFF;
text-align: center;
font-size: 11px;
background-color: #00A14B;
padding: 20px 0px 0px 0px;
line-height: 20px;
}

#footer .inner {
color: #FFF;
text-align: center;
font-size: 11px;
}


/* forms */

.input-label-style {
position: relative;
display: block;
background-color: #fbfbfb;
border-width: 1px;
border-style: solid;
border-color: #d3d3d3;
border-radius: 3px;
padding: 0px 0px 0px 0px;
margin: 0px 0px 20px 0px;
-webkit-touch-callout: none;
-webkit-user-select: none;
-khtml-user-select: none;
-moz-user-select: none;
-ms-user-select: none;
user-select: none;
}

.input-label-style:hover {
border-color: #9c3;
}

.input-label-style.omit {
position: relative;
display: inline;
background-color: transparent;
border: 0;
padding: 0;
margin: 0;
white-space: nowrap;
}

.input-label-text {
position: absolute;
display: block;
width: 100%;
min-height: 20px;
background-color: #d3d3d3;
text-align: center;
padding: 0px 0px;
}

.input-label-message {
display: block;
font-size: 12px;
line-height: 20px;
color: #333;
border-width: 0px;
border-style: solid;
border-color: transparent;
background-color: transparent;

}

.input-label-style.highlight .input-label-text {
background-color: #999;
}

.input-label-style.highlight .input-label-message {
color: #fff;
}

.input-label-text .input-label-error {
display: none;
}

.input-label-style.highlight-alert .input-label-text {
background-color: #e33;
}

.input-label-style.highlight-alert .input-label-text .input-label-message {
display: none;
}

.input-label-style.highlight-alert .input-label-text .input-label-error {
display: block;
font-size: 12px;
line-height: 20px;
color: #fff;
}

textarea, input[type=text], input[type=email], input[type=password], input[type=file] {
width: calc(100% - 21px);
min-height: 34px;
font-size: 16px;
padding: 20px 10px 5px 10px;
margin: 0px 0px 0px 1px;
border: 0;
resize: none;
overflow: auto;
background-color: #fbfbfb;
}

textarea {
margin: 0px 0px -5px 1px;
}

input[type=text].compact-style {
width: auto;
min-height: 0;
height: 20px;
padding: 2px 5px;
margin: 0px;
border: solid 1px #333;
}

.input-label-style .static-content {
padding: 25px 0px 15px 15px;
}

.input-label-style .input-padding {
padding: 25px 15px 15px 15px;
}

.input-label-style.no-padding .static-content {
padding: 0px;
}

.input-label-style .static-content .static-content-block {
background-color: #fff;
padding: 10px;
border-width: 1px;
border-style: solid;
border-color: #d3d3d3;
}

.input-label-style.add-padding {
padding: 25px 15px 15px 15px;
}

.input-label-style.top-padding {
padding: 20px 0px 0px 0px;
}

.input-label-style.top-padding .input-label-text {
margin: -20px 0px 0px 0px;
}


.input-padding-small {
padding: 7px 5px;
}

select {
width: 100%;
font-size: 16px;
border: 0;
padding: 0px 15px 0px 15px;
margin: 32px 0px 11px 0px;
-webkit-appearance: none;
-moz-appearance: none;
text-indent: 0.01px;
text-overflow: "";
appearance: none;
outline: none;
background: url('../images/arrows.png') no-repeat 100% 0% #fbfbfb;
}

select.compact-style {
width: auto;
padding: 3px 25px 3px 5px;
margin: 0px 0px 0px 0px;
background: url('../images/arrows.png') no-repeat 100% 50% #fbfbfb;
border: 1px solid #d3d3d3;
}

select.compact-style:focus {
outline: auto;
}

.select2-selection.select2-selection--multiple {
background: url('../images/arrows.png') no-repeat 100% 0% #fbfbfb;
}

input[type=submit], input[type=reset], button, .link-button {
display: inline-block;
border-width: 1px;
border-style: solid;
border-color: #696969;
border-radius: 5px;
font-size: 12px;
color: #fbfbfb;
background-color: #696969;
cursor: pointer;
text-decoration: none;
padding: 4px 7px;
margin: 0px 10px 0px 0px;
-webkit-appearance: none;
-webkit-touch-callout: none;
-webkit-user-select: none;
-khtml-user-select: none;
-moz-user-select: none;
-ms-user-select: none;
user-select: none;
}

button, .link-button {
padding: 3px 5px;
}

button.selected, .link-button.selected {
background-color:#fbfbfb;
border-color: #696969;
color: #696969;
text-decoration: none;
-webkit-appearance: none;
}

input[type=submit]:hover, input[type=reset]:hover, button:hover, .link-button:hover, input[type=submit].highlighted, .link-button.highlighted {
background-color:#fbfbfb;
border-color: #696969;
color: #696969;
text-decoration: none;
-webkit-appearance: none;
}

button.no-style, button.no-style:hover {
border: none;
background-color: transparent;
}

/* accordion */

.accordion-container {
padding: 0px 0px 0px 0px;
margin: 15px 0px 3px 0px;
}

.accordion-title {
position: relative;
display: block;
width: 100%;
color: #fff;
background-color: #696969;
border-width: 1px;
border-style: solid;
border-color: #696969;
text-align: center;
margin: 0px 0px 0px 0px;
text-decoration: none;
-webkit-touch-callout: none;
-webkit-user-select: none;
-khtml-user-select: none;
-moz-user-select: none;
-ms-user-select: none;
user-select: none;
cursor: pointer;
}

.accordion-container.accordion-closed > .accordion-heading > .accordion-title.active {
background-image: url("../images/icon-accordion-down.png");
background-position: right; 
background-repeat: no-repeat; 
background-size: 40px;
}

.accordion-title.active:hover  {
background-color: #838383;
border-color: #838383;
}

.accordion-title.disabled {
cursor: default;
border-radius: 5px 5px 0px 0px;
}

.accordion-title.disabled:hover  {
background-color: #696969;
}

.accordion-title.disabled.close {
border-radius: 5px;
}

.accordion-container.accordion-open > .accordion-heading > .accordion-title.active {
background-image: url("../images/icon-accordion-up.png");
background-position: right; 
background-repeat: no-repeat; 
background-size: 40px;
border-radius: 5px 5px 0px 0px;
}

.accordion-title span {
display: block;
color: #fff;
font-size: 18px;
padding: 10px 40px 10px 40px;
}

.accordion-content {
display: none;
padding: 15px;
background-color: #fff;
border-width: 1px;
border-style: solid;
border-color: #a3a3a3;
margin: 0;
}

.accordion-container.no-padding .accordion-content {
padding: 0px;
}

.accordion-container > .accordion-content {
border-radius: 0px 0px 5px 5px;
}

.accordion-content > table.tablesorter {
margin: 0px;
}

.accordion-container.accordion-open > .accordion-content {
display: block;
}

.accordion-container.accordion-closed > .accordion-content {
display: none;
}

</style>
EOF;

return $style;

}

function print_globals() {
	foreach($_SESSION as $key=>$value) {

		echo $key.': ';
		print_r($value);
		echo '<br>';
	}
}


/**
 * Records specifics about the site installation
 *
 */
function personalize_site($site_url, $site_name, $site_description, $db) {

	$site_name = $db->mysqli_escape($site_name);
	$site_description = $db->mysqli_escape($site_description);
	$site_url = $db->mysqli_escape($site_url);
	
	$query = array();

	$query[] = "UPDATE ".TABLE_PREFIX."site_meta SET meta_value = '".$site_url."' WHERE meta_key = 'site_url'";	
	$query[] = "UPDATE ".TABLE_PREFIX."site_meta SET meta_value = '".$site_name."' WHERE meta_key = 'site_title'";
	$query[] = "UPDATE ".TABLE_PREFIX."site_meta SET meta_value = '".$site_name."' WHERE meta_key = 'site_description'";
	$query[] = "UPDATE ".TABLE_PREFIX."site_meta SET meta_value = '".$_SESSION['site_admin']."' WHERE meta_key = 'site_email'";
	$query[] = "UPDATE ".TABLE_PREFIX."site_meta SET meta_value = '".$_SESSION['site_admin']."' WHERE meta_key = 'site_contact_email'";	

	foreach ($query as $each_query) {
		$db->query($each_query);
	}

	return 'continue';
	
}

function define_sql(){
$sql = <<<EOF

CREATE TABLE `vce_components` (
`component_id` bigint(20) unsigned NOT NULL,
`parent_id` bigint(20) unsigned NOT NULL DEFAULT '0',
`sequence` bigint(20) unsigned NOT NULL DEFAULT '0',
`url` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=17;

INSERT INTO `vce_components` (`component_id`, `parent_id`, `sequence`, `url`) VALUES
(1, 0, 1, ''),
(2, 1, 1, ''),
(3, 2, 1, 'admin'),
(4, 3, 1, 'admin/manage_recipes'),
(5, 3, 2, 'admin/manage_components'),
(6, 3, 4, 'admin/manage_menus'),
(7, 3, 5, 'admin/manage_users'),
(8, 3, 6, 'admin/manage_site'),
(9, 3, 7, 'admin/manage_datalists'),
(10, 0, 1, 'logout'),
(11, 0, 1, '/'),
(12, 0, 1, 'user'),
(13, 11, 1, ''),
(14, 13, 1, ''),
(15, 14, 1, ''),
(16, 15, 1, '');

ALTER TABLE `vce_components`
ADD PRIMARY KEY (`component_id`);
ALTER TABLE `vce_components`
ADD INDEX (`component_id`);
ALTER TABLE `vce_components`
ADD INDEX (`parent_id`);

ALTER TABLE `vce_components`
MODIFY `component_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=17;

CREATE TABLE `vce_components_meta` (
`id` bigint(20) unsigned NOT NULL,
`component_id` bigint(20) unsigned NOT NULL,
`meta_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
`meta_value` text COLLATE utf8_unicode_ci NOT NULL,
`minutia` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=82;

INSERT INTO `vce_components_meta` (`id`, `component_id`, `meta_key`, `meta_value`, `minutia`) VALUES
(1, 1, 'created_by', '1', ''),
(2, 1, 'created_at', UNIX_TIMESTAMP(), ''),
(3, 1, 'recipe', '{\"recipe\":[{\"title\":\"Login\",\"auto_create\":\"forward\",\"type\":\"Login\",\"components\":[{\"content_create\":1,\"role_access\":1,\"content_delete\":\"user\",\"content_edit\":\"user\",\"repudiated_url\":\"/\",\"title\":\"Admin Area\",\"auto_create\":\"forward\",\"type\":\"Access\",\"components\":[{\"url\":\"admin\",\"title\":\"Admin\",\"auto_create\":\"forward\",\"type\":\"Location\",\"components\":[{\"url\":\"admin/manage_recipes\",\"title\":\"Manage Recipes\",\"auto_create\":\"forward\",\"type\":\"ManageRecipes\"},{\"url\":\"admin/manage_components\",\"title\":\"Manage Components\",\"auto_create\":\"forward\",\"type\":\"ManageComponents\"},{\"url\":\"admin/manage_menus\",\"title\":\"Manage Menus\",\"auto_create\":\"forward\",\"type\":\"ManageMenus\"},{\"url\":\"admin/manage_users\",\"title\":\"Manage Users\",\"auto_create\":\"forward\",\"type\":\"ManageUsers\"},{\"url\":\"admin/mange_site\",\"title\":\"Manage Site\",\"auto_create\":\"forward\",\"type\":\"ManageSite\"},{\"url\":\"admin/manage_datalists\",\"title\":\"Manage Datalists\",\"auto_create\":\"forward\",\"type\":\"ManageDatalists\"}]}]}]}],\"recipe_name\":\"Admin\"}', ''),
(4, 1, 'title', 'Login', ''),
(5, 1, 'type', 'Login', ''),
(6, 1, 'recipe_name', 'Admin', ''),
(7, 2, 'created_by', '1', ''),
(8, 2, 'created_at', UNIX_TIMESTAMP(), ''),
(9, 2, 'role_access', '1', ''),
(10, 2, 'title', 'Admin Area', ''),
(11, 2, 'type', 'Access', ''),
(12, 2, 'content_create', '1', ''),
(13, 2, 'content_delete', 'user', ''),
(14, 2, 'content_edit', 'user', ''),
(15, 3, 'created_by', '1', ''),
(16, 3, 'created_at', UNIX_TIMESTAMP(), ''),
(17, 3, 'title', 'Admin', ''),
(18, 3, 'type', 'Location', ''),
(19, 4, 'created_by', '1', ''),
(20, 4, 'created_at', UNIX_TIMESTAMP(), ''),
(21, 4, 'title', 'Manage Recipes', ''),
(22, 4, 'type', 'ManageRecipes', ''),
(23, 5, 'created_by', '1', ''),
(24, 5, 'created_at', UNIX_TIMESTAMP(), ''),
(25, 5, 'title', 'Manage Components', ''),
(26, 5, 'type', 'ManageComponents', ''),
(27, 6, 'created_by', '1', ''),
(28, 6, 'created_at', UNIX_TIMESTAMP(), ''),
(29, 6, 'title', 'Manage Menus', ''),
(30, 6, 'type', 'ManageMenus', ''),
(31, 6, 'updated_at', UNIX_TIMESTAMP(), ''),
(32, 7, 'created_by', '1', ''),
(33, 7, 'created_at', UNIX_TIMESTAMP(), ''),
(34, 7, 'title', 'Manage Users', ''),
(35, 7, 'type', 'ManageUsers', ''),
(36, 8, 'created_by', '1', ''),
(37, 8, 'created_at', UNIX_TIMESTAMP(), ''),
(38, 8, 'title', 'Manage Site', ''),
(39, 8, 'type', 'ManageSite', ''),
(40, 9, 'created_by', '1', ''),
(41, 9, 'created_at', UNIX_TIMESTAMP(), ''),
(42, 9, 'title', 'Manage Datalists', ''),
(43, 9, 'type', 'ManageDatalists', ''),
(44, 10, 'created_by', '1', ''),
(45, 10, 'created_at', UNIX_TIMESTAMP(), ''),
(46, 10, 'recipe', '{\"recipe\":[{\"url\":\"logout\",\"title\":\"Logout\",\"auto_create\":\"forward\",\"type\":\"Logout\"}],\"recipe_name\":\"Logout\"}', ''),
(47, 10, 'title', 'Logout', ''),
(48, 10, 'type', 'Logout', ''),
(49, 10, 'recipe_name', 'Logout', ''),
(50, 11, 'created_by', '1', ''),
(51, 11, 'created_at', UNIX_TIMESTAMP(), ''),
(52, 11, 'recipe', '{\"recipe\":[{\"url\":\"/\",\"title\":\"Home Page\",\"auto_create\":\"forward\",\"type\":\"Location\",\"components\":[{\"content_delete\":1,\"content_edit\":1,\"content_create\":1,\"role_access\":\"1|x\",\"title\":\"Access\",\"auto_create\":\"forward\",\"type\":\"Access\",\"components\":[{\"media_types\":\"Text\",\"title\":\"Media\",\"type\":\"Media\",\"components\":[{\"content_delete\":1,\"content_edit\":1,\"content_create\":\"1\",\"role_access\":\"1|x\",\"title\":\"Access\",\"auto_create\":\"forward\",\"type\":\"Access\",\"components\":[{\"title\":\"Comments\",\"type\":\"Comments\"}]}]}]}]}],\"recipe_name\":\"Home Page\"}', ''),
(53, 11, 'template', 'home.php', ''),
(54, 11, 'title', 'Home Page', ''),
(55, 11, 'type', 'Location', ''),
(56, 11, 'recipe_name', 'Home Page', ''),
(57, 12, 'created_by', '1', ''),
(58, 12, 'created_at', UNIX_TIMESTAMP(), ''),
(59, 12, 'recipe', '{\"recipe\":[{\"url\":\"user\",\"title\":\"User Settings\",\"auto_create\":\"forward\",\"type\":\"UserSettings\"}],\"recipe_name\":\"My Account\"}', ''),
(60, 12, 'title', 'User Settings', ''),
(61, 12, 'type', 'UserSettings', ''),
(62, 12, 'recipe_name', 'My Account', ''),
(63, 13, 'created_by', '1', ''),
(64, 13, 'created_at', UNIX_TIMESTAMP(), ''),
(65, 13, 'title', 'Access', ''),
(66, 13, 'type', 'Access', ''),
(67, 14, 'type', 'Media', ''),
(68, 14, 'media_type', 'Text', ''),
(69, 14, 'title', 'Text Block', ''),
(70, 14, 'text', 'Welcome to Nestor!', ''),
(71, 14, 'created_by', '1', ''),
(72, 14, 'created_at', UNIX_TIMESTAMP(), ''),
(73, 15, 'title', 'Access', ''),
(74, 15, 'type', 'Access', ''),
(75, 15, 'created_by', '1', ''),
(76, 15, 'created_at', UNIX_TIMESTAMP(), ''),
(77, 16, 'type', 'Comments', ''),
(78, 16, 'text', 'Access the Admin area to log into this site.', ''),
(79, 16, 'created_by', '1', ''),
(80, 16, 'created_at', UNIX_TIMESTAMP(), ''),
(81, 16, 'title', ' Comments', '');

ALTER TABLE `vce_components_meta`
ADD PRIMARY KEY (`id`);
ALTER TABLE `vce_components_meta`
ADD INDEX (`component_id`);
ALTER TABLE `vce_components_meta` 
ADD INDEX (`meta_key`);

ALTER TABLE `vce_components_meta`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=82;

CREATE TABLE `vce_datalists` (
`datalist_id` bigint(20) unsigned NOT NULL,
`parent_id` bigint(20) unsigned NOT NULL DEFAULT '0',
`item_id` bigint(20) unsigned NOT NULL DEFAULT '0',
`component_id` bigint(20) unsigned NOT NULL DEFAULT '0',
`user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
`sequence` bigint(20) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

ALTER TABLE `vce_datalists`
ADD PRIMARY KEY (`datalist_id`);
ALTER TABLE `vce_datalists`
ADD INDEX (`datalist_id`);
ALTER TABLE `vce_datalists`
ADD INDEX (`parent_id`);
ALTER TABLE `vce_datalists`
ADD INDEX (`item_id`);
ALTER TABLE `vce_datalists`
ADD INDEX (component_id);
ALTER TABLE `vce_datalists`
ADD INDEX (`user_id`);

ALTER TABLE `vce_datalists`
MODIFY `datalist_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

CREATE TABLE `vce_datalists_meta` (
`id` bigint(20) unsigned NOT NULL,
`datalist_id` bigint(20) unsigned NOT NULL,
`meta_key` varchar(255) CHARACTER SET latin1 NOT NULL,
`meta_value` text CHARACTER SET latin1 NOT NULL,
`minutia` varchar(255) CHARACTER SET latin1 NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

ALTER TABLE `vce_datalists_meta`
ADD PRIMARY KEY (`id`);
ALTER TABLE `vce_datalists_meta`
ADD INDEX (`datalist_id`);
ALTER TABLE `vce_datalists_meta`
ADD INDEX (`meta_key`);

ALTER TABLE `vce_datalists_meta`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

CREATE TABLE `vce_datalists_items` (
`item_id` bigint(20) NOT NULL,
`datalist_id` bigint(20) NOT NULL,
`sequence` bigint(20) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

ALTER TABLE `vce_datalists_items`
ADD PRIMARY KEY (`item_id`);
ALTER TABLE vce_datalists_items`
ADD INDEX (`item_id`);
ALTER TABLE vce_datalists_items`
ADD INDEX (`datalist_id`);

ALTER TABLE `vce_datalists_items`
MODIFY `item_id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

CREATE TABLE `vce_datalists_items_meta` (
`id` bigint(20) unsigned NOT NULL,
`item_id` bigint(20) unsigned NOT NULL,
`meta_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
`meta_value` text COLLATE utf8_unicode_ci NOT NULL,
`minutia` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

ALTER TABLE `vce_datalists_items_meta`
ADD PRIMARY KEY (`id`);
ALTER TABLE `vce_datalists_items_meta`
ADD INDEX (`item_id`);
ALTER TABLE `vce_datalists_items_meta`
ADD INDEX (`meta_key`);

ALTER TABLE `vce_datalists_items_meta`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

CREATE TABLE `vce_site_meta` (
`id` bigint(20) unsigned NOT NULL,
`meta_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
`meta_value` text COLLATE utf8_unicode_ci NOT NULL,
`minutia` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=17;

INSERT INTO `vce_site_meta` (`id`, `meta_key`, `meta_value`, `minutia`) VALUES
(1, 'site_url', '', ''),
(2, 'site_title', '', ''),
(3, 'site_description', '', ''),
(4, 'site_email', '', ''),
(5, 'site_contact_email', '', ''),
(6, 'site_menus', '{\"main\":[{\"role_access\":\"1|2|3\",\"title\":\"My Account\",\"url\":\"user\",\"id\":1171},{\"role_access\":\"1|2|3\",\"title\":\"Logout\",\"url\":\"logout\",\"id\":235},{\"role_access\":\"1|x\",\"title\":\"Admin\",\"url\":\"admin\",\"id\":49}]}', ''),
(7, 'site_theme', 'default', ''),
(8, 'roles', '{\"1\":{\"role_name\":\"Admin\",\"permissions\":{\"ManageUsers\":\"create_users,edit_users,delete_users,masquerade_users\"},\"role_hierarchy\":\"0\"}}', ''),
(9, 'user_attributes', '{\"first_name\":{\"type\":\"text\",\"title\":\"First Name\",\"required\":\"1\",\"sortable\":\"1\",\"editable\":\"1\"},\"last_name\":{\"type\":\"text\",\"title\":\"Last Name\",\"required\":\"1\",\"sortable\":\"1\",\"editable\":\"1\"}}', ''),
(10, 'installed_components', '{\"Input\":\"vce-application/components/input/input.php\",\"File\":\"vce-application/components/file/file.php\",\"Upload\":\"vce-application/components/upload/upload.php\",\"Access\":\"vce-application/components/access/access.php\",\"Item\":\"vce-application/components/item/item.php\",\"Layout\":\"vce-application/components/layout/layout.php\",\"Login\":\"vce-application/components/login/login.php\",\"Logout\":\"vce-application/components/logout/logout.php\",\"ManageMenus\":\"vce-application/components/managemenus/managemenus.php\",\"ManageRecipes\":\"vce-application/components/managerecipes/managerecipes.php\",\"ManageComponents\":\"vce-application/components/managecomponents/managecomponents.php\",\"ManageSite\":\"vce-application/components/managesite/managesite.php\",\"ManageUsers\":\"vce-application/components/manageusers/manageusers.php\",\"Media\":\"vce-application/components/media/media.php\",\"Set\":\"vce-application/components/set/set.php\",\"UserSettings\":\"vce-application/components/usersettings/usersettings.php\",\"ManageDatalists\":\"vce-application/components/managedatalists/managedatalists.php\",\"Location\":\"vce-application/components/location/location.php\",\"Image\":\"vce-application/components/image/image.php\",\"Text\":\"vce-application/components/text/text.php\",\"Breadcrumbs\":\"vce-content/components/breadcrumbs/breadcrumbs.php\",\"PHPSessions\":\"vce-application/components/php_sessions/php_sessions.php\",\"AccessibilityDefaultStyle\":\"vce-application/components/accessibility_default_style/accessibility_default_style.php\",\"Comments\":\"vce-application/components/comments/comments.php\"}', ''),
(11, 'activated_components', '{\"Input\":\"vce-application/components/input/input.php\",\"File\":\"vce-application/components/file/file.php\",\"Upload\":\"vce-application/components/upload/upload.php\",\"Access\":\"vce-application/components/access/access.php\",\"Item\":\"vce-application/components/item/item.php\",\"Layout\":\"vce-application/components/layout/layout.php\",\"Login\":\"vce-application/components/login/login.php\",\"Logout\":\"vce-application/components/logout/logout.php\",\"ManageMenus\":\"vce-application/components/managemenus/managemenus.php\",\"ManageRecipes\":\"vce-application/components/managerecipes/managerecipes.php\",\"ManageComponents\":\"vce-application/components/managecomponents/managecomponents.php\",\"ManageSite\":\"vce-application/components/managesite/managesite.php\",\"ManageUsers\":\"vce-application/components/manageusers/manageusers.php\",\"Set\":\"vce-application/components/set/set.php\",\"UserSettings\":\"vce-application/components/usersettings/usersettings.php\",\"ManageDatalists\":\"vce-application/components/managedatalists/managedatalists.php\",\"Location\":\"vce-application/components/location/location.php\",\"Media\":\"vce-application/components/media/media.php\",\"Image\":\"vce-application/components/image/image.php\",\"Text\":\"vce-application/components/text/text.php\",\"Breadcrumbs\":\"vce-content/components/breadcrumbs/breadcrumbs.php\",\"PHPSessions\":\"vce-application/components/php_sessions/php_sessions.php\",\"AccessibilityDefaultStyle\":\"vce-application/components/accessibility_default_style/accessibility_default_style.php\",\"Comments\":\"vce-application/components/comments/comments.php\"}', ''),
(12, 'preloaded_components', '{\"Input\":\"vce-application/components/input/input.php\",\"File\":\"vce-application/components/file/file.php\",\"Upload\":\"vce-application/components/upload/upload.php\",\"Media\":\"vce-application/components/media/media.php\",\"Breadcrumbs\":\"vce-content/components/breadcrumbs/breadcrumbs.php\",\"PHPSessions\":\"vce-application/components/php_sessions/php_sessions.php\",\"AccessibilityDefaultStyle\":\"vce-application/components/accessibility_default_style/accessibility_default_style.php\",\"Comments\":\"vce-application/components/comments/comments.php\"}', ''),
(13, 'enabled_mediatype', '{\"Image\":\"vce-application/components/image/image.php\",\"Text\":\"vce-application/components/text/text.php\"}', ''),
(14, 'path_routing', '[]', ''),
(15, 'cache', '', ''),
(16, 'timezone', 'America/Los_Angeles', '');

ALTER TABLE `vce_site_meta`
ADD PRIMARY KEY (`id`);

ALTER TABLE `vce_site_meta`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=17;

CREATE TABLE `vce_users` (
`user_id` bigint(20) unsigned NOT NULL,
`vector` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
`hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
`role_id` bigint(20) unsigned NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

ALTER TABLE `vce_users`
ADD PRIMARY KEY (`user_id`);
ALTER TABLE `vce_users`
ADD INDEX (`user_id`);

ALTER TABLE `vce_users`
MODIFY `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

CREATE TABLE `vce_users_meta` (
`id` bigint(20) unsigned NOT NULL,
`user_id` bigint(20) unsigned NOT NULL,
`meta_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
`meta_value` text COLLATE utf8_unicode_ci NOT NULL,
`minutia` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

ALTER TABLE `vce_users_meta`
ADD PRIMARY KEY (`id`);
ALTER TABLE `vce_users_meta`
ADD INDEX (`user_id`);
ALTER TABLE `vce_users_meta`
ADD INDEX (`meta_key`);

ALTER TABLE `vce_users_meta`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

EOF;
return $sql;
}

?>