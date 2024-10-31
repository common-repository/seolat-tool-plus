jQuery(function($){
	$(document).ready(function(){

	/* JUGGERNAUT Content Links */
	$('th.sl-content-autolinks-link-options.sl-link-options .my-check').change(function (){
		if( $('th.sl-content-autolinks-link-options.sl-link-options .my-check').attr('checked') ){
			$('.sl-content-autolinks-link input:checkbox').attr('checked','checked');
		}else{
			$('.sl-content-autolinks-link input:checkbox').removeAttr('checked');
		}
	});

	/* JUGGERNAUT Footer Links */
	$('th.sl-footer-autolinks-link-options.sl-link-options .my-check').change(function (){
		if( $('th.sl-footer-autolinks-link-options.sl-link-options .my-check').attr('checked') ){
			$('.sl-footer-autolinks-link td.sl-link-options input:checkbox').attr('checked','checked');
		}else{
			$('.sl-footer-autolinks-link td.sl-link-options input:checkbox').removeAttr('checked');
		}
	});

	});
});