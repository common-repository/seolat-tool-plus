<?php
/**
 * Import Module
 * 
 * @abstract
 * @since 1.5
 */

if (class_exists('SL_Module')) {

class SL_ImportModule extends SL_Module {
	
	var $error = false;
	
	function get_menu_parent() { return 'sl-import-modules'; }
	
	function get_op_title() { return $this->get_module_title(); }
	function get_op_abbr() { return $this->get_module_title(); }
	function get_import_desc() { return ''; }
	
	function get_default_settings() {
		return array(
			  'import_postmeta' => true
			, 'postmeta_bothexist_action' => 'skip'
			, 'after_post_import' => 'nothing'
		);
	}
	
	function admin_page() {
		$this->admin_page_start('tools');
		
		if ($this->is_action('update')) {
			ob_start();
			$this->admin_page_contents();
			ob_end_clean();
			
			$this->import_page_contents();
		} else
			$this->admin_page_contents();
		
		$this->admin_page_end();
	}
	
	function admin_page_postmeta() {
		
		$name = $this->get_op_title();
		$abbr = $this->get_op_abbr();
		
		$this->textblock('<strong>'.__('Import Post Fields', 'seolat-tool-plus').'</strong> &mdash; '.
			sprintf(__('Post fields store the SEO data for your posts/pages (i.e. your custom title tags, meta descriptions, and meta keywords). If you provided custom titles/descriptions/keywords to %s, this importer can move that data over to SEOLAT Tool Plus.', 'seolat-tool-plus'), $name)
		);
		$this->admin_form_indent_start();
		$this->admin_form_group_start(__('Conflict Resolution Mode', 'seolat-tool-plus'));
		$this->textblock(sprintf(__('What should the import tool do if it tries to move over a post&#8217;s %s data, but different data already exists in the corresponding SEOLAT Tool Plus fields?', 'seolat-tool-plus'), $abbr));
		$this->radiobuttons('postmeta_bothexist_action', array(
			  'skip' => __('Skip that post and leave all data as-is (default).', 'seolat-tool-plus')
			, 'delete_sl' => sprintf(__('Delete the SEOLAT Tool Plus data and replace it with the %s data.', 'seolat-tool-plus'), $abbr)
			, 'delete_op' => sprintf(__('Keep the SEOLAT Tool Plus data and delete the %s data.', 'seolat-tool-plus'), $abbr)
		));
		$this->admin_form_group_end();
		$this->admin_form_group_start(__('Deletion Preference', 'seolat-tool-plus'));
		$this->textblock(sprintf(__('When the migration tool successfully copies a post&#8217;s %1$s data over to SEOLAT Tool Plus, what should it do with the old %1$s data?', 'seolat-tool-plus'), $abbr));
		$this->radiobuttons('after_post_import', array(
			  'delete_op' => sprintf(__('Delete the %s data.', 'seolat-tool-plus'), $abbr)
			, 'nothing' => sprintf(__('Leave behind the duplicate %s data (default).', 'seolat-tool-plus'), $abbr)
		));
		$this->admin_form_group_end();
		$this->admin_form_indent_end();
	}
	
	function admin_form_end($button = null, $table = true) {
		if ($button === null) $button = __('Import Now', 'seolat-tool-plus');
		parent::admin_form_end($button, $table);
		
		$this->print_message('warning', sprintf(__('The import cannot be undone. It is your responsibility to <a href="%s" target="_blank">backup your database</a> before proceeding!', 'seolat-tool-plus'), lat_wp::get_backup_url()));
	}
	
	function import_page_contents() {
		
		echo "<div id='import-status'>\n";
		$this->do_import();
		
		if (!$this->error)
			$this->import_status('success', __('Import complete.', 'seolat-tool-plus'));
		
		echo "</div>\n";
		
		if ($this->error) {
			echo '<p><a href="admin.php?page=sl-import-aiosp" class="button-secondary">';
			_e('Return to import page', 'seolat-tool-plus');
		} elseif ($this->plugin->module_exists('settings')) {
			echo '<p><a href="options-general.php?page=seo-lat#sl-import" class="button-secondary">';
			_e('Return to settings page', 'seolat-tool-plus');
		} else {
			echo '<p><a href="admin.php?page=seo" class="button-secondary">';
			_e('Return to SEO page', 'seolat-tool-plus');
		}
		echo "</a></p>\n";
	}
	
	function import_status($type, $message) {
		if (strcmp($type, 'error') == 0) $this->error = true;
		$this->print_mini_message($type, $message);
	}
	
	function import_option($module, $key, $option) {
		if (!isset($this->settings[$module][$key]) || $this->get_setting('overwrite_sl')) {
			$this->settings[$module][$key] = get_option($option);
			if ($this->get_setting('delete_import')) delete_option($option);
		}
	}
	
	function do_import_deactivate($path) {
		if (is_plugin_active($path)) {
			deactivate_plugins($path);
			$this->import_status('success', sprintf(__('Deactivated %s.', 'seolat-tool-plus'), $this->get_op_title()));
		}
	}
	
	function do_import_postmeta($postmeta_fields, $disabled_field=false) {
		
		$name = $this->get_op_title();
		$abbr = $this->get_op_abbr();
		
		global $wpdb;
		$posts = $wpdb->get_results("SELECT `ID` FROM {$wpdb->posts}");
		
		$numposts = 0;
		$numfields = 0;
		$numsudels = 0;
		$numopdels = 0;
		
		foreach ($posts as $p) {
			
			//Skip posts with "disabled" data
			if ($disabled_field && get_post_meta($p->ID, $disabled_field, true) === 'on')
				$numskipped++;
			else {
				
				foreach ($postmeta_fields as $op_field => $sl_field) {
					
					if (strlen($op_value = get_post_meta($p->ID, $op_field, true))) {
						
						$delete_op = false;
						
						if (strlen(get_post_meta($p->ID, $sl_field, true))) {
							//Conflict: SEOLAT Tool Plus field already exists
							
							switch ($this->get_setting('postmeta_bothexist_action')) {
								case 'skip': continue 2; break;
								case 'delete_sl': $numsudels++; break;
								case 'delete_op': $delete_op = true; break;
							}
						}
						
						//Import the other plugin's data if we're not supposed to delete it.
						if (!$delete_op)
							update_post_meta($p->ID, $sl_field, $op_value);
						
						//Delete the other plugin's data if the user has instructed us to do so
						if ($delete_op || $this->get_setting('after_post_import') == 'delete_op') {
							delete_post_meta($p->ID, $op_field, $op_value);
							$numopdels++;
						}
						
						$numfields++;
					}
				}
			}
			
			$numposts++;
		}
		
		$this->import_status('success', sprintf(_n(
			'Imported a total of %d fields for one post/page/revision.',
			'Imported a total of %1$d fields for %2$d posts/pages/revisions.',
			$numposts, 'seolat-tool-plus'), $numfields, $numposts));
		
		if ($numskipped > 0)
			$this->import_status('info', sprintf(_n(
				'Skipped one post with disabled %2$s data.',
				'Skipped %1$d posts with disabled %2$s data.',
				$numskipped, 'seolat-tool-plus'), $numskipped, $abbr));
		
		if ($numsudels > 0)
			$this->import_status('info', sprintf(_n(
				'Overwrote one SEOLAT Tool Plus field with %2$s data, as instructed by the settings you chose.',
				'Overwrote %1$d SEOLAT Tool Plus fields with %2$s data, as instructed by the settings you chose.',
				$numsudels, 'seolat-tool-plus'), $numsudels, $abbr));
		
		if ($numopdels > 0)
			$this->import_status('info', sprintf(_n(
				'Deleted one %2$s field, as instructed by the settings you chose.',
				'Deleted %1$d %2$s fields, as instructed by the settings you chose.',
				$numopdels, 'seolat-tool-plus'), $numopdels, $abbr));
	}
}

}
// Omit closing PHP tag to avoid "Headers already sent" issues.
