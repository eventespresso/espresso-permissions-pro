<?php
/*
Plugin Name: Event Espresso - Roles and Permissions Pro
Plugin URI: http://www.eventespresso.com
Description: Provides support for allowing members of the espreesso_event_admin and espreesso_event_manager roles to administer events.
Version: 2.0.4-beta
Author: Event Espresso
Author URI: http://www.eventespresso.com
Copyright 2011  Event Espresso  (email : support@eventespresso.com)

*/

//Update notifications
add_action('action_hook_espresso_permissions_pro_update_api', 'ee_permissions_pro_load_pue_update');
function ee_permissions_pro_load_pue_update() {
	global $org_options, $espresso_check_for_updates;
	if ( $espresso_check_for_updates == false )
		return;
		
	if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'class/pue/pue-client.php')) { //include the file 
		require(EVENT_ESPRESSO_PLUGINFULLPATH . 'class/pue/pue-client.php' );
		$api_key = $org_options['site_license_key'];
		$host_server_url = 'http://eventespresso.com';
		$plugin_slug = 'espresso-permissions-pro';
		$options = array(
			'apikey' => $api_key,
			'lang_domain' => 'event_espresso',
			'checkPeriod' => '24',
			'option_key' => 'site_license_key',
			'options_page_slug' => 'event_espresso'
		);
		$check_for_updates = new PluginUpdateEngineChecker($host_server_url, $plugin_slug, $options); //initiate the class and start the plugin update engine!
	}
}
//Define the version of the plugin
function espresso_manager_pro_version() {
	return '2.0.3-beta';
}


define("ESPRESSO_MANAGER_PRO_VERSION", espresso_manager_pro_version() );

