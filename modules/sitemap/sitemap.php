<?php
/*
 * Plugin Name: Simple Wp Sitemap
 * Plugin URI: http://www.webbjocke.com/simple-wp-sitemap/
 * Description: An easy, fast and secure plugin that adds both an xml and an html sitemap to your site, which updates and maintains themselves so you dont have to!
 * Version: 1.1.4
 * Author: Webbjocke
 * Author URI: http://www.webbjocke.com/
 * License: GPLv3
 */

/*
Simple Wp Sitemap - Wordpress plugin
Copyright (C) 2016 Webbjocke

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see http://www.gnu.org/licenses/.
*/

/**
 * Sitemap Module
 *
 * @since 1.0.4
 */

if (class_exists('SL_Module')) {

	class Sl_Sitemap extends SL_Module
	{
		var $namespaces_declared = false;
		var $jlsuggest_box_post_id = false;

		static function get_module_title()
		{
			return __('Sitemap Creator', 'seolat-tool-plus');
		}

		static function get_menu_title()
		{
			return __('Sitemap Creator', 'seolat-tool-plus');
		}
		
		function get_default_status() {
			if (is_multisite()) {
				return SL_MODULE_DISABLED;
			}
			
			return SL_MODULE_ENABLED;
		}
		
		function init()
		{
			add_action('admin_menu', array(__CLASS__, 'sitemapAdminSetup'));
		}
		
    private static $version = 13; // only changes when needed

    // Runs on plugin activation
    public static function activateSitemaps () {
        self::rewriteRules();
        flush_rewrite_rules();

        require_once 'simpleWpMapOptions.php';
        $ops = new SimpleWpMapOptions();
        $ops->migrateFromOld();

        update_option('simple_wp_sitemap_version', self::$version);
    }

    // Runs on plugin deactivation
    public static function deactivateSitemaps () {
        flush_rewrite_rules();
    }

    // Updates the plugin if needed (calls activateSitemaps)
    public static function updateCheck () {
        if (!($current = get_option('simple_wp_sitemap_version')) || $current < self::$version) {
            self::activateSitemaps();
        }
    }

    // Registers most hooks
    public static function registerHooks () {
        register_activation_hook(__FILE__, array(__CLASS__, 'activateSitemaps'));
        register_deactivation_hook(__FILE__, array(__CLASS__, 'deactivateSitemaps'));
				add_action( 'upgrader_process_complete', array(__CLASS__, 'activateSitemaps'), 10, 2 );
        add_action('init', array(__CLASS__, 'rewriteRules'), 1);
				
        add_filter('query_vars', array(__CLASS__, 'addSitemapQuery'), 1);
        add_filter('template_redirect', array(__CLASS__, 'generateSitemapContent'), 1);
        add_filter("plugin_action_links_" . plugin_basename(__FILE__), array(__CLASS__, 'pluginSettingsLink'));
    }

    // Adds a link to settings from the plugins page
    public static function pluginSettingsLink ($links) {
        return array_merge($links, array(sprintf('<a href="%s">%s</a>', esc_url(admin_url('admin.php?page=sl-sitemap')), __('Settings'))));
    }

    // Sets the option menu for admins and enqueues scripts n styles
    public static function sitemapAdminSetup () {
				add_submenu_page(
					'seo',
					'Sitemap',
					'Sitemap',
					'administrator',
					'sl-sitemap',
					array(__CLASS__, 'sitemapAdminArea')
				);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'sitemapScriptsAndStyles'));
        add_action('admin_init', array(__CLASS__, 'sitemapAdminInit'));
    }
    // Registers settings on admin_init and checks for updates
    public static function sitemapAdminInit () {
        foreach (array('simple_wp_other_urls', 'simple_wp_block_urls', 'simple_wp_attr_link', 'simple_wp_disp_categories', 'simple_wp_disp_tags', 'simple_wp_disp_authors', 'simple_wp_disp_sitemap_order', 'simple_wp_sitemap_version', 'simple_wp_last_updated') as $setting) {
            register_setting('simple_wp-sitemap-group', $setting);
        }
        self::updateCheck();
    }

    // Rewrite rules for sitemaps
    public static function rewriteRules () {
        add_rewrite_rule('sitemap\.xml$', 'index.php?thesimplewpsitemap=xml', 'top');
        add_rewrite_rule('sitemap\.html$', 'index.php?thesimplewpsitemap=html', 'top');
    }

    // Add custom query
    public static function addSitemapQuery ($vars) {
        $vars[] = 'thesimplewpsitemap';
        return $vars;
    }

    // Generates the content
    public static function generateSitemapContent () {
        global $wp_query;
        if (isset($wp_query->query_vars['thesimplewpsitemap']) && in_array(($q = $wp_query->query_vars['thesimplewpsitemap']), array('xml', 'html'))) {
            $wp_query->is_404 = false;

            require_once 'simpleWpMapBuilder.php';
            $sitemap = new SimpleWpMapBuilder();

            if ($q === 'xml') {
                header('Content-type: application/xml; charset=utf-8');
            }
            $sitemap->getContent($q);
            exit;
        }
    }

    // Add custom scripts and styles to the plugins customization page in admin area
    public static function sitemapScriptsAndStyles ($page) {
        if ($page === 'seo_page_sl-sitemap') {
            wp_enqueue_style('simple-wp-sitemap-admin-css', plugin_dir_url( __FILE__ ) . 'css/simple-wp-sitemap-admin.css', array(), self::$version);
            wp_enqueue_script('simple-wp-sitemap-admin-js', plugin_dir_url( __FILE__ ) . 'js/simple-wp-sitemap-admin.js', array('jquery'), self::$version, true);
        }
    }

    // Interface for settings page, also handles initial post request when settings are changed
    public static function sitemapAdminArea () {
        require_once 'simpleWpMapOptions.php';
        $options = new SimpleWpMapOptions();

        if (isset($_POST['simple_wp_other_urls'], $_POST['simple_wp_block_urls'], $_POST['simple_wp_home_n'], $_POST['simple_wp_posts_n'], $_POST['simple_wp_pages_n'], $_POST['simple_wp_other_n'], $_POST['simple_wp_categories_n'], $_POST['simple_wp_tags_n'], $_POST['simple_wp_authors_n'], $_POST['simple_wp_active-page'], $_POST['simple_wp_last_updated'])) {

            $order = $options->getDefaultOrder();
            foreach ($order as $key => $val) {
                $arr = explode('-|-', $_POST['simple_wp_' . $key . '_n']);
                $order[$key] = array('i' => $arr[0], 'title' => isset($arr[1]) ? $arr[1] : $key);
            }
            $options->setOptions($_POST['simple_wp_other_urls'], $_POST['simple_wp_block_urls'], (isset($_POST['simple_wp_attr_link']) ? 1 : 0), (isset($_POST['simple_wp_disp_categories']) ? 1 : 0), (isset($_POST['simple_wp_disp_tags']) ? 1 : 0), (isset($_POST['simple_wp_disp_authors']) ? 1 : 0), $order, $_POST['simple_wp_active-page'], $_POST['simple_wp_last_updated']);
        }
        
				/*
 * Simple Wp Sitemap Admin interface
 */
?>
<div class="wrap">
    <h2 id="simple-wp-sitemap-h2">
        <span>Sitemap settings</span>
    </h2>
    <p>Your two sitemaps are active! Here you can change and customize them.</p>
    <p><strong>Links to your xml and html sitemap:</strong></p>
    <ul>
        <li>Xml sitemap: <a href="<?php echo $options->sitemapUrl('xml'); ?>"><?php echo $options->sitemapUrl('xml'); ?></a></li>
        <li>Html sitemap: <a href="<?php echo $options->sitemapUrl('html'); ?>"><?php echo $options->sitemapUrl('html'); ?></a></li>
    </ul>
    <noscript>(Please enable javascript to edit options)</noscript>
    <form method="post" action="<?php echo $options->getSubmitUrl(); ?>" id="simple-wp-sitemap-form">

        <?php settings_fields('simple_wp-sitemap-group'); ?>

        <ul id="sitemap-settings">
            <li id="sitemap-normal" class="sitemap-active">General</li>
            <li id="sitemap-advanced">Order</li>
        </ul>
        <input type="hidden" id="simple_wp_active-page" name="simple_wp_active-page" value="<?php echo $options->getPage(); ?>">

        <table id="sitemap-table-show" class="widefat form-table table-hidden" data-id="sitemap-normal">
            <tr><td><strong>Add pages</strong></td></tr>
            <tr><td>Add pages to the sitemaps in addition to your normal wordpress ones. Just paste "full" urls in the textarea like: <strong>http://www.example.com/a-page/</strong>. Each link on a new row <em>(this will affect both your xml and html sitemap)</em>.</td></tr>
            <tr><td><textarea rows="7" name="simple_wp_other_urls" placeholder="http://www.example.com/a-page/" class="large-text code" id="swsp-add-pages-textarea"><?php echo $options->getOptions('simple_wp_other_urls'); ?></textarea></td></tr>

            <tr><td><strong>Block pages</strong></td></tr>
            <tr><td>Add pages you want to block from showing up in the sitemaps. Same as above, just paste every link on a new row. <em>(Hint: copy paste links from one of the sitemaps to get correct urls)</em>.</td></tr>
            <tr><td><textarea rows="7" name="simple_wp_block_urls" placeholder="http://www.example.com/block-this-page/" class="large-text code"><?php echo $options->getOptions('simple_wp_block_urls'); ?></textarea></td></tr>

            <tr><td><strong>Extra sitemap includes</strong></td></tr>
            <tr><td>Check if you want to include categories, tags and/or author pages in the sitemaps.</td></tr>
            <tr><td><input type="checkbox" name="simple_wp_disp_categories" id="simple_wp_cat" <?php echo $options->getOptions('simple_wp_disp_categories'); ?>><label for="simple_wp_cat"> Include categories</label></td></tr>
            <tr><td><input type="checkbox" name="simple_wp_disp_tags" id="simple_wp_tags" <?php echo $options->getOptions('simple_wp_disp_tags'); ?>><label for="simple_wp_tags"> Include tags</label></td></tr>
            <tr><td><input type="checkbox" name="simple_wp_disp_authors" id="simple_wp_authors" <?php echo $options->getOptions('simple_wp_disp_authors'); ?>><label for="simple_wp_authors"> Include authors</label></td></tr>

            
        </table>

        <table class="widefat form-table table-hidden" data-id="sitemap-advanced">
            <tr><td><strong>Display order &amp; titles</strong></td></tr>
            <tr><td>
                If you want to change the display order in your sitemaps, click the arrows to move sections up or down. They will be displayed as ordered below <em>(highest up is displayed first and lowest down last)</em>.<br><br>
                Hit the "Change" buttons to change the title displayed in the sitemaps.
            </td></tr>
            <tr><td>
                <ul id="sitemap-display-order">
                    <?php if (!($orderArray = $options->getOptions('simple_wp_disp_sitemap_order'))) {
                        $orderArray = $options->getDefaultOrder();
                    }
                    foreach ($orderArray as $key => $val) {
                        printf('<li><span class="swp-name" data-name="%s">%s</span><span class="sitemap-down" title="move down"></span><span class="sitemap-up" title="move up"></span><input type="hidden" name="simple_wp_%s_n" value="%d"><input type="button" value="Change" class="button-secondary sitemap-change-btn"></li>', $key, $val['title'], $key, $val['i']);
                    } ?>
                </ul>
                </td></tr>
            <tr><td><strong>Last updated text:</strong> <input type="text" name="simple_wp_last_updated" placeholder="Last updated" value="<?php echo $options->getOptions('simple_wp_last_updated'); ?>" id="simple_wp_last_updated"></td></tr>
            <tr><td><input type="button" id="sitemap-defaults" class="button-secondary" title="Restore the default display order" value="Restore defaults"></td></tr>
        </table>

        <p class="submit"><input type="submit" class="button-primary" value="Save Changes"></p>
        <p><em>(If you have a caching plugin, you might have to clear cache before changes will be shown in the sitemaps)</em></p>
    </form>

    <form method="post" action="<?php echo $options->getSubmitUrl(); ?>" class="table-hidden" id="simpleWpHiddenForm">
        <input type="hidden">
    </form>
</div>
    <?php }

	}
}
Sl_Sitemap::registerHooks();