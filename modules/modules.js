/*global jQuery:false, alert */
(function($) { 
		"use strict";
jQuery(document).ready(function($) {
	$('input, textarea, select', 'div.sdf-admin').change(sl_enable_unload_confirm);
	$('form', 'div.sdf-admin').submit(sl_disable_unload_confirm);
	
	$('#wpbody').on('click', '.sl_toggle_hide', function(e) {
		e.preventDefault();
		var selector = $(this).data('toggle'),
		to_toggle = $('#'+selector);
		to_toggle.slideToggle();
	});
	$('#wpbody').on('click', '.sl_toggle_up', function(e) {
		e.preventDefault();
		var selector = $(this).data('toggle'),
		to_toggle = $('#'+selector);
		to_toggle.slideToggle();
	});
});
})(jQuery);

function sl_reset_textbox(id, d, m, e) {
	if (confirm(m+"\n\n"+d)) {
		document.getElementById(id).value=d;
		e.className='hidden';
		sl_enable_unload_confirm();
	}
}

function sl_textbox_value_changed(e, d, l) {
	if (e.value==d)
		document.getElementById(l).className='hidden';
	else
		document.getElementById(l).className='';
}

function sl_enable_unload_confirm() {
	window.onbeforeunload = sl_confirm_unload_message;
}

function sl_disable_unload_confirm() {
	window.onbeforeunload = null;
}

function sl_confirm_unload_message() {
	return slModulesModulesL10n.unloadConfirmMessage;
}
