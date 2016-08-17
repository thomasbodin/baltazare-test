(function( $ ) {
	
	var itSyncSocial = {
		
		init: function() {
			this.bindEvents();

			/** The account list is not shown after post is published, so only call the initial character count method if list exists **/
			if ( $('.ithemes-social-accounts').length ) {
				this.updateCharCount( $('.ithemes-social-account-content textarea') );
			}
		},
		
		bindEvents: function() {
			$('#submitpost').on('click', '.ithemes-social-edit-content', this.toggleEdit);
			$('#submitpost').on('keyup', '.ithemes-social-account-content textarea', this.onKeyup);
		},
		
		toggleEdit: function(event) {
			event.preventDefault();
			$(this).siblings('.ithemes-social-account-content').slideToggle('fast');
		},
		
		onKeyup: function(event) {
			itSyncSocial.updateCharCount(event.currentTarget);
		},
		
		updateCharCount: function(element) {
			var content       = $(element).val()
								.replace( /\{post_author\}/, jQuery('#post_author_override option:selected').text() )
								.replace( /\{post_title\}/, jQuery('#title').val() )
								.replace( /\{post_url\}/, '{post_url-twentythree-}' ); //URLs are 23 characters, so we're padding this to that

			var container     = $(element).siblings('.ithemes-social-char-count'),
				currentLength = content.length,
				remaining     = 140 - currentLength;
				isOver        = remaining <= 0 ? true : false;

			container.html(remaining);
			isOver ? container.addClass('over') : container.removeClass('over');
		}

	};

	jQuery(document).ready( function() {
		itSyncSocial.init();
	});

})( jQuery );