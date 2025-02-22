<?php
/**
 * The pseudo-abstract class upon which all modules are based.
 * 
 * @abstract
 * @since 0.1
 */
class SL_Module {
	
	/********** VARIABLES **********/
	
	/**
	 * @since 0.1
	 * @var string
	 */
	var $module_key;
	
	/**
	 * Stores the parent module (an SL_Module object) if this module has a parent.
	 * 
	 * @since 1.5
	 * @var SL_Module
	 */
	var $parent_module = null;
	
	/**
	 * Stores any child modules as an array of SL_Module objects.
	 * 
	 * @since 1.5
	 * @var array
	 */
	var $modules = array();
	
	/**
	 * Stores the module file's URL.
	 * 
	 * @since 0.1
	 * @var string
	 */
	var $module_url;
	
	/**
	 * Stores the URL to the directory containing the module file. Has trailing slash.
	 * 
	 * @since 1.5
	 * @var string
	 */
	var $module_dir_url;
	
	/**
	 * Stores the module file's URL relative to the plugin directory.
	 * 
	 * @since 2.1
	 * @var string
	 */
	var $module_rel_url;
	
	/**
	 * Stores the URL to the directory containing the module file, relative to the plugin directory. Has trailing slash.
	 * 
	 * @since 2.1
	 * @var string
	 */
	var $module_dir_rel_url;
	
	/**
	 * Stores the module's plugin page hook (the full hook with seo_page_ prefix).
	 * A reconstructed value of the get_plugin_page_hook() function, which is only available after admin init.
	 * 
	 * @since 0.1
	 * @var string
	 */
	var $plugin_page_hook;
	
	/**
	 * Contains messages that are waiting to be displayed to the user.
	 * 
	 * @since 0.1
	 * @var array
	 */
	var $messages = array();
	
	/**
	 * Stores the plugin object by reference.
	 * 
	 * @since 1.5
	 */
	var $plugin = null;
	
	
	/********** CONSTRUCTOR FUNCTION **********/
	
	/**
	 * PHP4 constructor that points to the likely-overloaded PHP5 constructor.
	 * 
	 * @since 0.1
	 * @uses __construct()
	 */
	/*function SL_Module() {
		$this->__construct();
	}*/
	
	
	/********** PSEUDO-ABSTRACT FUNCTIONS **********/
	
	/**
	 * PHP5 constructor.
	 * 
	 * @since 0.1
	 */
	function __construct() { }
	
	/**
	 * The module's official title.
	 * 
	 * @since 1.5
	 * 
	 * @return string
	 */
	static function get_module_title() { return ''; }
	
	/**
	 * The title to be used by parent modules.
	 * 
	 * @since 1.5
	 * 
	 * @return string
	 */
	function get_module_subtitle() { return isset($this) ? $this->get_module_title() : ''; }
	
	/**
	 * The title of the admin page, which is displayed in the <title> and <h2> tags.
	 * Is the same as the menu title by default.
	 * 
	 * @since 0.1
	 * 
	 * @return string The title shown on this module's admin page.
	 */
	function get_page_title() { return isset($this) ? $this->get_module_title() : ''; }
	
	/**
	 * The title that appears on the administration navigation menu.
	 * 
	 * @since 0.1
	 * 
	 * @return string The title shown on the admin menu.
	 */
	
	static function get_menu_title() { return (isset($this)) ? $this->get_module_title() : ''; }
	
	/**
	 * Determines where this module's admin page should appear relative to those of other modules.
	 * If two modules have the same menu position index, they are sorted alphabetically.
	 * 
	 * @since 0.1
	 * 
	 * @return int The menu position index.
	 */
	static function get_menu_pos()   { return 10; }
	
	/**
	 * Determines where this module's admin contents should appear on the parent page relative to those of other sibling modules.
	 * If two modules have the same order index, they are sorted alphabetically.
	 * 
	 * @since 1.5
	 * 
	 * @return int The child order index.
	 */
	static function get_child_order() { return 999; }
	
	/**
	 * The number that should be displayed in a bubble next to the module's menu title.
	 * A return value of zero means no bubble is shown.
	 * 
	 * @since 0.1
	 * 
	 * @return int The number that should be displayed.
	 */
	function get_menu_count() {
		$count = 0;
		foreach ($this->modules as $key => $module) {
			$count += $this->modules[$key]->get_menu_count();
		}
		return $count;
	}
	
	/**
	 * Whether or not the module will ever return a non-zero menu count.
	 * 
	 * @since 1.5
	 * 
	 * @return boolean
	 */
	static function has_menu_count() { return false; }
	
	/**
	 * A descriptive label of the menu count.
	 * 
	 * @since 0.3
	 * 
	 * @return string The label.
	 */
	function get_menu_count_label() { return ''; }
	
	/**
	 * Indicates under which top-level menu this module's admin page should go.
	 * Examples: seo (This plugin's SEO menu), options-general.php (The Settings menu)
	 * 
	 * @since 0.1
	 * 
	 * @return string The value to pass to WordPress's add_submenu_page() function.
	 */
	function get_menu_parent(){ return 'seo'; }
	
	/**
	 * Returns the hook of this module's menu parent.
	 * Examples: seo (This plugin's SEO menu), settings (The Settings menu), toplevel (The toplevel)
	 * 
	 * @since 0.1
	 * 
	 * @return string The hook of the module's menu parent.
	 */
	function get_menu_parent_hook() { return $this->get_menu_parent(); }
	
	/**
	 * @since 7.2.5
	 */
	function belongs_in_admin($admin_scope = null) {
		
		if ($admin_scope === null)
			$admin_scope = lat_wp::get_admin_scope();
		
		switch ($admin_scope) {
			case 'blog':
				return true;
				break;
			case 'network':
			case 'user':
			default:
				return false;
				break;
		}
	}
	
	/**
	 * The status (enabled/silenced/hidden) of the module when the module is newly added to the plugin.
	 * 
	 * @since 1.5
	 * 
	 * @return int Either SL_MODULE_ENABLED, SL_MODULE_SILENCED, or SL_MODULE_HIDDEN.
	 */
	function get_default_status() { return SL_MODULE_ENABLED; }
	
	/**
	 * The module key of this module's parent. Defaults to false (no parent).
	 * 
	 * @since 0.3
	 * 
	 * @return string|bool
	 */
	static function get_parent_module() { return false; }
	
	/**
	 * Returns an array of admin page tabs; the label is the key and the callback is the value.
	 * 
	 * @since 1.5
	 * 
	 * @return array
	 */
	function get_admin_page_tabs() { return array(); }
	
	/**
	 * Whether or not the module can "exist on its own."
	 * Determines whether or not the module appears in the Module Manager.
	 * 
	 * @since 1.5
	 * 
	 * @return bool
	 */
	static function is_independent_module() {
		return true;
	}
	
	/**
	 * The array key of the plugin's settings array in which this module's settings are stored.
	 * 
	 * @since 1.5
	 * 
	 * @return string
	 */
	function get_settings_key() {
		if (isset($this)) {
			if (strlen($parent = $this->get_parent_module()) && !$this->is_independent_module())
				return $this->plugin->modules[$parent]->get_settings_key();
			else
				return $this->get_module_key();
		} else {
			if (strlen($parent = self::get_parent_module()) && !self::is_independent_module()) {
				global $seo_lat_tool_plus;
				return $seo_lat_tool_plus->modules[$parent]->get_settings_key();
			} else {
				if (strlen($parent = self::get_parent_module()) && !self::is_independent_module()) {
					global $seo_lat_tool_plus;
					return $seo_lat_tool_plus->get_module_key();
				} else {
					return false;
				}
			}
		}
	}
	
	/**
	 * Whether or not this module should be the default screen for the "SEO" menu.
	 * 
	 * @since 1.5
	 * @return bool
	 */
	function is_menu_default() { return false; }
	
	/**
	 * Called after the module has been constructed and its variables have been filled.
	 * 
	 * @since 3.9
	 */
	function load() {}
	
	/**
	 * Called at WordPress's init hook.
	 * 
	 * @since 0.1
	 */
	function init() {}
	
	/**
	 * Called under 3 circumstances:
	 * 1. When the SEOLAT Tool Plus plugin is activated (not necessarily for the first time)
	 * 2. When a module is newly registered in the database, which can happen for two reasons:
	 * 		a. The plugin is activated *for the first time*
	 * 		b. The module has been newly added via a plugin upgrade
	 * 3. When the module is re-enabled in the Module Manager after being disabled.
	 * 
	 * Note that this function will be called twice when the plugin is activated for the first time, since this will make #1 and #2 both true.
	 * If the plugin is deactivated and then reactivated, only #1 will be true.
	 * 
	 * WARNING: Do not use "$this" in the activate() function. It will not work under condition #3. Check for isset($this) and if false, use self:: instead.
	 * 
	 * @since 0.1
	 */
	function activate() { }
	
	/**
	 * Called under 2 circumstances:
	 * 1. When the SEOLAT Tool Plus plugin is deactivated or uninstalled.
	 * 2. When the module is disabled in the Module Manager.
	 * 
	 * @since 7.2.8
	 */
	function deactivate() { }
	
	/**
	 * Called when SEOLAT Tool Plus has just been upgraded to a new version.
	 * 
	 * @since 2.1
	 */
	function upgrade() { }
	
	/**
	 * Returns an array of default settings. The defaults will be saved in the database if the settings don't exist.
	 * 
	 * @since 0.1
	 * 
	 * @return array The default settings. (The setting name is the key, and the default value is the array value.)
	 */
	function get_default_settings() { return array(); }
	
	/**
	 * Is called at WordPress' admin_init hook when this module's admin page is showing.
	 * 
	 * @since 6.0
	 */
	function admin_page_init() { }
	
	/**
	 * Is called at WordPress' admin_init hook when the post editor is loaded.
	 * 
	 * @since 7.3
	 */
	function editor_init() { }
	
	/**
	 * The contents of the administration page.
	 * 
	 * @since 0.1
	 */
	function admin_page_contents() {
		$this->children_admin_page_tabs_form();
	}
	
	/**
	 * Returns a list of possible admin table columns that should be registered in "Screen Options"
	 * 
	 * @since 2.1
	 * 
	 * @return array
	 */
	function get_admin_table_columns() {
		return array();
	}
	
	/**
	 * Called at WordPress's load-{page} hook for this module's admin page.
	 * 
	 * @since 7.0
	 */
	function load_hook() {
		$this->add_help_tabs(get_current_screen());
	}
	
	/**
	 * @since 7.0
	 */
	function add_help_tabs($screen) { }
	
	/**
	 * Adds the module's post meta box field HTML to the array.
	 * 
	 * @since 0.1
	 * 
	 * @param array $fields The fields array.
	 * @return array The updated fields array.
	 */
	function postmeta_fields($fields, $screen) { return $fields;	}
	
	/********** INITIALIZATION FUNCTIONALITY **********/
	
	/**
	 * If settings are unset, apply the defaults if available.
	 * 
	 * @since 0.5
	 * @uses get_default_settings()
	 * @uses get_setting()
	 * @uses update_setting()
	 */
	function load_default_settings() {
		
		$defaults = $this->get_default_settings();
		foreach ($defaults as $setting => $default) {
			if ($this->get_setting($setting, "{reset}") === "{reset}") {
				$this->update_setting($setting, $default, null, null);
			}
		}
	}
	
	
	/********** MODULE FUNCTIONS **********/
	
	/**
	 * Returns the array key of the module.
	 * 
	 * @since 0.1
	 * @uses $module_key
	 * 
	 * @return string The module key.
	 */
	function get_module_key() {
		if ($this->module_key)
			return $this->module_key;
		else
			//This error will only be triggered if someone has seriously messed with the plugin architecture
			die("An SEOLAT Tool Plus module did not initialize properly. Perhaps you're trying to load an SEOLAT Tool Plus module independent of the plugin?");
	}
	
	/**
	 * Returns the key of the parent module if there is one; if not, the key of the current module.
	 * 
	 * @since 2.1
	 * 
	 * @return string
	 */
	function get_module_or_parent_key() {
		return $this->has_enabled_parent() ? $this->get_parent_module() : $this->get_module_key();
	}
	
	/**
	 * Returns true only if this module has a parent AND that parent is enabled.
	 * 
	 * @since 7.0
	 * 
	 * @return bool
	 */
	function has_enabled_parent() {
		return (strlen($p = $this->get_parent_module()) && $this->plugin->module_exists($p));
	}
	
	/**
	 * Returns the absolute URL of the module's admin page.
	 * 
	 * @since 0.7
	 * 
	 * @param string|false $key The key of the module for which to generate the admin URL. Optional.
	 * @return string The absolute URL to the admin page.
	 */
	function get_admin_url($key = false) {
		
		$anchor = '';
		if ($key === false) {
			if (($key = $this->get_parent_module()) && $this->plugin->module_exists($key)) {
				
				$tabs = $this->get_admin_page_tabs();
				if (!is_array($tabs))
					return false;
				
				if (count($tabs)) {
					$first_tab = reset($tabs);
					$anchor = '#' . $first_tab['id'];
				} else {
					$anchor = '#' . $this->plugin->key_to_hook($this->get_module_key());
				}
			} else
				$key = $this->get_module_key();
		}
		
		if (!$this->plugin->call_module_func($key, 'belongs_in_admin', $belongs_in_admin) || !$belongs_in_admin)
			return false;
		
		if (!$this->plugin->call_module_func($key, 'get_menu_title', $menu_title) || !$menu_title)
			return false;
		
		$basepage = 'admin.php';
		if ($this->plugin->call_module_func($key, 'get_menu_parent', $custom_basepage) && lat_string::endswith($custom_basepage, '.php'))
			$basepage = $custom_basepage;
		
		if (is_network_admin() && $this->belongs_in_admin('network'))
			$admin_url = 'network_admin_url';
		else
			$admin_url = 'admin_url';
		
		return $admin_url($basepage.'?page='.$this->plugin->key_to_hook($key).$anchor);
	}
	
