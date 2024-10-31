<?php
/**
 * Footer Deeplink Juggernaut Settings Module
 * 
 * @since 6.5
 */

if (class_exists('SL_Module')) {

class SL_FooterAutolinksSettings extends SL_Module {
	
	static function get_parent_module() { return 'autolinks'; }
	static function get_child_order() { return 40; }
	static function is_independent_module() { return false; }
	
	static function get_module_title() { return __('Footer Deeplink Juggernaut Settings', 'seolat-tool-plus'); }
	function get_module_subtitle() { return __('Footer Link Settings', 'seolat-tool-plus'); }
	
	function get_default_settings() {
		return array(
			  'footer_link_section_format' => '<div id="sl-footer-links" style="text-align: center;">{links}</div>'
			, 'footer_link_format' => '{link}'
			, 'footer_link_sep' => ' | '
		);
	}
	
	function admin_page_contents() {
		$this->admin_subheader(__('HTML Formats', 'seolat-tool-plus'));
		$this->admin_form_table_start();
		$this->textareas(array(
			  'footer_link_section_format' => __('Link Section Format', 'seolat-tool-plus')
			, 'footer_link_format' => __('Link Format', 'seolat-tool-plus')
		));
		$this->textbox('footer_link_sep', __('Link Separator', 'seolat-tool-plus'));
		$this->admin_form_table_end();
	}
}

}
?>