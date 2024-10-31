<?php
/*
Plugin Name: SEOLAT Tool Plus
Plugin URI: https://affiliatecms.com/seolat-tool-plus/
Description: This SEOLAT Tool Plus plugin gives you control over title tags, noindex/nofollow, meta tags, opengraph+, slugs, canonical tags, autolinks, 404 errors, rich snippets, and more.
Version: 2.2
Author: AffiliateCMS
Author URI: https://affiliatecms.com/
Text Domain: seolat-tool-plus
*/

/**
 * The main SEOLAT Tool Plus plugin file.
 * @package SEOLAT tool plus
 * @version 2.2
 * @link https://affiliatecms.com/ SEOLAT Tool Plus Homepage
 */

/*
Copyright (c) 2015 SEO Design Solutions

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	die();
}

/********** CONSTANTS **********/

//The bare minimum version of WordPress required to run without generating a fatal error.
//SEO LAT will refuse to run if activated on a lower version of WP.
define('SL_MINIMUM_WP_VER', '3.9');

//Reading plugin info from constants is faster than trying to parse it from the header above.
define('SL_PLUGIN_NAME', 'SEOLAT Tool Plus');
define('SL_PLUGIN_URI', 'https://affiliatecms.com/seolat-tool-plus/');
define('SL_VERSION', '2.2');
define('SL_AUTHOR', 'AffiliateCMS.com');
define('SL_AUTHOR_URI', 'https://affiliatecms.com/');
define('SL_USER_AGENT', 'Life of Technology/2.0.7');

/********** INCLUDES **********/

//Libraries
include 'includes/jlfunctions/jlfunctions.php';
include 'includes/jlwp/jlwp.php';

//Plugin files
include 'plugin/sl-constants.php';
include 'plugin/sl-functions.php';
include 'plugin/class.seolat-tool-plus.php';

//Module files
include 'modules/class.sl-module.php';
include 'modules/class.sl-importmodule.php';


/********** VERSION CHECK & INITIALIZATION **********/

global $wp_version;
if (version_compare($wp_version, SL_MINIMUM_WP_VER, '>=')) {
	global $seo_lat_tool_plus;
	$seo_lat_tool_plus = new SEO_LAT_Tool_Plus(__FILE__);
} else {
	add_action('admin_notices', 'sl_wp_incompat_notice');
}

function sl_wp_incompat_notice() {
	echo '<div class="error"><p>';
	printf(__('SEOLAT Tool Plus requires WordPress %s or above. Please upgrade to the latest version of WordPress to enable SEO LAT on your blog, or deactivate SEO LAT to remove this notice.', 'seolat-tool-plus'), SL_MINIMUM_WP_VER);
	echo "</p></div>\n";
}

/**
 * Displays an inactive message if the API License Key has not yet been activated
 */
if ( get_option( 'api_manager_sl_plus_activated' ) != 'Activated' ) {
    add_action( 'admin_notices', 'API_Manager_SL_Plus::am_sl_plus_inactive_notice' );
		if ( isset( $_GET['page'] ) ) {
			$current = (isset($_GET['page'])) ? $_GET['page'] : '';
			$seo_admin_pages = array('seo', 'sl-fofs', 'sl-misc', 'sl-alt-attribute', 'sl-user-code-plus', 'sl-user-code', 'sl-autolinks', 'sl-files', 'sl-internal-link-aliases', 'sl-meta-descriptions', 'sl-meta-keywords', 'sl-meta-robots', 'sl-opengraph', 'seolat-tool-plus', 'sl-wp-settings', 'sl-titles', 
			'sl-sds-blog', 
			'sl-sitemap');
			if( in_array( $current, $seo_admin_pages)) {
				add_action( 'admin_menu', 'API_Manager_SL_Plus::am_sl_plus_redirect' );
			}
		}
}

class API_Manager_SL_Plus {

	/**
	 * Self Upgrade Values
	 */
	// Base URL to the remote upgrade API Manager server. If not set then the Author URI is used.
	public $upgrade_url = 'https://tools.lat.vn/p_api/SEOLATToolPlus';

	/**
	 * @var string
	 */
	public $version = SL_VERSION;

