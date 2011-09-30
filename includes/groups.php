<?php
	global $wp_roles, $wpdb;
	$action = $_GET['action'];
	switch($action){
		case 'save':
			//	update user meta
			$locales = serialize($_REQUEST['locales']);
			$uid = $_GET['uid'];
			update_user_meta($uid, 'espresso_group', $locales);
			require_once("edit-groups.php");
		case 'edit':
			check_admin_referer( espresso_get_nonce( 'edit-groups' ) );
			$title = __('Edit Regional Managers', 'event_espresso');
			$uid = $_GET['uid'];
			require_once("edit-groups.php");
			break;
		default:
			require_once("default-groups.php");
			break;
	}
?>