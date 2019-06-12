<?php



/*
#####################################################################################################
#####################################################################################################
###################################### Contact Form 7 Shortcode #####################################
#####################################################################################################
#####################################################################################################
*/


// Contact Form 7 Shortcode [rueckrufthema] erstellen
/*
 * Erzeugt einen Shortcode, welcher direkt im Editor des Contact Form 7 Plugins genutzt wird.
 * 
 * Der Shortcode wird durch ein Select-Box mit der Themen-Auswahl (aus der Config-Seite) ersetzt.
 * Zudem erhaelt sie die ID und Klasse(n) aus der Config-Seite.
 * 
 */
add_action( 'wpcf7_init', 'custom_add_form_tag_rueckrufthema' );
function custom_add_form_tag_rueckrufthema() {
    wpcf7_add_form_tag( 'rueckrufthema', 'custom_rueckrufthema_form_tag_handler' );
}

// Unterfunktion fuer den Shortcode [rueckruf]
function custom_rueckrufthema_form_tag_handler( $tag ) {

    // Einstellungen aus der WPML Rueckruf Konfigurations-Seite
    $prefix = 'wpmlrueckruf'; 
    /* ... */
    $themen_zustaendigkeiten = wpmlrueckruf_get_option( $prefix . '_' . 'themen_zustaendigkeiten' );
    /* ... */
    $rueckrufe_ab_startzeit = wpmlrueckruf_get_option( $prefix . '_' . 'rueckrufe_ab_startzeit' );
    $rueckrufe_bis_endzeit = wpmlrueckruf_get_option( $prefix . '_' . 'rueckrufe_bis_endzeit' );
    $rueckrufe_bis_endzeit_freitag = wpmlrueckruf_get_option( $prefix . '_' . 'rueckrufe_bis_endzeit_freitag' );
    $minuten_schritte = wpmlrueckruf_get_option( $prefix . '_' . 'minuten_schritte' );
    $minuten_vorlauf_puffer = wpmlrueckruf_get_option( $prefix . '_' . 'minuten_vorlauf_puffer' );
    $tage_vorlauf_sperrliste = wpmlrueckruf_get_option( $prefix . '_' . 'tage_vorlauf_sperrliste' );
    $tage_rueckblick_anrufliste = wpmlrueckruf_get_option( $prefix . '_' . 'tage_rueckblick_anrufliste' );
    /* ... */
    $cf7_formular_nummer = wpmlrueckruf_get_option( $prefix . '_' . 'cf7_formular_nummer' );
    $termine_mit_themen_verknuepfen = wpmlrueckruf_get_option( $prefix . '_' . 'termine_mit_themen_verknuepfen' );    
    $trigger_element = wpmlrueckruf_get_option( $prefix . '_' . 'trigger_element' );
    $themen_auswahlbox_id = wpmlrueckruf_get_option( $prefix . '_' . 'themen_auswahlbox_id' );
    $themen_auswahlbox_class = wpmlrueckruf_get_option( $prefix . '_' . 'themen_auswahlbox_class' );

    // Themen anhand der Sortierungs-Nummer sortieren
    $sortierungs_array = array();
    foreach ($themen_zustaendigkeiten as $key => $val) {
        $sortierungs_array[$key] = $val['sortierung'];
    }
    array_multisort($sortierungs_array, SORT_ASC, $themen_zustaendigkeiten);

    // Themen aus der Config-Seite (Array) als Select formatieren
    $select_optionen = "";
    foreach ($themen_zustaendigkeiten as $key => $val) {
       $select_optionen .= "<option value='". $val['thema'] ."'>". $val['thema'] ."</option>";
    }

    // Return im Heredoc Schema - notwendig fuer Ersetzung des CF7 Shortcodes an Ort und Stelle
    $text = <<<EOT
    <span class="wpcf7-form-control-wrap $themen_auswahlbox_id">
        <select name="$themen_auswahlbox_id" class="$themen_auswahlbox_class" id="$themen_auswahlbox_id" aria-required="true" aria-invalid="false">
            <option value="">Bitte auswählen</option>
            $select_optionen
        </select>
    </span>
EOT;

    return $text;
    
}
