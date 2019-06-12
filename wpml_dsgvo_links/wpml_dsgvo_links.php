<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/* also read https://codex.wordpress.org/Writing_a_Plugin */

/*
Plugin Name: .CSTM DSGVO Links
Description: Schaltet vor alle externe Links ein Popup mit einem Hinweistext zur DSGVO. Es werden die Shortcodes "lnk" (mit Text) und "lnk2" (ohne Text) bereit gestellt um Links zu umgeben. Bereits erzeugte (externe) Links werden automatisch umgewandelt.
Version: 1.0.4
Author: Marco Heizmann
*/

#region Shortener Scripte einbinden
function dsgvo_links_anzeigen(){
    
    // Script und Style
    wp_enqueue_script( 'dsgvo_links_js', plugin_dir_url(__FILE__) . 'inc/js/dsgvo_links.js' , array('jquery'), '',  false );
    wp_enqueue_style( 'dsgvo_links_css', plugin_dir_url(__FILE__) . 'inc/css/dsgvo_links.css');
    
}
add_action( 'wp_enqueue_scripts', 'dsgvo_links_anzeigen' );
#endregion

#region Shortcode erzeugen
function dsgvo_links_shortcode($atts = [], $content = null) {
    
    // Konfiguration der Settings-Seite einlesen 
    $options = get_option( 'dsgvo_links_settings' );
    
    // http und https entfernen
    $content = preg_replace('/http[s]*:\/\//i', '', $content);
    
    $content = explode("###",$content,3);
	
    // Einfuegen
    if (!$content[1])
	$returner = "(<span class='lnk_info' data-trgt='$content[0]'>" . $options['dsgvo_links_text_field_0'] . "</span>)";
    else
	$returner = "<span class='lnk_text' data-trgt='$content[0]'>" . $content[1] . "</span>";
    
    // Shortcode mit Inhalt zurueck geben
    return $returner;
    
}
add_shortcode('lnk', 'dsgvo_links_shortcode');
#endregion

#region Shortcode 2 (ohne Linktext) erzeugen
function dsgvo_links_shortcode_2($atts, $content = null) {
    
    // Attribute aus dem Shortcode lesen und Platzhalter bereit stellen wenn nicht gesetzt
    extract(shortcode_atts( 
      array(
        'url' => '#',
      ), 
      $atts ));
    
    // http und https entfernen
    $url = preg_replace('/http[s]*:\/\//i', '', esc_attr($url));
    
    // Einfuegen
    $returner = "<span class='lnk_info' data-trgt='$url'>".$content."</span>";
    
    // Shortcode mit Inhalt zurueck geben
    return $returner;
    
}
add_shortcode('lnk2', 'dsgvo_links_shortcode_2');
#endregion

#region ArrayMatch Helfer Funktion

// Helfer Funktion - prueft ob ein Array Eintrag Teil der URL ist ($needles = array, $haystack = string)        
function arraymatch($needles = array(), $haystack = "") {
    foreach($needles as $needle){
        if (strpos($haystack, $needle) !== false) {
            return true;
        }
    }
    return false;
}   

#endregion

#region Alle bereits erzeugten Links automatisch umwandeln
function dsgvo_links_autoreplace( $content ) {
        
    // Zu Testzwecken die Funktion nur fuer eingeloggte Benutzer ausfuehren
    $user = wp_get_current_user();
    $allowed_roles = array('editor', 'administrator', 'author');	
    if( array_intersect($allowed_roles, $user->roles ) || true===true ) {
    
        // if( isset($_GET['dsgvo_check']) ) {  } 
    
        // Preg Replace Funktion fuer alle Links
        $content = preg_replace_callback('/<a(?:[\s]+title="[^"]*")?(?:[\s]+href="([^"]*)")?(?:[^>]*)>([^<]*)/i', function($m) {
            
            // Handelt es sich um einen internen oder externen link, hat er einen "verbotenen" href oder gar keinen?
            $needles = array('javascript', '#', 'mailto');
            if (strpos($m[1], home_url()) === false && strlen($m[1]) > 1 && !arraymatch($needles, $m[1]) ) {
            
                // Externer Link ... http, https und www entfernen
                $m[1] = preg_replace('/http[s]*:\/\//i', '', $m[1]);
                
                // return '<a href="'.$m[1].'" rel="nofollow" target="_blank">'.$m[2].'</a>';
                return "$m[2] (<span class='lnk_info' data-trgt='$m[1]'>link</span>)";
                
            } 
            else {
                
                // Interner Link (nicht veraendern)
                return $m[0];
                
            }
            
        }, $content);
            
    }
        
    return $content;
    
}
#add_filter('the_content', 'dsgvo_links_autoreplace', 99999); /* Hoechste Prioritaet */

#endregion

#region Popup Html Code im Footer platzieren
function dsgvo_links_footer_html() {

    // Konfiguration der Settings-Seite einlesen 
    $options = get_option( 'dsgvo_links_settings' );

    // Popup am Ende der Seite einfuegen
    ?>
    <div id="lnk_modal" class="lnk_modal">
        <div class="lnk_modal_content">
            <h2><?php echo $options['dsgvo_links_text_field_1']; ?></h2>
            <p><?php echo $options['dsgvo_links_textarea_field_1']; ?></p>
            <br>
            <table style="width:100%">
                <tr>
                    <td style="width: 50%;">
                        <a href="#" target="_blank" style="width:90%" class="<?php echo $options['dsgvo_links_text_field_4']; ?> lnk_modal_link"><?php echo $options['dsgvo_links_text_field_2']; ?></a>
                    </td>
                    <td style="text-align: right;">
                        <input type="button" value="<?php echo $options['dsgvo_links_text_field_3']; ?>" style="width:90%" class="<?php echo $options['dsgvo_links_text_field_5']; ?> lnk_modal_close">
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php

}
add_action('wp_footer', 'dsgvo_links_footer_html');
#endregion

