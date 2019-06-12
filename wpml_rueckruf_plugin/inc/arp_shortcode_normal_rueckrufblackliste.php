<?php



/*
#####################################################################################################
#####################################################################################################
##################################### Anruf-Blackliste Shortcode ####################################
#####################################################################################################
#####################################################################################################
*/


// Normalen Shortcode [rueckrufblackliste] erstellen


// Example 1 : WP Shortcode to display form on any page or post.
function wpml_rueckruf_shortcode_rueckrufblackliste(){
    
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
       
    // Andere Konfigurationen
    $rueckruf_ajax_url = admin_url('admin-ajax.php');
    
    // Zeitzone festlegen
    date_default_timezone_set("Europe/Berlin");
    
    // Wenn keine Vergnuepfung zwischen Termin und Thema gesetzt ist, dann direkt die Blockliste laden
    if($termine_mit_themen_verknuepfen != "yes") {
    
        // Datenbank-Abfrage (Unix-Timestamp in der Zukunft)
        global $wpdb;
        $results = $wpdb->get_results(  
            $wpdb->prepare( "
                SELECT *
                FROM wp_wpml_rueckruf_blocks
                WHERE unixtimestamp > (UNIX_TIMESTAMP() - 1 * 24 * 60 * 60 )
                ORDER BY unixtimestamp
            ", NULL)
        );

        // Helfer Funktion fuer die Formatierung aufrufen
        $blockedTimesString = unix_to_artsy_formater($results, 'string');
        
    }
    else {
        $blockedTimesString = "";
    }
    
    // Zusaetzliche Scripte einbinden
    wp_enqueue_script('rueckrufliste_blacklist_calendar', plugin_dir_url(__FILE__) . 'js/artsyDayScheduleSelector.min.js', array('jquery'), null, true);
    wp_enqueue_script('rueckrufliste_blacklist_calendar_momentlib', plugin_dir_url(__FILE__) . 'js/moment.min.js', array('jquery'), null, true);
    wp_enqueue_script('rueckrufliste_blacklist_calendar_init', plugin_dir_url(__FILE__) . 'js/artsyDayScheduleSelectorInit.min.js', array('jquery'), null, true);
    
    // Zusaetzliche Stylesheet einbinden
    wp_enqueue_style( 'rueckrufliste_blacklist_calendar_style', plugin_dir_url(__FILE__) . 'css/artsyDayScheduleSelector.min.css' );
    
    ?>

    <style>
    </style>
    
    <script type="text/javascript">
        
    // Anzahl der zu zeigenden Tage, Start und Endtime sowie Interval
    var dayShowCount = <?php echo $tage_vorlauf_sperrliste; ?>;
    var blockStartTime = "<?php echo $rueckrufe_ab_startzeit; ?>";
    var blockEndTime = "<?php echo date('H:i',  strtotime($rueckrufe_bis_endzeit) + 30*60 ); ?>";
    var blockEndTimeFriday = "<?php echo $rueckrufe_bis_endzeit_freitag; ?>";
    var blockInterval = <?php echo $minuten_schritte; ?>;
    
    // Bereits blockierte Zeiten
    var blockedTimes = { <?php echo $blockedTimesString; ?> };
    
    // Weitere Vars
    var ajaxurl = "<?php echo $rueckruf_ajax_url; ?>";
        
    // Zusatz-Funktionen
    jQuery(function () {

        // Speichern button
        jQuery(".blocklist_save_button").on('click', function(){
            
            var unixTimeString = "";
            jQuery("#day-schedule").find('.time-slot[data-selected="selected"]').each(function(){

                // Erzeuge mittels MomentJS ein Unix-Timestamp
                moment.locale("de");
                var momentYear = moment().format('YYYY');
                var momentTime = jQuery(this).data('time');
                var momentDayMonth = jQuery(this).data('day');

                var momentString = momentDayMonth + '.' + momentYear + ' ' + momentTime;
                var unixTimestampString = moment(momentString, 'DD.MM.YYYY HH:mm').unix();

                unixTimeString += unixTimestampString + ",";
            });
            
            // Debug
            //window.console.log(unixTimeString);

            if(jQuery('.arp_themenselectbox').val()) {

            // Uebergebene Variable
            var themenauswahl = $(".arp_themenselectbox").val();

            // Ajax-Anfrage absenden
            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: {
                    'action':'rueckruf_ajax_blacklistchange',
                    't' : themenauswahl,
                    'u' : unixTimeString
                },
                success:function(data) {
                    jQuery('#arp_successbox').slideDown('slow').delay(6000).slideUp('slow');
                },
                error: function(errorThrown){
                    var error_text = "<br>Fehler beim Speichervorgang.";
                    jQuery('#arp_errorbox').slideDown('slow').find('span').html(error_text).parent().delay(6000).slideUp();
                }
            }); 
            
            } else {
                var error_text = "<br>Der Themenbereich wurde nicht ausgewählt. Änderungen können nicht gespeichert werden.";
                jQuery('#arp_errorbox').slideDown('slow').find('span').html(error_text); /*.parent().delay(6000).slideUp();*/
            }

        });
        

    });
    </script>
    
    <?php 
    
    // Themen-Auswahl-Box (bei Aenderung die Blockzeiten neu einlesen ... wenn Termine mit Themen verknuepft sind
    if($termine_mit_themen_verknuepfen == "yes") {
        
        $select_optionen = "";
        foreach ($themen_zustaendigkeiten as $key => $val) {
           $select_optionen .= "<option value='". $val['thema'] ."'>". $val['thema'] ."</option>";
        }
        $block_time_theme_selectbox = <<<EOT
        <label>Themenbereich für Sperrzeiten:<br>
            <span class="wpcf7-form-control-wrap $themen_auswahlbox_id">
                <select name="$themen_auswahlbox_id" class="arp_themenselectbox $themen_auswahlbox_class" id="$themen_auswahlbox_id" aria-required="true" aria-invalid="false">
                    <option value="">Bitte auswählen</option>
                    $select_optionen
                </select>
            </span>
        </label><br><br>
        <script>
            jQuery(function(){
                // Read-Only bis zur Auswahl eines Themenbereichs
                jQuery(".time-slot").addClass('readonly');
            });
        </script>
EOT;
        
    }
    else {
        // Es erfolgt keine Verknuepfung... binde daher fuer den Ajax-Request ein hidden-Feld ein.
        $block_time_theme_selectbox = "<input type='hidden' class='arp_themenselectbox' name='$themen_auswahlbox_id' id='$themen_auswahlbox_id' value='Ohne Zuordnung'>";
    }
    
    // Fehler-Platzhalter-Box einfuegen
    $alert_placeholder_box = '<div class="alert alert-danger" id="arp_errorbox" style="display:none;"><strong>Achtung</strong><span>...</span></div>';

    // Fehler-Platzhalter-Box einfuegen
    $success_placeholder_box = '<div class="alert alert-success" id="arp_successbox" style="display:none;"><strong>Gespeichert </strong><span> Die Änderungen wurden erfolgreich gespeichert.</span></div>';
    
    // Hinweis auf Vorlauf Tag(e) in String speichern
    setlocale(LC_ALL, 'de_DE');
    $block_time_text = "Es können Anruf-Zeiten / -Tage bis zu <b>" . 
            sprintf( ngettext("einem Tag", "%d Tage", $tage_vorlauf_sperrliste), $tage_vorlauf_sperrliste) .
            "</b> im Voraus blockiert werden.";
    
    // Hinweis auf Freitag-Endzeit in String speichern
    $friday_block_time_text = "Am Freitag erfolgt der letzte Anruf laut Einstellung um <b>" . $rueckrufe_bis_endzeit_freitag . " Uhr.";
 
    return "$block_time_theme_selectbox $alert_placeholder_box $success_placeholder_box $block_time_text<br>$friday_block_time_text<br><br><div id='day-schedule'></div><br><div class='blocklist_save_button btn btn-warning'>Gesperrte Anrufzeiten speichern</div>";
    
}
add_shortcode('rueckrufblackliste', 'wpml_rueckruf_shortcode_rueckrufblackliste');



