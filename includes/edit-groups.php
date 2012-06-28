<?php
	$author = get_userdata( $uid );
#	echo "<pre>".print_r($author,true)."</pre>";
	$group = get_user_meta($uid, "espresso_group", true);
	$group = unserialize($group);
?>
<div class="wrap">
	<h2><?php echo "Edit ".$author->user_nicename."'s locales/regions"; ?></h2>
	<?php if ( $group_updated ) espresso_admin_message( '', __('Manager updated.', 'event-espresso') ); ?>
	<?php do_action( 'espresso_pre_edit_group_form' );?>
	<div id="poststuff">
		<form name="form0" method="post" action="<?php echo admin_url( esc_url( "admin.php?page=event_groups&amp;action=save&amp;uid={$uid}" ) ); ?>" style="border:none;background:transparent;">
			<?php wp_nonce_field( espresso_get_nonce( 'edit-groups' ) ); ?>
			<div class="postbox open">
				<h3><?php echo __('<strong>Locales/Regions:</strong> ', 'event-espresso'); ?></h3>
				<div class="inside">
					<table class="form-table">
					<tr>
						<th colspan=2>
							<?php _e('Select which locales/regions this user should manage.', 'event-espresso'); ?>
							<br /><br />
						<?php
							$sql = "SELECT * FROM " . EVENTS_LOCALE_TABLE;
							$locales = $wpdb->get_results($sql);
							if( count($locales) ){
								foreach ($locales as $locale){
									$checked = "";
									if( isset($group) && is_array($group) ){
										if( isset($group[$locale->id]) && $locale->id = $group[$locale->id] ){
											$checked = " checked='checked' ";
										}
									}
?>
									<div style='overflow: hidden; margin: 0 0 5px 0; float:left; width: 32.67%;'>
										<input name='locales[<?php echo $locale->id; ?>]' id='<?php echo $locale->id;?>'  <?php echo $checked; ?> type='checkbox' value="<?php echo $locale->id;?>" /> 
										<label for="<?php echo $locale->id;?>">
											<?php if ( $checked ) echo "<strong>{$locale->name}</strong>"; else echo "<em>{$locale->name}</em>"; ?>
										</label>
									</div>
								<?php } // Endforeach ?>
<?php						}else{	?>
								<p><?php _e('You have no created any locales/regions yet.', 'event_espresso'); ?> <a href="<?php echo admin_url( esc_url( "admin.php?page=event_locales" ) ); ?>"><?php _e('Please click here to add locales', 'event_espresso'); ?></a></p>
<?php						}	?>
						</td>
					</tr>
					</table>
				</div>
			</div>
			<p class="submit" style="clear:both;">
				<input type="submit" name="Submit"  class="button-primary" value="<?php _e('Update Manager', 'event-espresso') ?>" />
				<input type="hidden" name="edit-group-saved" value="Y" />
			</p>
		</form>
	</div>
</div>