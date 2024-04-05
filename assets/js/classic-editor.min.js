var XOBackgroundMediaUploader,
	XOSetBackgroundHTML,
	XORemoveBackground,
	XOSetAsBackground;
! ( function ( $ ) {
	( XOBackgroundMediaUploader = function ( options ) {
		var self = this,
			frame = wp.media.frames.file_frame;
		return (
			( this.settings = {
				uploader_title: '',
				uploader_button_text: '',
				id: '',
				selector: ! 1,
				cb: function ( attachment ) {},
			} ),
			( this.attachEvents = function attachEvents() {
				$( this.settings.selector ).on( 'click', this.openFrame );
			} ),
			( this.openFrame = function openFrame( e ) {
				e.preventDefault(),
					( frame = wp.media.frames.file_frame =
						wp.media( {
							title: self.settings.uploader_title,
							button: {
								text: self.settings.uploader_button_text,
							},
							multiple: ! 1,
							library: { type: 'image' },
						} ) ).on( 'toolbar:create:select', function () {
						frame.state().set( 'filterable', 'uploaded' );
					} ),
					frame.on( 'select', function () {
						var attachment = frame
							.state()
							.get( 'selection' )
							.first()
							.toJSON();
						self.settings.cb( attachment );
					} ),
					frame.on( 'open activate', function () {
						var $target = $( self.settings.selector );
						if ( '' !== self.settings.id ) {
							var Attachment = wp.media.model.Attachment,
								selection;
							frame
								.state()
								.get( 'selection' )
								.add( Attachment.get( self.settings.id ) );
						}
					} ),
					frame.open();
			} ),
			( this.init = function init() {
				( this.settings = $.extend( this.settings, options ) ),
					this.attachEvents();
			} ),
			this.init(),
			this
		);
	} ),
		( XOSetBackgroundHTML = function ( html ) {
			$( '.inside', '#postbackgrounddiv' ).html( html );
		} ),
		( XORemoveBackground = function ( id, nonce ) {
			$.post(
				ajaxurl,
				{
					action: 'set-post-background',
					post_id: id,
					background_id: 0,
					_ajax_nonce: nonce,
					cookie: encodeURIComponent( document.cookie ),
				},
				function ( str ) {
					'0' == str
						? alert( setPostThumbnailL10n.error )
						: XOSetBackgroundHTML( str );
				}
			);
		} ),
		( XOSetAsBackground = function ( background_id, id, nonce ) {
			var $link = $( 'a#set-post-background' );
			$link.text( setPostThumbnailL10n.saving ),
				$.post(
					ajaxurl,
					{
						action: 'set-post-background',
						post_id: id,
						background_id: background_id,
						_ajax_nonce: nonce,
						cookie: encodeURIComponent( document.cookie ),
					},
					function ( str ) {
						var win =
							window.dialogArguments || opener || parent || top;
						$link.text( setPostThumbnailL10n.setThumbnail ),
							'0' == str
								? alert( setPostThumbnailL10n.error )
								: ( $link.show(),
								  $link.text( setPostThumbnailL10n.done ),
								  $link.fadeOut( 2e3 ),
								  win.XOSetBackgroundHTML( str ) );
					}
				);
		} );
} )( jQuery );
