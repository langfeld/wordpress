<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/* also read https://codex.wordpress.org/Writing_a_Plugin */

/*
Plugin Name: .WPML Rückruf Plugin Button
Description: Blendet den WPML-Rückruf Button im Kopf jeder Seite ein.
Version: 1.0.0
Author: Marco Heizmann
*/

// Example 1 : WP Shortcode to display form on any page or post.
function wpml_rueckruf_button_anzeigen(){

    echo '<script>';
    echo 'var rueckruf_button_url="'.plugin_dir_url(__FILE__) . 'inc/img/wpml_rueckruf_logo.png' . '";';
    echo 'var rueckruf_seiten_url="https://www.beispielseite.de/rueckrufservice/";';    
    echo '</script>';
    
    // Zusaetzliche Scripte einbinden
    wp_enqueue_script('rueckrufliste_datatables', plugin_dir_url(__FILE__) . 'inc/js/rueckrufservice_button.min.js', array('jquery'));
    
    // Zusaetzliche Stylesheet einbinden
    //wp_enqueue_style( 'rueckrufliste_datatables_style', plugin_dir_url(__FILE__) . 'inc/css/dataTables.bootstrap.min.css' );

}
add_action( 'wp_enqueue_scripts', 'wpml_rueckruf_button_anzeigen' );