	/**
	 * @var string
	 * This version is saved after an upgrade to compare this db version to $version
	 */
	public $api_manager_sl_plus_version_name = 'plugin_api_manager_sl_plus_version';

	/**
	 * @var string
	 */
	public $plugin_url;

	/**
	 * @var string
	 * used to defined localization for translation, but a string literal is preferred
	 *
	 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/issues/59
	 * http://markjaquith.wordpress.com/2011/10/06/translating-wordpress-plugins-and-themes-dont-get-clever/
	 * http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/
	 */
	public $text_domain = 'seolat-tool-plus';

	/**
	 * Data defaults
	 * @var mixed
	 */
	private $am_sl_plus_software_product_id;

	public $am_sl_plus_data_key;
	public $am_sl_plus_api_key;
	public $am_sl_plus_activation_email;
	public $am_sl_plus_product_id_key;
	public $am_sl_plus_instance_key;
	public $am_sl_plus_deactivate_checkbox_key;
	public $am_sl_plus_activated_key;

	public $am_sl_plus_deactivate_checkbox;
	public $am_sl_plus_activation_tab_key;
	public $am_sl_plus_deactivation_tab_key;
	public $am_sl_plus_settings_menu_title;
	public $am_sl_plus_settings_title;
	public $am_sl_plus_menu_tab_activation_title;
	public $am_sl_plus_menu_tab_deactivation_title;

	public $am_sl_plus_options;
	public $am_sl_plus_plugin_name;
	public $am_sl_plus_product_id;
	public $am_sl_plus_renew_license_url;
	public $am_sl_plus_instance_id;
	public $am_sl_plus_domain;
	public $am_sl_plus_software_version;
	public $am_sl_plus_plugin_or_theme;

	public $am_sl_plus_update_version;

	public $am_sl_plus_update_check = 'am_sl_plus_plugin_update_check';

	/**
	 * Used to send any extra information.
	 * @var mixed array, object, string, etc.
	 */
	public $am_sl_plus_extra;

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

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.2
	 */
	private function __clone() {}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.2
	 */
	private function __wakeup() {}

