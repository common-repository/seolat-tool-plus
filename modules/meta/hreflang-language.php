<?php
/**
 * Hreflang Global Setting Module
 * 
 * @since 4.0
 */

if (class_exists('SL_Module')) {

class SL_HreflangLanguage extends SL_Module {
	
	static function get_module_title() { return __('Hreflang Global Setting', 'seolat-tool-plus'); }
	static function get_menu_title() { return __('Hreflang Global Setting', 'seolat-tool-plus'); }
	
	static function get_parent_module() { return 'misc'; }
	function get_settings_key() { return 'meta'; }
	
	function init() {
		add_action('sl_head', array(&$this, 'head_tag_output'));
	}
	
	function get_supported_language() {
		return array(
			  'language' => array(
				  'title' => __('Use hreflang for language and regional URLs', 'seolat-tool-plus')
				, 'meta_name' => 'alternate'
			)
		);
	}
	
	function head_tag_output() {	
		$verify = $this->get_supported_language();
		foreach ($verify as $site => $site_data) {
			$name = $site_data['meta_name'];
			$url = get_option( 'siteurl' );
			//Do we have verification tags? If so, output them.
			if ($value = $this->get_setting($site.'_verify')) {
				if (current_user_can('unfiltered_html') && lat_string::startswith(trim($value), '<meta ') && lat_string::endswith(trim($value), '/>'))
					echo "\t".trim($value)."\n";
				else {
					$value = sl_esc_attr($value);
					echo "\t<link rel=\"$name\" href=\"$url/$value\" hreflang=\"$value\" />\n";
				}
			}
		}
	}
	
	function admin_page_contents() {
		
		$this->child_admin_form_start(false);
		
		$this->admin_wftable_start(array(
			  'portal' => __('Hreflang Language', 'seolat-tool-plus')
			, 'meta_tag_before' => __('Meta Tag', 'seolat-tool-plus')
			, 'meta_tag' => ' '
			, 'meta_tag_after' => ' '
		));
		
		$sites = $this->get_supported_language();
		
		foreach ($sites as $site => $site_data) {
			echo "<tr>\n";
			echo "<td class='sl-hreflang-language-portal'>" . esc_html($site_data['title']) . "</td>\n";
			echo "<td class='sl-hreflang-language-meta_tag_before'>&lt;link rel=&quot;"
				. esc_html($site_data['meta_name']) . "&quot; hreflang=&quot;</td>\n";
			echo "<td class='sl-hreflang-language-meta_tag'>";
			$this->textbox("{$site}_verify", '', false, false, array('in_table' => false));
			echo "</td>\n";
			echo "<td class='sl-hreflang-language-meta_tag_after'>&quot; /&gt;</td>\n";
			echo "</tr>\n";
		}
		
		$this->admin_wftable_end();
		
		$this->child_admin_form_end(false);
		
		echo '<p class="hreflang-language">Click here to see <a href="http://www.w3schools.com/tags/ref_language_codes.asp" target="_blank">Hreflang country codes</a></p>';
	}

	function add_help_tabs($screen) {
		
		$overview = __("
<ul>
	<li><strong>Click to<a href='http://www.w3schools.com/tags/ref_language_codes.asp'> learn more</a> about hreflang.</strong></li>
</ul>
", 'seolat-tool-plus');
		
		if ($this->has_enabled_parent()) {
			$screen->add_help_tab(array(
			  'id' => 'sl-hreflang-language-help'
			, 'title' => __('Hreflang Language', 'seolat-tool-plus')
			, 'content' => 
				'<h3>' . __('Overview', 'seolat-tool-plus') . '</h3>' . $overview
			));
		} else {
			
			$screen->add_help_tab(array(
				  'id' => 'sl-hreflang-language-overview'
				, 'title' => __('Overview', 'seolat-tool-plus')
				, 'content' => $overview));
			
		}
	}

}

}
?>