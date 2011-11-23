<?php
/*
Plugin Name: Event Espresso - Roles and Permissions Pro
Plugin URI: http://www.eventespresso.com
Description: Provides support for allowing members of the espreesso_event_admin and espreesso_event_manager roles to administer events.
Version: 2.0.2
Author: Event Espresso
Author URI: http://www.eventespresso.com
Copyright 2011  Event Espresso  (email : support@eventespresso.com)

*/

//Define the version of the plugin
function espresso_manager_pro_version() {
	return '2.0.2';
}
define("ESPRESSO_MANAGER_PRO_VERSION", espresso_manager_pro_version() );

//Globals
global $espresso_manager;
$espresso_manager = get_option('espresso_manager_settings');

//Install the plugin
function espresso_manager_pro_install(){
	// add more capabilities
	
	//Regional Manager role
	$result = add_role('espresso_group_admin', 'Espresso Regional Manager', array(
		'read' => true, // True allows that capability
		'edit_posts' => false,
		'espresso_group_admin' => true,
		'espresso_event_admin' => true,
		'espresso_event_manager' => true,
		'delete_posts' => false, // Use false to explicitly deny
	));
	
	//Event Manager role
	$result = add_role('espresso_event_manager', 'Espresso Event Manager', array(
		'read' => true, // True allows that capability
		'edit_posts' => false,
		'espresso_group_admin' => false,
		'espresso_event_admin' => false,
		'espresso_event_manager' => true,
		'delete_posts' => false, // Use false to explicitly deny
	));
}
register_activation_hook(__FILE__,'espresso_manager_pro_install');

//Checks to see if a user has permissions to view an event.
if (!function_exists('espresso_can_view_event')) {
	function espresso_can_view_event($event_id){
	   if( espresso_member_data('role')=='espresso_group_admin' ){
		   if( espresso_do_i_manage($event_id) ){
			   return true;
		   }
	   }else{
		   if ( current_user_can('espresso_event_admin')==true || espresso_is_my_event($event_id) ){
			   return true;
		   }
	   }
	}
}

//Checks to see if a user is an admin
if (!function_exists('espresso_is_admin')) {
	function espresso_is_admin(){
		if( espresso_member_data('role')=='espresso_group_admin' || espresso_member_data('role')=='espresso_event_admin' || current_user_can('administrator') ){
			return true;
		}else{
			return false;
		}
	}
}

function espresso_select_manager_form(){
	espresso_load_selected_manager();
?>
<a name="selected_user" id="selected_user"></a>
<div class="postbox">
	<h3>
		<?php _e('Login as a User', 'event_espresso'); ?>
	</h3>
	<div class="inside">
		<form method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
			<ul>
			<li><?php _e('This section allows you to login as any event manager, regional manager or master admin.', 'event_espresso'); ?></li>
				<?php 
				if (espresso_get_selected_manager() == true){
					$user = new WP_User( espresso_get_selected_manager() );
				?>
				<li>
					<span class="highlight"><label><?php echo '<strong style="color:red">'. __('You are logged-in as:', 'event_espresso') . '</strong> <a href="user-edit.php?user_id='.$user->ID.'" target="_blank">' .  $user->display_name . ' (' . $user->user_email . ')</a>'; ?></label></span><br />
					 <input name="deactivate_user" type="checkbox" value="1" /> <?php _e('Logout of current user?', 'event_espresso'); ?></li>
				<?php 
					$manager_loaded = true;	
				}?>
				<li>
					<label for="event_manager_id">
						<?php _e('User Id:', 'event_espresso'); ?> <?php apply_filters( 'espresso_help', 'login_as_user');?>
					</label>
					<br />
					<input type="text" name="event_manager_id" size="10" value="" />
				</li>
				<li>
					<input class="button-primary" type="submit" name="Submit" value="<?php $manager_loaded == true ?  _e('Logout/','event_espresso'): ''; _e('Login Manager','event_espresso'); ?>" id="load_manager" />
				</li>
			</ul>
		</form>
	</div>
</div>
<?php
	if ( did_action( 'espresso_admin_notices' ) == false )
		do_action('espresso_admin_notices');
}