#region Config Page 
// (thanks to http://wpsettingsapi.jeroensormani.com/)

add_action( 'admin_menu', 'dsgvo_links_add_admin_menu' );
add_action( 'admin_init', 'dsgvo_links_settings_init' );


function dsgvo_links_add_admin_menu() { 

	add_options_page( 'DSGVO Links', 'DSGVO Links', 'manage_options', 'dsgvo_links', 'dsgvo_links_options_page' );

}


function dsgvo_links_settings_init() { 

	register_setting( 'pluginPage__wpml_dsgvo_links', 'dsgvo_links_settings' );

	add_settings_section(
		'dsgvo_links_pluginPage_section', 
		__( 'Einstellungen', 'wordpress' ), 
		'dsgvo_links_settings_section_callback', 
		'pluginPage__wpml_dsgvo_links'
	);

	add_settings_field( 
		'dsgvo_links_text_field_0', 
		__( 'Linktext', 'wordpress' ), 
		'dsgvo_links_text_field_0_render', 
		'pluginPage__wpml_dsgvo_links', 
		'dsgvo_links_pluginPage_section' 
	);

    add_settings_field( 
		'dsgvo_links_text_field_1', 
		__( 'Popup Überschrift', 'wordpress' ), 
		'dsgvo_links_text_field_1_render', 
		'pluginPage__wpml_dsgvo_links', 
		'dsgvo_links_pluginPage_section' 
	);

	add_settings_field( 
		'dsgvo_links_textarea_field_1', 
		__( 'Popup Hinweistext', 'wordpress' ), 
		'dsgvo_links_textarea_field_1_render', 
		'pluginPage__wpml_dsgvo_links', 
		'dsgvo_links_pluginPage_section' 
	);

	add_settings_field( 
		'dsgvo_links_text_field_2', 
		__( 'Popup Button Weiter', 'wordpress' ), 
		'dsgvo_links_text_field_2_render', 
		'pluginPage__wpml_dsgvo_links', 
		'dsgvo_links_pluginPage_section' 
	);

	add_settings_field( 
		'dsgvo_links_text_field_3', 
		__( 'Popup Button Abbrechen', 'wordpress' ), 
		'dsgvo_links_text_field_3_render', 
		'pluginPage__wpml_dsgvo_links', 
		'dsgvo_links_pluginPage_section' 
	);

	add_settings_field( 
		'dsgvo_links_text_field_4', 
		__( 'CSS Class 1. Button', 'wordpress' ), 
		'dsgvo_links_text_field_4_render', 
		'pluginPage__wpml_dsgvo_links', 
		'dsgvo_links_pluginPage_section' 
	);

	add_settings_field( 
		'dsgvo_links_text_field_5', 
		__( 'CSS Class 2. Button', 'wordpress' ), 
		'dsgvo_links_text_field_5_render', 
		'pluginPage__wpml_dsgvo_links', 
		'dsgvo_links_pluginPage_section' 
	);


}


function dsgvo_links_text_field_0_render() { 

	$options = get_option( 'dsgvo_links_settings' );
	?>
	<input type='text' name='dsgvo_links_settings[dsgvo_links_text_field_0]' value='<?php echo $options['dsgvo_links_text_field_0']; ?>'>
	<?php

}


function dsgvo_links_text_field_1_render() { 

	$options = get_option( 'dsgvo_links_settings' );
	?>
	<input type='text' name='dsgvo_links_settings[dsgvo_links_text_field_1]' value='<?php echo $options['dsgvo_links_text_field_1']; ?>'>
	<?php

}


function dsgvo_links_textarea_field_1_render() { 

	$options = get_option( 'dsgvo_links_settings' );
	?>
	<textarea cols='40' rows='5' name='dsgvo_links_settings[dsgvo_links_textarea_field_1]'><?php echo $options['dsgvo_links_textarea_field_1']; ?></textarea>
	<?php

}


function dsgvo_links_text_field_2_render() { 

	$options = get_option( 'dsgvo_links_settings' );
	?>
	<input type='text' name='dsgvo_links_settings[dsgvo_links_text_field_2]' value='<?php echo $options['dsgvo_links_text_field_2']; ?>'>
	<?php

}


function dsgvo_links_text_field_3_render() { 

	$options = get_option( 'dsgvo_links_settings' );
	?>
	<input type='text' name='dsgvo_links_settings[dsgvo_links_text_field_3]' value='<?php echo $options['dsgvo_links_text_field_3']; ?>'>
	<?php

}


function dsgvo_links_text_field_4_render() { 

	$options = get_option( 'dsgvo_links_settings' );
	?>
	<input type='text' name='dsgvo_links_settings[dsgvo_links_text_field_4]' value='<?php echo $options['dsgvo_links_text_field_4']; ?>'>
	<?php

}


function dsgvo_links_text_field_5_render() { 

	$options = get_option( 'dsgvo_links_settings' );
	?>
	<input type='text' name='dsgvo_links_settings[dsgvo_links_text_field_5]' value='<?php echo $options['dsgvo_links_text_field_5']; ?>'>
	<?php

}


function dsgvo_links_settings_section_callback() { 

	echo __( 'Einstellungen für das DSGVO Link Plugin', 'wordpress' );

}


function dsgvo_links_options_page() { 

	?>
	<form action='options.php' method='post'>

		<h2>DSGVO Links</h2>

		<?php
		settings_fields( 'pluginPage__wpml_dsgvo_links' );
		do_settings_sections( 'pluginPage__wpml_dsgvo_links' );
		submit_button();
		?>

	</form>
	<?php

}

#endregion
