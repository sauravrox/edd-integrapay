<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://easydigitaldownloads.com
 * @since      1.0.0
 *
 * @package    EDD_IntegraPay
 * @subpackage EDD_IntegraPay/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    EDD_IntegraPay
 * @subpackage EDD_IntegraPay/includes
 * @author     Easy Digital Downloads <https://easydigitaldownloads.com>
 */
class EDD_IntegraPay_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'edd-integrapay',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
