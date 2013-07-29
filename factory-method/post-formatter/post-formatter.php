<?php
/*
Plugin Name: Post Formatter
Description: Add <code>/format/(xml|json)</code> to post permalink (Factory method design pattern example).
Version: 0.1.0
Author: Akeda Bagus
Author URI: http://gedex.web.id/
Plugin URI: http://github.com/gedex/WP_DPP
*/

namespace WP_DPP;

/**
 * Factory class that builds the formatter instance.
 */
class Post_Formatter_Factory {
	/**
	 * Method that returns instance of Post Formatter.
	 *
	 * @param string $format Expected post format passed by endpoint
	 * @return WP_DPP_Post_Formatter
	 */
	public static function make_format( $format ) {
		$class_name = 'WP_DPP\Post_Format_' . strtoupper( $format );
		if ( ! class_exists( $class_name ) && ! is_a( $class_name, 'WP_DPP_Post_Formatter' ) )
			throw new \Exception( sprintf( 'Unsupported format <strong>%s</strong>.', $format ) );

		return new $class_name;
	}
}


/**
 * Interface in which Formatter class MUST implement
 */
interface Post_Formatter {
	public function render( $post );
}


/**
 * XML formatter
 */
class Post_Format_XML implements Post_Formatter {
	public function render( $post ) {
		setup_postdata( $post );
		header( 'Content-Type: text/xml' );
		$template = trim('
		<?xml version="1.0" encoding="utf-8"?>
		<post>
			<title>{{title}}</title>
			<link>{{permalink}}</link>
			<content>
			<![CDATA[
				{{content}}
			]]>
			</content>
		</post>
		');

		$tags = array(
			'title'     => get_the_title(),
			'permalink' => get_permalink(),
			'content'   => get_the_content(),
		);

		$callback = function($tag, $value) use(&$template) {
			$template = str_replace( '{{'.$tag.'}}', $value, $template );
		};
		array_map( $callback, array_keys($tags), $tags );

		echo $template;
	}
}


/**
 * JSON Formatter
 */
class Post_Format_JSON implements Post_Formatter {
	public function render( $post ) {
		setup_postdata( $post );
		header( 'Content-Type: text/json' );
		echo json_encode( array(
			'post' => array(
				'title'     => get_the_title(),
				'permalink' => get_permalink(),
				'content'   => get_the_content(),
			),
		) );
	}
}


/**
 * Class that bootstrap the plugin.
 */
class Post_Formatter_Bootstrap {
	const EP_NAME = 'format';

	/**
	 * Endpoint value. Expected value is json|xml
	 *
	 * @var string
	 */
	private $format;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Hack to get the corret path since we did symlink plugins via provisioner.
		$plugin_path = sprintf( '%s/%s/%s', WP_PLUGIN_DIR, basename( dirname( __FILE__ ) ), basename( __FILE__ ) );

		register_activation_hook(   $plugin_path, array( $this, 'on_activate' )   );
		register_deactivation_hook( $plugin_path, array( $this, 'on_deactivate' ) );

		add_action( 'init', array( $this, 'on_init' ) );
	}

	/**
	 * Callback for `init` action. Add rewrite endpoint and hook into
	 * appropriate actions.
	 *
	 * @action init
	 */
	public function on_init() {
		add_rewrite_endpoint( self::EP_NAME, EP_PERMALINK );

		add_action( 'template_redirect', array( $this, 'on_template_redirect' ) );
		add_action( 'pre_get_posts',     array( $this, 'on_pre_get_posts' )     );
	}

	/**
	 * Callback for `register_activation_hook`. Register the endpoint and flush rewrite rules.
	 */
	public function on_activate() {
		global $wp_rewrite;

		add_rewrite_endpoint( self::EP_NAME, EP_PERMALINK );
		$wp_rewrite->flush_rules();
	}

	/**
	 * Callback for `register_deactivation_hook`. Flush rewrite rules when plugin deactivated.
	 */
	public function on_deactivate() {
		global $wp_rewrite;

		$wp_rewrite->flush_rules();
	}

	/**
	 * Callback for `template_redirect` action. Render based on requested format.
	 * This will ask the Factory class the formatter instance and then render it
	 * if applicable.
	 *
	 * For example if a permalink like following is requested:
	 *
	 * ~~~
	 * http://33.33.33.33/2013/05/29/hello-world/format/json
	 * ~~~
	 *
	 * The Factory will returns an instance of WP_DPP_Post_Format_JSON and then
	 * call the render method that the instance has.
	 */
	public function on_template_redirect() {
		global $post;

		if ( ! $this->format )
			return;

		try {
			$formatter = Post_Formatter_Factory::make_format( $this->format );
			$formatter->render( $post );
		} catch (\Exception $e) {
			wp_die( $e->getMessage() );
		}
		exit;
	}

	/**
	 * Callback for `pre_get_posts` action. Set `format` property to
	 * the EP value.
	 */
	public function on_pre_get_posts( $query ) {
		if ( ! $query->is_single )
			return;

		if ( ! isset( $query->query_vars[ self::EP_NAME ] ) )
			return;

		$this->format = $query->query_vars[ self::EP_NAME ];
	}
}

add_action( 'plugins_loaded', function() {
	new Post_Formatter_Bootstrap();
} );
