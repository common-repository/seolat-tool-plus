jQuery(function($){
	$(document).ready(function(){

	/* Meta Robot Tags */

	/* checkboxes select/deselect all */
	
	$('.noindex-check').each(function() {
		var $noindex_th = $(this);
		$noindex_th.change(function (){
			var $noindex_td = $noindex_th.closest('table').find('[class$="-meta_robots_noindex"] input:checkbox');
			if( $noindex_th.attr('checked') ){
				$noindex_td.attr('checked','checked');
			}else{
				$noindex_td.removeAttr('checked');
			}
		});
	});

	$('.nofollow-check').each(function() {
		var $nofollow_th = $(this);
		$nofollow_th.change(function (){
			var $nofollow_td = $nofollow_th.closest('table').find('[class$="-meta_robots_nofollow"] input:checkbox');
			if( $nofollow_th.attr('checked') ){
				$nofollow_td.attr('checked','checked');
			}else{
				$nofollow_td.removeAttr('checked');
			}
		});
	});
	
	$('.noarchive-check').each(function() {
		var $noarchives_th = $(this);
		$noarchives_th.change(function (){
			var $noarchives_td = $noarchives_th.closest('table').find('[class$="-meta_robots_noarchive"] input:checkbox');
			if( $noarchives_th.attr('checked') ){
				$noarchives_td.attr('checked','checked');
			}else{
				$noarchives_td.removeAttr('checked');
			}
		});
	});
	
	$('.nosnippet-check').each(function() {
		var $nosnippet_th = $(this);
		$nosnippet_th.change(function (){
			var $nosnippet_td = $nosnippet_th.closest('table').find('[class$="-meta_robots_nosnippet"] input:checkbox');
			if( $nosnippet_th.attr('checked') ){
				$nosnippet_td.attr('checked','checked');
			}else{
				$nosnippet_td.removeAttr('checked');
			}
		});
	});

	});
});