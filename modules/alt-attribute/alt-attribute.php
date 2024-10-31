<?php
/**
 * Image Alternative Text Module
 *
 */
if (class_exists('SL_Module')) {

    function alt_attribute_import_filter($all_settings) {

        if (!SL_UserCode::user_authorized())
            unset($all_settings['alt-attribute']);

        return $all_settings;
    }
    add_filter('sl_settings_import_array', 'alt_attribute_import_filter');

    class SL_AltAttribute extends SL_Module {

        static function get_module_title() { return __('Alt Attribute', 'seolat-tool-plus'); }
        static function get_menu_title() { return __('Alt Attribute', 'seolat-tool-plus'); }

        function init()
        {
            if ( get_option( 'api_manager_sl_plus_activated' ) == 'Activated' )
            {
                //add style + js scripts
                add_action( 'admin_enqueue_scripts', array($this, 'supiat_add_js'));
                //add column Alt + fields in columns
                add_filter( 'manage_media_columns', array($this, 'supiat_display_column_alt') );
                add_action( 'manage_media_custom_column', array($this, 'supiat_field_input_alt') );
                //add column Caption + fields in columns
                add_filter( 'manage_media_columns', array($this, 'supiat_display_column_caption') );
                add_action( 'manage_media_custom_column', array($this, 'supiat_field_input_caption') );
                //ajax
                add_action( 'wp_ajax_supiat_update_alt_caption' , array($this,'supiat_update_alt_caption') );
                // add in admin menu (upload) in Media
                add_action( 'admin_menu' , array($this,'add_upload_submenu') );
            }
        }

        //add sub-menu in Media
        function add_upload_submenu()
        {
            add_submenu_page(
                'upload.php',
                '',
                'Alt Attribute',
                'manage_options',
                'upload.php?mode=list',
                ''
            );
        }

        // add style + js script for Image Alternative Text and Image Caption Text
        function supiat_add_js($hook) {
            // Check if we are on upload.php and enqueue script
            if ( $hook != 'upload.php' )
                return;
            wp_enqueue_script( 'supiat', plugin_dir_url( __FILE__ ).'supiat-script.js', array('jquery'));
            wp_register_style( 'supiat', plugin_dir_url( __FILE__ ).'supiat-style.css' );
            wp_enqueue_style( 'supiat' );
        }

        // Register the column to display for Alt
        function supiat_display_column_alt( $columns )
        {
            $columns['supiat-alt-column'] = 'Alternative Text';
            return $columns;
        }

        // Set inputs to display in column Alt
        function supiat_field_input_alt( $column )
        {
            if ( $column == 'supiat-alt-column' )
            {
                global $post;
                ?>
                <div class="altwrapper" id="wrapper-<?php echo $post->ID; ?>">
                    <textarea style="width: 100%; border-radius: 2px;" class="alttext" id="alt-<?php echo $post->ID; ?>" ><?php echo wp_strip_all_tags( get_post_meta( $post->ID, '_wp_attachment_image_alt', true ) ); ?></textarea>
                </div>
            <?php
            }
        }

        // Register the column to display for Caption
        function supiat_display_column_caption( $columns )
        {
            $new_columns = array(
                'at_caption_text-alt-column' => '<div class="div-header">
                <div class="div-caption">
                    <span style="padding-right: 10px; display: inline-block;">Caption Text</span>
                </div>
                <div class="div-button-save">
                    <input type="button" id="pid-all" value="Save" class="button-primary supiat-update" style="width: 50px;margin:0;">
                    <img class="waiting-save-all" style="display: none" src="http://test.my/wp-admin/images/wpspin_light.gif">
                </div>
            </div>'
            );
            return array_merge($columns, $new_columns);
        }

        // Set inputs to display in column Caption
        function supiat_field_input_caption( $column )
        {
            if ( $column == 'at_caption_text-alt-column' )
            {
                global $post;
                ?>
                <div class="altwrapper" id="wrapper-caption-<?php echo $post->ID; ?>">
                    <textarea style="width: 100%; border-radius: 2px;" class="caption" id="caption-<?php echo $post->ID; ?>" ><?php echo $post->post_excerpt ? $post->post_excerpt : ''; ?></textarea>
                </div>
            <?php
            }
        }

        // ajax function
        function supiat_update_alt_caption()
        {
            $alts = $_POST['alts'];
            $captions = $_POST['captions'];

            //update alt text
            if(is_array($alts))
            {
                foreach($alts as $alt)
                {
                    $res = update_post_meta( $alt['id'], '_wp_attachment_image_alt', $alt['alt'] );
                }
            }
            //update caption text
            if(is_array($captions))
            {
                foreach($captions as $caption)
                {
                    $id = wp_update_post( $caption );
                }
            }
        }
    }
}
?>