//Loads selected manager data
function espresso_load_selected_manager(){
	global $notices;
	
	//Get the user id
	$user_id = isset($_POST['event_manager_id']) ? $_POST['event_manager_id'] : '';
	
	//Deactivate the loaded user information
	if ($_POST['deactivate_user']){
		$_SESSION['espresso_use_selected_manager'] = 0;
		$_SESSION['espresso_selected_manager'] = 0;
		return false;
	}
	
	//If no user id, then exit.
	if (empty($user_id))
		return false;
	
	//Make sure the id exists and the user is one of the roles below.
	$user = new WP_User( $user_id );
	if ($user->has_cap('espresso_event_manager') || $user->has_cap('espresso_group_admin') || $user->has_cap('espresso_event_admin') || $user->has_cap('administrator')){
		//Load the manager
		$_SESSION['espresso_use_selected_manager'] = true;
		$_SESSION['espresso_selected_manager'] = $user_id;
		
		//Display update message
		$notices['updates'][] = __('User has been loaded.', 'event_espresso').$wp_user;
		do_action('espresso_admin_notices');
		return true;
	}else{
		//Unload current manager
		$_SESSION['espresso_use_selected_manager'] = 0;
		$_SESSION['espresso_selected_manager'] = 0;
		
		//Display error message
		$notices['errors'][] = __('That user is not an event manager/admin.', 'event_espresso').$wp_user;
		do_action('espresso_admin_notices');
		return false;
	}
}

//This function shows a notice that you are logged ina  someone else.
function espresso_show_user_loaded_notice(){
	global $notices, $current_user, $espresso_wp_user;
	if ($_SESSION['espresso_use_selected_manager'] == false)
		return false;
			
	if ($current_user->ID != $espresso_wp_user){
		$user = new WP_User( $espresso_wp_user );
		$notices['updates'][] = '<strong style="color:red">'. __('You are logged-in as:', 'event_espresso') . '</strong> <a href="user-edit.php?user_id='.$user->ID.'" target="_blank">' .  $user->display_name . ' (' . $user->user_email . ')</a> [<a href="admin.php?page=espresso_permissions#selected_user" target="_blank">' . __('Exit User', 'event_espresso') . '</a>]';
		return true;
	}
}
add_action('espresso_admin_notices', 'espresso_show_user_loaded_notice');

//Gets the selected manager
function espresso_get_selected_manager(){
	if ($_SESSION['espresso_use_selected_manager'] == false)
		return false;
	return $_SESSION['espresso_selected_manager'];
}

//Checks to see if this is the users event
if (!function_exists('espresso_is_my_event')) {
	function espresso_is_my_event($event_id){
	  global $wpdb;
	  if( current_user_can('administrator') || espresso_member_data('role')=='espresso_event_admin'){
		  return true;
	  }elseif( espresso_member_data('role')=='espresso_group_admin' ){
		  return ( espresso_do_i_manage($event_id) ? true : false);
	  }else{
		  $sql = "SELECT e.wp_user ";
		  $sql .= " FROM ". EVENTS_DETAIL_TABLE ." e ";
		  $sql .= " WHERE e.id = '$event_id' ";
		  $wpdb->get_results($sql);
		  if ($wpdb->num_rows >0)  return (espresso_member_data('id') == $wpdb->last_result[0]->wp_user ? true : false);
	  }
	}
}

