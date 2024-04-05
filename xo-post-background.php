<?php
/**
 * XO Post Background plugin for WordPress.
 *
 * @package xo-post-background
 * @author  ishitaka
 * @license GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       XO Post Background
 * Plugin URI:        https://xakuro.com/wordpress/
 * Description:       XO Post Background is a plugin to set background image and color for each post.
 * Author:            Xakuro
 * Author URI:        https://xakuro.com/
 * License:           GPL v2 or later
 * Requires at least: 4.9
 * Requires PHP:      5.6
 * Version:           2.0.11
 * Text Domain:       xo-post-background
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'XO_POST_BACKGROUND_VERSION', '2.0.11' );
define( 'XO_POST_BACKGROUND_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/inc/class-xo-post-background.php';

$xo_post_background = new XO_Post_Background();

register_uninstall_hook( __FILE__, 'XO_Post_Background::uninstall' );
