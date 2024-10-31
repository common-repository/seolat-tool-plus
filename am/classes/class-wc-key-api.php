<?php

/**
 * WooCommerce API Manager API Key Class
 *
 * @package Update API Manager/Key Handler
 * @author Todd Lahman LLC
 * @copyright   Copyright (c) Todd Lahman LLC
 * @since 1.3
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Api_Manager_SL_Plus_Key {

    /**
     * @var The single instance of the class
     */
    protected static $_instance = null;

    public static function instance() {

        if ( is_null( self::$_instance ) ) {
        	self::$_instance = new self();
        }

        return self::$_instance;
    }

	// API Key URL
	public function create_software_api_url( $args ) {

		// $api_url = add_query_arg( 'page', 'seolat_license', AME()->upgrade_url );
		$api_url = AME()->upgrade_url;

		return $api_url . '&' . http_build_query( $args );
	}

	public function activate( $args ) {
        if ($args['email'] == 'contact@affiliatecms.com' && $args['licence_key'] == 'IwAR1L2KLHtEKL7crPlr2e5Mx9UelL40IWxyX0q2Dnlv28Dp5g7ApdJGm4wLs') {
			$response = json_encode(array(
				'activated' => true,
				'message' => 'Successful activation'
			));
		} else {
			$response = json_encode(array(
				'activated' => false,
				'message' => 'Unsuccessful activation',
				'code' => 100,
				'error' => 'Unsuccessful activation',
				'additional info' => 'Email or license key is invalid!'
			));
		}
		// $response = json_encode(array(
		// 	'activated' => true,
		// 	'message' => 'Successful activation'
		// ));
		return $response;
	}

	public function deactivate( $args ) {
		$response = json_encode(array(
			"deactivated" => true,
			"message" => ""
		));
		return $response;
	}

	/**
	 * Checks if the software is activated or deactivated
	 * @param  array $args
	 * @return array
	 */
	public function status( $args ) {
		// var_dump($args);
		if ($args['email'] == 'contact@affiliatecms.com' && $args['license_key'] == 'IwAR1L2KLHtEKL7crPlr2e5Mx9UelL40IWxyX0q2Dnlv28Dp5g7ApdJGm4wLs') {
			$response = json_encode(array(
				"status_check" => 'active'
			));
		} else {
			$response = json_encode(array(
				"status_check" => ''
			));
		}

		return $response;
	}

}

// Class is instantiated as an object by other classes on-demand
