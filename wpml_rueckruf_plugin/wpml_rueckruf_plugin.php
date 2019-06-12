<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/* also read https://codex.wordpress.org/Writing_a_Plugin */

/*
Plugin Name: .WPML Rückruf Plugin
Description: Dieses Plugin stellt einen Rückruf-Shortcode [rueckruf] mit Ajax-Funktionalität für Contact Form 7 sowie die Shortcodes [rueckrufliste] und [rueckrufblackliste] für normale Seiten und Beiträge bereit. Der Shortcode [rueckruf] erzeugt 3 Dropdown-Felder mit verfügbaren Rückrufzeiten. Die Zeiten werden aus der Flamingo Datenbank entnommen. Der Shortcode [rueckrufliste] erzeugt eine Verwaltungs-Oberfläche mit einer Übersicht der gewünschten Rückrufe. Der Shortcode [rueckrufblackliste] erzeugt eine Verwaltungs-Oberfläche für gesperrte Anrufzeiten. Zur Nutzung in Kombination mit ContactForm 7 + Flamingo. Die Konfiguration erfolgt unter Einstellungen - WPML Rückruf Konfiguration.
Version: 1.5.1
Author: Marco Langfeld
*/

include 'inc/arp_install.php';                                      /* Funktionen fuer Installation und Uninstall des Plugins */


include 'inc/arp_shortcode_cf7_rueckruf.php';                       /* Generiert den CF7 Shortcode [rueckruf] mit verfuegbaren Uhrzeiten */
include 'inc/arp_shortcode_cf7_rueckrufthema.php';                  /* Generiert den CF7 Shortcode [rueckrufthema] mit Themen von der Config-Seite */
include 'inc/arp_shortcode_normal_rueckrufliste.php';               /* Generiert den Shortcode [rueckrufliste] mit Rueckruf-Anfragen (DataTables) */
include 'inc/arp_shortcode_normal_rueckrufblackliste.php';          /* Generiert den Shortcode [rueckrufblackliste] mit gesperrten Uhrzeiten */
include 'inc/arp_shortcode_normal_rueckrufblacklistekalender.php';  /* Generiert den Shortcode [rueckrufblacklistekalender] mit gesperrten Uhrzeiten */

include 'inc/arp_hook_cf7.php';                                     /* Schaltet sich in den Sende-Prozess des CF7 Formulars, ergaenzt CC Empfaenger und Betreff */

include 'inc/arp_configpage_statistik.php';                         /* Funktion zur Generierung der Statistik in der Config-Seite */
include 'inc/arp_configpage.php';                                   /* Generiert die Plugin Config-Seite */