	public function __construct() {

		// Run the activation function
		register_activation_hook( __FILE__, array( $this, 'activation' ) );

		if ( is_admin() ) {

			// Check for external connection blocking
			add_action( 'admin_notices', array( $this, 'check_external_blocking' ) );

			/**
			 * Software Product ID is the product title string
			 * This value must be unique, and it must match the API tab for the product in WooCommerce
			 */
			$this->am_sl_plus_software_product_id = SL_PLUGIN_NAME;

			/**
			 * Set all data defaults here
			 */
			$this->am_sl_plus_data_key 				= 'api_manager_sl_plus';
			$this->am_sl_plus_api_key 					= 'api_key';
			$this->am_sl_plus_activation_email 		= 'activation_email';
			$this->am_sl_plus_product_id_key 			= 'api_manager_sl_plus_product_id';
			$this->am_sl_plus_instance_key 			= 'api_manager_sl_plus_instance';
			$this->am_sl_plus_deactivate_checkbox_key 	= 'api_manager_sl_plus_deactivate_checkbox';
			$this->am_sl_plus_activated_key 			= 'api_manager_sl_plus_activated';

			/**
			 * Set all admin menu data
			 */
			$this->am_sl_plus_deactivate_checkbox 			= 'am_sl_plus_deactivate_example_checkbox';
			$this->am_sl_plus_activation_tab_key 			= 'api_manager_sl_plus_dashboard';
			$this->am_sl_plus_deactivation_tab_key 		= 'api_manager_sl_plus_deactivation';
			$this->am_sl_plus_settings_menu_title 			= 'SEOLAT Tool Plus API License';
			$this->am_sl_plus_settings_title 				= 'SEOLAT Tool Plus API License';
			$this->am_sl_plus_menu_tab_activation_title 	= __( 'License Activation', 'seolat-tool-plus' );
			// $this->am_sl_plus_menu_tab_deactivation_title 	= __( 'License Deactivation', 'seolat-tool-plus' );

			/**
			 * Set all software update data here
			 */
			$this->am_sl_plus_options 				= get_option( $this->am_sl_plus_data_key );
			$this->am_sl_plus_plugin_name 			= untrailingslashit( plugin_basename( __FILE__ ) ); // same as plugin slug. if a theme use a theme name like 'twentyeleven'
			$this->am_sl_plus_product_id 			= get_option( $this->am_sl_plus_product_id_key ); // Software Title
			$this->am_sl_plus_renew_license_url 	= 'https://tools.lat.vn/client'; // URL to renew a license. Trailing slash in the upgrade_url is required.
			$this->am_sl_plus_instance_id 			= get_option( $this->am_sl_plus_instance_key ); // Instance ID (unique to each blog activation)
			/**
			 * Some web hosts have security policies that block the : (colon) and // (slashes) in http://,
			 * so only the host portion of the URL can be sent. For example the host portion might be
			 * www.example.com or example.com. http://www.example.com includes the scheme http,
			 * and the host www.example.com.
			 * Sending only the host also eliminates issues when a client site changes from http to https,
			 * but their activation still uses the original scheme.
			 * To send only the host, use a line like the one below:
			 *
			 * $this->am_sl_plus_domain = str_ireplace( array( 'http://', 'https://' ), '', home_url() ); // blog domain name
			 */
			$this->am_sl_plus_domain 				= str_ireplace( array( 'http://', 'https://' ), '', home_url() ); // blog domain name
			$this->am_sl_plus_software_version 	= $this->version; // The software version
			$this->am_sl_plus_plugin_or_theme 		= 'plugin'; // 'theme' or 'plugin'

			// Performs activations and deactivations of API License Keys
			require_once( plugin_dir_path( __FILE__ ) . 'am/classes/class-wc-key-api.php' );

			// Checks for software updatess
			require_once( plugin_dir_path( __FILE__ ) . 'am/classes/class-wc-plugin-update.php' );

			// Admin menu with the license key and license email form
			require_once( plugin_dir_path( __FILE__ ) . 'am/admin/class-wc-api-manager-menu.php' );

			$options = get_option( $this->am_sl_plus_data_key );

			/**
			 * Check for software updates
			 */
			if ( ! empty( $options ) && $options !== false ) {

				$this->update_check(
					$this->upgrade_url,
					$this->am_sl_plus_plugin_name,
					$this->am_sl_plus_product_id,
					$this->am_sl_plus_options[$this->am_sl_plus_api_key],
					$this->am_sl_plus_options[$this->am_sl_plus_activation_email],
					$this->am_sl_plus_renew_license_url,
					$this->am_sl_plus_instance_id,
					$this->am_sl_plus_domain,
					$this->am_sl_plus_software_version,
					$this->am_sl_plus_plugin_or_theme,
					$this->text_domain
					);

			}

		}

		/**
		 * Deletes all data if plugin deactivated
		 */
		register_deactivation_hook( __FILE__, array( $this, 'uninstall' ) );

	}

	/** Load Shared Classes as on-demand Instances **********************************************/

	/**
	 * API Key Class.
	 *
	 * @return Api_Manager_SL_Plus_Key
	 */
	public function key() {
		return Api_Manager_SL_Plus_Key::instance();
	}

	/**
	 * Update Check Class.
	 *
	 * @return API_Manager_SL_Plus_Update_API_Check
	 */
	public function update_check( $upgrade_url, $plugin_name, $product_id, $api_key, $activation_email, $renew_license_url, $instance, $domain, $software_version, $plugin_or_theme, $text_domain, $extra = '' ) {

		return API_Manager_SL_Plus_Update_API_Check::instance( $upgrade_url, $plugin_name, $product_id, $api_key, $activation_email, $renew_license_url, $instance, $domain, $software_version, $plugin_or_theme, $text_domain, $extra );
	}

	public function plugin_url() {
		if ( isset( $this->plugin_url ) ) {
			return $this->plugin_url;
		}

		return $this->plugin_url = plugins_url( '/', __FILE__ );
	}

