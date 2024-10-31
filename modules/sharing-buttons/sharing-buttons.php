<?php
/**
 * Sharing Facilitator Module
 * 
 * @since 3.5
 */

if (class_exists('SL_Module')) {

class SL_SharingButtons extends SL_Module {
	
	static function get_module_title() { return __('Sharing Facilitator', 'seolat-tool-plus'); }
	
	static function get_parent_module() { return 'misc'; }
	function get_settings_key() { return 'sharing-buttons'; }
	
	function init() {
		add_filter('the_content', array(&$this, 'add_sharing_buttons'));
	}
	
	function get_default_settings() {
		return array(
			  'provider' => 'none'
			, 'sharethis_code' => '<script type="text/javascript" charset="utf-8" src="http://w.sharethis.com/widget/?wp={wpver}"></script>'
			, 'addthis_code' => '<a class="addthis_button" href="http://addthis.com/bookmark.php?v=250"><img src="https://s7.addthis.com/static/btn/v2/lg-share-en.gif" width="125" height="16" alt="' . __('Bookmark and Share', 'seolat-tool-plus') . '" style="border:0"/></a><script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js"></script>'
		);
	}
	
	/*
	function get_admin_page_tabs() {
		return array(
			  __('Providers', 'seolat-tool-plus') => 'providers_tab'
		);
	}
	*/
	
	function admin_page_contents() {
		$this->child_admin_form_start();
		$this->radiobuttons('provider', array(
			  'none' => __('None; disable sharing buttons', 'seolat-tool-plus')
			, 'sharethis' => __('Use the ShareThis button', 'seolat-tool-plus') //: %s{sharethis_code}
			, 'addthis' => __('Use the AddThis button', 'seolat-tool-plus') //: %s{addthis_code}
		), __('Which provider would you like to use for your sharing buttons?', 'seolat-tool-plus'));
		$this->child_admin_form_end();
	}
	
	function add_sharing_buttons($content) {
		if (!is_feed()) {
			switch ($this->get_setting('provider', 'none')) {
				case 'sharethis': $code = $this->get_setting('sharethis_code', ''); break;
				case 'addthis': $code = $this->get_setting('addthis_code', ''); break;
				default: return $content; break;
			}
			
			if ($code) {
				$code = str_replace(array(
						  '{wpver}'
					), array (
						  get_bloginfo('version')
					), $code);
				return $content . $code;
			}
		}
		return $content;
	}
	
	function add_help_tabs($screen) {
		
		$overview = __("
<ul>
	<li><strong>What it does:</strong> Sharing Facilitator adds buttons to your posts/pages that make it easy for visitors to share your content.</li>
	<li><strong>Why it helps:</strong> When visitors share your content on social networking sites, this can build links to your site. Sharing Facilitator makes it easy for visitors to do this.</li>
	<li><strong>How to use it:</strong> Pick which button type you&#8217;d like to use (ShareThis or AddThis) and click Save Changes. Try enabling each button on your site and see which one you like better.</li>
</ul>
", 'seolat-tool-plus');
		
		if ($this->has_enabled_parent()) {
			$screen->add_help_tab(array(
			  'id' => 'sl-sharing-buttons-help'
			, 'title' => __('Sharing Facilitator', 'seolat-tool-plus')
			, 'content' => 
				'<h3>' . __('Overview', 'seolat-tool-plus') . '</h3>' . $overview
			));
		} else {
			
			$screen->add_help_tab(array(
				  'id' => 'sl-sharing-buttons-overview'
				, 'title' => __('Overview', 'seolat-tool-plus')
				, 'content' => $overview));
			
		}
	}

}

}
?>