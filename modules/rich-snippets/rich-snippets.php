<?php
/**
 * Rich Snippet Creator Module
 * 
 * @since 3.0
 */

if (class_exists('SL_Module')) {

class SL_RichSnippets_Removed extends SL_Module {
	
	var $apply_subproperty_markup_args = array();
	var $apply_subsubproperty_markup_args = array();
	
	static function get_module_title() { return __('Rich Snippet Creator', 'seolat-tool-plus'); }
	
	static function get_parent_module() { return 'misc'; }
	function get_settings_key() { return 'rich-snippets'; }
	
	function init() {
		add_filter('the_content', array(&$this, 'apply_markup'));
	}
	
	function get_default_status() { return SL_MODULE_DISABLED; }
	function get_default_settings() { 
		return array(
			  'hide_rich_snippets' => 'off'
		);
	}
	
	function admin_page_contents() {
		$this->child_admin_form_start();
		$this->textblock(__('Rich Snippet Creator adds a &#8220; Rich Snippets Type&#8221; dropdown to the WordPress content editor screen. To add rich snippet data to a post, select &#8220;Review&#8221; or &#8220;Product&#8221; Or Others from a post&#8217;s  &#8220; Rich Snippets Type&#8221; dropdown and fill in the fields that appear.', 'seolat-tool-plus'));
		$this->radiobuttons('hide_rich_snippets', array(
			  'on' => __('Add Markup to Header and Page/Post', 'seolat-tool-plus')
			, 'off' => __('Add Markup to Header Only', 'seolat-tool-plus')
		), __('Set Global Rich Snippets Visibility', 'seolat-tool-plus'));
		$this->child_admin_form_end();
	}
	
	function get_supported_snippet_formats() {
		
		$hide_rich_snippets = ($this->get_postmeta('hide_rich_snippets') == 'global') ? $this->get_setting('hide_rich_snippets', 'on', 'rich-snippets') : $this->get_postmeta('hide_rich_snippets');
		$item_tags_template = ($hide_rich_snippets == 'on') ? '<div itemscope itemtype="http://schema.org/%1$s">%2$s</div>' : '<div class="stealth-snipp" itemscope itemtype="http://schema.org/%1$s">%2$s</div>';
		
		return array(
			  'so' => array(
				  'label' => __('Schema.org Microdata', 'seolat-tool-plus')
				, 'item_tags_template' => $item_tags_template
				, 'property_tags_template' => '<span itemprop="%1$s">%2$s</span>'
				, 'hidden_property_tags_template' => '<meta itemprop="%1$s" content="%2$s" />'
				)
		);
	}
	
	function get_supported_snippet_types() {
		return array(
			'review' => array(
				  'label' => __('Review', 'seolat-tool-plus')
				, 'tags' => 'Review'
				, 'content_tags' => '<div itemprop="reviewBody">%s</div>'
				, 'properties' => array(
					  'item' => array(
						  'label' => __('Name of Reviewed Item', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets reviewed-item">Name of Reviewed Item:<span itemprop="itemReviewed"> %s </span></span>'
					),'rating' => array(
						  'label' => __('Star Rating', 'seolat-tool-plus')
						, 'value_format' => array('%s star', '%s stars', '%s-star', '%s-stars')
						, 'tags' =>   '<span itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating">'
									. '<meta itemprop="worstRating" content="0" />'
									. '<span class="rich-snippets rating-value">Rating Value:<span itemprop="ratingValue"> %s </span></span>'
									. '<meta itemprop="bestRating" content="5" />'
									. '</span>'
						//, 'hidden_tags' => '<span itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating">'
						//				. '<meta itemprop="worstRating" content="0" />'
						//				. '<meta itemprop="ratingValue" content="%s" />'
						//				. '<meta itemprop="bestRating" content="5" />'
						//				. '</span>'
					),'image' => array(
						  'label' => __('Image of Reviewed Item', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets review-image">Image:<a itemprop="image" href="%1$s"> %1$s </a></span>'
						//, 'hidden_tags'=> '<link itemprop="image" href="%1$s" />'
						//, 'jlsuggest' => true
					),'reviewer' => array(
						  'label' => __('Review Author', 'seolat-tool-plus')
						, 'editable' => false
						, 'value_function' => 'get_the_author'
						, 'tags' => '<span class="rich-snippets review-author">Review Author:<span itemprop="author"> %s </span></span>'
					),'date_reviewed' => array(
						  'label' => __('Date Reviewed', 'seolat-tool-plus')
						, 'editable' => false
						, 'value_function' => array('get_the_time', 'Y-m-d')
						, 'tags' => '<span class="rich-snippets review-date">Review Date:<time itemprop="datePublished"> %s </time></span>'
						//, 'hidden_tags' => '<meta itemprop="datePublished" content="%s" />'
					)
				)
			),'product' => array(
				  'label' => __('Product', 'seolat-tool-plus')
				, 'tags' => 'Product'
				, 'properties' => array(
					  'name' => array(
						  'label' => __('Product Name', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets product-name">Product Name:<span itemprop="name"> %s </span></span>'
					),'brand' => array(
						  'label' => __('Product Brand', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets product-brand">Product Brand:<span itemprop="brand"> %s </span></span>'
					),'description' => array(
						  'label' => __('Product Description', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets product-description">Product Description: <span itemprop="description"> %s </span></span>'
					),'image' => array(
						  'label' => __('Product Image', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets product-image">Product Image:<a itemprop="image" src="%1$s"> %1$s </a></span>'
					),'offers' => array(
						  'label' => __('Offer', 'seolat-tool-plus')
						, 'tags' => '<span itemprop="offers" itemscope itemtype="http://schema.org/Offer"> %s </span>'
						, 'properties' => array(
							  'price' => array(
								  'label' => __('Product price', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets product-price">Product Price:<span itemprop="price"> %s </span></span>'
							),'price_currency' => array(
								  'label' => __('Price Currency', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets product-price-currency">Price Currency: <span itemprop="priceCurrency"> %s </span></span>'
							),'seller' => array(
								  'label' => __('Name Of The Seller', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets product-seller">Name Of The Seller:<span itemprop="seller"> %s </span></span>'
							)
						)
					),'aggregate' => array(
						  'label' => __('Product Ratting Values', 'seolat-tool-plus')
						, 'tags' => '<span itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating"> %s </span>'
						, 'properties' => array(
							  'rating' => array(
								  'label' => __('Rating Value', 'seolat-tool-plus')
								, 'tags' =>   '<meta itemprop="worstRating" content="0" />'
											. '<span class="rich-snippets product-rating">Rating Value:<span itemprop="ratingValue"> %s </span></span>'
											. '<meta itemprop="bestRating" content="5" />'
							),'reviewcount' => array(
								  'label' => __('Total Reviews', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets product-reviewcount">Total Reviews:<span itemprop="reviewCount"> %s </span> Reviews</span>'
							)
						)
					)
				)
			),'business' => array(
				  'label' => __('Local Business', 'seolat-tool-plus')
				, 'tags' => 'LocalBusiness'
				, 'properties' => array(
					  'name' => array(
						  'label' => __('Business Name', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets business-name">Business Name: <span itemprop="name"> %s </span></span>'
					),'image' => array(
						  'label' => __('Business Image', 'seolat-tool-plus')
						, 'tags'=> '<span class="rich-snippets business-image">Business Image:  <a itemprop="image" href="%1$s"> %1$s </a></span>'
					),'address' => array(
						  'label' => __('Address', 'seolat-tool-plus')
						, 'tags' => '<span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress"> %s </span>'
						, 'properties' => array(
							  'street' => array(
								  'label' => __('Street Address', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets business-street-address">Street Address: <span itemprop="streetAddress"> %s </span></span>'
							),'po_box' => array(
								  'label' => __('PO Box', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets business-po-box">PO Box: <span itemprop="postOfficeBoxNumber"> %s </span></span>'
							),'city' => array(
								  'label' => __('City', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets business-city">City: <span itemprop="addressLocality"> %s </span></span>'
							),'state' => array(
								  'label' => __('State', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets business-state">State: <span itemprop="addressRegion"> %s </span></span>'
							),'country' => array(
								  'label' => __('Country', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets business-country">Country: <span itemprop="addressCountry"> %s </span></span>'
							),'postal_code' => array(
								  'label' => __('Postal Code', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets business-postal-code">Postal Code: <span itemprop="postalCode"> %s </span></span>'
							)
						)
					),'tel_number' => array(
						  'label' => __('Phone Number', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets business-tel-number">Phone Number: <span itemprop="telephone"> %s </span></span>'
					),'fax_number' => array(
						  'label' => __('Fax Number', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets business-fax-number">Fax Number: <span itemprop="faxNumber"> %s </span></span>'
					),'email' => array(
						  'label' => __('Business Email', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets business-email">Business Email: <span itemprop="email"> %s </span></span>'
					),'website' => array(
						  'label' => __('Business Website', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets business-website">Business Website: <a itemprop="url" href="%1$s"> %1$s </a></span>'
					),'hours' => array(
						  'label' => __('Business Hours', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets business-hours">Business Hours: <span itemprop="openingHours"> %s </span></span>'
					),'map_url' => array(
						  'label' => __('Map URL', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets business-map-url">Map URL: <a itemprop="map" href="%1$s"> %1$s </a></span>'
						//, 'hidden_tags' => '<link itemprop="map" href="%1$s" />'
						//, 'jlsuggest' => true
					),'photo' => array(
						  'label' => __('Photo', 'seolat-tool-plus')
						, 'tags'=>'<span itemprop="photo" itemscope itemtype="http://schema.org/Photograph">'
								. '<span class="rich-snippets business-photo">Photo:  <a itemprop="url" href="%1$s"> %1$s </a></span>'
								. '</span>'
						//, 'hidden_tags'=> '<span itemprop="photo" itemscope itemtype="http://schema.org/Photograph">'
						//				. '<link itemprop="url" href="%1$s" />'
						//				. '</span>'
						, 'jlsuggest' => true
					),'pricerange' => array(
						  'label' => __('Price Range', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets business-price-range">Price Range: <span itemprop="priceRange"> %s </span></span>'
					)
				)
			),'person' => array(
				  'label' => __('Person', 'seolat-tool-plus')
				, 'tags' => 'Person'
				, 'properties' => array(
					'name' => array(
						  'label' => __('Name', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets person-name">Name: <span itemprop="name"> %s </span></span>'
					),'address' => array(
						  'label' => __('Address', 'seolat-tool-plus')
						, 'tags' => '<span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress"> %s </span>'
						, 'properties' => array(
							  'street' => array(
								  'label' => __('Street Address', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets person-street-address">Street Address: <span itemprop="streetAddress"> %s </span></span>'
							),'po_box' => array(
								  'label' => __('PO Box', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets person-po-box">PO Box: <span itemprop="postOfficeBoxNumber"> %s </span></span>'
							),'city' => array(
								  'label' => __('City', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets person-city">City: <span itemprop="addressLocality"> %s </span></span>'
							),'state' => array(
								  'label' => __('State', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets person-state">State: <span itemprop="addressRegion"> %s </span></span>'
							),'country' => array(
								  'label' => __('Country', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets person-country">Country: <span itemprop="addressCountry"> %s </span></span>'
							),'postal_code' => array(
								  'label' => __('Postal Code', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets person-postal-code">Postal Code: <span itemprop="postalCode"> %s </span></span>'
							)
						)
					),'tel_number' => array(
						  'label' => __('Phone Number', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets person-tel-number">Phone Number: <span itemprop="telephone"> %s </span></span>'
					),'email' => array(
						  'label' => __('Business Email', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets person-email">Business Email: <span itemprop="email"> %s </span></span>'
					),'jobtitle' => array(
						  'label' => __('job Title', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets person-jobtitle">job Title: <span itemprop="jobTitle"> %s </span></span>'
					),'affiliation' => array(
						  'label' => __('Business', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets person-business">Business: <span itemprop="affiliation"> %s </span></span>'
					),'image' => array(
						  'label' => __('Image', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets person-image">Image: <a itemprop="image" src="%1$s"> %1$s </a></span>'
					),'facebook_url' => array(
						  'label' => __('Facebook Url', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets person-facebook-url">Facebook Url: <a itemprop="sameAs" href="%1$s"> Facebook </a></span>'
					),'twitter_url' => array(
						  'label' => __('Twitter Url', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets person-twitter-url">Twitter Url: <a itemprop="sameAs" href="%1$s"> Twitter </a></span>'
					),'instagram_url' => array(
						  'label' => __('Instagram Url', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets person-instagram-url">Instagram Url: <a itemprop="sameAs" href="%1$s"> Instagram </a></span>'
					),'linkedin_url' => array(
						  'label' => __('LinkedIn Url', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets person-linkedin-url">LinkedIn Url: <a itemprop="sameAs" href="%1$s"> LinkedIn </a></span>'
					),'myspace_url' => array(
						  'label' => __('MySpace Url', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets person-myspace-url">MySpace Url: <a itemprop="sameAs" href="%1$s"> MySpace </a></span>'
					),'pinterest_url' => array(
						  'label' => __('Pinterest Url', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets person-pinterest-url">Pinterest Url: <a itemprop="sameAs" href="%1$s"> Pinterest </a></span>'
					),'youtube_url' => array(
						  'label' => __('YouTube Url', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets person-youtube-url">YouTube Url: <a itemprop="sameAs" href="%1$s"> YouTube </a></span>'
					),'google_plus_url' => array(
						  'label' => __('Google&#43; Url', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets person-google-plus-url">Google&#43; Url: <a itemprop="sameAs" href="%1$s"> Google&#43; </a></span>'
					)
				)
			),'organization' => array(
				  'label' => __('Organization', 'seolat-tool-plus')
				, 'tags' => 'Organization'
				, 'properties' => array(
					'company_name' => array(
						  'label' => __('Company Name', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets commpany-name">Company Name: <span itemprop="name"> %s </span></span>'
					),'website_url' => array(
						  'label' => __('Website URL', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets commpany-website-url">Website URL: <a itemprop="url" href="%1$s"> %1$s </a></span>'
					),'company_logo' => array(
						  'label' => __('Company Logo', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets commpany-logo">Company Logo: <a itemprop="logo" href="%1$s"> %1$s </a></span>'
					),'facebook_url' => array(
						  'label' => __('Facebook Url', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets commpany-facebook-url">Facebook Url: <a itemprop="sameAs" href="%1$s"> Facebook </a></span>'
					),'twitter_url' => array(
						  'label' => __('Twitter Url', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets commpany-twitter-url">Twitter Url: <a itemprop="sameAs" href="%1$s"> Twitter </a></span>'
					),'instagram_url' => array(
						  'label' => __('Instagram Url', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets commpany-instagram-url">Instagram Url: <a itemprop="sameAs" href="%1$s"> Instagram </a></span>'
					),'linkedin_url' => array(
						  'label' => __('LinkedIn Url', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets commpany-linkedin-url">LinkedIn Url: <a itemprop="sameAs" href="%1$s"> LinkedIn </a></span>'
					),'myspace_url' => array(
						  'label' => __('MySpace Url', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets commpany-myspace-url">MySpace Url: <a itemprop="sameAs" href="%1$s"> MySpace </a></span>'
					),'pinterest_url' => array(
						  'label' => __('Pinterest Url', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets commpany-pinterest-url">Pinterest Url: <a itemprop="sameAs" href="%1$s"> Pinterest </a></span>'
					),'youtube_url' => array(
						  'label' => __('YouTube Url', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets commpany-youtube-url">YouTube Url: <a itemprop="sameAs" href="%1$s"> YouTube </a></span>'
					),'google_plus_url' => array(
						  'label' => __('Google&#43; Url', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets commpany-google-plus-url">Google&#43; Url: <a itemprop="sameAs" href="%1$s"> Google&#43; </a></span>'
					),'contactpoint0' => array(
						  'label' => __('Contact Point', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets organization-contact-point0" itemprop="ContactPoint" itemscope itemtype="http://schema.org/ContactPoint"> %s </span>'
						, 'properties' => array(
							  'contacttype0' => array(
								  'label' => __('Contact Type', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-contact-type0">Contact Type: <span itemprop="contactType"> %s </span></span>'
							),'telephone0' => array(
								  'label' => __('Phone Number', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-tel-number0">Phone Number: <span itemprop="telephone"> %s </span></span>'
							)
						)
					),'contactpoint1' => array(
						  'label' => __('Contact Point', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets organization-contact-point1" itemprop="ContactPoint" itemscope itemtype="http://schema.org/ContactPoint"> %s </span>'
						, 'properties' => array(
							  'contacttype1' => array(
								  'label' => __('Contact Type', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-contact-type1">Contact Type: <span itemprop="contactType"> %s </span></span>'
							),'telephone1' => array(
								  'label' => __('Phone Number', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-tel-number1">Phone Number: <span itemprop="telephone"> %s </span></span>'
							)
						)
					),'contactpoint2' => array(
						  'label' => __('Contact Point', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets organization-contact-point1" itemprop="ContactPoint" itemscope itemtype="http://schema.org/ContactPoint"> %s </span>'
						, 'properties' => array(
							  'contacttype2' => array(
								  'label' => __('Contact Type', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-contact-type2">Contact Type: <span itemprop="contactType"> %s </span></span>'
							),'telephone2' => array(
								  'label' => __('Phone Number', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-tel-number2">Phone Number: <span itemprop="telephone"> %s </span></span>'
							)
						)
					),'contactpoint3' => array(
						  'label' => __('Contact Point', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets organization-contact-point3" itemprop="ContactPoint" itemscope itemtype="http://schema.org/ContactPoint"> %s </span>'
						, 'properties' => array(
							  'contacttype3' => array(
								  'label' => __('Contact Type', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-contact-type3">Contact Type: <span itemprop="contactType"> %s </span></span>'
							),'telephone3' => array(
								  'label' => __('Phone Number', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-tel-number3">Phone Number: <span itemprop="telephone"> %s </span></span>'
							)
						)
					),'contactpoint4' => array(
						  'label' => __('Contact Point', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets organization-contact-point4" itemprop="ContactPoint" itemscope itemtype="http://schema.org/ContactPoint"> %s </span>'
						, 'properties' => array(
							  'contacttype4' => array(
								  'label' => __('Contact Type', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-contact-type4">Contact Type: <span itemprop="contactType"> %s </span></span>'
							),'telephone4' => array(
								  'label' => __('Phone Number', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-tel-number4">Phone Number: <span itemprop="telephone"> %s </span></span>'
							)
						)
					),'contactpoint5' => array(
						  'label' => __('Contact Point', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets organization-contact-point5" itemprop="ContactPoint" itemscope itemtype="http://schema.org/ContactPoint"> %s </span>'
						, 'properties' => array(
							  'contacttype5' => array(
								  'label' => __('Contact Type', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-contact-type5">Contact Type: <span itemprop="contactType"> %s </span></span>'
							),'telephone5' => array(
								  'label' => __('Phone Number', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-tel-number5">Phone Number: <span itemprop="telephone"> %s </span></span>'
							)
						)
					),'contactpoint6' => array(
						  'label' => __('Contact Point', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets organization-contact-point6" itemprop="ContactPoint" itemscope itemtype="http://schema.org/ContactPoint"> %s </span>'
						, 'properties' => array(
							  'contacttype6' => array(
								  'label' => __('Contact Type', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-contact-type6">Contact Type: <span itemprop="contactType"> %s </span></span>'
							),'telephone6' => array(
								  'label' => __('Phone Number', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-tel-number6">Phone Number: <span itemprop="telephone"> %s </span></span>'
							)
						)
					),'contactpoint7' => array(
						  'label' => __('Contact Point', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets organization-contact-point7" itemprop="ContactPoint" itemscope itemtype="http://schema.org/ContactPoint"> %s </span>'
						, 'properties' => array(
							  'contacttype7' => array(
								  'label' => __('Contact Type', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-contact-type7">Contact Type: <span itemprop="contactType"> %s </span></span>'
							),'telephone7' => array(
								  'label' => __('Phone Number', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-tel-number7">Phone Number: <span itemprop="telephone"> %s </span></span>'
							)
						)
					),'contactpoint8' => array(
						  'label' => __('Contact Point', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets organization-contact-point8" itemprop="ContactPoint" itemscope itemtype="http://schema.org/ContactPoint"> %s </span>'
						, 'properties' => array(
							  'contacttype8' => array(
								  'label' => __('Contact Type', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-contact-type8">Contact Type: <span itemprop="contactType"> %s </span></span>'
							),'telephone8' => array(
								  'label' => __('Phone Number', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-tel-number8">Phone Number: <span itemprop="telephone"> %s </span></span>'
							)
						)
					),'contactpoint9' => array(
						  'label' => __('Contact Point', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets organization-contact-point9" itemprop="ContactPoint" itemscope itemtype="http://schema.org/ContactPoint"> %s </span>'
						, 'properties' => array(
							  'contacttype9' => array(
								  'label' => __('Contact Type', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-contact-type9">Contact Type: <span itemprop="contactType"> %s </span></span>'
							),'telephone9' => array(
								  'label' => __('Phone Number', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-tel-number9">Phone Number: <span itemprop="telephone"> %s </span></span>'
							)
						)
					),'contactpoint10' => array(
						  'label' => __('Contact Point', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets organization-contact-point10" itemprop="ContactPoint" itemscope itemtype="http://schema.org/ContactPoint"> %s </span>'
						, 'properties' => array(
							  'contacttype10' => array(
								  'label' => __('Contact Type', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-contact-type10">Contact Type: <span itemprop="contactType"> %s </span></span>'
							),'telephone10' => array(
								  'label' => __('Phone Number', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets organization-tel-number10">Phone Number: <span itemprop="telephone"> %s </span></span>'
							)
						)
					)
				)
			),'recipe' => array(
				  'label' => __('Recipe', 'seolat-tool-plus')
				, 'tags' => 'Recipe'
				, 'properties' => array(
					  'name' => array(
						  'label' => __('Recipe Name', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets recipe-name">Recipe Name: <span itemprop="name"> %s </span></span>'
					),'image' => array(
						  'label' => __('Recipe Image', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets recipe-image">Image: <a itemprop="image" href="%1$s"> %1$s </a></span>'
					),'reviewer' => array(
						  'label' => __('Review Author', 'seolat-tool-plus')
						, 'editable' => false
						, 'value_function' => 'get_the_author'
						, 'tags' => '<span class="rich-snippets recipe-author">Author: <span itemprop="author"> %s </span></span>'
					),'date_reviewed' => array(
						  'label' => __('Date Reviewed', 'seolat-tool-plus')
						, 'editable' => false
						, 'value_function' => array('get_the_time', 'Y-m-d')
						, 'tags' => '<span class="rich-snippets recipe-date">Review Date: <time itemprop="datePublished"> %s </time></span>'
					),'description' => array(
						  'label' => __('Recipe Description', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets recipe-description">Recipe Description: <span itemprop="description"> %s </span></span>'
					),'preptime' => array(
						  'label' => __('Prep Time', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets recipe-preptime">Preparation Time: <time itemprop="prepTime"> PT%s </time></span>'
					),'cooktime' => array(
						  'label' => __('Cook Time', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets recipe-cooktime">Cook Time: <time itemprop="cookTime"> PT%s </time></span>'
					),'totaltime' => array(
						  'label' => __('Total Time', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets recipe-totaltime">Total Time: <time itemprop="totalTime"> PT%s </time></span>'
					),'yield' => array(
						  'label' => __('Yield', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets recipe-yield">Yield: <span itemprop="recipeYield"> %s </span></span>'
					),'nutrition' => array(
						  'label' => __('Nutrition', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets recipe-nutrition" itemprop="nutrition" itemscope itemtype="http://schema.org/NutritionInformation"> %s </span>'
						, 'properties' => array(
							  'servingsize' => array(
								  'label' => __('Serving Size', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets recipe-servingsize">Serving Size: <span itemprop="servingSize"> %s </span></span>'
							),'calories' => array(
								  'label' => __('Calories', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets recipe-calories">Calories: <span itemprop="calories"> %s </span></span>'
							),'fatcontent' => array(
								  'label' => __('Fat', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets recipe-fatcontent">Fat: <span itemprop="fatContent"> %s </span></span>'
							)
						)
					),'ingredients' => array(
						  'label' => __('Ingredients', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets recipe-ingredients">Ingredients: <span itemprop="ingredients"> %s </span></span>'
					),'directions' => array(
						  'label' => __('Directions', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets recipe-directions">Directions: <span itemprop="recipeInstructions"> %s </span></span>'
					),'aggregate' => array(
						  'label' => __('Recipe Ratting Values', 'seolat-tool-plus')
						, 'tags' => '<span itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating"> %s </span></span>'
						, 'properties' => array(
							  'rating' => array(
								  'label' => __('Rating Value', 'seolat-tool-plus')
								, 'tags' =>   '<meta itemprop="worstRating" content="0" />'
											. '<span class="rich-snippets recipe-rating">Rating Value:<span itemprop="ratingValue"> %s </span></span>'
											. '<meta itemprop="bestRating" content="5" />'
							),'reviewcount' => array(
								  'label' => __('Total Reviews', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets recipe-reviewcount">Total Reviews: <span itemprop="reviewCount"> %s </span> Reviews</span>'
							)
						)
					)
				)
			),'software' => array(
				  'label' => __('Software', 'seolat-tool-plus')
				, 'tags' => 'SoftwareApplication'
				, 'properties' => array(
					  'name' => array(
						  'label' => __('Software Name', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets software-name">Software Name: <span itemprop="name"> %s </span></span>'
					),'description' => array(
						  'label' => __('Software Description', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets software-description">Software Description: <span itemprop="description"> %s </span></span>'
					),'image' => array(
						  'label' => __('Software Image', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets software-image">Software Image: <a itemprop="image" src="%1$s"> %1$s </a></span>'
					),'reviewer' => array(
						  'label' => __('Review Author', 'seolat-tool-plus')
						, 'editable' => false
						, 'value_function' => 'get_the_author'
						, 'tags' => '<span class="rich-snippets software-author">Author: <span itemprop="author"> %s </span></span>'
					),'date_reviewed' => array(
						  'label' => __('Date Reviewed', 'seolat-tool-plus')
						, 'editable' => false
						, 'value_function' => array('get_the_time', 'Y-m-d')
						, 'tags' => '<span class="rich-snippets software-date">Review Date: <time itemprop="datePublished"> %s </time></span>'
					),'version' => array(
						  'label' => __('Software Version', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets software-version">Software Version: <span itemprop="softwareVersion"> %s </span></span>'
					),'os' => array(
						  'label' => __('Operating System', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets software-os">Operating System: <span itemprop="operatingSystem"> %s </span></span>'
					),'offers' => array(
						  'label' => __('Offer', 'seolat-tool-plus')
						, 'tags' => '<span itemprop="offers" itemscope itemtype="http://schema.org/Offer"> %s </span></span>'
						, 'properties' => array(
							'price' => array(
							  'label' => __('Software Price', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets software-price">Software Price: <span itemprop="price"> %s </span></span>'
						),	'price_currency' => array(
							  'label' => __('Price Currency', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets software-price-currency">Price Currency: <span itemprop="priceCurrency"> %s </span></span>'
							)
						)
					),'appcategory' => array(
						  'label' => __('Application Category', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets app-category">Application Category:<link itemprop="applicationCategory" href="%1$s"> %1$s </link></span>'
					),'aggregate' => array(
						  'label' => __('Software Ratting Values', 'seolat-tool-plus')
						, 'tags' => '<span itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating"> %s </span>'
						, 'properties' => array(
							  'rating' => array(
								  'label' => __('Rating Value', 'seolat-tool-plus')
								, 'tags' =>   '<meta itemprop="worstRating" content="0" />'
											. '<span class="rich-snippets software-rating">Rating Value:<span itemprop="ratingValue"> %s </span></span>'
											. '<meta itemprop="bestRating" content="5" />'
							),'reviewcount' => array(
								  'label' => __('Total Reviews', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets software-reviewcount">Total Reviews: <span itemprop="reviewCount"> %s </span> Reviews</span>'
							)
						)
					)
				)
			),'video' => array(
				  'label' => __('Video', 'seolat-tool-plus')
				, 'tags' => 'VideoObject'
				, 'properties' => array(
					  'name' => array(
						  'label' => __('Video Name', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets video-name">Video Name: <span itemprop="name"> %s </span></span>'
					),'description' => array(
						  'label' => __('Video Description', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets video-description">Video Description: <span itemprop="description"> %s </span></span>'
					),'thumbnailurl' => array(
						  'label' => __('Video Thumbnail Image', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets video-thumbnailurl">Video Thumbnail Image: <a itemprop="thumbnailUrl" href="%1$s"> %1$s </a></span>'
					),'reviewer' => array(
						  'label' => __('Review Author', 'seolat-tool-plus')
						, 'editable' => false
						, 'value_function' => 'get_the_author'
						, 'tags' => '<span class="rich-snippets video-author">Video Author: <span itemprop="author"> %s </span></span>'
					),'date_reviewed' => array(
						  'label' => __('Date Reviewed', 'seolat-tool-plus')
						, 'editable' => false
						, 'value_function' => array('get_the_time', 'Y-m-d')
						, 'tags' => '<span class="rich-snippets video-date">Review Date: <time itemprop="datePublished"> %s </time></span>'
					),'upload_date' => array(
						  'label' => __('Upload Date', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets upload-date">Upload Date: <span itemprop="uploadDate"> %s </span></span>'
					),'contenturl' => array(
						  'label' => __('Video URL', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets video-contenturl">Video URL: <a itemprop="contentURL" href="%1$s"> %1$s </a></span>'
					),'embedurl' => array(
						  'label' => __('Embed URL', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets video-embedurl">Embed URL: <a itemprop="embedURL"> %s </a></span>'
					)
				)
				
			),'event' => array(
				  'label' => __('Event', 'seolat-tool-plus')
				, 'tags' => 'Event'
				, 'type' => 'Event'
				, 'properties' => array(
					  'name' => array(
						  'label' => __('Event Name', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets event-name">Event Name: <span itemprop="name"> %s </span></span>'
					),'description' => array(
						  'label' => __('Event Description', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets event-description">Event Description: <span itemprop="description"> %s </span></span>'
					),'url' => array(
							  'label' => __('Event URL', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets event-url">Event URL: <a itemprop="url" href="%1$s"> %1$s </a></span>'
					),'image' => array(
						  'label' => __('Event Image', 'seolat-tool-plus')
						, 'tags'=> '<span class="rich-snippets event-image">Event Image:  <a itemprop="image" href="%1$s"> %1$s </a></span>'
					),'status' => array(
						  'label' => __('Event Status', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets event-status">Event Status: <span itemprop="eventStatus"> %s </span></span>'
					),'startdate' => array(
						  'label' => __('Start Date', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets event-startdate">Start Date: <span itemprop="startDate"> %s </span></span>'
					),'enddate' => array(
						  'label' => __('End Date', 'seolat-tool-plus')
						, 'tags' => '<span class="rich-snippets event-enddate">End Date: <time itemprop="endDate"> %s </time></span>'
				    ),'location' => array(
						  'label' => __('Event Location', 'seolat-tool-plus')
						, 'tags' => '<span itemprop="location" itemscope itemtype="http://schema.org/Place"> %s </span>'
						, 'properties' => array (
							 'name' => array(
								  'label' => __('Event Location', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets event-location-name">Event Location: <span itemprop="name"> %s </span></span>'
							),'address' => array (
								  'label' => __('Event Address', 'seolat-tool-plus')
								, 'tags'=> '<span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress"> %s </span>'
								, 'properties' => array (
									 'street' => array (
										  'label' => __('Street Address', 'seolat-tool-plus')
										, 'tags'=> '<span class="rich-snippets event-location-address">Street Address: <span itemprop="streetAddress"> %s </span></span>'
									),'city' => array(
										  'label' => __('City', 'seolat-tool-plus')
										, 'tags' => '<span class="rich-snippets event-city">City: <span itemprop="addressLocality"> %s </span></span>'
									), 'state' => array(
										  'label' => __('State', 'seolat-tool-plus')
										, 'tags' => '<span class="rich-snippets event-state">State: <span itemprop="addressRegion"> %s </span></span>'
									),'postal_code' => array(
										  'label' => __('Postal Code', 'seolat-tool-plus')
										, 'tags' => '<span class="rich-snippets event-postal-code">Postal Code: <span itemprop="postalCode"> %s </span></span>'
									)
								)
							)
						)
					),'offers' => array(
						  'label' => __('Offer', 'seolat-tool-plus')
						, 'tags' => '<span itemprop="offers" itemscope itemtype="http://schema.org/Offer"> %s </span>'
						, 'properties' => array(
							'availability' => array(
							  'label' => __('Ticket Availability', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets event-ticket-availability">Ticket Availability: <span itemprop="availability"> %s </span></span>'
						),	'inventorylevel' => array(
							  'label' => __('Ticket In Stock', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets event-ticket-inventory-level">Ticket In Stock : <span itemprop="inventoryLevel"> %s </span></span>'
						),	'availabilitystarts' => array(
							  'label' => __('Ticket Available From', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets event-ticket-availability-starts">Ticket Available From: <span itemprop="availabilityStarts"> %s </span></span>'
						),	'price' => array(
							  'label' => __('Ticket Price', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets event-ticket-price">Ticket Price: <span itemprop="price"> %s </span></span>'
						),	'price_currency' => array(
							  'label' => __('Price Currency', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets event-ticket-price-currency">Price Currency: <span itemprop="priceCurrency"> %s </span></span>'
						),	'url' => array(
							  'label' => __('Ticket URL', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets ticket-url">Ticket URL: <a itemprop="url" href="%1$s"> %1$s </a></span>'
							)
						)
					)
				)
			),'article' => array(
				  'label' => __('Article', 'seolat-tool-plus')
				, 'type' => 'Article'
				, 'tags' => 'Article'
				, 'content_tags' => '<span class="article-rich-snippets article-meta"> <meta itemscope itemprop="mainEntityOfPage"  itemType="https://schema.org/WebPage" itemid="https://google.com/article"/> %s </span>'
				, 'properties' => array(
					  'meta' => array(
								'label' => __('Article Meta', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets article-meta"> <meta itemscope itemprop="mainEntityOfPage"  itemType="https://schema.org/WebPage" itemid="https://google.com/article"/></span>'
						),'headline' => array(
							  'label' => __('Article Headline', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets article-headline">Article Headline: <span itemprop="headline"> %s </span></span>'
						),'description' => array(
							  'label' => __('Article Description', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets article-description">Article Description: <span itemprop="description"> %s </span></span>'
						),'publisheddate' => array(
							  'label' => __('Publish Date', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets article-publish-date">Published Date: <meta itemprop="datePublished" content="%1$s"/> %s </span>'
						),'modifieddate' => array(
							  'label' => __('Modified Date', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets article-modified-date">Modified Date: <meta itemprop="dateModified" content="%1$s"/> %s </span>'
						),'author' => array(
							  'label' => __('Author', 'seolat-tool-plus')
							, 'tags' => '<span itemprop="author" itemscope itemtype="http://schema.org/Person"> %s </span>'
							, 'properties' => array(
								'name' => array(
								  'label' => __('Author Name', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets article-author-name">Author Name: <span itemprop="name"> %1$s </span></span>'
								)
							)
						),'image' => array(
							  'label' => __('Article Image', 'seolat-tool-plus')
							, 'tags' => '<span itemprop="image" itemscope itemtype="http://schema.org/ImageObject"> %s </span>'
							, 'properties' => array(
							//	'src' => array(
							//		 'label' => __('Image Source', 'seolat-tool-plus')
							//	  , 'tags' => '<span class="rich-snippets article-image-src">Image Source: <img src="%1$s"/></span>'
							//), 
							'url' => array(
									 'label' => __('Image URL', 'seolat-tool-plus')
								   , 'tags' => '<span class="rich-snippets article-image-url">Image URL: <meta itemprop="url" content="%1$s"> %s </span>'
							), 'width' => array(
									 'label' => __('Image Width', 'seolat-tool-plus')
								   , 'tags' => '<span class="rich-snippets article-image-width">Image Width: <meta itemprop="width" content="%1$s"> %s </span>'
							), 'height' => array(
									 'label' => __('Image height', 'seolat-tool-plus')
								   , 'tags' => '<span class="rich-snippets article-image-height">Image Height: <meta itemprop="height" content="%1$s"> %s </span>'
								)
							)
						),'publisher' => array(
							  'label' => __('Article Pubisher', 'seolat-tool-plus')
							, 'tags' => '<span itemprop="publisher" itemscope itemtype="http://schema.org/Organization"> %s </span>'
							, 'properties' => array(
								'name' => array (
									'label' => __('Publisher Name', 'seolat-tool-plus')
								  , 'tags' => '<span class="rich-snippets article-publisher-name">Publisher Name: <meta itemprop="name" content="%1$s"> %s </span>'
							  ),'logo' => array(
									'label' => __('Pubisher Logo', 'seolat-tool-plus')
								  , 'tags' => '<span itemprop="logo" itemscope itemtype="http://schema.org/ImageObject"> %1$s </span>'
										, 'properties' => array(
											//'src' => array(
											//	 'label' => __('Logo Source', 'seolat-tool-plus')
											//   , 'tags' => '<span class="rich-snippets article-logo-src">Logo Source: <img src="%1$s"/></span>'
											//)
										  'url' => array(
												 'label' => __('Logo URL', 'seolat-tool-plus')
											   , 'tags' => '<span class="rich-snippets article-logo-url">Logo URL: <meta itemprop="url" content="%1$s"> %s </span>'
										), 'width' => array(
												 'label' => __('Logo Width', 'seolat-tool-plus')
											   , 'tags' => '<span class="rich-snippets article-logo-width">Logo Width: <meta itemprop="width" content="%1$s"> %s </span>'
										), 'height' => array(
												 'label' => __('Logo height', 'seolat-tool-plus')
											   , 'tags' => '<span class="rich-snippets article-logo-height">Logo Height: <meta itemprop="height" content="%1$s"> %s </span>'
											)
										)
									)
								)
							)
						)
					),'newsarticle' => array(
				  'label' => __('News Article', 'seolat-tool-plus')
				, 'type' => 'News Article'
				, 'tags' => 'NewsArticle'
				, 'content_tags' => '<span class="news-article-rich-snippets news-article-meta"> <meta itemscope itemprop="mainEntityOfPage"  itemType="https://schema.org/WebPage" itemid="https://google.com/article"/> %s </span>'
				, 'properties' => array(
					  'meta' => array(
								'label' => __('Article Meta', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets news-article-meta"> <meta itemscope itemprop="mainEntityOfPage"  itemType="https://schema.org/WebPage" itemid="https://google.com/article"/></span>'
						),'headline' => array(
								  'label' => __('Article Headline', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets news-article-headline">News Headline: <span itemprop="headline"> %s </span></span>'
						),'description' => array(
							  'label' => __('News Description', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets news-article-description">Article Description: <span itemprop="description"> %s </span></span>'
						),'publisheddate' => array(
							  'label' => __('Publish Date', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets news-article-publish-date">Published Date: <meta itemprop="datePublished" content="%1$s"/> %s </span>'
						),'modifieddate' => array(
							  'label' => __('Modified Date', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets news-article-modified-date">Modified Date: <meta itemprop="dateModified" content="%1$s"/> %s </span>'
						),'author' => array(
							  'label' => __('Author', 'seolat-tool-plus')
							, 'tags' => '<span itemprop="author" itemscope itemtype="http://schema.org/Person"> %s </span>'
							, 'properties' => array(
								'name' => array(
								  'label' => __('Author Name', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets news-article-author-name">Author Name: <span itemprop="name"> %1$s </span></span>'
								)
							)
						),'image' => array(
							  'label' => __('Article Image', 'seolat-tool-plus')
							, 'tags' => '<span itemprop="image" itemscope itemtype="http://schema.org/ImageObject"> %s </span>'
							, 'properties' => array(
								//'src' => array(
								//	 'label' => __('Image Source', 'seolat-tool-plus')
								//   , 'tags' => '<span class="rich-snippets news-article-image-src">Image Source: <img src="%1$s"/></span>'
								//), 
							'url' => array(
									 'label' => __('Image URL', 'seolat-tool-plus')
								   , 'tags' => '<span class="rich-snippets news-article-image-url">Image URL: <meta itemprop="url" content="%1$s"> %s </span>'
							), 'width' => array(
									 'label' => __('Image Width', 'seolat-tool-plus')
								   , 'tags' => '<span class="rich-snippets news-article-image-width">Image Width: <meta itemprop="width" content="%1$s"> %s </span>'
							), 'height' => array(
									 'label' => __('Image height', 'seolat-tool-plus')
								   , 'tags' => '<span class="rich-snippets news-article-image-height">Image Height: <meta itemprop="height" content="%1$s"> %s </span>'
								)
							)
						),'publisher' => array(
							  'label' => __('Article Pubisher', 'seolat-tool-plus')
							, 'tags' => '<span itemprop="publisher" itemscope itemtype="http://schema.org/Organization"> %s </span>'
							, 'properties' => array(
								'name' => array (
									'label' => __('Publisher Name', 'seolat-tool-plus')
								  , 'tags' => '<span class="rich-snippets news-article-publisher-name">Publisher Name: <meta itemprop="name" content="%1$s"> %s </span>'
							  ),'logo' => array(
									  'label' => __('Pubisher Logo', 'seolat-tool-plus')
									, 'tags' => '<span itemprop="logo" itemscope itemtype="http://schema.org/ImageObject"> %1$s </span>'
									, 'properties' => array(
										//'src' => array(
										//	 'label' => __('Logo Source', 'seolat-tool-plus')
										//   , 'tags' => '<span class="rich-snippets news-article-logo-src">Logo Source: <img src="%1$s"/></span>'
										//), 
										'url' => array(
												 'label' => __('Logo URL', 'seolat-tool-plus')
											   , 'tags' => '<span class="rich-snippets news-article-logo-url">Logo URL: <meta itemprop="url" content="%1$s"> %s </span>'
										), 'width' => array(
												 'label' => __('Logo Width', 'seolat-tool-plus')
											   , 'tags' => '<span class="rich-snippets news-article-logo-width">Logo Width: <meta itemprop="width" content="%1$s"> %s </span>'
										), 'height' => array(
												 'label' => __('Logo height', 'seolat-tool-plus')
											   , 'tags' => '<span class="rich-snippets news-article-logo-height">Logo Height: <meta itemprop="height" content="%1$s"> %s </span>'
											)
										)
									)
								)
							)
						)
					),'blogposting' => array(
				  'label' => __('Blog Posting', 'seolat-tool-plus')
				, 'type' => 'Blog Posting'
				, 'tags' => 'BlogPosting'
				, 'content_tags' => '<span class="blog-posting-rich-snippets blog-posting-meta"> <meta itemscope itemprop="mainEntityOfPage"  itemType="https://schema.org/WebPage" itemid="https://google.com/article"/> %s </span>'
				, 'properties' => array(
					  'meta' => array(
								'label' => __('Article Meta', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets blog-posting-meta"> <meta itemscope itemprop="mainEntityOfPage"  itemType="https://schema.org/WebPage" itemid="https://google.com/article"/></span>'
						),'headline' => array(
								  'label' => __('Blog Headline', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets blog-posting-headline">Blog Headline: <span itemprop="headline"> %s </span></span>'
							),'description' => array(
								  'label' => __('Blog Description', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets blog-posting-description">Blog Description: <span itemprop="description"> %s </span></span>'
							),'publisheddate' => array(
								  'label' => __('Publish Date', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets blog-posting-publish-date">Published Date: <meta itemprop="datePublished" content="%1$s"/> %s </span>'
							),'modifieddate' => array(
								  'label' => __('Modified Date', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets blog-posting-modified-date">Modified Date: <meta itemprop="dateModified" content="%1$s"/> %s </span>'
							),'author' => array(
								  'label' => __('Author', 'seolat-tool-plus')
								, 'tags' => '<span itemprop="author" itemscope itemtype="http://schema.org/Person"> %s </span>'
								, 'properties' => array(
									'name' => array(
									  'label' => __('Author Name', 'seolat-tool-plus')
									, 'tags' => '<span class="rich-snippets blog-posting-author-name">Author Name: <span itemprop="name"> %1$s </span></span>'
									)
								)
							),'image' => array(
								  'label' => __('Article Image', 'seolat-tool-plus')
								, 'tags' => '<span itemprop="image" itemscope itemtype="http://schema.org/ImageObject"> %s </span>'
								, 'properties' => array(
									//'src' => array(
									//	 'label' => __('Image Source', 'seolat-tool-plus')
									//   , 'tags' => '<span class="rich-snippets blog-posting-image-src">Image Source: <img src="%1$s"/></span>'
									//),
									'url' => array(
										 'label' => __('Image URL', 'seolat-tool-plus')
									   , 'tags' => '<span class="rich-snippets blog-posting-image-url">Image URL: <meta itemprop="url" content="%1$s"> %s </span>'
								), 'width' => array(
										 'label' => __('Image Width', 'seolat-tool-plus')
									   , 'tags' => '<span class="rich-snippets blog-posting-image-width">Image Width: <meta itemprop="width" content="%1$s"> %s </span>'
								), 'height' => array(
										 'label' => __('Image height', 'seolat-tool-plus')
									   , 'tags' => '<span class="rich-snippets blog-posting-image-height">Image Height: <meta itemprop="height" content="%1$s"> %s </span>'
									)
								)
							),'publisher' => array(
								  'label' => __('Article Pubisher', 'seolat-tool-plus')
								, 'tags' => '<span itemprop="publisher" itemscope itemtype="http://schema.org/Organization"> %s </span>'
								, 'properties' => array(
									'name' => array (
										'label' => __('Publisher Name', 'seolat-tool-plus')
									  , 'tags' => '<span class="rich-snippets blog-posting-publisher-name">Publisher Name: <meta itemprop="name" content="%1$s"> %s </span>'
								  ),
									'logo' => array(
										  'label' => __('Pubisher Logo', 'seolat-tool-plus')
										, 'tags' => '<span itemprop="logo" itemscope itemtype="http://schema.org/ImageObject"> %1$s </span>'
										, 'properties' => array(
											//'src' => array(
											//	 'label' => __('Logo Source', 'seolat-tool-plus')
											//   , 'tags' => '<span class="rich-snippets blog-posting-logo-src">Logo Source: <img src="%1$s"/></span>'
											//), 
										'url' => array(
												 'label' => __('Logo URL', 'seolat-tool-plus')
											   , 'tags' => '<span class="rich-snippets blog-posting-logo-url">Logo URL: <meta itemprop="url" content="%1$s"> %s </span>'
										), 'width' => array(
												 'label' => __('Logo Width', 'seolat-tool-plus')
											   , 'tags' => '<span class="rich-snippets blog-posting-logo-width">Logo Width: <meta itemprop="width" content="%1$s"> %s </span>'
										), 'height' => array(
												 'label' => __('Logo height', 'seolat-tool-plus')
											   , 'tags' => '<span class="rich-snippets blog-posting-logo-height">Logo Height: <meta itemprop="height" content="%1$s"> %s </span>'
											)
										)
									)
								)
							)
						)
					),'course' => array(
				  'label' => __('Course', 'seolat-tool-plus')
				, 'type' => 'Course'
				, 'tags' => 'Course'
				, 'properties' => array(
					  'meta' => array(
							'label' => __('Course Meta', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets course-meta"> <meta itemscope itemprop="mainEntityOfPage"  itemType="https://schema.org/Course" itemid="https://google.com/article"/></span>'
						),'name' => array(
							  'label' => __('Course Name', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets course-name">Course Name: <span itemprop="name"> %s </span></span>'
						),'description' => array(
							  'label' => __('Course Description', 'seolat-tool-plus')
							, 'tags' => '<span class="rich-snippets course-description">Course Description: <span itemprop="description"> %s </span></span>'
						),'provider' => array(
							  'label' => __('Provider', 'seolat-tool-plus')
							, 'tags' => '<span itemprop="provider" itemscope itemtype="https://schema.org/Organization"> %s </span>'
							, 'properties' => array(
								'name' => array(
								  'label' => __('Organization Name', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets course-organization-name">Organization Name: <span itemprop="name"> %1$s </span></span>'
							 ),'organizationurl' => array(
								'label' => __('Organization Url', 'seolat-tool-plus')
								, 'tags' => '<span class="rich-snippets course-organization-url">Organization Url: <a itemprop="sameAs" href="%1$s"> Organization </a></span>'
								)
							)
						)
					)
				)
			);
		}
	
	function add_tags($content, $tags, $template, $escape=true) {
		if ($escape) $content = sl_esc_attr($content);
		$tags = array_reverse((array)$tags);
		
		foreach ($tags as $tag) {
			if (lat_string::startswith($tag, '<'))
				$content = sprintf($tag, $content);
			else
				$content = sprintf($template, $tag, $content);
		}

		return $content;
	}

	function apply_markup($content) {
		
		//Single items only
		if (!is_singular() || !in_the_loop()) return $content;
		
		//Get the current type
		$type = $this->get_postmeta('rich_snippet_type');
		if (!strlen($type) || $type == 'none') return $content;
		
		//Get the current format
		$format = 'so';
		
		//Get tag templates for the current format
		$formats = $this->get_supported_snippet_formats();
		
		//Get data for the current type
		$types = $this->get_supported_snippet_types();
		$type_data = $types[$type];
		
		//Cycle through the current type's properties
		$append = '';
		$num_properties = 0;
		$supervalue_regex = '';
		foreach ($type_data['properties'] as $property => $property_data) {
			
			//Get the property tags
			$tag = is_array($property_data['tags']) ?
							$property_data['tags'][$format] :
							$property_data['tags'];
			
			if (isset($property_data['hidden_tags'])) {
				$hidden_tag = is_array($property_data['hidden_tags']) ?
								$property_data['hidden_tags'][$format] :
								$property_data['hidden_tags'];
			} else
				$hidden_tag = $tag;
			
			
			if (isset($property_data['properties']) && is_array($property_data['properties']) && count($property_data['properties'])) {
				
				$subproperty_regex_pieces = array();
				$subproperty_hidden_markedup_values = array();
				foreach ($property_data['properties'] as $subproperty => $subproperty_data) {
					
					//Get the subproperty tags
					$subproperty_tag = is_array($subproperty_data['tags']) ?
										$subproperty_data['tags'][$format] :
										$subproperty_data['tags'];
					
					if (isset($subproperty_data['hidden_tags'])) {
						$subproperty_hidden_tag = is_array($subproperty_data['hidden_tags']) ?
													$subproperty_data['hidden_tags'][$format] :
													$subproperty_data['hidden_tags'];
					} else
						$subproperty_hidden_tag = $subproperty_tag;
						
	
					
					$subproperty_value = strval($this->get_postmeta("rich_snippet_{$type}_{$property}_{$subproperty}"));
					if ($subproperty_value) {
						$subproperty_hidden_markedup_values[] = $this->add_tags($subproperty_value, $subproperty_hidden_tag, $formats[$format]['hidden_property_tags_template']);
						$subproperty_regex_pieces[] = lat_string::preg_escape($subproperty_value);
					}
				}
				/*$supervalue_regex = implode('(<br ?/?>|\s|,)*?', $subproperty_regex_pieces);*/
				$supervalue_regex = implode('|', $subproperty_regex_pieces);
				$supervalue_regex = "($supervalue_regex)";
				
				if (is_array($subproperty_regex_pieces) && count($subproperty_regex_pieces)) {
					$supervalue_regex = array_fill(0, count($subproperty_regex_pieces), $supervalue_regex);
				}
				else{
					$supervalue_regex = array();
				}
				
				$supervalue_regex = implode('(<br ?/?>|\s|.){0,0}', $supervalue_regex);
				
				$this->apply_subproperty_markup_args = array(
					  'format' => $format
					, 'type' => $type
					, 'property' => $property
					, 'property_tag' => $tag
					, 'property_tag_template' => $formats[$format]['property_tags_template']
					, 'subproperties' => $property_data['properties']
				);
				$count = 0;
				$content = preg_replace_callback("%({$supervalue_regex})%", array(&$this, 'apply_subproperty_markup'), $content, 1, $count);
				
				if ($count == 0) {
					if (count($subproperty_hidden_markedup_values)) {
						$append .= $this->add_tags(implode($subproperty_hidden_markedup_values), $tag, $formats[$format]['property_tags_template'], false);
						$num_properties++;
					}
				} else {
					$num_properties++;
				}
				
					// third level start
					if (isset($subproperty_data['properties']) && is_array($subproperty_data['properties']) && count($subproperty_data['properties'])) {
						
						$subsubproperty_regex_pieces = array();
						$subsubproperty_hidden_markedup_values = array();
						foreach ($subproperty_data['properties'] as $subsubproperty => $subsubproperty_data) {
							
							//Get the subproperty tags
							$subsubproperty_tag = is_array($subsubproperty_data['tags']) ?
												$subsubproperty_data['tags'][$format] :
												$subsubproperty_data['tags'];
							
							if (isset($subsubproperty_data['hidden_tags'])) {
								$subsubproperty_hidden_tag = is_array($subsubproperty_data['hidden_tags']) ?
															$subsubproperty_data['hidden_tags'][$format] :
															$subsubproperty_data['hidden_tags'];
							} else
								$subsubproperty_hidden_tag = $subsubproperty_tag;
								
								
							
							$subsubproperty_value = strval($this->get_postmeta("rich_snippet_{$type}_{$property}_{$subproperty}_{$subsubproperty}"));
							if ($subsubproperty_value) {
								$subsubproperty_hidden_markedup_values[] = $this->add_tags($subsubproperty_value, $subsubproperty_hidden_tag, $formats[$format]['hidden_property_tags_template']);
								$subsubproperty_regex_pieces[] = lat_string::preg_escape($subsubproperty_value);
							}
						}
						/*$supervalue_regex = implode('(<br ?/?>|\s|,)*?', $subsubproperty_regex_pieces);*/
						$supervalue_regex = implode('|', $subsubproperty_regex_pieces);
						$supervalue_regex = "($supervalue_regex)";
						
						if (is_array($subsubproperty_regex_pieces) && count($subsubproperty_regex_pieces)) {
							$supervalue_regex = array_fill(0, count($subsubproperty_regex_pieces), $supervalue_regex);
						}
						else{
							$supervalue_regex = array();
						}
						
						$supervalue_regex = implode('(<br ?/?>|\s|.){0,0}', $supervalue_regex);
						
						$this->apply_subsubproperty_markup_args = array(
								'format' => $format
							, 'type' => $type
							, 'subproperty' => $subproperty
							, 'subproperty_tag' => $subproperty_tag
							, 'subproperty_tag_template' => $formats[$format]['property_tags_template']
							, 'subsubproperties' => $subproperty_data['properties']
						);
						$subcount = 0;
						$content = preg_replace_callback("%({$supervalue_regex})%", array(&$this, 'apply_subsubproperty_markup'), $content, 1, $subcount);
						
						
						
						if ($subcount == 0) { 
							if (count($subsubproperty_hidden_markedup_values)) {
								//$append = preg_replace("#(.*?)</span>(.*)#s", "$1$2", $append, 1 );
								$append = preg_replace('/(<\/span>)+$/', "", $append);
								$append .= $this->add_tags(implode($subsubproperty_hidden_markedup_values), $subproperty_tag, $formats[$format]['property_tags_template'], false);
								$append .= '</span>';
								$num_properties++;
							}
						} else {
							$num_properties++;
						}
						
					}				
					// third level end
				
			} else {
				
				//Get the current value for this property
				$value = strval($this->get_postmeta("rich_snippet_{$type}_{$property}"));
				
				if (strlen($value)) {
					
					if (lat_string::startswith($value, 'obj_') && isset($property_data['jlsuggest']) && $property_data['jlsuggest'])
						$value = $this->jlsuggest_value_to_url($value, true);
					
				} else {
					
					//If a value is not set, look for a value-generating function
					if (isset($property_data['value_function'])) {
						$valfunc = (array)$property_data['value_function'];
						if (is_callable($valfunc[0])) {
							$valfunc_args = isset($valfunc[1]) ? (array)$valfunc[1] : array();
							$value = call_user_func_array($valfunc[0], $valfunc_args);
						}
					}
				}
				
				//If still no value, skip this property
				if (!strlen($value)) continue;
				
				//Add property tags to the value
				$markedup_value = $this->add_tags($value, $tag, $formats[$format]['property_tags_template']);
				$hidden_markedup_value = $this->add_tags($value, $hidden_tag, $formats[$format]['hidden_property_tags_template']);
				
				//Apply a value format to visible values if provided
				if (isset($property_data['value_format'])) {
					$values = array_values(lat_string::batch_replace('%s', $value, $property_data['value_format']));
					$markedup_values = array_values(lat_string::batch_replace('%s', $markedup_value, $property_data['value_format']));
				} else {
					$values = array($value);
					$markedup_values = array($markedup_value);
				}
				
				//Is the value in the content, and are we allowed to search/replace the content for this value?
				$count = 0;
				if (empty($property_data['always_hidden'])) {
					for ($i=1; $i<count($values); $i++) {
						$content = lat_string::htmlsafe_str_replace($values[$i], $markedup_values[$i], $content, 1, $count);
						if ($count > 0) break;
					}
				}
				
				if ($count == 0)
					$append .= $hidden_markedup_value;
				
				$num_properties++;
			}
		}
		
		if (isset($type_data['content_tags'])) {
			$content_tag = is_array($type_data['content_tags']) ?
				$type_data['content_tags'][$format] :
				$type_data['content_tags'];
			
			$content = $this->add_tags($content, $content_tag, $formats[$format]['property_tags_template'], false);
		}
		
		if ($num_properties) {
			$type_tag = is_array($type_data['tags']) ?
						$type_data['tags'][$format] :
						$type_data['tags'];
			$content = $this->add_tags("$content<div>$append</div>", $type_tag, $formats[$format]['item_tags_template'], false);
			
			if ($this->get_setting('mark_code', true, 'settings'))
				$content .= "\n\n<!-- " . sprintf(__('Schema.org markup generated by %1$s (%2$s)', 'seolat-tool-plus'), SL_PLUGIN_NAME, SL_PLUGIN_URI) . " -->\n\n";
		}
		
		//Return filtered content
		return $content;
	}
	
	function apply_subproperty_markup($matches) {
		
		if (empty($matches[200]))
			return '';
		
		$content = $matches[200];
		
		extract($this->apply_subproperty_markup_args, EXTR_SKIP);
		
		foreach ($subproperties as $subproperty => $subproperty_data) {
		
			//Get the subproperty tags
			$subproperty_tag = is_array($subproperty_data['tags']) ?
								$subproperty_data['tags'][$format] :
								$subproperty_data['tags'];
			
			$subproperty_value = strval($this->get_postmeta("rich_snippet_{$type}_{$property}_{$subproperty}"));
			
			if ($subproperty_value) {
				$subproperty_markedup_value = $this->add_tags($subproperty_value, $subproperty_tag, $property_tag_template);
				$content = lat_string::htmlsafe_str_replace($subproperty_value, $subproperty_markedup_value, $content, 1, $count);
			}
		}
		
		$content = $this->add_tags($content, $property_tag, $property_tag_format, false);
		
		return $content;
	}
	
	function apply_subsubproperty_markup($matches) {
		
		if (empty($matches[200]))
			return '';
		
		$content = $matches[200];
		
		extract($this->apply_subsubproperty_markup_args, EXTR_SKIP);
		
		foreach ($subsubproperties as $subsubproperty => $subsubproperty_data) {
		
			//Get the subsubproperty tags
			$subsubproperty_tag = is_array($subsubproperty_data['tags']) ?
								$subsubproperty_data['tags'][$format] :
								$subsubproperty_data['tags'];
			
			$subsubproperty_value = strval($this->get_postmeta("rich_snippet_{$type}_{$property}_{$subproperty}_{$subsubproperty}"));
			
			if ($subsubproperty_value) {
				$subsubproperty_markedup_value = $this->add_tags($subsubproperty_value, $subsubproperty_tag, $subproperty_tag_template);
				$content = lat_string::htmlsafe_str_replace($subsubproperty_value, $subsubproperty_markedup_value, $content, 1, $count);
			}
		}
		
		$content = $this->add_tags($content, $subproperty_tag, $subproperty_tag_format, false);
		
		return $content;
	}
	
	function postmeta_fields($fields, $screen) {
		$fields['serp'][30]['hide_rich_snippets'] = $this->get_postmeta_dropdown('hide_rich_snippets', array(
			  'global' => __('Global', 'seolat-tool-plus')
			, 'on' => __('Add Markup to Header and Page/Post', 'seolat-tool-plus')
			, 'off' => __('Add Markup to Header Only', 'seolat-tool-plus')
		), __(' Set Rich Snippets Visibility:', 'seolat-tool-plus'));
		
		$fields['serp'][40]['rich_snippet_type'] = $this->get_postmeta_dropdown('rich_snippet_type', array(
			  'none' => __('Standard', 'seolat-tool-plus')
			, 'article' => __('Article', 'seolat-tool-plus')
			, 'newsarticle' => __('News Article', 'seolat-tool-plus')
			, 'blogposting' => __('Blog Posting', 'seolat-tool-plus')
			, 'business' => __('Local Business', 'seolat-tool-plus')
			, 'course' => __('Course', 'seolat-tool-plus')
			, 'event' => __('Event', 'seolat-tool-plus')
			, 'organization' => __('Organization', 'seolat-tool-plus')
			, 'person' => __('Person', 'seolat-tool-plus')
			, 'product' => __('Product', 'seolat-tool-plus')
			, 'recipe' => __('Recipe', 'seolat-tool-plus')
			, 'review' => __('Review', 'seolat-tool-plus')			
			, 'software' => __('Software', 'seolat-tool-plus')
			, 'video' => __('Video', 'seolat-tool-plus')
		), __(' Rich Snippets Type:', 'seolat-tool-plus'));
		
	$fields['serp'][41]['rich_snippet_article_headline|rich_snippet_article_description|rich_snippet_article_publisheddate|rich_snippet_article_modifieddate|rich_snippet_article_author_name|rich_snippet_article_image_url|rich_snippet_article_image_width|rich_snippet_article_image_height|rich_snippet_article_publisher_name|rich_snippet_article_publisher_logo_url|rich_snippet_article_publisher_logo_width|rich_snippet_article_publisher_logo_height'] =
			$this->get_postmeta_subsection('rich_snippet_type', 'article',
				$this->get_postmeta_textbox('rich_snippet_article_headline', __('Article Headline: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Headline or Title For The Article.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textarea('rich_snippet_article_description', __('Article Description: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Description Of The Article.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_article_publisheddate', __('Published Date: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Put Article Published Date. Example, 2015-02-05T09:20:00+08:00</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_article_modifieddate', __('Modified Date: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Put Article Modified Date. Example, 2015-02-05T09:20:00+08:00</span></a>', 'seolat-tool-plus'), array('type' => 'text'))				
				. $this->get_postmeta_textbox('rich_snippet_article_author_name', __('Author Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Article Author Name.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_medialib_box('rich_snippet_article_image_url', __('Upload Image: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Upload Article Image.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_article_image_width', __('Image Width: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Image Width In PX.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_article_image_height', __('Image Height: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Image Height In PX.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_article_publisher_name', __('Publisher Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Give Publisher Name.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))		
				. $this->get_postmeta_medialib_box('rich_snippet_article_publisher_logo_url', __('Upload Logo: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Upload Publisher Logo.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_article_publisher_logo_width', __('Logo Width: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Publisher Logo Width In PX.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_article_publisher_logo_height', __('Logo Height: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Publisher Logo Height In PX.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);
			
	$fields['serp'][42]['rich_snippet_newsarticle_headline|rich_snippet_newsarticle_description|rich_snippet_newsarticle_publisheddate|rich_snippet_newsarticle_modifieddate|rich_snippet_newsarticle_author_name|rich_snippet_newsarticle_image_url|rich_snippet_newsarticle_image_width|rich_snippet_newsarticle_image_height|rich_snippet_newsarticle_publisher_name|rich_snippet_newsarticle_publisher_logo_url|rich_snippet_newsarticle_publisher_logo_width|rich_snippet_newsarticle_publisher_logo_height'] =
			$this->get_postmeta_subsection('rich_snippet_type', 'newsarticle',
				$this->get_postmeta_textbox('rich_snippet_newsarticle_headline', __('News Headline: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Headline or Title For The News.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textarea('rich_snippet_newsarticle_description', __('News Description: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Description Of The News.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_newsarticle_publisheddate', __('Published Date: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Put News Published Date. Example, 2015-02-05T09:20:00+08:00</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_newsarticle_modifieddate', __('Modified Date: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Put News Modified Date. Example, 2015-02-05T09:20:00+08:00</span></a>', 'seolat-tool-plus'), array('type' => 'text'))				
				. $this->get_postmeta_textbox('rich_snippet_newsarticle_author_name', __('Author Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>News Author Name.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_medialib_box('rich_snippet_newsarticle_image_url', __('Upload Image: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Upload News Image.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_newsarticle_image_width', __('Image Width: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Image Width In PX.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_newsarticle_image_height', __('Image Height: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Image Height In PX.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_newsarticle_publisher_name', __('Publisher Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Give Publisher Name.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_medialib_box('rich_snippet_newsarticle_publisher_logo_url', __('Upload Logo: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Upload Publisher Logo.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_newsarticle_publisher_logo_width', __('Logo Width: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Publisher Logo Width In PX.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_newsarticle_publisher_logo_height', __('Logo Height: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Publisher Logo Height In PX.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);

	$fields['serp'][43]['rich_snippet_blogposting_headline|rich_snippet_blogposting_description|rich_snippet_blogposting_publisheddate|rich_snippet_blogposting_modifieddate|rich_snippet_blogposting_author_name|rich_snippet_blogposting_image_url|rich_snippet_blogposting_image_width|rich_snippet_blogposting_image_height|rich_snippet_blogposting_publisher_name|rich_snippet_blogposting_publisher_logo_url|rich_snippet_blogposting_publisher_logo_width|rich_snippet_blogposting_publisher_logo_height'] =
			$this->get_postmeta_subsection('rich_snippet_type', 'blogposting',
				$this->get_postmeta_textbox('rich_snippet_blogposting_headline', __('Blog Headline: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Headline or Title For The Blog.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textarea('rich_snippet_blogposting_description', __('Blog Description: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Description Of The Blog.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_blogposting_publisheddate', __('Published Date: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Put Blog Published Date. Example, 2015-02-05T09:20:00+08:00</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_blogposting_modifieddate', __('Modified Date: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Put Blog Modified Date. Example, 2015-02-05T09:20:00+08:00</span></a>', 'seolat-tool-plus'), array('type' => 'text'))				
				. $this->get_postmeta_textbox('rich_snippet_blogposting_author_name', __('Author Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Blog Author Name.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_medialib_box('rich_snippet_blogposting_image_url', __('Upload Image: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Upload Blog Image.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_blogposting_image_width', __('Image Width: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Image Width In PX.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_blogposting_image_height', __('Image Height: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Image Height In PX.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_blogposting_publisher_name', __('Publisher Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Give Publisher Name.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))		
				. $this->get_postmeta_medialib_box('rich_snippet_blogposting_publisher_logo_url', __('Upload Logo: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Upload Publisher Logo.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_blogposting_publisher_logo_width', __('Logo Width: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Publisher Logo Width In PX.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_blogposting_publisher_logo_height', __('Logo Height: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Publisher Logo Height In PX.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);
		
		$fields['serp'][44]['rich_snippet_business_name|rich_snippet_business_image|rich_snippet_business_address_street|rich_snippet_business_address_po_box|rich_snippet_business_address_city|rich_snippet_business_address_state|rich_snippet_business_address_country|rich_snippet_business_address_postal_code|rich_snippet_business_map_url|rich_snippet_business_tel_number|rich_snippet_business_fax_number|rich_snippet_business_email|rich_snippet_business_website|rich_snippet_business_hours|rich_snippet_business_photo|rich_snippet_business_latitude|rich_snippet_business_longitude|rich_snippet_business_pricerange'] =
			$this->get_postmeta_subsection('rich_snippet_type', 'business',
				$this->get_postmeta_textbox('rich_snippet_business_name', __('Business Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Name of your Business.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_medialib_box('rich_snippet_business_image', __('Business Image: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>An image or logo of your Business.</span></a>', 'seolat-tool-plus'), 'types=posttype_attachment&post_mime_type=image/*')
				. $this->get_postmeta_textbox('rich_snippet_business_address_street', __('Street Address: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The street address. For example, 1600 Amphitheatre Pkwy.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_business_address_po_box', __('Post Office Box Number: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The post office box number for PO box addresses.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_business_address_city', __('City: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The locality. For example, Mountain View.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_business_address_state', __('State or Region: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The region. For example, CA.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_business_address_country', __('Country: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The country. For example, USA.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_business_address_postal_code', __('Postal Code: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The postal code. For example, 94043.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_jlsuggest_box('rich_snippet_business_map_url', __('Map Page: <a class="tooltips" href="https://maps.google.com/" target="_blank"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>A URL to a map of the Business place. Click to open maps page in new tab.</span></a>', 'seolat-tool-plus'), 'types=posttype')
				. $this->get_postmeta_textbox('rich_snippet_business_tel_number', __('Phone Number: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The telephone number.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_business_fax_number', __('Fax Number: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The fax number.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_business_email', __('Business Email: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Email address.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_business_website', __('Business Website: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>URL of the website.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_business_hours', __('Business Hours: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The Business hours. For example, Mon-Sat 11am - 2:30pm.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_medialib_box('rich_snippet_business_photo', __('Photo: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>A photograph of your Business.</span></a>', 'seolat-tool-plus'), 'types=posttype_attachment&post_mime_type=image/*')
				. $this->get_postmeta_textbox('rich_snippet_business_pricerange', __('Price Range: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The Price Range of the Business. For example, $300.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);

		$fields['serp'][45]['rich_snippet_event_name|rich_snippet_event_description|rich_snippet_event_url|rich_snippet_event_image|rich_snippet_event_status|rich_snippet_event_startdate|rich_snippet_event_enddate|rich_snippet_event_location_name|rich_snippet_event_location_address_street|rich_snippet_event_location_address_city|rich_snippet_event_location_address_state|rich_snippet_event_location_address_postal_code|rich_snippet_event_offers_availability|rich_snippet_event_offers_availabilitystarts|rich_snippet_event_offers_inventorylevel|rich_snippet_event_offers_price|rich_snippet_event_offers_price_currency|rich_snippet_event_offers_url'] =
			$this->get_postmeta_subsection('rich_snippet_type', 'event',
				$this->get_postmeta_textbox('rich_snippet_event_name', __('Event Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Name of the Event.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textarea('rich_snippet_event_description', __('Event Description: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Description of the Event.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				.$this->get_postmeta_textbox('rich_snippet_event_url', __('Event URL: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Event URL.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_medialib_box('rich_snippet_event_image', __('Event Image: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>An image of your Event.</span></a>', 'seolat-tool-plus'), 'types=posttype_attachment&post_mime_type=image/*')
				.$this->get_postmeta_textbox('rich_snippet_event_status', __('Event Status: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Current status of the Event. For example, use open, close, cancle.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_event_startdate', __('Start Date: <a class="tooltips" href="https://en.wikipedia.org/wiki/ISO_8601" target="_blank"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The start date and time of the item (in ISO 8601 date format). For example, [YYYY]-[MM]-[DD]. Click for more information.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_event_enddate', __('End Date: <a class="tooltips" href="https://en.wikipedia.org/wiki/ISO_8601" target="_blank"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The end date and time of the item (in ISO 8601 date format). For example, [YYYY]-[MM]-[DD]. Click for more information.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_event_location_name', __('Event Location: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The location of the event, organization or action.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_event_location_address_street', __('Street Address: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The street address. For example, 1600 Amphitheatre Pkwy.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_event_location_address_city', __('City: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The locality. For example, Mountain View.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_event_location_address_state', __('State: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The region. For example, CA.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_event_location_address_postal_code', __('Postal Code: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The postal code. For example, 94043.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_event_offers_availability', __('Ticket Availability: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Event with limited ticket availability or not. For example, instock or outofstock.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_event_offers_inventorylevel', __('Ticket In Stock : <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>How many tickets left in stock.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_event_offers_availabilitystarts', __('Ticket Available From: <a class="tooltips" href="https://en.wikipedia.org/wiki/ISO_8601" target="_blank"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span> Ticket available from which date (in ISO 8601 date format). For example, [YYYY]-[MM]-[DD]. Click for more information.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_event_offers_price', __('Ticket Price: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Price for the ticket. For example 150 (See Price Currency Below For Additional Options).</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_event_offers_price_currency', __('Price Currency: <a class="tooltips" href="https://en.wikipedia.org/wiki/ISO_4217" target="_blank"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The currency in which the monetary amount is expressed (in 3-letter ISO 4217 format). For example, USD. Click for more information.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_event_offers_url', __('Ticket URL: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Ticket purchase URL.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);
			
		$fields['serp'][46]['rich_snippet_organization_company_name|rich_snippet_organization_website_url|rich_snippet_organization_company_logo|rich_snippet_organization_facebook_url|rich_snippet_organization_twitter_url|rich_snippet_organization_instagram_url|rich_snippet_organization_linkedin_url|rich_snippet_organization_myspace_url|rich_snippet_organization_pinterest_url|rich_snippet_organization_youtube_url|rich_snippet_organization_google_plus_url|rich_snippet_organization_contactpoint0_contacttype0|rich_snippet_organization_contactpoint0_telephone0|rich_snippet_organization_contactpoint1_contacttype1|rich_snippet_organization_contactpoint1_telephone1|rich_snippet_organization_contactpoint2_contacttype2|rich_snippet_organization_contactpoint2_telephone2|rich_snippet_organization_contactpoint3_contacttype3|rich_snippet_organization_contactpoint3_telephone3|rich_snippet_organization_contactpoint4_contacttype4|rich_snippet_organization_contactpoint4_telephone4|rich_snippet_organization_contactpoint5_contacttype5|rich_snippet_organization_contactpoint5_telephone5|rich_snippet_organization_contactpoint6_contacttype6|rich_snippet_organization_contactpoint6_telephone6|rich_snippet_organization_contactpoint7_contacttype7|rich_snippet_organization_contactpoint7_telephone7|rich_snippet_organization_contactpoint8_contacttype8|rich_snippet_organization_contactpoint8_telephone8|rich_snippet_organization_contactpoint9_contacttype9|rich_snippet_organization_contactpoint9_telephone9|rich_snippet_organization_contactpoint10_contacttype10|rich_snippet_organization_contactpoint10_telephone10'] =
			$this->get_postmeta_subsection('rich_snippet_type', 'organization',
				$this->get_postmeta_textbox('rich_snippet_organization_company_name', __('Company Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Name of the Company.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_organization_website_url', __('Website URL: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your Company website URL.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_medialib_box('rich_snippet_organization_company_logo', __('Company Logo: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your Company Logo.</span></a>', 'seolat-tool-plus'), 'types=posttype_attachment&post_mime_type=image/*')
				. $this->get_postmeta_textbox('rich_snippet_organization_facebook_url', __('Facebook URL: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Company&#39;s Facebook URL.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_organization_twitter_url', __('Twitter URL: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Company&#39;s Twitter URL.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_organization_instagram_url', __('Instagram URL: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Company&#39;s Instagram URL.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_organization_linkedin_url', __('LinkedIn URL: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Company&#39;s LinkedIn URL.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_organization_myspace_url', __('MySpace URL: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Company&#39;s MySpace URL.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_organization_pinterest_url', __('Pinterest URL: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Company&#39;s Pinterest URL.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_organization_youtube_url', __('YouTube URL: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Company&#39;s YouTube URL.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_organization_google_plus_url', __('Google&#43 URL: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Company&#39;s Google+ URL.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_dropdown('rich_snippet_organization_contactpoint0_contacttype0', array(
					  '' => __('Choose Your Contact Type', 'seolat-tool-plus')
					, 'customer service' => __('Customer Service', 'seolat-tool-plus')
					, 'technical support' => __('Technical Support', 'seolat-tool-plus')
					, 'billing support' => __('Billing Support', 'seolat-tool-plus')
					, 'bill payment' => __('Bill Payment', 'seolat-tool-plus')
					, 'sales' => __('Sales', 'seolat-tool-plus')
					, 'reservations' => __('Reservations', 'seolat-tool-plus')
					, 'credit card support' => __('Credit Card Support', 'seolat-tool-plus')			
					, 'emergency' => __('Emergency', 'seolat-tool-plus')
					, 'baggage tracking' => __('Baggage Tracking', 'seolat-tool-plus')
					, 'roadside assistance' => __('Roadside Assistance', 'seolat-tool-plus')
					, 'package tracking' => __('Package Tracking', 'seolat-tool-plus')
				), __('Contact Type: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>What kind of contact type you are using for the Company. For example, Customer Service.</span></a>', 'seolat-tool-plus'))
				. $this->get_postmeta_textbox('rich_snippet_organization_contactpoint0_telephone0', __('Phone Number: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Use the phone number you are using for Company contact type.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_dropdown('rich_snippet_organization_contactpoint1_contacttype1', array(
					  '' => __('Choose Your Contact Type', 'seolat-tool-plus')
					, 'customer service' => __('Customer Service', 'seolat-tool-plus')
					, 'technical support' => __('Technical Support', 'seolat-tool-plus')
					, 'billing support' => __('Billing Support', 'seolat-tool-plus')
					, 'bill payment' => __('Bill Payment', 'seolat-tool-plus')
					, 'sales' => __('Sales', 'seolat-tool-plus')
					, 'reservations' => __('Reservations', 'seolat-tool-plus')
					, 'credit card support' => __('Credit Card Support', 'seolat-tool-plus')			
					, 'emergency' => __('Emergency', 'seolat-tool-plus')
					, 'baggage tracking' => __('Baggage Tracking', 'seolat-tool-plus')
					, 'roadside assistance' => __('Roadside Assistance', 'seolat-tool-plus')
					, 'package tracking' => __('Package Tracking', 'seolat-tool-plus')
				), __('Contact Type: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>What kind of contact type you are using for the Company. For example, Customer Service.</span></a>', 'seolat-tool-plus'))
				. $this->get_postmeta_textbox('rich_snippet_organization_contactpoint1_telephone1', __('Phone Number: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Use the phone number you are using for Company contact type.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))		
				. $this->get_postmeta_dropdown('rich_snippet_organization_contactpoint2_contacttype2', array(
					  '' => __('Choose Your Contact Type', 'seolat-tool-plus')
					, 'customer service' => __('Customer Service', 'seolat-tool-plus')
					, 'technical support' => __('Technical Support', 'seolat-tool-plus')
					, 'billing support' => __('Billing Support', 'seolat-tool-plus')
					, 'bill payment' => __('Bill Payment', 'seolat-tool-plus')
					, 'sales' => __('Sales', 'seolat-tool-plus')
					, 'reservations' => __('Reservations', 'seolat-tool-plus')
					, 'credit card support' => __('Credit Card Support', 'seolat-tool-plus')			
					, 'emergency' => __('Emergency', 'seolat-tool-plus')
					, 'baggage tracking' => __('Baggage Tracking', 'seolat-tool-plus')
					, 'roadside assistance' => __('Roadside Assistance', 'seolat-tool-plus')
					, 'package tracking' => __('Package Tracking', 'seolat-tool-plus')
				), __('Contact Type: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>What kind of contact type you are using for the Company. For example, Customer Service.</span></a>', 'seolat-tool-plus'))
				. $this->get_postmeta_textbox('rich_snippet_organization_contactpoint2_telephone2', __('Phone Number: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Use the phone number you are using for Company contact type.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_dropdown('rich_snippet_organization_contactpoint3_contacttype3', array(
					  '' => __('Choose Your Contact Type', 'seolat-tool-plus')
					, 'customer service' => __('Customer Service', 'seolat-tool-plus')
					, 'technical support' => __('Technical Support', 'seolat-tool-plus')
					, 'billing support' => __('Billing Support', 'seolat-tool-plus')
					, 'bill payment' => __('Bill Payment', 'seolat-tool-plus')
					, 'sales' => __('Sales', 'seolat-tool-plus')
					, 'reservations' => __('Reservations', 'seolat-tool-plus')
					, 'credit card support' => __('Credit Card Support', 'seolat-tool-plus')			
					, 'emergency' => __('Emergency', 'seolat-tool-plus')
					, 'baggage tracking' => __('Baggage Tracking', 'seolat-tool-plus')
					, 'roadside assistance' => __('Roadside Assistance', 'seolat-tool-plus')
					, 'package tracking' => __('Package Tracking', 'seolat-tool-plus')
				), __('Contact Type: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>What kind of contact type you are using for the Company. For example, Customer Service.</span></a>', 'seolat-tool-plus'))
				. $this->get_postmeta_textbox('rich_snippet_organization_contactpoint3_telephone3', __('Phone Number: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Use the phone number you are using for Company contact type.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_dropdown('rich_snippet_organization_contactpoint4_contacttype4', array(
					  '' => __('Choose Your Contact Type', 'seolat-tool-plus')
					, 'customer service' => __('Customer Service', 'seolat-tool-plus')
					, 'technical support' => __('Technical Support', 'seolat-tool-plus')
					, 'billing support' => __('Billing Support', 'seolat-tool-plus')
					, 'bill payment' => __('Bill Payment', 'seolat-tool-plus')
					, 'sales' => __('Sales', 'seolat-tool-plus')
					, 'reservations' => __('Reservations', 'seolat-tool-plus')
					, 'credit card support' => __('Credit Card Support', 'seolat-tool-plus')			
					, 'emergency' => __('Emergency', 'seolat-tool-plus')
					, 'baggage tracking' => __('Baggage Tracking', 'seolat-tool-plus')
					, 'roadside assistance' => __('Roadside Assistance', 'seolat-tool-plus')
					, 'package tracking' => __('Package Tracking', 'seolat-tool-plus')
				), __('Contact Type: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>What kind of contact type you are using for the Company. For example, Customer Service.</span></a>', 'seolat-tool-plus'))
				. $this->get_postmeta_textbox('rich_snippet_organization_contactpoint4_telephone4', __('Phone Number: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Use the phone number you are using for Company contact type.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_dropdown('rich_snippet_organization_contactpoint5_contacttype5', array(
					  '' => __('Choose Your Contact Type', 'seolat-tool-plus')
					, 'customer service' => __('Customer Service', 'seolat-tool-plus')
					, 'technical support' => __('Technical Support', 'seolat-tool-plus')
					, 'billing support' => __('Billing Support', 'seolat-tool-plus')
					, 'bill payment' => __('Bill Payment', 'seolat-tool-plus')
					, 'sales' => __('Sales', 'seolat-tool-plus')
					, 'reservations' => __('Reservations', 'seolat-tool-plus')
					, 'credit card support' => __('Credit Card Support', 'seolat-tool-plus')			
					, 'emergency' => __('Emergency', 'seolat-tool-plus')
					, 'baggage tracking' => __('Baggage Tracking', 'seolat-tool-plus')
					, 'roadside assistance' => __('Roadside Assistance', 'seolat-tool-plus')
					, 'package tracking' => __('Package Tracking', 'seolat-tool-plus')
				), __('Contact Type: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>What kind of contact type you are using for the Company. For example, Customer Service.</span></a>', 'seolat-tool-plus'))
				. $this->get_postmeta_textbox('rich_snippet_organization_contactpoint5_telephone5', __('Phone Number: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Use the phone number you are using for Company contact type.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_dropdown('rich_snippet_organization_contactpoint6_contacttype6', array(
					  '' => __('Choose Your Contact Type', 'seolat-tool-plus')
					, 'customer service' => __('Customer Service', 'seolat-tool-plus')
					, 'technical support' => __('Technical Support', 'seolat-tool-plus')
					, 'billing support' => __('Billing Support', 'seolat-tool-plus')
					, 'bill payment' => __('Bill Payment', 'seolat-tool-plus')
					, 'sales' => __('Sales', 'seolat-tool-plus')
					, 'reservations' => __('Reservations', 'seolat-tool-plus')
					, 'credit card support' => __('Credit Card Support', 'seolat-tool-plus')			
					, 'emergency' => __('Emergency', 'seolat-tool-plus')
					, 'baggage tracking' => __('Baggage Tracking', 'seolat-tool-plus')
					, 'roadside assistance' => __('Roadside Assistance', 'seolat-tool-plus')
					, 'package tracking' => __('Package Tracking', 'seolat-tool-plus')
				), __('Contact Type: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>What kind of contact type you are using for the Company. For Example, Customer Service.</span></a>', 'seolat-tool-plus'))
				. $this->get_postmeta_textbox('rich_snippet_organization_contactpoint6_telephone6', __('Phone Number: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Use the phone number you are using for Company contact type.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))		
				. $this->get_postmeta_dropdown('rich_snippet_organization_contactpoint7_contacttype7', array(
					  '' => __('Choose Your Contact Type', 'seolat-tool-plus')
					, 'customer service' => __('Customer Service', 'seolat-tool-plus')
					, 'technical support' => __('Technical Support', 'seolat-tool-plus')
					, 'billing support' => __('Billing Support', 'seolat-tool-plus')
					, 'bill payment' => __('Bill Payment', 'seolat-tool-plus')
					, 'sales' => __('Sales', 'seolat-tool-plus')
					, 'reservations' => __('Reservations', 'seolat-tool-plus')
					, 'credit card support' => __('Credit Card Support', 'seolat-tool-plus')			
					, 'emergency' => __('Emergency', 'seolat-tool-plus')
					, 'baggage tracking' => __('Baggage Tracking', 'seolat-tool-plus')
					, 'roadside assistance' => __('Roadside Assistance', 'seolat-tool-plus')
					, 'package tracking' => __('Package Tracking', 'seolat-tool-plus')
				), __('Contact Type: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>What kind of contact type you are using for the Company. For Example, Customer Service.</span></a>', 'seolat-tool-plus'))
				. $this->get_postmeta_textbox('rich_snippet_organization_contactpoint7_telephone7', __('Phone Number: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Use the phone number you are using for Company contact type.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))		
				. $this->get_postmeta_dropdown('rich_snippet_organization_contactpoint8_contacttype8', array(
					  '' => __('Choose Your Contact Type', 'seolat-tool-plus')
					, 'customer service' => __('Customer Service', 'seolat-tool-plus')
					, 'technical support' => __('Technical Support', 'seolat-tool-plus')
					, 'billing support' => __('Billing Support', 'seolat-tool-plus')
					, 'bill payment' => __('Bill Payment', 'seolat-tool-plus')
					, 'sales' => __('Sales', 'seolat-tool-plus')
					, 'reservations' => __('Reservations', 'seolat-tool-plus')
					, 'credit card support' => __('Credit Card Support', 'seolat-tool-plus')			
					, 'emergency' => __('Emergency', 'seolat-tool-plus')
					, 'baggage tracking' => __('Baggage Tracking', 'seolat-tool-plus')
					, 'roadside assistance' => __('Roadside Assistance', 'seolat-tool-plus')
					, 'package tracking' => __('Package Tracking', 'seolat-tool-plus')
				), __('Contact Type: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>What kind of contact type you are using for the Company. For Example, Customer Service.</span></a>', 'seolat-tool-plus'))
				. $this->get_postmeta_textbox('rich_snippet_organization_contactpoint8_telephone8', __('Phone Number: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Use the phone number you are using for Company contact type.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))	
				. $this->get_postmeta_dropdown('rich_snippet_organization_contactpoint9_contacttype9', array(
					  '' => __('Choose Your Contact Type', 'seolat-tool-plus')
					, 'customer service' => __('Customer Service', 'seolat-tool-plus')
					, 'technical support' => __('Technical Support', 'seolat-tool-plus')
					, 'billing support' => __('Billing Support', 'seolat-tool-plus')
					, 'bill payment' => __('Bill Payment', 'seolat-tool-plus')
					, 'sales' => __('Sales', 'seolat-tool-plus')
					, 'reservations' => __('Reservations', 'seolat-tool-plus')
					, 'credit card support' => __('Credit Card Support', 'seolat-tool-plus')			
					, 'emergency' => __('Emergency', 'seolat-tool-plus')
					, 'baggage tracking' => __('Baggage Tracking', 'seolat-tool-plus')
					, 'roadside assistance' => __('Roadside Assistance', 'seolat-tool-plus')
					, 'package tracking' => __('Package Tracking', 'seolat-tool-plus')
				), __('Contact Type: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>What kind of contact type you are using for the Company. For Example, Customer Service.</span></a>', 'seolat-tool-plus'))
				. $this->get_postmeta_textbox('rich_snippet_organization_contactpoint9_telephone9', __('Phone Number: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Use the phone number you are using for Company contact type.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))	
				. $this->get_postmeta_dropdown('rich_snippet_organization_contactpoint10_contacttype10', array(
					  '' => __('Choose Your Contact Type', 'seolat-tool-plus')
					, 'customer service' => __('Customer Service', 'seolat-tool-plus')
					, 'technical support' => __('Technical Support', 'seolat-tool-plus')
					, 'billing support' => __('Billing Support', 'seolat-tool-plus')
					, 'bill payment' => __('Bill Payment', 'seolat-tool-plus')
					, 'sales' => __('Sales', 'seolat-tool-plus')
					, 'reservations' => __('Reservations', 'seolat-tool-plus')
					, 'credit card support' => __('Credit Card Support', 'seolat-tool-plus')			
					, 'emergency' => __('Emergency', 'seolat-tool-plus')
					, 'baggage tracking' => __('Baggage Tracking', 'seolat-tool-plus')
					, 'roadside assistance' => __('Roadside Assistance', 'seolat-tool-plus')
					, 'package tracking' => __('Package Tracking', 'seolat-tool-plus')
				), __('Contact Type: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>What kind of contact type you are using for the Company. For example, Customer Service.</span></a>', 'seolat-tool-plus'))
				. $this->get_postmeta_textbox('rich_snippet_organization_contactpoint10_telephone10', __('Phone Number: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Use the phone number you are using for Company contact type.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);
			
		$fields['serp'][47]['rich_snippet_person_name|rich_snippet_person_address_street|rich_snippet_person_address_po_box|rich_snippet_person_address_city|rich_snippet_person_address_state|rich_snippet_person_address_country|rich_snippet_person_address_postal_code|rich_snippet_person_tel_number|rich_snippet_person_email|rich_snippet_person_jobtitle|rich_snippet_person_affiliation|rich_snippet_person_image|rich_snippet_person_facebook_url|rich_snippet_person_twitter_url|rich_snippet_person_instagram_url|rich_snippet_person_linkedin_url|rich_snippet_person_myspace_url|rich_snippet_person_pinterest_url|rich_snippet_person_youtube_url|rich_snippet_person_google_plus_url'] =
			$this->get_postmeta_subsection('rich_snippet_type', 'person',
				$this->get_postmeta_textbox('rich_snippet_person_name', __('Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your Name.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_address_street', __('Street Address: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your street address. For example, 1600 Amphitheatre Pkwy.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_address_po_box', __('Post Office Box Number: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your post office box number for PO box addresses.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_address_city', __('City: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your locality. For example, Mountain View.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_address_state', __('State or Region: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your region. For example, CA.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_address_country', __('Country: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your country. For example, USA.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_address_postal_code', __('Postal Code: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your postal code. For example, 94043.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_tel_number', __('Phone Number: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your telephone number.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_email', __('Email: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your Email address.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_jobtitle', __('Job Title: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your Job Title.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_affiliation', __('Business: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your Business Name.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_medialib_box('rich_snippet_person_image', __('Image: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your Profile Image.</span></a>', 'seolat-tool-plus'), 'types=posttype_attachment&post_mime_type=image/*')
				. $this->get_postmeta_textbox('rich_snippet_person_facebook_url', __('Facebook Url: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your Facebook Url.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_twitter_url', __('Twitter Url: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your Twitter Url.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_instagram_url', __('Instagram Url: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your Instagram Url.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_linkedin_url', __('LinkedIn Url: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your LinkedIn Url.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_myspace_url', __('MySpace Url: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your MySpace Url.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_pinterest_url', __('Pinterest Url: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your Pinterest Url.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_youtube_url', __('YouTube Url: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your YouTube Url.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_person_google_plus_url', __('Google&#43 Url: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your Google&#43 Url.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);
			
		$fields['serp'][48]['rich_snippet_product_name|rich_snippet_product_brand|rich_snippet_product_description|rich_snippet_product_image|rich_snippet_product_offers_price|rich_snippet_product_offers_price_currency|rich_snippet_product_offers_seller|rich_snippet_product_aggregate_rating|rich_snippet_product_aggregate_reviewcount'] =
			$this->get_postmeta_subsection('rich_snippet_type', 'product',
				$this->get_postmeta_textbox('rich_snippet_product_name', __('Product Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Name of the product.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_product_brand', __('Product Brand: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The brand(s) associated with a product or service, or the brand(s) maintained by an organization or business person.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textarea('rich_snippet_product_description', __('Product Description: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>A short description of the Product.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_medialib_box('rich_snippet_product_image', __('Product Image: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Image of the Product.</span></a>', 'seolat-tool-plus'), 'types=posttype_attachment&post_mime_type=image/*')
				. $this->get_postmeta_textbox('rich_snippet_product_offers_price', __('Price: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Price for the Product. For example, 200.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_product_offers_price_currency', __('Price Currency: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Price currency for the Product. For example, EUR, USD.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_product_offers_seller', __('Name Of The Seller: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Product Seller Name, If have.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_dropdown('rich_snippet_product_aggregate_rating', array(
					  '0'   => __('None', 'seolat-tool-plus')
					, '0.5' => __('0.5 stars', 'seolat-tool-plus')
					, '1'   => __('1 star', 'seolat-tool-plus')
					, '1.5' => __('1.5 stars', 'seolat-tool-plus')
					, '2'   => __('2 stars', 'seolat-tool-plus')
					, '2.5' => __('2.5 stars', 'seolat-tool-plus')
					, '3'   => __('3 stars', 'seolat-tool-plus')
					, '3.5' => __('3.5 stars', 'seolat-tool-plus')
					, '4'   => __('4 stars', 'seolat-tool-plus')
					, '4.5' => __('4.5 stars', 'seolat-tool-plus')
					, '5'   => __('5 stars', 'seolat-tool-plus')
				), __('Rating Value: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Product rating value out of 5.</span></a>', 'seolat-tool-plus'))
				. $this->get_postmeta_textbox('rich_snippet_product_aggregate_reviewcount', __('Total Ratings: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Enter the number of how many ratings you have. For example, 52.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);
			
		$fields['serp'][49]['rich_snippet_recipe_name|rich_snippet_recipe_image|rich_snippet_recipe_reviewer|rich_snippet_recipe_date_reviewed|rich_snippet_recipe_description|rich_snippet_recipe_preptime|rich_snippet_recipe_cooktime|rich_snippet_recipe_totaltime|rich_snippet_recipe_yield|rich_snippet_recipe_nutrition_servingsize|rich_snippet_recipe_nutrition_calories|rich_snippet_recipe_nutrition_fatcontent|rich_snippet_recipe_ingredients|rich_snippet_recipe_directions|rich_snippet_recipe_aggregate_rating|rich_snippet_recipe_aggregate_reviewcount'] =
			$this->get_postmeta_subsection('rich_snippet_type', 'recipe',
				$this->get_postmeta_textbox('rich_snippet_recipe_name', __('Recipe Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Name of the Recipe.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_medialib_box('rich_snippet_recipe_image', __('Recipe Image: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Image of the Recipe.</span></a>', 'seolat-tool-plus'), 'types=posttype_attachment&post_mime_type=image/*')
				. $this->get_postmeta_textbox('rich_snippet_recipe_reviewer', __('Author Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Author of the Recipe.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_recipe_date_reviewed', __('Published Date: <a class="tooltips" href="https://en.wikipedia.org/wiki/ISO_8601" target="_blank"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Date of first broadcast/publication (in ISO 8601 date format). For example, [YYYY]-[MM]-[DD]. Click for more information.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textarea('rich_snippet_recipe_description', __('Recipe Description: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>A short description of the Recipe.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_recipe_preptime', __('Preparation Time: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The length of time it takes to prepare the recipe. For example, 15M. M for Munites & H for Hours.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_recipe_cooktime', __('Cook Time: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The time it takes to actually cook the dish. For example, 1H. M for Munites & H for Hours.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_recipe_totaltime', __('Total Time: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The total time it takes to prepare and cook the recipe. For Example, 1H30M. M for Munites & H for Hours.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_recipe_yield', __('Yield: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The quantity produced by the recipe (for example, number of people served, number of servings, etc).</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_recipe_nutrition_servingsize', __('Serving Size: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The serving size, in terms of the number of volume or mass.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_recipe_nutrition_calories', __('Calories: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The number of calories. For example, 240 calories.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_recipe_nutrition_fatcontent', __('Fat Content: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The number of grams of fat. For example, 9 grams fat.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_recipe_ingredients', __('Ingredients: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>A single ingredient used in the recipe. For example, sugar, flour or garlic.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textarea('rich_snippet_recipe_directions', __('Directions: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Instruction of making the Recipe in step by step.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_dropdown('rich_snippet_recipe_aggregate_rating', array(
					  '0'   => __('None', 'seolat-tool-plus')
					, '0.5' => __('0.5 stars', 'seolat-tool-plus')
					, '1'   => __('1 star', 'seolat-tool-plus')
					, '1.5' => __('1.5 stars', 'seolat-tool-plus')
					, '2'   => __('2 stars', 'seolat-tool-plus')
					, '2.5' => __('2.5 stars', 'seolat-tool-plus')
					, '3'   => __('3 stars', 'seolat-tool-plus')
					, '3.5' => __('3.5 stars', 'seolat-tool-plus')
					, '4'   => __('4 stars', 'seolat-tool-plus')
					, '4.5' => __('4.5 stars', 'seolat-tool-plus')
					, '5'   => __('5 stars', 'seolat-tool-plus')
				), __('Rating Value: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Recipe rating value out of 5.</span></a>', 'seolat-tool-plus'))
				. $this->get_postmeta_textbox('rich_snippet_recipe_aggregate_reviewcount', __('Total Ratings: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Enter the number of how many ratings you have. For example, 20.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);
			
		$fields['serp'][50]['rich_snippet_review_item|rich_snippet_review_image|rich_snippet_review_rating'] =
			$this->get_postmeta_subsection('rich_snippet_type', 'review',
				  $this->get_postmeta_textbox('rich_snippet_review_item', __('Name of Reviewed Item: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Name of the item.</span></a>', 'seolat-tool-plus'))
				. $this->get_postmeta_medialib_box('rich_snippet_review_image', __('Image of Reviewed Item: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Image of the item.</span></a>', 'seolat-tool-plus'), 'types=posttype_attachment&post_mime_type=image/*')
				. $this->get_postmeta_dropdown('rich_snippet_review_rating', array(
					  '0'   => __('None', 'seolat-tool-plus')
					, '0.5' => __('0.5 stars', 'seolat-tool-plus')
					, '1'   => __('1 star', 'seolat-tool-plus')
					, '1.5' => __('1.5 stars', 'seolat-tool-plus')
					, '2'   => __('2 stars', 'seolat-tool-plus')
					, '2.5' => __('2.5 stars', 'seolat-tool-plus')
					, '3'   => __('3 stars', 'seolat-tool-plus')
					, '3.5' => __('3.5 stars', 'seolat-tool-plus')
					, '4'   => __('4 stars', 'seolat-tool-plus')
					, '4.5' => __('4.5 stars', 'seolat-tool-plus')
					, '5'   => __('5 stars', 'seolat-tool-plus')
				), __('Star Rating for Reviewed Item: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Give rating value out of 5.</span></a>', 'seolat-tool-plus'))
			);
			
		$fields['serp'][51]['rich_snippet_software_name|rich_snippet_software_description|rich_snippet_software_image|rich_snippet_software_reviewer|rich_snippet_software_date_reviewed|rich_snippet_software_version|rich_snippet_software_os|rich_snippet_software_offers_price|rich_snippet_software_offers_price_currency|rich_snippet_software_appcategory|rich_snippet_software_aggregate_rating|rich_snippet_software_aggregate_reviewcount'] =
			$this->get_postmeta_subsection('rich_snippet_type', 'software',
				$this->get_postmeta_textbox('rich_snippet_software_name', __('Software Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Name of your Software.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textarea('rich_snippet_software_description', __('Software Description: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Description of your Software.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_medialib_box('rich_snippet_software_image', __('Software Image: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Image of the Software.</span></a>', 'seolat-tool-plus'), 'types=posttype_attachment&post_mime_type=image/*')
				. $this->get_postmeta_textbox('rich_snippet_software_reviewer', __('Author Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Software Author Name.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_software_date_reviewed', __('Released Date: <a class="tooltips" href="https://en.wikipedia.org/wiki/ISO_8601" target="_blank"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Software released Date (in ISO 8601 date format). For example, [YYYY]-[MM]-[DD]. Click for more information.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_software_version', __('Software Version: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Your Software Version Number.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_software_os', __('Operating System: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Supported OS for your Software. For example, ANDROID.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_software_offers_price', __('Price: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Cost of the Software. For example 75 (See Price Currency Below For Additional Options).</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_software_offers_price_currency', __('Price Currency: <a class="tooltips" href="https://en.wikipedia.org/wiki/ISO_4217" target="_blank"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The currency in which the monetary amount is expressed (in 3-letter ISO 4217 format). For example, USD. Click for more information.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_software_appcategory', __('Application Category: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Type of software application, e.g. "Game, Multimedia". For example, GameApplication.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_dropdown('rich_snippet_software_aggregate_rating', array(
					  '0'   => __('None', 'seolat-tool-plus')
					, '0.5' => __('0.5 stars', 'seolat-tool-plus')
					, '1'   => __('1 star', 'seolat-tool-plus')
					, '1.5' => __('1.5 stars', 'seolat-tool-plus')
					, '2'   => __('2 stars', 'seolat-tool-plus')
					, '2.5' => __('2.5 stars', 'seolat-tool-plus')
					, '3'   => __('3 stars', 'seolat-tool-plus')
					, '3.5' => __('3.5 stars', 'seolat-tool-plus')
					, '4'   => __('4 stars', 'seolat-tool-plus')
					, '4.5' => __('4.5 stars', 'seolat-tool-plus')
					, '5'   => __('5 stars', 'seolat-tool-plus')
				), __('Rating Value: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Software rating value out of 5.</span></a>', 'seolat-tool-plus'))
				. $this->get_postmeta_textbox('rich_snippet_software_aggregate_reviewcount', __('Total Reviews: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Enter the number of how many ratings you have. For example, 88.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);
			
		$fields['serp'][52]['rich_snippet_video_name|rich_snippet_video_description|rich_snippet_video_thumbnailurl|rich_snippet_video_reviewer|rich_snippet_video_date_reviewed|rich_snippet_video_upload_date|rich_snippet_video_contenturl|rich_snippet_video_embedurl'] =
			$this->get_postmeta_subsection('rich_snippet_type', 'video',
				$this->get_postmeta_textbox('rich_snippet_video_name', __('Video Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Name of the Video.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textarea('rich_snippet_video_description', __('Video Description: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Description of the Video.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_medialib_box('rich_snippet_video_thumbnailurl', __('Video Image: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>A thumbnail image relevant to the Video.</span></a>', 'seolat-tool-plus'), 'types=posttype_attachment&post_mime_type=image/*')
				. $this->get_postmeta_textbox('rich_snippet_video_reviewer', __('Author Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>The author of this Video.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_video_date_reviewed', __('Published Date: <a class="tooltips" href="https://en.wikipedia.org/wiki/ISO_8601" target="_blank"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Video published Date (in ISO 8601 date format). For example, [YYYY]-[MM]-[DD]. Click for more information.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_video_upload_date', __('Upload Date: <a class="tooltips" href="https://en.wikipedia.org/wiki/ISO_8601" target="_blank"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Video upload Date (in ISO 8601 date format). For example, [YYYY]-[MM]-[DD]. Click for more information.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_video_contenturl', __('Video URL: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>URL of the Video.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_video_embedurl', __('Embed URL: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>A URL pointing to a player for a specific video. In general, this is the information in the src element of an embed tag and should not be the same as the content of the loc tag.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);
			
		$fields['serp'][53]['rich_snippet_course_name|rich_snippet_course_description|rich_snippet_course_provider_name|rich_snippet_course_provider_organizationurl'] =
			$this->get_postmeta_subsection('rich_snippet_type', 'course',
				$this->get_postmeta_textbox('rich_snippet_course_name', __('Course Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Name of the Course.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textarea('rich_snippet_course_description', __('Course Description: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Description of the Course.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_course_provider_name', __('Organization Name: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Name of the Course Provider or Organization.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
				. $this->get_postmeta_textbox('rich_snippet_course_provider_organizationurl', __('Organization URL: <a class="tooltips"> <img class="rs-info-icon" src="' . plugins_url( 'rich-snippets/rs-info-icon.png', dirname(__FILE__) ) . '" > <span>Course Provider or Organization URL.</span></a>', 'seolat-tool-plus'), array('type' => 'text'))
			);
			
		return $fields;
	}

	function add_help_tabs($screen) {
		
		$overview = __("
<ul>
	<li><strong>What it does:</strong> Rich Snippet Creator adds special code (called Schema.org data) to your posts that asks Google and other major search engines to display special pertinent information (known as Rich Snippets) in search results for certain types of content. For example, if you&#8217;ve written a product review, you can use Rich Snippet Creator to ask Google to display the star rating that you gave the product in your review next to your review webpage when it appears in search results.</li>
	<li><strong>Why it helps:</strong> Rich Snippet Creator enhances the search engine results for your content by asking Google to add extra, eye-catching info that could help draw in more search engine visitors.</li>
	<li><strong>How it works:</strong> When editing one of your posts or pages, see if your content fits one of the available rich snippet types (for example, a review). If so, select that type from the &#8220;Search Result Type&#8221; dropdown box. Once you select the applicable type, additional options will appear that vary based on the type selected. For example, a &#8220;Star Rating for Reviewed Item&#8221; field will appear if you select the &#8220;Review&#8221; type. Once you save the post/page, Rich Snippet Creator will add the special code to it. You can remove this code at any time by selecting &#8220;Standard&#8221; from the &#8220;Search Result Type&#8221; dropdown and resaving the post/page.</li>
</ul>
", 'seolat-tool-plus');
		
		$troubleshooting = __("
<ul>
	<li><p><strong>Why aren&#8217;t rich snippets showing up in Google search results for my site?</strong><br />Enter the URL of your post/page into <a href='http://www.google.com/webmasters/tools/richsnippets' target='_blank'>Google&#8217;s testing tool</a> to make sure Google can find the rich snippet code on your site. If no code is found, check and make sure you&#8217;ve enabled rich snippets for that particular post/page.</p><p>Note that having the code on a post/page doesn&#8217;t guarantee that Google will actually use it to create a rich snippet. If Google is able to read your code but isn&#8217;t using it to generate rich snippets, you can ask Google to do so using <a href='http://www.google.com/support/webmasters/bin/request.py?contact_type=rich_snippets_feedback' target='_blank'>this form</a>.</p></li>
</ul>
", 'seolat-tool-plus');
		
		if ($this->has_enabled_parent()) {
			$screen->add_help_tab(array(
			  'id' => 'sl-rich-snippets-help'
			, 'title' => __('Rich Snippet Creator', 'seolat-tool-plus')
			, 'content' => 
				'<h3>' . __('Overview', 'seolat-tool-plus') . '</h3>' . $overview . 
				'<h3>' . __('Troubleshooting', 'seolat-tool-plus') . '</h3>' . $troubleshooting
			));
		} else {
			
			$screen->add_help_tab(array(
				  'id' => 'sl-rich-snippets-overview'
				, 'title' => __('Overview', 'seolat-tool-plus')
				, 'content' => $overview));
			
			$screen->add_help_tab(array(
				  'id' => 'sl-rich-snippets-troubleshooting'
				, 'title' => __('Troubleshooting', 'seolat-tool-plus')
				, 'content' => $troubleshooting));
		}
	
	}
}

}
?>