jQuery(document).ready( function() {
    jQuery('.supiat-update').on('click', function()
    {
        // Vm WDS
            if(jQuery("div").is("#succesMsg")) {
                jQuery('#succesMsg').fadeIn();
            } else {
                jQuery('#posts-filter').prepend('<div id="succesMsg" class="sl-message">' +
                    '<p class="sl-success">Settings updated.</p>' + '</div>');
            }

            setTimeout(function(){
                jQuery('#succesMsg').fadeOut();
            }, 10000);

        //========//

        jQuery('.waiting-save-all').show();

        var alts = [];  // array id posts with alt-text
        jQuery("body").find(".alttext").each(function(indx, element)
        {
            var post_id = jQuery(element).attr("id").replace('alt-', '');
            var alt_text = jQuery(element).val();
            alts.push({"id":post_id, "alt":alt_text});
        });

        var captions = [];  // array id posts with caption
        jQuery("body").find(".caption").each(function(indx, element)
        {
            var post_id = jQuery(element).attr("id").replace('caption-', '');
            var caption_text = jQuery(element).val();
            captions.push({"ID":post_id, "post_excerpt":caption_text});
        });

        jQuery.post( ajaxurl, {
                action: 'supiat_update_alt_caption',
                'alts' : alts,
                'captions' : captions
            },
            function(r)
            {
                if (r)
                {
                    jQuery('.waiting-save-all').hide();
                }
            }
        );
    });
});
