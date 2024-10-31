
function sl_toggle_select_children(select) {
	var i=0;
	for (i=0;i<select.options.length;i++) {
		var option = select.options[i];
		var c = ".sl_" + select.name.replace("_sl_", "") + "_" + option.value + "_subsection";
		if (option.index == select.selectedIndex) jQuery(c).show().removeClass("hidden"); else jQuery(c).hide();
	}
}

// bootstrap/prototype conflict
jQuery.noConflict();
if ( typeof Prototype !== 'undefined' && Prototype.BrowserFeatures.ElementExtensions ) {
    var disablePrototypeJS = function (method, pluginsToDisable) {
            var handler = function (event) {
                event.target[method] = undefined;
                setTimeout(function () {
                    delete event.target[method];
                }, 0);
            };
            pluginsToDisable.each(function (plugin) { 
                jQuery(window).on(method + '.bs.' + plugin, handler);
            });
        },
        pluginsToDisable = ['collapse', 'dropdown', 'modal', 'tooltip', 'popover', 'tab'];
    disablePrototypeJS('show', pluginsToDisable);
    disablePrototypeJS('hide', pluginsToDisable);
}
jQuery(document).ready(function ($) {
    $('.bs-example-tooltips').children().each(function () {
        $(this).tooltip();
    });
    $('.bs-example-popovers').children().each(function () {
            $(this).popover();
    });

    // if ($('#_sl_keyword').length > 0) {
    //     keywordCheck();
    // }

    // function keywordCheck() {
    //     var keyword = $('#_sl_keyword').val();
    //     var title_seo = $('#_sl_title').val();
    //     var desc_seo = $('#_sl_description').val();
    //     var url = ($('#sample-permalink').length > 0) ? $('#sample-permalink a').attr('href') : false;
    //     var content = $('#content').val();
    //     tinyMCE.activeEditor.settings
    //     console.log(tinyMCE.activeEditor);
    //     var regex_keyword = new RegExp(keyword, 'gm');

    //     title_seo = title_seo.replace(new RegExp('[\\w]*'+keyword+'[\\w]+|[\\w]+'+keyword+'[\\w]*','gm'), '');
    //     var title_seo_match = title_seo.match(regex_keyword);
    //     var title_seo_match_count = (title_seo_match) ? title_seo_match.length : 0;
        

    //     desc_seo = desc_seo.replace(new RegExp('[\\w]*'+keyword+'[\\w]+|[\\w]+'+keyword+'[\\w]*','gm'), '');
    //     var desc_seo_match = desc_seo.match(regex_keyword);
    //     var desc_seo_match_count = (desc_seo_match) ? desc_seo_match.length : 0;

    //     var html = '';
    //     let max_title_seo_keyword = 5;
    //     let title_keyword_percent = (title_seo_match_count <= max_title_seo_keyword) ? title_seo_match_count/max_title_seo_keyword : 0;
    //     html += '<p><span style="font-weight: 600; color: '+ ["hsl(",(title_keyword_percent*120).toString(10),",100%,40%)"].join("") + '">' + ((title_seo_match_count > 0) ? 'Have ' + title_seo_match_count + ' keywords' : 'Don\'t have any keyword' ) + '</span> in your seo title.';
    //     html += ((title_seo_match_count > max_title_seo_keyword) ? ' Keyword appear too much in your title. Number of recommendations: ' + max_title_seo_keyword + '.' : ' Number of recommendations: ' + max_title_seo_keyword) + '</p>';
        
    //     let max_desc_seo_keyword = 5;
    //     let desc_keyword_percent = (desc_seo_match_count <= max_desc_seo_keyword) ? desc_seo_match_count/max_desc_seo_keyword : 0;
    //     html += '<p><span style="font-weight: 600; color: '+ ["hsl(",(desc_keyword_percent*120).toString(10),",100%,40%)"].join("") + '">' + ((desc_seo_match_count > 0) ? 'Have ' + desc_seo_match_count + ' keywords' : 'Don\'t have any keyword' ) + '</span> in your seo description.';
    //     html += ((desc_seo_match_count > max_desc_seo_keyword) ? ' Keyword appear too much in your description. Number of recommendations: ' + max_desc_seo_keyword + '.' : ' Number of recommendations: ' + max_desc_seo_keyword) + '</p>';
    //     $('#sl_check_keyword_result').html(html);
    // }
    // $('#content').on('keyup', () => {keywordCheck()});
    // $('#_sl_keyword').on('keyup', () => {keywordCheck()});
    // $('#_sl_title').on('keyup', () => {keywordCheck()});
    // $('#_sl_description').on('keyup', () => {keywordCheck()});

});