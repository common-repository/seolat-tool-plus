<?php
/**
 * Class SL_301Rules
 * 
 * @since 2.2
 */

if (class_exists('SL_Module')) {

class SL_301Rules extends SL_Module {
	
	static function get_parent_module() { return '301s'; }
	static function get_child_order() { return 11; }
	static function is_independent_module() { return false; }
	
	static function get_module_title() { return __('Rules', 'seolat-tool-plus'); }
	function get_module_subtitle() { return __('Rules', 'seolat-tool-plus'); }
	
	function init() {
		$this->redirect();
		// add_action('init', array($this, 'redirect'), 0);

        // js scripts
		add_action( 'admin_enqueue_scripts', array($this, 'redirect_301s_add_js'));
		// ============================================================================

	}

	// add js script
	function redirect_301s_add_js($hook) {
        if ( 'seo_page_sl-301s' === $hook ) {
            wp_register_style('sl-301s-style', plugin_dir_url( __FILE__ ).'redirect-301s-style.css', array( 'sdf-bootstrap-admin', 'sdf-bootstrap-admin-theme' ), null, 'screen');
			wp_enqueue_style( 'sl-301s-style' );
        }
		wp_enqueue_script( 'sl-301s-script', plugin_dir_url( __FILE__ ).'redirect-301s-script.js', array('jquery'));
	}
		
	/**
	 * getAddress function
	 * utility function to get the full address of the current request
	 * credit: http://www.phpro.org/examples/Get-Full-URL.html
	 * @access public
	 * @return void
	 */
	function get_address() {
		// return the full address
		return $this->get_protocol().'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	} // end function get_address
	
	function get_protocol() {
		// Set the base protocol to http
		$protocol = 'http';
		// check for https
		if ( isset( $_SERVER["HTTPS"] ) && strtolower( $_SERVER["HTTPS"] ) == "on" ) {
			$protocol .= "s";
		}
		
		return $protocol;
	} // end function get_protocol
	
	/**
	 * redirect function
	 * Read the list of redirects and if the current page 
	 * is found in the list, send the visitor on her way
	 * @access public
	 * @return void
	 */
	function redirect() {
		// this is what the user asked for (strip out home portion, case insensitive)
		$userrequest = str_ireplace(get_option('home'),'',$this->get_address());
		$userrequest = rtrim($userrequest,'/');
		
		$redirects = get_option('301_redirects');

		$rules = $this->get_setting('rules', array());
		// var_dump($userrequest);
		// die();
		if ( count( $rules ) > 0 ) {
			$wildcard    = $this->get_setting('301_redirects_wildcard', false);
			$do_redirect = '';
			
			// compare user request to each 301 stored in the db
			foreach ($rules as $i => $rule) {
				$storedrequest = $rule['request'];
				$destination   = $rule['destination'];
				$disabled      = ( isset( $rule['disabled'] ) ) ? (bool)$rule['disabled'] : false;
				if ( ! $disabled ) {
					// check if we should use regex search 
					if ($wildcard === 'true' && strpos($storedrequest,'*') !== false) {
						// wildcard redirect
						
						// don't allow people to accidentally lock themselves out of admin
						if ( strpos($userrequest, '/wp-login') !== 0 && strpos($userrequest, '/wp-admin') !== 0 ) {
							// Make sure it gets all the proper decoding and rtrim action
							$storedrequest = str_replace('*','(.*)',$storedrequest);
							$pattern = '/^' . str_replace( '/', '\/', rtrim( $storedrequest, '/' ) ) . '/';
							$destination = str_replace('*','$1',$destination);
							$output = preg_replace($pattern, $destination, $userrequest);
							if ($output !== $userrequest) {
								// pattern matched, perform redirect
								$do_redirect = $output;
							}
						}
					} elseif(urldecode($userrequest) == rtrim($storedrequest,'/')) {
						// simple comparison redirect
						$do_redirect = $destination;
					}
				
					// redirect. the second condition here prevents redirect loops as a result of wildcards.
					if ($do_redirect !== '' && trim($do_redirect,'/') !== trim($userrequest,'/')) {
						// check if destination needs the domain prepended
						if (strpos($do_redirect,'/') === 0){
							$do_redirect = home_url().$do_redirect;
						}
						header ('HTTP/1.1 301 Moved Permanently');
						header ('Location: ' . $do_redirect);
						exit();
					}
				}
			}
		}
	} // end funcion redirect

	function admin_page_contents() {
        if ($this->is_action('update')) {
            $rules = array();
            // var_dump( $_POST['rules'] );
            $new_rules = ( isset( $_POST['rules'] ) && is_array( $_POST['rules'] ) ) ? $_POST['rules'] : array();
            foreach ( $new_rules as $i => $new_rule ) {
                if ( empty( $new_rule['request'] ) || empty( $new_rule['destination'] ) || ( isset( $new_rule['delete'] ) && (bool)$new_rule['delete'] ) ) {
                    unset($new_rules[$i]);
                }
            }
            $rules = array_values($new_rules);
			$this->update_setting('rules', $rules);

			$new_wildcard = ( isset( $_POST['301_redirects_wildcard'] ) ) ? (bool)$_POST['301_redirects_wildcard'] : false;
			$this->update_setting('301_redirects_wildcard', $new_wildcard);
			$wildcard = $new_wildcard;
        } else {
            $rules = $this->get_setting('rules', array());
			$wildcard = $this->get_setting('301_redirects_wildcard', false);
        }

        /** Create table list */
        // Add a blank row to prepare for new item.
        $rules[] = array(
            'request'     => '',
            'destination' => '',
            'disabled'    => false
        );
        $num_rules = count( $rules );

        //Set headers
		$headers = array();
		$headers['rule-request'] = __('Requests', 'seolat-tool-plus');
		$headers['rule-destination'] = __('Destination', 'seolat-tool-plus');
		$headers['rule-disabled'] = __('<input type="checkbox" class="check my-check" /> Disabled', 'seolat-tool-plus');
		$headers['rule-delete'] = __('<input type="checkbox" class="check my-check" /> Delete', 'seolat-tool-plus');
		
		//Begin table; output headers
		$this->admin_wftable_start($headers);
        for ( $i = 0; $i < $num_rules; $i++ ) {
            $rule  = $rules[$i];
            if ( ! isset( $rule['disabled'] ) ) {
                $rule['disabled'] = false;
            }
            $cells = array();

            $cells['rule-request'] = $this->get_input_element('textbox', "rules[{$i}][request]", $rule['request']);
            $cells['rule-destination'] = $this->get_input_element('textbox', "rules[{$i}][destination]", $rule['destination']);
            $cells['rule-disabled'] = $this->get_input_element('checkbox', "rules[{$i}][disabled]", $rule['disabled']);
            $cells['rule-delete'] = $this->get_input_element('checkbox', "rules[{$i}][delete]");


            $this->table_row($cells, $i, 'rules');
        }
        
		$this->admin_wftable_end();
		echo $this->get_input_element('checkbox', '301_redirects_wildcard', $wildcard, 'Use Wildcards?');
    }
		
}
}
?>
