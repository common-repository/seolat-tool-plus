<?php
/**
 * Non-class functions.
 */

/********** INDEPENDENTLY-OPERABLE FUNCTIONS **********/

/**
 * Returns the plugin's User-Agent value.
 * Can be used as a WordPress filter.
 * 
 * @since 0.1
 * @uses SL_USER_AGENT
 * 
 * @return string The user agent.
 */
function sl_get_user_agent() {
	return SL_USER_AGENT;
}

/**
 * Records an event in the debug log file.
 * Usage: sl_debug_log(__FILE__, __CLASS__, __FUNCTION__, __LINE__, "Message");
 * 
 * @since 0.1
 * @uses SL_VERSION
 * 
 * @param string $file The value of __FILE__
 * @param string $class The value of __CLASS__
 * @param string $function The value of __FUNCTION__
 * @param string $line The value of __LINE__
 * @param string $message The message to log.
 */
function sl_debug_log($file, $class, $function, $line, $message) {
	global $seo_lat_tool_plus;
	if (isset($seo_lat_tool_plus->modules['settings']) && $seo_lat_tool_plus->modules['settings']->get_setting('debug_mode') === true) {
	
		$date = date("Y-m-d H:i:s");
		$version = SL_VERSION;
		$message = str_replace("\r\n", "\n", $message);
		$message = str_replace("\n", "\r\n", $message);
		
		$log = "Date: $date\r\nVersion: $version\r\nFile: $file\r\nClass: $class\r\nFunction: $function\r\nLine: $line\r\nMessage: $message\r\n\r\n";
		$logfile = trailingslashit(dirname(__FILE__))."seolat-tool-plus.log";
		
		@error_log($log, 3, $logfile);
	}
}

/**
 * Joins strings into a natural-language list.
 * Can be internationalized with gettext or the sl_lang_implode filter.
 * 
 * @since 1.1
 * 
 * @param array $items The strings (or objects with $var child strings) to join.
 * @param string|false $var The name of the items' object variables whose values should be imploded into a list.
	If false, the items themselves will be used.
 * @param bool $ucwords Whether or not to capitalize the first letter of every word in the list.
 * @return string|array The items in a natural-language list.
 */
function sl_lang_implode($items, $var=false, $ucwords=false) {
	
	if (is_array($items) ) {
		
		if (strlen($var)) {
			$_items = array();
			foreach ($items as $item) $_items[] = $item->$var;
			$items = $_items;
		}
		
		if ($ucwords) $items = array_map('ucwords', $items);
		
		switch (count($items)) {
			case 0: $list = ''; break;
			case 1: $list = $items[0]; break;
			case 2: $list = sprintf(__('%s and %s', 'seolat-tool-plus'), $items[0], $items[1]); break;
			default:
				$last = array_pop($items);
				$list = implode(__(', ', 'seolat-tool-plus'), $items);
				$list = sprintf(__('%s, and %s', 'seolat-tool-plus'), $list, $last);
				break;
		}
		
		return apply_filters('sl_lang_implode', $list, $items);
	}

	return $items;
}

/**
 * Escapes an attribute value and removes unwanted characters.
 * 
 * @since 0.8
 * 
 * @param string $str The attribute value.
 * @return string The filtered attribute value.
 */
function sl_esc_attr($str) {
	if (!is_string($str)) return $str;
	$str = str_replace(array("\t", "\r\n", "\n"), ' ', $str);
	$str = esc_attr($str);
	return $str;
}

/**
 * Escapes HTML.
 * 
 * @since 2.1
 */
function sl_esc_html($str) {
	return esc_html($str);
}

/**
 * Escapes HTML. Double-encodes existing entities (ideal for editable HTML).
 * 
 * @since 1.5
 * 
 * @param string $str The string that potentially contains HTML.
 * @return string The filtered string.
 */
function sl_esc_editable_html($str) {
	return _wp_specialchars($str, ENT_QUOTES, false, true);
}