function espresso_do_i_manage($event_id){
	global $wpdb, $org_options,$current_user;
	wp_get_current_user();
	$group = get_user_meta(espresso_member_data('id'), "espresso_group", true);
	$group = unserialize($group);
	if( espresso_member_data('role')=='espresso_group_admin' ){
		$sql = "(SELECT e.wp_user ";
		$sql .= " FROM ". EVENTS_DETAIL_TABLE ." e ";
		if ($group !=''){
			$sql .= " JOIN " . EVENTS_VENUE_REL_TABLE . " r ON r.event_id = e.id ";
			$sql .= " JOIN " . EVENTS_LOCALE_REL_TABLE . " l ON  l.venue_id = r.venue_id ";
		}
		$sql .= " WHERE  e.id = '$event_id'";
		$sql .= $group !='' ? " AND l.locale_id IN (" . implode(",",$group) . ") " : '';
		$sql .= ") UNION (";
		$sql .= "SELECT e.wp_user ";
		$sql .= " FROM ". EVENTS_DETAIL_TABLE ." e ";
		$sql .= " WHERE  e.id = '$event_id'";
		$sql .= " AND wp_user = '" . espresso_member_data('id') ."'";
		$sql .= ")";
		$wpdb->get_results($sql);
		if ($wpdb->num_rows >0)	return $wpdb->last_result[0]->wp_user;
	}
}

//Build the  regional maanger page
function espresso_permissions_user_groups(){
	global $wpdb, $espresso_manager, $wp_roles;
?>
<div id="configure_espresso_manager_form" class="wrap meta-box-sortables ui-sortable">
	<div id="icon-options-event" class="icon32"> </div>
	<h2>
		<?php _e('Event Espresso - Regional Managers','event_espresso'); ?>
	</h2>
	<div id="event_espresso-col-left" style="width:70%;">
		<?php espresso_edit_groups_page(); ?>
	</div>
</div>
<?php
}

function espresso_manager_pro_options(){
	require_once( 'includes/pro_help.php' );
	global $espresso_manager;
	$values=array(
		array('id'=>'N','text'=> __('No','event_espresso')),
		array('id'=>'Y','text'=> __('Yes','event_espresso')),
	);
?>
<div class="postbox">
	<h3>
		<?php _e('Advanced Options', 'event_espresso'); ?>
	</h3>
	<div class="inside">
		<p>
			<?php _e('Events created by "Event Managers" require approval?', 'event_espresso'); ?>
			<?php echo select_input('event_manager_approval', $values, $espresso_manager['event_manager_approval']);?> <?php apply_filters( 'espresso_help', 'event_manager_approval');?></p>
		<p>
			<?php _e('Regional managers can edit venues assigned to them?', 'event_espresso'); ?>
			<?php echo select_input('event_manager_venue', $values, $espresso_manager['event_manager_venue']);?> <?php apply_filters( 'espresso_help', 'event_manager_venue');?></p>
		<?php
		//I can't remember what this is for and it doesn't seem to have any settings anywhere. So I am disabling it for now. I think it was added for SMW??
		 /*?><p>
			<?php _e('Regional managers are in charge only of their staff?', 'event_espresso'); ?>
			<?php echo select_input('event_manager_staff', $values, $espresso_manager['event_manager_staff']);?> <?php apply_filters( 'espresso_help', 'event_manager_create_post');?></p>
		<p><?php */?>
			<?php _e('Anyone can create a post when publishing an event?', 'event_espresso'); ?>
			<?php echo select_input('event_manager_create_post', $values, $espresso_manager['event_manager_create_post']);?> <?php apply_filters( 'espresso_help', 'event_manager_create_post');?></p>
		<p>
			<?php _e('Enable sharing of categories between users?', 'event_espresso'); ?>
			<?php echo select_input('event_manager_share_cats', $values, $espresso_manager['event_manager_share_cats']);?> <?php apply_filters( 'espresso_help', 'event_manager_share_cats');?></p>
		<p>
		  <?php _e('Managers can accept payments for their events?', 'event_espresso'); ?>
		  <?php echo select_input('can_accept_payments', $values, $espresso_manager['can_accept_payments']);?> <?php apply_filters( 'espresso_help', 'can_accept_payments');?></p> 
	</div>
</div>
<?php
}

function espresso_edit_groups_page(){
	require_once( 'includes/groups.php' );
}

