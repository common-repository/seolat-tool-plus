jQuery(document).ready(function ($) {

    function keywordCheck() {

        if ($('#_sl_keyword').length = 0) {
            return; //keywordCheck();
        }

        let max_title_seo_keyword = 1;
        let max_desc_seo_keyword = 1;
        let max_content_keyword = 1;
        let max_first_paragraph_keyword = 1;
        let max_url_keyword = 0;

        let keyword = $('#_sl_keyword').val();
        let title_seo = $('#_sl_title').val();
        let desc_seo = $('#_sl_description').val();
        let url = ($('#sample-permalink').length > 0) ? $('#sample-permalink a').text() : false;
        console.log(url)
        let content = '';
        let html_content = '';
        let first_paragraph = '';

        if(tinyMCE.get('content')==null) {
            $('#sl_check_keyword_result').html('We can only check the content of post for the classic editor.');
            // content = $('#content').val();
        } else {
            content = tinyMCE.get('content').getContent({format: 'text'});
            html_content = tinyMCE.get('content').getContent();
            $(html_content).each((k,e) => {
                if ($(e).prop("tagName") == 'P') {
                    first_paragraph = $(e).text();
                    return false;
                }
            })
        }
        console.log(tinyMCE.get('content'))

        console.log(content)

        if ( !keyword.length ) {
            $('#sl_check_keyword_result').html('No focus keyword');
            return;
        }
        let regex_keyword = new RegExp(keyword, 'igm');

        title_seo = title_seo.replace(new RegExp('[\\w]*'+keyword+'[\\w]+|[\\w]+'+keyword+'[\\w]*','igm'), '');
        let title_seo_match = title_seo.match(regex_keyword);
        let title_seo_match_count = (title_seo_match) ? title_seo_match.length : 0;

        desc_seo = desc_seo.replace(new RegExp('[\\w]*'+keyword+'[\\w]+|[\\w]+'+keyword+'[\\w]*','igm'), '');
        let desc_seo_match = desc_seo.match(regex_keyword);
        let desc_seo_match_count = (desc_seo_match) ? desc_seo_match.length : 0;

        content = content.replace(new RegExp('[\\w]*'+keyword+'[\\w]+|[\\w]+'+keyword+'[\\w]*','igm'), '');
        let content_seo_match = content.match(regex_keyword);
        let content_seo_match_count = (content_seo_match) ? content_seo_match.length : 0;

        first_paragraph = first_paragraph.replace(new RegExp('[\\w]*'+keyword+'[\\w]+|[\\w]+'+keyword+'[\\w]*','igm'), '');
        let first_paragraph_match = first_paragraph.match(regex_keyword);
        let first_paragraph_match_count = (first_paragraph_match) ? first_paragraph_match.length : 0;

        url = url.replace(new RegExp('[\\w]*'+keyword+'[\\w]+|[\\w]+'+keyword+'[\\w]*','igm'), '');
        let url_match = url.match(regex_keyword);
        let url_match_count = (url_match) ? url_match.length : 0;

        let html = '';
        
        let title_keyword_percent = (max_title_seo_keyword > 1) ? ((title_seo_match_count <= max_title_seo_keyword) ? title_seo_match_count/max_title_seo_keyword : 0) : Math.min( title_seo_match_count, 1);
        html += '<p><span style="font-weight: 600; color: '+ ["hsl(",(title_keyword_percent*120).toString(10),",100%,40%)"].join("") + '">' + ((title_seo_match_count > 0) ? 'Have ' + title_seo_match_count + ' keywords' : 'Don\'t have any keyword' ) + '</span> in your seo title.';
        if (max_title_seo_keyword > 1) html += ((title_seo_match_count > max_title_seo_keyword) ? ' Keyword appear too much in your title. Number of recommendations: ' + max_title_seo_keyword + '.' : ' Number of recommendations: ' + max_title_seo_keyword) + '</p>';
        
        
        let desc_keyword_percent = (max_desc_seo_keyword > 1) ? ((desc_seo_match_count <= max_desc_seo_keyword) ? desc_seo_match_count/max_desc_seo_keyword : 0) : Math.min( desc_seo_match_count, 1);
        html += '<p><span style="font-weight: 600; color: '+ ["hsl(",(desc_keyword_percent*120).toString(10),",100%,40%)"].join("") + '">' + ((desc_seo_match_count > 0) ? 'Have ' + desc_seo_match_count + ' keywords' : 'Don\'t have any keyword' ) + '</span> in your seo description.';
        if (max_desc_seo_keyword > 1) html += ((desc_seo_match_count > max_desc_seo_keyword) ? ' Keyword appear too much in your description. Number of recommendations: ' + max_desc_seo_keyword + '.' : ' Number of recommendations: ' + max_desc_seo_keyword) + '</p>';
        $('#sl_check_keyword_result').html(html);
        
        
        let content_keyword_percent = (max_content_keyword > 1) ? ((content_seo_match_count <= max_content_keyword) ? content_seo_match_count/max_content_keyword : 0) : Math.min( content_seo_match_count, 1);
        html += '<p><span style="font-weight: 600; color: '+ ["hsl(",(content_keyword_percent*120).toString(10),",100%,40%)"].join("") + '">' + ((content_seo_match_count > 0) ? 'Have ' + content_seo_match_count + ' keywords' : 'Don\'t have any keyword' ) + '</span> in your content.';
        if (max_content_keyword > 1) html += ((content_seo_match_count > max_content_keyword) ? ' Keyword appear too much in your content. Number of recommendations: ' + max_content_keyword + '.' : ' Number of recommendations: ' + max_content_keyword) + '</p>';
        $('#sl_check_keyword_result').html(html);
        
        
        let first_paragraph_keyword_percent = (max_first_paragraph_keyword > 1) ? ((first_paragraph_match_count <= max_first_paragraph_keyword) ? first_paragraph_match_count/max_first_paragraph_keyword : 0) : Math.min( first_paragraph_match_count, 1);
        html += '<p><span style="font-weight: 600; color: '+ ["hsl(",(first_paragraph_keyword_percent*120).toString(10),",100%,40%)"].join("") + '">' + ((first_paragraph_match_count > 0) ? 'Have ' + first_paragraph_match_count + ' keywords' : 'Don\'t have any keyword' ) + '</span> in your first paragraph.';
        if (max_first_paragraph_keyword > 1) html += ((first_paragraph_match_count > max_first_paragraph_keyword) ? ' Keyword appear too much in first paragraph. Number of recommendations: ' + max_first_paragraph_keyword + '.' : ' Number of recommendations: ' + max_first_paragraph_keyword) + '</p>';
        $('#sl_check_keyword_result').html(html);
        
        
        let url_keyword_percent = (max_url_keyword > 1) ? ((url_match_count <= max_url_keyword) ? url_match_count/max_url_keyword : 0) : Math.min( url_match_count, 1);
        html += '<p><span style="font-weight: 600; color: '+ ["hsl(",(url_keyword_percent*120).toString(10),",100%,40%)"].join("") + '">' + ((url_match_count > 0) ? 'Have ' + url_match_count + ' keywords' : 'Don\'t have any keyword' ) + '</span> in your url.';
        if (max_url_keyword > 1) html += ((url_match_count > max_url_keyword) ? ' Keyword appear too much in url. Number of recommendations: ' + max_url_keyword + '.' : ' Number of recommendations: ' + max_url_keyword) + '</p>';
        $('#sl_check_keyword_result').html(html);
    }
    $('#content').on('keyup', () => {keywordCheck()});
    $('#_sl_keyword').on('keyup', () => {keywordCheck()});
    $('#_sl_title').on('keyup', () => {keywordCheck()});
    $('#_sl_description').on('keyup', () => {keywordCheck()});
    $('#edit-slug-box').on('change', '#editable-post-name-full', () => {keywordCheck()});
    $("body").on('DOMSubtreeModified', "#edit-slug-box", function() {
        console.log($("body #sample-permalink").find('a').length)
        if ($("body #sample-permalink").find('a').length > 0)
            keywordCheck();
    });
    $(document).on( 'tinymce-editor-init', function( event, editor ) {
        if (editor.id == 'content') {
            keywordCheck()
            editor.on('keyup', function() {
                keywordCheck()
            })
        }
    });
});