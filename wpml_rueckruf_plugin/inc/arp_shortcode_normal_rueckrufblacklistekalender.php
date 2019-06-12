<?php



/*
#####################################################################################################
#####################################################################################################
##################################### Anruf-Blackliste Shortcode ####################################
#####################################################################################################
#####################################################################################################
*/


// Normalen Shortcode [rueckrufblacklistekalender] erstellen

// Example 1 : WP Shortcode to display form on any page or post.
function wpml_rueckruf_shortcode_rueckrufblacklistekalender(){
    
    // Einstellungen aus der WPML Rueckruf Konfigurations-Seite
    $prefix = 'wpmlrueckruf';
    $suffix = 'kalender';
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
    /* ... */
    $trigger_element = wpmlrueckruf_get_option( $prefix . '_' . 'trigger_element' . '_' . $suffix );
    $themen_auswahlbox_id = wpmlrueckruf_get_option( $prefix . '_' . 'themen_auswahlbox_id' . '_' . $suffix );
    $themen_auswahlbox_class = wpmlrueckruf_get_option( $prefix . '_' . 'themen_auswahlbox_class' . '_' . $suffix );
       
    // Andere Konfigurationen
    $rueckruf_ajax_url = admin_url('admin-ajax.php');
    
    // Zeitzone festlegen
    date_default_timezone_set("Europe/Berlin");
    
    // Wenn keine Vergnuepfung zwischen Termin und Thema gesetzt ist, dann direkt die Blockliste laden
    // Ansonsten wird die Blockliste dynamisch per Ajax bei der Themenauswahl nachgeladen
    if($termine_mit_themen_verknuepfen != "yes") {
    
        // Datenbank-Abfrage (Unix-Timestamp in der Zukunft)
        global $wpdb;
        $results = $wpdb->get_results(  
            $wpdb->prepare( "
                SELECT *
                FROM wp_wpml_rueckruf_tage_blocks
                ORDER BY unixtimestampVon DESC
            ", NULL)
        );

        // Helfer Funktion fuer die Formatierung aufrufen
        $blockedDatesString = unix_to_cal_formater($results, 'string');
        
    }
    else {
        $blockedDatesString = "";
    }

    // Zusaetzliche Scripte einbinden ( https://longbill.github.io/jquery-date-range-picker )
    wp_enqueue_script('rueckrufliste_blacklist_calendar_datepickk', plugin_dir_url(__FILE__) . 'js/daterangepicker.min.js', array('jquery'), null, true);
    wp_enqueue_script('rueckrufliste_blacklist_calendar_momentlib', plugin_dir_url(__FILE__) . 'js/moment.min.js', array('jquery'), null, true);
    wp_enqueue_script('rueckrufliste_blacklist_calendar_datepickk_init', plugin_dir_url(__FILE__) . 'js/daterangepickerInit.js', array('jquery'), null, true);
    
    // Zusaetzliche Stylesheet einbinden
    wp_enqueue_style( 'rueckrufliste_blacklist_calendar_datepickk_style', plugin_dir_url(__FILE__) . 'css/daterangepicker.css' );
    wp_enqueue_style( 'rueckrufliste_blacklist_calendar_datepickk_styleCustom', plugin_dir_url(__FILE__) . 'css/daterangepickerCustom.css' );
    
    ?>

    <style>
    </style>
    
    <script type="text/javascript">
            
    // Bereits blockierte Zeiten
    var blockedDates = [<?php echo implode(", ", $blockedDatesString); ?>];
    
    // Weitere Vars
    var ajaxurl = "<?php echo $rueckruf_ajax_url; ?>";
        
    // Zusatz-Funktionen
    jQuery(function () {
    
        //console.log(blockedDates);
        
        // Speichern button
        jQuery(".blocklist_kalender_save_button").on('click', function(){
            
            var unixTimeStringVon = $("#datepicker").val();
            var unixTimeStringBis = $("#datepickerEnd").val();

            // Eingabeformat pruefen (fuer manuelle Eingabe-Option)
            var validVon = moment(unixTimeStringVon, 'YYYY-MM-DD', true).isValid();
            var validBis = moment(unixTimeStringBis, 'YYYY-MM-DD', true).isValid();
            if(!validVon) {
                var error_text = "<br>Das eingegebene Datum (Start der Sperre) entspricht nicht dem Vorgabe-Format: JJJJ-MM-TT";
                jQuery('#arp_errorbox_kalender').slideDown('slow').find('span').html(error_text);
                return false;
            }
            if(!validBis) {
                var error_text = "<br>Das eingegebene Datum (Ende der Sperre) entspricht nicht dem Vorgabe-Format: JJJJ-MM-TT";
                jQuery('#arp_errorbox_kalender').slideDown('slow').find('span').html(error_text);
                return false;
            }

            if(jQuery('.arp_themenselectbox_kalender').val()) {

                // Uebergebene Variable
                var themenauswahl = $(".arp_themenselectbox_kalender").val();

                // Ajax-Anfrage absenden
                $.ajax({
                    url: ajaxurl,
                    method: 'post',
                    data: {
                        'action':'rueckruf_ajax_blacklistkalenderchange',
                        't' : themenauswahl,
                        'uv' : unixTimeStringVon,
                        'ub' : unixTimeStringBis
                    },
                    success:function(data) {
                        jQuery('#arp_successbox_kalender').slideDown('slow').delay(6000).slideUp('slow');
                        setTimeout(function(){ location.reload(true); }, 3000);
                    },
                    error: function(errorThrown){
                        var error_text = "<br>Fehler beim Speichervorgang.";
                        jQuery('#arp_errorbox_kalender').slideDown('slow').find('span').html(error_text).parent().delay(6000).slideUp();
                    }
                }); 
            
            } else {
                var error_text = "<br>Der Themenbereich wurde nicht ausgewählt. Änderungen können nicht gespeichert werden.";
                jQuery('#arp_errorbox_kalender').slideDown('slow').find('span').html(error_text);
            }

        });

        // Loeschen button
        jQuery(".blocklist_kalender_delete_button").on('click', function(){
            
            var calid  = $(this).attr('data-calid');
            var ths = $(this);

            // Ajax-Anfrage absenden
            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: {
                    'action':'rueckruf_ajax_blacklistkalenderremove',
                    'id' : calid
                },
                success:function(data) {                    
                    ths.parents('tr').fadeOut();
                    setTimeout(function(){ location.reload(true); }, 2000);
                },
                error: function(errorThrown){
                    var error_text = "<br>Fehler beim Löschvorgang.";
                    jQuery('#arp_errorbox_kalender').slideDown('slow').find('span').html(error_text).parent().delay(6000).slideUp();
                }
            }); 

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
                <select name="$themen_auswahlbox_id" class="arp_themenselectbox_kalender $themen_auswahlbox_class" id="$themen_auswahlbox_id" aria-required="true" aria-invalid="false">
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
        $block_time_theme_selectbox = "<input type='hidden' class='arp_themenselectbox_kalender' name='$themen_auswahlbox_id' id='$themen_auswahlbox_id' value='Ohne Zuordnung'>";
    }
    
    // Fehler-Platzhalter-Box einfuegen
    $alert_placeholder_box = '<div class="alert alert-danger" id="arp_errorbox_kalender" style="display:none;"><strong>Achtung</strong><span>...</span></div>';

    // Fehler-Platzhalter-Box einfuegen
    $success_placeholder_box = '<div class="alert alert-success" id="arp_successbox_kalender" style="display:none;"><strong>Gespeichert </strong><span> Die Änderungen wurden erfolgreich gespeichert.</span></div>';
 
    // Vorhandene Blockierungen als Tabelle anzeigen und zum Loeschen anbieten
    #date_default_timezone_set('Europe/Berlin');
    $blockierte_tage = "<table class='table table-bordered table-hover'><thead><tr><th>Von</th><th>Bis</th>";
    if($termine_mit_themen_verknuepfen == "yes") { $blockierte_tage .= "<th>Thema</th>"; }
    $blockierte_tage .= "<th>Entfernen</th></tr></thead><tbody style='font-weight: normal;'>";    
    
    foreach ($results as $key => $row) {
        $blockierte_tage .= "<tr><td>" . date("d.m.Y (H:i:s)", $row->unixtimestampVon) . "</td><td>" . date("d.m.Y (H:i:s)", $row->unixtimestampBis);
        if($termine_mit_themen_verknuepfen == "yes") { 
            $blockierte_tage .= "</td><td>" . $row->themengebiet; 
        }
        $blockierte_tage .= "</td><td><div class='btn btn-danger btn-xs blocklist_kalender_delete_button' data-calid='".$row->p_id."'>X</div></td></tr>";
    }
    $blockierte_tage .= "</tbody></table>";

    // Rueckgabe
    return  "$block_time_theme_selectbox $alert_placeholder_box $success_placeholder_box <br>".
            "<div id='blocklist_kalender_container' style='text-align: center; font-weight:normal;'>".

                "<div class='datepickerinputfields row' style='display:none; text-align:left;'>".

                    "<div class='col-md-12'>".
                        "<span style='color:darkred;'><b>Es wurde ein Internet Explorer Browser erkannt. ".
                        "Dieser Browser bietet keine Kalender-Unterstützung zur Auswahl der Sperrtage.</b></span><br>".
                        "Es wird der Umstieg auf einen modernen Browser (Chrome, Firefox o.ä.) empfohlen.<br><br>".
                    "</div>".

                    "<div class='col-md-6'>".
                        "Eine manuelle Eingabe ist dennoch möglich. ".
                        "Bitte sowohl Start- wie auch Enddatum im Format: <b>JJJJ-MM-TT</b> (mit Bindestrichen) eingeben.".
                    "</div>".
                    "<div class='col-md-3'>".
                        "Start der Sperre:<br> <input type='text' id='datepicker' class='form-control'/>".
                    "</div>".
                    "<div class='col-md-3'>".
                        "Ende der Sperre:<br> <input type='text' id='datepickerEnd' class='form-control'/><br>".
                    "</div>".
                "</div>".
            
            "</div>".
            "<div class='blocklist_kalender_save_button btn btn-warning'>Ausgewählte Tage blockieren</div><br><br>$blockierte_tage";
    
}
add_shortcode('rueckrufblacklistekalender', 'wpml_rueckruf_shortcode_rueckrufblacklistekalender');



/* ---------------------------------------------------------- */



// Die DB Rueckgabe durchlaufen und alle Unix-Timestamps in eine Liste der vergebenen Nummern eintragen und zurueck geben
/*
 * Helfer Funktion: Konvertiert die Liste in das richtige Format fuer das Artsy-JS-Script
 */
function unix_to_cal_formater($results, $typ='string') {
    
    // Die DB Rueckgabe durchlaufen und alle Unix-Timestamps in die Liste der vergebenen Nummern eintragen
    $blockedDaysArray = [];

    // Results durchlaufen und String sowie Array erzeugen
    foreach( $results as $key => $row ) {

        date_default_timezone_set('Europe/Berlin');

        // Start- und Endwert aus der Row zuweisen
        $blockUnixVon = $row->unixtimestampVon;
        $blockUnixBis = $row->unixtimestampBis;
        $blockDate = "";
    
        // Alle Daten dazwischen ermitteln
        while( $blockUnixVon <= $blockUnixBis ) {

            $blockDate = "'" . date('d.m.Y', $blockUnixVon) . "'";
            array_push($blockedDaysArray, $blockDate);

            $blockUnixVon = strtotime('+1 day', $blockUnixVon);

        }

    }
    
    // Rueckgabe
    if( $typ === "object" ) {
        return json_encode($blockedDaysArray);
    }
    else {
        return $blockedDaysArray;        
    }
    
}



/* ---------------------------------------------------------- */


// WPML Rueckrufservice AJAX Helfer fuer Status Aenderungen aus der Anruf-Protokoll-Liste
/*
 * Dieser Bereich wird per Ajax aufgerufen und listet die blockierten Termine
 * (Blockliste wird auch bei Aenderung der Themen-Selectbox neu eingelesen)
 * 
 * TODO ... (Bei Bedarf an eine Trennung je nach Thema...)
 */
function rueckruf_ajax_blacklistkalenderget() {
 
    // Das $_REQUEST beinhaltet alle uebergebenen Daten
    if ( isset($_POST) ) {
     
        $themenauswahl = filter_input(INPUT_POST, 't');

        // Datenbank-Abfrage (gruppierte / doppelte Abfrage + nur Eintraege mit einem Unix-Timestamp in der Zukunft)
        global $wpdb;
        
        // Datenbank-Abfrage (Unix-Timestamp in der Zukunft)
        $results = $wpdb->get_results(  
            $wpdb->prepare( "

                SELECT *
                FROM wp_wpml_rueckruf_tage_blocks
                WHERE (
                    unixtimestampVon > (UNIX_TIMESTAMP() - 1 * 24 * 60 * 60 ) OR
                    unixtimestampBis > (UNIX_TIMESTAMP() - 1 * 24 * 60 * 60 )
                )
                AND
                themengebiet = %s
                ORDER BY unixtimestampVon
            ",
            $themenauswahl)
        );
        
        // Helfer Funktion fuer die Formatierung aufrufen + Rueckgabe 
        echo unix_to_cal_formater($results, 'object');
        
    }
     
    // IMMER die bei Ajax-Funktionen in der functions.php
   die();
}
add_action( 'wp_ajax_rueckruf_ajax_blacklistkalenderget', 'rueckruf_ajax_blacklistkalenderget' );
add_action( 'wp_ajax_nopriv_rueckruf_ajax_blacklistkalenderget', 'rueckruf_ajax_blacklistkalenderget' );


/* ---------------------------------------------------------- */


// WPML Rueckrufservice AJAX Helfer fuer Status Aenderungen aus der Anruf-Protokoll-Liste
/*
 * Dieser Bereich wird per Ajax aufgerufen und fuehrt die DB Aenderung durch
 */
function rueckruf_ajax_blacklistkalenderchange() {
 
    // Das $_REQUEST beinhaltet alle uebergebenen Daten
    if ( isset($_POST) ) {
     
        $themenauswahl = filter_input(INPUT_POST, 't');

        date_default_timezone_set('Europe/Berlin');
        $unixzeitVon   = strtotime(filter_input(INPUT_POST, 'uv') . "  0:0:01");
        $unixzeitBis   = strtotime(filter_input(INPUT_POST, 'ub') . "  23:59:59");

        // Datenbank-Abfrage
        global $wpdb;
         
        // Daten speichern
        $query = "INSERT INTO wp_wpml_rueckruf_tage_blocks (unixtimestampVon, unixtimestampBis, themengebiet) VALUES (%s, %s, %s)";
        $wpdb->query( 
            $wpdb->prepare("$query", array($unixzeitVon, $unixzeitBis, $themenauswahl) )
        );
        
    }
     
    // IMMER die bei Ajax-Funktionen in der functions.php
   die();
}
add_action( 'wp_ajax_rueckruf_ajax_blacklistkalenderchange', 'rueckruf_ajax_blacklistkalenderchange' );
add_action( 'wp_ajax_nopriv_rueckruf_ajax_blacklistkalenderchange', 'rueckruf_ajax_blacklistkalenderchange' );


/* ---------------------------------------------------------- */


// WPML Rueckrufservice AJAX Helfer fuer Status Aenderungen aus der Anruf-Protokoll-Liste
/*
 * Dieser Bereich wird per Ajax aufgerufen und loescht einen Eintrag aus der DB
 */
function rueckruf_ajax_blacklistkalenderremove() {
 
    // Das $_REQUEST beinhaltet alle uebergebenen Daten
    if ( isset($_POST) ) {
     
        $calid = filter_input(INPUT_POST, 'id');

        // Datenbank-Abfrage
        global $wpdb;
 
        // Eintrag entsprechend uebergabe loeschen
        $resultsA = $wpdb->get_results(  
            $wpdb->prepare( "
                DELETE FROM wp_wpml_rueckruf_tage_blocks
                WHERE p_id = '%s'
            ", $calid )
        );
        
    }

    // IMMER die bei Ajax-Funktionen in der functions.php
    die();
}
add_action( 'wp_ajax_rueckruf_ajax_blacklistkalenderremove', 'rueckruf_ajax_blacklistkalenderremove' );
add_action( 'wp_ajax_nopriv_rueckruf_ajax_blacklistkalenderremove', 'rueckruf_ajax_blacklistkalenderremove' );
