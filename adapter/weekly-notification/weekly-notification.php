<?php
/*
Plugin Name: Weekly Notification
Description: Logs when post is updated (Abstract factory design pattern example).
Version: 0.1.0
Author: Akeda Bagus
Author URI: http://gedex.web.id/
Plugin URI: http://github.com/gedex/WP_DPP
*/

namespace WP_DPP\Weekly_Notification;

define( 'PLUGIN_PATH', sprintf( '%s/%s/%s', WP_PLUGIN_DIR, basename( dirname( __FILE__ ) ), basename( __FILE__ ) ) );

require_once PLUGIN_PATH . '/interface-notifier.php';

class WP_Plugin {
	public function __construct() {
	}
}

add_action( 'plugins_loaded', function() {
	new WP_Plugin();
} );
