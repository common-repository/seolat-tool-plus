jQuery(function($){
	$(document).ready(function(){

	/* Link Mask Generator */
	$('th.sl-internal-link-aliases-alias-delete.sl-alias-delete .my-check').change(function (){
		if( $('th.sl-internal-link-aliases-alias-delete.sl-alias-delete .my-check').attr('checked') ){
			$('.sl-internal-link-aliases-alias input:checkbox').attr('checked','checked');
		}else{
			$('.sl-internal-link-aliases-alias input:checkbox').removeAttr('checked');
		}
	});

	});
});