	/**
	 * Returns an <a> link to the module's admin page, if the module is enabled.
	 * 
	 * @since 1.0
	 * @uses get_admin_url()
	 * 
	 * @param string|false $key The key of the module for which to generate the admin URL.
	 * @param string $label The text to go inside the <a> element.
	 * @return string The <a> element, if the module exists; otherwise, the label by itself.
	 */
	function get_admin_link($key, $label) {
	
		if ($key == false || $this->plugin->module_exists($key))
			return sprintf('<a href="%s">%s</a>', $this->get_admin_url($key), $label);
		else
			return $label;
	}
	
	/**
	 * Returns a boolean indicating whether the user is currently viewing this module's admin page.
	 * 
	 * @since 1.1.1
	 * 
	 * @return bool Whether the user is currently viewing this module's admin page.
	 */
	function is_module_admin_page() {
		if (is_admin()) {
			global $plugin_page;
			if ($plugin_page == $this->plugin->key_to_hook($this->get_module_or_parent_key())) return true;
		}
		
		return false;
	}
	
	/**
	 * Returns the filename of the module's icon URL.
	 * 
	 * @since 1.5
	 * 
	 * @return string
	 */
	function get_menu_icon_filename() {
		$filenames = array(
			  $this->get_settings_key()
			, $this->get_module_key()
			, $this->get_parent_module()
		);
		
		foreach ($filenames as $filename) {
			$image = $this->module_dir_url.$filename.'.png';
			if (is_readable($image)) return $image;
		}
		
		return '';
	}
	
	
	/********** CHILD MODULE FUNCTIONS **********/
	
	/**
	 * Finds child modules of this module and fills $this->modules accordingly.
	 * 
	 * @since 1.5
	 */
	function load_child_modules() {
		foreach ($this->plugin->modules as $key => $x_module) {
			if ($key != $this->get_module_key()) {
				$module =& $this->plugin->modules[$key];
				if ($module->get_parent_module() == $this->get_module_key()) {
					$module->parent_module =& $this;
					$this->modules[$key] =& $module;
				}
			}
		}
		
		if (count($this->modules) > 0)
			@uasort($this->modules, array(&$this, 'module_sort_callback'));
	}
	
	/**
	 * Returns an array of this module's admin tabs plus those of its children.
	 * 
	 * @since 1.5
	 * @return array
	 */
	function get_children_admin_page_tabs() {
		$tabs = $this->get_admin_page_tabs();
		if (!is_array($tabs)) $tabs = array();
		
		foreach ($this->modules as $key => $x_module) {
			$module =& $this->modules[$key];
			
			if ($module->belongs_in_admin()) {
				$child_tabs = $module->get_admin_page_tabs();
				
				if (is_array($child_tabs)) {
				
					if (empty($child_tabs))
						$child_tabs[] = array(
							  'title' => $module->get_module_subtitle()
							, 'id' => $this->plugin->key_to_hook($key)
							, 'callback' => array(&$module, 'admin_page_contents')
						);
					
					foreach ($child_tabs as $child_tab) {
						if (is_array($child_tab) && !is_array($child_tab['callback']))
							$child_tab['callback'] = array(&$module, $child_tab['callback']);
						
						$tabs[] = $child_tab;
					}
				}
			}
		}
		
		return $tabs;
	}
	
	/**
	 * Outputs this module's admin tabs plus those of its children.
	 * 
	 * @since 1.5
	 */
	function children_admin_page_tabs() {
		if (count($tabs = $this->get_children_admin_page_tabs()))
			$this->admin_page_tabs($tabs);
	}
	
	/**
	 * Outputs a form containing this module's admin tabs plus those of its children.
	 * 
	 * @since 1.5
	 */
	function children_admin_page_tabs_form() {
		if (count($tabs = $this->get_children_admin_page_tabs())) {
			echo "\n\n<div class='row'>\n";
			echo "\n\n<div class='col-sm-8 col-md-9'>\n";
		
			$this->admin_form_start(false, false);
			$this->admin_page_tabs($tabs);
			$this->admin_form_end(null, false);
			
			echo "\n\n</div>\n";
				echo "\n\n<div class='col-sm-4 col-md-3'>\n";
				$this->promo_sdf_banners();
				echo "\n\n</div>\n";
			echo "\n\n</div>\n";
		}
	}
	
	/**
	 * Outputs the admin pages of this module's children, one after the other.
	 * 
	 * @since 1.5
	 */
	function children_admin_pages() {
		foreach ($this->modules as $key => $x_module) {
			echo "<div id='" . $this->plugin->key_to_hook($key) . "'>\n";
			$this->modules[$key]->admin_subheader($this->modules[$key]->get_module_subtitle());
			$this->modules[$key]->admin_page_contents();
			echo "</div>\n";
		}
	}
	
	/**
	 * Outputs a form containing the admin pages of this module's children, outputted one after the other.
	 * 
	 * @since 1.5
	 */
	function children_admin_pages_form() {
		if (count($this->modules)) {
			$this->admin_form_start(false, false);
			$this->children_admin_pages();
			$this->admin_form_end(null, false);
		} else
			$this->print_message('warning', sprintf(__('All the modules on this page have been disabled. You can re-enable them using the <a href="%s">Module Manager</a>.', 'seolat-tool-plus'), $this->get_admin_url('modules')));
	}
	
	/**
	 * Compares two modules to determine which of the two should be displayed first on the parent page.
	 * Sorts by child order first, and title second.
	 * Works as a uasort() callback.
	 * 
	 * @since 1.5
	 * @uses SL_Module::get_child_order()
	 * @uses SL_Module::get_module_subtitle()
	 * 
	 * @param SL_Module $a The first module to compare.
	 * @param SL_Module $b The second module to compare.
	 * @return int This will be -1 if $a comes first, or 1 if $b comes first.
	 */
	function module_sort_callback($a, $b) {
		
		if ($a->get_child_order() == $b->get_child_order()) {
			return strcmp($a->get_module_subtitle(), $b->get_module_subtitle());
		}
		
		return ($a->get_child_order() < $b->get_child_order()) ? -1 : 1;
	}
	
	/********** SETTINGS FUNCTIONS **********/
	
	/**
	 * Retrieves the given setting from a module's settings array.
	 * 
	 * @since 0.1
	 * @uses get_settings_key()
	 * 
	 * @param string $key The name of the setting to retrieve.
	 * @param mixed $default What should be returned if the setting does not exist. Optional.
	 * @param string|null $module The module to which the setting belongs. Defaults to the current module's settings key. Optional.
	 * @return mixed The value of the setting, or the $default variable.
	 */
	function get_setting($key, $default=null, $module=null, $sneakpeak=false) {
		if (!$module) $module = $this->get_settings_key();
		
		$msdata = (array)get_option("seo_lat_tool_plus_module_$module", array());
		
		if ($sneakpeak && $this->is_action('update'))
			$setting = stripslashes(isset($_POST[$key]) ? $_POST[$key] : null);
		elseif (isset($msdata[$key]))
			$setting = $msdata[$key];
		else
			$setting = $default;
		
		$setting = apply_filters("sl_get_setting-$module", $setting, $key);
		$setting = apply_filters("sl_get_setting-$module-$key", $setting, $key);
		
		return $setting;
	}
	
	/**
	 * Sets a value in the module's settings array.
	 * 
	 * @since 0.1
	 * @uses get_settings_key()
	 * 
	 * @param string $key The key of the setting to be changed.
	 * @param string $value The new value to assign to the setting.
	 * @param string|null $module The module to which the setting belongs. Defaults to the current module's settings key. Optional.
	 */
	function update_setting($key, $value, $module=null, $array_key=null) {
		if (!$module) $module = $this->get_settings_key();

		$msdata = (array)get_option("seo_lat_tool_plus_module_$module", array());
		
		$use_custom  = 	apply_filters("sl_custom_update_setting-$module-$key", false, $value, $key) ||
						apply_filters("sl_custom_update_setting-$module", false, $value, $key);
		
		if (!$use_custom) {
			if ($array_key)
				$msdata[$key][$array_key] = $value;
			else
				$msdata[$key] = $value;
		}
		
		update_option("seo_lat_tool_plus_module_$module", $msdata);
	}
	
	/**
	 * Gets a setting's value, deletes the setting, and returns the value.
	 * 
	 * @since 2.1
	 * @uses get_settings_key()
	 * 
	 * @param string $key The name of the setting to retrieve/delete.
	 * @param mixed $default What should be returned if the setting does not exist. Optional.
	 * @param string|null $module The module to which the setting belongs. Defaults to the current module's settings key. Optional.
	 * @return mixed The value of the setting, or the $default variable.
	 */
	function flush_setting($key, $default=null, $module=null) {
		$setting = $this->get_setting($key, $default, $module); //We need to retrieve the setting before deleting it
		$this->delete_setting($key, $module);
		return $setting;
	}
	
	/**
	 * Deletes a module setting.
	 * 
	 * @since 2.1
	 * @uses get_settings_key()
	 * 
	 * @param string $key The name of the setting to delete.
	 * @param string|null $module The module to which the setting belongs. Defaults to the current module's settings key. Optional.
	 */
	function delete_setting($key, $module=null, $array_key = null) {
		if (!$module) $module = $this->get_settings_key();
		
		$msdata = (array)get_option("seo_lat_tool_plus_module_$module", array());
		
		if (isset($msdata[$key])) {
			if ($array_key) {
				if (isset($msdata[$key][$array_key]))
					unset($msdata[$key][$array_key]);
			} else {
				unset($msdata[$key]);
			}
		}
	}
	
	/**
	 * Returns a default setting. Only use this function if a default is indeed provided!
	 * 
	 * @since 1.3
	 * @uses get_default_settings()
	 * 
	 * @param string $key The name of the setting whose default to retrieve.
	 * @return mixed The default value for the setting.
	 */
	function get_default_setting($key) {
		$defaults = $this->get_default_settings();
		return $defaults[$key];
	}
	
	
	/********** ADMIN PAGE FUNCTIONS **********/
	
	/**
	 * Displays the beginning, contents, and end of the module's administration page.
	 * 
	 * @since 0.1
	 * @uses admin_page_start()
	 * @uses admin_page_contents()
	 * @uses admin_page_end()
	 */
	function admin_page() {
		if (!apply_filters('sl_custom_admin_page-'.$this->get_module_key(), false)) {
			$this->admin_page_start();
			$this->admin_page_contents();
			$this->admin_page_end();
		}
	}
	
	/**
	 * Outputs the starting code for an administration page: 
	 * wrapper, ID'd <div>, icon, and title
	 * 
	 * @since 0.1
	 * @uses admin_footer() Hooked into WordPress's in_admin_footer action.
	 * @uses get_module_key()
	 * @uses get_page_title()
	 * 
	 * @param string $icon The ID that should be applied to the icon element. The icon is loaded via CSS based on the ID. Optional.
	 */
	function admin_page_start($icon = 'options-general') {
		
		//Add our custom footer attribution
		add_action('in_admin_footer', array(&$this, 'admin_footer'));
		
		//Output the beginning of the admin screen
		echo "<div class=\"wrap\">\n";
		
		if (strcmp($pclass = strtolower(get_parent_class($this)), 'sl_module') != 0)
			$class = ' '.str_replace('_', '-', $pclass);
		else
			$class = '';
		
		echo "<div id=\"sl-".sl_esc_attr($this->get_module_key())."\" class=\"sdf-admin$class\">\n";
		echo "\n<h2><img width=\"207\" alt=\"logo\" src=\"" . plugins_url() . "/seolat-tool-plus/plugin/img/aff-logo.png\"> <span>".$this->get_page_title()."</span></h2>\n";
	}
	
	/**
	 * Outputs an administration page subheader (an <h4> tag).
	 * 
	 * @since 0.1
	 * 
	 * @param string $title The text to output.
	 */
	function admin_subheader($title, $id=false) {
		if ($id) $id = ' id="' . sl_esc_attr($id) . '"';
		echo "<h4 class='sl-subheader'$id>$title</h4>\n";
	}
	
	/**
	 * Outputs an administration form table subheader.
	 * 
	 * @since 0.1
	 * 
	 * @param string $title The text to output.
	 */
	function admin_form_subheader($title) {
		echo "<tr><th colspan='2'><strong>$title</strong></th></tr>\n";
	}
	
	/**
	 * Outputs the ending code for an administration page.
	 * 
	 * @since 0.1
	 */
	function admin_page_end() {
		echo "\n</div>\n</div>\n";
	}
	
