<?php
/**
 * Install Module
 * 
 * @since 2.5
 */

if (class_exists('SL_Module')) {

define('SL_DOWNGRADE_LIMIT', '5.0');

class SL_Install extends SL_Module {
	
	static function get_parent_module() { return 'settings'; }
	static function get_child_order() { return 20; }
	static function is_independent_module() { return false; }
	
	// static function get_module_title() { return __('Upgrade/Downgrade/Reinstall', 'seolat-tool-plus'); }
	static function get_menu_title() { return __('Installer', 'seolat-tool-plus'); }
	
	function get_admin_page_tabs() {
		
		$tabs = array();
		
		if ($this->current_user_can_upgrade())
			$tabs[] = array('title' => __('Upgrade', 'seolat-tool-plus'), 'id' => 'sl-upgrade', 'callback' => 'upgrade_tab');
		
		if ($this->current_user_can_downgrade())
			$tabs[] = array('title' => __('Downgrade', 'seolat-tool-plus'), 'id' => 'sl-downgrade', 'callback' => 'downgrade_tab');
		
		if ($this->current_user_can_reinstall())
			$tabs[] = array('title' => __('Reinstall', 'seolat-tool-plus'), 'id' => 'sl-reinstall', 'callback' => 'reinstall_tab');
		
		if (count($tabs))
			return $tabs;
		
		return false;
	}
	
	function belongs_in_admin($admin_scope = null) {
		
		if ($admin_scope === null)
			$admin_scope = lat_wp::get_admin_scope();
		
		if ( ! function_exists( 'is_plugin_active_for_network' ) )
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		
		switch ($admin_scope) {
			case 'blog':
				return !is_multisite() || !is_plugin_active_for_network($this->plugin->plugin_basename);
				break;
			case 'network':
				return is_plugin_active_for_network($this->plugin->plugin_basename);
				break;
			default:
				return false;
				break;
		}
	}
	
	function current_user_can_upgrade() {
		return false;
		return current_user_can('update_plugins') && (!is_multisite() || is_super_admin());
	}
	
	function current_user_can_downgrade() {
		return false;
		return current_user_can('install_plugins') && (!is_multisite() || is_super_admin());
	}
	
	function current_user_can_reinstall() {
		return false;
		return current_user_can('install_plugins') && (!is_multisite() || is_super_admin());
	}
	
	function init() {
		if ($this->is_action('update')) {
			add_filter('sl_custom_admin_page-settings', array(&$this, 'do_installation'));
		}
	}
	
	function upgrade_tab() {
		
		if (!$this->current_user_can_upgrade()) {
			$this->print_message('error', __('You do not have sufficient permissions to upgrade plugins on this site.', 'seolat-tool-plus'));
			return;
		}
		
		$radiobuttons = $this->get_version_radiobuttons(SL_VERSION, false);
		if (is_array($radiobuttons)) {
			if (count($radiobuttons) > 1) {
				
				echo "\n<p>";
				_e('From the list below, select the version to which you would like to upgrade. Then click the &#8220;Upgrade&#8221; button at the bottom of the screen.', 'seolat-tool-plus');
				echo "</p>\n";
				
				echo "<div class='sl-xgrade'>\n";
				$this->admin_form_start();
				$this->radiobuttons('version', $radiobuttons);
				$this->admin_form_end(__('Upgrade', 'seolat-tool-plus'));
				echo "</div>\n";
			} else
				$this->print_message('success', __('You are already running the latest version.', 'seolat-tool-plus'));
		} else
			$this->print_message('error', __('There was an error retrieving the list of available versions. Please try again later. You can also upgrade to the latest version of SEOLAT Tool Plus using the WordPress plugin upgrader.', 'seolat-tool-plus'));
	}
	
	function downgrade_tab() {
		
		if (!$this->current_user_can_downgrade()) {
			$this->print_message('error', __('You do not have sufficient permissions to downgrade plugins on this site.', 'seolat-tool-plus'));
			return;
		}
		
		$radiobuttons = $this->get_version_radiobuttons(SL_DOWNGRADE_LIMIT, SL_VERSION, 5);
		if (is_array($radiobuttons)) {
			if (count($radiobuttons) > 1) {
				
				$this->print_message('warning', lat_wp::add_backup_url(__('Downgrading is provided as a convenience only and is not officially supported. Although unlikely, you may lose data in the downgrading process. It is your responsibility to backup your database before proceeding.', 'seolat-tool-plus')));
				
				echo "\n<p>";
				_e('From the list below, select the version to which you would like to downgrade. Then click the &#8220;Downgrade&#8221; button at the bottom of the screen.', 'seolat-tool-plus');
				echo "</p>\n";
				
				echo "<div class='sl-xgrade'>\n";
				$this->admin_form_start();
				$this->radiobuttons('version', $radiobuttons);
				$this->admin_form_end(__('Downgrade', 'seolat-tool-plus'));
				echo "</div>\n";
			} else
				$this->print_message('warning', sprintf(__('Downgrading to versions earlier than %s is not supported because doing so will result the loss of some or all of your SEOLAT Tool Plus settings.', 'seolat-tool-plus'), SL_DOWNGRADE_LIMIT));
		} else
			$this->print_message('error', __('There was an error retrieving the list of available versions. Please try again later.', 'seolat-tool-plus'));
	}
	
	function reinstall_tab() {
		
		if (!$this->current_user_can_reinstall()) {
			$this->print_message('error', __('You do not have sufficient permissions to reinstall plugins on this site.', 'seolat-tool-plus'));
			return;
		}
		
		echo "\n<p>";
		_e('To download and install a fresh copy of the SEOLAT Tool Plus version you are currently using, click the &#8220;Reinstall&#8221; button below.', 'seolat-tool-plus');
		echo "</p>\n";
		
		$this->admin_form_start(false, false);
		echo "<input type='hidden' name='version' id='version' value='".sl_esc_attr(SL_VERSION)."' />\n";
		$this->admin_form_end(__('Reinstall', 'seolat-tool-plus'), false);
	}
	
	function get_version_radiobuttons($min, $max, $limit=false) {
		
		$this->update_setting('version', SL_VERSION);
		
		$versions = $this->plugin->download_changelog();
		
		if (is_array($versions) && count($versions)) {
			
			$radiobuttons = array();
			$i = 0;
			foreach ($versions as $title => $changes) {
				if (preg_match('|Version ([0-9.]{3,9}) |', $title, $matches)) {
					$version = $matches[1];
					
					if ($max && version_compare($version, $max, '>')) continue;
					if ($min && version_compare($version, $min, '<')) break;
					
					$changes = wptexturize($changes);
					if ($version == SL_VERSION)
						$message = __('Your Current Version', 'seolat-tool-plus');
					elseif (0 == $i)
						$message = __('Latest Version', 'seolat-tool-plus');
					else
						$message = '';
					if ($message) $message = " &mdash; <em>$message</em>";
					
					$radiobuttons[$version] = "<strong>$title</strong>$message</label>\n$changes\n";
					
					if ($limit !== false && $limit > 0 && ++$i >= $limit) break;
				}
			}
			
			return $radiobuttons;
		}
		
		return false; //Error
	}
	
	function do_installation() {
		
		if (!isset($_POST['version'])) return false;
		
		$nv = lat_string::preg_filter('0-9a-zA-Z .', $_POST['version']);
		if (!strlen($nv)) return false;
		
		//Don't allow downgrading to anything below the minimum limit
		if (version_compare(SL_DOWNGRADE_LIMIT, $nv, '>')) return;
		
		switch (version_compare($nv, SL_VERSION)) {
			case -1: //Downgrade
				$title = __('Downgrade to SEOLAT Tool Plus %s', 'seolat-tool-plus');
				
				if (!$this->current_user_can_downgrade()) {
					wp_die(__('You do not have sufficient permissions to downgrade plugins on this site.', 'seolat-tool-plus'));
					return;
				}
				
				break;
			case 0: //Reinstall
				$title = __('Reinstall SEOLAT Tool Plus %s', 'seolat-tool-plus');
				
				if (!$this->current_user_can_reinstall()) {
					wp_die(__('You do not have sufficient permissions to reinstall plugins on this site.', 'seolat-tool-plus'));
					return;
				}
				
				break;
			case 1: //Upgrade
				$title = __('Upgrade to SEOLAT Tool Plus %s', 'seolat-tool-plus');
				
				if (!$this->current_user_can_upgrade()) {
					wp_die(__('You do not have sufficient permissions to upgrade plugins on this site.', 'seolat-tool-plus'));
					return;
				}
				
				break;
			default:
				return;
		}
		
		$title = sprintf($title, $nv);
		$nonce = 'sl-install-plugin';
		$plugin = 'seolat-tool-plus/seolat-tool-plus.php';
		$url = 'update.php?action=upgrade-plugin&plugin='.$plugin;
		
		include_once $this->plugin->plugin_dir_path.'plugin/class.sl-installer.php';
		
		$upgrader = new SL_Installer( new SL_Installer_Skin( compact('title', 'nonce', 'url', 'plugin') ) );
		$upgrader->upgrade($plugin, SL_VERSION, $nv);
		
		return true;
	}
}

}
?>