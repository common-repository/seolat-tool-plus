jQuery(document).ready( function($) {
	$('.supiat-update').on('click', function() {
		var post_id = $(this).attr("id").replace('pid-', '');
		$('#wrapper-' + post_id + ' .waiting').show();
		var alt_text = $('#alt-' + post_id).val();

		$.post( ajaxurl, {
			action: 'supiat_alt_update',
			'post_id' : post_id,
			'alt_text' : alt_text
		},
			function(r) {
				if (r) {
				$('#wrapper-' + post_id + ' .waiting').hide();
				}
			}
		);
	});
});