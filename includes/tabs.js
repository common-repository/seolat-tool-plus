function sl_init_tabs()
{
	/* if this is not an SEOLAT Tool Plus admin page, quit */
	if (!jQuery("#sl-tabset").length) return;		
	
	/* init markup for tabs */
	jQuery('#sl-tabset').prepend("<ul><\/ul>");
	jQuery('#sl-tabset > fieldset').each(function(i)
	{
		id      = jQuery(this).attr('id');
		caption = jQuery(this).find('h3').text();
		jQuery('#sl-tabset > ul').append('<li><a href="#'+id+'"><span>'+caption+"<\/span><\/a><\/li>");
		jQuery(this).find('h3').hide();					    
	});
	
	/* init the tabs plugin */
	var jquiver = undefined == jQuery.ui ? [0,0,0] : undefined == jQuery.ui.version ? [0,1,0] : jQuery.ui.version.split('.');
	switch(true) {
		// tabs plugin has been fixed to work on the parent element again.
		case jquiver[0] >= 1 && jquiver[1] >= 7:
			jQuery("#sl-tabset").tabs();
			break;
		// tabs plugin has bug and needs to work on ul directly.
		default:
			jQuery("#sl-tabset > ul").tabs(); 
	}
	
	if (location.hash.length) {
		jQuery(document).ready(function() {
			sl_hash_form(location.hash);
		});
		window.scrollTo(0,0);
	}
	
	/* handler for opening the last tab after submit (compability version) */
	jQuery('#sl-tabset ul a').click(function(i){
		sl_hash_form(jQuery(this).attr('href'));
	});
}

jQuery(document).ready(function() {
	sl_init_tabs();
});

function sl_hash_form(hash) {
	var form   = jQuery('#sl-admin-form');
	if (form) {
		var action = form.attr("action").split('#', 1) + hash;
		// an older bug pops up with some jQuery version(s), which makes it
		// necessary to set the form's action attribute by standard javascript 
		// node access:						
		form.get(0).setAttribute("action", action);
	}
}