if (!function_exists('espresso_manager_list')) {

	function espresso_manager_list($current_value=0) {
		global $espresso_premium;
		if ($espresso_premium != true)
			return;
		//global $wpdb, $espresso_manager, $current_user;
		 
		// Get all event users users
		$blogusers = get_users();
		//print_r($blogusers);
		
		// If there are any users
		if ($blogusers) {
			$field = '<label>' . __('Select an Event Admin or Manager', 'event_espresso') . '</label>';
			$field .= '<select name="wp_user[]" id="wp_user" style="width:240px">';
			$field .= '<option value="0">' . __('Select a User', 'event_espresso') . '</option>';
			$div = "";
			$help_div = "";
			$i = 0;
			foreach ($blogusers as $bloguser) {
	 			//echo $bloguser->ID.'<br />';
				// Get user info
				$user = new WP_User($bloguser->ID);
				//echo $current_value;
			   
				if ($user->has_cap('espresso_event_manager') || $user->has_cap('espresso_group_admin') || $user->has_cap('espresso_event_admin') || $user->has_cap('administrator') ) {
					$i++;
					$selected = $user->ID == $current_value ? 'selected="selected"' : '';
					if ($user->first_name) { 
						$user_name = $user->first_name;
						$user_name .= $user->last_name ? ' ' . $user->last_name:'' ;
					}else{
						$user_name = $user->user_nicename;
					}
					$field .= '<option rel="' . $i . '" ' . $selected . ' value="' . $user->ID . '">'.$user_name.' (' . $user->user_login . ') </option>';
					
					
					$hidden = "display:none;";
					if ($selected)
						$hidden = '';
					//$div .= "<br />";
					$div .= "<fieldset id='eebox_user_" . $i . "' class='eebox_user' style='" . $hidden . "'>";
					$div .= "<hr />";
					$div .= "<ul class='user-view'>";
					$div .= '<li><div style="float:right">'.get_avatar($user->user_email, $size = '48').'</div><strong>Display Name:</strong> <a href="user-edit.php?user_id='.$user->ID.'">'.$user->display_name.'</a>';
					if ($user->first_name) { 
						$div .= '<li><strong>'.__('Full Name:', 'event_espresso').'</strong> ' . $user->first_name;
						if ($user->last_name) { $div .= ' '.$user->last_name; }
						$div .= "<li>";
					}
					$div .= '<li><strong>' . __('Username:', 'event_espresso') . '</strong> ' . $user->user_login . '</li>';
					$div .= '<li><strong>' . __('Email:', 'event_espresso') . '</strong> ' . $user->user_email . '</li>';
					$div .= "</ul>";
					$div .= "<hr />";
					$div .= "</fieldset>";
					
					/*// display avatar (48px square)
					echo get_avatar($user->user_email, $size = '48');
	 
					// output other user data, if populated
					echo('Display Name: <a href=\"'.$user->user_url."\">".$user->display_name."</a><br />\n");
					echo('Username: ' . $user->user_login . "<br />\n");
					if ($user->user_nicename) { echo('User Nice Name: ' . $user->user_nicename . "<br />\n"); }
					if ($user->user_email) { echo('User e-mail: ' . $user->user_email . "<br />\n"); }
					if ($user->first_name) { echo('First Name: ' . $user->first_name . "&nbsp;"); }
					if ($user->last_name) { echo($user->last_name . "<br />\n"); }
					if ($user->nickname) { echo('Nickname: ' . $user->nickname . "<br />\n"); }
					if ($user->description) { echo('Bio: ' . $user->description . "<br />\n"); }
					echo "<hr />";*/
				}
			}
			$field .= "</select>";
			ob_start();
				?>
<script>
					jQuery("#wp_user").change( function(){
						var selected = jQuery("#wp_user option:selected");
						var rel = selected.attr("rel");
						jQuery(".eebox_user").hide();
						jQuery("#eebox_user_"+rel).show();
					});
				</script>
<?php
				$js = ob_get_contents();
				ob_end_clean();
				$html = '<table><tr><td>' . $field . '</td></tr><tr><td>' . $div . '</td></tr></table>' . $js;
				return $html;
		}
	}
}