//Define the plugin directory and path
define("ESPRESSO_MANAGER_PRO_PLUGINPATH", "/" . plugin_basename( dirname(__FILE__) ) . "/");
define("ESPRESSO_MANAGER_PRO_PLUGINFULLPATH", WP_PLUGIN_DIR . ESPRESSO_MANAGER_PLUGINPATH  );
define("ESPRESSO_MANAGER_PRO_PLUGINFULLURL", WP_PLUGIN_URL . ESPRESSO_MANAGER_PLUGINPATH );


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

	//make sure that questions get duplicated for existing users that have master_admin privileges.
	$event_admin_users = get_users('role=espresso_event_admin');
	$administrator_users = get_users('role=administrator');

	foreach ( $event_admin_users as $e_user ) {
		espresso_add_default_questions($e_user->ID, 'espresso_event_admin');
	}

	foreach ( $administrator_users as $a_user ) {
		espresso_add_default_questions($a_user->ID, 'administrator');
	}
}
register_activation_hook(__FILE__,'espresso_manager_pro_install');


	function espresso_do_i_manage_event($event_id){
		global $wpdb, $org_options,$current_user;
		wp_get_current_user();
		$group = get_user_meta(espresso_member_data('id'), "espresso_group", true);
		$group = unserialize($group);
		if( espresso_member_data('role')=='espresso_group_admin' ){
			$sql = "(SELECT e.wp_user ";
			$sql .= " FROM ". EVENTS_DETAIL_TABLE ." e ";
			if (!empty($group)){
				$sql .= " JOIN " . EVENTS_VENUE_REL_TABLE . " r ON r.event_id = e.id ";
				$sql .= " JOIN " . EVENTS_LOCALE_REL_TABLE . " l ON  l.venue_id = r.venue_id ";
			}
			$sql .= " WHERE  e.id = '$event_id'";
			$sql .= !empty($group) ? " AND l.locale_id IN (" . implode(",",$group) . ") " : '';
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
	
//Checks to see if a user has permissions to view an event.
if (!function_exists('espresso_can_view_event')) {
	function espresso_can_view_event($event_id){
	   if( espresso_member_data('role')=='espresso_group_admin' ){
		   if( espresso_do_i_manage_event($event_id) ){
			   return true;
		   }
	   }else{
		   if ( current_user_can('espresso_event_admin')==true || espresso_is_my_event($event_id) ){
			   return true;
		   }
	   }
	}
}

add_action('action_hook_espresso_permissions', 'espresso_permissions_pro_run',10);
// IMPORTANT: added the bellow add_action in init because the above add action doesn't work. Because, espresso core loads before this
// and the do_action call fails because this add_action wasn't loaded.
if ( !function_exists('espresso_is_admin')) {
    add_action('init','espresso_permissions_pro_run',10);
}
function espresso_permissions_pro_run(){ 
	//Checks to see if a user is an admin 
	if (!function_exists('espresso_is_admin')) {
		function espresso_is_admin(){
			global $current_user;
			if( current_user_can('espresso_group_admin') || current_user_can('espresso_event_admin') || current_user_can('administrator') ){
				return true;
			}
			return false;
		}
	}
	
	//Checks to see if this is the users event
	if (!function_exists('espresso_check_user_level')) {
		function espresso_check_user_level(){
			if ( espresso_member_data('role')=='espresso_event_manager' ){
				return 1;
			}
			
			if ( espresso_member_data('role')=='espresso_group_admin' ){
				return 2;
			}
			
			if ( espresso_member_data('role')=='espresso_event_admin' ){
				return 3;
			}
			
			if ( espresso_member_data('role')=='administrator' ){
				return 4;
			}
			
			return 0;
		}
	}
			
			
	//Checks to see if this is the users event
	if (!function_exists('espresso_is_my_event')) {
		function espresso_is_my_event($event_id){
		  global $wpdb;
		  if( current_user_can('administrator') || espresso_member_data('role')=='espresso_event_admin'){
			  return true;
		  }elseif( espresso_member_data('role')=='espresso_group_admin' ){
			  return ( espresso_do_i_manage_event($event_id) ? true : false);
		  }else{
              
			  $sql = "SELECT e.wp_user ";
			  $sql .= " FROM ". EVENTS_DETAIL_TABLE ." e ";
			  $sql .= " WHERE e.id = '$event_id' ";
			  $wpdb->get_results($sql);
			  if ($wpdb->num_rows >0)  return (espresso_member_data('id') == $wpdb->last_result[0]->wp_user ? true : false);
		  }
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
		
		<?php if ( function_exists('espresso_event_submission_version') ){
				$min_levels = array(
					array('id'=>'0','text'=> __('All Users','event_espresso')),
					array('id'=>'1','text'=> __('Espresso Event Manager','event_espresso')),
					array('id'=>'2','text'=> __('Espresso Regional Manager','event_espresso')),
					array('id'=>'3','text'=> __('Espresso Master Admin','event_espresso')),
					array('id'=>'4','text'=> __('Administrator','event_espresso')),
				);	
		?>
				<p>
				  <?php _e('Minimum level required to use the Front Event Manager page?', 'event_espresso'); ?>
				  <?php echo select_input('minimum_fes_level', $min_levels, $espresso_manager['minimum_fes_level']);?></p>
		<?php }?>
      </div>
    </div>
<?php
}

/** ADDED BY DARREN **/
/**
 * adding in filters for questions displayed on edit groups pages.
 */

//add filters for pro
add_filter('espresso_get_user_questions_for_group', 'espresso_rp_pro_get_user_questions_for_group', 15, 3);
add_filter('espresso_get_user_questions_for_group_extra_attached','espresso_rp_pro_get_user_questions_for_group', 15, 3);
add_filter('espresso_get_user_questions_where', 'espresso_rp_pro_get_user_questions_where', 15, 3);

function espresso_rp_pro_get_user_questions_for_group( $where, $group_id, $user_id ) {
	$where = " WHERE q.wp_user = '" . $user_id . "'";
	return $where;
}

function espresso_rp_pro_get_user_questions_where( $where, $user_id, $num ) {
	$modified_where = " WHERE qg.wp_user = '" . $user_id . "' ";

	if ( espresso_is_admin() && !$num ) {
		$where = !isset($_REQUEST['all']) ? $modified_where : "";
	} else {
		$where = $modified_where;
	}

	return $where;
}

//also to account for users who don't have questions duplicated for them when pro is activated.  IF there are NO questions pulled from the database when creating or editing a group (so we need to check insert group page as well) then we need to hook into the after questions query so that we can duplicate all system questions (save them to the db for that user) and then display THEM for the user editing adding the group... should happen transparently.  OR BETTER YET it may be a good idea to just make sure all existing users on activation get the duplicated questions/groups.

function espresso_edit_groups_page(){
	require_once( 'includes/groups.php' );
}

function espresso_add_default_questions($user_id, $_role = false) {
	global $wpdb;
	$role = $_role ? $_role : $_POST['role'];

	if (substr($role, 0, 9) == "espresso_" || $role == 'administrator' ) { // this covers any espresso roles
		// since this is an espresso role, let's check to see if there are any questions assigned to this user
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'events_question WHERE wp_user = "' . $user_id . '" AND (system_name = "fname" OR system_name = "lname" OR system_name = "email")';
		$questions = $wpdb->get_results($wpdb->prepare($sql));

		if (sizeof($questions) == 0) {
			// no questions found, then insert the default questions
			$wpdb->insert($wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'First Name', 'question_type' => 'TEXT', 'system_name' => 'fname', 'required' => 'Y', 'sequence' => '0'), array('%s', '%s', '%s', '%s', '%s'));
			$wpdb->insert($wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Last Name', 'question_type' => 'TEXT', 'system_name' => 'lname', 'required' => 'Y', 'sequence' => '1'), array('%s', '%s', '%s', '%s', '%s'));
			$wpdb->insert($wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Email', 'question_type' => 'TEXT', 'system_name' => 'email', 'required' => 'Y', 'sequence' => '2'), array('%s', '%s', '%s', '%s', '%s'));
			$wpdb->insert($wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Address', 'system_name' => 'address', 'sequence' => '3'), array('%s', '%s', '%s'));
			$wpdb->insert($wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Address 2', 'system_name' => 'address2', 'sequence' => '3'), array('%s', '%s', '%s'));
			$wpdb->insert($wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'City', 'system_name' => 'city', 'sequence' => '4'), array('%s', '%s', '%s'));
			$wpdb->insert($wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'State', 'system_name' => 'state', 'sequence' => '5'), array('%s', '%s', '%s'));
			$wpdb->insert($wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Zip', 'system_name' => 'zip', 'sequence' => '6'), array('%s', '%s', '%s'));
			$wpdb->insert($wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Country', 'system_name' => 'country', 'sequence' => '6'), array('%s', '%s', '%s'));
			$wpdb->insert($wpdb->prefix . "events_question", array('wp_user' => $user_id, 'question' => 'Phone', 'system_name' => 'phone', 'sequence' => '7'), array('%s', '%s', '%s'));
		}

		$system_group = $wpdb->get_row("SELECT system_group FROM " . $wpdb->prefix . "events_qst_group" . " WHERE system_group = 1 AND wp_user='" . $user_id . "' ");

		if ($wpdb->num_rows == 0) {

			//Add new groups, find id, assign the system questions to the group
			$wpdb->insert($wpdb->prefix . "events_qst_group", array('wp_user' => $user_id, 'group_name' => 'Personal Information', 'group_identifier' => 'personal_information-' . time(), 'system_group' => 1, 'group_order' => 1), array('%d', '%s', '%s', '%d', '%d'));
			$personal_group_id = $wpdb->insert_id;
			$wpdb->insert($wpdb->prefix . "events_qst_group", array('wp_user' => $user_id, 'group_name' => 'Address Information', 'group_identifier' => 'address_information-' . time(), 'system_group' => 0, 'group_order' => 2), array('%d', '%s', '%s', '%d', '%d'));
			$address_group_id = $wpdb->insert_id;

			//Personal Information System Group
			//Find fname, lname, and email ids.  At this point, they will be in the system group.
			$system_name_data = "SELECT id, system_name FROM " . $wpdb->prefix . "events_question" . " WHERE system_name IN ('fname', 'lname', 'email') AND wp_user='" . $user_id . "' ";
			$system_names = $wpdb->get_results($system_name_data);
			foreach ($system_names as $system_name) {
				$wpdb->insert($wpdb->prefix . "events_qst_group_rel", array('group_id' => $personal_group_id, 'question_id' => $system_name->id), array('%d', '%d'));
			}

			//Address Group
			//Find address, city, state, and zip ids.
			$system_name_data = "SELECT id, system_name FROM " . $wpdb->prefix . "events_question" . " where system_name IN ('address', 'city', 'state', 'zip' ) AND wp_user='" . $user_id . "' ";
			$system_names = $wpdb->get_results($system_name_data);
			foreach ($system_names as $system_name) {
				$wpdb->insert($wpdb->prefix . "events_qst_group_rel", array('group_id' => $address_group_id, 'question_id' => $system_name->id), array('%d', '%d'));
			}
		}
	}
}

// function espresso_add_default_questions
add_action('personal_options_update', 'espresso_add_default_questions');
add_action('edit_user_profile_update', 'espresso_add_default_questions');
add_action('user_register', 'espresso_add_default_questions');/**/
