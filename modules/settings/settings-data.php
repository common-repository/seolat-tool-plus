<?php
/**
 * Settings Data Manager Module
 * 
 * @since 2.1
 */

if (class_exists('SL_Module')) {

class SL_SettingsData extends SL_Module {

	static function get_parent_module() { return 'settings'; }
	static function get_child_order() { return 20; }
	static function is_independent_module() { return false; }
	
	static function get_module_title() { return __('Settings Data Manager', 'seolat-tool-plus'); }
	function get_module_subtitle() { return __('Manage Settings Data', 'seolat-tool-plus'); }
	
	function get_admin_page_tabs() {
		return array(
			  array('title' => __('Import', 'seolat-tool-plus'), 'id' => 'sl-import', 'callback' => 'import_tab')
			, array('title' => __('Export', 'seolat-tool-plus'), 'id' => 'sl-export', 'callback' => 'export_tab')
			, array('title' => __('Reset', 'seolat-tool-plus'),  'id' => 'sl-reset',  'callback' => 'reset_tab')
		);
	}
	
	function portable_options() {
		return array('settings', 'modules');
	}
	
	function init() {
		
		if ($this->is_action('sl-export')) {
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="SEOLAT Tool Plus Settings ('.date('Y-m-d').').dat"');
			
			$export = array();
			
			$psdata = (array)get_option('seo_lat_tool_plus', array());
			
			//Module statuses
			$export['modules'] = apply_filters('sl_modules_export_array', $psdata['modules']);
			
			//Module settings
			$modules = array_keys($psdata['modules']);
			$module_settings = array();
			foreach($modules as $module) {
				if (!$this->plugin->call_module_func($module, 'get_settings_key', $key) || !$key)
					$key = $module;
				
				$msdata = (array)get_option("seo_lat_tool_plus_module_$key", array());
				if ($msdata) $module_settings[$key] = $msdata;
			}
			$export['settings'] = apply_filters('sl_settings_export_array', $module_settings);
			
			//Encode
			$export = base64_encode(serialize($export));
			
			//Output
			echo $export;
			die();
			
		} elseif ($this->is_action('sl-import')) {
			
			if (strlen($_FILES['settingsfile']['name'])) {
				
				$file = $_FILES['settingsfile']['tmp_name'];			
				if (is_uploaded_file($file)) {
					$import = base64_decode(file_get_contents($file));
					if (is_serialized($import)) {
						$import = unserialize($import);
						
						//Module statuses
						$psdata = (array)get_option('seo_lat_tool_plus', array());
						$psdata['modules'] = array_merge($psdata['modules'], $import['modules']);
						update_option('seo_lat_tool_plus', $psdata);
						
						//Module settings
						$module_settings = apply_filters('sl_settings_import_array', $import['settings']);
						foreach ($module_settings as $key => $module_settings) {
							$msdata = (array)get_option("seo_lat_tool_plus_module_$key", array());
							$msdata = array_merge($msdata, $module_settings);
							update_option("seo_lat_tool_plus_module_$key", $msdata);							
						}
						
						$this->queue_message('success', __('Settings successfully imported.', 'seolat-tool-plus'));
					} else
						$this->queue_message('error', __('The uploaded file is not in the proper format. Settings could not be imported.', 'seolat-tool-plus'));
				} else
					$this->queue_message('error', __('The settings file could not be uploaded successfully.', 'seolat-tool-plus'));
					
			} else
				$this->queue_message('warning', __('Settings could not be imported because no settings file was selected. Please click the &#8220;Browse&#8221; button and select a file to import.', 'seolat-tool-plus'));
			
		} elseif ($this->is_action('sl-reset')) {
			
			$psdata = (array)get_option('seo_lat_tool_plus', array());
			$modules = array_keys($psdata['modules']);
			foreach ($modules as $module) {
				
				if (!$this->plugin->call_module_func($module, 'get_settings_key', $key) || !$key)
					$key = $module;
				
				delete_option("seo_lat_tool_plus_module_$key");
			}
			unset($psdata['modules']);
			update_option('seo_lat_tool_plus', $psdata);
			
			$this->load_default_settings();
			
		} elseif ($this->is_action('dj-export')) {
			header('Content-Disposition: attachment; filename="Deeplink Juggernaut Content Links ('.date('Y-m-d').').csv"');
			
			$djlinks = $this->get_setting('links', array(), 'autolinks');
			$csv_headers = array(
				  'anchor' => 'Anchor'
				, 'to_type' => 'Destination Type'
				, 'to_id' => 'Destination'
				, 'title' => 'Title'
				, 'sitewide_lpa' => 'Site Cap'
				, 'nofollow' => 'Nofollow'
				, 'target' => 'Target'
			);
			if (is_array($djlinks) && count($djlinks))
				$djlinks = lat_array::key_replace($djlinks, $csv_headers, true, true);
			else
				$djlinks = array(array_fill_keys($csv_headers, ''));
			
			lat_io::export_csv($djlinks);
			die();
			
		} elseif ($this->is_action('dj-import')) {
			
			if (strlen($_FILES['settingsfile']['name'])) {
			
				$file = $_FILES['settingsfile']['tmp_name'];			
				if (is_uploaded_file($file)) {
					$import = lat_io::import_csv($file);
					if ($import === false)
						$this->queue_message('error', __('The uploaded file is not in the proper format. Links could not be imported.', 'seolat-tool-plus'));
					else {
						$import = lat_array::key_replace($import, array(
							  'Anchor' => 'anchor'
							, 'Destination Type' => 'to_type'
							, 'Destination' => 'to_id'
							, 'URL' => 'to_id'
							, 'Title' => 'title'
							, 'Site Cap' => 'sidewide_lpa'
							, 'Nofollow' => 'nofollow'
							, 'Target' => 'target'
						), true, true);
						$import = lat_array::value_replace($import, array(
							  'No' => false
							, 'Yes' => true
							, 'URL' => 'url'
						), true, false);
						
						$djlinks = array();
						foreach ($import as $link) {
							
							//Validate destination type
							if ($link['to_type'] != 'url'
									&& !lat_string::startswith($link['to_type'], 'posttype_')
									&& !lat_string::startswith($link['to_type'], 'taxonomy_'))
								$link['to_type'] = 'url';
							
							//Validate nofollow
							if (!is_bool($link['nofollow']))
								$link['nofollow'] = false;
							
							//Validate target
							$link['target'] = ltrim($link['target'], '_');
							if (!in_array($link['target'], array('self', 'blank'))) //Only _self or _blank are supported  right now
								$link['target'] = 'self';
							
							//Add link!
							$djlinks[] = $link;
						}
						
						$this->update_setting('links', $djlinks, 'autolinks');
						
						$this->queue_message('success', __('Links successfully imported.', 'seolat-tool-plus'));
					}	
				} else
					$this->queue_message('error', __('The CSV file could not be uploaded successfully.', 'seolat-tool-plus'));
					
			} else
				$this->queue_message('warning', __('Links could not be imported because no CSV file was selected. Please click the &#8220;Browse&#8221; button and select a file to import.', 'seolat-tool-plus'));
			
		}
	}
	
	function import_tab() {
		$this->print_messages();
		$hook = $this->plugin->key_to_hook($this->get_module_or_parent_key());
		
		//SEOLAT Tool Plus
		$this->admin_subheader(__('Import SEOLAT Tool Plus Settings File', 'seolat-tool-plus'));
		echo "\n<p>";
		_e('You can use this form to upload and import an SEOLAT Tool Plus settings file stored on your computer. (These files can be created using the Export tool.) Note that importing a file will overwrite your existing settings with those in the file.', 'seolat-tool-plus');
		echo "</p>\n";
		echo "<form enctype='multipart/form-data' method='post' action='?page=$hook&amp;action=sl-import#sl-import'>\n";
		echo "\t<input name='settingsfile' type='file' /> ";
		$confirm = __('Are you sure you want to import this settings file? This will overwrite your current settings and cannot be undone.', 'seolat-tool-plus');
		echo "<input type='submit' class='button-primary' value='".__('Import Settings File', 'seolat-tool-plus')."' onclick=\"javascript:return confirm('$confirm')\" />\n";
		wp_nonce_field($this->get_nonce_handle('sl-import'));
		echo "</form>\n";
		
		if ($this->plugin->module_exists('content-autolinks')) {
			//Deeplink Juggernaut
			$this->admin_subheader(__('Import Deeplink Juggernaut CSV File', 'seolat-tool-plus'));
			echo "\n<p>";
			_e('You can use this form to upload and import a Deeplink Juggernaut CSV file stored on your computer. (These files can be created using the Export tool.) Note that importing a file will overwrite your existing links with those in the file.', 'seolat-tool-plus');
			echo "</p>\n";
			echo "<form enctype='multipart/form-data' method='post' action='?page=$hook&amp;action=dj-import#sl-import'>\n";
			echo "\t<input name='settingsfile' type='file' /> ";
			$confirm = __('Are you sure you want to import this CSV file? This will overwrite your current Deeplink Juggernaut links and cannot be undone.', 'seolat-tool-plus');
			echo "<input type='submit' class='button-primary' value='".__('Import CSV File', 'seolat-tool-plus')."' onclick=\"javascript:return confirm('$confirm')\" />\n";
			wp_nonce_field($this->get_nonce_handle('dj-import'));
			echo "</form>\n";
		}
		
		//Import from other plugins
		$importmodules = array();
		foreach ($this->plugin->modules as $key => $x_module) {
			$module =& $this->plugin->modules[$key];
			if (is_a($module, 'SL_ImportModule')) {
				$importmodules[$key] =& $module;
			}
		}
		
		if (count($importmodules)) {
			$this->admin_subheader(__('Import from Other Plugins', 'seolat-tool-plus'));
			echo "\n<p>";
			_e('You can import settings and data from these plugins. Clicking a plugin&#8217;s name will take you to the importer page, where you can customize parameters and start the import.', 'seolat-tool-plus');
			echo "</p>\n";
			echo "<table class='table table-bordered'>\n";
			
			$class = '';
			foreach ($importmodules as $key => $x_module) {
				$module =& $importmodules[$key];
				$title = $module->get_op_title();
				$desc = $module->get_import_desc();
				$url = $module->get_admin_url();
				$class = ($class) ? '' : 'alternate';
				echo "\t<tr class='$class'><td><a href='$url'>$title</a></td><td>$desc</td></tr>\n";
			}
			
			echo "</table>\n";
		}
	}
	
	function export_tab() {
		//SEOLAT Tool Plus
		$this->admin_subheader(__('Export SEOLAT Tool Plus Settings File', 'seolat-tool-plus'));
		echo "\n<p>";
		_e('You can use this export tool to download an SEOLAT Tool Plus settings file to your computer.', 'seolat-tool-plus');
		echo "</p>\n<p>";
		_e('A settings file includes the data of every checkbox and textbox of every installed module. It does NOT include site-specific data like logged 404s or post/page title/meta data (this data would be included in a standard database backup, however).', 'seolat-tool-plus');
		echo "</p>\n<p>";
		$url = $this->get_nonce_url('sl-export');
		echo "<a href='$url' class='button-primary'>".__('Download Settings File', 'seolat-tool-plus')."</a>";
		echo "</p>\n";
		
		if ($this->plugin->module_exists('content-autolinks')) {
			//Deeplink Juggernaut
			$this->admin_subheader(__('Export Deeplink Juggernaut CSV File', 'seolat-tool-plus'));
			echo "\n<p>";
			_e('You can use this export tool to download a CSV file (comma-separated values file) that contains your Deeplink Juggernaut links. Once you download this file to your computer, you can edit it using your favorite spreadsheet program. When you&#8217;re done editing, you can re-upload the file using the Import tool.', 'seolat-tool-plus');
			echo "</p>\n<p>";
			$url = $this->get_nonce_url('dj-export');
			echo "<a href='$url' class='button-primary'>".__('Download CSV File', 'seolat-tool-plus')."</a>";
			echo "</p>\n";
		}
	}
	
	function reset_tab() {
		if ($this->is_action('sl-reset'))
			$this->print_message('success', __('All settings have been erased and defaults have been restored.', 'seolat-tool-plus'));
		echo "\n<p>";
		_e('You can erase all your SEOLAT Tool Plus settings and restore them to &#8220;factory defaults&#8221; by clicking the button below.', 'seolat-tool-plus');
		echo "</p>\n<p>";
		$url = $this->get_nonce_url('sl-reset');
		$confirm = __('Are you sure you want to erase all module settings? This cannot be undone.', 'seolat-tool-plus');
		echo "<a href='$url#sl-reset' class='button-primary' onclick=\"javascript:return confirm('$confirm')\">".__('Restore Default Settings', 'seolat-tool-plus')."</a>";
		echo "</p>\n";
	}
}

}

?>