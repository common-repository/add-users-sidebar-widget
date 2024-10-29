<?php
/*
Plugin Name: Add Users Sidebar Widget.
Plugin URI: http://blogs.ubc.ca/support/plugins/add-user-widget/
Description: Creates a sidebar widget that allows site users to add themselves to a blog based on predefined conditions. Based heavily on the sidebar add users widget by DSader
Author: OLT UBC
Version: 1.0.3
Author URI: http://olt.ubc.ca
*/

/*
 * UPDATES:
 * 1.0.3  Dec. 2, 2009 by Compass
 *   Fixed a bug when the widget is not enabled in index.php home page and adding user fails
 */

/*  Copyright 2008  UBC Office of Learning Technology  (email : andre.malan@hotmail.com)
    written for the University of British Columbia
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
function add_the_user() {
	require_once( ABSPATH . WPINC . '/registration.php');
	global $wpdb, $username, $blog_id, $userdata;
	$options = get_option('add_user_widget');
	get_currentuserinfo(); //allows us to check whether the user is a subscriber to the blog or not.
	$blog_id_info = 'wp_' . $blog_id . '_capabilities'; //this variable stores a representation of the wp_?_user_level variable from get_currentuserinfo with ? being the current blog id.
	$display_name = 'display_name';//used for the welcome method
	$idholder = 'ID';
	$user_id = $userdata->$idholder;
	$userAdded = FALSE; //Marker to check whether or not user has been added
	$nonce= isset($_REQUEST['adduser-nonce'])?$_REQUEST['adduser-nonce']:'';
		

	if (isset($_REQUEST['action']) && $_REQUEST['action'] && ($userdata->$blog_id_info == null)) {
		if (!wp_verify_nonce($nonce, 'adduser-nonce'))
			die('Security check failed. Please use the back button and try resubmitting the information.');
		
		if(($options['use_password'] && ($options['password']==$_POST['user_password'])) || (!($options['use_password']== 'yes'))){
			add_user_to_blog($blog_id, $user_id, $options['privilege']);
			do_action( "added_existing_user", $user_id );
			$userAdded = TRUE;
			get_currentuserinfo();
			echo "<p><strong>Successfully Added</strong></p>";
		}
		else {
			echo "<p><strong>" . $options['errortext'] . "</strong></p>";			
		}
	}
	//error checking
	if ( isset($add_user_error) && is_wp_error($add_user_errors) ) {
		foreach ( array('user_login' => 'user_login', 'first_name' => 'user_firstname', 'last_name' => 'user_lastname', 'email' => 'user_email', 'url' => 'user_uri', 'role' => 'user_role') as $formpost => $var ) {
			$var = 'new_' . $var;
			$var = attribute_escape(stripslashes($_POST[$formpost]));
		}
		unset($name);
	}

			//Next three statements define how the user sees the widget depending on what their status is.
	if (isset($userdata->$blog_id_info)) echo '<p>Welcome '. $userdata->$display_name .'.</p>'; //message to display if user is already registered
	elseif (!is_user_logged_in()) echo "<p>If you want to add yourself to this blog, please log in.</p>"; //message to display if user is not logged in
	elseif ((is_user_logged_in())&&(!isset($userdata->$blog_id_info)) && !$userAdded){
		echo '<p>Welcome '. $userdata->$display_name .'.</p>';?>
		<!-- The form that the user clicks on if they want to be added to the blog-->	
		<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" name="sidebar_adduser" id="sidebar_adduser">
			<?php //wp_nonce_field('add-user') ?>
			<?php if($options['use_password']){
				echo '<p>Please enter the password for this blog to register:<br /><input type="password" size="15" name="user_password" /></p>'; 
			}?>
			<input type='hidden' name='action' value='addexistinguser'>
			<p class="submit">
			<?php
			/*
				$referer = '<input type="hidden" name="wp_http_referer" value="'. attribute_escape(stripslashes($_SERVER['REQUEST_URI'])) . '" />';
				echo $referer;
			*/ 
			?>
				<input type="hidden" name="adduser-nonce" value="<?php echo wp_create_nonce('adduser-nonce');?>" />
				<input name="sidebar_adduser" type="submit" id="sidebar_addusersub" value="<?php _e($options['button']) ?>" />
			</p>
		</form>
		<br />		
		<?php wp_loginout();
	}
	return; 
} 
	