/* ---------------------------------------------------------- */



// Die DB Rueckgabe durchlaufen und alle Unix-Timestamps in eine Liste der vergebenen Nummern eintragen und zurueck geben
/*
 * Helfer Funktion: Konvertiert die Liste in das richtige Format fuer das Artsy-JS-Script
 */
function unix_to_artsy_formater($results, $typ='string') {
    
    // Die DB Rueckgabe durchlaufen und alle Unix-Timestamps in die Liste der vergebenen Nummern eintragen
    $blockedTimesString = "";
    $blockedTimesArray = [];
    $blockDatePrev = "";

    // Results durchlaufen und String sowie Array erzeugen
    foreach( $results as $key => $row) { 

        //array_push($datum_blacklist, $row->unixtimestamp);
        $blockUnix = $row->unixtimestamp;
        date_default_timezone_set('Europe/Berlin');
        $blockDate = date("d.m", $blockUnix);
        $blockTimeA = date("H:i", $blockUnix);
        $blockTimeB = date("H:i", $blockUnix + 30*60);

        // String fuer das Javascript formatieren
        if( $blockDatePrev === $blockDate ) {
            
            // Weitere Uhrzeiten fuer diesen Tag... letzte Klammer entfernen und Uhrzeit anhaengen
            $blockedTimesString = substr($blockedTimesString, 0, -2);
            $blockedTimesString .= ",['$blockTimeA', '$blockTimeB']]";
            
            array_push($blockedTimesArray[$blockDate], array($blockTimeA, $blockTimeB));
        }
        else {
            
            // Tag schliessen ... (bei weiteren Uhrzeiten an diesem Tag wird er nochmal oben geoeffnet)
            $blockedTimesString .= "'$blockDate': [['$blockTimeA', '$blockTimeB']]";
            
            $blockedTimesArray[$blockDate] = array(array($blockTimeA, $blockTimeB));
        }
        $blockedTimesString .= ",";

        // Zuletzt verwendetes Datum merken
        $blockDatePrev = $blockDate;

    }
    // Letztes Komma entfernen
    $blockedTimesString = rtrim($blockedTimesString, ",");
    
    // Rueckgabe
    if( $typ === "object" ) {
        return json_encode($blockedTimesArray);
    }
    else {
        return $blockedTimesString;        
    }
    
}