	/**
	 * Generate the default data arrays
	 */
	public function activation() {

		$global_options = array(
			$this->am_sl_plus_api_key 				=> '',
			$this->am_sl_plus_activation_email 	=> '',
					);

		update_option( $this->am_sl_plus_data_key, $global_options );
		$single_options = array(
			$this->am_sl_plus_product_id_key 			=> $this->am_sl_plus_software_product_id,
			$this->am_sl_plus_instance_key 			=> wp_generate_password( 12, false ),
			$this->am_sl_plus_deactivate_checkbox_key 	=> 'on',
			$this->am_sl_plus_activated_key 			=> 'Deactivated',
			);

		foreach ( $single_options as $key => $value ) {
			update_option( $key, $value );
		}

		$curr_ver = get_option( $this->api_manager_sl_plus_version_name );

		// checks if the current plugin version is lower than the version being installed
		if ( version_compare( $this->version, $curr_ver, '>' ) ) {
			// update the version
			update_option( $this->api_manager_sl_plus_version_name, $this->version );
		}

	}

	/**
	 * Deletes all data if plugin deactivated
	 * @return void
	 */
	public function uninstall() {
		global $blog_id;

		$this->license_key_deactivation();

		// Remove options
		if ( is_multisite() ) {

			switch_to_blog( $blog_id );

			foreach ( array(
					$this->am_sl_plus_data_key,
					$this->am_sl_plus_product_id_key,
					$this->am_sl_plus_instance_key,
					$this->am_sl_plus_deactivate_checkbox_key,
					$this->am_sl_plus_activated_key,
					) as $option) {

					delete_option( $option );

					}

			restore_current_blog();

		} else {

			foreach ( array(
					$this->am_sl_plus_data_key,
					$this->am_sl_plus_product_id_key,
					$this->am_sl_plus_instance_key,
					$this->am_sl_plus_deactivate_checkbox_key,
					$this->am_sl_plus_activated_key
					) as $option) {

					delete_option( $option );

					}

		}

	}

	/**
	 * Deactivates the license on the API server
	 * @return void
	 */
	public function license_key_deactivation() {

		$activation_status = get_option( $this->am_sl_plus_activated_key );

		$api_email = $this->am_sl_plus_options[$this->am_sl_plus_activation_email];
		$api_key = $this->am_sl_plus_options[$this->am_sl_plus_api_key];

		$args = array(
			'email' => $api_email,
			'licence_key' => $api_key,
			);

		if ( $activation_status == 'Activated' && $api_key != '' && $api_email != '' ) {
			$this->key()->deactivate( $args ); // reset license key activation
		}
	}

    /**
     * Displays an inactive notice when the software is inactive.
     */
	public static function am_sl_plus_inactive_notice() { ?>
		<?php if ( ! current_user_can( 'manage_options' ) ) return; ?>
		<?php if ( isset( $_GET['page'] ) && 'api_manager_sl_plus_dashboard' == $_GET['page'] ) return; ?>
		<div id="message" class="error">
			<p><?php printf( __( 'The SEOLAT Tool Plus API License Key has not been activated, so the plugin is inactive! %sClick here%s to activate the license key and the plugin.', 'seolat-tool-plus' ), '<a href="' . esc_url( admin_url( 'options-general.php?page=api_manager_sl_plus_dashboard' ) ) . '">', '</a>' ); ?></p>
		</div>
		<?php
	}

    /**
     * Redirects when the software is inactive.
     */
	public static function am_sl_plus_redirect() {
		wp_redirect( admin_url('options-general.php?page=api_manager_sl_plus_dashboard') );
	}

	/**
	 * Check for external blocking contstant
	 * @return string
	 */
	public function check_external_blocking() {
		// show notice if external requests are blocked through the WP_HTTP_BLOCK_EXTERNAL constant
		if( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL === true ) {

			// check if our API endpoint is in the allowed hosts
			$host = parse_url( $this->upgrade_url, PHP_URL_HOST );

			if( ! defined( 'WP_ACCESSIBLE_HOSTS' ) || stristr( WP_ACCESSIBLE_HOSTS, $host ) === false ) {
				?>
				<div class="error">
					<p><?php printf( __( '<b>Warning!</b> You\'re blocking external requests which means you won\'t be able to get %s updates. Please add %s to %s.', 'seolat-tool-plus' ), $this->am_sl_plus_software_product_id, '<strong>' . $host . '</strong>', '<code>WP_ACCESSIBLE_HOSTS</code>'); ?></p>
				</div>
				<?php
			}

		}
	}

} // End of class

function AME() {
    return API_Manager_SL_Plus::instance();
}

// Initialize the class instance only once
AME();
// Omit closing PHP tag to avoid "Headers already sent" issues.