function add_user_widget_init() {
 
 	$widget_title = "Add Users";	// Change this to name the widget


	// This is the function that gets the values from sidbar widgets and
	// displays it on the output page.
	function add_user_widget($args) {
	
		// $args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys. Default tags: li and h2.
		extract($args);

		// Each widget can store its own options. We keep strings here.
		$options = get_option('add_user_widget');
$title = empty($options['title']) ? __('Add Users') : $options['title'];
		$begin_wrap = '<li>';
		$end_wrap = '</li>';

		// These lines generate our output.
		echo $before_widget . $before_title . $title . $after_title;
		
add_the_user();

echo $after_widget;

	}
	
	// This is the function that outputs the form in the "Sidbar Widgets" page and
	// to let the users edit the widget's options.
	function add_user_widget_control() {
	
		// Get our options and see if we're handling a form submission.
		$options = get_option('add_user_widget');
		if ( !is_array($options) )
			//initial values
			$options = array('title'=>$widget_title, 'limit'=>'10', 'display'=>'name', 'button'=>'Add Me!', 'list'=>"", 'privilege'=>'subscriber', 'use_password'=>'', 'errortext'=>'Please enter the correct password to join this blog.');
		
		if ( $_POST['sidebar_adduser-submit'] ) {
				
			$nonce= $_REQUEST['adduser-nonce'];
			if (!wp_verify_nonce($nonce, 'adduser-nonce') )
				die('Security check failed. Please use the back button and try resubmitting the information.');

			// Remember to sanitize and format user input appropriately.
			$options['title'] = strip_tags(stripslashes($_POST['sidebar_adduser-title']));
			$options['password'] = strip_tags(stripslashes($_POST['sidebar_adduser_password']));
			$options['button'] = strip_tags(stripslashes($_POST['sidebar_adduser-button']));
			$options['privilege'] = strip_tags(stripslashes($_POST['privilege']));
			$options['errortext'] = strip_tags(stripslashes($_POST['sidebar_adduser-error']));
			$options['use_password'] = strip_tags(stripslashes($_POST['use_password']));
			update_option('add_user_widget', $options);
		}

		// Be sure you format your options to be valid HTML attributes.
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$password = htmlspecialchars($options['password'], ENT_QUOTES);
		$button = htmlspecialchars($options['button'], ENT_QUOTES);
		$privilege = htmlspecialchars($options['privilege'], ENT_QUOTES);
		$use_password = htmlspecialchars($options['use_password'], ENT_QUOTES);
		$errortext = htmlspecialchars($options['errortext'], ENT_QUOTES);
		?>
				<!-- Here is our little form segment. Notice that we don't need a complete form. This will be embedded into the existing form.-->
		<p><label for="sidebar_adduser-title">Title: </label><input style="width: 200px;" id="sidebar_adduser-title" name="sidebar_adduser-title" type="text" value="<?php echo $title; ?>" /></p>
		<p><label for="sidebar_adduser-button">Text for button: </label><input style="width: 200px;" id="sidebar_adduser-button" name="sidebar_adduser-button" type="text" value="<?php echo $button; ?>" /></p>
		<?php
				//Form output for control checkboxes to choose what type of privilege the users will have
		?>
		<input id="radio1" type="radio" name="privilege" value="subscriber"<?php if($options['privilege'] == 'subscriber') echo 'checked'; ?>> <label for="radio1">Subscriber</label><br /> 
		<input id="radio2" type="radio" name="privilege" value="contributor"<?php if($options['privilege'] == 'contributor') echo 'checked'; ?>> <label for="radio2">Contributor</label><br /> 
		<input id="radio3" type="radio" name="privilege" value="author"<?php if($options['privilege'] == 'author') echo 'checked';?>> <label for="radio3">Author</label><br /><br />
		<?php //checkbox allowing the user to choose whether or not they want to include a password in the add-user widget ?>			
		<p>
			<input style="text-align:right;" type="checkbox" name="use_password" id="use_password" value="yes" onChange="jQuery('#add_user_password').toggle();return false;" <?php if($options['use_password'] == 'yes') echo 'checked';?> />
			<label for="use_password">Add a password to stop unwanted users from signing up</label>
		</p>
		<div id="add_user_password" <?php if (!($use_password == 'yes')){?> style="display:none;" <?php }; ?>>
			<p><label for="addUserPassword">Password: </label><input id="addUserPassword" style="width: 200px;" type="text" name="sidebar_adduser_password" value="<?php echo $password;?>"></p>
			<p><label for="sidebar_adduser-error">Error message shown if user has wrong password</label><br /><input style="width: 265px;" id="sidebar_adduser-error" name="sidebar_adduser-error" type="text" value="<?php echo $errortext; ?>" /></p>
		</div>
			<input type="hidden" name="adduser-nonce" value="<?php $printed_nonce = wp_create_nonce('adduser-nonce'); echo $printed_nonce; ?>" />
			<input type="hidden" id="sidebar_adduser-submit" name="sidebar_adduser-submit" value=" " />


		
		<?php

		
	}
	
	// This registers our widget so it appears with the other available
	// widgets and can be dragged and dropped into any active sidebars.
	register_sidebar_widget(array($widget_title, 'widgets'), 'add_user_widget');

	// This registers our optional widget control form.
	register_widget_control(array($widget_title, 'widgets'), 'add_user_widget_control', 300, 700);
	
	//This registers our database addition hook
	register_activation_hook(__FILE__,'jal_install');
}

// Run our code later in case this loads prior to any required plugins.
add_action('widgets_init', 'add_user_widget_init');
 ?>
