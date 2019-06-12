<?php


/*
#####################################################################################################
#####################################################################################################
######################################## Anruflisten Shortcode ######################################
#####################################################################################################
#####################################################################################################
*/


// Normalen Shortcode [rueckrufliste] erstellen


/*
Verwendete Formular-Daten aus Contact-Form-7 bzw. Flamingo:
----------------------------------------------------------
- themengebiet (jQ Trigger-Element - wird per Shorcode gesetzt)
- kalkulationsnummer
- nachname
- vorname
- anrede
- tel
- company
- companymail
- anliegenvorab
*/


// Example 1 : WP Shortcode to display form on any page or post.
function wpml_rueckruf_shortcode_liste(){

    // Einstellungen aus der WPML Rueckruf Konfigurations-Seite
    $prefix = 'wpmlrueckruf';
    $tage_rueckblick_anrufliste =  wpmlrueckruf_get_option($prefix.'_'.'tage_rueckblick_anrufliste');
    
    // Vergebene Termine und Blacklist Array vormerken
    $vergebene_termine = [];
    $datum_blacklist = [];
    
    // Datenbank-Abfrage (gruppierte / doppelte Abfrage + nur Eintraege mit einem Unix-Timestamp in der Zukunft bei 48 Stunden Puffer)
    global $wpdb;
    $results = $wpdb->get_results(  
        $wpdb->prepare( "
            SELECT 

            pm.meta_value  AS themengebiet, 
            pm2.meta_value AS unixtimestamp,
            pm3.meta_value AS kalkulationsnummer, 
            pm4.meta_value AS nachname,
            pm41.meta_value AS vorname,
            pm42.meta_value AS anrede,
            pm5.meta_value AS tel,
            pm6.meta_value AS company,
            pm7.meta_value AS companymail,
            pm8.meta_value AS anliegenvorab,
            pm9.meta_value AS rueckruf_erledigt,
            pm9.meta_id as meta_id,
            pm10.meta_value AS rueckruf_erledigt_am,
            pm10.meta_id as meta_id_am

            FROM   wp_postmeta pm 

            INNER JOIN wp_postmeta pm2 ON pm2.post_id = pm.post_id AND pm2.meta_key = '_field_rueckrufzeit_unixtimestamp' 
            INNER JOIN wp_postmeta pm3 ON pm3.post_id = pm.post_id AND pm3.meta_key = '_field_kalkulationsnummer' 
            INNER JOIN wp_postmeta pm4 ON pm4.post_id = pm.post_id AND pm4.meta_key = '_field_nachname' 
            INNER JOIN wp_postmeta pm41 ON pm41.post_id = pm.post_id AND pm41.meta_key = '_field_vorname'
            INNER JOIN wp_postmeta pm42 ON pm42.post_id = pm.post_id AND pm42.meta_key = '_field_anrede'
            INNER JOIN wp_postmeta pm5 ON pm5.post_id = pm.post_id AND pm5.meta_key = '_field_tel' 
            INNER JOIN wp_postmeta pm6 ON pm6.post_id = pm.post_id AND pm6.meta_key = '_field_company' 
            INNER JOIN wp_postmeta pm7 ON pm7.post_id = pm.post_id AND pm7.meta_key = '_field_companymail' 
            INNER JOIN wp_postmeta pm8 ON pm8.post_id = pm.post_id AND pm8.meta_key = '_field_anliegenvorab' 
            INNER JOIN wp_postmeta pm9 ON pm9.post_id = pm.post_id AND pm9.meta_key = '_field_rueckruf_erledigt' 
            INNER JOIN wp_postmeta pm10 ON pm10.post_id = pm.post_id AND pm10.meta_key = '_field_rueckruf_erledigt_am' 

            WHERE  pm.meta_key = '_field_themengebiet' 
            AND pm2.meta_value > ( UNIX_TIMESTAMP() - %s * 60 * 60 )

            ORDER BY pm.meta_value
        ", 
            $tage_rueckblick_anrufliste*24
        )
    );

    // Zusaetzliche Scripte einbinden
    wp_enqueue_script('rueckrufliste_datatables', plugin_dir_url(__FILE__) . 'js/jquery.dataTables.min.js', array('jquery'), null, true);
    wp_enqueue_script('rueckrufliste_dt_bootstrap', plugin_dir_url(__FILE__) . 'js/dataTables.bootstrap.min.js', array('jquery'), null, true);
    wp_enqueue_script('rueckrufliste_datatables_init', plugin_dir_url(__FILE__) . 'js/dataTablesInit.min.js', array('rueckrufliste_datatables'), null, true);
    wp_enqueue_script('rueckrufliste_calendar_momentlib', plugin_dir_url(__FILE__) . 'js/moment.min.js', array('jquery'), null, true);
    wp_enqueue_script('rueckrufliste_cookie_lib', plugin_dir_url(__FILE__) . 'js/jscookie.min.js', array('jquery'), null, true);
    
    // Zusaetzliche Stylesheet einbinden
    wp_enqueue_style( 'rueckrufliste_datatables_style', plugin_dir_url(__FILE__) . 'css/dataTables.bootstrap.min.css' );
    
    ?>

    <style>
    table.dataTable thead .sorting:after {
        content: "—" !important;
    }

    table.dataTable thead .sorting_asc:after {
        content: "▲" !important;
    }

    table.dataTable thead .sorting_desc:after {
        content: "▼" !important;
    }
    .content {
        width: 95% !important;
    }
    .details table {
        width: 100%;
    }
    .mehr_button {
        font-size: 23px;
        background-color: #f0ad4e;
        padding: 0 8px;
        color: white;
        border-radius: 50%;
        cursor: pointer;
    }
    .mehr_class {
        padding-right: 20px;
        font-weight: bold;
        width: 180px;
        line-height: 1.8;
    }
    .mehr_separator {
        border-top: 2px solid #dddddd;
        padding-top: 10px;
    }
    /* Farben */
    .unix_color_zukunft { 
        color: #817dff; 
    }
    .unix_color_heute { 
        background-color: rgba(255, 232, 0, 0.41);
    }
    .unix_color_jetzt {
        background-color: rgba(255, 79, 0, 0.41);
    }
    .unix_color_jetzt_blink {
        background-color: rgba(255, 79, 0, 0.41);
        color: red;
    }
    .unix_color_vergangenheit { 
        color: #bdbcbc; 
    }
    .unix_color_erledigt {
        background-color: rgba(123, 255, 120, 0.41) !important;
    }
    </style>

    <div class="pull-right form-inline">
        Bearbeiter-Name
        <input type="text" class="form-control input-sm" id="rueckruf_bearbeiter" value="" style="margin-bottom:10px;">
    </div>
    <div class="clearfix"></div>

    <table id="rueckruf_uebersicht" class="table table-striped table-bordered" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>Rückrufzeit</th>
                <th>Countdown</th>
                <th>Thema</th>
                <th>Name</th>
                <th>Tel.</th>
                <th>Arbeitgeber</th>
                <th>Mail</th>
                <th>Kalk. Nr.</th>
                <th>Anliegen</th>
                <th>Bearbeitet am</th>
                <th>Bearbeitet von</th>
            </tr>
        </thead>
        <tbody>
            
        <?php

        // Zeitzone festlegen
        date_default_timezone_set("Europe/Berlin");
        
        // Die DB Rueckgabe durchlaufen und alle Unix-Timestamps in die Liste der vergebenen Nummern eintragen
        foreach( $results as $key => $row) { 
            
            // Farbklasse durch die Differenz von JETZT und TERMIN ermitteln 
            $unix_now = time();
            $unix_sheduled = $row->unixtimestamp;            
            $unix_diff = $unix_sheduled - $unix_now;
            /* ... */
            $diff_hour = floor($unix_diff / 3600);
            $diff_min  = floor(($unix_diff - $diff_hour * 3600) / 60);
            $diff_sec = $unix_diff - $diff_hour * 3600 - $diff_min * 60;

            
            // Farb-Formatierung nach Stunden
            if( $unix_diff < 0 ) {
                $farbklasse = "unix_color_vergangenheit";
            }
            // Jetzt (weniger als 1 Stunde
            else if( $diff_hour < 1 && $diff_min > 0) {
                
                // Wenn es weniger als 5 Minuten sind
                if( $diff_hour < 1 && $diff_min <= 5 ) {
                    $farbklasse = "unix_color_jetzt_blink";
                } else {
                    $farbklasse = "unix_color_jetzt";
                }
                
            }
            // Heute oder in der Zukunft
            else if( $diff_hour >= 1 && $diff_hour < 10) {
                $farbklasse = "unix_color_heute";
            }
            else {
                $farbklasse = "unix_color_zukunft";
            }
            
            // Farbklasse bei erledigten Anrufen auf Gruen aendern
            $erledigtklasse = "";
            if( $row->rueckruf_erledigt != "") {
                $erledigtklasse = "unix_color_erledigt";
            }
            
            // Zeit-Differenz in Stunden und Minuten anzeigen, wenn in der Zukunft
            $time_diff_text = "-";
            if( $unix_diff > 0 ) {
                $time_diff_text = "$diff_hour Std. - $diff_min Min.";
            }
            
            // Anrede-Filter (Herr / Frau)
            if (strpos($row->anrede, 'Herr') !== false) {
                $row->anrede = "Herr";
            }
            else {
                $row->anrede = "Frau";
            }
            
            // Tabelle-Zeile erzeugen
            echo "<tr>";
            echo "<td class='$farbklasse $erledigtklasse'><span>" . date( 'd.m.Y - H:i', $row->unixtimestamp ) . "</span></td>";
            echo "<td class='$farbklasse $erledigtklasse'><span>$time_diff_text</span></td>";
            echo "<td>" . $row->themengebiet . "</td>";
            echo "<td>" . $row->anrede . ' ' . $row->nachname . ', ' . $row->vorname . "</td>";
            echo "<td><a href='tel:" . $row->tel . "' title='" . $row->anrede . ' ' . $row->nachname . ', ' . $row->vorname . " anrufen'>" . $row->tel . "</a></td>";
            echo "<td>" . $row->company . "</td>";
            echo "<td>" . $row->companymail . "</td>";
            echo "<td>" . $row->kalkulationsnummer . "</td>";
            echo "<td>" . $row->anliegenvorab . "</td>";
            
            // Button fuer Status-Aenderungen
            if( $row->rueckruf_erledigt != "") {
                echo "<td><div class='rueckruf_statusdate' data-metaidam='".$row->meta_id_am."'>" . date("d.m.Y, H:i", $row->rueckruf_erledigt_am) . " Uhr</div></td>";
                echo "<td>".$row->rueckruf_erledigt." <div class='btn btn-warning btn-xs rueckruf_statusbtn' style='margin-left: 50px; color:white;' data-setstatus='' data-metaid='".$row->meta_id."'>Als offen markieren</div></td>";                
            }
            else {
                echo "<td><div class='rueckruf_statusdate' data-metaidam='".$row->meta_id_am."'><i>( noch nicht erledigt )</i> </div></td>";
                echo "<td><div class='btn btn-success rueckruf_statusbtn' style='color:white;' data-setstatus='yes' data-metaid='".$row->meta_id."'>Als erledigt markieren</div></td>";                
            }

            echo "</tr>";
            
        }

        ?>

        </tbody>
    </table>
    
    <?php setlocale(LC_ALL, 'de_DE'); ?>
    Es werden zusätzlich Einträge 
    <b><?php printf( ngettext("von gestern", " der letzten %d Tagen", $tage_rueckblick_anrufliste), $tage_rueckblick_anrufliste); ?></b>
    angezeigt.
            
    <script>
        /* DataTables: Ausgeblendete Spalten ... Layout in dataTablesInit.js nicht vergessen...*/
        var zeilen_verstecken = [6,7,8,9,10,11];
        
        /* Weitere Funktionen */
        jQuery(document).ready(function() {
            
            // Blink-Klasse animieren
            var blinkcount = 0;
            (function blink() { 
                blinkcount+=1;
                if(blinkcount<=5) {
                    jQuery('.unix_color_jetzt_blink span').fadeOut(500).fadeIn(500, blink); 
                } else {
                    jQuery('.unix_color_jetzt_blink span').css('color', 'black');
                }
            })();
            
            // Bearbeiter aus Cookie auslesen und in Formular-Feld setzen
            jQuery("#rueckruf_bearbeiter").val( Cookies.get('arpBearbeiter') );
            
            // Ajax Request fuer Status-Aenderungen
            jQuery( "#rueckruf_uebersicht" ).on( "click", ".rueckruf_statusbtn", function(event) {

                // Variablen setzen
                var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";  
                var newstatus = jQuery(this).data('setstatus');
                var metaid = jQuery(this).data('metaid');
                var metaidam = jQuery(this).parents('tbody').find('.rueckruf_statusdate').data('metaidam');
                var reviser = jQuery("#rueckruf_bearbeiter").val();

                // Wurde schon ein Bearbeiter-Name eingetragen? Wenn ja, diesen als Cookie speichern
                if(!reviser) {
                    alert('Bitte tragen Sie Ihren Namen in das Bearbeiter-Feld ein.');
                    jQuery("#rueckruf_bearbeiter").focus();
                    return;
                } else {
                    Cookies.set('arpBearbeiter', reviser);
                }
                
                // Wenn der Status als erledigt markiert werden soll, dann den Bearbeiter-Namen setzen
                if(newstatus==="yes") {
                    newstatus = reviser;
                }

                // Ausloesenden Button als Variable speichern
                var target = jQuery( event.target );
                var bearbeitet_am_target = jQuery(this).parents('tbody').find('.rueckruf_statusdate');

                // Ajax-Anfrage absenden
                jQuery.ajax({
                    url: ajaxurl,
                    method: 'post',
                    data: {
                        'action':'rueckruf_ajax_statuschange',
                        'id' : metaid,
                        'idam' : metaidam,
                        'v': newstatus
                    },
                    success:function(data) {
                        
                        // Farbe tauschen und Zeitstempel einfügen
                        var parent_element = target.parents('tr.details').prev().find('td[class^="unix_color_"]');
                        if( parent_element.hasClass('unix_color_erledigt') ) {
                            
                            parent_element.removeClass('unix_color_erledigt');
                            bearbeitet_am_target.html( '<i>( noch nicht erledigt )</i>' );
                            
                        } else {
                            
                            parent_element.addClass('unix_color_erledigt');
                            bearbeitet_am_target.html( moment().format('DD.MM.YYYY, HH:mm') + " Uhr" );
                            
                        }
                        
                        // Hinweis anzeigen
                        target.parent().html('<b style="color:green;">Die Änderung wurde gespeichert</b>');
                    },
                    error: function(errorThrown){
                        console.log('Rueckruf Ajax Fehler');
                    }
                });  
            
            });        

        });
    </script>
    
    
    <?php    
}
add_shortcode('rueckrufliste', 'wpml_rueckruf_shortcode_liste');



