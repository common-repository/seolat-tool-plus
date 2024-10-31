<?php
/**
 * AISOP Import Module
 * 
 * @since 1.6
 */

if (class_exists('SL_ImportModule')) {

class SL_ImportAIOSP extends SL_ImportModule {
	
	static function get_module_title() { return __('Import from All in One SEO Pack', 'seolat-tool-plus'); }
	static function get_menu_title() { return __('AIOSP Import', 'seolat-tool-plus'); }
	
	function get_op_title() { return __('All in One SEO Pack', 'seolat-tool-plus'); }
	function get_op_abbr()  { return __('AIOSP', 'seolat-tool-plus'); }
	function get_import_desc() { return __('Import post data (custom title tags and meta tags).', 'seolat-tool-plus'); }
	
	function admin_page_contents() {
		echo "<p>";
		_e('Here you can move post fields from the All in One SEO Pack (AIOSP) plugin to SEO LAT+. AIOSP&#8217;s data remains in your WordPress database after AIOSP is deactivated or even uninstalled. This means that as long as AIOSP was active on this blog sometime in the past, AIOSP does <em>not</em> need to be currently installed or activated for the import to take place.', 'seolat-tool-plus');
		echo "</p>\n<p>";
		_e('The import tool can only move over data from AIOSP version 1.6 or above. If you use an older version of AIOSP, you should update to the latest version first and run AIOSP&#8217;s upgrade process.', 'seolat-tool-plus');
		echo "</p>\n";
		
		$this->admin_form_start();
		$this->admin_page_postmeta();
		$this->admin_form_end();
	}
	
	function do_import() {
		$this->do_import_deactivate(SL_AIOSP_PATH);
		
		$this->do_import_postmeta(
			  lat_array::aprintf('_aioseop_%s', '_sl_%s', array('title', 'description', 'keywords'))
			, '_aioseop_disable'
		);
	}
}

}
?>