<?php
/**
 * XO Post Background main.
 *
 * @package xo-post-background
 * @since 1.0.0
 */

/**
 * XO Post Background class.
 */
class XO_Post_Background {
	/**
	 * Available setting panel.
	 *
	 * @var bool.
	 */
	public $available_setting_panel;

	/**
	 * Construct.
	 */
	public function __construct() {
		global $wp_version;

		load_plugin_textdomain( 'xo-post-background' );

		$this->available_setting_panel = ( version_compare( $wp_version, '5.7' ) >= 0 );

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	/**
	 * Uninstall.
	 */
	public static function uninstall() {
		global $wpdb;

		if ( is_multisite() ) {
			$site_ids = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				delete_post_meta_by_key( '_background_id' );
				delete_post_meta_by_key( '_xo_background' );
			}
			restore_current_blog();
		} else {
			delete_post_meta_by_key( '_background_id' );
			delete_post_meta_by_key( '_xo_background' );
		}
	}

	/**
	 * Sets or returns whether the block editor is loading on the current screen.
	 */
	private function is_block_editor() {
		$current_screen = get_current_screen();
		return (
			( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() )
			|| ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) );
	}

	/**
	 * Sanitizes a hex color for CSS Color Level 4.
	 *
	 * @since 2.0.3
	 *
	 * @param string $color Hex color (with #).
	 * @return string|void Returns either '', a 3, 6 or 8 digit hex color (with #), or nothing.
	 */
	private function sanitize_hex_color( $color ) {
		if ( '' === $color ) {
			return '';
		}

		// 3, 6 or 8 hex digits, or the empty string.
		if ( preg_match( '|^#([A-Fa-f0-9]{3,4}){1,2}$|', $color ) ) {
			return $color;
		}
	}

	/**
	 * Setup plugin.
	 */
	public function plugins_loaded() {
		global $wp_version;

		if ( ! current_theme_supports( 'custom-background' ) ) {
			add_theme_support( 'custom-background' );
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'wp_ajax_set-post-background', array( $this, 'set_background' ) );

		if ( $this->available_setting_panel ) {
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		}

		add_action( 'wp_head', array( $this, 'enqueue_style' ), 1000 );
	}

	/**
	 * FIlter for init.
	 */
	public function init() {
		$plugin_dir = plugin_dir_path( __DIR__ );
		$asset_file = include $plugin_dir . 'build/index.asset.php';

		wp_register_script( 'xo-post-background-block-editor', XO_POST_BACKGROUND_URL . 'build/index.js', $asset_file['dependencies'], $asset_file['version'], true );
		wp_set_script_translations( 'xo-post-background-block-editor', 'xo-post-background', $plugin_dir . 'languages' );

		// For Classic editor.
		register_meta(
			'post',
			'_background_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		// For Block editor.
		register_meta(
			'post',
			'_xo_background',
			array(
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'       => 'object',
						'properties' => array(
							'id'              => array(
								'type'    => 'integer',
								'default' => 0,
							),
							'position_x'      => array(
								'type'    => 'string',
								'default' => 'left',
							),
							'position_y'      => array(
								'type'    => 'string',
								'default' => 'top',
							),
							'size'            => array(
								'type'    => 'string',
								'default' => 'auto',
							),
							'repeat'          => array(
								'type' => 'boolean',
							),
							'attachment'      => array(
								'type' => 'boolean',
							),
							'enable_color'    => array(
								'type' => 'boolean',
							),
							'color'           => array(
								'type'    => 'string',
								'default' => '',
							),
							'gradient'        => array(
								'type'    => 'string',
								'default' => 'custom',
							),
							'custom_gradient' => array(
								'type'    => 'string',
								'default' => '',
							),
						),
					),
				),
				'sanitize_callback' => function ( $meta_value ) {
					$meta_value['id'] = absint( $meta_value['id'] );
					if ( ! in_array( $meta_value['size'], array( 'auto', 'contain', 'cover' ), true ) ) {
						$meta_value['size'] = 'auto';
					}
					if ( ! in_array( $meta_value['position_x'], array( 'left', 'center', 'right' ), true ) ) {
						$meta_value['position_x'] = 'left';
					}
					if ( ! in_array( $meta_value['position_y'], array( 'top', 'center', 'bottom' ), true ) ) {
						$meta_value['position_y'] = 'top';
					}
					$meta_value['color'] = empty( $meta_value['color'] ) ? '' : $this->sanitize_hex_color( $meta_value['color'] );
					$meta_value['gradient'] = sanitize_text_field( $meta_value['gradient'] );
					$meta_value['custom_gradient'] = sanitize_text_field( $meta_value['custom_gradient'] );
					return $meta_value;
				},
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * FIlter for enqueue_block_editor_assets.
	 */
	public function enqueue_block_editor_assets() {
		global $post_type;

		if ( post_type_supports( $post_type, 'custom-fields' ) ) {
			wp_enqueue_script( 'xo-post-background-block-editor' );
		}
	}

	/**
	 * FIlter for admin_enqueue_scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function admin_enqueue_scripts( $hook_suffix ) {
		if ( ! $this->available_setting_panel || ! $this->is_block_editor() ) {
			/*
			Classic editor.
			*/
			global $post_id;

			if ( ! in_array( $hook_suffix, array( 'post-new.php', 'post.php' ), true ) ) {
				return;
			}

			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_media( array( 'post' => ( $post_id ? $post_id : null ) ) );
			wp_enqueue_script( 'xo-post-background-classic-editor', XO_POST_BACKGROUND_URL . "assets/js/classic-editor{$min}.js", array( 'jquery', 'media-models', 'set-post-thumbnail' ), XO_POST_BACKGROUND_VERSION, false );

			wp_add_inline_style( 'edit', '#postbackgrounddiv .inside img { max-width: 100%; height: auto; }' );
		}
	}

	/**
	 * FIlter for add_meta_boxes.
	 */
	public function add_meta_boxes() {
		if ( ! $this->available_setting_panel || ! $this->is_block_editor() ) {
			/*
			Classic editor.
			*/
			global $wp_version;
			$screens = ( version_compare( $wp_version, '4.4' ) >= 0 ) ? array_diff( get_post_types( array( 'public' => true ), 'names' ), array( 'attachment' ) ) : null;
			$screens = apply_filters( 'xo_post_background_meta_box_screens', $screens );
			add_meta_box( 'postbackgrounddiv', __( 'Background Image', 'xo-post-background' ), array( $this, 'add_meta_box' ), $screens, 'side', 'low' );
		}
	}

	/**
	 * FIlter for add_meta_box.
	 *
	 * @param WP_Post $post Post.
	 */
	public function add_meta_box( $post ) {
		$background_id = get_post_meta( $post->ID, '_background_id', true );
		echo $this->post_background_html( $post->ID, $background_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Get ths background meta box html.
	 *
	 * @param int $post_id       Post id.
	 * @param int $background_id Background madia id.
	 */
	private function post_background_html( $post_id, $background_id = 0 ) {
		global $_wp_additional_image_sizes;

		$ajax_nonce = wp_create_nonce( 'set_post_background-' . $post_id );

		$format_link = '<p class="hide-if-no-js"><a href="#" id="set-post-background"%s>%s</a></p>' . "\n";
		$content     = sprintf( $format_link, '', __( 'Set background image', 'xo-post-background' ) );

		if ( $background_id && get_post( $background_id ) ) {
			$size           = isset( $_wp_additional_image_sizes['post-thumbnail'] ) ? 'post-thumbnail' : array( 266, 266 );
			$thumbnail_html = wp_get_attachment_image( $background_id, $size );
			if ( ! empty( $thumbnail_html ) ) {
				$content  = sprintf( $format_link, ' aria-describedby="set-post-background-desc"', $thumbnail_html );
				$content .= '<p class="hide-if-no-js howto" id="set-post-background-desc">' . __( 'Click the image to edit or update', 'xo-post-background' ) . '</p>';
				$content .= '<p class="hide-if-no-js"><a href="#" id="remove-post-background" onclick="XORemoveBackground(\'' . $post_id . '\',\'' . $ajax_nonce . '\');return false;">' . __( 'Remove background image', 'xo-post-background' ) . '</a></p>' . "\n";
			}
		}

		$script  = 'new XOBackgroundMediaUploader({';
		$script .= 'uploader_title: "' . __( 'Background Image', 'xo-post-background' ) . '",';
		$script .= 'uploader_button_text: "' . __( 'Set background image', 'xo-post-background' ) . '",';
		$script .= 'id: "' . $background_id . '",';
		$script .= 'selector: "#set-post-background",';
		$script .= 'cb: function(attachment) { XOSetAsBackground(attachment.id, "' . $post_id . '", "' . $ajax_nonce . '"); }';
		$script .= '});';

		$content .= '<script type="text/javascript">' . $script . '</script>' . "\n";

		return $content;
	}

	/**
	 * Ajax callback function.
	 */
	public function set_background() {
		if ( ! isset( $_POST['post_id'] ) ) {
			die( '-1' );
		}

		$post_id = intval( $_POST['post_id'] );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			die( '-1' );
		}

		check_ajax_referer( 'set_post_background-' . $post_id );

		$background_id = isset( $_POST['background_id'] ) ? intval( $_POST['background_id'] ) : 0;

		if ( 0 >= $background_id ) {
			delete_post_meta( $post_id, '_background_id' );
			die( $this->post_background_html( $post_id, 0 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( $background_id && get_post( $background_id ) ) {
			$thumbnail_html = wp_get_attachment_image( $background_id, 'thumbnail' );
			if ( ! empty( $thumbnail_html ) ) {
				update_post_meta( $post_id, '_background_id', $background_id );
				delete_post_meta( $post_id, '_xo_background' );
				die( $this->post_background_html( $post_id, $background_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		die( '0' );
	}

	/**
	 * Get ths image src from ID.
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0 Use get_post_background_meta()
	 *
	 * @param int|null $post_id Post ID.
	 * @param string   $size    Image size.
	 */
	public function get_post_image_src( $post_id = null, $size = 'fullsize' ) {
		if ( ! isset( $post_id ) ) {
			global $post;
			if ( ! isset( $post ) ) {
				return false;
			}
			$post_id = $post->ID;
		}
		$attachment_id = get_post_meta( $post_id, '_background_id', true );
		if ( ( '' === $attachment_id ) ) {
			return false;
		}
		return wp_get_attachment_image_src( $attachment_id, $size );
	}

	/**
	 * Get post background meta.
	 *
	 * @param int|null $post_id Post ID.
	 */
	public function get_post_background_meta( $post_id = null ) {
		if ( ! isset( $post_id ) ) {
			global $post;
			if ( ! isset( $post ) ) {
				return false;
			}
			$post_id = $post->ID;
		}

		$background_default = array(
			'id'           => 0,
			'position_x'   => 'left',
			'position_y'   => 'top',
			'size'         => 'auto',
			'repeat'       => true,
			'attachment'   => true,
			'enable_color' => false,
			'color'        => '',
		);

		$background = get_post_meta( $post_id, '_xo_background', true );

		if ( '' === $background ) {
			// Backward compatibility.
			$background_id = get_post_meta( $post_id, '_background_id', true );
			if ( '' === $background_id ) {
				return false;
			}
			$background_default['id'] = $background_id;
			return $background_default;
		}

		$background = $background + $background_default;

		return $background;
	}

	/**
	 * Filter dor wp_head.
	 */
	public function enqueue_style() {
		/**
		 * Filters whether to set the background on the archive page.
		 *
		 * @since 1.3.0
		 *
		 * @param bool $xo_post_background_enable_archive Whether to enable it on the archive page. Default false.
		 */
		if ( ! apply_filters( 'xo_post_background_enable_archive', false ) ) {
			if ( ! is_singular() ) {
				return;
			}
		}

		$background = $this->get_post_background_meta();
		if ( false === $background ) {
			return;
		}

		$style     = '';
		$image_src = '';
		$gradient  = '';

		if ( $background['enable_color'] ) {
			if ( ! empty( $background['gradient'] ) ) {
				if ( ! empty( $background['custom_gradient'] ) ) {
					$gradient = $background['custom_gradient'];
				}
			} elseif ( ! empty( $background['color'] ) ) {
				if ( ( 1 + 6 ) >= strlen( $background['color'] ) ) {
					$style = "background-color: {$background['color']};";
				} else {
					$gradient = 'radial-gradient(' . $background['color'] . ' 0%, ' . $background['color'] . ' 100%)';
				}
			}
		}

		$image_attributes = wp_get_attachment_image_src( $background['id'], 'fullsize' );
		if ( false === $image_attributes ) {
			if ( ! empty( $gradient ) ) {
				$style = "background: {$gradient};";
			}
		} else {
			$image_src = $image_attributes[0];
			if ( ! empty( $gradient ) ) {
				$gradient .= ',';
			}
			$image = ' background-image: ' . $gradient . 'url("' . esc_url( $image_src ) . '");';

			// Background Position.
			$position_x = ( in_array( $background['position_x'], array( 'left', 'center', 'right' ), true ) ) ? $background['position_x'] : 'left';
			$position_y = ( in_array( $background['position_y'], array( 'top', 'center', 'bottom' ), true ) ) ? $background['position_y'] : 'top';
			$position   = " background-position: $position_x $position_y;";

			// Background Size.
			$size = in_array( $background['size'], array( 'auto', 'contain', 'cover' ), true ) ? $background['size'] : 'auto';
			$size = " background-size: $size;";

			// Background Repeat.
			$repeat = $background['repeat'] ? 'repeat' : 'no-repeat';
			$repeat = " background-repeat: $repeat;";

			// Background Scroll.
			$attachment = $background['attachment'] ? 'scroll' : 'fixed';
			$attachment = " background-attachment: $attachment;";

			$style = trim( $style . $image . $position . $size . $repeat . $attachment );
		}

		if ( $style ) {
			$style = 'body, body.custom-background { ' . $style . ' }';
		}

		/**
		 * Filters CSS styles.
		 *
		 * @since 1.2.0
		 * @since 2.0.0 Added `$background` parameter.
		 *
		 * @param string $style      CSS styles.
		 * @param string $image_src  Image URL.
		 * @param array  $background Background meta data.
		 */
		$style = apply_filters( 'xo_post_background_style', $style, $image_src, $background );

		if ( $style ) {
			$type_attr = current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"';
			echo '<style' . $type_attr . ' id="xo-post-background-css">' . wp_strip_all_tags( $style ) . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
