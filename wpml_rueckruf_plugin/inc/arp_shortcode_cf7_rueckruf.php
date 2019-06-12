<?php



/*
#####################################################################################################
#####################################################################################################
###################################### Contact Form 7 Shortcode #####################################
#####################################################################################################
#####################################################################################################
*/



// Hauptfunktion
function rueckruf_get_arrays( $filter=NULL ) {
    
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
    
    // Zeitzone festlegen
    date_default_timezone_set("Europe/Berlin");

    /* 
     * --------------------------------------------------------------
     * 
     * Mit dem gesendeten Formular wird der Tag und gewaehlte Uhrzeit im Unix Format als hidden uebergeben und dank Flamingo in der DB gespeichert.
     * 
     * Diese Funktion erzeugt 3 Arrays mit Daten zwischen Start- und End-Uhrzeit und durchsucht die DB nach dem Aendern des Select Feldes mittels Ajax 
     * nach gespeicherten Formular-Eintraege welche den typischen Rueckruf-Service-Formular-Syntax beinhalten: 
     * (_field_themengebiet, _field_timepicker, _field_timepickernext) sowie eine extra Blacklist Tabelle.
     * Dabei werden nur aktuelle und zukuenftige Termine ausgelesen (Unix-Timestamp)
     * Hinweis: das Wordpress Addon Flamingo ist dafuer noetig: https://wordpress.org/plugins/flamingo/
     * 
     * Die gefundenen Werte werden genutzt um die vergebenen Uhrzeiten aus dem erzeugten Voll-Uhrzeit-Array unten heraus zu halten.
     * Weiterhin wird eine Datums- und Uhrzeit Blackliste bereit gestellt, die ebenfalls heraus gefiltert wird.
     * 
     * Es werden 3 Uhrzeit-Array erzeugt
     *      1. mit: aktuelle Uhrzeit auf Steps gerundet + Puffer
     *      2. mit: alle Uhrzeiten im Range fuer +1 Werktag
     *      3. mit: alle Uhrzeiten im Range fuer +2 Werktage
     * 
     * LOGIK:
     * 1. Datenbank-Abfrage der mittels Flamingo gespeicherten Formular-Daten (evtl. mit dem Zusatz-Filter "themengebiet")
     * 2. Datenbank-Abfrage der Uhrzeit/Datums-Blackliste
     * 3. Alle Uhrzeiten von Start bis Ende erzeugen und in Zeit-Array uebergeben
     * 4. Heute-Array* erzeugen (Kopie von Zeit-Array) - dann vergangene (+ Puffer) UND vergebene Uhrzeiten (aus DB) entfernen
     * 5. Next-Array* per Schleife erzeugen... wird +1 Werktag fuer Next kein freier Wert gefunden, dann wird weiter hoch gezaehlt. 
     *    So koennen auch laengere Sperrzeiten (z.B. Urlaub) ueberbrueckt werden. 
     *    * wenn Freitag, dann separate Endzeit berücksichtigen.
     * 
     * --------------------------------------------------------------
     */

    // Vergebene Termine und Blacklist Array vormerken
    $vergebene_termine = [];
    $datum_blacklist = [];
    
    // Datenbank-Abfrage (gruppierte / doppelte Abfrage + nur Eintraege mit einem Unix-Timestamp in der Zukunft)
    global $wpdb;
    // Sollen Filter verwendet werden?
    if($termine_mit_themen_verknuepfen == "yes") {
        
        // Vergebene Termine
        $results = $wpdb->get_results(  
            $wpdb->prepare( "
            SELECT pm.meta_value  AS themengebiet, 
                   pm2.meta_value AS unixtimestamp
            FROM   wp_postmeta pm 
                   INNER JOIN wp_postmeta pm2 
                           ON pm2.post_id = pm.post_id 
                              AND pm2.meta_key = '_field_rueckrufzeit_unixtimestamp' 
            WHERE  pm.meta_key = '_field_themengebiet' 
                   AND pm.meta_value = '%s'
                   AND pm2.meta_value > UNIX_TIMESTAMP()
            ", $filter )
        );
        
        // Blacklist
        $resultsBlacklist = $wpdb->get_results(  
            $wpdb->prepare( "
            SELECT themengebiet, unixtimestamp
            FROM   wp_wpml_rueckruf_blocks
            WHERE  unixtimestamp > UNIX_TIMESTAMP()
            AND themengebiet = '%s'
            ", $filter )
        );

        // Blockierte Tage
        $resultsBlacklistDays = $wpdb->get_results(  
            $wpdb->prepare( "
            SELECT themengebiet, unixtimestampVon, unixtimestampBis
            FROM   wp_wpml_rueckruf_tage_blocks
            WHERE  themengebiet = '%s'
            ", $filter )
        );
        
    }
    else {
        
        // Vergebene Termine
        $results = $wpdb->get_results(  
            $wpdb->prepare( "
            SELECT pm.meta_value  AS themengebiet, 
                   pm2.meta_value AS unixtimestamp
            FROM   wp_postmeta pm 
                   INNER JOIN wp_postmeta pm2 
                           ON pm2.post_id = pm.post_id 
                              AND pm2.meta_key = '_field_rueckrufzeit_unixtimestamp' 
            WHERE  pm.meta_key = '_field_themengebiet'
                   AND pm2.meta_value > UNIX_TIMESTAMP()
            ", NULL)
        );
        
        // Blacklist
        $resultsBlacklist = $wpdb->get_results(  
            $wpdb->prepare( "
            SELECT themengebiet, unixtimestamp
            FROM   wp_wpml_rueckruf_blocks
            WHERE  unixtimestamp > UNIX_TIMESTAMP()
            ", NULL)
        );

        // Blockierte Tage
        $resultsBlacklistDays = $wpdb->get_results(  
            $wpdb->prepare( "
            SELECT themengebiet, unixtimestampVon, unixtimestampBis
            FROM   wp_wpml_rueckruf_tage_blocks
            WHERE  unixtimestampBis >= UNIX_TIMESTAMP()
            ", NULL)
        );
        
    }
    
    // Die DB Rueckgabe durchlaufen und alle Unix-Timestamps in die Liste der vergebenen Nummern eintragen
    foreach( $results as $key => $row) { 
        array_push($vergebene_termine, $row->unixtimestamp);
    }
    
    // Die DB Blacklist Rueckgabe durchlaufen und alle Unix-Timestamps in die Liste der vergebenen Nummern eintragen
    foreach( $resultsBlacklist as $key => $row) { 
        array_push($datum_blacklist, $row->unixtimestamp);
    }
            
    // Ist das Datum ein Wochenende?
    function isWeekend($date) {
        $weekDay = date('w', $date);
        /*if( current_user_can('editor') || current_user_can('administrator') ) {
            echo "<!--A ".$date." (".$weekDay.") Formated: " .gmdate("d.m.Y H:i:s", $date). "-->";
        }*/
        return ($weekDay == 0 || $weekDay == 6);
    }
    
    // Ist das Datum ein Freitag?
    function isFriday($date) {
        $weekDay = date('w', $date);
        /*if( current_user_can('editor') || current_user_can('administrator') ) {
            echo "<!--B ".$date." (".$weekDay.") Formated: " .gmdate("d.m.Y H:i:s", $date). "-->";
        }*/
        return ($weekDay == 5);
    }

    // Pruefe ob das Datum in den Sperr-Zeitraum der Tageweisen Blockierung faellt
    function is_date_in_blockrange($date, $resultsBlacklistDays=[]) {
        foreach ( $resultsBlacklistDays as $key => $row ) {
            if($date > $row->unixtimestampVon && $date < $row->unixtimestampBis) {
                return true;
            }
        }
        return false;
    }
    
    // Zeitbereich erzeugen (heute + Puffer)
    // ------------------------------
    $now_plus_puffer = strtotime('+'.$minuten_vorlauf_puffer.' minutes');
    $now_plus_puffer_rounded = ceil($now_plus_puffer / ($minuten_schritte * 60)) * ($minuten_schritte * 60);
    // ------------------------------
    // Rueckwaertszaehlung verhindern
    if(isFriday($now_plus_puffer_rounded)) {
        if( $now_plus_puffer_rounded <= strtotime($rueckrufe_bis_endzeit_freitag) && !isWeekend($now_plus_puffer_rounded) ) {
            $time_range_today_array = range($now_plus_puffer_rounded, strtotime($rueckrufe_bis_endzeit_freitag), $minuten_schritte*60);            
        } else {
            $time_range_today_array = [];
        }
    } 
    else {
        if( $now_plus_puffer_rounded <= strtotime($rueckrufe_bis_endzeit) && !isWeekend($now_plus_puffer_rounded) ) {
            $time_range_today_array = range($now_plus_puffer_rounded, strtotime($rueckrufe_bis_endzeit), $minuten_schritte*60);
        } else {
            $time_range_today_array = [];
        }
    }

    // Vergebene Termine und Blacklist Termine entfernen
    $time_range_today_array_clean = array_diff($time_range_today_array, $vergebene_termine);
    $time_range_today_array_cleaner = array_diff($time_range_today_array_clean, $datum_blacklist);

    // Pruefe ob die uebrigen Termine in den Sperr-Zeitraum der Tageweisen Blockierung fallen
    // Jeden Tag auf jeden Blockiereintrag pruefen
    foreach ($time_range_today_array_cleaner as $key => $datum) {
        if(is_date_in_blockrange($datum, $resultsBlacklistDays)) {
            unset($time_range_today_array_cleaner[$key]);
        }
    }

    /* ... */
    
    // Schleife fuer die naechsten beiden freien Tage
    // Wird +1 Werktag kein Termin gefunden, dann wird der naechste Werktag getestet...
    $maximale_vorausschau = 100;
    for($tag = 1; $tag<$maximale_vorausschau; $tag++){
        
        // Zeitbereich erzeugen (naechster Arbeitstag)
        // ------------------------------
        $date_plus_1_weekday = date( 'Y-m-j' , strtotime ( '+' . $tag . ' weekdays +1 hours +1 minute' ) );
        $date_plus_1_weekday_german = date( 'j.m.Y' , strtotime ( '+' . $tag . ' weekdays +1 hours +1 minute' ) );         
        // Handelt es sich bei dem Tag um einen Freitag?
        if(isFriday( strtotime ( '+' . $tag . ' weekdays +1 hours +1 minute' ) )) {
            $date_plus_1_weekday_start = strtotime($date_plus_1_weekday . ' ' . $rueckrufe_ab_startzeit);
            $date_plus_1_weekday_ende = strtotime($date_plus_1_weekday . ' ' . $rueckrufe_bis_endzeit_freitag);
        }
        else  {        
            $date_plus_1_weekday_start = strtotime($date_plus_1_weekday . ' ' . $rueckrufe_ab_startzeit);
            $date_plus_1_weekday_ende = strtotime($date_plus_1_weekday . ' ' . $rueckrufe_bis_endzeit);
        }
        // ------------------------------
        // Rueckwaertszaehlung verhindern
        if( $date_plus_1_weekday_start <= $date_plus_1_weekday_ende) {
            $time_range_next_array = range($date_plus_1_weekday_start, $date_plus_1_weekday_ende, $minuten_schritte*60);
        } else {
            $time_range_next_array = [];
        }
        
        // Vergebene Termine und Blacklist Termine entfernen
        if( count($time_range_next_array)>0 ) {
            $time_range_next_array_clean = array_diff($time_range_next_array, $vergebene_termine);
            $time_range_next_array_cleaner = array_diff($time_range_next_array_clean, $datum_blacklist);
        }
        
        // Pruefe ob die uebrigen Termine in den Sperr-Zeitraum der Tageweisen Blockierung fallen
        // Jeden Tag auf jeden Blockiereintrag pruefen
        foreach ($time_range_next_array_cleaner as $key => $datum) {
            if(is_date_in_blockrange($datum, $resultsBlacklistDays)) {                    
                unset($time_range_next_array_cleaner[$key]);
            }
        }

        if( !empty($time_range_next_array_cleaner) ) {
            break;
        }
        
    }
    
    // Testausgabe
    // if( current_user_can('editor') || current_user_can('administrator') ) {
    //     var_dump($time_range_next_array_cleaner);
    // }

    // Rueckgabe
    return( array(
        'rueckruf_heute'=>$time_range_today_array_cleaner, 
        'rueckruf_next'=>$time_range_next_array_cleaner, 
        'rueckruf_nextdate'=>$date_plus_1_weekday_german
    ) );
    
}


/* ---------------------------------------------------------- */


// WPML Rueckrufservice AJAX Helfer fuer Zeiten-Rueckgabe
/*
 * Dieser Bereich wird per Ajax aufgerufen und gibt lediglich den Wert der Hauptfunktion als Json String zurueck.
 */
function rueckruf_ajax_request() {
 
    // Das $_REQUEST beinhaltet alle uebergebenen Daten
    if ( isset($_POST) ) {
     
        $triggerelementvalue = filter_input(INPUT_POST, 'triggerelementvalue');        
        echo json_encode( rueckruf_get_arrays($triggerelementvalue) );
    }
     
    // IMMER die bei Ajax-Funktionen in der functions.php
   die();
}
add_action( 'wp_ajax_rueckruf_ajax_request', 'rueckruf_ajax_request' );
add_action( 'wp_ajax_nopriv_rueckruf_ajax_request', 'rueckruf_ajax_request' );


/* ---------------------------------------------------------- */


// Contact Form 7 Shortcode [rueckruf] erstellen
/*
 * Erzeugt einen Shortcode, welcher direkt im Editor des Contact Form 7 Plugins genutzt wird.
 * 
 * Der Shortcode wird durch 3 Radio Button, 3 Select-Felder, 1 Hidden Feld sowie diversem JS- und CSS-Code ersetzt.
 * Die Select-Felder sind bis zur Wahl von Radio "Heute, Morgen, Uebermorgen" ausgeblendet und leer, 
 * bis das Trigger-Element den Ajax-Request ausloest.
 * 
 * Der Ajax-Request gibt ein Json-Element mit 3 Gruppen zurueck (Heute, Morgen, Uebermorgen).
 * Die Json-Werte je Gruppe in die Select-Boxen uebertragen (und Unix-Zeit in normale Zeit umrechnen).
 * 
 * Wenn Gruppe 1 leer ist, dann automatisch den Radio Button von Gruppe 2 selektieren.
 * Wenn Gruppe 1 UND 2 leer ist, dann deren Radio Button ausblenden und Gruppe 3 anzeigen und auswaehlen ansonsten
 * Gruppe 3 ausblenden.
 * 
 * Nach dem Auswaehlen eines Select-Feldes springen die beiden anderen auf Null zurueck. 
 * Gleichzeitig wird der Unix-Zeitstempel (liegt als data-attribut vor) in das hidden UNIX Feld uebertragen.
 */
add_action( 'wpcf7_init', 'custom_add_form_tag_rueckruf' );
function custom_add_form_tag_rueckruf() {
    wpcf7_add_form_tag( 'rueckruf', 'custom_rueckruf_form_tag_handler' );
    
    // Moment_JS einbinden
    wp_enqueue_script('rueckrufliste_calendar_momentlib', plugin_dir_url(__FILE__) . 'js/moment.min.js', array('jquery'), null, true);
}

// Unterfunktion fuer den Shortcode [rueckruf]
function custom_rueckruf_form_tag_handler( $tag ) {

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
    $trigger_more_element = wpmlrueckruf_get_option( $prefix . '_' . 'trigger_more_element' );    
    $trigger_more_type = wpmlrueckruf_get_option( $prefix . '_' . 'trigger_more_type' );
    $themen_auswahlbox_id = wpmlrueckruf_get_option( $prefix . '_' . 'themen_auswahlbox_id' );
    $themen_auswahlbox_class = wpmlrueckruf_get_option( $prefix . '_' . 'themen_auswahlbox_class' );
    
    // Andere Konfigurationen
    $rueckruf_ajax_url = admin_url('admin-ajax.php');
            
    // Return einleiten (normal)
    ?>

        <script type="text/javascript">
            var ajaxurl = "<?php echo $rueckruf_ajax_url; ?>";
            var rueckruf_element_trigger = "<?php echo $trigger_element; ?>";
            var rueckruf_weitere_element_trigger = "<?php echo $trigger_more_element; ?>";
            var rueckruf_weitere_element_triggertyp = "<?php echo $trigger_more_type; ?>";
            
            jQuery(function() {
               
                // Uhrzeiten bei Klick auf Uhrzeit-Dropdown Feld neu einlesen
                jQuery( document ).on( rueckruf_weitere_element_triggertyp, rueckruf_weitere_element_trigger, function() {
                    arp_rueckruf_plugin_get_times(false);
                });
               
                // Bei Aenderung des Trigger-Elements (delegate Methode fuer dynamische Elemente)
                jQuery( document ).on( 'change', rueckruf_element_trigger, function() {
                    arp_rueckruf_plugin_get_times(true);
                });

                // Uhrzeiten (neu) einlesen
                function arp_rueckruf_plugin_get_times(jumper){
                    
                    // Uebergebene Variable
                    var trigger_element_value = $(rueckruf_element_trigger).val();

                    // Ajax-Anfrage absenden
                    $.ajax({
                        url: ajaxurl,
                        dataType: 'json',
                        method: 'post',
                        data: {
                            'action':'rueckruf_ajax_request',
                            'triggerelementvalue' : trigger_element_value
                        },
                        success:function(data) {
                                               
                            // Die Laengen der Ajax-Rueckgabe
                            var uhrzeiten_anzahl_heute = (Object.keys(data['rueckruf_heute']).length);
                            var uhrzeiten_anzahl_next = (Object.keys(data['rueckruf_next']).length);
            
                            // Die Laengen der Ajax-Rueckgabe fuer Heute pruefen. Wenn Null, dann Morgen als selected auswaehlen
                            // (nur umspringen, wenn das Haupt-Trigger-Element die Aktion ausgeloest hat)
                            if( jumper ) {
                                if ( (uhrzeiten_anzahl_heute) < 1 ) {
                                    jQuery('#rueckruf_radio_label_next').find('input').prop("checked", true);

                                    // Select-Feld entsprechend der Radio-Button Auwahl anzeigen
                                    jQuery('#rueckrufhidden_next').show();
                                    jQuery('#rueckrufhidden_heute').hide();
                                } 
                                else {
                                    jQuery('#rueckruf_radio_label_heute').find('input').prop("checked", true);

                                    // Select-Feld entsprechend der Radio-Button Auwahl anzeigen
                                    jQuery('#rueckrufhidden_next').hide();
                                    jQuery('#rueckrufhidden_heute').show();
                                }
                            }
                        
                            /* ---- */
            
                            // Jeden Ajax-Schluessel durchlaufen
                            $.each(data, function (key, jsdata) {

                                // Wenn der Schluessel der Hinweis (bzw. das Datum) auf den naechsten verfuegbaren Tag ist,
                                // dann nicht nach weiteren Werten suchen, ansonsten alle Werte als Schleife durchlaufen
                                if( key === "rueckruf_nextdate" ) {
                                    
                                    // Handelt es sich um einen Tag morgen?
                                    var nowdate = moment();
                                    var nextdate = moment(jsdata, "DD.MM.YYYY");
                                    var diffdate = nextdate.diff(nowdate, 'days')+1;
                                    
                                    // Label mit naechstem freien Datum setzen ... moment.js nutzen um evtl. "morgen" als Text zu setzen
                                    if(diffdate===1) {                                    
                                        $('.rueckruf_next_labeltext').text("morgen");
                                        $('.rueckruf_next_labeltext_large').text("Morgen");
                                    }
                                    else {
                                        $('.rueckruf_next_labeltext').text("am " + jsdata);
                                        $('.rueckruf_next_labeltext_large').text("Am " + jsdata);
                                    }

                                } 
                                else {

                                    // Alle zuvor gesetzte Optionen entfernen
                                    jQuery('#'+key).find('option').remove();

                                    // "Kein Termin" Hinweise entfernen
                                    jQuery('#'+key).parent().find('span').remove();
                                    jQuery('#'+key).removeClass('rueckrufclass_chooseanother');

                                    // Laenge ermitteln und bei Null einen Hinweis einblenden
                                    if(Object.keys(jsdata).length > 0) {
                                        jQuery('#'+key).append('<option value>Bitte auswählen</option>');
                                    } 
                                    else {
                                        jQuery('#'+key).append('<option value>An diesem Tag ist kein Rückruf möglich...</option>');
                                        jQuery('#'+key).parent().append('<span style="color:red;">Ein Rückruf ist an diesem Tag nicht mehr möglich. <br>Bitte wählen Sie den nächsten verfügbaren Werktag aus.</span>');
                                        jQuery('#'+key).addClass('rueckrufclass_chooseanother');
                                    }

                                    // Alle verfuegbaren Zeiten durchlaufen und als Option setzen
                                    $.each(jsdata, function (index, jsdata) {

                                        // Unix zu Normalzeit
                                        var dt=eval(jsdata*1000);
                                        var mydate = new Date(dt);

                                        // Stunden und Minuten mit fuehrenden Nullen
                                        var minutes = ('0'+mydate.getMinutes()).slice(-2);
                                        var hour = ('0'+mydate.getHours()).slice(-2);

                                        // Select-Felder fuellen
                                        jQuery('#'+key).append('<option value="' + hour + ':' + minutes + ' Uhr" data-unixformat="' + jsdata + '">' + hour + ':' + minutes + ' Uhr</option>');

                                    });
                                    
                                }
                            })          
                        },
                        error: function(errorThrown){
                            console.log('Rueckruf Ajax Fehler');
                        }
                    }); 

                }
            
                /* ----------------- */
            
                // Beim Aendern der Radio-Button
                jQuery("input[name=rueckrufzeit_tag]").on('change', function(){
            
                    // Alle Select-Felder ausblenden
                    jQuery('#rueckrufhidden_heute, #rueckrufhidden_next').hide();

                    // Select-Feld entsprechend der Radio-Button Auwahl anzeigen
                    jQuery('#rueckrufhidden_' + jQuery(this).val() ).show();
                    
                    // Label mit Datum / heute / morgen fuer Info-Mail (an Kunden) Formatierung nutzen
                    //var nicedate_str = jQuery(this).parents('label').text()
                    //nicedate_str = nicedate_str.replace(/(\r\n|\n|\r|  )+/g,'');
                    //jQuery('#rueckruf_datum_formatiert').val( nicedate_str );

                });

                /* ----------------- */
            
                // Beim Aendern der Select-Felder            
                jQuery( '#rueckruf_heute, #rueckruf_next' ).on( 'change', function() {
            
                    // Die anderen beiden Auswahlfelder zurueck setzen
                    jQuery( '#rueckruf_heute, #rueckruf_next' ).not(this).val('');
            
                    // Unix Timestamp Feld fuellen
                    jQuery('#rueckrufzeit_unixtimestamp').val( jQuery(this).find(':selected').data('unixformat') );
                    
                    // Unix-Timestamp fuer Info-Mail (an Kunden) nutzen
                    jQuery('#rueckruf_datum_formatiert').val( moment.unix(jQuery('#rueckrufzeit_unixtimestamp').val()).format("DD.MM.YYYY") );
            
                });
                
                /* ----------------- */
            
                // Formular Sendung verhindern wenn keine Uhrzeit (heute oder morgen) ausgewaehlt wurde
                $(".wpcf7-submit").on('click', function(e){
                    
                    // Unix-Timestamp-Uebergabe ist leer... verhindere das Absenden und zeige Hinweis
                    if( jQuery('#rueckrufzeit_unixtimestamp').val() < 1) {
                        e.preventDefault();
                        alert('Bitte wählen Sie eine Uhrzeit für den Rückruf aus.');
                    }
                });
            
            });
        </script>
            
        <style>
            .rueckrufclass_radio {
                padding-right:10px;
                display: inline;
                font-weight: normal;
            }
            .rueckrufclass_hidden {
                display:none;
            }
            .rueckrufclass_chooseanother {
                display:none;
            }
 
            /* Form 7 Styles */
            div.wpcf7-mail-sent-ok {
                background-color: #e2ffe2;
            }
            div.wpcf7-validation-errors {
                background-color: #ffffee;
            }
            div.wpcf7-response-output {
                margin: 0 !important;
                padding: 1em 1em !important;
                border-radius: 5px;
            }
        </style>
        
        
    <?php
    // Return im Heredoc Schema - notwendig fuer Ersetzung des CF7 Shortcodes an Ort und Stelle
    $text = <<<EOT
        <input type="hidden" name="rueckrufzeit_unixtimestamp" id="rueckrufzeit_unixtimestamp" value="">
        <input type="hidden" name="rueckruf_erledigt" value="">
        <input type="hidden" name="rueckruf_erledigt_am" value="">
        <input type="hidden" name="rueckruf_datum_formatiert" id="rueckruf_datum_formatiert" value="">
            
        <label for="rueckrufzeit_1" class="rueckrufclass_radio" id="rueckruf_radio_label_heute">
            <input type="radio" id="rueckrufzeit_1" name="rueckrufzeit_tag" value="heute"> heute </label>
        <label for="rueckrufzeit_2" class="rueckrufclass_radio" id="rueckruf_radio_label_next">
            <input type="radio" id="rueckrufzeit_2" name="rueckrufzeit_tag" value="next"> <span class="rueckruf_next_labeltext">...bitte warten</span> </label>
            
        <br>
                    
        <div class="rueckrufclass_hidden" id="rueckrufhidden_heute">
            <b>Heute um:</b> <br>
            <select id="rueckruf_heute" name="rueckruf_heute" class="form-control" style="width: 70%;"><option value>Bitte auswählen</option></select>
        </div>
        
        <div class="rueckrufclass_hidden" id="rueckrufhidden_next">
            <b><div class="rueckruf_next_labeltext_large" style="float:left;">...bitte warten</div>&nbsp;um:</b> <br>
            <select id="rueckruf_next" name="rueckruf_next" class="form-control" style="width: 70%;"><option value>Bitte auswählen</option></select>
        </div>
            
EOT;

    return $text;
    
}
