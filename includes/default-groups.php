<div class="wrap">
	<h2><?php echo $title; ?></h2>
	<?php do_action( 'action_hook_espresso_pre_edit_groups_form' );?>
	<div id="poststuff">
		<table class="widefat fixed" cellspacing="0">
		<thead>
			<tr>
				<th class='check-column'><input type='checkbox' /></th>
				<th class='name-column'><?php _e('UserName', 'event-espresso'); ?></th>
				<th><?php _e('Email', 'event-espresso'); ?></th>
				<th><?php _e('Nice Name', 'event-espresso'); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th class='check-column'><input type='checkbox' /></th>
				<th class='name-column'><?php _e('UserName', 'event-espresso'); ?></th>
				<th><?php _e('Email', 'event-espresso'); ?></th>
				<th><?php _e('Nice Name', 'event-espresso'); ?></th>
			</tr>
		</tfoot>
		<tbody id="users" class="list:user user-list plugins">
<?php
	global $table_prefix;
	$authors = $wpdb->get_results("SELECT * from $wpdb->usermeta WHERE meta_key = '".$table_prefix.'capabilities'."' AND meta_value LIKE '%espresso_group_admin%'");
	foreach ( (array) $authors as $author ) {
		$author = get_userdata( $author->user_id );
//		print_r($author);
//		die();
		$name = $author->user_login;
		$uid = $author->ID;
		$edit_link = admin_url( wp_nonce_url( "admin.php?page=event_groups&amp;action=edit&amp;uid={$uid}", espresso_get_nonce( 'edit-groups' ) ) );
?>
		<tr>
			<th class="manage-column column-cb check-column"></th>
			<td>
				<a href="<?php echo $edit_link; ?>" title="<?php printf( __('Edit the %1$s role', 'event-espresso'), $name ); ?>"><strong><?php echo $name; ?></strong></a>
				<div class="row-actions">
					<a href="<?php echo $edit_link; ?>" title="<?php printf( __('Edit the %1$s role', 'event-espresso'), $name ); ?>"><? _e('Edit', 'event-espresso'); ?></a> 
				</div>
			</td>
			<td><?=$author->user_email?></td>
			<td><?=$author->user_nicename?></td>
		</tr>
<?php
	}
?>		
		</tbody>
		</table>
	</div>
</div>