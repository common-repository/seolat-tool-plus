<?php
/**
 * Class SL_301Rules
 * 
 * @since 2.2
 */

if (class_exists('SL_Module')) {

class SL_301Help extends SL_Module {
	
	static function get_parent_module() { return '301s'; }
	static function get_child_order() { return 10; }
	static function is_independent_module() { return false; }
	
	static function get_module_title() { return __('Documentation', 'seolat-tool-plus'); }
	function get_module_subtitle() { return __('Documentation', 'seolat-tool-plus'); }
	
	function init() {

	}
	function admin_page_contents() {
?>
<div class="documentation" style="display: block;">
    <h3>Redirect 301 Rules</h3>
    <p>Redirect 301 Rules work similar to the format that Apache uses: the request should be relative to your WordPress root. The destination can be either a full URL to any page on the web, or relative to your WordPress root.</p>
    <h4>Example</h4>
    <ul>
        <li><strong>Request:</strong> /old-page/</li>
        <li><strong>Destination:</strong> /new-page/</li>
    </ul>
    
    <h3>Wildcards</h3>
    <p>To use wildcards, put an asterisk (*) after the folder name that you want to redirect.</p>
    <h4>Example</h4>
    <ul>
        <li><strong>Request:</strong> /old-folder/*</li>
        <li><strong>Destination:</strong> /redirect-everything-here/</li>
    </ul>

    <p>You can also use the asterisk in the destination to replace whatever it matched in the request if you like. Something like this:</p>
    <h4>Example</h4>
    <ul>
        <li><strong>Request:</strong> /old-folder/*</li>
        <li><strong>Destination:</strong> /some/other/folder/*</li>
    </ul>
    <p>Or:</p>
    <ul>
        <li><strong>Request:</strong> /old-folder/*/content/</li>
        <li><strong>Destination:</strong> /some/other/folder/*</li>
    </ul>
</div>
<?php
    }
		
}
}
?>
