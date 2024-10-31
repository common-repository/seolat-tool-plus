<?php
/**
 * Settings Monitor Module
 * 
 * @since 6.9
 */

if (class_exists('SL_Module')) {

class SL_WpSettings extends SL_Module {
	
	var $results = array();
	
	static function get_module_title() { return __('Settings Monitor', 'seolat-tool-plus'); }
	static function get_menu_title() { return __('Settings Monitor', 'seolat-tool-plus'); }
	
	static function has_menu_count() { return true; }
	function get_menu_count() {
		$count = 0;
		foreach ($this->results as $data) {
			if ($data[0] == SL_RESULT_ERROR) $count++;
		}
		return $count;
	}
	
	function init() {
		
		if (is_admin()) {
			if (get_option('blog_public'))
				$this->results[] = array(SL_RESULT_OK, __('Blog is visible to search engines', 'seolat-tool-plus'),
					__('WordPress will allow search engines to visit your site.', 'seolat-tool-plus'));
			else
				$this->results[] = array(SL_RESULT_ERROR, __('Blog is hidden from search engines', 'seolat-tool-plus'),
					__('WordPress is configured to discourage search engines. This will nullify your site&#8217;s SEO and should be resolved immediately.', 'seolat-tool-plus'), 'options-reading.php');
			
			switch (lat_wp::permalink_mode()) {
				case LATWP_QUERY_PERMALINKS:
					$this->results[] = array(SL_RESULT_ERROR, __('Query-string permalinks enabled', 'seolat-tool-plus'),
						__('It is highly recommended that you use a non-default and non-numeric permalink structure.', 'seolat-tool-plus'), 'options-permalink.php');
					break;
					
				case LATWP_INDEX_PERMALINKS:
					$this->results[] = array(SL_RESULT_WARNING, __('Pathinfo permalinks enabled', 'seolat-tool-plus'), 
						__('Pathinfo permalinks add a keyword-less &#8220;index.php&#8221; prefix. This is not ideal, but it may be beyond your control (since it&#8217;s likely caused by your site&#8217;s web hosting setup).', 'seolat-tool-plus'), 'options-permalink.php');
					
				case LATWP_PRETTY_PERMALINKS:
					
					if (strpos(get_option('permalink_structure'), '%postname%') !== false)
						$this->results[] = array(SL_RESULT_OK, __('Permalinks include the post slug', 'seolat-tool-plus'),
							__('Including a version of the post&#8217;s title helps provide keyword-rich URLs.', 'seolat-tool-plus'));
					else
						$this->results[] = array(SL_RESULT_ERROR, __('Permalinks do not include the post slug', 'seolat-tool-plus'),
							__('It is highly recommended that you include the %postname% variable in the permalink structure.', 'seolat-tool-plus'), 'options-permalink.php');
					
					break;
			}
		}
	}
	
	function admin_page_contents() {
		
		if ($this->should_show_sdf_theme_promo()) {
			echo "\n\n<div class='row'>\n";
			echo "\n\n<div class='col-sm-8 col-md-9'>\n";
		}
		
		echo "\n<p>";
		_e("Settings Monitor analyzes your blog&#8217;s settings and notifies you of any problems. If any issues are found, they will show up in red or yellow below.", 'seolat-tool-plus');
		echo "</p>\n";
		
		echo "<table class='report'>\n";
		
		$first = true;
		foreach ($this->results as $data) {
			
			$result = $data[0];
			$title  = $data[1];
			$desc   = $data[2];
			$url    = isset($data[3]) ? $data[3] : false;
			$action = isset($data[4]) ? $data[4] : __('Go to setting &raquo;', 'seolat-tool-plus');
			
			switch ($result) {
				case SL_RESULT_OK: $class='success'; break;
				case SL_RESULT_ERROR: $class='error'; break;
				default: $class='warning'; break;
			}
			
			if ($result == SL_RESULT_OK || !$url)
				$link='';
			else {
				if (substr($url, 0, 7) == 'http://') $target = " target='_blank'"; else $target='';
				$link = "<a href='$url'$target>$action</a>";
			}
			
			if ($first) { $firstclass = " class='first'"; $first = false; } else $firstclass='';
			echo "\t<tr$firstclass>\n\t\t<td><div class='sl-$class'><strong>$title</strong></div><div>$desc</div><div>$link</div></td>\n\t</tr>\n";
		}
		
		echo "</table>\n\n";
		
		if ($this->should_show_sdf_theme_promo()) {
			echo "\n\n</div>\n";
			echo "\n\n<div class='col-sm-4 col-md-3'>\n";
			$this->promo_sdf_banners();
			echo "\n\n</div>\n";
			echo "\n\n</div>\n";
		}
	}
}

}
?>