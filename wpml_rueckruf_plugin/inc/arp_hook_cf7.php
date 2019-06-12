<?php

/* 
 * Haengt sich in den CF7 Sende-Prozess ein und ergaenzt die zustaendigen Sachbearbeiter als CC Empfaenger
 */

add_action('wpcf7_before_send_mail', 'dynamic_addcc');
function dynamic_addcc($wpcf7) {

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
    
    // CF7 Variablen
    $post_id = $wpcf7->id();
    $submission = WPCF7_Submission::get_instance();
    
    // Form ID checken
    if ($cf7_formular_nummer == $post_id) {

        // Wurden Daten uebermittelt?
        if ($submission) {

            // Array vorbereiten
            $cc_email = array();
            
            // Gesendete Daten auslesen
            $posted_data = $submission->get_posted_data();
            
            // Zeitzone festlegen
            date_default_timezone_set("Europe/Berlin");
            
            // Themengebiet aus gesendeten Daten auslesen
            $posted_data_themengebiet = $posted_data[$themen_auswahlbox_id];
            $rueckrufzeit_zeit = date("\a\m d.m.Y \u\m H:i", $posted_data['rueckrufzeit_unixtimestamp']);

            // Neue Adressen der Sachbearbeiter in Array aufnehmen
            foreach ( $themen_zustaendigkeiten as $key => $entry ) {

                // Ist das Thema gesetzt und entspricht es der Auswahl?
                if ( isset($entry['thema']) && $entry['thema'] == $posted_data_themengebiet ) {

                    // Ein oder mehrere Sachbearbeiter gesetzt?
                    if ( isset( $entry['sachbearbeiter'] ) ) {
                        $cc_email = preg_split('/\r\n|[\r\n]/', $entry['sachbearbeiter']);
                    }

                }

            }

            // Adress-Array in Komma-Liste zerteilen
            $cclist = implode(', ', $cc_email);
            
            // Momentanen Mail-Koerper auslesen
            $mail_body = $wpcf7->prop('mail');

            // Wenn die Liste mit eMail Adressen nicht leer ist, diese in CC nehmen
            if (!empty($cclist)) {
                $mail_body['subject'] .= " ( Wunsch: $rueckrufzeit_zeit )";
                $mail_body['additional_headers'] = "Cc: $cclist";
            }

            // Neue Daten speichern
            $wpcf7->set_properties(array(
                "mail" => $mail_body
            ));

            // CF7 Instanz zurueck geben
            return $submission;
        }
    }
}
