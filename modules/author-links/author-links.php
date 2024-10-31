<?php
/**
 * Author Highlighter Module
 * 
 * @since 7.4
 */

if (class_exists('SL_Module')) {

define('SL_AUTHORLINKS_MODE_OFF', 0);
define('SL_AUTHORLINKS_MODE_SINGLEAUTHOR', 1);
define('SL_AUTHORLINKS_MODE_MULTIAUTHOR', 2);

class SL_AuthorLinks extends SL_Module {
	
	static function get_module_title() { return __('Author Highlighter', 'seolat-tool-plus'); }
	static function get_menu_title() { return __('Author Highlighter', 'seolat-tool-plus'); }
	static function get_parent_module() { return 'misc'; }
	function get_settings_key() { return 'author-links'; }
	function get_default_status() { return SL_MODULE_DISABLED; }
	
	function init() {
		add_filter('user_contactmethods', array(&$this, 'add_google_profile_field'));
		add_filter('user_contactmethods', array(&$this, 'add_fb_profile_field'));
	//	add_action('sl_head', array(&$this, 'output_author_link_tags'));
	}
	
	function admin_page_contents() {
		
		$mode = $this->get_mode();
		switch ($mode) {
			case SL_AUTHORLINKS_MODE_OFF:
				$this->print_message('warning', __('In order for author highlighting to work, your authors with Google+ accounts need to add their Google+ URLs to their profile page on this site. Why don&#8217;t you start by adding your Google+ URL to <a href="profile.php">your profile</a>?', 'seolat-tool-plus'));
				break;
			
			case SL_AUTHORLINKS_MODE_SINGLEAUTHOR:
				$this->child_admin_form_start();
				$this->textblock(__('Since this site only has one <a href="users.php">registered user</a> who has written posts, Author Highlighter is providing that user&#8217;s Google+ profile image to Google for it to use when any page on this WordPress site appears in Google&#8217;s search result listings. If at some point you were to add an additional user to this site and that user were to write some posts, then additional options would show up in this section.', 'seolat-tool-plus'));
				$this->child_admin_form_end();
				break;
			
			case SL_AUTHORLINKS_MODE_MULTIAUTHOR:
				$users_with_gp = get_users(array(
					  'fields' => array('ID', 'user_login', 'display_name')
					, 'meta_key' => 'googleplus'
					, 'meta_value' => ''
					, 'meta_compare' => '!='
				));
				
				$this->child_admin_form_start();
				
				$user_dropdown_options = array('none' => __('(None)', 'seolat-tool-plus'));
				foreach ($users_with_gp as $user) {
					$user_dropdown_options[$user->ID] = $user->display_name ? $user->display_name : $user->user_login;
				}
				
				$this->dropdown('home_author', $user_dropdown_options, __('Author of Blog Homepage', 'seolat-tool-plus'));
				$this->dropdown('archives_author', $user_dropdown_options, __('Author of Archive Pages', 'seolat-tool-plus'));
				
				$this->child_admin_form_end();
				break;
		}
	}
	
	function get_users_with_gp() {
		static $users_with_gp = null;
		if ($users_with_gp === null) {
			$users_with_gp = get_users(array(
				  'fields' => array('ID', 'user_login', 'display_name')
				, 'meta_key' => 'googleplus'
				, 'meta_value' => ''
				, 'meta_compare' => '!='
			));
		}
		return $users_with_gp;
	}
	
	function get_mode() {
		
		if (count($this->get_users_with_gp()) > 0) {
			//We have at least one user who provided a Google+ profile
			
			if (is_multi_author())
				return SL_AUTHORLINKS_MODE_MULTIAUTHOR;
			else
				return SL_AUTHORLINKS_MODE_SINGLEAUTHOR;
			
		} else {
			return SL_AUTHORLINKS_MODE_OFF;
		}
		
	}
	
	function add_google_profile_field( $contactmethods ) {
		$contactmethods['googleplus'] = __('Google+ Profile URL', 'seolat-tool-plus');
		return $contactmethods;
	}
	
	function add_fb_profile_field( $contactmethods ) {
		$contactmethods['facebook'] = __('Facebook Profile URL', 'seolat-tool-plus');
		return $contactmethods;
	}
	
	/*
	function output_author_link_tags() {
		
		if (is_404())
			return;
		
		$user_id = false;
		
		$mode = $this->get_mode();
		switch ($mode) {
			case SL_AUTHORLINKS_MODE_OFF:
				return;
				break;
			case SL_AUTHORLINKS_MODE_SINGLEAUTHOR:
				$users_with_gp = (array)$this->get_users_with_gp();
				$user = reset($users_with_gp);
				$user_id = $user->ID;
				break;
			case SL_AUTHORLINKS_MODE_MULTIAUTHOR:
				if (is_home()) {
					$home_author = $this->get_setting('home_author', 'none');
					if (is_numeric($home_author)) $user_id = $home_author;
				} elseif (is_singular()) {
					global $post;
					if (is_object($post)) $user_id = $post->post_author;
				} elseif (is_author()) {
					global $wp_query;
					$user_id = $wp_query->get_queried_object_id();
				} elseif (is_archive()) {
					$archives_author = $this->get_setting('archives_author', 'none');
					if (is_numeric($archives_author)) $user_id = $archives_author;
				}
				break;
		}
		
		if ($user_id !== false) {
			$url = get_user_meta($user_id, 'googleplus', true);
			$url = sl_esc_attr($url);
			if ($url)
				echo "\t<link rel=\"author\" href=\"$url\" />\n";
		}
		
		if ($user_id !== false) {
			$url = get_user_meta($user_id, 'facebook', true);
			$url = sl_esc_attr($url);
			if ($url)
				echo "\t<link rel=\"author\" href=\"$url\" />\n";
		}	
	}
	*/
}
}
?>