/* Rueckruf-Button einblenden */
jQuery(function(){ 
    
    if(window.innerWidth > 768) {
        var appendA = '<div class="wpml_rueckruf_plugin_button" style="position: absolute;right: 18px;top: 25px; z-index: 4;"><a href="'+rueckruf_seiten_url+'"><img src="'+rueckruf_button_url+'"></a></div>';
        
        jQuery('.header figure.media-wrapper').css('position', 'relative'); 
        jQuery('.header figure.media-wrapper').append(appendA);
        
        jQuery('#carousel-example-generic').append(appendA);
    }
    else {
        var appendA = '<div class="wpml_rueckruf_plugin_button" style="position: absolute;right: 56px;top: 85px; z-index: 4;"><a href="'+rueckruf_seiten_url+'"><img style="width:50px;" src="'+rueckruf_button_url+'"></a></div>';
        
        jQuery('.header .navbar').append(appendA);
    }

});
