<?php
/**
 * Noindex Manager Module
 * 
 * @since 0.1
 */

if (class_exists('SL_Module')) {

function sl_noindex_export_filter($all_settings) {
	unset($all_settings['meta']['taxonomy_meta_robots_noindex']);
	unset($all_settings['meta']['taxonomy_meta_robots_nofollow']);
	unset($all_settings['meta']['taxonomy_meta_robots_noarchive']);
	unset($all_settings['meta']['taxonomy_meta_robots_nosnippet']);
	return $all_settings;
}
add_filter('sl_settings_export_array', 'sl_noindex_export_filter');

class SL_Noindex extends SL_Module {
	
	static function get_module_title() { return __('Noindex Manager', 'seolat-tool-plus'); }
	function get_module_subtitle() { return __('Noindex', 'seolat-tool-plus'); }
	
	static function get_parent_module() { return 'meta-robots'; }
	function get_settings_key() { return 'noindex'; }
	static function is_independent_module() { return false; }
	
	function init() {
		
		//Hook into our wp_head() action
		add_action('sl_meta_robots', array(&$this, 'wphead_meta_robots'), 1);
		
		//Now we'll hook into places where wp_head() is not called
		
		//Hook into comment feed headers
		if ($this->get_setting('noindex_comments_feed'))
			add_action('commentsrss2_head', array(&$this, 'rss2_noindex_tag'));
		
		//Hook into the admin header
		if ($this->get_setting('noindex_admin'))
			add_action('admin_head', array(&$this, 'xhtml_noindex_tag'));
		
		//Hook into the login header
		if ($this->get_setting('noindex_login'))
			add_action('login_head', array(&$this, 'xhtml_noindex_tag'));
	}
	
	function get_admin_page_tabs() {
		
		return array_merge(
			  array(
				  array('title' => __('Default Values', 'seolat-tool-plus'), 'id' => 'sl-default-values', 'callback' => 'defaults_tab')
				)
			, $this->get_postmeta_edit_tabs(array(
				  array(
					  'type' => 'checkbox'
					, 'name' => 'meta_robots_noindex'
					, 'label' => __('<input type="checkbox" class="check noindex-check" /> Noindex', 'seolat-tool-plus')
					)
				, array(
					  'type' => 'checkbox'
					, 'name' => 'meta_robots_nofollow'
					, 'label' => __('<input type="checkbox" class="check nofollow-check" /> Nofollow', 'seolat-tool-plus')
					)
				, array(
					  'type' => 'checkbox'
					, 'name' => 'meta_robots_noarchive'
					, 'label' => __('<input type="checkbox" class="check noarchive-check" /> Noarchive', 'seolat-tool-plus')
					)
				, array(
					  'type' => 'checkbox'
					, 'name' => 'meta_robots_nosnippet'
					, 'label' => __('<input type="checkbox" class="check nosnippet-check" /> Nosnippet', 'seolat-tool-plus')
					)
			))
			, $this->get_taxmeta_edit_tabs(array(
				  array(
					  'type' => 'dropdown'
					, 'name' => 'meta_robots_noindex'
					, 'options' => array(
						  0 => __('Use default', 'seolat-tool-plus')
						, 1 => __('noindex', 'seolat-tool-plus')
						, -1 => __('index', 'seolat-tool-plus')
					)
					, 'term_settings_key' => 'taxonomy_meta_robots_noindex'
					, 'label' => __('Noindex', 'seolat-tool-plus')
					)
				, array(
					  'type' => 'dropdown'
					, 'name' => 'meta_robots_nofollow'
					, 'options' => array(
						  0 => __('Use default', 'seolat-tool-plus')
						, 1 => __('nofollow', 'seolat-tool-plus')
						, -1 => __('follow', 'seolat-tool-plus')
					)
					, 'term_settings_key' => 'taxonomy_meta_robots_nofollow'
					, 'label' => __('Nofollow', 'seolat-tool-plus')
					)
				, array(
					  'type' => 'dropdown'
					, 'name' => 'meta_robots_noarchive'
					, 'options' => array(
						  0 => __('Use default', 'seolat-tool-plus')
						, 1 => __('noarchive', 'seolat-tool-plus')
						, -1 => __('archive', 'seolat-tool-plus')
					)
					, 'term_settings_key' => 'taxonomy_meta_robots_noarchive'
					, 'label' => __('Noarchive', 'seolat-tool-plus')
					)
				, array(
					  'type' => 'dropdown'
					, 'name' => 'meta_robots_nosnippet'
					, 'options' => array(
						  0 => __('Use default', 'seolat-tool-plus')
						, 1 => __('nosnippet', 'seolat-tool-plus')
						, -1 => __('snippet', 'seolat-tool-plus')
					)
					, 'term_settings_key' => 'taxonomy_meta_robots_nosnippet'
					, 'label' => __('Nosnippet', 'seolat-tool-plus')
					)
			))
		);
	}
	
	function defaults_tab() {
		
		//If global noindex tags are enabled, these settings will be moot, so notify the user.
		if (!get_option('blog_public'))
			$this->queue_message('error',
				__('Note: The <a href="options-reading.php">&#8220;discourage search engines&#8221; checkbox</a> will block indexing of the entire site, regardless of which options are set below.', 'seolat-tool-plus') );
		
		$this->admin_form_table_start();
		$this->admin_form_subheader(__('Prevent indexing of...', 'seolat-tool-plus'));
		$this->checkboxes(array('noindex_admin' => __('Administration back-end pages', 'seolat-tool-plus')
							,	'noindex_author' => __('Author archives', 'seolat-tool-plus')
							,	'noindex_search' => __('Blog search pages', 'seolat-tool-plus')
							,	'noindex_category' => __('Category archives', 'seolat-tool-plus')
							,	'noindex_comments_feed' => __('Comment feeds', 'seolat-tool-plus')
							,	'noindex_cpage' => __('Comment subpages', 'seolat-tool-plus')
							,	'noindex_date' => __('Date-based archives', 'seolat-tool-plus')
							,	'noindex_home_paged' => __('Subpages of the homepage', 'seolat-tool-plus')
							,	'noindex_tag' => __('Tag archives', 'seolat-tool-plus')
							,	'noindex_login' => __('User login/registration pages', 'seolat-tool-plus')
							,	'noindex_attachment' => __('Noindex media attachment', 'seolat-tool-plus')
		));
		$this->admin_form_table_end();
	}
	
	function wphead_meta_robots($commands) {
        global $seo_lat_tool_plus;
		// $new = array(
		// 	  $this->should_noindex()  ? 'noindex'  : 'index'
		// 	, $this->should_nofollow() ? 'nofollow' : 'follow'
		// 	, $this->should_noarchive() ? 'noarchive' : 'archive'
		// 	, $this->should_nosnippet() ? 'nosnippet' : 'snippet'
		// );
		$new = array();
		if ( $this->should_noindex() ) $new[] = 'noindex';
		if ( $this->should_nofollow() ) $new[] = 'nofollow';
		if ( $this->should_noarchive() ) $new[] = 'noarchive';
		if ( $this->should_nosnippet() ) $new[] = 'nosnippet';
		
		// if ($new != array('index', 'follow', 'archive', 'snippet'))
		if ( count( $new ) > 0 )
			$commands = array_merge($commands, $new);
		
		return $commands;
	}
	
	function should_noindex() {
		if ($this->get_postmeta('meta_robots_noindex')) return true;
		
		switch ($this->get_termmeta('meta_robots_noindex', false, 'meta')) {
			case 1: return true; break;
			case -1: return false; break;
		}
		
		$checks = array('author', 'search', 'category', 'date', 'tag', 'attachment');
		
		foreach ($checks as $setting) {
			if (call_user_func("is_$setting")) return $this->get_setting("noindex_$setting");
		}
		
		//Homepage subpages
		if ($this->get_setting('noindex_home_paged') && is_home() && is_paged()) return true;
		
		//Comment subpages
		global $wp_query;
		if ($this->get_setting('noindex_cpage') && isset($wp_query->query_vars['cpage'])) return true;
		
		return false;
	}
	
	function should_nofollow() {
		if ($this->get_postmeta('meta_robots_nofollow')) return true;
		
		switch ($this->get_termmeta('meta_robots_nofollow', false, 'meta')) {
			case 1: return true; break;
			case 0: case -1: return false; break;
		}
		
		return false;
	}

	function should_noarchive() {
		if ($this->get_postmeta('meta_robots_noarchive')) return true;
		
		switch ($this->get_termmeta('meta_robots_noarchive', false, 'meta')) {
			case 1: return true; break;
			case 0: case -1: return false; break;
		}
		
		return false;
	}
	
	function should_nosnippet() {
		if ($this->get_postmeta('meta_robots_nosnippet')) return true;
		
		switch ($this->get_termmeta('meta_robots_nosnippet', false, 'meta')) {
			case 1: return true; break;
			case 0: case -1: return false; break;
		}
		
		return false;
	}
	
	function rss2_noindex_tag() {
		echo "<xhtml:meta xmlns:xhtml=\"http://www.w3.org/1999/xhtml\" name=\"robots\" content=\"noindex\" />\n";
	}
	
	function xhtml_noindex_tag() {
		echo "\t<meta name=\"robots\" content=\"noindex\" />\n";
	}
	
	function postmeta_fields($fields, $screen) {
		$fields['advanced'][25]['meta_robots_noindex|meta_robots_nofollow|meta_robots_noarchive|meta_robots_nosnippet'] = $this->get_postmeta_checkboxes(array(
			  'meta_robots_noindex' => __('Noindex: Tell search engines not to index this webpage.', 'seolat-tool-plus')
			, 'meta_robots_nofollow' => __('Nofollow: Tell search engines not to spider links on this webpage.', 'seolat-tool-plus')
			, 'meta_robots_noarchive' => __('Noarchive: Tell search engines not to archive this page.', 'seolat-tool-plus')
			, 'meta_robots_nosnippet' => __('Nosnippet: Tell search engines not to display a snippet or cache this page.', 'seolat-tool-plus')
		), __('Meta Robots Tag:', 'seolat-tool-plus'));
		
		return $fields;
	}
}
}
?>