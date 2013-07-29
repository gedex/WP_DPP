<?php
/*
Plugin Name: Censor Bad Words
Description: Censor bad words in post's title, content, and excerpt (Singleton design pattern example).
Version: 0.1.0
Author: Akeda Bagus
Author URI: http://gedex.web.id/
Plugin URI: http://github.com/gedex/WP_DPP
*/

namespace WP_DPP;

class Censor_Bad_Words {

	private static $instance;

	private $words_to_search;
	private $words_replacement;

	const OPTION_NAME = 'cbw_bad_words_dictionary';

	/**
	 * Public method to access the instance. Instantiates the object
	 * and keep the reference in private property.
	 *
	 * @return object Instance of this class
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register callbacks to appropriate hooks where it needs to censor the bad words.
	 *
	 * @filter the_title
	 * @filter the_content
	 * @filter the_excerpt
	 */
	private function _register_callbacks() {
		$filters = array( 'the_title', 'the_content', 'the_excerpt' );
		foreach ( $filters as $filter ) {
			add_filter( $filter, array( $this, 'replace_bad_words' ) );
		}
	}

	/**
	 * Makes it private so it can not be instantiated publicly.
	 *
	 * @return void
	 */
	private function __construct() {
		$this->_build_dictionary();

		// Hack to get the corret path since we did symlink plugins via provisioner.
		$plugin_path = sprintf( '%s/%s/%s', WP_PLUGIN_DIR, basename( dirname( __FILE__ ) ), basename( __FILE__ ) );

		register_deactivation_hook( $plugin_path, array( __CLASS__, 'remove_dictionary' ) );
		register_uninstall_hook(    $plugin_path, array( __CLASS__, 'remove_dictionary' ) );

		$this->_register_callbacks();
	}

	/**
	 * Prevents instance to be cloned
	 *
	 * @link http://php.net/manual/en/language.oop5.cloning.php
	 */
	private function __clone() {}

	/**
	 * Prevents `unserialize` to reconstruct this instance
	 *
	 * @link http://www.php.net/manual/en/language.oop5.magic.php#object.wakeup
	 */
	private function __wakeup() {}

	/**
	 * Replaces bad words with replacement words.
	 *
	 * @param string $content String to be censored
	 * @return string String where bad words are replaced with word replacements
	 */
	public function replace_bad_words($content = '') {
		if ( ! $this->words_to_search && ! $this->words_replacement )
			$this->_build_dictionary();

		return str_ireplace( $this->words_to_search, $this->words_replacement, $content );
	}

	/**
	 * Builds the bad words list from file and store it into property and option.
	 *
	 * @return void
	 */
	private function _build_dictionary() {
		$blacklist_dict = get_option( self::OPTION_NAME, array() );

		if ( empty( $blacklist_dict ) ) {
			$words = explode( ',', file_get_contents( dirname( __FILE__ ) . '/blacklist.txt' ) );

			$blacklist_dict = array();
			foreach ($words as $word) {
				$blacklist_dict[$word] = str_replace( array('a', 'i', 'u', 'e', 'o'), '*', $word );
			}
			update_option( self::OPTION_NAME, $blacklist_dict );
		}

		self::$instance->words_to_search   = array_keys( $blacklist_dict );
		self::$instance->words_replacement = array_values( $blacklist_dict );
	}

	/**
	 * Callback for `register_deactivation_hook` and `register_uninstall_hook` to remove dictionary.
	 *
	 * @return void
	 */
	public static function remove_dictionary() {
		delete_option( self::OPTION_NAME );
	}
}

add_action( 'plugins_loaded', array( 'WP_DPP\Censor_Bad_Words', 'get_instance' ) );
