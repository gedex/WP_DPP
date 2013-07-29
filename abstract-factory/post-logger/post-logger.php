<?php
/*
Plugin Name: Post Logger
Description: Logs when post is updated (Abstract factory design pattern example).
Version: 0.1.0
Author: Akeda Bagus
Author URI: http://gedex.web.id/
Plugin URI: http://github.com/gedex/WP_DPP
*/

namespace WP_DPP\Post_Logger;


interface Abstract_Factory {
	public static function get_instance();
	public static function get_loggers();
	public static function set_loggers();
}


class Factory {
	/**
	 * Logger products
	 *
	 * @var array
	 */
	private static $loggers;

	public static function get_instance( $logger ) {
		$classname = 'WP_DPP\Post_Logger\Logger_' . ucfirst( strtolower( $logger ) );
		if ( in_array( $logger, self::$products ) && class_exists( $classname ) ) {
			return new $classname;
		}

		throw new Exception( 'Undefined Logger ' . $logger );
	}

	public static function set_loggers(array $loggers) {
		self::$loggers = $loggers;
	}

	public static function get_loggers() {
		return self::$loggers;
	}
}


interface Logger {
	public function do_logging(\WP_Post $post);
}


class Logger_File implements Logger {
	public function do_logging(\WP_Post $post) {
		error_log( sprintf( 'Logger message for post "%s."', $post->post_title ) );
	}
}


class Logger_Email implements Logger {
	public function do_logging(\WP_Post $post) {
		extract( array(
			'to'      => 'admin@gedex.web.id',
			'subject' => 'Logger subject',
			'message' => 'Logger message for post ' . $post->post_title,
		) );
		wp_mail( $to, $subject, $message );
	}
}


/**
 * Class that bootstrap the plugin
 */
class WP_Plugin {

	public function __construct() {
		Factory::set_products( array('file', 'email') );

		add_action( 'save_post', array( $this, 'logger_in_action' ) );
	}

	public function logger_in_action( $post_id ) {
		if ( ! wp_is_post_revision( $post_id ) )
			return;

		foreach ( Factory::get_loggers() as $logger ) {
			try {
				$instance = Factory::get_instance( $logger );
				$instance->do_logging( get_post( $post_id ) );
			} catch (\Exception $e) {
				error_log( $e->getMessage() );
			}
		}
	}

}

add_action( 'plugins_loaded', function() {
	new WP_Plugin();
} );