/* ---------------------------------------------------------- */


// WPML Rueckrufservice AJAX Helfer
/*
 * Dieser Bereich wird per Ajax aufgerufen und listet die blockierten Termine
 * (Blockliste wird auch bei Aenderung der Themen-Selectbox neu eingelesen)
 */
function rueckruf_ajax_blacklistget() {
 
    // Das $_REQUEST beinhaltet alle uebergebenen Daten
    if ( isset($_POST) ) {
     
        $themenauswahl = filter_input(INPUT_POST, 't');

        // Datenbank-Abfrage (gruppierte / doppelte Abfrage + nur Eintraege mit einem Unix-Timestamp in der Zukunft)
        global $wpdb;
        
        // Datenbank-Abfrage (Unix-Timestamp in der Zukunft)
        $results = $wpdb->get_results(  
            $wpdb->prepare( "
                SELECT *
                FROM wp_wpml_rueckruf_blocks
                WHERE unixtimestamp > (UNIX_TIMESTAMP() - 1 * 24 * 60 * 60 )
                AND
                themengebiet = %s
                ORDER BY unixtimestamp
            ",
            $themenauswahl)
        );
        
        // Helfer Funktion fuer die Formatierung aufrufen + Rueckgabe 
        echo unix_to_artsy_formater($results, 'object');
        
    }
     
    // IMMER die bei Ajax-Funktionen in der functions.php
   die();
}
add_action( 'wp_ajax_rueckruf_ajax_blacklistget', 'rueckruf_ajax_blacklistget' );
add_action( 'wp_ajax_nopriv_rueckruf_ajax_blacklistget', 'rueckruf_ajax_blacklistget' );


/* ---------------------------------------------------------- */


// WPML Rueckrufservice AJAX Helfer fuer Status Aenderungen aus der Anruf-Protokoll-Liste
/*
 * Dieser Bereich wird per Ajax aufgerufen und listet durch Kunden reservierte Termine
 */
