jQuery(document).ready(
	function() {
		jQuery('.ithemes-sync-wrapper .deauthenticate').click(
			function( event ) {
				var response = confirm( ithemes_sync_settings.confirm_dialog_text );
				return response;
			}
		);
	}
);
