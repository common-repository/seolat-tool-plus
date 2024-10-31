<?php
/**
 * Permalink Tweaker Module
 * 
 * @since 5.8
 */

//Permalink base removal code based on code from WP No Category Base plugin
//http://wordpress.org/extend/plugins/wp-no-category-base/

if (class_exists('SL_Module')) {

class SL_Permalinks extends SL_Module {
	
	static function get_module_title() { return __('Permalink Tweaker', 'seolat-tool-plus'); }
	static function get_parent_module() { return 'misc'; }
	function get_settings_key() { return 'permalinks'; }
	
	function get_default_settings() {
		return array(
			  'add_rule_if_conflict' => true
		);
	}
	
	function init() {
		if (lat_wp::permalink_mode()) {
			$nobase_enabled = false;
			$taxonomies = lat_wp::get_taxonomy_names();
			foreach ($taxonomies as $taxonomy) {
				if ($this->get_setting("nobase_$taxonomy", false)) {
					add_action("created_$taxonomy", 'flush_rewrite_rules');
					add_action("edited_$taxonomy", 'flush_rewrite_rules');
					add_action("delete_$taxonomy", 'flush_rewrite_rules');
					add_filter("{$taxonomy}_rewrite_rules", array(&$this, 'nobase_rewrite_rules'));
					$nobase_enabled = true;
				}
			}
			if ($nobase_enabled) {
				add_action('wp_insert_post', 'flush_rewrite_rules');
				add_filter('term_link', array(&$this, 'nobase_term_link'), 1000, 2);
				add_filter('query_vars', array(&$this, 'nobase_query_vars'));
				add_filter('request', array(&$this, 'nobase_old_base_redirect'));
			}
		}
	}
	
	function deactivate() {
		if (lat_wp::permalink_mode()) {
			$nobase_enabled = false;
			$taxonomies = lat_wp::get_taxonomy_names();
			foreach ($taxonomies as $taxonomy) {
				if ($this->get_setting("nobase_$taxonomy", false)) {
					remove_action("created_$taxonomy", 'flush_rewrite_rules');
					remove_action("edited_$taxonomy", 'flush_rewrite_rules');
					remove_action("delete_$taxonomy", 'flush_rewrite_rules');
					remove_filter("{$taxonomy}_rewrite_rules", array(&$this, 'nobase_rewrite_rules'));
					$nobase_enabled = true;
				}
			}
			if ($nobase_enabled) {
				remove_action('wp_insert_post', 'flush_rewrite_rules');
				remove_filter('term_link', array(&$this, 'nobase_term_link'), 1000, 2);
				remove_filter('query_vars', array(&$this, 'nobase_query_vars'));
				remove_filter('request', array(&$this, 'nobase_old_base_redirect'));
			}
		}
		
		flush_rewrite_rules();
	}
	
	function admin_page_contents() {
		
		if (!lat_wp::permalink_mode()) {
			$this->print_message('warning', __('To use the Permalinks Tweaker, you must disable default (query-string) permalinks in your <a href="options-permalink.php">Permalink Settings</a>.', 'seolat-tool-plus'));
			return;
		}
		
		$this->child_admin_form_start();
		
		$nobase_checkboxes = array();
		$taxonomies = lat_wp::get_taxonomies();
		foreach ($taxonomies as $taxonomy) {
			
			global $wp_rewrite;
			$before_url = $wp_rewrite->get_extra_permastruct($taxonomy->name);
			$before_url = str_replace("%{$taxonomy->name}%", 'example', $before_url);
			$before_url = home_url( user_trailingslashit($before_url, 'category') );
			
			$after_url = home_url( user_trailingslashit('/example', 'category') );
			
			$nobase_checkboxes[] = array(
				  'setting_id' => 'nobase_' . $taxonomy->name
				, 'taxonomy_label' => $taxonomy->labels->name
				, 'example_before' => $before_url
				, 'example_after' => $after_url
			);
		}
		
		$this->admin_form_group_start(__('Remove the URL bases of...', 'seolat-tool-plus'));
		
		echo "<tr><td>\n";
		$this->admin_wftable_start(array(
			  'taxonomy' => ' '
			, 'before' => __('Before', 'seolat-tool-plus')
			, 'arrow' => ' '
			, 'after' => __('After', 'seolat-tool-plus')
		));
		
		foreach ($nobase_checkboxes as $nobase_checkbox) {
			echo "<tr>\n";
			echo "<td class='sl-permalinks-taxonomy'>";
			$this->checkbox($nobase_checkbox['setting_id'], $nobase_checkbox['taxonomy_label'], false, array('output_tr' => false));
			echo "</td>\n";
			echo "<td class='sl-permalinks-before'>" . esc_html($nobase_checkbox['example_before']) . "</td>\n";
			echo "<td class='sl-permalinks-arrow'>&rArr;</td>\n";
			echo "<td class='sl-permalinks-after'>" . esc_html($nobase_checkbox['example_after']) . "</td>\n";
			echo "</tr>\n";
		}
		
		$this->admin_wftable_end();
		echo "</td></tr>\n";
		
		$this->admin_form_group_end();
		
		$this->dropdown('add_rule_if_conflict', array(
			  '1' => __('term archive', 'seolat-tool-plus')
			, '0' => __('page', 'seolat-tool-plus')
		), __('URL Conflict Resolution', 'seolat-tool-plus'), __('If a term archive and a Page with the same slug end up having the same URL because of the term&#8217;s base being removed, the URL should be given to the %s.', 'seolat-tool-plus'));
		
		$this->child_admin_form_end();
		
		$this->update_rewrite_filters();
		flush_rewrite_rules();
	}
	
	function update_rewrite_filters() {
		if (lat_wp::permalink_mode()) {
			$taxonomies = lat_wp::get_taxonomy_names();
			foreach ($taxonomies as $taxonomy) {
				if ($this->get_setting("nobase_$taxonomy", false))
					add_filter("{$taxonomy}_rewrite_rules", array(&$this, 'nobase_rewrite_rules'));
				else
					remove_filter("{$taxonomy}_rewrite_rules", array(&$this, 'nobase_rewrite_rules'));
			}
		}
	}
	
	function nobase_term_link($url, $term_obj) {
		if ($this->get_setting('nobase_' . $term_obj->taxonomy, false))
			return home_url( user_trailingslashit('/' . lat_wp::get_term_slug($term_obj), 'category') );
		
		return $url;
	}
	
	function nobase_rewrite_rules($rules) {
		$rules=array();
		
		$tax_name = lat_string::rtrim_str(current_filter(), '_rewrite_rules');
		$tax_obj = get_taxonomy($tax_name);
		
		wp_cache_flush(); //Otherwise get_terms() won't include the term just added
		$terms = get_terms($tax_name);
		if ($terms && !is_wp_error($terms)) {
			foreach ($terms as $term_obj) {
				$term_slug = lat_wp::get_term_slug($term_obj);
				
				if ($tax_obj->query_var && is_string($tax_obj->query_var))
					$url_start = "index.php?{$tax_obj->query_var}=";
				else
					$url_start = "index.php?taxonomy={$tax_name}&term=";
				
				if ($this->get_setting('add_rule_if_conflict', true) || get_page_by_path($term_slug) === null) {
					$rules['('.$term_slug.')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'] = $url_start . '$matches[1]&feed=$matches[2]';
					$rules['('.$term_slug.')/page/?([0-9]{1,})/?$'] = $url_start . '$matches[1]&paged=$matches[2]';
					$rules['('.$term_slug.')/?$'] = $url_start . '$matches[1]';
				}
			}
		}
		
		global $wp_rewrite;
		$old_base = $wp_rewrite->get_extra_permastruct($tax_name);
		$old_base = str_replace( "%{$tax_name}%", '(.+)', $old_base );
		$old_base = trim($old_base, '/');
		$rules[$old_base.'$'] = 'index.php?sl_term_redirect=$matches[1]';
		
		return $rules;
	}
	
	function nobase_query_vars($query_vars) {
		$query_vars[] = 'sl_term_redirect';
		return $query_vars;
	}
	
	function nobase_old_base_redirect($query_vars) {
		if (isset($query_vars['sl_term_redirect'])) {
			wp_redirect(home_url(user_trailingslashit($query_vars['sl_term_redirect'], 'category')));
			exit;
		}
		return $query_vars;
	}
}

}
?>