	/**
	 * Outputs a tab control and loads the current tab.
	 * 
	 * @since 0.7
	 * 
	 * @param array $tabs Array (id => __, title => __, callback => __)
	 * @param bool $table Whether or not the tab contents should be wrapped in a form table.
	 */
	function admin_page_tabs($tabs=array(), $table=false) {
		$this->plugin->tabs($tabs, $table, $this);
	}
	
	/**
	 * Adds the hook necessary to initialize the admin page tabs.
	 * 
	 * @since 0.8
	 */
	function admin_page_tabs_init() {
		add_action('admin_enqueue_scripts', array(&$this, 'admin_page_tabs_js'));
	}
	
	/**
	 * Enqueues the JavaScript needed for the admin page tabs.
	 * 
	 * @since 0.8
	 * @uses is_module_admin_page()
	 */
	function admin_page_tabs_js() {
		if ($this->is_module_admin_page())
			wp_enqueue_script('jquery-ui-tabs');
	}
	
	/**
	 * Adds plugin/module information to the admin footer.
	 * 
	 * @since 0.1
	 * @uses SL_PLUGIN_URI
	 * @uses SL_PLUGIN_NAME
	 * @uses SL_AUTHOR_URI
	 * @uses SL_AUTHOR
	 */
	function admin_footer() {
		printf(__('%1$s | %2$s %3$s by %4$s', 'seolat-tool-plus'),
			$this->get_module_title(),
			'<a href="'.SL_PLUGIN_URI.'" target="_blank" rel="nofollow">'.__(SL_PLUGIN_NAME, 'seolat-tool-plus').'</a>',
			SL_VERSION,
			'<a href="'.SL_AUTHOR_URI.'" target="_blank" rel="nofollow">'.__(SL_AUTHOR, 'seolat-tool-plus').'</a>'
		);
		
		echo "<br />";
	}
	
	/**
	 * Returns tabs for post/taxonomy meta editing tables.
	 * 
	 * @since 2.9
	 * @uses get_postmeta_edit_tabs()
	 * @uses get_taxmeta_edit_tabs()
	 * 
	 * @param array $fields The array of meta fields that the user can edit with the tables.
	 */
	function get_meta_edit_tabs($fields) {
		return array_merge(
			$this->get_postmeta_edit_tabs($fields)
			,$this->get_taxmeta_edit_tabs($fields)
		);
	}
	
	/**
	 * Returns tabs for post meta editing tables.
	 * 
	 * @since 2.9
	 * 
	 * @param array $fields The array of meta fields that the user can edit with the tables.
	 */
	function get_postmeta_edit_tabs($fields) {
		
		$types = get_post_types(array('public' => true), 'objects');
		
		//Turn the types array into a tabs array
		$tabs = array();
		foreach ($types as $type)
			$tabs[$type->name] = array(
				  'title' => $type->labels->name
				, 'id' => 'sl-' . $type->name
				, 'callback' => array('meta_edit_tab', 'post', 'sl-' . $type->name, $type->name, $type->labels->singular_name, $fields)
			);
		return $tabs;
	}
	
	/**
	 * Returns tabs for taxonomy meta editing tables.
	 * 
	 * @since 2.9
	 * 
	 * @param array $fields The array of meta fields that the user can edit with the tables.
	 */
	function get_taxmeta_edit_tabs($fields) {
		$types = lat_wp::get_taxonomies();
		
		//Turn the types array into a tabs array
		$tabs = array();
		foreach ($types as $name => $type) {
			if ($type->labels->name) {
				$tabs[] = array(
					  'title' => $type->labels->name
					, 'id' => 'sl-' . $name
					, 'callback' => array('meta_edit_tab', 'term', 'sl-' . $name, $name, $type->labels->singular_name, $fields)
				);
			}
		}
		return $tabs;
	}
	
	/**
	 * Outputs the contents of a meta editing tab.
	 * 
	 * @since 2.9
	 */
	function meta_edit_tab($genus, $tab, $type, $type_label, $fields) {
		if (!$this->meta_edit_table($genus, $tab, $type, $type_label, $fields))
			$this->print_message('info', __('Your site currently doesn&#8217;t have any public items of this type.', 'seolat-tool-plus'));
	}
	
	/**
	 * Outputs the contents of a meta editing table.
	 * 
	 * @since 2.9
	 * 
	 * @param string $genus The type of object being handled (either 'post' or 'term')
	 * @param string $tab The ID of the current tab; used to generate a URL hash (e.g. #$tab)
	 * @param string $type The type of post/taxonomy type being edited (examples: post, page, attachment, category, post_tag)
	 * @param string $type_label The singular label for the post/taxonomy type (examples: Post, Page, Attachment, Category, Post Tag)
	 * @param array $fields The array of meta fields that the user can edit with the tables. The data for each meta field are stored in an array with these elements: "type" (can be textbox, textarea, or checkbox), "name" (the meta field, e.g. title or description), "term_settings_key" (the key of the setting for cases when term meta data are stored in the settings array), and "label" (the internationalized label of the field, e.g. "Meta Description" or "Title Tag")
	 */
	function meta_edit_table($genus, $tab, $type, $type_label, $fields) {
		
		//Pseudo-constant
		$per_page = 100;
		
		//Sanitize parameters
		if (!is_array($fields) || !count($fields)) return false;
		if (!isset($fields[0]) || !is_array($fields[0])) $fields = array($fields);
		
		//Get search query
		$type_s = $type . '_s';
		$search = isset($_REQUEST[$type_s]) ? sanitize_text_field($_REQUEST[$type_s]) : '';
		
		//Save meta if applicable
		if ($is_update = ($this->is_action('update') && !strlen(trim($search)))) {
            $arrayPOST = array_map( 'sanitize_text_field', $_POST );
			foreach ($arrayPOST as $key => $value) {
				$value = stripslashes($value);
				if (lat_string::startswith($key, $genus.'_'))
					foreach ($fields as $field)
						if (preg_match("/{$genus}_([0-9]+)_{$field['name']}/", $key, $matches)) {
							$id = (int)$matches[1];
							switch ($genus) {
								case 'post': update_post_meta($id, "_sl_{$field['name']}", $value); break;
								case 'term': $this->update_setting($field['term_settings_key'], $value, null, $id); break;
							}
							continue 2; //Go to next $_POST item
						}
			}
		}
		
		$pagenum = isset( $_GET[$type . '_paged'] ) ? absint( $_GET[$type . '_paged'] ) : 0;
		if ( empty($pagenum) ) $pagenum = 1;
		
		//Load up the objects based on the genus
		switch ($genus) {
			case 'post':
				
				//Get the posts
				wp(array(
					  'post_type' => $type
					, 'posts_per_page' => $per_page
					, 'post_status' => 'any'
					, 'paged' => $pagenum
					, 'order' => 'ASC'
					, 'orderby' => 'title'
					, 's' => $search
				));
				global $wp_query;
				$objects = &$wp_query->posts;
				
				$num_pages = $wp_query->max_num_pages;
				$total_objects = $wp_query->found_posts;
				
				break;
				
			case 'term':
				$objects = get_terms($type, array('search' => $search));
				$total_objects = count($objects);
				$num_pages = ceil($total_objects / $per_page);
				$objects = array_slice($objects, $per_page * ($pagenum-1), $per_page);
				break;
			default:
				return false;
				break;
		}
		
		if ($total_objects < 1) return false;
		
		echo "\n<div class='sl-meta-edit-table'>\n";
		
		$page_links = paginate_links( array(
			  'base' => html_entity_decode( esc_url( add_query_arg( $type . '_paged', '%#%' ) ) ) . '#' . $tab
			, 'format' => ''
			, 'prev_text' => __('&laquo;')
			, 'next_text' => __('&raquo;')
			, 'total' => $num_pages
			, 'current' => $pagenum
			, 'add_args' => false
		));
		
		if ( $page_links ) {
			$page_links_text = '<div class="tablenav"><div class="tablenav-pages">';
			$page_links_text .= sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
					number_format_i18n( ( $pagenum - 1 ) * $per_page + 1 ),
					number_format_i18n( min( $pagenum * $per_page, $total_objects ) ),
					number_format_i18n( $total_objects ),
					$page_links
					);
			$page_links_text .= "</div></div>\n";
			
			echo $page_links_text;
		} else $page_links_text = '';
		
		//Get object identification headers
		$headers = array(
			  'actions' => __('Actions', 'seolat-tool-plus')
			, 'id' => __('ID', 'seolat-tool-plus')
			, 'name' => $type_label
		);
		
		//Get meta field headers
		foreach ($fields as $field) {
			$headers[$field['name']] = $field['label'];
		}
		
		//Output all headers
		$this->admin_wftable_start($headers);
		
		//Output rows
		foreach ($objects as $object) {
			
			switch ($genus) {
				case 'post':
					$id = intval($object->ID);
					$name = $object->post_title;
					$view_url = get_permalink($id);
					$edit_url = get_edit_post_link($id);
					
					$status_obj = get_post_status_object($object->post_status);
					switch ($object->post_status) {
						case 'publish': $status = ''; break;
						case 'inherit': $status = ''; break;
						case 'auto-draft': $status = $status; break;
						default: $status = $status_obj->label; break;
					}
					
					if ($status)
						$name .= "<span class='sl-meta-table-post-status'> &mdash; $status</span>";
					
					break;
				case 'term':
					if (!isset($object->term_taxonomy_id)) {
						$id = intval($object->term_id);
						$view_url = get_term_link($id, $type);
					}
					else{
						$id = intval($object->term_id);
						$view_url = get_term_link(intval($object->term_id), $type);
					}
					$name = $object->name;

					$edit_url = lat_wp::get_edit_term_link($id, $type);
					break;
				default: return false; break;
			}
			
			$view_url = sl_esc_attr($view_url);
			$edit_url = sl_esc_attr($edit_url);
			
			$actions = array(sprintf('<a href="%s">%s</a>', $view_url, __('View', 'seolat-tool-plus')));
			if ($edit_url)
				$actions[] = sprintf('<a href="%s">%s</a>', $edit_url, __('Edit', 'seolat-tool-plus'));
			$actions = implode(' | ', $actions);
			
			$cells = compact('actions', 'id', 'name');
			
			//Get meta field cells
			foreach ($fields as $field) {
				$inputid = "{$genus}_{$id}_{$field['name']}";
				
				switch ($genus) {
					case 'post':
						$value = $this->get_postmeta($field['name'], $id);
						break;
					case 'term':
						$value = $this->get_setting($field['term_settings_key'], array());
						$value = isset($value[$id]) ? $value[$id] : null;
						break;
				}
				
				if ($is_update && $field['type'] == 'checkbox' && $value == '1' && !isset($_POST[$inputid]))
					switch ($genus) {
						case 'post': delete_post_meta($id, "_sl_{$field['name']}"); $value = 0; break;
						case 'term': $this->update_setting($field['term_settings_key'], false, null, $id); break;
					}
				
				$counter_text = '';
				switch ($field['type']) {
					case 'textbox': $counter_text = 'You&#8217;ve Entered %s Characters. Most Search Engines Use Up To 70.'; break;
					case 'textarea': $counter_text = 'You&#8217;ve Entered %s Characters. Most Search Engines Use Up To 140.'; break;
				}
				switch ($field['name']) {
					case 'og_title': $counter_text = 'You&#8217;ve Entered %s Characters. It’s Best to Stay Between 60 and 90 Characters.'; break;
					case 'og_description': $counter_text = 'You&#8217;ve Entered %s Characters. Keep Description to 200 Characters Max.'; break;
					case 'canonical_url_tag': $counter_text = ''; break;
				}
				
				$cells[$field['name']] = $this->get_input_element(
					  $field['type'] //Type
					, $inputid
					, $value
					, isset($field['options']) ? $field['options'] : false
					, true
					, array('counter_text' => $counter_text)
				);
			}
			
			//Output all cells
			$this->table_row($cells, $id, $type);
		}
		
		//End table
		$this->admin_wftable_end();
		
		echo $page_links_text;
		
		echo "</div>\n";
		