function rueckruf_ajax_blacklistget_vergeben() {
 
    // Das $_REQUEST beinhaltet alle uebergebenen Daten
    if ( isset($_POST) ) {
     
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
        
        $themenauswahl = filter_input(INPUT_POST, 't');

        // Datenbank-Abfrage
        global $wpdb;
        
        // Wenn Termine nicht Themen verknüpft sind, dann Themengebiet ignorieren
        if($termine_mit_themen_verknuepfen != "yes") {
            
            // Datenbank-Abfrage (Unix-Timestamp in der Zukunft)
            $results = $wpdb->get_results(  
                $wpdb->prepare( "
                    SELECT 

                    pm.meta_value  AS themengebiet, 
                    pm2.meta_value AS unixtimestamp

                    FROM   wp_postmeta pm 

                    INNER JOIN wp_postmeta pm2 ON pm2.post_id = pm.post_id AND pm2.meta_key = '_field_rueckrufzeit_unixtimestamp'

                    WHERE  pm.meta_key = '_field_themengebiet'
                    AND pm2.meta_value != ''
                    AND pm2.meta_value > UNIX_TIMESTAMP()

                    ORDER BY pm.meta_value
                ", NULL )
            );
            
        }
        else {
        
            // Datenbank-Abfrage (Unix-Timestamp in der Zukunft)
            $results = $wpdb->get_results(  
                $wpdb->prepare( "
                    SELECT 

                    pm.meta_value  AS themengebiet, 
                    pm2.meta_value AS unixtimestamp

                    FROM   wp_postmeta pm 

                    INNER JOIN wp_postmeta pm2 ON pm2.post_id = pm.post_id AND pm2.meta_key = '_field_rueckrufzeit_unixtimestamp'

                    WHERE  pm.meta_key = '_field_themengebiet' 
                    AND pm.meta_value = '%s'
                    AND pm2.meta_value != ''
                    AND pm2.meta_value > UNIX_TIMESTAMP()

                    ORDER BY pm.meta_value
                ",
                $themenauswahl)
            );
        
        }
        
        // Helfer Funktion fuer die Formatierung aufrufen + Rueckgabe 
        echo unix_to_artsy_formater($results, 'object');
        
    }
     
    // IMMER die bei Ajax-Funktionen in der functions.php
   die();
}
add_action( 'wp_ajax_rueckruf_ajax_blacklistget_vergeben', 'rueckruf_ajax_blacklistget_vergeben' );
add_action( 'wp_ajax_nopriv_rueckruf_ajax_blacklistget_vergeben', 'rueckruf_ajax_blacklistget_vergeben' );


/* ---------------------------------------------------------- */


// WPML Rueckrufservice AJAX Helfer fuer Status Aenderungen aus der Anruf-Protokoll-Liste
/*
 * Dieser Bereich wird per Ajax aufgerufen und fuehrt die DB Aenderung durch
 */
function rueckruf_ajax_blacklistchange() {
 
    // Das $_REQUEST beinhaltet alle uebergebenen Daten
    if ( isset($_POST) ) {
     
        $themenauswahl = filter_input(INPUT_POST, 't');
        $unixzeiten = explode( ",", rtrim( filter_input(INPUT_POST, 'u'), ",") );
        $insertArray = [];
        $insertArrayPlaceholder = [];

        // Datenbank-Abfrage
        global $wpdb;
 
        // Vorherige Eintraege mit dem gewaehlten / Standard Themengebiet entfernen
        $resultsA = $wpdb->get_results(  
            $wpdb->prepare( "
                DELETE FROM wp_wpml_rueckruf_blocks
                WHERE themengebiet = '%s'
            ", $themenauswahl )
        );
        
        // Multiple Values mit Prepare
        foreach ($unixzeiten AS $val) {
            array_push($insertArray, $val, $themenauswahl);
            $insertArrayPlaceholder[] = "('%s', '%s')";
        }
        $query = "INSERT INTO wp_wpml_rueckruf_blocks (unixtimestamp, themengebiet) VALUES ";
        $query .= implode(', ', $insertArrayPlaceholder);
        $wpdb->query( 
            $wpdb->prepare("$query ", $insertArray)
        );
        
    }
     
    // IMMER die bei Ajax-Funktionen in der functions.php
   die();
}
add_action( 'wp_ajax_rueckruf_ajax_blacklistchange', 'rueckruf_ajax_blacklistchange' );
add_action( 'wp_ajax_nopriv_rueckruf_ajax_blacklistchange', 'rueckruf_ajax_blacklistchange' );



