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
define("ESPRESSO_MANAGER_VERSION", espresso_manager_pro_version() );

//Define the plugin directory and path
define("ESPRESSO_MANAGER_PLUGINPATH", "/" . plugin_basename( dirname(__FILE__) ) . "/");
define("ESPRESSO_MANAGER_PLUGINFULLPATH", WP_PLUGIN_DIR . ESPRESSO_MANAGER_PLUGINPATH  );
define("ESPRESSO_MANAGER_PLUGINFULLURL", WP_PLUGIN_URL . ESPRESSO_MANAGER_PLUGINPATH );

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

//Returns information about the current roles
if (!function_exists('espresso_role_data')) {
	function espresso_role_data($type){
		global $wpdb;
		$sql = "SELECT
		ID, user_email, user_login,
		first_name.meta_value as first_name,
		last_name.meta_value as last_name,
		phone_number.meta_value as phone_number,
		wp_capabilities.meta_value as wp_capabilities ";
		$sql .= " FROM wp_users
			JOIN wp_usermeta AS wp_capabilities ON wp_capabilities.user_id=ID
				AND wp_capabilities.meta_key='wp_capabilities'
			LEFT JOIN wp_usermeta AS first_name ON first_name.user_id=ID
				AND first_name.meta_key='first_name'
			LEFT JOIN wp_usermeta AS last_name ON last_name.user_id=ID
				AND last_name.meta_key='last_name'
			LEFT JOIN wp_usermeta AS phone_number ON phone_number.user_id=ID
				AND phone_number.meta_key='phone_number' ";
		$sql .= " WHERE ";
		//$sql .= " wp_capabilities.meta_value LIKE '%administrator%' OR wp_capabilities.meta_value LIKE '%espresso_event_admin%' OR wp_capabilities.meta_value LIKE '%espresso_event_manager%' ";
		//$sql .= " ORDER BY ID";
	
		switch($type){
			case 'admin_count':
				$sql .= " wp_capabilities.meta_value LIKE '%administrator%' ";
				$wpdb->get_results($sql);
				return $wpdb->num_rows;
			break;
			case 'event_manager_count':
				$sql .= " wp_capabilities.meta_value LIKE '%espresso_event_manager%' ";
				$wpdb->get_results($sql);
				return $wpdb->num_rows;
			break;
			case 'event_admin_count':
				$sql .= " wp_capabilities.meta_value LIKE '%espresso_event_admin%' ";
				$wpdb->get_results($sql);
				return $wpdb->num_rows;
			case 'event_group_count':
				$sql .= " wp_capabilities.meta_value LIKE '%espresso_group_admin%' ";
				$wpdb->get_results($sql);
				return $wpdb->num_rows;
			break;
		}
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
