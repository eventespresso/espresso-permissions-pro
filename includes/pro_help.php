<div style="display: none;">
	<?php
    	/**
    	 * Payments
    	 */
    ?>
	<div id="can_accept_payments" class="pop-help" >
		<div class="TB-ee-frame">
			<h2>
				<?php _e('Allow Managers to Accept Payments for Events', 'event_espresso'); ?>
			</h2>
			<p>
				<?php _e('Enabling this option will allow managers (event managers, regional managers, master admins, and super admins) to accept payments for the events they have created.', 'event_espresso'); ?>
			</p>
			<p>
				<?php _e('Attention! If this option is enabled, you must grant access to the payment settings page abouve. ', 'event_espresso'); ?>
			</p>
			
		</div>
	</div>
	<?php
		/**
		 * Category Sharing
		 */
	?>
	<div id="event_manager_share_cats" class="pop-help" >
		<div class="TB-ee-frame">
			<h2>
				<?php _e('Category Sharing', 'event_espresso'); ?>
			</h2>
		
		<p>
			<?php _e('Enabling this option will allow event managers access to categories created by other users. ', 'event_espresso'); ?>
			</p>
			
		</div>
	</div>
	<?php
		/**
		 * Create a Post
		 */
	?>
	<div id="event_manager_create_post" class="pop-help" >
		<div class="TB-ee-frame">
			<h2>
				<?php _e('Custom Post, Posts, and Page Creation', 'event_espresso'); ?>
			</h2>
			<p>
				<?php _e('Enabling this option will allow event managers to automatically create psots when adding new events. Depending on you WordPress settings, these posts may appear on your website.', 'event_espresso'); ?>
			</p>
			
		</div>
	</div>
	<?php
		/**
		 * Regional Managers Can Edit Other Venues
		 */
	?>
	<div id="event_manager_venue" class="pop-help" >
		<div class="TB-ee-frame">
			<h2>
				<?php _e('Regional Managers Can Edit Other Venues', 'event_espresso'); ?>
			</h2>
			<p>
			<?php _e('Enabling this option will grant Regional Managers full access to edit venues that are within the regions they manage.', 'event_espresso'); ?>
			</p>
			
		</div>
	</div>
	<?php
		/**
		 * Event Managers Require Approval
		 */
	?>
	<div id="event_manager_approval" class="pop-help" >
		<div class="TB-ee-frame" style="height:500px; overflow-y: scroll;">
			<h2>
				<?php _e('Event Managers Require Approval', 'event_espresso'); ?>
			</h2>
			<p>
				<?php _e('If this option is enabled, events created by Event Managers will go into a pending status until approved by an Admin, Regional Manager, or Master Admin.', 'event_espresso'); ?>
			</p>
			
		</div>
	</div>
	<?php
		/**
		 * Login as user
		 */
	?>
	<div id="login_as_user" class="pop-help" >
		<div class="TB-ee-frame" style="height:500px; overflow-y: scroll;">
			<h2>
				<?php _e('Login as User', 'event_espresso'); ?>
			</h2>
			<p>
				<?php _e('To use this feature, just enter the ID of any Admin, Regional Manager, Event Manager, or Master Admin member. You will then be allowed to view all of the Event Espresso settings, event and attendee data as that user, without logging out of your account.', 'event_espresso'); ?>
			</p>
			
		</div>
	</div>
	
	<!-- End parent div -->
</div>