		return true;
	}
	
	/**
	 * Returns the HTML for a given type of input element, without any surrounding <td> elements etc.
	 * 
	 * @since 2.9
	 * 
	 * @param string $type The type of input element (can be textbox, textarea, or checkbox)
	 * @param string $inputid The name/ID of the input element
	 * @param string $value The current value of the field
	 * @return string
	 */
	function get_input_element($type, $name, $value=null, $extra=false, $inputid=true, $args=array()) {
		if ($value === null) $value = $this->get_setting($name);
		
		if ($inputid === true) $inputid = $name;
		if (strlen($inputid)) $inputid = " id='".sl_esc_attr($inputid)."'";
		
		$counter_text = isset($args['counter_text']) ? $args['counter_text'] : false;
		
		//Get HTML element
		switch ($type) {
			case 'textbox':
				$value = sl_esc_editable_html($value);
				$placeholder = $extra ? " placeholder='" . sl_esc_attr($extra) . "'" : '';
				return "<input name='$name'$inputid value='$value'$placeholder type='text' class='form-control input-sm textbox regular-text' onkeyup=\"javascript:document.getElementById('".sl_esc_attr($name)."_charcount').innerHTML = document.getElementById('".sl_esc_attr($name)."').value.length\" /><span class=''>".sprintf(__($counter_text, 'seolat-tool-plus'), "<strong  id='".sl_esc_attr($name)."_charcount'>".strlen($value)."</strong>")."</span>";
				break;
			case 'textarea':
				$value = sl_esc_editable_html($value);
				return "<textarea name='$name'$inputid type='text' rows='3' cols='50' class='textarea form-control regular-text qqq'  onkeyup=\"javascript:document.getElementById('".sl_esc_attr($name)."_charcount').innerHTML = document.getElementById('".sl_esc_attr($name)."').value.length\">$value</textarea><span class=''>".sprintf(__($counter_text, 'seolat-tool-plus'), "<strong  id='".sl_esc_attr($name)."_charcount'>".strlen($value)."</strong>")."</span>";
				break;
			case 'checkbox':
				$checked = $value ? " checked='checked'" : '';
				$html = "<input name='$name'$inputid value='1' type='checkbox' class='checkbox'$checked />";
				if (is_string($extra)) {
					$extra = sl_esc_html($extra);
					$html = "<label>$html&nbsp;$extra</label>";
				}
				return $html;
				break;
			case 'dropdown':
				$html = "<select name='$name'$inputid onchange='javascript:sl_toggle_select_children(this)' class='dropdown'>";
				if (is_array($extra)) $html .= lat_html::option_tags($extra, $value); else $html .= $extra;
				$html .= "</select>";
				return $html;
				break;
			case 'hidden':
				return "<input name='$name'$inputid value='$value' type='hidden' />";
				break;
			case 'jlsuggest':
				$params = isset($extra['params']) ? $extra['params'] : '';
				$placeholder = isset($extra['placeholder']) ? $extra['placeholder'] : '';
				
				return $this->get_jlsuggest_box($name, $value, $params, $placeholder);
				break;
		}
		
		return '';
	}
	
	/**
	 * Creates an admin form subsection.
	 * 
	 * @since 3.8
	 * @uses get_setting()
	 * @see get_input_element()
	 * 
	 * @param string $field
	 * @param string|null $current_value
	 * @param string $trigger_value
	 * @param string $html
	 * @return string
	 */
	function get_admin_form_subsection($field, $current_value, $trigger_value, $html) {
		if ($current_value === null) $current_value = $this->get_setting($field);
		$hidden = ($current_value == $trigger_value) ? '' : ' hidden';
		
		$field = sl_esc_attr($field);
		$trigger_value = sl_esc_attr($trigger_value);
		$html = "<div class='sl_{$field}_{$trigger_value}_subsection$hidden'>$html</div>";
		return $html;
	}
	
	/**
	 * Creates multiple admin form subsections.
	 * 
	 * @since 3.8
	 * @uses get_admin_form_subsection()
	 * @see get_input_element()
	 * 
	 * @param string $field
	 * @param array $subsections Array of ($field => $trigger_value)
	 * @return string
	 */
	function get_admin_form_subsections($field, $current_value, $subsections) {
		$allhtml = '';
		if (!in_array($current_value, $subsection_keys = array_keys($subsections))) $current_value = $subsection_keys[0];
		foreach ($subsections as $trigger_value => $html)
			$allhtml .= $this->get_admin_form_subsection($field, $current_value, $trigger_value, $html);
		return $allhtml;
	}
	
	
	/********** ADMIN FORM FUNCTIONS **********/
	
	/**
	 * Begins an administration form.
	 * Outputs a subheader if provided, queues a success message upon settings update, outputs queued messages,
	 * opens a form tag, outputs a nonce field and other WordPress fields, and begins a form table.
	 * 
	 * @since 0.1
	 * @uses SEO_LAT_Tool_Plus::key_to_hook()
	 * @uses get_module_key()
	 * @uses admin_subheader()
	 * @uses is_action()
	 * @uses print_message()
	 * @uses get_parent_module()
	 * 
	 * @param mixed $header The text of the subheader that should go right before the form. Optional.
	 * @param boolean $table Whether or not to start a form table.
	 */
	function admin_form_start($header = false, $table = true, $form = true) {
		
		if ($header) $this->admin_subheader($header);
		
		if ($form) {
			$hook = $this->plugin->key_to_hook($this->get_module_or_parent_key());
			if ($this->is_action('update')) $this->print_message('success', __('Settings updated.', 'seolat-tool-plus'));
			echo "<form id='sl-admin-form' class='form-horizontal' method='post' action='?page=$hook'>\n";
			settings_fields($hook);
		}
		
		echo "\n";
		//if ($table) echo "<table class='form-table'>\n";
	}
	
	/**
	 * Ends an administration form.
	 * Closes the table tag, outputs a "Save Changes" button, and closes the form tag.
	 * 
	 * @since 0.1
	 * @uses get_parent_module()
	 * 
	 * @param string|false $button The label of the submit button.
	 * @param boolean $table Whether or not a form table should be ended.
	 */
	function admin_form_end($button = null, $table = true) {
		
		if ($button === null) $button = __('Save Changes'); //This string is used in normal WP, so we don't need a textdomain
		//if ($table) echo "</table>\n";
		
		if ($button !== false) {
?>
<p class="submit">
	<input type="submit" class="button-primary" value="<?php echo $button ?>" />
</p>
</form>
<?php
		}
	}
	function admin_form_save_top($button = null, $table = true) {
		
		if ($button === null) $button = __('Save Changes'); //This string is used in normal WP, so we don't need a textdomain
		//if ($table) echo "</table>\n";
		
		if ($button !== false) {
?>
<p class="submit">
	<input type="submit" class="button-primary" value="<?php echo $button ?>" />
</p>
<?php
		}
	}
	function close_form_ani(){
		echo "</form>";
	}
	/**
	 * Begins an admin form table.
	 * 
	 * @since 1.5
	 */
	function admin_form_table_start() {
		//echo "<table class='form-table'>\n";
	}
	
	/**
	 * Ends an admin form table
	 * 
	 * @since 1.5
	 */
	function admin_form_table_end() {
		//echo "</table>\n";
	}
	
	/**
	 * @since 5.8
	 */
	function child_admin_form_start($table=true) {
		if ($this->get_parent_module() && $this->plugin->module_exists($this->get_parent_module())) {
			if ($table) $this->admin_form_table_start();
		} else {
			$this->admin_form_start(false, $table);
		}
	}
	
	/**
	 * @since 5.8
	 */
	function child_admin_form_end($table=true) {
		if ($this->get_parent_module() && $this->plugin->module_exists($this->get_parent_module())) {
			if ($table) $this->admin_form_table_end();
		} else {
			$this->admin_form_end(null, $table);
		}
	}
	
	/**
	 * Begins a "widefat" WordPress table.
	 * 
	 * @since 1.8
	 * 
	 * @param $headers Array of (CSS class => Internationalized column title)
	 */
	function admin_wftable_start($headers = false) {
		echo "\n<table class='table table-bordered'>\n";
		if ($headers)
			$this->table_column_headers($headers);
		else {
			echo "\t<thead><tr>\n";
			print_column_headers($this->plugin_page_hook);
			echo "\t</tr></thead>\n";
			echo "\t<tfoot><tr>\n";
			print_column_headers($this->plugin_page_hook);
			echo "\t</tr></tfoot>\n";
		}
		echo "\t<tbody>\n";
	}
	
	/**
	 * Outputs a <tr> of <th scope="col"></th> tags based on an array of column headers.
	 * 
	 * @since 2.1
	 * 
	 * @param $headers Array of (CSS class => Internationalized column title)
	 */
	function table_column_headers($headers) {
		echo "\t<thead><tr>\n";
		$mk = $this->get_module_key();
		foreach ($headers as $class => $header) {
			$class = is_numeric($class) ? '' : " class='sl-$mk-$class sl-$class'";
			echo "\t\t<th scope='col'$class>$header</th>\n";
		}
		echo "\t</tr></thead>\n";
	}
	
	/**
	 * Outputs <td> tags based on an array of cell data.
	 * 
	 * @since 2.1
	 * 
	 * @param $headers Array of (CSS class => Cell data)
	 */
	function table_cells($cells) {
		
		if (count($this->get_admin_table_columns())) {
			$columns = get_column_headers($this->plugin_page_hook);
			$hidden = get_hidden_columns($this->plugin_page_hook);
			foreach ( $columns as $column_name => $column_display_name ) {
				$class = "class=\"$column_name column-$column_name\"";
				$style = in_array($column_name, $hidden) ? ' style="display:none;"' : '';
				echo "\t\t<td $class$style>".$cells[$column_name]."</td>\n";
			}
		} elseif (is_array($cells) && count($cells)) {
			foreach ($cells as $class => $content) {
				$class = is_numeric($class) ? '' : " class='sl-$class'";
				echo "\t\t<td$class>$content</td>\n";
			}
		}
	}
	
	/**
	 * Outputs a <tr> tag with <td> children.
	 * 
	 * @since 2.9
	 */
	function table_row($cells, $id, $class) {
		$mk = $this->get_module_key();
		echo "\t<tr id='sl-$mk-$id' class='sl-$mk-$class'>\n";
		$this->table_cells($cells);
		echo "\t</tr>\n";
	}
	
	/**
	 * Ends a "widefat" WordPress table.
	 * 
	 * @since 1.8
	 */
	function admin_wftable_end() {
		echo "\t</tbody>\n</table>\n";
	}
	
	/**
	 * Outputs the HTML that begins an admin form group.
	 * 
	 * @since 1.5
	 * 
	 * @param string $title The title of the group.
	 * @param bool $newtable Whether to open a new <table> element.
	 */
	function admin_form_group_start($title, $newtable=true) {
		echo "<div class='form-group'>\n<label class='col-sm-4 col-md-4 control-label'>$title</label><div class='col-sm-4 col-md-4'>\n";
	}
	
	/**
	 * Outputs the HTML that ends an admin form group.
	 * 
	 * @since 1.5
	 * 
	 * @param bool $newtable Whether to close a <table> element.
	 */
	function admin_form_group_end($newtable=true) {
		echo "</div>\n<div class='col-sm-4 col-md-4 help-text'>\n</div>\n</div>\n";
	}
	
	function admin_form_indent_start() {
		echo "<div class='form-group'><div class='col-md-12'>";
	}
	
	function admin_form_indent_end() {
		echo "</div></div>";
	}
	
	/**
	 * Outputs a text block into an admin form.
	 * 
	 * @since 1.5
	 * 
	 * @param string $text
	 */
	function textblock($text) {
		echo "<div class='form-group sl-admin-form-textblock'>\n<div class='col-md-12'>\n";
		echo $text;
		echo "\n</div>\n</div>\n";
	}
	
	/**
	 * Outputs a group of checkboxes into an admin form, and saves the values into the database after form submission.
	 * 
	 * @since 0.1
	 * @uses is_action()
	 * @uses update_setting()
	 * @uses get_module_key()
	 * @uses get_setting()
	 * 
	 * @param array $checkboxes An array of checkboxes. (Field/setting IDs are the keys, and descriptions are the values.)
	 * @param mixed $grouptext The text to display in a table cell to the left of the one containing the checkboxes. Optional.
	 */
	function checkboxes($checkboxes, $grouptext=false, $args=array()) {
		
		extract(wp_parse_args($args, array(
			  'output_tr' => true
		)));
		
		//Save checkbox settings after form submission
		if ($this->is_action('update')) {
			foreach ($checkboxes as $name => $desc) {
				$new_value = isset($_POST[$name]) ? ($_POST[$name] == '1') : false;
				
				if (is_array($desc)) {
					$disabled = isset($desc['disabled']) ? $desc['disabled'] : false;
					$desc = isset($desc['description']) ? $desc['description'] : '';
				} else {
					$disabled = false;
				}
				
				if (!$disabled)
					$this->update_setting($name, $new_value);
				
				if (strpos($desc, '%d') !== false) {
					$name .= '_value';
					$this->update_setting($name, lat_string::to_int($_POST[$name]));
				}
			}
		}
		
		if ($grouptext)
			$this->admin_form_group_start($grouptext, false);
		elseif ($output_tr)
			echo "<div class='form-group sl-admin-form-checkbox'>\n<div class='col-md-12'>\n";
		
		if (is_array($checkboxes)) {
			foreach ($checkboxes as $name => $desc) {
				
				if (is_array($desc)) {
					$indent = isset($desc['indent']) ? $desc['indent'] : false;
					$disabled = isset($desc['disabled']) ? $desc['disabled'] : false;
					$checked = isset($desc['checked']) ? $desc['checked'] : null;
					$desc = $desc['description'];
				} else {
					$indent = false;
					$disabled = false;
					$checked = null;
				}
				
				register_setting($this->get_module_key(), $name, array('lat_string', 'to_int'));
				$name = sl_esc_attr($name);
				
				if (strpos($desc, '%d') === false) {
					$onclick = '';
				} else {
					$int_var_name = $name.'_value';
					$int_var_value = lat_string::to_int($this->get_setting($int_var_name));
					if ($this->get_setting($name) === true) $sfdisabled = ''; else $sfdisabled = "readonly='readonly' ";
					$desc = str_replace('%d', "</label><input name='$int_var_name' id='$int_var_name' class='form-control input-sm nowidth-input' type='text' value='$int_var_value' size='2' maxlength='3' $sfdisabled/><label for='$name'>", $desc);
					$desc = str_replace("<label for='$name'></label>", '', $desc);
					$onclick = " onclick=\"javascript:document.getElementById('$int_var_name').readOnly=!this.checked;\"";
				}
				
				if ($indent) $labelclass = " class='sl-indent'"; else $labelclass = '';
				echo "<div class='checkbox'><label for='$name'$labelclass><input name='$name' id='$name' type='checkbox' value='1'";
				if ($checked !== false && ($checked === true || $this->get_setting($name) === true)) echo " checked='checked'";
				if ($disabled) echo " disabled='disabled'";
				echo "$onclick /> $desc</label></div>\n";
			}
		}
		
		if ($grouptext) {
			$this->admin_form_group_end(false);
		} elseif ($output_tr) {
			echo "</div>\n</div>\n";
		}
	}
	
	/**
	 * Outputs a single checkbox into an admin form and saves its value into the database after form submission.
	 * 
	 * @since 1.5
	 * @uses checkboxes()
	 * 
	 * @param string $id The field/setting ID.
	 * @param string $desc The checkbox's label.
	 * @param mixed $grouptext The text to display in a table cell to the left of the one containing the checkbox. Optional.
	 * @return string The HTML that would render the checkbox.
	 */
	function checkbox($id, $desc, $grouptext = false, $args=array()) {
		$this->checkboxes(array($id => $desc), $grouptext, $args);
	}
	
	/**
	 * Outputs a set of radio buttons into an admin form and saves the set's value into the database after form submission.
	 * 
	 * @since 1.5
	 * @uses is_action()
	 * @uses update_setting()
	 * @uses admin_form_group_start()
	 * @uses admin_form_group_end()
	 * @uses sl_esc_attr()
	 * @uses get_setting()
	 * 
	 * @param string $name The name of the set of radio buttons.
	 * @param array $values The keys of this array are the radio button values, and the array values are the label strings.
	 * @param string|false $grouptext The text to display in a table cell to the left of the one containing the radio buttons. Optional.
	 */
	function radiobuttons($name, $values, $grouptext=false) {
		
		//Save radio button setting after form submission
		if ($this->is_action('update') && isset($_POST[$name]))
			$this->update_setting($name, $_POST[$name]);
		
		if ($grouptext)
			$this->admin_form_group_start($grouptext, false);
		else
			echo "<tr valign='top' class='sl-admin-form-radio'>\n<td colspan='2'>\n";
		
		if (is_array($values)) {
			
			register_setting($this->get_module_key(), $name);
			$name = sl_esc_attr($name);
			
			$first = true;
			foreach ($values as $value => $desc) {
				
				$value = sl_esc_attr($value);
				$id = "{$name}_{$value}";
				
				$current = (strcmp($this->get_setting($name), $value) == 0);
				$class = $first ? 'first' : ''; $first = false;
				if ($current) $class .= ' current-setting';
				$class = trim($class);
				if ($class) $class = " class='$class'";
				
				extract($this->insert_subfield_textboxes($name, $desc));
				
				echo "<div class='radio'><label for='$id'$class><input name='$name' id='$id' type='radio' value='$value'";
				if ($current) echo " checked='checked'";
				echo " /> $label";
				
				if (!lat_string::has($label, '</label>')) echo '</label>';
				echo "</div>\n";
			}
		}
		
		if ($grouptext)
			$this->admin_form_group_end(false);
	}
	
	/**
	 * Outputs a dropdown into an admin form and saves the dropdown's value into the database after form submission.
	 * 
	 * @since 3.7
	 * @uses is_action()
	 * @uses update_setting()
	 * @uses admin_form_group_start()
	 * @uses admin_form_group_end()
	 * @uses sl_esc_attr()
	 * @uses get_setting()
	 * 
	 * @param string $name The name of the setting which the dropdown is supposed to set.
	 * @param array $values The keys of this array are the possible dropdown option values, and the array values are the option label strings.
	 * @param string|false $grouptext The text to display in a table cell to the left of the one containing the dropdown. Optional.
	 * @param string $text A printf-style format string in which "%s" is replaced with the dropdown. Use this to put text before or after the dropdown.
	 */
	function dropdown($name, $values, $grouptext=false, $text='%s', $args=array()) {
		
		$in_table = isset($args['in_table']) ? $args['in_table'] : true;
		
		//Save dropdown setting after form submission
		if ($this->is_action('update') && isset($_POST[$name]))
			$this->update_setting($name, $_POST[$name]);
		
		if ($grouptext)
			$this->admin_form_group_start($grouptext, false);
		elseif ($in_table)
			echo "<div class='form-group sl-admin-form-dropdown'>\n<div class='col-sm-4 col-md-4'>\n";
		
		if (is_array($values)) {
			
			register_setting($this->get_module_key(), $name);
			
			$name = sl_esc_attr($name);
			$dropdown =   "<select name='$name' id='$name'>\n"
						. lat_html::option_tags($values, $this->get_setting($name))
						. "</select>";
			printf($text, $dropdown);
		}
		
		if ($grouptext)
			$this->admin_form_group_end();
		elseif ($in_table)
			echo "</div>\n<div class='col-sm-4 col-md-4 help-text'>\n</div>\n</div>\n";
	}
	
	/**
	 * @since 3.0
	 */
	function insert_subfield_textboxes($name, $label, $enabled = true) {
		
		$pattern = '/%(d|s)({([a-z0-9_-]+)})?/';
		
		if (preg_match($pattern, $label, $matches)) {
			$is_int_field = ($matches[1] == 'd');
			$sfname = $name.'_value';
			
			if (isset($matches[3]))
				$sfname = $matches[3];
			
			if ($this->is_action('update'))
				$sfvalue = stripslashes($_POST[$sfname]);
			else
				$sfvalue = $this->get_setting($sfname);
			
			if ($is_int_field)
				$sfvalue = lat_string::to_int($sfvalue);
			
			if ($this->is_action('update'))
				$this->update_setting($sfname, $sfvalue);
			
			if ($enabled) $disabled = ''; else $disabled = " readonly='readonly'";
			
			$esfvalue = sl_esc_attr($sfvalue);
			$field_html = "</label><input class='textbox subfield form-control input-sm' name='$sfname' id='$sfname' type='text' value='$esfvalue'$disabled";
			if ($is_int_field) $field_html .= " size='2' maxlength='3'";
			$field_html .= " /><label for='$name'>";
			
			$label = preg_replace($pattern, $field_html, $label);
			$label = preg_replace("@<label for='$name'>$@", '', $label);
			
			$onclick = " onclick=\"javascript:document.getElementById('$sfname').readOnly=!this.checked;\"";
		} else
			$onclick = '';
		
		return compact('label', 'onclick');
	}
	
	/**
	 * Outputs a group of textboxes into an admin form, and saves the values into the database after form submission.
	 * Can also display a "Reset" link next to each textbox that reverts its value to a specified default.
	 * 
	 * @since 0.1
	 * @uses is_action()
	 * @uses update_setting()
	 * @uses get_module_key()
	 * @uses get_setting()
	 * 
	 * @param array $textboxes An array of textboxes. (Field/setting IDs are the keys, and descriptions are the values.)
	 * @param array $defaults An array of default textbox values that trigger "Reset" links. (The field/setting ID is the key, and the default value is the value.) Optional.
	 * @param mixed $grouptext The text to display in a table cell to the left of the one containing the textboxes. Optional.
	 */
	function textboxes($textboxes, $defaults=array(), $grouptext=false, $args=array(), $textbox_args=array()) {
		
		$is_tree_parent = isset($args['is_tree_parent']) ? $args['is_tree_parent'] : false;
		$is_ec_tree = isset($args['is_ec_tree']) ? $args['is_ec_tree'] : false;
		$tree_level = isset($args['tree_level']) ? $args['tree_level'] : false;
		$disabled = isset($args['disabled']) ? $args['disabled'] : false;
		$in_table = isset($args['in_table']) ? $args['in_table'] : true;
		$help_text = isset($args['help_text']) ? $args['help_text'] : false;
		$counter_text = isset($args['counter_text']) ? $args['counter_text'] : false;
		$callout = isset($args['callout']) ? $args['callout'] : false;
		
		if (!$disabled && $this->is_action('update')) {
			foreach ($textboxes as $id => $title) {
				if (isset($_POST[$id]))
					$this->update_setting($id, stripslashes(sanitize_text_field($_POST[$id])));
			}
		}
		
		$indentattrs = $indenttoggle = $hidden = '';
		if ($tree_level !== false) {
			$indentattrs = " class='sl-indent sl-indent-level-{$tree_level}'";
			if ($is_ec_tree) {
				if ($is_tree_parent)
					$indenttoggle = "<span class='sl-child-fields-toggle'>+</span> ";
				else
					$indenttoggle = "<span class='sl-child-fields-toggle-filler'> </span> ";
				
				if ($tree_level > 1)
					$hidden = " style='display: none;'";
			}
		}
		
		if ($grouptext) $this->admin_form_group_start($grouptext, false);
		
		foreach ($textboxes as $id => $title) {
			
			$before = isset($textbox_args[$id]['before']) ? $textbox_args[$id]['before'] : '';
			$after  = isset($textbox_args[$id]['after'])  ? $textbox_args[$id]['after']  : '';
			$placeholder = isset($textbox_args[$id]['placeholder']) ? $textbox_args[$id]['placeholder']  : '';
			
			register_setting($this->get_module_key(), $id);
			$value = sl_esc_editable_html($this->get_setting($id));
			$id = sl_esc_attr($id);
			$resetmessage = sl_esc_attr(__('Are you sure you want to replace the textbox contents with this default value?', 'seolat-tool-plus'));
			
			if ($callout)
				echo "<div class='bs-callout bs-callout-grey'><h4>$callout</h4></div>\n";
				
			if ($grouptext)
				echo "<div class='form-group'><label class='col-sm-3 col-md-3 control-label' for='$id'>$title</label>\n<div class='col-sm-4 col-md-4'>\n";
			elseif ($in_table && strpos($title, '</a>') === false)
				echo "<div class='form-group'$indentattrs$hidden>\n<label class='col-sm-3 col-md-3 control-label' for='$id'><span class='sl-field-label-text'>$title</span></label>\n<div class='col-sm-4 col-md-4'>\n";
			elseif ($in_table)
				echo "<div class='form-group'$indentattrs$hidden>\n<label class='col-sm-3 col-md-3 control-label' for='$id'><span class='sl-field-label-text'>$title</span></label>\n<div class='col-sm-4 col-md-4'>\n";
			
			echo $before;
			
			echo "<input name='$id' id='$id' type='text' value='$value'  class='form-control input-sm regular-text' ";
			
			if ($placeholder) {
				$a_placeholder = sl_esc_attr($placeholder);
				echo "placeholder='$placeholder' ";
			}
			
			if ($disabled)
				echo "disabled='disabled' />";
			elseif (isset($defaults[$id])) {
				$default = sl_esc_editable_html($defaults[$id]);
				echo "onkeyup=\"javascript:sl_textbox_value_changed(this, '$default', '{$id}_reset')\" />";
				echo "&nbsp;<a href=\"#\" id=\"{$id}_reset\" onclick=\"javascript:sl_reset_textbox('$id', '$default', '$resetmessage', this); return false;\"";
				if ($default == $value) echo ' class="hidden"';
				echo ">";
				_e('Reset', 'seolat-tool-plus');
				echo "</a>";
				
				if (isset($args['open_url_value_link']))
					echo ' |';
			} else {
				echo " onkeyup=\"javascript:document.getElementById('{$id}_charcount').innerHTML = document.getElementById('$id').value.length\" />";
			}
			
			if (isset($args['open_url_value_link'])) {
				echo " <a href='#' onclick=\"javascript:window.open(document.getElementById('$id').value);return false;\">";
				echo sl_esc_html($args['open_url_value_link']);
				echo '</a>';
			}
			
			echo $after;
			
			if ($grouptext)
				echo "</div>\n";
			elseif ($in_table)
				echo "</div>\n";
				
			if ($help_text)
				echo "<div class='col-sm-3 col-md-3 help-text'>$help_text</div>\n<div class='col-sm-2 col-md-2'>".sprintf(__($counter_text, 'seolat-tool-plus'), "<strong  id='{$id}_charcount'>".strlen($value)."</strong>")."</div>\n</div>\n";
			else
				echo "<div class='col-sm-3 col-md-3'></div>\n<div class='col-sm-2 col-md-2'>".sprintf(__($counter_text, 'seolat-tool-plus'), "<strong  id='{$id}_charcount'>".strlen($value)."</strong>")."</div>\n</div>\n";
		}
		
		if ($grouptext) $this->admin_form_group_end(false);
	}
	
	/**
	 * Outputs a single textbox into an admin form and saves its value into the database after form submission.
	 * 
	 * @since 0.1
	 * @uses textboxes()
	 * 
	 * @param string $id The field/setting ID.
	 * @param string $title The label of the HTML element.
	 * @param string|false $default The default textbox value. Setting this will trigger a "Reset" link. Optional.
	 * @return string The HTML that would render the textbox.
	 */
	function textbox($id, $title, $default=false, $grouptext=false, $args=array(), $textbox_args=array()) {
		if ($default === false) $default = array(); else $default = array($id => $default);
		$this->textboxes(array($id => $title), $default, $grouptext, $args, array($id => $textbox_args));
	}
	
	/**
	 * Outputs a group of textareas into an admin form, and saves the values into the database after form submission.
	 * 
	 * @since 0.1
	 * @uses is_action()
	 * @uses update_setting()
	 * @uses get_module_key()
	 * @uses get_setting()
	 * 
	 * @param array $textareas An array of textareas. (Field/setting IDs are the keys, and descriptions are the values.)
	 * @param int $rows The value of the textareas' rows attribute.
	 * @param int $cols The value of the textareas' cols attribute.
	 */
	function textareas($textareas, $rows = 5, $cols = 30, $args=array()) {
		
		$disabled = isset($args['disabled']) ? $args['disabled'] : false;
		$help_text = isset($args['help_text']) ? $args['help_text'] : false;
		$counter_text = isset($args['counter_text']) ? $args['counter_text'] : false;
		
		if (!$disabled && $this->is_action('update')) {
			foreach ($textareas as $id => $title) {
				if (isset($_POST[$id]))
					$this->update_setting($id, stripslashes($_POST[$id]));
			}
		}
		
		foreach ($textareas as $id => $title) {
			register_setting($this->get_module_key(), $id);
			$value = sl_esc_editable_html($this->get_setting($id));
			$id = sl_esc_attr($id);
			
			echo "<div class='form-group'>\n";
			if ($title) echo "<label class='col-sm-3 col-md-3 control-label' for='$id'>$title</label>\n";
			echo "<div class='col-sm-4 col-md-4'>";
			echo "<textarea name='$id' id='$id' type='text' class='form-control regular-text' cols='$cols' rows='$rows' onkeyup=\"javascript:document.getElementById('{$id}_charcount').innerHTML = document.getElementById('$id').value.length\"";
			if ($disabled) echo " disabled='disabled'";
			echo ">$value</textarea>";
			echo "</div>\n<div class='col-sm-3 col-md-3 help-text'>$help_text</div>\n<div class='col-sm-2 col-md-2'>".sprintf(__($counter_text, 'seolat-tool-plus'), "<strong  id='{$id}_charcount'>".strlen($value)."</strong>")."</div>\n</div>\n";
		}
	}
	
	/**
	 * Outputs a single textarea into an admin form and saves its value into the database after form submission.
	 * 
	 * @since 0.1
	 * @uses textareas()
	 * 
	 * @param string $id The field/setting ID.
	 * @param string $title The label of the HTML element.
	 * @param int $rows The value of the textarea's rows attribute.
	 * @param int $cols The value of the textarea's cols attribute.
	 * @return string The HTML that would render the textarea.
	 */
	function textarea($id, $title = '', $rows = 5, $cols = 30, $args=array()) {
		$this->textareas(array($id => $title), $rows, $cols, $args);
	}
	
	/**
	 * @since 7.3
	 */
	function jlsuggest_boxes($jls_boxes) {
		
		if ($this->is_action('update')) {
			foreach ($jls_boxes as $jls_box) {
				
				if (!isset($jls_box['id']))
					continue;
				
				$id = $jls_box['id'];
				
				if (isset($_POST[$id]))
					$this->update_setting($id, stripslashes($_POST[$id]));
			}
		}
		
		foreach ($jls_boxes as $jls_box) {
			
			if (!isset($jls_box['id']))
				continue;
			
			$jls_box = wp_parse_args($jls_box, array(
				  'title' => ''
				, 'params' => ''
			));
			
			extract($jls_box, EXTR_SKIP);
			
			register_setting($this->get_module_key(), $id);
			
			echo "<div class='form-group'>\n";
			if ($title) echo "<label class='col-sm-4 col-md-4 control-label' for='$id'>$title</label>\n";
			echo "<div class='col-sm-4 col-md-4'>";
			echo $this->get_jlsuggest_box($id, $this->get_setting($id), $params);
			echo "</div>\n<div class='col-sm-4 col-md-4 help-text'>\n</div>\n</div>\n";
		}
	}
	
	/**
	 * @since 7.3
	 */
	function jlsuggest_box($id, $title, $params='') {
		$this->jlsuggest_boxes(array(compact('id', 'title', 'params')));
	}
	/**
	 * @since 7.6.3
	 */
	function medialib_boxes($media_boxes) {
		
		if ($this->is_action('update')) {
			foreach ($media_boxes as $media_box) {
				
				if (!isset($media_box['id']))
					continue;
				
				$id = $media_box['id'];
				
				if (isset($_POST[$id]))
					$this->update_setting($id, stripslashes($_POST[$id]));
			}
		}
		
		foreach ($media_boxes as $media_box) {
			
			if (!isset($media_box['id']))
				continue;
			
			$media_box = wp_parse_args($media_box, array(
				  'title' => ''
				, 'params' => ''
			));
			
			extract($media_box, EXTR_SKIP);
			
			register_setting($this->get_module_key(), $id);
			
			echo "<div class='form-group'>\n";
			if ($title) echo "<label class='col-sm-4 col-md-4 control-label' for='$id'>$title</label>\n";
			echo "<div class='col-sm-4 col-md-4'>";
			echo "<div class='input-group'>
				<input id='".sl_esc_attr($id)."' name='".sl_esc_attr($id)."' type='text' class='wpu-image form-control' size='40' value='".$this->get_setting($id)."'>
				<span class='input-group-btn'>
					<span class='btn btn-custom btn-file wpu-media-upload'>
						<i class='fa fa-upload'></i> Upload Image <input type='file'>
					</span>
				</span>
			</div>";
			echo "</div>\n<div class='col-sm-4 col-md-4 help-text'>\n</div>\n</div>\n";
		}
	}
	
	/**
	 * @since 7.6.3
	 */
	function medialib_box($id, $title, $params='') {
		$this->medialib_boxes(array(compact('id', 'title', 'params')));
	}
	
	/********** ADMIN SECURITY FUNCTIONS **********/
	
	/**
	 * Determines if a particular nonce-secured admin action is being executed.
	 * 
	 * @since 0.1
	 * @uses SEO_LAT_Tool_Plus::key_to_hook()
	 * @uses get_module_key()
	 * @uses nonce_validates()	 
	 * 
	 * @param string $action The name of the action to check.
	 * @return bool Whether or not the action is being executed.
	 */
	function is_action($action) {
		if (!isset($_GET['object']) || !($object = $_GET['object'])) $object = false;
		return (
					!empty($_GET['page'])
					&& (
						   ( strcasecmp($_GET['page'], $this->plugin->key_to_hook($this->get_module_key())) == 0 ) //Is $this module being shown?
						|| ( strlen($this->get_parent_module()) && strcasecmp($_GET['page'], $this->plugin->key_to_hook($this->get_parent_module())) == 0) //Is the parent module being shown?
					)
					&& (
						   (!empty($_GET['action']) && $_GET['action'] == $action)
						|| (!empty($_POST['action']) && $_POST['action'] == $action)
					) //Is this $action being executed?
					&& $this->nonce_validates($action, $object) //Is the nonce valid?
		);
	}
	
	/**
	 * Determines whether a nonce is valid.
	 * 
	 * @since 0.1
	 * @uses get_nonce_handle()
	 * 
	 * @param string $action The name of the action.
	 * @param mixed $id The ID of the object being acted upon. Optional.
	 * @return bool Whether or not the nonce is valid.
	 */
	function nonce_validates($action, $id = false) {
		return check_admin_referer($this->get_nonce_handle($action, $id));
	}
	
	/**
	 * Generates a unique name for a nonce.
	 * 
	 * @since 0.1
	 * @uses get_parent_module()
	 * @uses get_module_key()
	 * @uses SL_PLUGIN_NAME
	 * 
	 * @param string $action The name of the action.
	 * @param mixed $id The ID of the object being acted upon. Optional.
	 * @return The handle to use for the nonce.
	 */
	function get_nonce_handle($action, $id = false) {
		
		$key = $this->get_parent_module();
		if (!$key || !$this->plugin->module_exists($key)) $key = $this->get_module_key();
		
		$hook = $this->plugin->key_to_hook($key);
		
		if (strcmp($action, 'update') == 0) {
			//We use the settings_fields() function, which outputs a nonce in this particular format.
			return "$hook-options";
		} else {
			if ($id) $id = '-'.md5($id); else $id = '';
			$handle = SL_PLUGIN_NAME."-$hook-$action$id";
			return strtolower(str_replace(' ', '-', $handle));
		}
	}
	
	/**
	 * Returns a GET-action URL with an appended nonce.
	 * 
	 * @since 0.1
	 * @uses get_module_key()
	 * @uses get_nonce_handle()
	 * 
	 * @param string $action The name of the action.
	 * @param mixed $id The ID of the object being acted upon. Optional.
	 * @return The URL to use in an <a> tag.
	 */
	function get_nonce_url($action, $object=false) {
		$action = urlencode($action);
		if ($object) $objectqs = '&object='.urlencode($object); else $objectqs = '';
		
		$hook = $this->plugin->key_to_hook($this->get_module_or_parent_key());
		
		//We don't need to escape ampersands since wp_nonce_url will do that for us
		return wp_nonce_url("?page=$hook&action=$action$objectqs",
			$this->get_nonce_handle($action, $object));
	}
	
	
	/********** ADMIN MESSAGE FUNCTIONS **********/
	
	/**
	 * Print a message (and any previously-queued messages) right away.
	 * 
	 * @since 0.1
	 * @uses queue_message()
	 * @uses print_messages()
	 * 
	 * @param string $type The message's type. Valid values are success, error, warning, and info.
	 * @param string $message The message text.
	 */
	function print_message($type, $message) {
		$this->queue_message($type, $message);
		$this->print_messages();
	}
	
	/**
	 * Adds a message to the queue.
	 * 
	 * @since 0.1
	 * @uses $messages
	 * 
	 * @param string $type The message's type. Valid values are success, error, warning, and info.
	 * @param string $message The message text.
	 */
	function queue_message($type, $message) {
		$this->messages[$type][] = $message;
	}
	
	/**
	 * Prints all queued messages and flushes the queue.
	 * 
	 * @since 0.1
	 * @uses $messages
	 */
	function print_messages() {
		foreach ($this->messages as $type => $messages) {
			$messages = implode('<br />', $messages);
			if ($messages) {
				$type = sl_esc_attr($type);
				echo "<div class='sl-message'><p class='sl-$type'>$messages</p></div>\n";
			}
		}
		
		$this->messages = array();
	}
	
	/**
	 * Prints a mini-style message.
	 * 
	 * @since 2.1
	 */
	function print_mini_message($type, $message) {
		$type = sl_esc_attr($type);
		echo "<div class='sl-status sl-$type'>$message</div>";
	}
	
	/********** ADMIN META FUNCTIONS **********/
	
	/**
	 * Gets a specified meta value of the current post (i.e. the post currently being edited in the admin,
	 * the post being shown, the post now in the loop, or the post with specified ID).
	 * 
	 * @since 0.1
	 * 
	 * @param string $key The meta key to fetch.
	 * @param mixed $id The ID number of the post/page.
	 * @return string The meta value requested.
	 */
	function get_postmeta($key, $id=false) {
		
		if (!$id) {
			//This code is different from lat_wp::get_post_id();
			if (is_admin()) {
				$id = empty($_REQUEST['post']) ? false : intval($_REQUEST['post']);
				global $post;
			} elseif (in_the_loop()) {
				$id = intval(get_the_ID());
				global $post;
			} elseif (is_singular()) {
				global $wp_query;
				$id = $wp_query->get_queried_object_id();
				$post = $wp_query->get_queried_object();
			}
		}
		
		if ($id) {
			
			if (isset($post) && $post)
				$_post = $post;
			else
				$_post = get_post($id);
			
			$value = get_post_meta($id, "_sl_$key", true);
			if ( ! $value ) {
				$value = $value = get_post_meta($id, "_su_$key", true);
			}
			$value = apply_filters("sl_get_postmeta", $value, $key, $_post);
			$value = apply_filters("sl_get_postmeta-$key", $value, $key, $_post);
		} else
			$value = '';
		
		return $value;
	}
	
	/**
	 * Generates the HTML for multiple post meta textboxes.
	 * 
	 * @since 0.1
	 * @uses get_postmeta()
	 * 
	 * @param array $textboxes An array of textboxes. (Field/setting IDs are the keys, and descriptions are the values.)
	 * @return string The HTML that would render the textboxes.
	 */
	function get_postmeta_textboxes($textboxes, $textbox_args=array(), $grouptext=false) {
		
		$html = '';
		
		if ($grouptext) {
			$h_grouptext = esc_html($grouptext);
			$html = "<div class='form-group sl textbox'>\n<h4 class='col-sm-4 col-md-4 control-label'>$h_grouptext</h4>\n<div class='col-sm-4 col-md-4'></div>\n<div class='col-sm-4 col-md-4 help-text'>\n</div>\n</div>\n";
		}
		
		foreach ($textboxes as $id => $title) {
			
			$type = isset($textbox_args[$id]['type']) ? $textbox_args[$id]['type'] : 'text';
			$input_clss = ($type == 'text') ? 'input-sm ' : '';
			 
			register_setting('seolat-tool-plus', $id);
			$value = sl_esc_editable_html($this->get_postmeta($id));
			$id = "_sl_".sl_esc_attr($id);
			
			$e_title = sl_esc_attr($title);
			
			$html = "<div class='form-group sl textbox'>\n<label class='col-sm-4 col-md-4 control-label' for='$id'>$title</label>\n<div class='col-sm-4 col-md-4'><input name='$id' id='$id' type='$type' value='$value' class='"
			. $input_clss."form-control regular-text' tabindex='2' />\n"
			. "</div>\n<div class='col-sm-4 col-md-4 help-text'>\n</div>\n</div>\n";
		}
		
		if ($grouptext) {
			$h_grouptext = esc_html($grouptext);
			//$html .= "</table></td>\n</tr>\n";
		}
		
		return $html;
	}
	
	/**
	 * Generates the HTML for a single post meta textbox.
	 * 
	 * @since 0.1
	 * @uses get_postmeta_textboxes()
	 * 
	 * @param string $id The ID of the HTML element.
	 * @param string $title The label of the HTML element.
	 * @return string The HTML that would render the textbox.
	 */
	function get_postmeta_textbox($id, $title, $args=array()) {
		return $this->get_postmeta_textboxes(array($id => $title), array($id => $args));
	}
	
	/**
	 * Generates the HTML for multiple post meta textareas.
	 * 
	 * @since 3.9
	 * @uses get_postmeta()
	 * 
	 * @param array $textareas An array of textareas. (Field/setting IDs are the keys, and descriptions are the values.)
	 * @return string The HTML that would render the textareas.
	 */
	function get_postmeta_textareas($textareas) {

		$html = '';
		
		foreach ($textareas as $id => $title) {
		
			register_setting('seolat-tool-plus', $id);
			$value = sl_esc_editable_html($this->get_postmeta($id));
			$id = "_sl_".sl_esc_attr($id);
			
			$html = "<div class='form-group sl textarea'>\n<label class='col-sm-4 col-md-4 control-label' for='$id'>$title</label>\n<div class='col-sm-4 col-md-4'><textarea name='$id' id='$id' class='form-control regular-text' tabindex='2' cols='60' rows='3'>$value</textarea>\n"
			."</div>\n<div class='col-sm-4 col-md-4 help-text'>\n</div>\n</div>\n";
		}
		
		return $html;
	}
	
	/**
	 * Generates the HTML for a single post meta textarea.
	 * 
	 * @since 3.9
	 * @uses get_postmeta_textareas()
	 * 
	 * @param string $id The ID of the HTML element.
	 * @param string $title The label of the HTML element.
	 * @return string The HTML that would render the textarea.
	 */
	function get_postmeta_textarea($id, $title) {
		return $this->get_postmeta_textareas(array($id => $title));
	}
	
	/**
	 * Generates the HTML for a group of post meta checkboxes.
	 * 
	 * @since 0.1
	 * @uses get_module_key()
	 * @uses get_postmeta()
	 * 
	 * @param array $checkboxes An array of checkboxes. (Field/setting IDs are the keys, and descriptions are the values.)
	 * @param string $grouptext The text to display in a table cell to the left of the one containing the checkboxes.
	 */
	function get_postmeta_checkboxes($checkboxes, $grouptext) {
		
		$valign = (is_array($checkboxes) && count($checkboxes) > 1) ? 'top' : 'middle';
		$html = "<div class='form-group sl checkboxes'>\n<label class='col-sm-4 col-md-4 control-label'>$grouptext</label>\n<div class='col-sm-4 col-md-4'>\n";
		
		if (is_array($checkboxes)) {
			foreach ($checkboxes as $name => $desc) {
				
				register_setting('seolat-tool-plus', $name);
				$checked = ($this->get_postmeta($name) == 1);
				$name = "_sl_".sl_esc_attr($name);
				
				$html .= "<div class='checkbox'><label for='$name'><input name='$name' id='$name' type='checkbox' tabindex='2' value='1'";
				if ($checked) $html .= " checked='checked'";
				$html .= " /> $desc</label></div>\n";
			}
		}
		
		$html .= "</div>\n<div class='col-sm-4 col-md-4 help-text'>\n</div>\n</div>\n";
		
		return $html;
	}
	
	/**
	 * Generates the HTML for a single post meta checkbox.
	 * 
	 * @since 0.1
	 * @uses get_postmeta_checkboxes()
	 * 
	 * @param string $id The ID of the HTML element.
	 * @param string $title The label of the HTML element.
	 * @param string $grouptext The text to display in a table cell to the left of the one containing the checkboxes.
	 * @return string The HTML that would render the textbox.
	 */
	function get_postmeta_checkbox($id, $title, $grouptext) {
		return $this->get_postmeta_checkboxes(array($id => $title), $grouptext);
	}
	
	/**
	 * Generates the HTML for a single <select> post meta dropdown.
	 * 
	 * @since 2.5
	 * @uses get_module_key()
	 * @uses get_postmeta()
	 * 
	 * @param string $name The name of the <select> element.
	 * @param array $options An array of options, where the array keys are the <option> values and the array values are the labels (<option> contents).
	 * @param string $grouptext The text to display in a table cell to the left of the one containing the dropdown.
	 * @return string $html
	 */
	function get_postmeta_dropdown($name, $options, $grouptext) {
		
		register_setting('seolat-tool-plus', $name);
		$current = $this->get_postmeta($name);
		if ($current === '') {
			$current = reset($options);
		}
		$name = "_sl_".sl_esc_attr($name);
		
		$html = "<div class='form-group sl dropdown'>\n<label class='col-sm-4 col-md-4 control-label' for='$name'>$grouptext</label>\n<div class='col-sm-4 col-md-4'>\n";
		$html .= "<select name='$name' id='$name' onchange='javascript:sl_toggle_select_children(this)'>\n";
		$html .= lat_html::option_tags($options, $current);
		$html .= "</select>\n";
		$html .= "</div>\n<div class='col-sm-4 col-md-4 help-text'></div>\n</div>\n";
		
		return $html;
	}
	
	/**
	 * Generates the HTML for multiple post meta JLSuggest boxes.
	 * 
	 * @since 7.3
	 * 
	 * @param array $jls_boxes An array of JLSuggest boxes. (Field/setting IDs are the keys, and descriptions are the values.)
	 * @return string The HTML for the JLSuggest boxes.
	 */
	function get_postmeta_jlsuggest_boxes($jls_boxes) {
		
		$html = '';
		
		foreach ($jls_boxes as $jls_box) {
			
			if (!isset($jls_box['id']) || !isset($jls_box['title']))
				continue;
			
			$id = $jls_box['id'];
			$title = $jls_box['title'];
			$params = isset($jls_box['params']) ? $jls_box['params'] : false;
			
			register_setting('seolat-tool-plus', $id);
			$value = sl_esc_editable_html($this->get_postmeta($id));
			$id = "_sl_".sl_esc_attr($id);
			
			$html .= "<div class='form-group sl jlsuggestbox'>\n<label class='col-sm-4 col-md-4 control-label' for='$id'>$title</label>\n"
					."<div class='col-sm-4 col-md-4 sl'>";
			$html .= $this->get_jlsuggest_box($id, $value, $params);
			$html .= "</div>\n<div class='col-sm-4 col-md-4 help-text'>\n</div>\n</div>\n";
		}
		
		return $html;
	}
	
	/**
	 * Generates the HTML for a single post meta JLSuggest box.
	 * 
	 * @since 7.3
	 * @uses get_postmeta_jlsuggest_boxes()
	 * 
	 * @param string $id The ID of the HTML element.
	 * @param string $title The label of the HTML element.
	 * @param string $params The value of the sl:params attribute of the JLSuggest box (optional).
	 * @return string The HTML that would render the JLSuggest box.
	 */
	function get_postmeta_jlsuggest_box($id, $title, $params=false) {
		$jls_box = compact('id', 'title', 'params');
		return $this->get_postmeta_jlsuggest_boxes(array($jls_box));
	}
	
	/**
	 * Generates the HTML for multiple post meta mediaupload boxes.
	 * 
	 * @since 7.6.3
	 * 
	 * @param array $media_boxes An array of mediaupload boxes. (Field/setting IDs are the keys, and descriptions are the values.)
	 * @return string The HTML for the mediaupload boxes.
	 */
	function get_postmeta_medialib_boxes($media_boxes) {
		
		$html = '';
		
		foreach ($media_boxes as $media_box) {
			
			if (!isset($media_box['id']) || !isset($media_box['title']))
				continue;
			
			$id = $media_box['id'];
			$title = $media_box['title'];
			
			register_setting('seolat-tool-plus', $id);
			$value = sl_esc_editable_html($this->get_postmeta($id));
			$id = "_sl_".sl_esc_attr($id);
			
			$html .= "<div class='form-group sl'>\n<label class='col-sm-4 col-md-4 control-label' for='$id'>$title</label>\n"
					."<div class='col-sm-4 col-md-4 sl'>";
			$html .= "<div class='input-group'>
				<input id='".sl_esc_attr($id)."' name='".sl_esc_attr($id)."' type='text' class='wpu-image form-control' size='40' value='".$value."'>
				<span class='input-group-btn'>
					<span class='btn btn-custom btn-file wpu-media-upload'>
						<i class='fa fa-upload'></i> Upload Image <input type='file'>
					</span>
				</span>
			</div>";
			$html .= "</div>\n<div class='col-sm-4 col-md-4 help-text'>\n</div>\n</div>\n";
		}
		
		return $html;
	}
	
	/**
	 * Generates the HTML for a single post meta mediaupload box.
	 * 
	 * @since 7.6.3
	 * @uses get_postmeta_medialib_boxes()
	 * 
	 * @param string $id The ID of the HTML element.
	 * @param string $title The label of the HTML element.
	 * @return string The HTML that would render the mediaupload box.
	 */
	function get_postmeta_medialib_box($id, $title) {
		$media_box = compact('id', 'title');
		return $this->get_postmeta_medialib_boxes(array($media_box));
	}
	
	/**
	 * Turns a <tr> into a post meta subsection.
	 * 
	 * @since 2.5
	 * @uses get_postmeta
	 * 
	 * @param string $field
	 * @param string $value
	 * @param string $html
	 * @return string $html
	 */
	function get_postmeta_subsection($field, $value, $html) {
		$hidden = ($this->get_postmeta($field) == $value) ? '' : ' hidden';
		
		$field = sl_esc_attr($field);
		$value = sl_esc_attr($value);
		$html = str_replace("<div class='form-group ", "<div class='form-group sl_{$field}_{$value}_subsection$hidden ", $html);
		return $html;
	}
	
	/**
	 * Gets a specified meta value of the current term.
	 * 
	 * @since 5.4
	 * 
	 * @param string $key The database setting where the metadata is stored. The function will add a "taxonomy_" prefix.
	 * @param mixed $id The ID number of the post/page.
	 * @return string The meta value requested.
	 */
	function get_termmeta($key, $id=false, $module=false) {
		
		global $wp_query;
		
		if (!$id && lat_wp::is_tax())
			$id = $wp_query->get_queried_object_id();
		
		if (!$id)
			return null;
		
		$tax_meta = $this->get_setting(lat_string::startwith($key, 'taxonomy_'), array(), $module);
		
		if (is_array($tax_meta) && isset($tax_meta[$id]))
			return $tax_meta[$id];
		
		return null;
	}
	
	/********** CRON FUNCTION **********/
	
	/**
	 * Creates a cron job if it doesn't already exists, and ensures it runs at the scheduled time.
	 * Should be called in a module's init() function.
	 * 
	 * @since 0.1
	 * @uses get_module_key()
	 * 
	 * @param string $function The name of the module function that should be run.
	 * @param string $recurrence How often the job should be run. Valid values are hourly, twicedaily, and daily.
	 */
	function cron($function, $recurrence) {
		
		$mk = $this->get_module_key();
		
		$hook = "sl-$mk-".str_replace('_', '-', $function);
		$start = time();
		
		if (wp_next_scheduled($hook) === false) {
			//This is a new cron job
			
			//Schedule the event
			wp_schedule_event($start, $recurrence, $hook);
			
			//Make a record of it
			$psdata = (array)get_option('seo_lat_tool_plus', array());
			$psdata['cron'][$mk][$function] = array($hook, $start, $recurrence);
			update_option('seo_lat_tool_plus', $psdata);
			
			//Run the event now
			call_user_func(array($this, $function));
		}
		
		add_action($hook, array(&$this, $function));
	}
	
	/********** JLSUGGEST **********/
	
	/**
	 * Initializes JLSuggest.
	 * Must be called in the admin_page_init() function of the module that wants to use JLSuggest.
	 * 
	 * @since 6.0
	 * @uses jlsuggest_xml_ns()
	 * @uses SEO_LAT_Tool_Plus::queue_js()
	 * @uses SEO_LAT_Tool_Plus::queue_css()
	 */
	function jlsuggest_init() {
		add_action('admin_xml_ns', array(&$this, 'jlsuggest_xml_ns'));
		$this->plugin->queue_js ('includes', 'encoder');
		$this->plugin->queue_js ('includes/jlsuggest', 'jlsuggest');
		$this->plugin->queue_css('includes/jlsuggest', 'jlsuggest');
	}
	
	/**
	 * Outputs the SEOLAT Tool Plus XMLNS used by JLSuggest.
	 * 
	 * @since 6.0
	 */
	function jlsuggest_xml_ns() {
		echo ' xmlns:sl="http://johnlamansky.com/xmlns/seo-lat" ';
	}
	
	/**
	 * Explodes a JLSuggest database string into an array with the destination type and the destination ID.
	 * 
	 * @since 6.0
	 * 
	 * @param $valstr The database string, e.g. http://example.com or obj_posttype_post/1
	 * @return array
	 */
	function jlsuggest_value_explode($valstr) {
		
		if (is_array($valstr)) {
			
			if (count($valstr) == 3)
				return $valstr;
			
		} elseif (is_string($valstr)) {
			
			if (lat_string::startswith($valstr, 'obj_')) {
				$valstr = lat_string::ltrim_str($valstr, 'obj_');
				
				$valarr = explode('/', $valstr);
				if (count($valarr) == 2) {
					$valarr_type = explode('_', $valarr[0], 2);
					if (count($valarr_type) == 2)
						return array($valarr_type[0], $valarr_type[1], $valarr[1]);
					else
						return array($valarr[0], null, $valarr[1]);
				} else
					return array($valstr, null, null);
			} else {
				return array('url', null, $valstr);
			}
		}
		
		return array('url', null, '');
	}
	
	/**
	 * Returns the HTML code for a JLSuggest textbox
	 * 
	 * @since 6.0
	 * 
	 * @param string $name The value of the textbox's name/ID attributes
	 * @param string $value The current database string associated with this textbox
	 */
	function get_jlsuggest_box($name, $value, $params='', $placeholder='') {
		
		list($to_genus, $to_type, $to_id) = $this->jlsuggest_value_explode($value);
		
		$text_dest = '';
		$disabled = false;
		
		switch ($to_genus) {
			
			case 'posttype':
				$selected_post = get_post($to_id);
				if ($selected_post) {
					$selected_post_type = get_post_type_object($selected_post->post_type);
					$text_dest = $selected_post->post_title . '<span class="type">&nbsp;&mdash;&nbsp;'.$selected_post_type->labels->singular_name.'</span>';
				} else {
					$selected_post_type = get_post_type_object($to_type);
					if ($selected_post_type)
						$text_dest = sprintf(__('A Deleted %s', 'seolat-tool-plus'), $selected_post_type->labels->singular_name);
					else
						$text_dest = __('A Deleted Post', 'seolat-tool-plus');
					$text_dest = '<span class="type">' . $text_dest . '</span>';
					$disabled = true;
				}
				break;
			case 'taxonomy':
				if ($selected_taxonomy = get_taxonomy($to_type)) {
					if ($selected_term = get_term($to_id, $selected_taxonomy->name)) {
						$text_dest = $selected_term->name . '<span class="type">&nbsp;&mdash;&nbsp;'.$selected_taxonomy->labels->singular_name.'</span>';
					} else {
						$text_dest = sprintf(__('A Deleted %s', 'seolat-tool-plus'), $selected_taxonomy->labels->singular_name);
						$text_dest = '<span class="type">' . $text_dest . '</span>';
						$disabled = true;
					}
				} else {
					$text_dest = __('A Deleted Term', 'seolat-tool-plus');
					$text_dest = '<span class="type">' . $text_dest . '</span>';
					$disabled = true;
				}
				break;
			case 'home':
				$text_dest = __('Blog Homepage', 'seolat-tool-plus');
				break;
			case 'author':
				if (is_user_member_of_blog($to_id)) {
					$selected_author = get_userdata($to_id);
					$text_dest = $selected_author->user_login . '<span class="type">&nbsp;&mdash;&nbsp;'.__('Author', 'seolat-tool-plus').'</span>';
				} else {
					$text_dest = __('A Deleted User', 'seolat-tool-plus');
					$text_dest = '<span class="type">' . $text_dest . '</span>';
					$disabled = true;
				}
				break;
			case 'internal-link-alias':
			
				$alias_dir = $this->get_setting('alias_dir', 'go', 'internal-link-aliases');
				$aliases = $this->get_setting('aliases', array(), 'internal-link-aliases');
				
				if (isset($aliases[$to_id]['to'])) {
					$h_alias_to = sl_esc_html($aliases[$to_id]['to']);
					$text_dest = "/$alias_dir/$h_alias_to/" . '<span class="type">&nbsp;&mdash;&nbsp;';
					
					if ($this->plugin->module_exists('internal-link-aliases')) {
						$text_dest .= __('Link Mask', 'seolat-tool-plus');
					} else {
						$text_dest .= __('Link Mask (Disabled)', 'seolat-tool-plus');
						$disabled = true;
					}
					$text_dest .= '</span>';
				} else {
					$text_dest = __('A Deleted Link Mask', 'seolat-tool-plus');
					$text_dest = '<span class="type">' . $text_dest . '</span>';
					$disabled = true;
				}
				
				break;
		}
		
		$is_url = (('url' == $to_genus) && !$text_dest);
		
		$to_genus_type = implode('_', array_filter(array($to_genus, $to_type)));
		$obj = 'obj_' . implode('/', array_filter(array($to_genus_type, $to_id)));
		
		//URL textbox
		//(hide if object is selected)
		$html = "<input name='$name' id='$name' value='";
		$html .= sl_esc_editable_html($is_url ? $to_id : $obj);
		$html .= "'";
		
		if ($params) {
			$e_params = sl_esc_attr($params);
			$html .= " sl:params='$e_params'";
		}
		
		if ($placeholder) {
			$e_placeholder = sl_esc_attr($placeholder);
			$html .= " placeholder='$e_placeholder'";
		}
		
		$html .= " type='text' class='form-control input-sm textbox regular-text jlsuggest'";
		$html .= ' title="' . __('Type a URL or start typing the name of an item on your site', 'seolat-tool-plus') . '"';
		$html .= $is_url ? '' : ' style="display:none;" ';
		$html .= ' />';
		
		//Object box
		//(hide if URL is entered)
		$disabled = $disabled ? ' jlsuggest-disabled' : '';
		$html .= "<div class='jls_text_dest$disabled'";
		$html .= $is_url ? ' style="display:none;" ' : '';
		$html .= '>';
		$html .= '<div class="jls_text_dest_text">';
		$html .= $text_dest;
		$html .= '</div>';
		$html .= '<div><a href="#" onclick="javascript:return false;" class="jls_text_dest_close" title="'.__('Remove this location from this textbox', 'seolat-tool-plus').'">'.__('X', 'seolat-tool-plus').'</a></div>';
		$html .= '</div>';
		
		return $html;
	}
	
	/**
	 * Converts a JLSuggest database string into a URL.
	 * 
	 * @since 6.0
	 * 
	 * @param string $value The JLSuggest database string to convert.
	 * @param bool $get_src_if_media Whether to get the URL to the actual media item rather than the URL to its WP-powered singular page, if the item is an attachment.
	 * @return string The URL of the referenced destination
	 */
	function jlsuggest_value_to_url($value, $get_src_if_media=false) {
		
		list($to_genus, $to_type, $to_id) = $this->jlsuggest_value_explode($value);
		
		switch ($to_genus) {
			case 'url':
				return $to_id; break;
			case 'posttype':
				$to_id = (int)$to_id;
				switch (get_post_status($to_id)) {
					case 'publish':
						if ($get_src_if_media && 'attachment' == get_post_type($to_id))
							return wp_get_attachment_url($to_id);
						
						return get_permalink($to_id);
					case false: //Post doesn't exist
					default: //Post exists but isn't published
						return false;
				}				
				break;
			case 'taxonomy':
				$to_id = (int)$to_id;
				$term_link = get_term_link($to_id, $to_type);
				if ($term_link && !is_wp_error($term_link)) return $term_link;
				return false;
				break;
			case 'home':
				return lat_wp::get_blog_home_url(); break;
			case 'author':
				$to_id = (int)$to_id;
				if (is_user_member_of_blog($to_id))
					return get_author_posts_url($to_id);
				return false;
				break;
			case 'internal-link-alias':
				if ($this->plugin->module_exists('internal-link-aliases')) {
					$alias_dir = $this->get_setting('alias_dir', 'go', 'internal-link-aliases');
					$aliases   = $this->get_setting('aliases', array(),'internal-link-aliases');
					
					if (isset($aliases[$to_id]['to'])) {
						$u_alias_to = urlencode($aliases[$to_id]['to']);
						return get_bloginfo('url') . "/$alias_dir/$u_alias_to/";
					}
				}
				return false;
				break;
		}
		
		return false;
	}
	
	
	/**
	 * @since 7.6
	 */
	function should_show_sdf_theme_promo() {
		return $this->is_sdf_theme_promo_applicable() && $this->get_setting('sdf_theme','', 'settings');
	}
	
	/**
	 * @since 7.6
	 */
	function is_sdf_theme_promo_applicable() {
		//If the current user can install themes and if SDF isn't already uploaded...
		if (current_user_can('install_themes') && (wp_get_theme('seodesign')->errors() === false)){
			$theme = wp_get_theme(); // gets the current theme
			return ('SEO Design Framework' == $theme->name || 'seodesign' == $theme->template || 'seodesign' == $theme->parent_theme) ? false : true;
		}
		else{
			return true;
		}
	}	

	
	/**
	* Display the RSS entries in a list.
	*
	* @since 7.6.2
	*
	* @param string|array|object $rss RSS url.
	* @param array $args Widget arguments.
	*/
	function promo_sdf_banners_rss_output( $rss, $args = array() ) {
		if ( is_string( $rss ) ) {
			$rss = fetch_feed($rss);
		} elseif ( is_array($rss) && isset($rss['url']) ) {
			$args = $rss;
			$rss = fetch_feed($rss['url']);
		} elseif ( !is_object($rss) ) {
			return;
		}

		if ( is_wp_error($rss) ) {
			if ( is_admin() || current_user_can('manage_options') )
				echo '<p>' . sprintf( __('<strong>RSS Error</strong>: %s'), $rss->get_error_message() ) . '</p>';
			return;
		}

		$default_args = array( 'show_author' => 0, 'show_date' => 0, 'show_summary' => 0 );
		$args = wp_parse_args( $args, $default_args );
		extract( $args, EXTR_SKIP );

		$items = (int) $items;
		if ( $items < 1 || 20 < $items )
			$items = 10;
		$show_summary  = (int) $show_summary;
		$show_author   = (int) $show_author;
		$show_date     = (int) $show_date;

		if ( !$rss->get_item_quantity() ) {
			echo '<ul><li>' . __( 'An error has occurred, which probably means the feed is down. Try again later.' ) . '</li></ul>';
			$rss->__destruct();
			unset($rss);
			return;
		}

		foreach ( $rss->get_items(0, $items) as $item ) {
			$link = $item->get_link();
			while ( stristr($link, 'http') != $link )
				$link = substr($link, 1);
			$link = esc_url(strip_tags($link));
			$title = esc_attr(strip_tags($item->get_title()));
			if ( empty($title) )
				$title = '';

			$desc = str_replace( array("\n", "\r"), ' ', esc_attr( strip_tags( @html_entity_decode( $item->get_description(), ENT_QUOTES, get_option('blog_charset') ) ) ) );
			$excerpt = wp_html_excerpt( $desc, 120 );

			// Append ellipsis. Change existing [...] to [&hellip;].
			if ( '[...]' == substr( $excerpt, -5 ) )
				$excerpt = substr( $excerpt, 0, -5 ) . '[&hellip;]';
			elseif ( '[&hellip;]' != substr( $excerpt, -10 ) && $desc != $excerpt )
				$excerpt .= ' [&hellip;]';

			$excerpt = esc_html( $excerpt );

			if ( $show_summary ) {
				$summary = "<p>$excerpt</p>";
			} else {
				$summary = '';
			}

			$date = '';
			if ( $show_date ) {
				$date = $item->get_date( 'U' );

				if ( $date ) {
					$date = ' <span class="rss-date">' . date_i18n( get_option( 'date_format' ), $date ) . '</span>';
				}
			}

			$author = '';
			if ( $show_author ) {
				$author = $item->get_author();
				if ( is_object($author) ) {
					$author = $author->get_name();
					$author = ' <cite>' . esc_html( strip_tags( $author ) ) . '</cite>';
				}
			}

			if ( $link == '' ) {
				echo "<h3>$title</h3>{$summary}";
			} else {
				echo "<h3>$title</h3>{$summary}<p><a class='btn btn-large btn-warning' href='$link' target='_blank' rel='nofollow'>Read More</a></p>";
			}
		}
		$rss->__destruct();
		unset($rss);
	}
	
	/**
	 * @since 7.6.2
	 */
	function promo_sdf_banners() {
		
		if ($this->should_show_sdf_theme_promo()) {	
			?>
			<div id="sds_promo_blog_post" class="hide">
			<?php $this->promo_sdf_banners_rss_output( 'https://tranngocthuy.com/noi-bat/feed/', array('show_summary' => 1, 'show_date' => 0, 'items' => 1) ); ?>
			</div>
			<div id="sdf-promo-carousel"></div>
			<?php
		}	
	}
}
// Omit closing PHP tag to avoid "Headers already sent" issues.
