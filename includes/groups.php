<?php
	global $wp_roles, $wpdb;
	$action = isset( $_GET[ 'action' ] ) ? $_GET[ 'action' ] : '';
    $group_updated = false;
	switch($action){
		case 'save':
			//	update user meta
			if ( !empty($_REQUEST['locales']) ){
				$locales = serialize($_REQUEST['locales']);
			}else{
				$locales = 0;
			}
			$uid = $_GET['uid'];
			update_user_meta($uid, 'espresso_group', $locales);
            $group_updated = true;
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