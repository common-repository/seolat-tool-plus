<?php
/**
 * Meta Robot Tags Editor Module
 * 
 * @since 4.0
 */

if (class_exists('SL_Module')) {

class SL_MetaRobots extends SL_Module {
	
	static function get_module_title() { return __('Meta Robot Tags Editor', 'seolat-tool-plus'); }
	static function get_menu_title()   { return __('Meta Robot Tags', 'seolat-tool-plus'); }
	function get_settings_key() { return 'meta'; }
	
	function init() {
		// js scripts
		add_action( 'admin_enqueue_scripts', array($this, 'meta_robots_add_js'));
		// ============================================================================

		add_filter('sl_meta_robots', array(&$this, 'meta_robots'));
	}

	// add js script
	function meta_robots_add_js($hook) {
		wp_enqueue_script( 'meta_robots', plugin_dir_url( __FILE__ ).'meta-robots-script.js', array('jquery'));
	}

	function get_admin_page_tabs() {
		return array(
			array('title' => __('Sitewide Values', 'seolat-tool-plus'), 'id' => 'sl-sitewide-values', 'callback' => 'global_tab')
		);
	}
	
	function global_tab() {
		$this->admin_form_table_start();
		$this->checkboxes(array(
				'noarchive' => __('Don&#8217t cache or archive this site.', 'seolat-tool-plus')
			), __('Spider Instructions', 'seolat-tool-plus'));
		$this->admin_form_table_end();
	}
	
	//Add the appropriate commands to the meta robots array
	function meta_robots($commands) {
		
		$tags = array('noarchive');
		
		foreach ($tags as $tag) {
			if ($this->get_setting($tag)) $commands[] = $tag;
		}
		
		return $commands;
	}
	
	function add_help_tabs($screen) {
		
		$screen->add_help_tab(array(
			  'id' => 'sl-meta-robots-overview'
			, 'title' => __('Overview', 'seolat-tool-plus')
			, 'content' => __("
<ul>
	<li><strong>What it does:</strong> Meta Robot Tags Editor lets you convey instructions to search engine spiders, as well as prohibit the spiders from indexing certain webpages on your blog using the <code>&lt;meta name=&quot;robots&quot; content=&quot;noindex&quot; /&gt;</code> tag.</li>
	<li><strong>Why it helps:</strong> The &#8220;Global&#8221; tab lets you stop DMOZ or Yahoo! Directory from overriding your custom meta descriptions, as well as prevent spiders from caching your site if you so desire. The &#8220;Default Values&#8221; tab lets you deindex entire sections of your site that contain content unimportant to visitors (e.g. the administration section), or sections of your site that mostly contain duplicate content (e.g. date archives). The editor tabs can do something similar, but for individual content items. By removing webpages from search results that visitors find unhelpful, you can help increase the focus on your more useful content.</li>
	<li><strong>How to use it:</strong> Adjust the settings as desired, and then click Save Changes. You can refer to the &#8220;Settings Help&#8221; tab for information on the settings available. You can also use the editor tabs to deindex individual content items on your site as well as enable the &#8220;nofollow&#8221; meta parameter that will nullify all outgoing links on a specific webpage.</li>
</ul>
", 'seolat-tool-plus')));
		
		$screen->add_help_tab(array(
				  'id' => 'sl-meta-robots-global'
				, 'title' => __('Sitewide Settings Tab', 'seolat-tool-plus')
				, 'content' => __("
<ul>
	<li><strong>Don&#8217;t use this site&#8217;s Open Directory / Yahoo! Directory description in search results</strong> &mdash; If your site is listed in the <a href='http://www.dmoz.org/' target='_blank'>Open Directory (DMOZ)</a> or the <a href='http://dir.yahoo.com/' target='_blank'>Yahoo! Directory</a>, some search engines may use your directory listing as the meta description. These boxes tell search engines not to do that and will give you full control over your meta descriptions. These settings have no effect if your site isn&#8217;t listed in the Open Directory or Yahoo! Directory respectively.</li>
	<li><strong>Don&#8217;t cache or archive this site</strong> &mdash; When you check this box, Meta Editor will ask search engines (Google, Yahoo!, Bing, etc.) and archivers (Archive.org, etc.) to <em>not</em> make cached or archived &#8220;copies&#8221; of your site.</li>
</ul>
", 'seolat-tool-plus')));
		
		$screen->add_help_tab(array(
				  'id' => 'sl-meta-robots-defaults'
				, 'title' => __('Default Values Tab', 'seolat-tool-plus')
				, 'content' => __("
<p><strong>Prevent indexing of&hellip;</strong></p>
<ul>
	<li><strong>Administration back-end pages</strong> &mdash; Tells spiders not to index the administration area (the part you&#8217;re in now), in the unlikely event a spider somehow gains access to the administration. Recommended.</li>
	<li><strong>Author archives</strong> &mdash; Tells spiders not to index author archives. Useful if your blog only has one author.</li>
	<li><strong>Blog search pages</strong> &mdash; Tells spiders not to index the result pages of WordPress's blog search function. Recommended.</li>
	<li><strong>Category archives</strong> &mdash; Tells spiders not to index category archives. Recommended only if you don't use categories.</li>
	<li><strong>Comment feeds</strong> &mdash; Tells spiders not to index the RSS feeds that exist for every post's comments. (These comment feeds are totally separate from your normal blog feeds.)</li>
	<li><strong>Comment subpages</strong> &mdash; Tells spiders not to index posts' comment subpages.</li>
	<li><strong>Date-based archives</strong> &mdash; Tells spiders not to index day/month/year archives. Recommended, since these pages have little keyword value.</li>
	<li><strong>Subpages of the homepage</strong> &mdash; Tells spiders not to index the homepage's subpages (page 2, page 3, etc). Recommended.</li>
	<li><strong>Tag archives</strong> &mdash; Tells spiders not to index tag archives. Recommended only if you don't use tags.</li>
	<li><strong>User login/registration pages</strong> &mdash; Tells spiders not to index WordPress's user login and registration pages. Recommended.</li>
</ul>
", 'seolat-tool-plus')));
		
		$screen->add_help_tab(array(
				  'id' => 'sl-meta-robots-metaedit'
				, 'title' => __('Bulk Editor Tabs', 'seolat-tool-plus')
				, 'content' => __("
<ul>
	<li><strong>Noindex</strong> &mdash; Checking this for an item will ask search engines to remove that item&#8217;s webpage from their indices. Use this to remove pages that you don&#8217;t want showing up in search results (such as a Privacy Policy page, for example).</li>
	<li><strong>Nofollow</strong> &mdash; Checking this for an item will tell search engines to ignore the links to other webpages that are on that item&#8217;s webpage. Note: this is page-level &#8220;meta nofollow,&#8221; not to be confused with link-level &#8220;rel nofollow.&#8221;</li>
</ul>
", 'seolat-tool-plus')));
		
		$screen->add_help_tab(array(
			  'id' => 'sl-meta-robots-troubleshooting'
			, 'title' => __('Troubleshooting', 'seolat-tool-plus')
			, 'content' => __("
<ul>
	<li>
		<p><strong>What do I do if my site has multiple meta tags?</strong><br />First, try removing your theme&#8217;s built-in meta tags if it has them. Go to <a href='theme-editor.php' target='_blank'>Appearance &rArr; Editor</a> and edit <code>header.php</code>. Delete or comment-out any <code>&lt;meta&gt;</code> tags.</p>
		<p>If the problem persists, try disabling other SEO plugins that may be generating meta tags.</p>
		<p>Troubleshooting tip: Go to <a href='options-general.php?page=seo-lat'>Settings &rArr; SEO LAT+</a> and enable the &#8220;Insert comments around HTML code insertions&#8221; option. This will mark SEOLAT Tool Plus meta tags with comments, allowing you to see which meta tags are generated by SEOLAT Tool Plus and which aren&#8217;t.</p>
	</li>
</ul>
", 'seolat-tool-plus')));
		
	}
}

}
?>