// Add a shortcut link for admin toolbar
function seo_lat_tool_plus_admin_bar_menu( $meta = true ) {
	global $wp_admin_bar, $seo_lat_tool_plus;
		if ( !is_user_logged_in() ) { return; }
		if ( !is_super_admin() || !is_admin_bar_showing() ) { return; }
		if (isset($seo_lat_tool_plus->modules['settings']) && $seo_lat_tool_plus->modules['settings']->get_setting('seo_toolbar_menu') === false) { return; }
		
			// Add the parent link for admin toolbar
			$args = array(
				'id' => 'slplus-menu',
				'title' => __( 'SEO', 'seolat-tool-plus' ),
				'href' => self_admin_url( 'admin.php?page=seo' ), 
				'meta' => array(
					'class' => 'seolat-tool-plus',
					'title' => __( 'SEO', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-sds-blog',
				'title' =>  SL_SdsBlog::get_menu_title(), //__( 'What&#39;s New', 'seolat-tool-plus' ), 
				'href' => self_admin_url( 'admin.php?page=sl-sds-blog' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-sds-blog', 
					'title' => SL_SdsBlog::get_menu_title(), //__( 'What&#39;s New', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
		
			// Add the child link for SEO Settings
			$wp_admin_bar->add_menu( array(
				'parent' => 'slplus-menu',
				'id'     => 'slplus-menu-settings',
				'title'  => __( 'SEO Settings', 'seolat-tool-plus' ),
				'#',
			) );
			
			$args = array(
				'id' => 'sl-modules',
				'title' => __( 'Modules', 'seolat-tool-plus' ), 
				'href' => self_admin_url( 'admin.php?page=seo' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-modules', 
					'title' => __( 'Modules', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-opengraph',
				'title' => __( 'Open Graph+', 'seolat-tool-plus' ),
				'href' => self_admin_url( 'admin.php?page=sl-opengraph' ),
				'parent' => 'slplus-menu-settings',
				'meta' => array(
					'class' => 'sl-opengraph', 
					'title' => __( 'Open Graph+', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-titles',
				'title' => __( 'Title Tag Rewriter', 'seolat-tool-plus' ),
				'href' => self_admin_url( 'admin.php?page=sl-titles' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-titles', 
					'title' => __( 'Title Tag Rewriter', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-meta-descriptions',
				'title' => __( 'Meta Description Editor', 'seolat-tool-plus' ), 
				'href' => self_admin_url( 'admin.php?page=sl-meta-descriptions' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-meta-descriptions', 
					'title' => __( 'Meta Description Editor', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-meta-robots',
				'title' => __( 'Meta Robot Tags Editor', 'seolat-tool-plus' ), 
				'href' => self_admin_url( 'admin.php?page=sl-meta-robots' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-meta-robots', 
					'title' => __( 'Meta Robot Tags Editor', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-autolinks',
				'title' => __( 'Deeplink Juggernaut', 'seolat-tool-plus' ), 
				'href' => self_admin_url( 'admin.php?page=sl-autolinks' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-autolinks', 
					'title' => __( 'Deeplink Juggernaut', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-canonical-url',
				'title' => __( 'Global Canonical Manager', 'seolat-tool-plus' ), 
				'href' => self_admin_url( 'admin.php?page=sl-canonical-url' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-canonical-url', 
					'title' => __( 'Global Canonical Manager', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);

		if(isset($seo_lat_tool_plus->modules['alt-attribute']))
		{
			$args = array(
				'id' => 'sl-alt-attribute',
				'title' => __( 'Alt Attribute', 'seolat-tool-plus' ),
				'href' => self_admin_url('upload.php'),//'admin.php?page=sl-alt-attribute' ),
				'parent' => 'slplus-menu-settings',
				'meta' => array(
						'class' => 'sl-alt-attribute',
						'title' => __( 'Alt Attribute', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
		}
           
		if(isset($seo_lat_tool_plus->modules['sitemap']))
		{
			$args = array(
				'id' => 'sl-sitemap',
				'title' => __( 'Sitemap Creator', 'seolat-tool-plus' ),
				'href' => self_admin_url('admin.php?page=sl-sitemap' ),
				'parent' => 'slplus-menu-settings',
				'meta' => array(
					'class' => 'sl-sitemap',
					'title' => __( 'Sitemap Creator', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
		}
		
            if($seo_lat_tool_plus->modules['user-code'])
            {
                $args = array(
                    'id' => 'sl-user-code',
                    'title' => __( 'Code Inserter', 'seolat-tool-plus' ),
                    'href' => self_admin_url( 'admin.php?page=sl-user-code' ),
                    'parent' => 'slplus-menu-settings',
                    'meta' => array(
                        'class' => 'sl-user-code',
                        'title' => __( 'Code Inserter', 'seolat-tool-plus' )
                    )
                );
                $wp_admin_bar->add_node($args);
            }

            if($seo_lat_tool_plus->modules['user-code-plus'])
            {
                $args = array(
                    'id' => 'sl-user-code-plus',
                    'title' => __( 'Code Inserter +', 'seolat-tool-plus' ),
                    'href' => self_admin_url( 'admin.php?page=sl-user-code-plus' ),
                    'parent' => 'slplus-menu-settings',
                    'meta' => array(
                        'class' => 'sl-user-code-plus',
                        'title' => __( 'Code Inserter +', 'seolat-tool-plus' )
                    )
                );
                $wp_admin_bar->add_node($args);
            }
			
			$args = array(
				'id' => 'sl-link-nofollow',
				'title' => __( 'Nofollow Manager', 'seolat-tool-plus' ),
				'href' => self_admin_url( 'admin.php?page=sl-misc#sl-link-nofollow' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-link-nofollow', 
					'title' => __( 'Nofollow Manager', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'seo-data-importer',
				'title' => __( 'SEO Data Importer', 'seolat-tool-plus' ), 
				'href' => self_admin_url( 'admin.php?page=seodt' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'seo-data-importer', 
					'title' => __( 'SEO Data Importer', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-internal-link-aliases',
				'title' => __( 'Link Mask Generator', 'seolat-tool-plus' ), 
				'href' => self_admin_url( 'admin.php?page=sl-internal-link-aliases' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-internal-link-aliases', 
					'title' => __( 'Link Mask Generator', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-permalinks',
				'title' => __( 'Permalink Tweaker', 'seolat-tool-plus' ),
				'href' => self_admin_url( 'admin.php?page=sl-misc#sl-permalinks' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-permalinks', 
					'title' => __( 'Permalink Tweaker', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-hreflang-language',
				'title' => __( 'Hreflang Global Setting', 'seolat-tool-plus' ),
				'href' => self_admin_url( 'admin.php?page=sl-misc#sl-hreflang-language' ),
				'parent' => 'slplus-menu-settings',
				'meta' => array(
					'class' => 'sl-hreflang-language', 
					'title' => __( 'Hreflang Global Setting', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);

			$args = array(
				'id' => 'sl-fofs',
				'title' => __( '404 Monitor', 'seolat-tool-plus' ), 
				'href' => self_admin_url( 'admin.php?page=sl-fofs' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-fofs', 
					'title' => __( '404 Monitor', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-author-links',
				'title' => __( 'Author Highlighter', 'seolat-tool-plus' ), 
				'href' => self_admin_url( 'admin.php?page=sl-misc#sl-author-links' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-author-links', 
					'title' => __( 'Author Highlighter', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);

			$args = array(
				'id' => 'sl-canonical',
				'title' => __( 'Canonicalizer', 'seolat-tool-plus' ), 
				'href' => self_admin_url( 'admin.php?page=sl-misc#sl-canonical' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-canonical',
					'title' => __( 'Canonicalizer', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-files',
				'title' => __( 'File Editor', 'seolat-tool-plus' ), 
				'href' => self_admin_url( 'admin.php?page=sl-files' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-files', 
					'title' => __( 'File Editor', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-linkbox',
				'title' => __( 'Linkbox Inserter', 'seolat-tool-plus' ), 
				'href' => self_admin_url( 'admin.php?page=sl-misc#sl-linkbox' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-linkbox',
					'title' => __( 'Linkbox Inserter', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-meta-keywords',
				'title' => __( 'Meta Keywords Editor', 'seolat-tool-plus' ), 
				'href' => self_admin_url( 'admin.php?page=sl-meta-keywords' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-meta-keywords', 
					'title' => __( 'Meta Keywords Editor', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-more-links',
				'title' => __( 'More Link Customizer', 'seolat-tool-plus' ),
				'href' => self_admin_url( 'admin.php?page=sl-misc#sl-more-links' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-more-links',
					'title' => __( 'More Link Customizer', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-misc',
				'title' => __( 'Miscellaneous', 'seolat-tool-plus' ), 
				'href' => self_admin_url( 'admin.php?page=sl-misc' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-misc', 
					'title' => __( 'Miscellaneous', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-options',
				'title' => __( 'Plugin Settings', 'seolat-tool-plus' ),
				'href' => self_admin_url( 'options-general.php?page=seo-lat' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-options',
					'title' => __( 'Plugin Settings', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			// $args = array(
			// 	'id' => 'sl-rich-snippets',
			// 	'title' => __( 'Rich Snippet Creator', 'seolat-tool-plus' ),
			// 	'href' => self_admin_url( 'admin.php?page=sl-misc#sl-rich-snippets' ),
			// 	'parent' => 'slplus-menu-settings', 
			// 	'meta' => array(
			// 		'class' => 'sl-rich-snippets',
			// 		'title' => __( 'Rich Snippet Creator', 'seolat-tool-plus' )
			// 	)
			// );
			// $wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-widgets',
				'title' => __( 'SEOLAT Tool Plus Widgets', 'seolat-tool-plus' ),
				'href' => self_admin_url( 'widgets.php' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-widgets',
					'title' => __( 'SEOLAT Tool Plus Widgets', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-wp-settings',
				'title' => __( 'Settings Monitor', 'seolat-tool-plus' ), 
				'href' => self_admin_url( 'admin.php?page=sl-wp-settings' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-wp-settings', 
					'title' => __( 'Settings Monitor', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-sharing-buttons',
				'title' => __( 'Sharing Facilitator', 'seolat-tool-plus' ),
				'href' => self_admin_url( 'admin.php?page=sl-misc#sl-sharing-buttons' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-sharing-buttons',
					'title' => __( 'Sharing Facilitator', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-slugs',
				'title' => __( 'Slug Optimizer', 'seolat-tool-plus' ),
				'href' => self_admin_url( 'admin.php?page=sl-misc#sl-slugs' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-slugs',
					'title' => __( 'Slug Optimizer', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			$args = array(
				'id' => 'sl-webmaster-verify',
				'title' => __( 'Webmaster Verification Assistant', 'seolat-tool-plus' ),
				'href' => self_admin_url( 'admin.php?page=sl-misc#sl-webmaster-verify' ),
				'parent' => 'slplus-menu-settings', 
				'meta' => array(
					'class' => 'sl-webmaster-verify',
					'title' => __( 'Webmaster Verification Assistant', 'seolat-tool-plus' )
				)
			);
			$wp_admin_bar->add_node($args);
			
			// Add the child link for Keyword Research
			$wp_admin_bar->add_menu( array(
				'parent' => 'slplus-menu',
				'id'     => 'slplus-kwresearch',
				'title'  => __( 'Keyword Research', 'seolat-tool-plus' ),
				'#',
			) );
			$args = array(
				'id' => 'sl-text-tools',
				'title' => 'Semantic Content/Competitor Analysis', 
				'href' => 'http://www.text-tools.net/members/aff/go/seodesignframework',
				'parent' => 'slplus-kwresearch', 
				'meta' => array(
					'class' => 'sl-text-tools', 
					'title' => 'Keyword and Competitor Analysis',
					'target' => '_blank'
					)
			);
			$wp_admin_bar->add_node($args);
			
			$wp_admin_bar->add_menu( array(
				'parent' => 'slplus-kwresearch',
				'id'     => 'slplus-adwordsexternal',
				'title'  => __( 'Google AdWords Keyword Planner', 'seolat-tool-plus' ),
				'href'   => 'http://adwords.google.com/keywordplanner',
				'meta'   => array( 'target' => '_blank' ),
			) );
			$wp_admin_bar->add_menu( array(
				'parent' => 'slplus-kwresearch',
				'id'     => 'slplus-googleinsights',
				'title'  => __( 'Google Insights', 'seolat-tool-plus' ),
				'href'   => 'https://www.google.com/trends/',
				'meta'   => array( 'target' => '_blank' ),
			) );

			if ( ! is_admin() ) {
				$sl_canonical = new SL_Canonical();
				$url = $sl_canonical->get_canonical_url();

				if ( is_string( $url ) ) {
					$wp_admin_bar->add_menu( array(
						'parent' => 'slplus-menu',
						'id'     => 'slplus-analysis',
						'title'  => __( 'Page Analysis Tools', 'seolat-tool-plus' ),
						'#',
					) );
					$wp_admin_bar->add_menu( array(
						'parent' => 'slplus-analysis',
						'id'     => 'slplus-inlinks-ose',
						'title'  => __( 'Check OSE DA/PA', 'seolat-tool-plus' ),
						'href'   => '//moz.com/researchtools/ose/links?site=' . urlencode( $url ),
						'meta'   => array( 'target' => '_blank' ),
					) );
					$wp_admin_bar->add_menu( array(
						'parent' => 'slplus-analysis',
						'id'     => 'slplus-kwdensity',
						'title'  => __( 'Check Keyword Density', 'seolat-tool-plus' ),
						'href'   => '//www.zippy.co.uk/keyworddensity/index.php?url=' . urlencode( $url ) . '&keyword=',
						'meta'   => array( 'target' => '_blank' ),
					) );
					$wp_admin_bar->add_menu( array(
						'parent' => 'slplus-analysis',
						'id'     => 'slplus-cache',
						'title'  => __( 'Check Google Cache', 'seolat-tool-plus' ),
						'href'   => '//webcache.googleusercontent.com/search?strip=1&q=cache:' . urlencode( $url ),
						'meta'   => array( 'target' => '_blank' ),
					) );
					$wp_admin_bar->add_menu( array(
						'parent' => 'slplus-analysis',
						'id'     => 'slplus-header',
						'title'  => __( 'Check Headers', 'seolat-tool-plus' ),
						'href'   => '//quixapp.com/headers/?r=' . urlencode( $url ),
						'meta'   => array( 'target' => '_blank' ),
					) );
					$wp_admin_bar->add_menu( array(
						'parent' => 'slplus-analysis',
						'id'     => 'slplus-richsnippets',
						'title'  => __( 'Check Rich Snippets', 'seolat-tool-plus' ),
						'href'   => '//www.google.com/webmasters/tools/richsnippets?q=' . urlencode( $url ),
						'meta'   => array( 'target' => '_blank' ),
					) );
					$wp_admin_bar->add_menu( array(
						'parent' => 'slplus-analysis',
						'id'     => 'slplus-facebookdebug',
						'title'  => __( 'Facebook Debugger', 'seolat-tool-plus' ),
						'href'   => '//developers.facebook.com/tools/debug/og/object?q=' . urlencode( $url ),
						'meta'   => array( 'target' => '_blank' ),
					) );
					$wp_admin_bar->add_menu( array(
						'parent' => 'slplus-analysis',
						'id'     => 'slplus-pinterestvalidator',
						'title'  => __( 'Pinterest Rich Pins Validator', 'seolat-tool-plus' ),
						'href'   => '//developers.pinterest.com/rich_pins/validator/?link=' . urlencode( $url ),
						'meta'   => array( 'target' => '_blank' ),
					) );
					$wp_admin_bar->add_menu( array(
						'parent' => 'slplus-analysis',
						'id'     => 'slplus-htmlvalidation',
						'title'  => __( 'HTML Validator', 'seolat-tool-plus' ),
						'href'   => '//validator.w3.org/check?uri=' . urlencode( $url ),
						'meta'   => array( 'target' => '_blank' ),
					) );
					$wp_admin_bar->add_menu( array(
						'parent' => 'slplus-analysis',
						'id'     => 'slplus-cssvalidation',
						'title'  => __( 'CSS Validator', 'seolat-tool-plus' ),
						'href'   => '//jigsaw.w3.org/css-validator/validator?uri=' . urlencode( $url ),
						'meta'   => array( 'target' => '_blank' ),
					) );
					$wp_admin_bar->add_menu( array(
						'parent' => 'slplus-analysis',
						'id'     => 'slplus-pagespeed',
						'title'  => __( 'Google Page Speed Test', 'seolat-tool-plus' ),
						'href'   => '//developers.google.com/speed/pagespeed/insights/?url=' . urlencode( $url ),
						'meta'   => array( 'target' => '_blank' ),
					) );
					$wp_admin_bar->add_menu( array(
						'parent' => 'slplus-analysis',
						'id'     => 'slplus-modernie',
						'title'  => __( 'Modern IE Site Scan', 'seolat-tool-plus' ),
						'href'   => '//www.modern.ie/en-us/report#' . urlencode( $url ),
						'meta'   => array( 'target' => '_blank' ),
					) );
					$wp_admin_bar->add_menu( array(
						'parent' => 'slplus-analysis',
						'id'     => 'slplus-page-archive',
						'title'  => __( 'Wayback Machine Page Archive', 'seolat-tool-plus' ),
						'href'   => 'https://www.archive.org/web/*/' . urlencode( $url ),
						'meta'   => array( 'target' => '_blank' ),
					) );
				}
			}
}
add_action('admin_bar_menu', 'seo_lat_tool_plus_admin_bar_menu', 95);

// remove jetpack open graph tags
add_filter( 'jetpack_enable_open_graph', '__return_false', 1000 );

?>
