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
	return '2.0.1';
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
		}
	}
}

function espresso_select_manager_form(){
	espresso_load_selected_manager();
?>
<a name="selected_user" id="selected_user"></a>
<div class="postbox">
	<h3>
		<?php _e('Login As User', 'event_espresso'); ?>
	</h3>
	<div class="inside">
		<form method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
			<ul>
			<li><?php _e('This section allows you to login as any event manager, regional manager or master admin.', 'event_espresso'); ?></li>
				<?php 
				if (espresso_get_selected_manager() == true){ ?>
				<li>
					<span class="highlight"><label><strong><?php _e('Currently Logged-in As User ID:', 'event_espresso'); ?> <?php echo espresso_get_selected_manager(); ?></strong></label><br />
					 <input name="deactivate_user" type="checkbox" value="1" /> <?php _e('Logout of current user?', 'event_espresso'); ?></span></li>
				<?php 
					$manager_loaded = true;	
				}?>
				<li>
					<label for="event_manager_id">
						<?php _e('User Id:', 'event_espresso'); ?>
					</label>
					<br />
					<input type="text" name="event_manager_id" size="10" value="" />
				</li>
				<li>
					<input class="button-primary" type="submit" name="Submit" value="<?php $manager_loaded == true ?  _e('Unload/','event_espresso'): ''; _e('Load Manager','event_espresso'); ?>" id="load_manager" />
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

function espresso_show_user_loaded_notice(){
	global $notices, $current_user, $espresso_wp_user;
	if ($_SESSION['espresso_use_selected_manager'] == false)
		return false;
			
	if ($current_user->ID != $espresso_wp_user){
		$notices['updates'][] = __('User Loaded:', 'event_espresso'). ' ' . $espresso_wp_user . ' <a href="admin.php?page=espresso_permissions#selected_user">' . __('Deactivate?', 'event_espresso') . '</a>';
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
			<?php echo select_input('event_manager_approval', $values, $espresso_manager['event_manager_approval']);?></p>
		<p>
			<?php _e('Regional managers can edit venues assigned to them?', 'event_espresso'); ?>
			<?php echo select_input('event_manager_venue', $values, $espresso_manager['event_manager_venue']);?></p>
		<p>
			<?php _e('Regional managers are in charge only of their staff?', 'event_espresso'); ?>
			<?php echo select_input('event_manager_staff', $values, $espresso_manager['event_manager_staff']);?></p>
		<p>
			<?php _e('Anyone can create a post when publishing an event?', 'event_espresso'); ?>
			<?php echo select_input('event_manager_create_post', $values, $espresso_manager['event_manager_create_post']);?></p>
		<p>
			<?php _e('Enable sharing of categories between users?', 'event_espresso'); ?>
			<?php echo select_input('event_manager_share_cats', $values, $espresso_manager['event_manager_share_cats']);?></p>
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



