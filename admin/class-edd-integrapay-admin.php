<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://easydigitaldownloads.com
 * @since      1.0.0
 *
 * @package    EDD_IntegraPay
 * @subpackage EDD_IntegraPay/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    EDD_IntegraPay
 * @subpackage EDD_IntegraPay/admin
 * @author     Easy Digital Downloads <https://easydigitaldownloads.com>
 */
class EDD_IntegraPay_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}


	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
    public function gateway_settings( $gateway_settings ) {

        $default_settings = array(
            'edd_integrapay' => array(
                'id'   => 'edd_integrapay',
                'name' => '<strong>' . __( 'IntegraPay Settings', 'edd-integrapay' ) . '</strong>',
                'type' => 'header',
            ),
            'integrapay_username' => array(
                'id'   => 'integrapay_username',
                'name' => __( 'Username', 'edd-integrapay' ),
                'desc' => '',
                'type' => 'text',
                'size' => 'regular',
            ),
            'integrapay_password' => array(
                'id'   => 'integrapay_password',
                'name' => __( 'Password', 'edd-integrapay' ),
                'desc' => '',
                'type' => 'text',
                'size' => 'regular',
            ),
            'integrapay_business' => array(
                'id'   => 'integrapay_business',
                'name' => __( 'Business Id', 'edd-integrapay' ),
                'desc' => '',
                'type' => 'text',
                'size' => 'regular',
            ),
        );

        $default_settings    = apply_filters( 'wpas_edd_default_integrapay_settings', $default_settings );
        $gateway_settings['edd_integrapay'] = $default_settings;

        return $gateway_settings;
    }

    /**
     * Register the payment gateway
     *
     * @since  1.0.0
     * @param  array $gateways Array of payment gateways
     * @return array
     */

    function edd_integrapay_register_gateway( $gateways ) {
        $gateways['edd_integrapay'] = array(
            'admin_label'    => 'IntegraPay',
            'checkout_label' => __( 'Credit Card', 'edds' ),
            'supports'       => array(
                'buy_now'
            )
        );
        return $gateways;
    }

    /**
     * Register the payment gateways setting section
     *
     * @since  1.0.0
     * @param  array $gateway_sections Array of sections for the gateways tab
     * @return array                   Added PayUmoney Payments into sub-sections
     */
    function edd_inegrapay_register_gateway_section($gateway_sections){
        $gateway_sections['edd_integrapay'] = __( 'IntegraPay', 'edd-integrapay' );

        return $gateway_sections;
    }
    
}
