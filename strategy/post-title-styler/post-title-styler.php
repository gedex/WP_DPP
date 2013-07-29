<?php
/*
Plugin Name: Post Title Styler
Description: Style post title with several strategies (Strategy design pattern example).
Version: 0.1.0
Author: Akeda Bagus
Author URI: http://gedex.web.id/
Plugin URI: http://github.com/gedex/WP_DPP
*/

namespace WP_DPP\Post_Title_Styler;

// Hack to get the corret path since we did symlink plugins via provisioner.
define( 'WP_DPP_PTS_PATH', sprintf( '%s/%s/', WP_PLUGIN_DIR, basename( dirname( __FILE__ ) ) ) );

define( 'WP_DPP_PTS_DOMAIN', 'WP_DPP_PTS' );


/**
 * Strategy context.
 */
class Strategy_Context {
	private $strategy;

	public function __construct( $strategy ) {
		$classname = "WP_DPP\\Post_Title_Styler\\{$strategy}";
		if ( ! class_exists( $classname ) && ! is_a( $classname, 'Strategy' ) )
			throw new \Exception( 'Unsupported title styler ' . $strategy );

		$this->strategy = new $classname;
	}

	public function get_styled_title( $title ) {
		return $this->strategy->style_the_title( $title );
	}
}


/**
 * Interface in which Strategy class MUST implement
 */
interface Strategy {
	public function style_the_title( $title );
	public static function get_label();
}


class Uppercase_First_Strategy implements Strategy {
	public function style_the_title( $title ) {
		return ucfirst( $title );
	}
	public static function get_label() {
		return __( 'Uppercase first char', WP_DPP_PTS_DOMAIN );
	}
}


class Uppercase_All_Strategy implements Strategy {
	public function style_the_title( $title ) {
		return strtoupper( $title );
	}
	public static function get_label() {
		return __( 'Uppercase all chars', WP_DPP_PTS_DOMAIN );
	}
}


class Lowercase_All_Strategy implements Strategy {
	public function style_the_title( $title ) {
		return strtolower( $title );
	}
	public static function get_label() {
		return __( 'Lowercase all chars', WP_DPP_PTS_DOMAIN );
	}
}


/**
 * This plugin bootstraper.
 */
class WP_Plugin {

	const STYLER_POSTMETA = 'WP_DPP_PTS';
	const FIELD_ID = 'wp_dpp_pts_strategy';

	/**
	 * List of classes which implement `WP_DPP_PTS_Strategy`.
	 *
	 * @var array
	 */
	private $strategies;

	public function __construct() {
		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( $this, 'on_add_meta_boxes' ) );
			add_action( 'save_post',      array( $this, 'on_save_post' ) );
		} else {
			add_filter( 'the_title', array( $this, 'style_the_title' ), 10, 2 );
		}
	}

	/**
	 * Callback for `save_post` action. Save the choosen strategy from metabox.
	 *
	 * @param int $post_id Post ID
	 */
	public function on_save_post( $post_id ) {
		global $pagenow;

		$in_expected_condition = (
			in_array( $pagenow, array( 'post.php', 'post-new.php' ) )
			&&
			current_user_can( 'edit_post', $post_id )
			&&
			isset( $_POST[ __CLASS__ ] ) // nonce
			&&
			wp_verify_nonce( $_POST[ __CLASS__ ], WP_DPP_PTS_PATH . basename( __FILE__ ) )
		);

		if ( ! $in_expected_condition )
			return;

		$val = sanitize_text_field( $_POST[ self::FIELD_ID ] );
		if ( ! in_array( $val, array_keys( $this->_get_strategies() ) ) )
			$val = '';

		update_post_meta( $post_id, self::STYLER_POSTMETA, $val );
	}

	/**
	 * Callback for `add_meta_boxes` action.
	 *
	 * @action add_meta_boxes
	 */
	public function on_add_meta_boxes() {
		extract( array(
			'id'       => basename( dirname( __FILE__ ) ),
			'title'    => __( 'Post title styler', WP_DPP_PTS_DOMAIN ),
			'callback' => array( $this, 'render_meta_box' ),
			'screen'   => 'post',
			'context'  => 'advanced',
			'priority' => 'default',
		) );

		add_meta_box( $id, $title, $callback, $screen, $context, $priority );
	}

	/**
	 * Callback for `add_meta_box`.
	 *
	 * @param object $post Post object
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( WP_DPP_PTS_PATH . basename( __FILE__ ), __CLASS__ );

		$styler = $this->_get_styler( $post->ID );

		extract( array(
			'id'         => self::FIELD_ID,
			'name'       => self::FIELD_ID,
			'strategies' => $this->_get_strategies(),
		) );

		?>
		<p>
		<label for="<?php echo esc_attr( $id ); ?>">
			<?php _e( 'Styler', WP_DPP_PTS_DOMAIN ); ?>
		</label>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
			<option value=""></option>
			<?php foreach ( $strategies as $class => $strategy ): ?>
				<option value="<?php echo esc_attr( $class ); ?>"<?php selected( $styler, $class ); ?>><?php echo esc_html( $strategy ); ?></option>
			<?php endforeach; ?>
		</select>
		</p>
		<p class="howto">
			<?php _e( 'Choose the strategy on how the title will be styled.', WP_DPP_PTS_DOMAIN ); ?>
		</p>
		<?php
	}

	/**
	 * Callback for `the_title` filter. This method asks WP_DPP_PTS_Strategy_Context
	 * to style based on given strategy.
	 *
	 * @filter the_title
	 */
	public function style_the_title( $title, $post_id ) {
		$styler = $this->_get_styler( $post_id );

		if ( ! $styler )
			return $title;

		try {
			$strategy_context = new Strategy_Context( $styler );
			$title = $strategy_context->get_styled_title( $title );
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() );
		}

		return $title;
	}

	/**
	 * Gets string of styler's classname.
	 *
	 * @return string Styler's classname stored in postmeta.
	 */
	private function _get_styler( $post_id ) {
		return get_post_meta( $post_id, self::STYLER_POSTMETA, true );
	}

	/**
	 * Collect all classes which implement `WP_DPP_PTS_Strategy` interface.
	 * Wont work if class is loaded via `__autoload`.
	 *
	 * @link http://stackoverflow.com/questions/3993759/php-how-to-get-a-list-of-classes-that-implement-certain-interface
	 * @return array Key is classname and value is description label
	 */
	private function _get_strategies() {
		if ( ! $this->strategies ) {
			$this->strategies = array();
			foreach ( get_declared_classes() as $class ) {
				$reflection = new \ReflectionClass( $class );
				if ( $reflection->implementsInterface( 'WP_DPP\Post_Title_Styler\Strategy' ) )
					$this->strategies[ str_replace( array( __NAMESPACE__, '\\' ), '', $class ) ] = $class::get_label();
			}
		}

		return $this->strategies;
	}
}

add_action( 'plugins_loaded', function() {
	new WP_Plugin();
} );
