<?php
/**
 * Code Inserter + Module
 * 
 * @since 2.7
 */

if (class_exists('SL_Module')) {

function sl_user_code_plus_import_filter($all_settings) {
	
	if (!SL_UserCode::user_authorized())
		unset($all_settings['user-code-plus']);
	
	return $all_settings;
}
add_filter('sl_settings_import_array', 'sl_user_code_plus_import_filter');

    class SL_UserCodePlus extends SL_Module {

        static function get_module_title() { return __('Code Inserter +', 'seolat-tool-plus'); }
        static function get_menu_title() { return __('Code Inserter +', 'seolat-tool-plus'); }

        function init()
        {
            $hooks = array('sl_head', 'the_content', 'wp_footer');
            foreach ($hooks as $hook) add_filter($hook, array(&$this, "{$hook}_code"), 'sl_head' == $hook ? 11 : 10);
        }

        function sl_head_code()
        {
            global $post;

            $code = '';
            if(is_singular())
            {
                $code = $this->get_postmeta('header', $post->ID);
            }
            elseif(is_archive())
            {
                if(is_category())
                {
                    $id = get_query_var('cat');
                    $value = $this->get_setting('taxonomy_header', array());
                    $code = isset($value[$id]) ? $value[$id] : null;
                }
                elseif(is_tag())
                {
                    $id = get_query_var('tag_id');
                    $value = $this->get_setting('taxonomy_header', array());
                    $code = isset($value[$id]) ? $value[$id] : null;
                }
                elseif(is_tax())
                {
                    global $wp_query;
                    $id = $wp_query->queried_object_id;
                    $value = $this->get_setting('taxonomy_header', array());
                    $code = isset($value[$id]) ? $value[$id] : null;
                }
            }
            echo $this->plugin->mark_code($code, __('SEO LAT+Code Inserter module', 'seolat-tool-plus'), 'wp_head');
        }

        function wp_footer_code()
        {
            global $post;
            $code = '';
            if(is_singular())
            {
                $code = $this->get_postmeta('footer', $post->ID);
            }
            elseif(is_archive())
            {
                if(is_category())
                {
                    $id = get_query_var('cat');
                    $value = $this->get_setting('taxonomy_footer', array());
                    $code = isset($value[$id]) ? $value[$id] : null;
                }
                elseif(is_tag())
                {
                    $id = get_query_var('tag_id');
                    $value = $this->get_setting('taxonomy_footer', array());
                    $code = isset($value[$id]) ? $value[$id] : null;
                }
                elseif(is_tax())
                {
                    global $wp_query;
                    $id = $wp_query->queried_object_id;
                    $value = $this->get_setting('taxonomy_footer', array());
                    $code = isset($value[$id]) ? $value[$id] : null;
                }
            }

            if($code){
                echo $code;
            }
        }

        function the_content_code($content)
        {
            global $post;
            $before = $after = '';

            if(is_singular())
            {
                $before = $this->get_postmeta('before', $post->ID);
                $after = $this->get_postmeta('after', $post->ID);
            }
            elseif(is_archive())
            {
                if(is_category())
                {
                    $id = get_query_var('cat');
                    $value = $this->get_setting('taxonomy_before', array());
                    $before = isset($value[$id]) ? $value[$id] : null;
                    $value = $this->get_setting('taxonomy_after', array());
                    $after = isset($value[$id]) ? $value[$id] : null;
                }
                elseif(is_tag())
                {
                    $id = get_query_var('tag_id');
                    $value = $this->get_setting('taxonomy_before', array());
                    $before = isset($value[$id]) ? $value[$id] : null;
                    $value = $this->get_setting('taxonomy_after', array());
                    $after = isset($value[$id]) ? $value[$id] : null;
                }
                elseif(is_tax())
                {
                    global $wp_query;
                    $id = $wp_query->queried_object_id;
                    $value = $this->get_setting('taxonomy_before', array());
                    $before = isset($value[$id]) ? $value[$id] : null;
                    $value = $this->get_setting('taxonomy_after', array());
                    $after = isset($value[$id]) ? $value[$id] : null;
                }
            }

            return $before . $content . $after;
        }

        function get_arr_settings()
        {
            return array(
                'type' => 'textbox',
                'name' => 'user_code_plus',
                'term_settings_key' => 'taxonomy_user_code_plus',
                'label' => __('Title Tag', 'seolat-tool-plus'),
                'dop' => array(
                    array(
                        'type' => 'textarea',
                        'name' => 'header',
                        'term_settings_key' => 'taxonomy_header',
                        'label' => __('Header', 'seolat-tool-plus')
                    ),
                    array(
                        'type' => 'textarea',
                        'name' => 'before',
                        'term_settings_key' => 'taxonomy_before',
                        'label' => __('Above Content', 'seolat-tool-plus')
                    ),
                    array(
                        'type' => 'textarea',
                        'name' => 'after',
                        'term_settings_key' => 'taxonomy_after',
                        'label' => __('Below Content', 'seolat-tool-plus')
                    ),
                    array(
                        'type' => 'textarea',
                        'name' => 'footer',
                        'term_settings_key' => 'taxonomy_footer',
                        'label' => __('Footer', 'seolat-tool-plus')
                    )
                ));

        }

        function get_admin_page_tabs() {
            return array_merge(
            /*array(
                array('title' => __('Default Formats', 'seolat-tool-plus'), 'id' => 'sl-default-formats', 'callback' => 'formats_tab')
            , array('title' => __('Settings', 'seolat-tool-plus'), 'id' => 'sl-settings', 'callback' => 'settings_tab')
            )
            ,*/
                $this->get_meta_edit_tabs($this->get_arr_settings())
            );
        }

        /**
         * Outputs the contents of a meta editing table.
         *
         * @since 2.9
         *
         * @param string $genus The type of object being handled (either 'post' or 'term')
         * @param string $tab The ID of the current tab; used to generate a URL hash (e.g. #$tab)
         * @param string $type The type of post/taxonomy type being edited (examples: post, page, attachment, category, post_tag)
         * @param string $type_label The singular label for the post/taxonomy type (examples: Post, Page, Attachment, Category, Post Tag)
         * @param array $fields The array of meta fields that the user can edit with the tables. The data for each meta field are stored in an array with these elements: "type" (can be textbox, textarea, or checkbox), "name" (the meta field, e.g. title or description), "term_settings_key" (the key of the setting for cases when term meta data are stored in the settings array), and "label" (the internationalized label of the field, e.g. "Meta Description" or "Title Tag")
         */
        function meta_edit_table($genus, $tab, $type, $type_label, $fields) {

            //Pseudo-constant
            $per_page = 100;

            //Sanitize parameters
            if (!is_array($fields) || !count($fields)) return false;
            if (!isset($fields[0]) || !is_array($fields[0])) $fields = array($fields);

            //Get search query
            $type_s = $type . '_s';
            $search = isset($_REQUEST[$type_s]) ? $_REQUEST[$type_s] : '';

            //Save meta if applicable
            if ($is_update = ($this->is_action('update') && !strlen(trim($search)))) {

                $arr = array();
                $post_request = array_map( 'stripslashes', $_POST );
                foreach ($post_request as $key => $value){
                    // $value = stripslashes($value);
                    if (lat_string::startswith($key, $genus.'_'))
                    {
                        foreach ($fields as $field)
                        {
                            if(isset($field['dop']) && is_array($field['dop']))
                            {
                                foreach($field['dop'] as $item)
                                {
                                    if (preg_match("/{$genus}_([0-9]+)_{$item['name']}/", $key, $matches))
                                    {
                                        $id = (int)$matches[1];
                                        $arr[$id][] = array('type'=>$item['type'], 'term_settings_key'=>$item['term_settings_key'], 'name'=>"_sl_{$item['name']}", 'label'=>$value);
                                    }
                                }
                            }
                        }
                    }
                }

                switch ($genus)
                {
                    case 'post':
                    {
                        foreach($arr as $el_key => $el_val)
                        {
                            foreach($el_val as $item_key => $item_val)
                            {
                                update_post_meta($el_key, $item_val['name'], $item_val['label']);
                            }
                        }
                    } break;
                    case 'term':
                    {
                        foreach($arr as $el_key => $el_val)
                        {
                            foreach($el_val as $item_key => $item_val)
                            {
                                $this->update_setting($item_val['term_settings_key'], $item_val['label'], null, $el_key);
                            }
                        }
                    } break;
                }
            }

            $pagenum = isset( $_GET[$type . '_paged'] ) ? absint( $_GET[$type . '_paged'] ) : 0;
            if ( empty($pagenum) ) $pagenum = 1;

            //Load up the objects based on the genus
            switch ($genus) {
                case 'post':

                    //Get the posts
                    wp(array(
                        'post_type' => $type
                    , 'posts_per_page' => $per_page
                    , 'post_status' => 'any'
                    , 'paged' => $pagenum
                    , 'order' => 'ASC'
                    , 'orderby' => 'title'
                    , 's' => $search
                    ));
                    global $wp_query;
                    $objects = &$wp_query->posts;

                    $num_pages = $wp_query->max_num_pages;
                    $total_objects = $wp_query->found_posts;

                    break;

                case 'term':
                    $objects = get_terms($type, array('search' => $search));
                    $total_objects = count($objects);
                    $num_pages = ceil($total_objects / $per_page);
                    $objects = array_slice($objects, $per_page * ($pagenum-1), $per_page);
                    break;
                default:
                    return false;
                    break;
            }

            if ($total_objects < 1) return false;

            echo "\n<div class='sl-meta-edit-table'>\n";

            $page_links = paginate_links( array(
                'base' => html_entity_decode( esc_url( add_query_arg( $type . '_paged', '%#%' ) ) ) . '#' . $tab
            , 'format' => ''
            , 'prev_text' => __('&laquo;')
            , 'next_text' => __('&raquo;')
            , 'total' => $num_pages
            , 'current' => $pagenum
            , 'add_args' => false
            ));

            if ( $page_links ) {
                $page_links_text = '<div class="tablenav"><div class="tablenav-pages">';
                $page_links_text .= sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
                    number_format_i18n( ( $pagenum - 1 ) * $per_page + 1 ),
                    number_format_i18n( min( $pagenum * $per_page, $total_objects ) ),
                    number_format_i18n( $total_objects ),
                    $page_links
                );
                $page_links_text .= "</div></div>\n";

                echo $page_links_text;
            } else $page_links_text = '';

            //Get object identification headers
            $headers = array(
                'actions' => __('Actions', 'seolat-tool-plus')
            , 'id' => __('ID', 'seolat-tool-plus')
            , 'name' => $type_label
            );

            //Get meta field headers
            foreach ($fields as $field) {
                if(isset($field['dop']) && is_array($field['dop']))
                {
                    foreach($field['dop'] as $item)
                    {
                        $headers[$item['name']] = $item['label'];
                    }
                }
                else
                {
                    $headers[$field['name']] = $field['label'];
                }
            }

            //Output all headers
            $this->admin_wftable_start($headers);

            //Output rows
            foreach ($objects as $object) {

                switch ($genus) {
                    case 'post':
                        $id = intval($object->ID);
                        $name = $object->post_title;
                        $view_url = get_permalink($id);
                        $edit_url = get_edit_post_link($id);

                        $status_obj = get_post_status_object($object->post_status);
                        switch ($object->post_status) {
                            case 'publish': $status = ''; break;
                            case 'inherit': $status = ''; break;
                            case 'auto-draft': $status = $status; break;
                            default: $status = $status_obj->label; break;
                        }

                        if ($status)
                            $name .= "<span class='sl-meta-table-post-status'> &mdash; $status</span>";

                        break;
                    case 'term':
                        if (!isset($object->term_taxonomy_id)) {
                            $id = intval($object->term_id);
                            $view_url = get_term_link($id, $type);
                        }
                        else{
                            $id = intval($object->term_id);
                            $view_url = get_term_link(intval($object->term_id), $type);
                        }
                        $name = $object->name;

                        $edit_url = lat_wp::get_edit_term_link($id, $type);
                        break;
                    default: return false; break;
                }

                $view_url = sl_esc_attr($view_url);
                $edit_url = sl_esc_attr($edit_url);

                $actions = array(sprintf('<a href="%s">%s</a>', $view_url, __('View', 'seolat-tool-plus')));
                if ($edit_url)
                    $actions[] = sprintf('<a href="%s">%s</a>', $edit_url, __('Edit', 'seolat-tool-plus'));
                $actions = implode(' | ', $actions);

                $cells = compact('actions', 'id', 'name');

                //Get meta field cells
                foreach ($fields as $field) {

                    if(is_array($field['dop']))
                    {
                        foreach($field['dop'] as $item)
                        {
                            $inputid = "{$genus}_{$id}_{$item['name']}";

                            switch ($genus) {
                                case 'post':
                                    $value = $this->get_postmeta($item['name'], $id);
                                    break;
                                case 'term':
                                    $value = $this->get_setting($item['term_settings_key'], array());
                                    $value = isset($value[$id]) ? $value[$id] : null;
                                    break;
                            }

                            if ($is_update && $item['type'] == 'checkbox' && $value == '1' && !isset($_POST[$inputid]))
                                switch ($genus) {
                                    case 'post': delete_post_meta($id, "_sl_{$item['name']}"); $value = 0; break;
                                    case 'term': $this->update_setting($item['term_settings_key'], false, null, $id); break;
                                }

                            $cells[$item['name']] = $this->get_input_element(
                                $item['type'] //Type
                                , $inputid
                                , $value
                                , isset($item['options']) ? $item['options'] : false
                            );
                        }
                    }
                    else
                    {
                        $inputid = "{$genus}_{$id}_{$field['name']}";

                        switch ($genus) {
                            case 'post':
                                $value = $this->get_postmeta($field['name'], $id);
                                break;
                            case 'term':
                                $value = $this->get_setting($field['term_settings_key'], array());
                                $value = isset($value[$id]) ? $value[$id] : null;
                                break;
                        }

                        if ($is_update && $field['type'] == 'checkbox' && $value == '1' && !isset($_POST[$inputid]))
                            switch ($genus) {
                                case 'post': delete_post_meta($id, "_sl_{$field['name']}"); $value = 0; break;
                                case 'term': $this->update_setting($field['term_settings_key'], false, null, $id); break;
                            }

                        $cells[$field['name']] = $this->get_input_element(
                            $field['type'] //Type
                            , $inputid
                            , $value
                            , isset($field['options']) ? $field['options'] : false
                        );
                    }
                }

                //Output all cells
                $this->table_row($cells, $id, $type);
            }

            //End table
            $this->admin_wftable_end();

            echo $page_links_text;

            echo "</div>\n";

            return true;
        }

        function formats_tab() {
            $this->textboxes($this->get_supported_settings(), $this->get_default_settings());
        }

        function settings_tab() {
            $this->admin_form_table_start();
            $this->checkbox('terms_ucwords', __('Convert lowercase category/tag names to title case when used in title tags.', 'seolat-tool-plus'), __('Title Tag Variables', 'seolat-tool-plus'));
            $this->radiobuttons('rewrite_method', array(
                'ob' => __('Use output buffering &mdash; no configuration required, but slower (default)', 'seolat-tool-plus')
            , 'filter' => __('Use filtering &mdash; faster, but configuration required (see the &#8220;Settings Tab&#8221 section of the &#8220;Help&#8221; dropdown for details)', 'seolat-tool-plus')
            ), __('Rewrite Method', 'seolat-tool-plus'));
            $this->admin_form_table_end();
        }

        function get_default_settings() {

            //We internationalize even non-text formats (like "{post} | {blog}") to allow RTL languages to switch the order of the variables
            return array(
                'title_home' => __('{blog}', 'seolat-tool-plus')
            , 'title_single' => __('{post} | {blog}', 'seolat-tool-plus')
            , 'title_page' => __('{page} | {blog}', 'seolat-tool-plus')
            , 'title_category' => __('{category} | {blog}', 'seolat-tool-plus')
            , 'title_tag' => __('{tag} | {blog}', 'seolat-tool-plus')
            , 'title_day' => __('Archives for {month} {day}, {year} | {blog}', 'seolat-tool-plus')
            , 'title_month' => __('Archives for {month} {year} | {blog}', 'seolat-tool-plus')
            , 'title_year' => __('Archives for {year} | {blog}', 'seolat-tool-plus')
            , 'title_author' => __('Posts by {author} | {blog}', 'seolat-tool-plus')
            , 'title_search' => __('Search Results for {query} | {blog}', 'seolat-tool-plus')
            , 'title_404' => __('404 Not Found | {blog}', 'seolat-tool-plus')
            , 'title_paged' => __('{title} - Page {num}', 'seolat-tool-plus')
            , 'terms_ucwords' => true
            , 'rewrite_method' => 'ob'
            );
        }

        function get_supported_settings() {
            return array(
                'title_home' => __('Blog Homepage Title', 'seolat-tool-plus')
            , 'title_single' => __('Post Title Format', 'seolat-tool-plus')
            , 'title_page' => __('Page Title Format', 'seolat-tool-plus')
            , 'title_category' => __('Category Title Format', 'seolat-tool-plus')
            , 'title_tag' => __('Tag Title Format', 'seolat-tool-plus')
            , 'title_day' => __('Day Archive Title Format', 'seolat-tool-plus')
            , 'title_month' => __('Month Archive Title Format', 'seolat-tool-plus')
            , 'title_year' => __('Year Archive Title Format', 'seolat-tool-plus')
            , 'title_author' => __('Author Archive Title Format', 'seolat-tool-plus')
            , 'title_search' => __('Search Title Format', 'seolat-tool-plus')
            , 'title_404' => __('404 Title Format', 'seolat-tool-plus')
            , 'title_paged' => __('Pagination Title Format', 'seolat-tool-plus')
            );
        }

        function get_title_format() {
            if ($key = $this->get_current_page_type())
                return $this->get_setting("title_$key");

            return false;
        }

        function get_current_page_type() {
            $pagetypes = $this->get_supported_settings();
            unset($pagetypes['title_paged']);

            foreach ($pagetypes as $key => $title) {
                $key = str_replace('title_', '', $key);
                if (call_user_func("is_$key")) return $key;
            }

            return false;
        }

        function should_rewrite_title() {
            return (!is_admin() && !is_feed());
        }

        function before_header() {
            if ($this->should_rewrite_title()) ob_start(array(&$this, 'change_title_tag'));
        }

        function after_header() {
            if ($this->should_rewrite_title()) {

                $handlers = ob_list_handlers();
                if (count($handlers) > 0 && strcasecmp($handlers[count($handlers)-1], 'SL_Titles::change_title_tag') == 0)
                    ob_end_flush();
                else
                    sl_debug_log(__FILE__, __CLASS__, __FUNCTION__, __LINE__, "Other ob_list_handlers found:\n".print_r($handlers, true));
            }
        }

        function change_title_tag($head) {

            $title = $this->get_title();
            if (!$title) return $head;
            // Pre-parse the title replacement text to escape the $ ($n backreferences) when followed by a number 0-99 because of preg_replace issue
            $title = preg_replace('/\$(\d)/', '\\\$$1', $title);
            //Replace the old title with the new and return
            return preg_replace('/<title>[^<]*<\/title>/i', '<title>'.$title.'</title>', $head);
        }

        function get_title() {

            global $wp_query, $wp_locale;

            //Custom post/page title?
            if ($post_title = $this->get_postmeta('title'))
                return htmlspecialchars($this->get_title_paged($post_title));

            //Custom taxonomy title?
            if (lat_wp::is_tax()) {
                $tax_titles = $this->get_setting('taxonomy_titles');
                if ($tax_title = $tax_titles[$wp_query->get_queried_object_id()])
                    return htmlspecialchars($this->get_title_paged($tax_title));
            }

            //Get format
            if (!$this->should_rewrite_title()) return '';
            if (!($format = $this->get_title_format())) return '';

            //Load post/page titles
            $post_id = 0;
            $post_title = '';
            $parent_title = '';
            if (is_singular()) {
                $post = $wp_query->get_queried_object();
                $post_title = strip_tags( apply_filters( 'single_post_title', $post->post_title, $post ) );
                $post_id = $post->ID;

                if ($parent = $post->post_parent) {
                    $parent = get_post($parent);
                    $parent_title = strip_tags( apply_filters( 'single_post_title', $parent->post_title, $post ) );
                }
            }

            //Load date-based archive titles
            if ($m = get_query_var('m')) {
                $year = substr($m, 0, 4);
                $monthnum = intval(substr($m, 4, 2));
                $daynum = intval(substr($m, 6, 2));
            } else {
                $year = get_query_var('year');
                $monthnum = get_query_var('monthnum');
                $daynum = get_query_var('day');
            }
            $month = $wp_locale->get_month($monthnum);
            $monthnum = zeroise($monthnum, 2);
            $day = date('jS', mktime(12,0,0,$monthnum,$daynum,$year));
            $daynum = zeroise($daynum, 2);

            //Load category titles
            $cat_title = $cat_titles = $cat_desc = '';
            if (is_category()) {
                $cat_title = single_cat_title('', false);
                $cat_desc = category_description();
            } elseif (count($categories = get_the_category())) {
                $cat_titles = sl_lang_implode($categories, 'name');
                usort($categories, '_usort_terms_by_ID');
                $cat_title = $categories[0]->name;
                $cat_desc = category_description($categories[0]->term_id);
            }
            if (strlen($cat_title) && $this->get_setting('terms_ucwords', true))
                $cat_title = lat_string::tclcwords($cat_title);

            //Load tag titles
            $tag_title = $tag_desc = '';
            if (is_tag()) {
                $tag_title = single_tag_title('', false);
                $tag_desc = tag_description();

                if ($this->get_setting('terms_ucwords', true))
                    $tag_title = lat_string::tclcwords($tag_title);
            }

            //Load author titles
            if (is_author()) {
                $author_obj = $wp_query->get_queried_object();
            } elseif (is_singular()) {
                global $authordata;
                $author_obj = $authordata;
            } else {
                $author_obj = null;
            }
            if ($author_obj)
                $author = array(
                    'username' => $author_obj->user_login
                , 'name' => $author_obj->display_name
                , 'firstname' => get_the_author_meta('first_name', $author_obj->ID)
                , 'lastname' => get_the_author_meta('last_name',  $author_obj->ID)
                , 'nickname' => get_the_author_meta('nickname',   $author_obj->ID)
                );
            else
                $author = array(
                    'username' => ''
                , 'name' => ''
                , 'firstname' => ''
                , 'lastname' => ''
                , 'nickname' => ''
                );

            $variables = array(
                '{blog}' => get_bloginfo('name')
            , '{tagline}' => get_bloginfo('description')
            , '{post}' => $post_title
            , '{page}' => $post_title
            , '{page_parent}' => $parent_title
            , '{category}' => $cat_title
            , '{categories}' => $cat_titles
            , '{category_description}' => $cat_desc
            , '{tag}' => $tag_title
            , '{tag_description}' => $tag_desc
            , '{tags}' => sl_lang_implode(get_the_tags($post_id), 'name', true)
            , '{daynum}' => $daynum
            , '{day}' => $day
            , '{monthnum}' => $monthnum
            , '{month}' => $month
            , '{year}' => $year
            , '{author}' => $author['name']
            , '{author_name}' => $author['name']
            , '{author_username}' => $author['username']
            , '{author_firstname}' => $author['firstname']
            , '{author_lastname}' => $author['lastname']
            , '{author_nickname}' => $author['nickname']
            , '{query}' => sl_esc_attr(get_search_query())
            , '{ucquery}' => sl_esc_attr(ucwords(get_search_query()))
            , '{url_words}' => $this->get_url_words($_SERVER['REQUEST_URI'])
            );

            $title = str_replace(array_keys($variables), array_values($variables), htmlspecialchars($format));

            return $this->get_title_paged($title);
        }

        function get_title_paged($title) {

            global $wp_query, $numpages;

            if (is_paged() || get_query_var('page')) {

                if (is_paged()) {
                    $num = absint(get_query_var('paged'));
                    $max = absint($wp_query->max_num_pages);
                } else {
                    $num = absint(get_query_var('page'));

                    if (is_singular()) {
                        $post = $wp_query->get_queried_object();
                        $max = count(explode('<!--nextpage-->', $post->post_content));
                    } else
                        $max = '';
                }

                return str_replace(
                    array('{title}', '{num}', '{max}'),
                    array( $title, $num, $max ),
                    $this->get_setting('title_paged'));
            } else
                return $title;
        }

        function get_url_words($url) {

            //Remove any extensions (.html, .php, etc)
            $url = preg_replace('|\\.[a-zA-Z]{1,4}$|', ' ', $url);

            //Turn slashes to >>
            $url = str_replace('/', ' &raquo; ', $url);

            //Remove word separators
            $url = str_replace(array('.', '/', '-'), ' ', $url);

            //Capitalize the first letter of every word
            $url = explode(' ', $url);
            $url = array_map('trim', $url);
            $url = array_map('ucwords', $url);
            $url = implode(' ', $url);
            $url = trim($url);

            return $url;
        }

        function postmeta_fields($fields, $screen) {
            $id = "_sl_title";
            $value = sl_esc_attr($this->get_postmeta('title'));
            $fields['serp'][10]['title'] =
                "<div class='form-group sl textbox'>\n<label class='col-sm-4 col-md-4 control-label' for='$id'>".__('Title Tag:', 'seolat-tool-plus')."</label>\n<div class='col-sm-4 col-md-4'><input name='$id' id='$id' type='text' value='$value' class='form-control input-sm regular-text' tabindex='2'"
                . " onkeyup=\"javascript:document.getElementById('sl_title_charcount').innerHTML = document.getElementById('_sl_title').value.length\" />"
                . "</div>\n<div class='col-sm-4 col-md-4 help-text'>".sprintf(__('You&#8217;ve Entered %s Characters. Most Search Engines Use Up To 70.', 'seolat-tool-plus'), "<strong id='sl_title_charcount'>".strlen($value)."</strong>")
                . "</div>\n</div>\n";


            return $fields;
        }

        function postmeta_help($help) {
            $help[] = __('<strong>Title Tag</strong> &mdash; The exact contents of the &lt;title&gt; tag. The title appears in visitors&#8217; title bars and in search engine result titles. If this box is left blank, then the <a href="admin.php?page=sl-titles" target="_blank">default post/page titles</a> are used.', 'seolat-tool-plus');
            return $help;
        }

        function add_help_tabs($screen) {

            $screen->add_help_tab(array(
                'id' => 'sl-user-code-overview'
            , 'title' => __('Overview', 'seolat-tool-plus')
            , 'content' => __("
<ul>
	<li><strong>What it does:</strong> Code Inserter can add custom HTML code to various parts of your site.</li>
	<li>
		<p><strong>Why it helps:</strong> Code Inserter is useful for inserting third-party code that can improve the SEO or user experience of your site. For example, you can use Code Inserter to add Google Analytics code to your footer, Feedburner FeedFlares or social media widgets after your posts, or Google AdSense section targeting code before/after your content.</p>
		<p>Using Code Inserter is easier than editing your theme manually because your custom code is stored in one convenient location and will be added to your site even if you change your site&#8217;s theme.</p>
	</li>
	<li><strong>How to use it:</strong> Just paste the desired HTML code into the appropriate fields and then click Save Changes.</li>
</ul>
", 'seolat-tool-plus')));

            $screen->add_help_tab(array(
                'id' => 'sl-user-code-troubleshooting'
            , 'title' => __('Troubleshooting', 'seolat-tool-plus')
            , 'content' => __("
<ul>
	<li><p><strong>Why do I get a message saying my account doesn&#8217;t have permission to insert arbitrary HTML code?</strong><br />WordPress has a security feature that only allows administrators to insert arbitrary, unfiltered HTML into the site. On single-site setups, site administrators have this capability. On multisite setups, only network admins have this capability. This is done for security reasons, since site users with the ability to insert arbitrary HTML could theoretically insert malicious code that could be used to hijack control of the site.</p><p>If you are the administrator of a site running on a network, then you will not be able to use Code Inserter under default security settings. However, the network administrator <em>will</em> be able to edit the fields on this page. If you have code that you really want inserted into a certain part of your site, ask your network administrator to do it for you.</p><p>If you are the network administrator of a multisite WordPress setup, and you completely trust all of the administrators and editors of the various sites on your network, you can install the <a href='http://wordpress.org/extend/plugins/unfiltered-mu/' target='_blank'>Unfiltered MU</a> plugin to enable the Code Inserter for all of those users.</p></li>
	<li><strong>Why doesn't my code appear on my site?</strong><br />It&#8217;s possible that your theme doesn't have the proper &#8220;hooks,&#8221; which are pieces of code that let WordPress plugins insert custom HTML into your theme. <a href='http://johnlamansky.com/wordpress/theme-plugin-hooks/' target='_blank'>Click here</a> for information on how to check your theme and add the hooks if needed.</li>
</ul>
", 'seolat-tool-plus')));

        }
    }
}
?>