jQuery(function(jQuery) {
	
    // Open Modal
	jQuery('.lnk_info').on('click', function(e) {
		e.preventDefault();
		
        // Read link
        jQuery('.lnk_modal_link').attr('href', 'https://' + jQuery(this).data('trgt') );
        
        // #lnk_modal exists
		if (jQuery('#lnk_modal').length > 0) { 
			jQuery('#lnk_modal').fadeIn();            
		}
				
	});

	jQuery('.lnk_text').on('click', function(e) {
		e.preventDefault();
		
        // Read link
        jQuery('.lnk_modal_link').attr('href', 'https://' + jQuery(this).data('trgt') );
        
        // #lnk_modal exists
		if (jQuery('#lnk_modal').length > 0) { 
			jQuery('#lnk_modal').fadeIn();            
		}
				
	});
	
	// Close Modal
	jQuery('body').on('click', '#lnk_modal', function() {
		jQuery('#lnk_modal').fadeOut();
	});
    
    jQuery('.lnk_modal_close').on('click', function() {
		jQuery('#lnk_modal').fadeOut();
	});

});
