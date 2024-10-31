jQuery(function($){
	$(document).ready(function(){

        /* Redirect 301 rules disabled */
        $('th.sl-301-rules-rule-disabled .my-check').change(function (){
            if( $('th.sl-301-rules-rule-disabled .my-check').attr('checked') ){
                $('.sl-301-rules-rules td.sl-rule-disabled input:checkbox').attr('checked','checked');
            }else{
                $('.sl-301-rules-rules td.sl-rule-disabled input:checkbox').removeAttr('checked');
            }
        });

        /* Redirect 301 rules delete */
        $('th.sl-301-rules-rule-delete .my-check').change(function (){
            if( $('th.sl-301-rules-rule-delete .my-check').attr('checked') ){
                $('.sl-301-rules-rules td.sl-rule-delete input:checkbox').attr('checked','checked');
            }else{
                $('.sl-301-rules-rules td.sl-rule-delete input:checkbox').removeAttr('checked');
            }
        });

	});
});