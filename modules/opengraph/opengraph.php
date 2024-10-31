<?php
/**
 * Open Graph+ Module
 * 
 * @since 7.3
 */

if (class_exists('SL_Module')) {

class SL_OpenGraph extends SL_Module {
	
	var $namespaces_declared = false;
	var $jlsuggest_box_post_id = false;
	
	static function get_module_title() { return __('Open Graph+', 'seolat-tool-plus'); }
	static function get_menu_title() { return __('Open Graph+', 'seolat-tool-plus'); }
	
	function get_default_settings() {
		return array(
			  'default_post_og_type' => 'article'
			, 'default_page_og_type' => 'article'
			, 'default_post_twitter_card' => 'summary'
			, 'default_page_twitter_card' => 'summary'
			, 'default_attachment_twitter_card' => 'summary_large_image'
			, 'enable_og_article_author' => true
		);
	}
	
	function init() {
		add_filter('language_attributes', array(&$this, 'html_tag_attrs'), 100000);
		add_action('sl_head', array(&$this, 'head_tag_output'));
		add_filter('sl_get_setting-opengraph-twitter_site_handle', array(&$this, 'sanitize_twitter_handle'));
		add_filter('user_contactmethods', array(&$this, 'add_twitter_field'));
		add_filter('sl_get_setting-opengraph-twitter_creator_handle', array(&$this, 'sanitize_twitter_handle'));
		add_action( 'show_user_profile', array(&$this, 'user_meta_fields'));
		add_action( 'edit_user_profile', array(&$this, 'user_meta_fields'));
		add_action( 'personal_options_update', array(&$this, 'save_user_meta_fields'));
		add_action( 'edit_user_profile_update', array(&$this, 'save_user_meta_fields'));
	}
	
	function html_tag_attrs($attrs) {
		$this->namespaces_declared = true;
		$namespace_urls = $this->get_namespace_urls();
		
		$doctype = $this->get_setting('doctype', '');
		switch ($doctype) {
			case 'xhtml':
				foreach ($namespace_urls as $namespace => $url) {
					$namespace = sl_esc_attr($namespace);
					$url = sl_esc_attr($url);
					$attrs .= " xmlns:$namespace=\"$url\"";
				}
				break;
			case 'html5':
			default:
				$attrs .= ' prefix="';
				$whitespace = '';
				foreach ($namespace_urls as $namespace => $url) {
					$namespace = sl_esc_attr($namespace);
					$url = sl_esc_attr($url);
					$attrs .= "$whitespace$namespace: $url";
					$whitespace = ' ';
				}
				$attrs .= '"';
				break;
		}
		
		return $attrs;
	}
	
	function get_namespace_urls() {
		return array(
			  'og' => 'http://ogp.me/ns#'
			, 'fb' => 'http://ogp.me/ns/fb#'
			, 'article' => 'http://ogp.me/ns/article#'			
		);
	}
	
	function head_tag_output() {
		global $wp_query;
		
		$tags = $twitter_tags = array();
		
		if (is_home()) {

			//Twitter Type
			$twitter_tags['twitter:card'] = 'summary';

			//Type
			$tags['og:type'] = 'website';
			
			//Title
			if (!($tags['og:title'] = $this->get_setting('og_title')))
				$tags['og:title'] = get_bloginfo('name');
			
			//Description
			if (!($tags['og:description'] = $this->get_setting('og_description')))
				$tags['og:description'] = get_bloginfo('description');
			
			//Image
			$tags['og:image'] = $this->get_setting('og_image');

			//URL
			$tags['og:url'] = lat_wp::get_blog_home_url();
			
		}
        elseif (is_singular()) {
			
			$post = $wp_query->get_queried_object();
			
			if (is_object($post)) {
				
				//Type
				if (!($tags['og:type'] = $this->get_postmeta('og_type')))
					$tags['og:type'] = $this->get_setting("default_{$post->post_type}_og_type");
				
				//Additional fields
				switch ($tags['og:type']) {
					case 'article' || 'blog':
						
						$tags['article:published_time'] = get_the_date('Y-m-d');
						$tags['article:modified_time'] = get_the_modified_date('Y-m-d');
						
						//Authorship generally doesn't apply to pages
						if (!is_page() && $this->get_setting('enable_og_article_author', true))
							$tags['article:author'] = get_author_posts_url($post->post_author);
						
						$single_category = (count(get_the_category()) == 1);
						
						$taxonomy_names = lat_wp::get_taxonomy_names();
						foreach ($taxonomy_names as $taxonomy_name) {
							if ($terms = get_the_terms(get_the_ID(), $taxonomy_name)) {
								
								if ($single_category && 'category' == $taxonomy_name)
									$meta_property = 'article:section';
								else
									$meta_property = 'article:tag';
								
								foreach ($terms as $term) {
									$tags[$meta_property][] = $term->name;
								}
							}
						}
						
						break;
				}
				
				//Title
				if (!($tags['og:title'] = $this->get_postmeta('og_title')))
					$tags['og:title'] = $this->get_setting("default_{$post->post_type}_og_title");
				
				//Description
				if (!($tags['og:description'] = $this->get_postmeta('og_description')))
					$tags['og:description'] = $this->get_setting("default_{$post->post_type}_og_description");
				
				//URL
				$tags['og:url'] = get_permalink($post->ID);
				
				//Image
				$tags['og:image'] = $this->jlsuggest_value_to_url($this->get_postmeta('og_image'), true);
				if (!$tags['og:image']) {
					if ('attachment' == $post->post_type) {
						$tags['og:image'] = wp_get_attachment_url();
					} elseif (current_theme_supports('post-thumbnails') && $thumbnail_id = get_post_thumbnail_id($post->ID)) {
						$tags['og:image'] = wp_get_attachment_url($thumbnail_id);
					}
				}
				
				//GOOGLE PLUS
				
				// if (!($gplus_tags['headline'] = $this->get_postmeta('gplus_title')))
				// 	$gplus_tags['headline'] = $this->get_setting("default_{$post->post_type}_gplus_title");
				
				// if (!($gplus_tags['description'] = $this->get_postmeta('gplus_description')))
				// 	$gplus_tags['description'] = $this->get_setting("default_{$post->post_type}_gplus_description");
				
				// if (!($gplus_tags['image'] = $this->get_postmeta('gplus_image_src')))
				// 	$gplus_tags['image'] = $this->get_setting("default_{$post->post_type}_gplus_image_src");
				
				// if (!($gplus_publisher['publisher'] = $this->get_setting("default_{$post->post_type}_gplus_description")))
				// 	$gplus_publisher['publisher'] = $this->get_setting("default_{$post->post_type}_gplus_page_url");
				
				//FB App ID
				if (!($tags['fb:app_id'] = $this->get_postmeta('fb_app_id')))
					$tags['fb:app_id'] = $this->get_setting("default_{$post->post_type}_fb_app_id");
				
				if (!($tags['fb:admins'] = $this->get_postmeta('fb_admins_url')))
					$tags['fb:admins'] = $this->get_setting("default_{$post->post_type}_fb_admins_url");
					
				//Twitter Card Type
				if (!($twitter_tags['twitter:card'] = $this->get_postmeta('twitter_card')))
					$twitter_tags['twitter:card'] = $this->get_setting("default_{$post->post_type}_twitter_card");
	
				if (!($twitter_tags['twitter:site'] = $this->get_postmeta('twitter_site_handle')))
					$twitter_tags['twitter:site'] = $this->get_setting("default_{$post->post_type}_twitter_site_handle");
				
				if (!($twitter_tags['twitter:site:id'] = $this->get_postmeta('twitter_site_id_handle')))
					$twitter_tags['twitter:site:id'] = $this->get_setting("default_{$post->post_type}_twitter_site_id_handle");
				
				if (!($twitter_tags['twitter:creator'] = $this->get_postmeta('twitter_creator_handle')))
					$twitter_tags['twitter:creator'] = $this->get_setting("default_{$post->post_type}_twitter_creator_handle");
				
				if (!($twitter_tags['twitter:creator:id'] = $this->get_postmeta('twitter_creator_id_handle')))
					$twitter_tags['twitter:creator:id'] = $this->get_setting("default_{$post->post_type}_twitter_creator_id_handle");
				
				if (!($twitter_tags['twitter:title'] = $this->get_postmeta('twitter_title_handle')))
					$twitter_tags['twitter:title'] = $this->get_setting("default_{$post->post_type}_twitter_title_handle");
				
				if (!($twitter_tags['twitter:description'] = $this->get_postmeta('twitter_description_handle')))
					$twitter_tags['twitter:description'] = $this->get_setting("default_{$post->post_type}_twitter_description_handle");
				
				if (!($twitter_tags['twitter:image'] = $this->get_postmeta('twitter_image_handle')))
					$twitter_tags['twitter:image'] = $this->get_setting("default_{$post->post_type}_twitter_image_handle");
				
				if (!($twitter_tags['twitter:image:width'] = $this->get_postmeta('twitter_image_width_handle')))
					$twitter_tags['twitter:image:width'] = $this->get_setting("default_{$post->post_type}_twitter_image_width_handle");
				
				if (!($twitter_tags['twitter:image:height'] = $this->get_postmeta('twitter_image_height_handle')))
					$twitter_tags['twitter:image:height'] = $this->get_setting("default_{$post->post_type}_twitter_image_height_handle");
				
				if (!($twitter_tags['twitter:data1'] = $this->get_postmeta('twitter_data1_handle')))
					$twitter_tags['twitter:data1'] = $this->get_setting("default_{$post->post_type}_twitter_data1_handle");
				
				if (!($twitter_tags['twitter:label1'] = $this->get_postmeta('twitter_label1_handle')))
					$twitter_tags['twitter:label1'] = $this->get_setting("default_{$post->post_type}_twitter_label1_handle");
				
				if (!($twitter_tags['twitter:data2'] = $this->get_postmeta('twitter_data2_handle')))
					$twitter_tags['twitter:data2'] = $this->get_setting("default_{$post->post_type}_twitter_data2_handle");
				
				if (!($twitter_tags['twitter:label2'] = $this->get_postmeta('twitter_label2_handle')))
					$twitter_tags['twitter:label2'] = $this->get_setting("default_{$post->post_type}_twitter_label2_handle");
				
				if (!($twitter_tags['twitter:image0'] = $this->get_postmeta('twitter_image0_handle')))
					$twitter_tags['twitter:image0'] = $this->get_setting("default_{$post->post_type}_twitter_image0_handle");
				
				if (!($twitter_tags['twitter:image1'] = $this->get_postmeta('twitter_image1_handle')))
					$twitter_tags['twitter:image1'] = $this->get_setting("default_{$post->post_type}_twitter_image1_handle");
				
				if (!($twitter_tags['twitter:image2'] = $this->get_postmeta('twitter_image2_handle')))
					$twitter_tags['twitter:image2'] = $this->get_setting("default_{$post->post_type}_twitter_image2_handle");
				
				if (!($twitter_tags['twitter:image3'] = $this->get_postmeta('twitter_image3_handle')))
					$twitter_tags['twitter:image3'] = $this->get_setting("default_{$post->post_type}_twitter_image3_handle");
				
				if (!($twitter_tags['twitter:player'] = $this->get_postmeta('twitter_player_handle')))
					$twitter_tags['twitter:player'] = $this->get_setting("default_{$post->post_type}_twitter_player_handle");
				
				if (!($twitter_tags['twitter:player:width'] = $this->get_postmeta('twitter_player_width_handle')))
					$twitter_tags['twitter:player:width'] = $this->get_setting("default_{$post->post_type}_twitter_player_width_handle");
				
				if (!($twitter_tags['twitter:player:height'] = $this->get_postmeta('twitter_player_height_handle')))
					$twitter_tags['twitter:player:height'] = $this->get_setting("default_{$post->post_type}_twitter_player_height_handle");
				
				if (!($twitter_tags['twitter:player:stream'] = $this->get_postmeta('twitter_player_stream_handle')))
					$twitter_tags['twitter:player:stream'] = $this->get_setting("default_{$post->post_type}_twitter_player_stream_handle");
				
				if (!($twitter_tags['twitter:player:stream:content_type'] = $this->get_postmeta('twitter_player_stream_content_type_handle')))
					$twitter_tags['twitter:player:stream:content_type'] = $this->get_setting("default_{$post->post_type}_twitter_player_stream_content_type_handle");
				
				if (!($twitter_tags['twitter:app:name:iphone'] = $this->get_postmeta('twitter_app_name_iphone_handle')))
					$twitter_tags['twitter:app:name:iphone'] = $this->get_setting("default_{$post->post_type}_twitter_app_name_iphone_handle");
				
				if (!($twitter_tags['twitter:app:id:iphone'] = $this->get_postmeta('twitter_app_id_iphone_handle')))
					$twitter_tags['twitter:app:id:iphone'] = $this->get_setting("default_{$post->post_type}_twitter_app_id_iphone_handle");
				
				if (!($twitter_tags['twitter:app:url:iphone'] = $this->get_postmeta('twitter_app_url_iphone_handle')))
					$twitter_tags['twitter:app:url:iphone'] = $this->get_setting("default_{$post->post_type}_twitter_app_url_iphone_handle");
				
				if (!($twitter_tags['twitter:app:name:iphone'] = $this->get_postmeta('twitter_app_name_ipad_handle')))
					$twitter_tags['twitter:app:name:iphone'] = $this->get_setting("default_{$post->post_type}_twitter_app_name_ipad_handle");
				
				if (!($twitter_tags['twitter:app:id:iphone'] = $this->get_postmeta('twitter_app_id_ipad_handle')))
					$twitter_tags['twitter:app:id:iphone'] = $this->get_setting("default_{$post->post_type}_twitter_app_id_ipad_handle");
				
				if (!($twitter_tags['twitter:app:url:iphone'] = $this->get_postmeta('twitter_app_url_ipad_handle')))
					$twitter_tags['twitter:app:url:iphone'] = $this->get_setting("default_{$post->post_type}_twitter_app_url_ipad_handle");
				
				if (!($twitter_tags['twitter:app:name:googleplay'] = $this->get_postmeta('twitter_app_name_googleplay_handle')))
					$twitter_tags['twitter:app:name:googleplay'] = $this->get_setting("default_{$post->post_type}_twitter_app_name_googleplay_handle");
				
				if (!($twitter_tags['twitter:app:id:googleplay'] = $this->get_postmeta('twitter_app_id_googleplay_handle')))
					$twitter_tags['twitter:app:id:googleplay'] = $this->get_setting("default_{$post->post_type}_twitter_app_id_googleplay_handle");
				
				if (!($twitter_tags['twitter:app:url:googleplay'] = $this->get_postmeta('twitter_app_url_googleplay_handle')))
					$twitter_tags['twitter:app:url:googleplay'] = $this->get_setting("default_{$post->post_type}_twitter_app_url_googleplay_handle");
				
				//Author's Twitter Handle
				$handle = get_user_meta($post->post_author, 'twitter', true);
				$handle = $this->sanitize_twitter_handle($handle);
				$twitter_tags['twitter:creator'] = $handle;
			}
		}
        elseif (is_author()) {
			
			$author = $wp_query->get_queried_object();
			
			if (is_object($author)) {
				//Type
				$tags['og:type'] = 'profile';
				
				//Title
				$tags['og:title'] = get_the_author_meta('author_title', $author->ID);
				
				//Description
				$tags['og:description'] = get_the_author_meta('author_desc', $author->ID);
				$author_desc_tags['description'] = get_the_author_meta('author_desc', $author->ID);
				
				//Image
				$tags['og:image'] = false;
				
				//URL
				$tags['og:url'] = get_author_posts_url($author->ID, $author->user_nicename);
				
				//First Name
				$tags['profile:first_name'] = get_the_author_meta('first_name', $author->ID);
				
				//Last Name
				$tags['profile:last_name'] = get_the_author_meta('last_name', $author->ID);
				
				//Username
				$tags['profile:username'] = $author->user_login;
				
				//Twitter Handle
				$handle = get_user_meta($author->ID, 'twitter', true);
				$handle = $this->sanitize_twitter_handle($handle);
				$twitter_tags['twitter:creator'] = $handle;

				//Output meta tags
				$namespace_urls = $this->namespaces_declared ? array() : $this->get_namespace_urls();
				$doctype = $this->get_setting('doctype', '');
				
				switch ($doctype) {
					case 'xhtml':
						$output_formats = array('<meta%3$s name="%1$s" content="%2$s" />' => array_merge($author_desc_tags));
						break;
					case 'html5':
						$output_formats = array('<meta%3$s name="%1$s" content="%2$s">' => array_merge($author_desc_tags));
						break;
					default:
						$output_formats = array(
							'<meta%3$s name="%1$s" content="%2$s" />' => $author_desc_tags
						);
						break;
				}
				
				foreach ($output_formats as $html_format => $format_tags) {
					foreach ($format_tags as $property => $values) {
						foreach ((array)$values as $value) {
							$property = sl_esc_attr($property);
							$value  = sl_esc_attr($value);
							if (strlen(trim($property)) && strlen(trim($value))) {
								
								$namespace_attr = '';
								$namespace = lat_string::upto($property, ':');
								if (!empty($namespace_urls[$namespace])) {
									$a_namespace = sl_esc_attr($namespace);
									$a_namespace_url = sl_esc_attr($namespace_urls[$namespace]);
								
									switch ($doctype) {
										case 'xhtml':
											$namespace_attr = " xmlns:$a_namespace=\"$a_namespace_url\"";
											break;
										case 'html5':
										default:
											$namespace_attr = " prefix=\"$a_namespace: $a_namespace_url\"";
											break;
									}
								}
								
								echo "\t";
								printf($html_format, $property, $value, $namespace_attr);
								echo "\n";
							}
						}
					}
				}
			}
		}
        else
			return;
		
		if (!($tags['og:title'] = $this->get_postmeta('og_title')))
			$tags['og:title'] = $this->get_setting('og_title');
		
		if (!($tags['og:description'] = $this->get_postmeta('og_description')))
			$tags['og:description'] = $this->get_setting('og_description');
		
		if ((!isset($tags['og:image']) || !$tags['og:image']) && $tags['og:image'] !== false)
			$tags['og:image'] = $this->jlsuggest_value_to_url($this->get_setting('og_image'), true);

		if ($tags['og:type'] == 'none')
			$tags['og:type'] = '';
	
		//Books OG Type
		if ($tags['og:type'] == 'books')
			$tags['og:type'] = 'books.book';
		
		if (!($tags['books:isbn'] = $this->get_postmeta('og_books_isbn')))
			$tags['books:isbn'] = $this->get_setting('og_books_isbn');
		
		//Business OG Type
		if ($tags['og:type'] == 'business')
			$tags['og:type'] = 'business.business';
			
		if (!($tags['business:contact_data:street_address'] = $this->get_postmeta('og_business_address')))
			$tags['business:contact_data:street_address'] = $this->get_setting('og_business_address');
		
		if (!($tags['business:contact_data:locality'] = $this->get_postmeta('og_business_locality')))
			$tags['business:contact_data:locality'] = $this->get_setting('og_business_locality');
		
		if (!($tags['business:contact_data:postal_code'] = $this->get_postmeta('og_business_postal_code')))
			$tags['business:contact_data:postal_code'] = $this->get_setting('og_business_postal_code');
		
		if (!($tags['business:contact_data:country_name'] = $this->get_postmeta('og_business_country')))
			$tags['business:contact_data:country_name'] = $this->get_setting('og_business_country');
		
		if (!($tags['place:location:latitude'] = $this->get_postmeta('og_business_latitude')))
			$tags['place:location:latitude'] = $this->get_setting('og_business_latitude');
		
		if (!($tags['place:location:longitude'] = $this->get_postmeta('og_business_longitude')))
			$tags['place:location:longitude'] = $this->get_setting('og_business_longitude');
		
		//Product OG Type
		if (!($tags['product:original_price:amount'] = $this->get_postmeta('og_product_original_price_amount')))
			$tags['product:original_price:amount'] = $this->get_setting('og_product_original_price_amount');
		
		if (!($tags['product:original_price:currency'] = $this->get_postmeta('og_product_original_price_currency')))
			$tags['product:original_price:currency'] = $this->get_setting('og_product_original_price_currency');
		
		if (!($tags['product:pretax_price:amount'] = $this->get_postmeta('og_product_pretax_price_amount')))
			$tags['product:pretax_price:amount'] = $this->get_setting('og_product_pretax_price_amount');
		
		if (!($tags['product:pretax_price:currency'] = $this->get_postmeta('og_product_pretax_price_currency')))
			$tags['product:pretax_price:currency'] = $this->get_setting('og_product_pretax_price_currency');
		
		if (!($tags['product:price:amount'] = $this->get_postmeta('og_product_price_amount')))
			$tags['product:price:amount'] = $this->get_setting('og_product_price_amount');
		
		if (!($tags['product:price:currency'] = $this->get_postmeta('og_product_price_currency')))
			$tags['product:price:currency'] = $this->get_setting('og_product_price_currency');
		
		if (!($tags['product:shipping_cost:amount'] = $this->get_postmeta('og_product_shipping_cost_amount')))
			$tags['product:shipping_cost:amount'] = $this->get_setting('og_product_shipping_cost_amount');
		
		if (!($tags['product:shipping_cost:currency'] = $this->get_postmeta('og_product_shipping_cost_currency')))
			$tags['product:shipping_cost:currency'] = $this->get_setting('og_product_shipping_cost_currency');
		
		if (!($tags['product:weight:value'] = $this->get_postmeta('og_product_weight_value')))
			$tags['product:weight:value'] = $this->get_setting('og_product_weight_value');
		
		if (!($tags['product:weight:units'] = $this->get_postmeta('og_product_weight_units')))
			$tags['product:weight:units'] = $this->get_setting('og_product_weight_units');
		
		if (!($tags['product:shipping_weight:value'] = $this->get_postmeta('og_product_shipping_weight_value')))
			$tags['product:shipping_weight:value'] = $this->get_setting('og_product_shipping_weight_value');
		
		if (!($tags['product:shipping_weight:units'] = $this->get_postmeta('og_product_shipping_weight_units')))
			$tags['product:shipping_weight:units'] = $this->get_setting('og_product_shipping_weight_units');
		
		if (!($tags['product:sale_price:amount'] = $this->get_postmeta('og_product_sale_price_amount')))
			$tags['product:sale_price:amount'] = $this->get_setting('og_product_sale_price_amount');
		
		if (!($tags['product:sale_price:currency'] = $this->get_postmeta('og_product_sale_price_currency')))
			$tags['product:sale_price:currency'] = $this->get_setting('og_product_sale_price_currency');
		
		if (!($tags['product:sale_price_dates:start'] = $this->get_postmeta('og_product_sale_price_dates_start')))
			$tags['product:sale_price_dates:start'] = $this->get_setting('og_product_sale_price_dates_start');
		
		if (!($tags['product:sale_price_dates:end'] = $this->get_postmeta('og_product_sale_price_dates_end')))
			$tags['product:sale_price_dates:end'] = $this->get_setting('og_product_sale_price_dates_end');
		
		//Place OG Type
		if (!($tags['place:location:latitude'] = $this->get_postmeta('og_place_latitude')))
			$tags['place:location:latitude'] = $this->get_setting('og_place_latitude');
		
		if (!($tags['place:location:longitude'] = $this->get_postmeta('og_place_longitude')))
			$tags['place:location:longitude'] = $this->get_setting('og_place_longitude');
		
		//Profile OG Type
		if (!($tags['profile:first_name'] = $this->get_postmeta('og_profile_first_name')))
			$tags['profile:first_name'] = $this->get_setting('og_profile_first_name');
		
		if (!($tags['profile:last_name'] = $this->get_postmeta('og_profile_last_name')))
			$tags['profile:last_name'] = $this->get_setting('og_profile_last_name');
		
		if (!($tags['profile:gender'] = $this->get_postmeta('og_profile_gender')))
			$tags['profile:gender'] = $this->get_setting('og_profile_gender');
		
		if (!($tags['profile:username'] = $this->get_postmeta('og_profile_username')))
			$tags['profile:username'] = $this->get_setting('og_profile_username');
		
		//Video Episode OG Type
		if ($tags['og:type'] == 'videoepisode')
			$tags['og:type'] = 'video.episode';

		if (!($tags['og:video:url'] = $this->get_postmeta('og_video_episode_url')))
			$tags['og:video:url'] = $this->get_setting('og_video_episode_url');
		
		if (!($tags['og:video:secure_url'] = $this->get_postmeta('og_video_episode_secure_url')))
			$tags['og:video:secure_url'] = $this->get_setting('og_video_episode_secure_url');
		
		if (!($tags['og:video:type'] = $this->get_postmeta('og_video_episode_type')))
			$tags['og:video:type'] = $this->get_setting('og_video_episode_type');
		
		if (!($tags['og:video:width'] = $this->get_postmeta('og_video_episode_width')))
			$tags['og:video:width'] = $this->get_setting('og_video_episode_width');
		
		if (!($tags['og:video:height'] = $this->get_postmeta('og_video_episode_height')))
			$tags['og:video:height'] = $this->get_setting('og_video_episode_height');
		
		if (!($tags['video:actor:id'] = $this->get_postmeta('og_video_episode_actor_id')))
			$tags['video:actor:id'] = $this->get_setting('og_video_episode_actor_id');
		
		if (!($tags['video:actor:role'] = $this->get_postmeta('og_video_episode_actor_role')))
			$tags['video:actor:role'] = $this->get_setting('og_video_episode_actor_role');
		
		if (!($tags['video:director'] = $this->get_postmeta('og_video_episode_director')))
			$tags['video:director'] = $this->get_setting('og_video_episode_director');
		
		if (!($tags['video:duration'] = $this->get_postmeta('og_video_episode_duration')))
			$tags['video:duration'] = $this->get_setting('og_video_episode_duration');
		
		if (!($tags['video:release_date'] = $this->get_postmeta('og_video_episode_release_date')))
			$tags['video:release_date'] = $this->get_setting('og_video_episode_release_date');
		
		if (!($tags['video:tag'] = $this->get_postmeta('og_video_episode_tags')))
			$tags['video:tag'] = $this->get_setting('og_video_episode_tag');
		
		if (!($tags['video:writer'] = $this->get_postmeta('og_video_episode_writer')))
			$tags['video:writer'] = $this->get_setting('og_video_episode_writer');
		
		//Site Name
		if (!($tags['og:site_name'] = $this->get_setting('og_site_name')))
			$tags['og:site_name'] = get_bloginfo('name');
		
		//FB App ID
		if (!($tags['fb:app_id'] = $this->get_postmeta('fb_app_id')))
			$tags['fb:app_id'] = $this->get_setting('fb_app_id');
			
		if (!($tags['fb:admins'] = $this->get_postmeta('fb_admins_url')))
			$tags['fb:admins'] = $this->get_setting('fb_admins_url');
		
		//GOOGLE PLUS
		// if (!($gplus_tags['headline'] = $this->get_postmeta('gplus_title')))
		// 	$gplus_tags['headline'] = $this->get_setting('gplus_title');
		
		// if (!($gplus_tags['description'] = $this->get_postmeta('gplus_description')))
		// 	$gplus_tags['description'] = $this->get_setting('gplus_description');	
		
		// if (!($gplus_tags['image'] = $this->get_postmeta('gplus_image_src')))
		// 	$gplus_tags['image'] = $this->get_setting('gplus_image_src');
		
		//GOOGLE PLUS AUTHORSHIP		
		// if (!($gplus_publisher['publisher'] = $this->get_postmeta('gplus_page_url')))
		// 	$gplus_publisher['publisher'] = $this->get_setting('gplus_page_url');
		
		
		//PINTEREST VERIFICATION
		if (!($pinterest_meta_tag['p:domain_verify'] = $this->get_postmeta('pinterest_verification')))
			$pinterest_meta_tag['p:domain_verify'] = $this->get_setting('pinterest_verification');
		
		//Twitter Site Handle				
		if (!($twitter_tags['twitter:site'] = $this->get_postmeta('twitter_site_handle')))
			$twitter_tags['twitter:site'] = $this->get_setting('twitter_site_handle');
		
		if (!($twitter_tags['twitter:site:id'] = $this->get_postmeta('twitter_site_id_handle')))
			$twitter_tags['twitter:site:id'] = $this->get_setting('twitter_site_id_handle');
		
		if (!($twitter_tags['twitter:creator'] = $this->get_postmeta('twitter_creator_handle')))
			$twitter_tags['twitter:creator'] = $this->get_setting('twitter_creator_handle');
		
		if (!($twitter_tags['twitter:creator:id'] = $this->get_postmeta('twitter_creator_id_handle')))
			$twitter_tags['twitter:creator:id'] = $this->get_setting('twitter_creator_id_handle');
		
		if (!($twitter_tags['twitter:title'] = $this->get_postmeta('twitter_title_handle')))
			$twitter_tags['twitter:title'] = $this->get_setting('twitter_title_handle');
		
		if (!($twitter_tags['twitter:description'] = $this->get_postmeta('twitter_description_handle')))
			$twitter_tags['twitter:description'] = $this->get_setting('twitter_description_handle');
		
		if (!($twitter_tags['twitter:image'] = $this->get_postmeta('twitter_image_handle')))
			$twitter_tags['twitter:image'] = $this->get_setting('twitter_image_handle');
		
		if (!($twitter_tags['twitter:image:width'] = $this->get_postmeta('twitter_image_width_handle')))
			$twitter_tags['twitter:image:width'] = $this->get_setting('twitter_image_width_handle');
		
		if (!($twitter_tags['twitter:image:height'] = $this->get_postmeta('twitter_image_height_handle')))
			$twitter_tags['twitter:image:height'] = $this->get_setting('twitter_image_height_handle');
		
		if (!($twitter_tags['twitter:data1'] = $this->get_postmeta('twitter_data1_handle')))
			$twitter_tags['twitter:data1'] = $this->get_setting('twitter_data1_handle');
		
		if (!($twitter_tags['twitter:label1'] = $this->get_postmeta('twitter_label1_handle')))
			$twitter_tags['twitter:label1'] = $this->get_setting('twitter_label1_handle');
		
		if (!($twitter_tags['twitter:data2'] = $this->get_postmeta('twitter_data2_handle')))
			$twitter_tags['twitter:data2'] = $this->get_setting('twitter_data2_handle');
		
		if (!($twitter_tags['twitter:label2'] = $this->get_postmeta('twitter_label2_handle')))
			$twitter_tags['twitter:label2'] = $this->get_setting('twitter_label2_handle');
		
		if (!($twitter_tags['twitter:image0'] = $this->get_postmeta('twitter_image0_handle')))
			$twitter_tags['twitter:image0'] = $this->get_setting('twitter_image0_handle');
		
		if (!($twitter_tags['twitter:image1'] = $this->get_postmeta('twitter_image1_handle')))
			$twitter_tags['twitter:image1'] = $this->get_setting('twitter_image1_handle');
		
		if (!($twitter_tags['twitter:image2'] = $this->get_postmeta('twitter_image2_handle')))
			$twitter_tags['twitter:image2'] = $this->get_setting('twitter_image2_handle');
		
		if (!($twitter_tags['twitter:image3'] = $this->get_postmeta('twitter_image3_handle')))
			$twitter_tags['twitter:image3'] = $this->get_setting('twitter_image3_handle');
		
		if (!($twitter_tags['twitter:player'] = $this->get_postmeta('twitter_player_handle')))
			$twitter_tags['twitter:player'] = $this->get_setting('twitter_player_handle');
		
		if (!($twitter_tags['twitter:player:width'] = $this->get_postmeta('twitter_player_width_handle')))
			$twitter_tags['twitter:player:width'] = $this->get_setting('twitter_player_width_handle');
		
		if (!($twitter_tags['twitter:player:height'] = $this->get_postmeta('twitter_player_height_handle')))
			$twitter_tags['twitter:player:height'] = $this->get_setting('twitter_player_height_handle');
		
		if (!($twitter_tags['twitter:player:stream'] = $this->get_postmeta('twitter_player_stream_handle')))
			$twitter_tags['twitter:player:stream'] = $this->get_setting('twitter_player_stream_handle');
		
		if (!($twitter_tags['twitter:player:stream:content_type'] = $this->get_postmeta('twitter_player_stream_content_type_handle')))
			$twitter_tags['twitter:player:stream:content_type'] = $this->get_setting('twitter_player_stream_content_type_handle');
		
		if (!($twitter_tags['twitter:app:name:iphone'] = $this->get_postmeta('twitter_app_name_iphone_handle')))
			$twitter_tags['twitter:app:name:iphone'] = $this->get_setting('twitter_app_name_iphone_handle');
		
		if (!($twitter_tags['twitter:app:id:iphone'] = $this->get_postmeta('twitter_app_id_iphone_handle')))
			$twitter_tags['twitter:app:id:iphone'] = $this->get_setting('twitter_app_id_iphone_handle');
		
		if (!($twitter_tags['twitter:app:url:iphone'] = $this->get_postmeta('twitter_app_url_iphone_handle')))
			$twitter_tags['twitter:app:url:iphone'] = $this->get_setting('twitter_app_url_iphone_handle');
		
		if (!($twitter_tags['twitter:app:name:iphone'] = $this->get_postmeta('twitter_app_name_ipad_handle')))
			$twitter_tags['twitter:app:name:iphone'] = $this->get_setting('twitter_app_name_ipad_handle');
		
		if (!($twitter_tags['twitter:app:id:iphone'] = $this->get_postmeta('twitter_app_id_ipad_handle')))
			$twitter_tags['twitter:app:id:iphone'] = $this->get_setting('twitter_app_id_ipad_handle');
		
		if (!($twitter_tags['twitter:app:url:iphone'] = $this->get_postmeta('twitter_app_url_ipad_handle')))
			$twitter_tags['twitter:app:url:iphone'] = $this->get_setting('twitter_app_url_ipad_handle');
		
		if (!($twitter_tags['twitter:app:name:googleplay'] = $this->get_postmeta('twitter_app_name_googleplay_handle')))
			$twitter_tags['twitter:app:name:googleplay'] = $this->get_setting('twitter_app_name_googleplay_handle');
		
		if (!($twitter_tags['twitter:app:id:googleplay'] = $this->get_postmeta('twitter_app_id_googleplay_handle')))
			$twitter_tags['twitter:app:id:googleplay'] = $this->get_setting('twitter_app_id_googleplay_handle');
		
		if (!($twitter_tags['twitter:app:url:googleplay'] = $this->get_postmeta('twitter_app_url_googleplay_handle')))
			$twitter_tags['twitter:app:url:googleplay'] = $this->get_setting('twitter_app_url_googleplay_handle');
		
		//Output meta tags
		$namespace_urls = $this->namespaces_declared ? array() : $this->get_namespace_urls();
		$doctype = $this->get_setting('doctype', '');
		
		switch ($doctype) {
			case 'xhtml':
				$output_formats = array('<meta%3$s name="%1$s" content="%2$s" />' => array_merge($tags, 
				// $gplus_tags, 
				$pinterest_meta_tag, $twitter_tags, $gplus_publisher));
				break;
			case 'html5':
				$output_formats = array('<meta%3$s property="%1$s" content="%2$s">' => array_merge($tags, 
				// $gplus_tags, 
				$pinterest_meta_tag, $twitter_tags, $gplus_publisher));
				break;
			default:
				$output_formats = array(
					  '<meta%3$s property="%1$s" content="%2$s" />' => $tags
					// , '<meta%3$s itemprop="%1$s" content="%2$s" />' => $gplus_tags
					, '<meta%3$s name="%1$s" content="%2$s" />' => $pinterest_meta_tag
					, '<meta%3$s name="%1$s" content="%2$s" />' => $twitter_tags
					// , '<link%3$s rel="%1$s" href="%2$s" />' => $gplus_publisher

				);
				break;
		}
		
		foreach ($output_formats as $html_format => $format_tags) {
			foreach ($format_tags as $property => $values) {
				foreach ((array)$values as $value) {
					$property = sl_esc_attr($property);
					$value  = sl_esc_attr($value);
					if (strlen(trim($property)) && strlen(trim($value))) {
						
						$namespace_attr = '';
						$namespace = lat_string::upto($property, ':');
						if (!empty($namespace_urls[$namespace])) {
							$a_namespace = sl_esc_attr($namespace);
							$a_namespace_url = sl_esc_attr($namespace_urls[$namespace]);
						
							switch ($doctype) {
								case 'xhtml':
									$namespace_attr = " xmlns:$a_namespace=\"$a_namespace_url\"";
									break;
								case 'html5':
								default:
									$namespace_attr = " prefix=\"$a_namespace: $a_namespace_url\"";
									break;
							}
						}
						
						echo "\t";
						printf($html_format, $property, $value, $namespace_attr);
						echo "\n";
					}
				}
			}
		}

		//PAGINATION PREVIOUS URL TAG
		if (!($previous_url['prev'] = $this->get_postmeta('previous_url_tag')))
			$previous_url['prev'] = $this->get_setting('previous_url_tag');
		
		//Output meta tags
		$namespace_urls = $this->namespaces_declared ? array() : $this->get_namespace_urls();
		$doctype = $this->get_setting('doctype', '');		
		
		switch ($doctype) {
			case 'xhtml':
				$output_formats = array('<meta%3$s name="%1$s" content="%2$s" />' => array_merge($previous_url));
				break;
			case 'html5':
				$output_formats = array('<meta%3$s property="%1$s" content="%2$s">' => array_merge($previous_url));
				break;
			default:
				$output_formats = array(
					  '<link%3$s rel="%1$s" href="%2$s" />' => $previous_url
				);
				break;
		}
		
		foreach ($output_formats as $html_format => $format_tags) {
			foreach ($format_tags as $property => $values) {
				foreach ((array)$values as $value) {
					$property = sl_esc_attr($property);
					$value  = sl_esc_attr($value);
					if (strlen(trim($property)) && strlen(trim($value))) {
						
						$namespace_attr = '';
						$namespace = lat_string::upto($property, ':');
						if (!empty($namespace_urls[$namespace])) {
							$a_namespace = sl_esc_attr($namespace);
							$a_namespace_url = sl_esc_attr($namespace_urls[$namespace]);
						
							switch ($doctype) {
								case 'xhtml':
									$namespace_attr = " xmlns:$a_namespace=\"$a_namespace_url\"";
									break;
								case 'html5':
								default:
									$namespace_attr = " prefix=\"$a_namespace: $a_namespace_url\"";
									break;
							}
						}
						
						echo "\t";
						printf($html_format, $property, $value, $namespace_attr);
						echo "\n";
					}
				}
			}
		}

		//PAGINATION NEXT URL TAG
		if (!($next_url['next'] = $this->get_postmeta('next_url_tag')))
			$next_url['next'] = $this->get_setting('next_url_tag');
		
		//Output meta tags
		$namespace_urls = $this->namespaces_declared ? array() : $this->get_namespace_urls();
		$doctype = $this->get_setting('doctype', '');		
		
		switch ($doctype) {
			case 'xhtml':
				$output_formats = array('<meta%3$s name="%1$s" content="%2$s" />' => array_merge($next_url));
				break;
			case 'html5':
				$output_formats = array('<meta%3$s property="%1$s" content="%2$s">' => array_merge($next_url));
				break;
			default:
				$output_formats = array(
					  '<link%3$s rel="%1$s" href="%2$s" />' => $next_url
				);
				break;
		}
		
		foreach ($output_formats as $html_format => $format_tags) {
			foreach ($format_tags as $property => $values) {
				foreach ((array)$values as $value) {
					$property = sl_esc_attr($property);
					$value  = sl_esc_attr($value);
					if (strlen(trim($property)) && strlen(trim($value))) {
						
						$namespace_attr = '';
						$namespace = lat_string::upto($property, ':');
						if (!empty($namespace_urls[$namespace])) {
							$a_namespace = sl_esc_attr($namespace);
							$a_namespace_url = sl_esc_attr($namespace_urls[$namespace]);
						
							switch ($doctype) {
								case 'xhtml':
									$namespace_attr = " xmlns:$a_namespace=\"$a_namespace_url\"";
									break;
								case 'html5':
								default:
									$namespace_attr = " prefix=\"$a_namespace: $a_namespace_url\"";
									break;
							}
						}
						
						echo "\t";
						printf($html_format, $property, $value, $namespace_attr);
						echo "\n";
					}
				}
			}
		}
	}
	
	function admin_page_init() {
		$this->jlsuggest_init();
	}
	
	function editor_init() {
		$this->jlsuggest_init();
	}
	
	function get_admin_page_tabs() {
		
		$postmeta_edit_tabs = $this->get_postmeta_edit_tabs(array(
			  array(
				  'type' => 'dropdown'
				, 'options' => array_merge(array('' => __('Use default', 'seolat-tool-plus')), $this->get_type_options())
				, 'name' => 'og_type'
				, 'label' => __('Open Graph Type', 'seolat-tool-plus')
				)
			, array(
				  'type' => 'textbox'
				, 'name' => 'og_title'
				, 'label' => __('Open Graph Title', 'seolat-tool-plus')
				)
			, array(
				  'type' => 'textarea'
				, 'name' => 'og_description'
				, 'label' => __('Open Graph Description', 'seolat-tool-plus')
				)
			, array(
				  'type' => 'jlsuggest'
				, 'name' => 'og_image'
				, 'label' => __('Open Graph Image', 'seolat-tool-plus')
				, 'options' => array(
					'params' => 'types=posttype_attachment&post_mime_type=image/*'
				))
			));
		
		//Remove the Image boxes from the Media tab
		//(it's obvious what the og:image of an attachment should be...)
		unset($postmeta_edit_tabs['attachment']['callback'][5][3]);
		
		return array_merge(
			  array(
				  array('title' => __('<div class="fb-icons">Facebook</div>', 'seolat-tool-plus'), 'id' => 'sl-facebook-settings', 'callback' => 'facebook_tab')
				, array('title' => __('<div class="twitter-icons">Twitter</div>', 'seolat-tool-plus'), 'id' => 'sl-twitter-settings', 'callback' => 'twitter_tab')
				// , array('title' => __('<div class="google-plus-icons">Google+</div>', 'seolat-tool-plus'), 'id' => 'sl-googleplus-settings', 'callback' => 'googleplus_tab')
				, array('title' => __('<div class="pinterest-icons">Pinterest</div>', 'seolat-tool-plus'), 'id' => 'sl-pinterest-settings', 'callback' => 'pinterest_tab')
				)
			, $postmeta_edit_tabs
		);
	}
	
	function facebook_tab() {
		$this->admin_form_table_start();			
		echo "<p class='admin_subtitle'>Enter Homepage Default Values</p>";
		$this->textbox('og_title', __('Open Graph Title:', 'seolat-tool-plus'), false, false, array('help_text' => 'Enter Default Open Graph Title.', 'counter_text' => 'You&#8217;ve Entered %s Characters. It’s Best to Stay Between 60 and 90 Characters.'));
		$this->textarea('og_description', __('Open Graph Description:', 'seolat-tool-plus'), false, false, array('help_text' => 'Enter Default Open Graph Description.', 'counter_text' => 'You&#8217;ve Entered %s Characters. Keep Description to 200 Characters Max.'));		
		$this->medialib_box('og_image', __('<a class="tooltips">Open Graph Image:<span>Use image at least 1200 X 630 for best display on high resolution devices. Minimum recommended image is 600 x 315 pixels.</span></a>', 'seolat-tool-plus'), 'types=posttype_attachment&post_mime_type=image/*');
		$posttypes = get_post_types(array('public' => true), 'objects');
		echo "<br><p class='admin_subtitle'>Enter Default Open Graph Type</p>";
		$this->admin_wftable_start(array(
			  'posttype' => __('Post Type', 'seolat-tool-plus')
			, 'og' => __('Open Graph Type', 'seolat-tool-plus')
		));
		foreach ($posttypes as $posttype) {
			echo "<tr valign='middle'>\n";
			echo "\t<th class='sl-opengraph-posttype' scope='row'>" . esc_html($posttype->labels->name) . "</th>\n";
			echo "\t<td class='sl-opengraph-og'>";
			$this->dropdown("default_{$posttype->name}_og_type", $this->get_type_options(), false, '%s', array('in_table' => false));
			echo "</td>\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		$this->admin_wftable_end();
		echo "<br><p class='admin_subtitle'>Enter Facebook Default Values</p>";
		$this->textbox('fb_app_id', __('Facebook App ID:', 'seolat-tool-plus'), false, false, array('help_text' => 'Enter Facebook App ID associated with website.'));
		$this->textbox('fb_admins_url', __('Facebook Admins:', 'seolat-tool-plus'), false, false, array('help_text' => 'Enter Facebook admin name/number (located under Facebook General Account Settings > Username). If more than one admin exists, enter all separated by commas.'));
		echo "<br><p class='admin_subtitle'>Select Global Markup Validation Type</p>";
		$this->radiobuttons('doctype', array(
			  '' => __('Use the non-validating code prescribed by Open Graph and Twitter', 'seolat-tool-plus')
			, 'xhtml' => __('Alter the code to validate as XHTML', 'seolat-tool-plus')
			, 'html5' => __('Alter the code to validate as HTML5', 'seolat-tool-plus')
		), __('HTML Validation', 'seolat-tool-plus'));
		$this->checkbox('enable_og_article_author', __('Include author data for posts', 'seolat-tool-plus'), __('Open Graph Data', 'seolat-tool-plus'));
		$this->admin_form_table_end();
	}
	
	function twitter_tab() {
		$this->admin_form_table_start();	
		echo "<p class='admin_subtitle'>Enter Default Twitter Card Types.</p><p>Learn More About <a href='https://dev.twitter.com/cards/types' target='_blank'>Twitter Card Types.</a></p><br>";
		$posttypes = get_post_types(array('public' => true), 'objects');
		$this->admin_wftable_start(array(
			  'posttype' => __('Post Type', 'seolat-tool-plus')
			, 'twitter' => __('Twitter Card Type', 'seolat-tool-plus')
		));
		foreach ($posttypes as $posttype) {
			echo "<tr valign='middle'>\n";
			echo "\t<th class='sl-opengraph-posttype' scope='row'>" . esc_html($posttype->labels->name) . "</th>\n";
			echo "</td>\n";
			echo "\t<td class='sl-opengraph-twitter'>";
			$this->dropdown("default_{$posttype->name}_twitter_card", $this->get_twitter_type_options(), false, '%s', array('in_table' => false));
			echo "</td>\n";
			echo "</tr>\n";
		}
		$this->admin_wftable_end();
		echo "<br><p class='admin_subtitle'>Enter Information below for Summary Card or Summary Card with Large Image.<span style='font-weight:normal'></span></p>";
		$this->textbox('twitter_site_handle', __('Twitter Site:', 'seolat-tool-plus'), false, false, array('help_text' => 'The Twitter @username the card should be attributed to.', 'callout' => ''));
		$this->textbox('twitter_site_id_handle', __('Twitter Site ID:', 'seolat-tool-plus'), false, false, array('help_text' => 'Same as twitter:site, but the user&#39;s Twitter ID. *Either twitter:site or twitter:site:id is required.'));
		$this->textbox('twitter_creator_handle', __('Twitter Creator:', 'seolat-tool-plus'), false, false, array('help_text' => '@username of content creator'));
		$this->textbox('twitter_creator_id_handle', __('Twitter Creator ID:', 'seolat-tool-plus'), false, false, array('help_text' => 'Twitter user ID of content creator. Used with summary, summary_large_image, photo, gallery, product cards'));
		$this->textbox('twitter_title_handle', __('Twitter Title:', 'seolat-tool-plus'), false, false, array('help_text' => 'Title should be concise and will be truncated at 70 characters.'));
		$this->textarea('twitter_description_handle', __('Twitter Description:', 'seolat-tool-plus'), false, false, array('help_text' => 'Description of content (maximum 200 characters)'));
		$this->medialib_box('twitter_image_handle', __('<a class="tooltips">Twitter Image:<span>Use button to upload image. It must be less than 1MB in size.</span></a>', 'seolat-tool-plus'), 'types=posttype_attachment&post_mime_type=image/*', false, false, array('help_text' => 'Use Button To Upload Image. It Must Be Less Than 1MB In Size.'));
		echo "<br><p class='admin_subtitle'>Player Card: <span style='font-weight:normal'>A Card to provide video/audio/media.</span></p>";
		$this->textbox('twitter_player_handle', __('Twitter Player:', 'seolat-tool-plus'), false, false, array('help_text' => 'HTTPS URL of player iframe'));
		$this->textbox('twitter_player_width_handle', __('Player Width:', 'seolat-tool-plus'), false, false, array('help_text' => 'Width of iframe in pixels'));
		$this->textbox('twitter_player_height_handle', __('Player Height:', 'seolat-tool-plus'), false, false, array('help_text' => 'Height of iframe in pixels'));
		$this->textbox('twitter_player_stream_handle', __('Player Stream:', 'seolat-tool-plus'), false, false, array('help_text' => 'URL to raw video or audio stream'));
		$this->textbox('twitter_player_stream_content_type_handle', __('Player Stream Content Type:', 'seolat-tool-plus'), false, false, array('help_text' => 'The MIME type/subtype combination that describes the content contained in twitter:player:stream. Takes the form specified in RFC 6381. Currently supported content_type values are those defined in RFC 4337 (MIME Type Registration for MP4)'));
		echo "<br><p class='admin_subtitle'>App Card: <span style='font-weight:normal'>A Card to detail a mobile app with direct download.</span></p>";
		$this->textbox('twitter_app_name_iphone_handle', __('Twitter App Name iPhone:', 'seolat-tool-plus'), false, false, array('help_text' => 'Name of your iPhone app'));
		$this->textbox('twitter_app_id_iphone_handle', __('Twitter App ID iPhone:', 'seolat-tool-plus'), false, false, array('help_text' => 'Your app ID in the iTunes App Store (Note: NOT your bundle ID)'));
		$this->textbox('twitter_app_url_iphone_handle', __('Twitter App URL iPhone:', 'seolat-tool-plus'), false, false, array('help_text' => 'Your app&#8217;s custom URL scheme (you must include "://" after your scheme name)'));
		$this->textbox('twitter_app_name_ipad_handle', __('Twitter App Name iPad:', 'seolat-tool-plus'), false, false, array('help_text' => 'Name of your iPad optimized app'));
		$this->textbox('twitter_app_id_ipad_handle', __('Twitter App ID iPad:', 'seolat-tool-plus'), false, false, array('help_text' => 'Your app ID in the iTunes App Store'));
		$this->textbox('twitter_app_url_ipad_handle', __('Twitter App URL iPad:', 'seolat-tool-plus'), false, false, array('help_text' => 'Your app&#8217;s custom URL scheme'));
		$this->textbox('twitter_app_name_googleplay_handle', __('Twitter App Name Googleplay:', 'seolat-tool-plus'), false, false, array('help_text' => 'Name of your Android app'));
		$this->textbox('twitter_app_id_googleplay_handle', __('Twitter App ID Googleplay:', 'seolat-tool-plus'), false, false, array('help_text' => 'Your app ID in the Google Play Store'));
		$this->textbox('twitter_app_url_googleplay_handle', __('Twitter App URL Googleplay:', 'seolat-tool-plus'), false, false, array('help_text' => 'Your app#8217;s custom URL scheme'));
		$this->admin_form_table_end();
	}
	
	function googleplus_tab() {
		$this->admin_form_table_start();
		echo "<p class='admin_subtitle'>Google Plus Itemprop Settings</p>";
		$this->textbox('gplus_title', __('Google Plus Title:', 'seolat-tool-plus'), false, false, array('help_text' => 'Enter Default Open Graph Title.', 'counter_text' => 'You&#8217;ve Entered %s Characters. Title Should Not Exceed 60 Characters.'));
		$this->textarea('gplus_description', __('Google Plus Description:', 'seolat-tool-plus'), false, false, array('help_text' => 'Enter Default Open Graph Description.', 'counter_text' => 'You&#8217;ve Entered %s Characters. Description Should Not Exceed 156 Characters.'));
		$this->medialib_box('gplus_image_src', __('<a class="tooltips">Google Plus Image:<span>The height must be at least 120px, and if the width is less than 100px, then the aspect ratio must be no greater than 3.0.</span></a>', 'seolat-tool-plus'), 'types=posttype_attachment&post_mime_type=image/*');
		echo "<br><p class='admin_subtitle'>Google Authorship Markup Settings</p>";
		$this->textbox('gplus_page_url', __('Google Plus Publisher:', 'seolat-tool-plus'), false, false, array('help_text' => 'Enter your Google Plus page URL here if you have set up a "Google+ Page" for your organization or product, and the plugin will put a rel="publisher" link to the specified Google+ page on your home page.'));
		$this->admin_form_table_end();
	}
	
	function pinterest_tab() {
		$this->admin_form_table_start();
		echo "<p>In Addition To Facebook, Open Graph Meta Data Is Also used On Pinterest, LinkedIn and Myspace. Add Your <a href='https://help.pinterest.com/en/articles/verify-your-website' target='_blank'>Pinterest Verification</a> Code Here If You Want Your Open Graph Preferences To Include This Social Network.</p>";
		$this->textbox('pinterest_verification', __('Pinterest Verification:', 'seolat-tool-plus'));
		$this->admin_form_table_end();
	}
	
	function postmeta_fields($fields, $screen) {
		$id = "_sl_og_title";
		$value = sl_esc_attr($this->get_postmeta('og_title'));
		$fields['facebook'][50]['og_title'] =
			"<div class='form-group sl textbox'>\n<label class='col-sm-4 col-md-4 control-label' for='$id'>".__('Open Graph Title: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>Enter preferred title, description and image for Facebook / Open Graph. Additional social properties such as Linkedin, Pinterest & MySpace also use Open Graph information. To customize preferred Google+ or Twitter Card data, use tabs above.</span></a>', 'seolat-tool-plus')."</label>\n<div class='col-sm-4 col-md-4'><input name='$id' id='$id' type='text' value='$value' class='form-control input-sm regular-text' tabindex='2'"
			. " onkeyup=\"javascript:document.getElementById('sl_og_title_charcount').innerHTML = document.getElementById('_sl_og_title').value.length\" />"
			. "</div>\n<div class='col-sm-4 col-md-4 help-text'>".sprintf(__('You&#8217;ve Entered %s Characters. It&#8217;s Best to Stay Between 60 and 90 Characters.', 'seolat-tool-plus'), "<strong id='sl_og_title_charcount'>".strlen($value)."</strong>")
			. "</div>\n</div>\n";

		$id = '_sl_og_description';
		$value = sl_esc_attr($this->get_postmeta('og_description'));
		$fields['facebook'][51]['og_description'] =
			"<div class='form-group sl textarea'>\n<label class='col-sm-4 col-md-4 control-label' for='$id'>".__('Open Graph Description: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>Facebook can display up to 300 characters, but we suggest a maximum of 200 characters.</span></a>', 'seolat-tool-plus')."</label>\n<div class='col-sm-4 col-md-4'>"
			. "<textarea name='$id' id='$id' class='form-control regular-text' cols='60' rows='3' tabindex='2'"
			. " onkeyup=\"javascript:document.getElementById('sl_og_description_charcount').innerHTML = document.getElementById('_sl_og_description').value.length\">$value</textarea>"
			. "</div>\n<div class='col-sm-4 col-md-4 help-text'>".sprintf(__('You&#8217;ve Entered %s Characters. Keep Description to 200 Characters Max.', 'seolat-tool-plus'), "<strong id='sl_og_description_charcount'>".strlen($value)."</strong>")
			. "</div>\n</div>\n";

		$fields['facebook'][52]['og_image'] = $this->get_postmeta_medialib_box('og_image', __('Open Graph Image: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>Use image at least 1200 X 630 for best display on high resolution devices. Minimum recommended image is 600 x 315 pixels.</span></a>', 'seolat-tool-plus'));
		
		$fields['facebook'][53]['og_type'] = $this->get_postmeta_dropdown('og_type', array(
			'' => __('Use default', 'seolat-tool-plus')
			, 'none' => __('None', 'seolat-tool-plus')
			, 'blog' => __('Blog', 'seolat-tool-plus')
			, 'books' => __('Books', 'seolat-tool-plus')
			, 'business' => __('Business', 'seolat-tool-plus')
			, 'product' => __('Product', 'seolat-tool-plus')
			, 'place' => __('Place', 'seolat-tool-plus')
			, 'website' => __('Website', 'seolat-tool-plus')
			,__('Internet', 'seolat-tool-plus') => array(
				  'article' => __('Article', 'seolat-tool-plus')
				, 'profile' => __('Profile', 'seolat-tool-plus')
			),__('Music', 'seolat-tool-plus') => array(
				  'music.album' => __('Album', 'seolat-tool-plus')
				, 'music.playlist' => __('Playlist', 'seolat-tool-plus')
				, 'music.radio_station' => __('Radio Station', 'seolat-tool-plus')
				, 'music.song' => __('Song', 'seolat-tool-plus')
			),__('Videos', 'seolat-tool-plus') => array(
				  'videoepisode' => __('TV Episode', 'seolat-tool-plus')
				, 'video.movie' => __('Movie', 'seolat-tool-plus')
				, 'video.other' => __('Video', 'seolat-tool-plus')
				, 'video.tv_show' => __('TV Show', 'seolat-tool-plus')
			)
		), __('Open Graph Content Type:', 'seolat-tool-plus'), array('help_text' => ''));
		
		$fields['facebook'][54]['og_books_isbn'] =
			$this->get_postmeta_subsection('og_type', 'books',
				$this->get_postmeta_textbox('og_books_isbn', __('ISBN: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The International Standard Book Number (ISBN) for the book.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);
		
		$fields['facebook'][55]['og_business_address|og_business_locality|og_business_postal_code|og_business_country|og_business_latitude|og_business_longitude'] =
			$this->get_postmeta_subsection('og_type', 'business',
				$this->get_postmeta_textbox('og_business_address', __('Street Address: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The number and street of the postal address for this business. For example, 1600 Amphitheatre Pkwy.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_business_locality', __('Business City: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The city (or locality) line of the postal address for this business. For example, Mountain View.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_business_postal_code', __('Postal Code: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The postcode (or ZIP code) of the postal address for this business. For example, 94043.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_business_country', __('Country Name: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The country of the postal address for this business. For example, USA.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_business_latitude', __('Latitude: <a class="tooltips" href="https://maps.google.com/" target="_blank"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The latitude of the business place. For example, 40.75. Click to open maps page in new tab.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_business_longitude', __('Longitude: <a class="tooltips" href="https://maps.google.com/" target="_blank"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The longitude of the business place. For example, 73.98. Click to open maps page in new tab.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);

		$fields['facebook'][56]['og_product_original_price_amount|og_product_original_price_currency|og_product_pretax_price_amount|og_product_pretax_price_currency|og_product_price_amount|og_product_price_currency|og_product_shipping_cost_amount|og_product_shipping_cost_currency|og_product_weight_value|og_product_weight_units|og_product_shipping_weight_value|og_product_shipping_weight_units|og_product_sale_price_amount|og_product_sale_price_currency|og_product_sale_price_dates_start|og_product_sale_price_dates_end'] =
			$this->get_postmeta_subsection('og_type', 'product',		
				$this->get_postmeta_textbox('og_product_original_price_amount', __('Original Price: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The original price of the product. For example 150 (See Price Currency Below For Additional Options).</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_product_original_price_currency', __('Original Price Currency: <a class="tooltips" href="https://en.wikipedia.org/wiki/ISO_4217" target="_blank"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>An array of the currencies of the original prices of the product (in 3-letter ISO 4217 format). For example, USD. Click for more information.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_product_pretax_price_amount', __('Pre-tax Price: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The pre-tax price of the product. For example 20 (See Price Currency Below For Additional Options).</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_product_pretax_price_currency', __('Pre-tax Price Currency: <a class="tooltips" href="https://en.wikipedia.org/wiki/ISO_4217" target="_blank"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>An array of the currencies of the pre-tax prices of the product(in 3-letter ISO 4217 format). For example, USD. Click for more information.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_product_price_amount', __('Product Price: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The price of the product. For example 50 (See Price Currency Below For Additional Options).</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_product_price_currency', __('Product Price Currency: <a class="tooltips" href="https://en.wikipedia.org/wiki/ISO_4217" target="_blank"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>An array of the currencies of the prices of the product(in 3-letter ISO 4217 format). For example, USD. Click for more information.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_product_shipping_cost_amount', __('Shipping Cost: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The shipping cost of the product. For example 10 (See Price Currency Below For Additional Options).</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_product_shipping_cost_currency', __('Shipping Cost Currency: <a class="tooltips" href="https://en.wikipedia.org/wiki/ISO_4217" target="_blank"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>An array of the currencies of the shipping costs for the product(in 3-letter ISO 4217 format). For example, USD. Click for more information.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_product_weight_value', __('Product Weight: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The weight of the product. For example, 7.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_product_weight_units', __('Product Weight Units: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The units of the weight of the product. For example, Kg.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_product_shipping_weight_value', __('Shipping Weight: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The shipping weight of the product. For example, 2.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_product_shipping_weight_units', __('Shipping Weight Units: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The units of the shipping weight of the product. For example, Kg.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_product_sale_price_amount', __('Sale Price: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The sale price of the product. For example 25 (See Price Currency Below For Additional Options).</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_product_sale_price_currency', __('Sale Price Currency: <a class="tooltips" href="https://en.wikipedia.org/wiki/ISO_4217" target="_blank"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The currency of the sale price of the product(in 3-letter ISO 4217 format). For example, USD. Click for more information.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_product_sale_price_dates_start', __('Sale Starts: <a class="tooltips" href="https://en.wikipedia.org/wiki/ISO_8601" target="_blank"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The starting date and time of the sale(in ISO 8601 date format). For example, [YYYY]-[MM]-[DD]. Click for more information.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_product_sale_price_dates_end', __('Sale Ends: <a class="tooltips" href="https://en.wikipedia.org/wiki/ISO_8601" target="_blank"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The ending date and time of the sale(in ISO 8601 date format). For example, [YYYY]-[MM]-[DD]. Click for more information.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);

		$fields['facebook'][57]['og_place_latitude|og_place_longitude'] =
			$this->get_postmeta_subsection('og_type', 'place',		
				$this->get_postmeta_textbox('og_place_latitude', __('Latitude: <a class="tooltips" href="https://maps.google.com/" target="_blank"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The latitude of the place. For example, 40.75. Click to open maps page in new tab.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))				
				. $this->get_postmeta_textbox('og_place_longitude', __('Longitude: <a class="tooltips" href="https://maps.google.com/" target="_blank"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The longitude of the place. For example, 73.98. Click to open maps page in new tab.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);
			
		$fields['facebook'][58]['og_profile_first_name|og_profile_last_name|og_profile_gender|og_profile_username'] =
			$this->get_postmeta_subsection('og_type', 'profile',
				$this->get_postmeta_textbox('og_profile_first_name', __('First Name: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The first name of the person that this profile represents.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))	
				. $this->get_postmeta_textbox('og_profile_last_name', __('Last Name: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The last name of the person that this profile represents.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))				
				. $this->get_postmeta_textbox('og_profile_gender', __('Gender: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The gender of the person that this profile represents.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))				
				. $this->get_postmeta_textbox('og_profile_username', __('User Name: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>A username for the person that this profile represents.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);
			
		$fields['facebook'][59]['og_video_episode_url|og_video_episode_secure_url|og_video_episode_type|og_video_episode_width|og_video_episode_height|og_video_episode_actor_id|og_video_episode_actor_role|og_video_episode_director|og_video_episode_duration|og_video_episode_release_date|og_video_episode_tags|og_video_episode_writer'] =
			$this->get_postmeta_subsection('og_type', 'videoepisode',
				$this->get_postmeta_textbox('og_video_episode_url', __('Video URL: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The URL of a video resource associated with the object.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_video_episode_secure_url', __('Video Secure URL: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>An alternate URL to use if a video resource requires HTTPS.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_video_episode_type', __('Video Type: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The MIME type of a video resource associated with the object.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_video_episode_width', __('Video Width: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The width of a video resource associated with the object in pixels.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_video_episode_height', __('Video Height: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The height of a video resource associated with the object in pixels</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_video_episode_actor_id', __('Actor ID: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The Facebook IDs (or references to the profiles) of the actors in the movie.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('og_video_episode_actor_role', __('Actor Role: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The roles played by the actors in the movie.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))				
				. $this->get_postmeta_textbox('og_video_episode_director', __('Director: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The Facebook IDs (or references to the profiles) of the directors of the movie.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))				
				. $this->get_postmeta_textbox('og_video_episode_duration', __('Duration: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>An integer representing the length of the movie in seconds.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))				
				. $this->get_postmeta_textbox('og_video_episode_release_date', __('Release Date: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>A time representing when the movie was released.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))				
				. $this->get_postmeta_textbox('og_video_episode_tags', __('Tags: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>Keywords relevant to the movie.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))				
				. $this->get_postmeta_textbox('og_video_episode_writer', __('Writer: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The Facebook IDs (or references to the profiles) of the writers of the movie.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);

		$id = "_sl_fb_app_id";
		$value = sl_esc_attr($this->get_postmeta('fb_app_id'));
		$fields['facebook'][80]['fb_app_id'] = 
			"<div class='form-group sl textbox'>\n<label class='col-sm-4 col-md-4 control-label' for='$id'>".__('Facebook App ID:', 'seolat-tool-plus')."</label>\n<div class='col-sm-4 col-md-4'><input name='$id' id='$id' type='text' value='$value' class='form-control input-sm regular-text' tabindex='2'"
			. " />"
			. "</div>\n<div class='col-sm-4 col-md-4 help-text'>".sprintf(__('Leave blank to pull from global or enter Facebook admin name/number (located under Facebook General Account Settings > Username). If more than one admin exists, enter all separated by commas.', 'seolat-tool-plus'))
			. "</div>\n</div>\n";

		$id = "_sl_fb_admins_url";
		$value = sl_esc_attr($this->get_postmeta('fb_admins_url'));
		$fields['facebook'][81]['fb_admins_url'] = 
			"<div class='form-group sl textbox'>\n<label class='col-sm-4 col-md-4 control-label' for='$id'>".__('Facebook Admins:', 'seolat-tool-plus')."</label>\n<div class='col-sm-4 col-md-4'><input name='$id' id='$id' type='text' value='$value' class='form-control input-sm regular-text' tabindex='2'"
			. " />"
			. "</div>\n<div class='col-sm-4 col-md-4 help-text'>".sprintf(__('Enter user id of facebook fan page admin. If more than one admin exists, enter all the id\'s separated by comma for facebook insights.', 'seolat-tool-plus'))
			. "</div>\n</div>\n";

		$fields['twitter'][60]['twitter_card'] = $this->get_postmeta_dropdown('twitter_card', array(
			  '' => __('Use default', 'seolat-tool-plus')
			, 'summary' => __('Summary', 'seolat-tool-plus')
			, 'summary_large_image' => __('Summary Large Image', 'seolat-tool-plus')
			, 'app' => __('App', 'seolat-tool-plus')
			, 'player' => __('Player', 'seolat-tool-plus')
		), __('Twitter Card Type:', 'seolat-tool-plus'));
		
		$id = "_sl_twitter_title_handle";
		$value = sl_esc_attr($this->get_postmeta('twitter_title_handle'));
		$fields['twitter'][61]['twitter_title_handle'] =
			"<div class='form-group sl textbox'>\n<label class='col-sm-4 col-md-4 control-label' for='$id'>".__('<a class="tooltips">Twitter Title:<span>Title should be concise and will be truncated at 70 characters.</span></a>', 'seolat-tool-plus')."</label>\n<div class='col-sm-4 col-md-4'><input name='$id' id='$id' type='text' value='$value' class='form-control input-sm regular-text' tabindex='2'"
			. " onkeyup=\"javascript:document.getElementById('sl_twitter_title_handle_charcount').innerHTML = document.getElementById('_sl_twitter_title_handle').value.length\" />"
			. "</div>\n<div class='col-sm-4 col-md-4 help-text'>".sprintf(__('You&#8217;ve Entered %s Characters. Twitter Displays Up To 70 Characters.', 'seolat-tool-plus'), "<strong id='sl_twitter_title_handle_charcount'>".strlen($value)."</strong>")
			. "</div>\n</div>\n";

		$id = '_sl_twitter_description_handle';
		$value = sl_esc_attr($this->get_postmeta('twitter_description_handle'));
		$fields['twitter'][62]['twitter_description_handle'] =
			"<div class='form-group sl textarea'>\n<label class='col-sm-4 col-md-4 control-label' for='$id'>".__('<a class="tooltips">Twitter Description:<span>Description of content (maximum 200 characters).</span></a>', 'seolat-tool-plus')."</label>\n<div class='col-sm-4 col-md-4'>"
			. "<textarea name='$id' id='$id' class='form-control regular-text' cols='60' rows='3' tabindex='2'"
			. " onkeyup=\"javascript:document.getElementById('sl_twitter_description_handle_charcount').innerHTML = document.getElementById('_sl_twitter_description_handle').value.length\">$value</textarea>"
			. "</div>\n<div class='col-sm-4 col-md-4 help-text'>".sprintf(__('You&#8217;ve Entered %s Characters. Maximum Suggested Twitter Description is 200 Characters or Less.', 'seolat-tool-plus'), "<strong id='sl_twitter_description_handle_charcount'>".strlen($value)."</strong>")
			. "</div>\n</div>\n";

		$fields['twitter'][63]['twitter_image_handle'] = $this->get_postmeta_medialib_box('twitter_image_handle', __('<a class="tooltips">Twitter Image:<span>Use button to upload image. It must be less than 1MB in size.</span></a>', 'seolat-tool-plus'));
			
		$fields['twitter'][68]['twitter_app_name_iphone_handle|twitter_app_id_iphone_handle|twitter_app_url_iphone_handle|twitter_app_name_ipad_handle|twitter_app_id_ipad_handle|twitter_app_url_ipad_handle|twitter_app_name_googleplay_handle|twitter_app_id_googleplay_handle|twitter_app_url_googleplay_handle'] =
			$this->get_postmeta_subsection('twitter_card', 'app',
				$this->get_postmeta_textbox('twitter_app_name_iphone_handle', __('<a class="tooltips">Twitter App Name iPhone:<span>Name of your iPhone app.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('twitter_app_id_iphone_handle', __('<a class="tooltips">Twitter App ID iPhone:<span>Your app ID in the iTunes App Store (Note: NOT your bundle ID).</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('twitter_app_url_iphone_handle', __('<a class="tooltips">Twitter App URL iPhone:<span>Your app\'s custom URL scheme (you must include "://" after your scheme name).</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('twitter_app_name_ipad_handle', __('<a class="tooltips">Twitter App Name iPad:<span>Name of your iPad optimized app.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('twitter_app_id_ipad_handle', __('<a class="tooltips">Twitter App ID iPad:<span>Your app ID in the iTunes App Store.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('twitter_app_url_ipad_handle', __('<a class="tooltips">Twitter App URL iPad:<span>Your app&#39;s custom URL scheme.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('twitter_app_name_googleplay_handle', __('<a class="tooltips">Twitter App Name Googleplay:<span>Name of your Android app.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('twitter_app_id_googleplay_handle', __('<a class="tooltips">Twitter App ID Googleplay:<span>Your app ID in the Google Play Store.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('twitter_app_url_googleplay_handle', __('<a class="tooltips">Twitter App URL Googleplay:<span>Your app&#39;s custom URL scheme.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);
			
		$fields['twitter'][69]['twitter_player_handle|twitter_player_width_handle|twitter_player_height_handle|twitter_image_handle|twitter_player_stream_handle|twitter_player_stream_content_type_handle'] = 
			$this->get_postmeta_subsection('twitter_card', 'player',
				$this->get_postmeta_textbox('twitter_player_handle', __('<a class="tooltips">Twitter Player:<span>HTTPS URL of player iframe.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('twitter_player_width_handle', __('<a class="tooltips">Player Width:<span>Width of iframe in pixels.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('twitter_player_height_handle', __('<a class="tooltips">Player Height:<span>Height of iframe in pixels.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('twitter_player_stream_handle', __('<a class="tooltips">Player Stream:<span>URL to raw video or audio stream.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('twitter_player_stream_content_type_handle', __('<a class="tooltips">Player Stream Content Type:<span>The MIME type/subtype combination that describes the content contained in twitter:player:stream. Takes the form specified in RFC 6381. Currently supported content_type values are those defined in RFC 4337 (MIME Type Registration for MP4).</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);
		
		// $id = "_sl_gplus_title";
		// $value = sl_esc_attr($this->get_postmeta('gplus_title'));
		// $fields['google-plus'][71]['gplus_title'] =
		// 	"<div class='form-group sl textbox'>\n<label class='col-sm-4 col-md-4 control-label' for='$id'>".__('Google Plus Title:', 'seolat-tool-plus')."</label>\n<div class='col-sm-4 col-md-4'><input name='$id' id='$id' type='text' value='$value' class='form-control input-sm regular-text' tabindex='2'"
		// 	. " onkeyup=\"javascript:document.getElementById('sl_gplus_title_charcount').innerHTML = document.getElementById('_sl_gplus_title').value.length\" />"
		// 	. "</div>\n<div class='col-sm-4 col-md-4 help-text'>".sprintf(__('You&#8217;ve Entered %s Characters. Title Should Not Exceed 60 Characters.', 'seolat-tool-plus'), "<strong id='sl_gplus_title_charcount'>".strlen($value)."</strong>")
		// 	. "</div>\n</div>\n";

		// $id = '_sl_gplus_description';
		// $value = sl_esc_attr($this->get_postmeta('gplus_description'));
		// $fields['google-plus'][72]['gplus_description'] =
		// 	"<div class='form-group sl textarea'>\n<label class='col-sm-4 col-md-4 control-label' for='$id'>".__('Google Plus Description:', 'seolat-tool-plus')."</label>\n<div class='col-sm-4 col-md-4'>"
		// 	. "<textarea name='$id' id='$id' class='form-control regular-text' cols='60' rows='3' tabindex='2'"
		// 	. " onkeyup=\"javascript:document.getElementById('sl_gplus_description_charcount').innerHTML = document.getElementById('_sl_gplus_description').value.length\">$value</textarea>"
		// 	. "</div>\n<div class='col-sm-4 col-md-4 help-text'>".sprintf(__('You&#8217;ve Entered %s Characters. Description Should Not Exceed 156 Characters.', 'seolat-tool-plus'), "<strong id='sl_gplus_description_charcount'>".strlen($value)."</strong>")
		// 	. "</div>\n</div>\n";
		
		// $fields['google-plus'][73]['gplus_image_src'] = $this->get_postmeta_medialib_box('gplus_image_src', __('<a class="tooltips">Google Plus Image:<span>The height must be at least 120px, and if the width is less than 100px, then the aspect ratio must be no greater than 3.0.</span></a>', 'seolat-tool-plus'), 'types=posttype_attachment&post_mime_type=image/*');
			
		// $id = "_sl_gplus_page_url";
		// $value = sl_esc_attr($this->get_postmeta('gplus_page_url'));
		// $fields['google-plus'][74]['gplus_page_url'] = 
		// 	"<div class='form-group sl textbox'>\n<label class='col-sm-4 col-md-4 control-label' for='$id'>".__('Google Plus Publisher:', 'seolat-tool-plus')."</label>\n<div class='col-sm-4 col-md-4'><input name='$id' id='$id' type='text' value='$value' class='form-control input-sm regular-text' tabindex='2'"
		// 	. " />"
		// 	. "</div>\n<div class='col-sm-4 col-md-4 help-text'>".sprintf(__('Enter your Google Plus page URL here if you have set up a "Google+ Page" for your organization or product, and the plugin will put a rel="publisher" link to the specified Google+ page on your page.', 'seolat-tool-plus'))
		// 	. "</div>\n</div>\n";
			
		$fields['advanced'][28]['previous_url_tag'] = $this->get_postmeta_textbox('previous_url_tag', __('Previous URL: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The first page only contains rel="next" and no rel="prev" markup. Pages two to the second-to-last page should be doubly-linked with both rel="next" and rel="prev" markup. The last page only contains markup for rel="prev", not rel="next".</span></a>', 'seolat-tool-plus'), array('type' => 'text'));
		
		$fields['advanced'][29]['next_url_tag'] = $this->get_postmeta_textbox('next_url_tag', __('Next URL: <a class="tooltips"> <img class="og-info-icon" src="' . plugins_url( 'opengraph/og-info-icon.png', dirname(__FILE__) ) . '" > <span>The first page only contains rel="next" and no rel="prev" markup. Pages two to the second-to-last page should be doubly-linked with both rel="next" and rel="prev" markup. The last page only contains markup for rel="prev", not rel="next".</span></a>', 'seolat-tool-plus'), array('type' => 'text'));
			
		

		$fields['advanced'][31]['meta_robots_opengraph'] = $this->get_postmeta_checkboxes( array(
            'meta_robots_opengraph' => __('Don\'t add Open Graph.', 'seolat-tool-plus')
        ), __('Open Graph:', 'seolat-tool-plus'));

		return $fields;
	}
	
	function get_postmeta_jlsuggest_boxes($jls_boxes) {
		$this->jlsuggest_box_post_id = lat_wp::get_post_id();
		return parent::get_postmeta_jlsuggest_boxes($jls_boxes);
	}
	
	function get_input_element($type, $name, $value=null, $extra=false, $inputid=true, $args=array()) {
		
		$name_parts = explode('_', $name);
		if (isset($name_parts[1]) && is_numeric($post_id = $name_parts[1]))
			$this->jlsuggest_box_post_id = $post_id;
		else
			$this->jlsuggest_box_post_id = false;
		
		return parent::get_input_element($type, $name, $value, $extra, $inputid, $args);
	}
	
	function get_jlsuggest_box($name, $value, $params='', $placeholder='') {
		
		if (empty($value) && $this->jlsuggest_box_post_id && current_theme_supports('post-thumbnails') && $thumbnail_id = get_post_thumbnail_id($this->jlsuggest_box_post_id)) {
			$selected_post = get_post($thumbnail_id);
			$placeholder = sprintf(__('Featured Image: %s', 'seolat-tool-plus'), $selected_post->post_title);
		}
		
		return parent::get_jlsuggest_box($name, $value, $params, $placeholder);
	}
	
	function get_type_options() {
		return array(
			  'none' => __('None', 'seolat-tool-plus')
			, 'blog' => __('Blog', 'seolat-tool-plus')
			, 'business' => __('Business', 'seolat-tool-plus')
			, 'product' => __('Product', 'seolat-tool-plus')
			, 'place' => __('Place', 'seolat-tool-plus')
			, 'website' => __('Website', 'seolat-tool-plus')
			,__('Internet', 'seolat-tool-plus') => array(
				  'article' => __('Article', 'seolat-tool-plus')
				, 'profile' => __('Profile', 'seolat-tool-plus')
			),__('Books', 'seolat-tool-plus') => array(
				  'books.book' => __('Book', 'seolat-tool-plus')
				, 'books.author' => __('Author', 'seolat-tool-plus')
				, 'books.genre' => __('Genre', 'seolat-tool-plus')
			),__('Music', 'seolat-tool-plus') => array(
				  'music.album' => __('Album', 'seolat-tool-plus')
				, 'music.playlist' => __('Playlist', 'seolat-tool-plus')
				, 'music.radio_station' => __('Radio Station', 'seolat-tool-plus')
				, 'music.song' => __('Song', 'seolat-tool-plus')
			),__('Videos', 'seolat-tool-plus') => array(
				  'video.movie' => __('Movie', 'seolat-tool-plus')
				, 'video.episode' => __('TV Episode', 'seolat-tool-plus')
				, 'video.tv_show' => __('TV Show', 'seolat-tool-plus')
				, 'video.other' => __('Video', 'seolat-tool-plus')
			)
		);
	}
	
	function get_twitter_type_options() {
		return array(
			  'summary' => __('Summary', 'seolat-tool-plus')
			, 'summary_large_image' => __('Summary Large Image', 'seolat-tool-plus')
			, 'app' => __('App', 'seolat-tool-plus')
			, 'player' => __('Player', 'seolat-tool-plus')
		);
	}
	
	function sanitize_twitter_handle($value) {
		if (strpos($value, '/') === false) {
			$handle = ltrim($value, '@');
		} else {
			$url_parts = explode('/', $value);
			$handle = array_pop($url_parts);
		}
		
		$handle = lat_string::preg_filter('a-zA-Z0-9_', $handle);
		$handle = trim($handle);
		
		if ($handle)
			$handle = "@$handle";
		
		return $handle;
	}
	
	function add_twitter_field( $contactmethods ) {
		$contactmethods['twitter'] = __('Twitter ID', 'seolat-tool-plus');
		return $contactmethods;
	}
	
	function user_meta_fields( $user ) {
	?>
	<h3><?php _e("SEO LAT Settings", "blank"); ?></h3>
		<table class="form-table">
		<tr>
			<th>
				<label
					for="author_metatitle"><?php _e("Author Meta Title", 'seolat-tool-plus' ); ?></label>
			</th>
			<td>
				<input class="regular-text" type="text" id="author_metatitle" name="author_metatitle" value="<?php echo esc_attr( get_the_author_meta( 'author_title', $user->ID ) ); ?>"/>
			</td>
		</tr>
		<tr>
			<th>
				<label
					for="author_metadesc"><?php _e( 'Author Meta Description', 'seolat-tool-plus' ); ?></label>
			</th>
			<td>
				<textarea rows="3" cols="30" id="author_metadesc" name="author_metadesc"><?php echo esc_textarea( get_the_author_meta( 'author_desc', $user->ID ) ); ?></textarea>
			</td>
		</tr>
	  </table>
	<?php
	}

	function save_user_meta_fields( $user_id ) {
	  $saved = false;
	  if ( current_user_can( 'edit_user', $user_id ) ) {
		update_user_meta( $user_id, 'author_title', $_POST['author_metatitle'] );
		update_user_meta( $user_id, 'author_desc', $_POST['author_metadesc'] );
		$saved = true;
	  }
	  return true;
	}
	
	function add_help_tabs($screen) {
		
		$screen->add_help_tab(array(
			  'id' => 'sl-opengraph-overview'
			, 'title' => __('Overview', 'seolat-tool-plus')
			, 'content' => __("
<ul>
	<li><strong>What it does:</strong> Open Graph+ makes it easy for you to convey information about your site to social networks like Facebook, Twitter, and Google+.</li>
	<li><strong>Why it helps:</strong> By providing this Open Graph data, you can customize how these social networks will present your site when people share it with their followers.</li>
	<li><strong>How to use it:</strong> The &#8220;Global Settings&#8221; tab lets you specify data that applies to your entire site. The &#8220;Default Values&#8221; tab lets you specify default data for your posts, pages, etc. The bulk editor tabs let you override those defaults on individual posts and pages. If the authors on your site fill in the &#8220;Twitter Handle&#8221; field which Social and Semantic Generator adds to the <a href='profile.php'>profile editor</a>, Social and Semantic Generator will communicate that information to Twitter as well.</li>
</ul>
", 'seolat-tool-plus')));
		
	}
}

}
?>