add_action( 'personal_options_update', 'espresso_check_default_questions' );
add_action( 'edit_user_profile_update', 'espresso_check_default_questions' );

function espresso_check_default_questions( $user_id ) {
	global $wpdb;

	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;

    $role = $_POST['role'];

    if (substr($role, 0, 9) == "espresso_") { // this covers any espresso roles
        // since this is an espresso role, let's check to see if there are any questions assigned to this user
        $questions = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix . "events_question WHERE wp_user=".$user_id));

        if (sizeof($questions) == 0) {
            // no questions, which hopefully means that somehow things went wrong and we need to add the defaults
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'First Name', 'question_type' => 'TEXT', 'system_name' => 'fname', 'required' => 'Y', 'sequence' => '0'), array('%s', '%s', '%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Last Name', 'question_type' => 'TEXT', 'system_name' => 'lname', 'required' => 'Y', 'sequence' => '1'), array('%s', '%s', '%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Email', 'question_type' => 'TEXT', 'system_name' => 'email', 'required' => 'Y', 'sequence' => '2'), array('%s', '%s', '%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Address', 'system_name' => 'address', 'sequence' => '3'), array('%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Address 2', 'system_name' => 'address2', 'sequence' => '3'), array('%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'City', 'system_name' => 'city', 'sequence' => '4'), array('%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'State', 'system_name' => 'state', 'sequence' => '5'), array('%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Zip', 'system_name' => 'zip', 'sequence' => '6'), array('%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Country', 'system_name' => 'country', 'sequence' => '6'), array('%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Phone', 'system_name' => 'phone', 'sequence' => '7'), array('%s', '%s', '%s') );
            
        }
			$system_group = $wpdb->get_row("SELECT system_group FROM ". $wpdb->prefix . "events_qst_group" . " WHERE system_group = 1 AND wp_user='".$user_id."' ");
	
			if ($wpdb->num_rows == 0){
	
				/**
				 * Add new groups, find id, assign the system questions to the group
				 */
	
				$wpdb->insert( $wpdb->prefix . "events_qst_group", array('wp_user' => $user_id, 'group_name' => 'Personal Information', 'group_identifier'=>'personal_information-'.time(), 'system_group' => 1, 'group_order' => 1), array('%d','%s', '%s', '%d', '%d') );
				
				$personal_group_id = $wpdb->insert_id;
				
				$wpdb->insert( $wpdb->prefix . "events_qst_group", array('wp_user' => $user_id, 'group_name' => 'Address Information', 'group_identifier'=>'address_information-'.time(), 'system_group' => 0, 'group_order' => 2), array('%d','%s', '%s', '%d', '%d') );
				
				$address_group_id = $wpdb->insert_id;				
	
				/**
				* Personal Information System Group
				* Find fname, lname, and email ids.  At this point, they will be in the system group.
				*/
				$system_name_data = "SELECT id, system_name FROM " . $wpdb->prefix . "events_question" . " WHERE system_name IN ('fname', 'lname', 'email') AND wp_user='".$user_id."' ";
				$system_names = $wpdb->get_results($system_name_data);
				foreach ($system_names as $system_name){
					$wpdb->insert( $wpdb->prefix . "events_qst_group_rel", array('group_id' => $personal_group_id, 'question_id' => $system_name->id), array('%d', '%d') );
				}
						
				/**
				* Address Group
				* Find address, city, state, and zip ids.
				*/
				$system_name_data = "SELECT id, system_name FROM " . $wpdb->prefix . "events_question" . " where system_name IN ('address', 'city', 'state', 'zip' ) AND wp_user='".$user_id."' ";
				$system_names = $wpdb->get_results($system_name_data);
				foreach ($system_names as $system_name){
					$wpdb->insert( $wpdb->prefix . "events_qst_group_rel", array('group_id' => $address_group_id, 'question_id' => $system_name->id), array('%d', '%d') );
				}
			}
    }    
} // function espresso_check_default_questions


