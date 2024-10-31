<?php
/**
 * 404 Monitor Log Module
 * 
 * @since 2.1
 */

if (class_exists('SL_Module')) {

function sl_fofs_log_export_filter($all_settings) {
	unset($all_settings['404s']['log']);
	return $all_settings;
}
add_filter('sl_settings_export_array', 'sl_fofs_log_export_filter');

class SL_FofsLog extends SL_Module {
	
	static function get_parent_module() { return 'fofs'; }
	static function get_child_order() { return 10; }
	static function is_independent_module() { return false; }
	
	static function get_module_title() { return __('404 Monitor Log', 'seolat-tool-plus'); }
	function get_module_subtitle() { return __('Log', 'seolat-tool-plus'); }
	function get_default_status() { return SL_MODULE_DISABLED; }
	static function has_menu_count() { return true; }
	function get_settings_key() { return '404s'; }
	
	function get_menu_count() {
		$new = 0;
		$the404s = $this->get_setting('log');
		if (is_array($the404s) && count($the404s)) {
			foreach ($the404s as $a404) {
				if ($a404['is_new']) $new++;
			}
		}
		return $new;
	}
	
	function init() {
		add_action('admin_enqueue_scripts', array(&$this, 'queue_admin_scripts'));
		add_action('sl_save_hit', array(&$this, 'log_hit'));
	}
	
	//Upgrade to new wp_options-only system if needed
	function upgrade() {
		global $wpdb;
		
		$suppress = $wpdb->suppress_errors(true);
		
		//Get old storage system if it exists
		if ($result = @$wpdb->get_results("SELECT * FROM {$wpdb->prefix}sds_hits WHERE status_code=404 AND redirect_url='' AND url NOT LIKE '%/favicon.ico' ORDER BY id DESC", ARRAY_A)) {
			
			//Get new storage system
			$l = $this->get_setting('log', array());
			
			//Move old to new
			foreach ($result as $row) $this->log_hit($row);
			
			//Out with the old
			mysqli_query("DROP TABLE IF EXISTS {$wpdb->prefix}sds_hits");
		}
		
		$wpdb->suppress_errors($suppress);
	}
	
	function queue_admin_scripts() {
		//if ($this->is_module_admin_page()) wp_enqueue_script('scriptaculous-effects');
	}
	
	function log_hit($hit) {
		
		if ($hit['status_code'] == 404) {
			
			if ($this->get_setting('restrict_logging', true)) {
				if (!($this->get_setting('log_spiders', true) && lat_web::is_search_engine_ua($hit['user_agent'])) &&
					!($this->get_setting('log_errors_with_referers', true) && strlen($hit['referer'])))
						return $hit;
			}
			
			$exceptions = lat_array::explode_lines($this->get_setting('exceptions', ''));
			foreach ($exceptions as $exception) {
				if (preg_match(lat_string::wildcards_to_regex($exception), $hit['url']))
					return $hit;
			}
			
			$l = $this->get_setting('log', array());
			$max_log_size = absint(lat_string::preg_filter('0-9', strval($this->get_setting('max_log_size', 100))));
			while (count($l) > $max_log_size) array_pop($l);
			
			$u = $hit['url'];
			if (!isset($l[$u])) {
				$l[$u] = array();
				$l[$u]['hit_count'] = 0;
				$l[$u]['is_new'] = isset($hit['is_new']) ? $hit['is_new'] : true;
				$l[$u]['referers'] = array();
				$l[$u]['user_agents'] = array();
				$l[$u]['last_hit_time'] = 0;
			}
			
			$l[$u]['hit_count']++;
			if (!$l[$u]['is_new'] && $hit['is_new'])
				$l[$u]['is_new'] = true;
			if ($hit['time'] > $l[$u]['last_hit_time'])
				$l[$u]['last_hit_time'] = $hit['time'];
			if (strlen($hit['referer']) && !in_array($hit['referer'], $l[$u]['referers']))
				$l[$u]['referers'][] = $hit['referer'];
			if (strlen($hit['user_agent']) && !in_array($hit['user_agent'], $l[$u]['user_agents']))
				$l[$u]['user_agents'][] = $hit['user_agent'];
			
			$this->update_setting('log', $l);
		}
		
		return $hit;
	}
	
	function get_admin_table_columns() {
		return array(
			  'actions' => __('Actions', 'seolat-tool-plus')
			, 'hit-count' => __('Hits', 'seolat-tool-plus')
			, 'url' => __('URL with 404 Error', 'seolat-tool-plus')
			, 'last-hit-time' => __('Date of Most Recent Hit', 'seolat-tool-plus')
			, 'referers' => __('Referers', 'seolat-tool-plus')
			, 'user-agents' => __('User Agents', 'seolat-tool-plus')
		);
	}
	
	function sort_log_callback($a, $b) {
		if ($a['is_new'] == $b['is_new'])
			return $b['last_hit_time'] - $a['last_hit_time'];
		
		return $a['is_new'] ? -1 : 1;
	}
	
	function admin_page_contents() {
		
		$the404s = $this->get_setting('log');
		
		if (!$this->get_setting('log_enabled', true))
			$this->queue_message('warning', __('New 404 errors will not be recorded because 404 logging is disabled on the Settings tab.', 'seolat-tool-plus'));
		
		//Are we deleting a 404 entry?
		if ($this->is_action('delete')) {
		
			if (isset($the404s[$_GET['object']])) {
				unset($the404s[$_GET['object']]);
				$this->queue_message('success', __('The log entry was successfully deleted.', 'seolat-tool-plus'));
			} else
				$this->queue_message('error', __('This log entry has already been deleted.', 'seolat-tool-plus'));
			
			$this->update_setting('log', $the404s);
			
		//Are we clearing the whole 404 log?
		} elseif ($this->is_action('clear')) {
			
			$the404s = array();
			$this->update_setting('log', array());
			$this->queue_message('success', __('The log was successfully cleared.', 'seolat-tool-plus'));
		}
		
		if (!is_array($the404s) || !count($the404s))
			$this->queue_message('success', __('No 404 errors in the log.', 'seolat-tool-plus'));
		
		$this->print_messages();
		
		if (is_array($the404s) && count($the404s)) {
			
			$this->clear_log_button();
			
			echo "<div id='sl-404s-log-table'>\n";
			$headers = $this->get_admin_table_columns();
			$this->admin_wftable_start();
			
			uasort($the404s, array(&$this, 'sort_log_callback'));
			
			foreach ($the404s as $url => $data) {
				$new = $data['is_new'] ? ' sl-404s-new-hit' : '';
				
				$a_url = sl_esc_attr($url);
				$ae_url = sl_esc_attr(urlencode($url));
				$md5url = md5($url);
				
				echo "\t<tr id='sl-404s-hit-$md5url-data' class='sl-404s-hit-data$new'>\n";
				
				$this->table_cells(array(
					  'actions' =>
							  "<span class='sl-404s-hit-open'><a href='$a_url' target='_blank'><img src='{$this->module_dir_url}hit-open.png' title='".__('Open URL in new window (will not be logged)', 'seolat-tool-plus')."' /></a></span>"
							. "<span class='sl-404s-hit-cache'><a href='http://www.google.com/search?q=cache%3A{$ae_url}' target='_blank'><img src='{$this->module_dir_url}hit-cache.png' title='".__('Query Google for cached version of URL (opens in new window)', 'seolat-tool-plus')."' /></a></span>"
							. "<span class='sl-404s-hit-delete'><a href='".$this->get_nonce_url('delete', $url)."'><img src='{$this->module_dir_url}hit-delete.png' title='".__('Remove this URL from the log', 'seolat-tool-plus')."' /></a></span>"
					, 'hit-count' => $data['hit_count']
					, 'url' => "<attr title='$a_url'>" . esc_html(lat_string::truncate($url, 100)) . '</attr>'
					, 'last-hit-time' => sprintf(__('%s at %s', 'seolat-tool-plus')
						, date_i18n(get_option('date_format'), $data['last_hit_time'])
						, date_i18n(get_option('time_format'), $data['last_hit_time'])
						)
					, 'referers' => number_format_i18n(count($data['referers'])) . (count($data['referers']) ? " <a href='#' class='sl_toggle_hide' data-toggle='sl-404s-hit-$md5url-referers'><img src='{$this->module_dir_url}hit-details.png' title='".__('View list of referring URLs', 'seolat-tool-plus')."' /></a>" : '')
					, 'user-agents' => number_format_i18n(count($data['user_agents'])) . (count($data['user_agents']) ? " <a href='#' class='sl_toggle_hide' data-toggle='sl-404s-hit-$md5url-user-agents'><img src='{$this->module_dir_url}hit-details.png' title='".__('View list of user agents', 'seolat-tool-plus')."' /></a>" : '')
				));
				
				echo "\t</tr>\n";
				
				echo "\t<tr class='sl-404s-hit-referers$new'>\n\t\t<td colspan='".count($headers)."'>";
				
				if (count($data['referers'])) {
					
					echo "<div id='sl-404s-hit-$md5url-referers' class='sl-404s-hit-referers-list' style='display:none;'>\n";
					echo "\t\t\t<div><strong>".__('Referring URLs', 'seolat-tool-plus')."</strong> &mdash; ";
					echo "<a href='#' class='sl_toggle_up' data-toggle='sl-404s-hit-$md5url-referers'>".__('Hide list', 'seolat-tool-plus')."</a>";
					echo "</div>\n";
					echo "\t\t\t<ul>\n";
					
					foreach ($data['referers'] as $referer) {
						$referer = sl_esc_attr($referer); //Don't let attacks pass through the referer URLs!
						echo "\t\t\t\t<li><a href='$referer' target='_blank'>$referer</a></li>\n";
					}
					
					echo "\t\t\t</ul>\n";
					
					echo "\t\t</div>";
				}
				
				echo "</td>\n\t</tr>\n";
				
				echo "\t<tr class='sl-404s-hit-user-agents$new'>\n\t\t<td colspan='".count($headers)."'>";
				
				if (count($data['user_agents'])) {
					echo "<div id='sl-404s-hit-$md5url-user-agents' class='sl-404s-hit-user-agents-list' style='display:none;'>\n";
					echo "\t\t\t<div><strong>".__('User Agents', 'seolat-tool-plus')."</strong> &mdash; ";
					echo "<a href='#' class='sl_toggle_up' data-toggle='sl-404s-hit-$md5url-user-agents'>".__('Hide list', 'seolat-tool-plus')."</a>";
					echo "</div>\n";
					echo "\t\t\t<ul>\n";
					
					foreach ($data['user_agents'] as $useragent) {
						$useragent = sl_esc_html($useragent); //Don't let attacks pass through the user agent strings!
						echo "\t\t\t\t<li>$useragent</li>\n";
					}
					
					echo "\t\t\t</ul>\n";
					
					echo "</td>\n\t</tr>\n";
				}
				
				echo "\t\t</div>";
				
				$the404s[$url]['is_new'] = false;
			}
			
			$this->update_setting('log', $the404s);
			
			$this->admin_wftable_end();
			echo "</div>\n";
			
			$this->clear_log_button();
		}
	}
	
	function clear_log_button() {
		//Create the "Clear Log" button
		$clearurl = $this->get_nonce_url('clear');
		$confirm = __('Are you sure you want to delete all 404 log entries?', 'seolat-tool-plus');
		echo "<div class='sl-404s-clear-log'><a href=\"$clearurl\" class=\"button-secondary\" onclick=\"javascript:return confirm('$confirm')\">";
		_e('Clear Log', 'seolat-tool-plus');
		echo "</a></div>";
	}
}

}
?>