/* ---------------------------------------------------------- */


// WPML Rueckrufservice AJAX Helfer fuer Status Aenderungen aus der Anruf-Protokoll-Liste
/*
 * Dieser Bereich wird per Ajax aufgerufen und fuehrt die DB Aenderung durch.
 * Dabei wird die eindeutige meta_id als Selector genutzt (jeder Wert hat eine bestimmte ID)
 */
function rueckruf_ajax_statuschange() {
 
    // Das $_POST beinhaltet alle uebergebenen Daten
    if ( isset($_POST) ) {
     
        $meta_id_select = filter_input(INPUT_POST, 'id');
        $meta_id_am_select = filter_input(INPUT_POST, 'idam');
        $meta_new_value = filter_input(INPUT_POST, 'v');
        $meta_new_am_value = time();

        // Datenbank-Abfrage (gruppierte / doppelte Abfrage + nur Eintraege mit einem Unix-Timestamp in der Zukunft)
        global $wpdb;
        
        // Bearbeiter setzen
        $results = $wpdb->get_results(  
            $wpdb->prepare( "
                UPDATE wp_postmeta SET
                meta_value = '%s'
                WHERE meta_id = '%s';
            ", $meta_new_value, $meta_id_select )
        );
        
        // Datum setzen
        $results = $wpdb->get_results(  
            $wpdb->prepare( "
                UPDATE wp_postmeta SET
                meta_value = '%s'
                WHERE meta_id = '%s';
            ", $meta_new_am_value, $meta_id_am_select )
        );
        
    }
     
    // IMMER die bei Ajax-Funktionen in der functions.php
   die();
}
add_action( 'wp_ajax_rueckruf_ajax_statuschange', 'rueckruf_ajax_statuschange' );
add_action( 'wp_ajax_nopriv_rueckruf_ajax_statuschange', 'rueckruf_ajax_statuschange' );