add_action('user_register','espresso_add_default_questions');

function espresso_add_default_questions($user_id){
  	global $wpdb;
    $role = $_POST['role'];

    if (substr($role, 0, 9) == "espresso_") { // this covers any espresso roles
        // since this is an espresso role, let's check to see if there are any questions assigned to this user
        $questions = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix . "events_question WHERE wp_user=".$user_id));

        if (sizeof($questions) == 0) {
            // no questions found, which is what we should be seeing, since this is a new user
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'First Name', 'question_type' => 'TEXT', 'system_name' => 'fname', 'required' => 'Y', 'sequence' => '0'), array('%s', '%s', '%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Last Name', 'question_type' => 'TEXT', 'system_name' => 'lname', 'required' => 'Y', 'sequence' => '1'), array('%s', '%s', '%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Email', 'question_type' => 'TEXT', 'system_name' => 'email', 'required' => 'Y', 'sequence' => '2'), array('%s', '%s', '%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Address', 'system_name' => 'address', 'sequence' => '3'), array('%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Address 2', 'system_name' => 'address2', 'sequence' => '3'), array('%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'City', 'system_name' => 'city', 'sequence' => '4'), array('%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'State', 'system_name' => 'state', 'sequence' => '5'), array('%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Zip', 'system_name' => 'zip', 'sequence' => '6'), array('%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Country', 'system_name' => 'country', 'sequence' => '6'), array('%s', '%s', '%s') );
			$wpdb->insert( $wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Phone', 'system_name' => 'phone', 'sequence' => '7'), array('%s', '%s', '%s') );

        }
		
		$system_group = $wpdb->get_row("SELECT system_group FROM ". $wpdb->prefix . "events_qst_group" . " WHERE system_group = 1 AND wp_user='".$user_id."' ");
	
			if ($wpdb->num_rows == 0){
	
				/**
				 * Add new groups, find id, assign the system questions to the group
				 */
	
				$wpdb->insert( $wpdb->prefix . "events_qst_group", array('wp_user' => $user_id, 'group_name' => 'Personal Information', 'group_identifier'=>'personal_information-'.time(), 'system_group' => 1, 'group_order' => 1), array('%d','%s', '%s', '%d', '%d') );
				
				$personal_group_id = $wpdb->insert_id;
				
				$wpdb->insert( $wpdb->prefix . "events_qst_group", array('wp_user' => $user_id, 'group_name' => 'Address Information', 'group_identifier'=>'address_information-'.time(), 'system_group' => 0, 'group_order' => 2), array('%d','%s', '%s', '%d', '%d') );
				
				$address_group_id = $wpdb->insert_id;				
	
				/**
				* Personal Information System Group
				* Find fname, lname, and email ids.  At this point, they will be in the system group.
				*/
				$system_name_data = "SELECT id, system_name FROM " . $wpdb->prefix . "events_question" . " WHERE system_name IN ('fname', 'lname', 'email') AND wp_user='".$user_id."' ";
				$system_names = $wpdb->get_results($system_name_data);
				foreach ($system_names as $system_name){
					$wpdb->insert( $wpdb->prefix . "events_qst_group_rel", array('group_id' => $personal_group_id, 'question_id' => $system_name->id), array('%d', '%d') );
				}
						
				/**
				* Address Group
				* Find address, city, state, and zip ids.
				*/
				$system_name_data = "SELECT id, system_name FROM " . $wpdb->prefix . "events_question" . " where system_name IN ('address', 'city', 'state', 'zip' ) AND wp_user='".$user_id."' ";
				$system_names = $wpdb->get_results($system_name_data);
				foreach ($system_names as $system_name){
					$wpdb->insert( $wpdb->prefix . "events_qst_group_rel", array('group_id' => $address_group_id, 'question_id' => $system_name->id), array('%d', '%d') );
				}
			}
    }

} // function espresso_add_default_questions


