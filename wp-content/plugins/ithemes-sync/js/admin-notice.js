jQuery(document).ready(
	function() {
		jQuery('.ithemes-sync-notice-dismiss').click(
			function( e ) {
				jQuery('#ithemes-sync-notice').hide();
				
				return false;
			}
		);
		
		jQuery('.ithemes-sync-notice-hide').click(
			function( e ) {
				jQuery('#ithemes-sync-notice').hide();
				
				var post_data = {
					action: 'ithemes_sync_hide_notice'
				};
				
				jQuery.post( ajaxurl, post_data );
				
				return false;
			}
		);
	}
);
