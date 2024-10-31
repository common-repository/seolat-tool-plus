<?php
/**
 * Canonical URL
 * 
 * @since 2.0.1
 */

if (class_exists('SL_Module')) {

class SL_CanonicalURL extends SL_Module {

	static function get_module_title() { return __('Global Canonical Manager', 'seolat-tool-plus'); }
	static function get_menu_title()   { return __('Global Canonical Manager', 'seolat-tool-plus'); }

	function get_settings_key() { return 'canonical_url_tag'; }
	
	function init() {        
		
		add_filter('sl_get_setting-canonical-canonical_url_scheme', array(&$this, 'filter_canonical_url_scheme'));
		
		$this->sl_canonical = new SL_Canonical();
		//If the canonical tags are enabled, then...
		if ($this->sl_canonical->get_setting('link_rel_canonical')) {
			
			//...remove WordPress's default canonical tags (since they only handle posts/pages/attachments)
			remove_action('wp_head', 'rel_canonical');
			
			//...and add our custom canonical tags.
			add_action('sl_head', array(&$this, 'link_rel_canonical_tag'));
		}
		
		if ($this->sl_canonical->get_setting('http_link_rel_canonical'))
			add_action('template_redirect', array(&$this, 'http_link_rel_canonical'), 11, 0);
		
		//Should we remove nonexistent pagination?
		if ($this->get_setting('remove_nonexistent_pagination'))
			add_action('template_redirect', array(&$this, 'remove_nonexistent_pagination'), 11);
	}
	
	function get_admin_page_tabs() {
		return array_merge(
			  $this->get_meta_edit_tabs(array(
				  'type' => 'textbox',
					'name' => 'canonical_url_tag',
					'term_settings_key' => 'taxonomy_canonical_url_tag',
					'label' => __('Canonical URL', 'seolat-tool-plus'),
			))
		);
	}
	

	function link_rel_canonical_tag() {
		
		//CANONICAL URL TAG
		if (!($canonical_url = $this->get_postmeta('canonical_url_tag'))) {
			$canonical_url = $this->get_setting('canonical_url_tag');
		}
		
		$url = ($canonical_url) ? $canonical_url : $this->get_canonical_url();
		
		//Handle protocol change
		if ($scheme = $this->sl_canonical->get_setting('canonical_url_scheme', 'http'))
			$url = preg_replace('@^https?://@', "$scheme://", $url);
		
		//Display the canonical tag if a canonical URL is available
		if ($url) {
			$url = sl_esc_attr($url);
			echo "\t<link rel=\"canonical\" href=\"$url\" />\n";
		}
	}
	
	function http_link_rel_canonical() {
		if (headers_sent())
			return;
		
		//CANONICAL URL TAG
		if (!($canonical_url = $this->get_postmeta('canonical_url_tag'))) {
			$canonical_url = $this->get_setting('canonical_url_tag');
		}
		
		$url = ($canonical_url) ? $canonical_url : $this->get_canonical_url();
		
		//Handle protocol change
		if ($scheme = $this->sl_canonical->get_setting('canonical_url_scheme', 'http'))
			$url = preg_replace('@^https?://@', "$scheme://", $url);
		
		if ($url) {
			$url = sl_esc_attr($url);
			header("Link: <$url>; rel=\"canonical\"", false);
		}
	}
	
	/**
	 * Returns the canonical URL to put in the link-rel-canonical tag.
	 * 
	 * This function is modified from the GPL-licensed {@link http://wordpress.org/extend/plugins/canonical/ Canonical URLs} plugin,
	 * which in turn was heavily based on the {@link http://svn.fucoder.com/fucoder/permalink-redirect/ Permalink Redirect} plugin.
	 */
	function get_canonical_url() {
		global $wp_query, $wp_rewrite;
		
		//404s and search results don't have canonical URLs
		if ($wp_query->is_404 || $wp_query->is_search) return false;
		
		//Are there posts in the current Loop?
		$haspost = count($wp_query->posts) > 0;
		
		//Handling special case with '?m=yyyymmddHHMMSS'.
		if (get_query_var('m')) {
			$m = preg_replace('/[^0-9]/', '', get_query_var('m'));
			switch (strlen($m)) {
				case 4: // Yearly
					$link = get_year_link($m);
					break;
				case 6: // Monthly
					$link = get_month_link(substr($m, 0, 4), substr($m, 4, 2));
					break;
				case 8: // Daily
					$link = get_day_link(substr($m, 0, 4), substr($m, 4, 2),
										 substr($m, 6, 2));
					break;
				default:
					//Since there is no code for producing canonical archive links for is_time, we will give up and not try to produce a link.
					return false;
			}
		
		//Posts and pages
		} elseif (($wp_query->is_single || $wp_query->is_page) && $haspost) {
			$post = $wp_query->posts[0];
			$link = get_permalink($post->ID);
			if (is_front_page()) $link = trailingslashit($link);
			
		//Author archives
		} elseif ($wp_query->is_author && $haspost) {
			$author = get_userdata(get_query_var('author'));
			if ($author === false) return false;
			$link = get_author_posts_url($author->ID, $author->user_nicename);
			
		//Category archives
		} elseif ($wp_query->is_category && $haspost) {
			$link = get_category_link(get_query_var('cat'));
			
		//Tag archives
		} else if ($wp_query->is_tag  && $haspost) {
			$tag = get_term_by('slug',get_query_var('tag'),'post_tag');
			if (!empty($tag->term_id)) $link = get_tag_link($tag->term_id);
		
		//Day archives
		} elseif ($wp_query->is_day && $haspost) {
			$link = get_day_link(get_query_var('year'),
								 get_query_var('monthnum'),
								 get_query_var('day'));
		
		//Month archives
		} elseif ($wp_query->is_month && $haspost) {
			$link = get_month_link(get_query_var('year'),
								   get_query_var('monthnum'));
		
		//Year archives
		} elseif ($wp_query->is_year && $haspost) {
			$link = get_year_link(get_query_var('year'));
		
		//Homepage
		} elseif ($wp_query->is_home) {
			if ((get_option('show_on_front') == 'page') && ($pageid = get_option('page_for_posts')))
				$link = trailingslashit(get_permalink($pageid));
			else
				$link = trailingslashit(get_option('home'));
			
		//Other
		} else
			return false;
		
		//Handle pagination
		$page = get_query_var('paged');
		if ($page && $page > 1) {
			if ($wp_rewrite->using_permalinks()) {
				$link = trailingslashit($link) ."page/$page";
				$link = user_trailingslashit($link, 'paged');
			} else {
				$link = esc_url(add_query_arg( 'paged', $page, $link ));
			}
		}
		
		//Handle protocol change
		if ($scheme = $this->get_setting('canonical_url_scheme', 'http'))
			$link = preg_replace('@^https?://@', "$scheme://", $link);
		
		//Return the canonical URL
		return $link;
	}
	
	function remove_nonexistent_pagination() {
		
		if (!is_admin()) {
			
			global $wp_rewrite, $wp_query;
			
			$url = lat_url::current();
			
			if (is_singular()) {
				$num = absint(get_query_var('page'));
				$post = $wp_query->get_queried_object();
				$max = count(explode('<!--nextpage-->', $post->post_content));
				
				if ($max > 0 && ($num == 1 || ($num > 1 && $num > $max))) {
					
					if ($wp_rewrite->using_permalinks())
						wp_redirect(preg_replace('|/[0-9]{1,9}/?$|', '/', $url), 301);
					else
						wp_redirect(esc_url(remove_query_arg('page', $url)), 301);
				}
				
			} elseif (is_404() && $num = absint(get_query_var('paged'))) {
				
				if ($wp_rewrite->using_permalinks())
					wp_redirect(preg_replace('|/page/[0-9]{1,9}/?$|', '/', $url), 301);
				else
					wp_redirect(esc_url(remove_query_arg('paged', $url)), 301);
			}
		}
	}
	
	function filter_canonical_url_scheme($scheme) {
		return lat_string::preg_filter('a-z', $scheme);
	}
	
	function postmeta_fields($fields, $screen) {									
		$id = "_sl_canonical_url_tag";
		$value = sl_esc_attr($this->get_postmeta('canonical_url_tag'));
		$fields['advanced'][30]['canonical_url_tag'] =
			"<div class='form-group sl textbox'>\n<label class='col-sm-4 col-md-4 control-label' for='$id'>".__('Canonical URL:', 'seolat-tool-plus')."</label>\n<div class='col-sm-4 col-md-4'><input name='$id' id='$id' type='text' value='$value' class='form-control input-sm regular-text' tabindex='2'"
			. " />"
			. "</div>\n<div class='col-sm-4 col-md-4 help-text'>".sprintf(__('The Canonical URL that this page should point to. Leave field empty to default to existing permalink.', 'seolat-tool-plus'))
			. "</div>\n</div>\n";
		
		return $fields;
	}
	
	function add_help_tabs($screen) {
		
		$overview = __("
<ul>
	<li><strong>What it does:</strong> Canonicalizer will point Google to the correct URL for your homepage and each of your posts, Pages, categories, tags, date archives, and author archives.</li>
	<li><strong>Why it helps:</strong> If Google comes across an alternate URL by which one of those items can be accessed, it will be able to find the correct URL and won&#8217;t penalize you for having two identical pages on your site.</li>
	<li><strong>How to use it:</strong> Just insert your canonical url. If your site is accessible using both <code>http://</code> and <code>https://</code>, be sure to use it.</li>
</ul>
", 'seolat-tool-plus');
		
		if ($this->has_enabled_parent()) {
			$screen->add_help_tab(array(
			  'id' => 'sl-canonical-help'
			, 'title' => __('Canonicalizer', 'seolat-tool-plus')
			, 'content' => 
				'<h3>' . __('Overview', 'seolat-tool-plus') . '</h3>' . $overview
			));
		} else {
			
			$screen->add_help_tab(array(
				  'id' => 'sl-canonical-overview'
				, 'title' => __('Overview', 'seolat-tool-plus')
				, 'content' => $overview));
			
		}
	}
}

}
?>