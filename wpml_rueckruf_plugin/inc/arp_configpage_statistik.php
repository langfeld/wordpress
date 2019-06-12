<?php


// Statistik Plugins nur fuer die ARP-Config-Seite laden
function arp_my_enqueue ( $hook ) {
    if ( $hook === 'toplevel_page_wpml_rueckruf_optionen' ) {
        
        /* Zusaetzliche Ressourcen fuer die Statistik laden */
        wp_enqueue_script('rueckrufliste_configpage_flot_js', plugin_dir_url(__FILE__) . 'js/flot.min.js', array('jquery'), null, false);
        wp_enqueue_script('rueckrufliste_configpage_flotpie_js', plugin_dir_url(__FILE__) . 'js/flot.pie.min.js', array('jquery'), null, false);
//        wp_enqueue_script('rueckrufliste_configpage_flotinit_js', plugin_dir_url(__FILE__) . 'js/flotInit.min.js', array('jquery'), null, false);
        
    }
}
add_action( 'admin_enqueue_scripts', 'arp_my_enqueue' );


// Hauptfunktion fuer die Statistik
function arp_statistics_get($statistic_tab) {
    
    // Daten aus der Datenbank entnehmen
    global $wpdb;

    // Rueckgabe-Var vordefinieren    
    $gesamt_rueckgabe_string = "";
    
    /*
    #####################################################################################################
    #####################################################################################################
    ##################################### Anrufe diesen Monat ###########################################
    #####################################################################################################
    #####################################################################################################
    */
    
    // Ueberschrift und andere Variablen
    $rueckgabe_ueberschrift = "Rückrufe diesen Monat";
    $js_daten_string = "";
    
    // Datenbank-Abfrage (Unix-Timestamp in der Zukunft)
    $results = $wpdb->get_results(  
        $wpdb->prepare( "
        SELECT 
               pm9.meta_value  AS rueckruf_erledigt

        FROM   wp_postmeta pm 
               INNER JOIN wp_postmeta pm2 
                       ON pm2.post_id = pm.post_id 
                          AND pm2.meta_key = '_field_rueckrufzeit_unixtimestamp' 
               INNER JOIN wp_postmeta pm9 
                       ON pm9.post_id = pm.post_id 
                          AND pm9.meta_key = '_field_rueckruf_erledigt' 

        WHERE  pm.meta_key = '_field_themengebiet' 
               AND Month(From_unixtime(pm2.meta_value)) = Month(Curdate()) 

        ORDER  BY pm.meta_value 
        ", NULL)
    , "ARRAY_A");
        
    // Datenbank-Rueckgabe durchlaufen und Array abflachen
    $rueckruf_erledigt_array = array();
    foreach( $results as $key => $row) { 
        if(empty($row['rueckruf_erledigt'])) {
            $row['rueckruf_erledigt'] = "Unbeantwortet";
        }
        array_push($rueckruf_erledigt_array, $row['rueckruf_erledigt']);
    }
    // Vorkommnise zaehlen und JS-Statistik-String erzeugen
    foreach( array_count_values($rueckruf_erledigt_array) as $key => $row) {
        $js_daten_string .= "{label: '$key', data: $row},";
    }
       
    // JS-Rueckgabe-String an Chart-Generierungs-Funktion uebermitteln
    $gesamt_rueckgabe_string .= '<div class="col-md-6"><h1>'.$rueckgabe_ueberschrift.'</h1><br>'.arp_statistics_get_pie($js_daten_string, $statistic_tab).'</div>';
   
    /*
    #####################################################################################################
    #####################################################################################################
    ###################################### Anrufe dieses Jahr ###########################################
    #####################################################################################################
    #####################################################################################################
    */
    
    // Ueberschrift und andere Variablen
    $rueckgabe_ueberschrift = "Rückrufe dieses Jahr";
    $js_daten_string = "";
    
    // Datenbank-Abfrage (Unix-Timestamp in der Zukunft)
    $results = $wpdb->get_results(  
        $wpdb->prepare( "
            SELECT 

            COUNT(pm.meta_value) AS anzahl,
            pm.meta_value  AS themengebiet, 
            pm2.meta_value AS unixtimestamp,
            Month(From_unixtime(pm2.meta_value)) AS month,
            pm9.meta_value AS rueckruf_erledigt,
            pm10.meta_value AS rueckruf_erledigt_am

            FROM   wp_postmeta pm 

            INNER JOIN wp_postmeta pm2 ON pm2.post_id = pm.post_id AND pm2.meta_key = '_field_rueckrufzeit_unixtimestamp' 
            INNER JOIN wp_postmeta pm9 ON pm9.post_id = pm.post_id AND pm9.meta_key = '_field_rueckruf_erledigt' 
            INNER JOIN wp_postmeta pm10 ON pm10.post_id = pm.post_id AND pm10.meta_key = '_field_rueckruf_erledigt_am' 

            WHERE  pm.meta_key = '_field_themengebiet' 
		AND YEAR(FROM_UNIXTIME(pm2.meta_value))= YEAR(CURDATE())

            GROUP BY rueckruf_erledigt
            ORDER BY month
        ", NULL)
    , "ARRAY_A");
        
    // Datenbank-Rueckgabe durchlaufen und Array abflachen
    $rueckruf_erledigt_array = array();
    foreach( $results as $key => $row) { 
        if(empty($row['rueckruf_erledigt'])) {
            $row['rueckruf_erledigt'] = "Unbeantwortet";
        }
        //$rueckruf_erledigt_array[$row['month']][$row['rueckruf_erledigt']] = $row['anzahl'];
        $rueckruf_erledigt_array[$row['rueckruf_erledigt']] .= "[" . $row['month'].", ".$row['anzahl'] . "],";
    }
    // Vorkommnise zaehlen und JS-Statistik-String erzeugen
    foreach( $rueckruf_erledigt_array as $bearbeiter => $row) {
        $js_daten_string .= "'$bearbeiter':{ label:'$bearbeiter', data: [$row] },";
    }
    
    // JS-Rueckgabe-String an Chart-Generierungs-Funktion uebermitteln
    $gesamt_rueckgabe_string .= '<div class="col-md-6"><h1>'.$rueckgabe_ueberschrift.'</h1><br>'.arp_statistics_get_line($js_daten_string, $statistic_tab).'</div>';
    

    
    /*
    #####################################################################################################
    #####################################################################################################
    ###################################### Fertige Rueckgabe ############################################
    #####################################################################################################
    #####################################################################################################
    */
    

    // Rueckgabe   
    return <<<EOT
        <style>
            .flot_chart table td {  padding: 0 !important;  }
            .flot_chart table {  right: -150px !important;  }
            .chart-container .flot-text { font-size: initial; }
            .row .col-md-6 { float:left; width:50%; }
        </style>
        <div class="row">
        $gesamt_rueckgabe_string
        </div>
EOT;
    
}


/*
 #####################################################################################################
 #####################################################################################################
 ########################################### Pie-Chart ###############################################
 #####################################################################################################
 #####################################################################################################
 */


function arp_statistics_get_pie($statdata, $statistic_tab) {

    // Vars vorbereiten
    $unique_id = uniqid();
    
    return <<<EOT
    <script>

        jQuery(function(){

            // Helfer-Funktion fuer das Innen-Label
            function labelFormatterFunction(label, series) {
                var percent = Math.round(series.percent);
                var number = series.data[0][1];
                var label = series.label;
                return "<div style='font-size:10pt; text-align:center; padding:0px; margin: -30px; color:black;'>" + label + "<br>" + number + "</div>";
            }

            // Flot Konfiguration
            var flot_config_pie = {
                series: {
                    pie: {
                        show: true,
                        radius: 1,
                        label: {
                            show: true,
                            radius: 3 / 5,
                            formatter: labelFormatterFunction,
                            background: {
                                opacity: 0.5
                            }
                        }
                    }
                },
                legend: {
                    show: true,
                    position: "ne",
                    sorted: "ascending",
                    labelFormatter: function (label, series) {
                        var percent = Math.round(series.percent);
                        var number = series.data[0][1];
                        return('<div style="font-size:12pt; padding:2px;">&nbsp;<b>' + label + '</b>:&nbsp;&nbsp;' + percent + '%' + '&nbsp;&nbsp;&nbsp;( ' + number + ' )</div>');
                    }
                },
                grid: {
                    hoverable: true
                }
            }

            // Flot-Daten
            var data$unique_id = [
                $statdata
            ];

            
            // Beim Wechsel des Tabs pruefen ob die Statistik nun sichtbar ist
            // da diese erst bei Sichtbarkeit geplottet werden kann.
            jQuery('body').on('click.cmbTabs', '.cmb-tabs .cmb-tab', function (e) {
                
                var tab_clicked = jQuery(this).attr('id');
            
                // Debug
                // console.log( tab_clicked + " (Tab geklickt)");
                // console.log( "$statistic_tab (Statistik-Tab Config)");
                // console.log( "... festgelegt in arp_configpage Zeile \$statistik_tab_id = \"tab-3\";" );
            
                // Wenn der Statistik-Tab angeklickt wurde, dann die Statistiken parsen
                if(tab_clicked == "$statistic_tab") {            
                    setTimeout(function () {
                        
                            // Nach 2 Sekunden plotten
                            $.plot('#$unique_id', data$unique_id, flot_config_pie);
                        
                    }, 500);          
                }
            });
            
        });

    </script>
    <div class="flot_chart" id="$unique_id" style="width:500px;height:300px;"></div>
EOT;
    
}



/*
 #####################################################################################################
 #####################################################################################################
 ########################################### Pie-Chart ###############################################
 #####################################################################################################
 #####################################################################################################
 */


function arp_statistics_get_line($statdata, $statistic_tab) {

    // Vars vorbereiten
    $unique_id = uniqid();
    
    return <<<EOT
    <script>

        jQuery(function() {

            // Deutsches Monats-Array
            var german_monat = ['Jan', 'Feb', 'Mrz', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];

            // Daten
            var datasets = {
                $statdata
            };

            // Farben hinterlegen damit die gleiche bei an bzw. abwahl genutzt werden
            var i = 0;
            jQuery.each(datasets, function(key, val) {
                val.color = i;
                ++i;
            });

            // Checkboxen einfuegen
            var choiceContainer = jQuery("#choices$unique_id");
            jQuery.each(datasets, function(key, val) {
                choiceContainer.append("<br/><input type='checkbox' name='" + key +
                    "' checked='checked' id='id" + key + "'></input>" +
                    "<label for='id" + key + "'>" +
                    val.label + "</label>");
            });
            choiceContainer.find("input").click(plotAccordingToChoices$unique_id);

            // Funktion fuer die An- bzw. Abwahl von Menuepunkten
            function plotAccordingToChoices$unique_id() {

                var data = [];
                choiceContainer.find("input:checked").each(function() {
                    var key = jQuery(this).attr("name");
                    if (key && datasets[key]) {
                        data.push(datasets[key]);
                    }
                });
                
                if (data.length > 0) {            
                    jQuery.plot("#$unique_id", data, {
                        yaxis: {
                            min: 0,
                            tickDecimals: 0,
                        },
                        xaxis: {
                            tickDecimals: 0,
                            tickFormatter: function(x) { return german_monat[x-1] + "&nbsp;&nbsp;&nbsp;&nbsp;"; }
                        },
                        grid: {
                            hoverable: true,
                            clickable: true
                        },
                        points: {
                            show: true
                        },
                        lines: {
                            show: true
                        },
                    });
                }
                
            }
            plotAccordingToChoices$unique_id();

            // Tooltip einbinden		
            jQuery("<div id='tooltip$unique_id'></div>").css({
                position: "absolute",
                display: "none",
                border: "1px solid #f18800",
                padding: "2px",
                "background-color": "#f18800",
                color: "#FFF",
                opacity: 0.80
            }).appendTo("body");

            // Tooltip bei Hover aktivieren
            jQuery("#$unique_id").bind("plothover", function(event, pos, item) {

                if (item) {
                    var x = item.datapoint[0].toFixed(2),
                        y = item.datapoint[1].toFixed(2);

                    jQuery("#tooltip$unique_id").html(item.series.label + "<br>" + Math.round(y) + " (" + german_monat[Math.round(x-1)] + ")")
                        .css({
                            top: item.pageY + 15,
                            left: item.pageX + 5
                        })
                        .fadeIn(200);
                } else {
                    jQuery("#tooltip$unique_id").hide();
                }

            });
                        
            // Beim Wechsel des Tabs pruefen ob die Statistik nun sichtbar ist
            // da diese erst bei Sichtbarkeit geplottet werden kann.
            jQuery('body').on('click.cmbTabs', '.cmb-tabs .cmb-tab', function (e) {
                
                var tab_clicked = jQuery(this).attr('id');
            
                // Debug
                // console.log( tab_clicked + " (Tab geklickt)");
                // console.log( "$statistic_tab (Statistik-Tab Config)");
                // console.log( "... festgelegt in arp_configpage Zeile \$statistik_tab_id = \"tab-3\";" );
            
                // Wenn der Statistik-Tab angeklickt wurde, dann die Statistiken parsen
                if(tab_clicked == "$statistic_tab") {            
                    setTimeout(function () {
                        
                            // Nach 2 Sekunden plotten
                            plotAccordingToChoices$unique_id();
                        
                    }, 500);          
                }
            });

        });

    </script>

    <div class="chart-container">	
        <div class="flot_chart" id="$unique_id" style="width:500px;height:300px;"></div>
        <p id="choices$unique_id" style="float:right; width:135px;"></p>
    </div>
EOT;
    
}