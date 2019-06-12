<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/* also read https://codex.wordpress.org/Writing_a_Plugin */

/*
Plugin Name: .WPML Html5 Videoplayer
Description: Erzeugt einen Html5 Videoplayer im WPML Stil. Es wird ein Shortcode "wpmlvideo" bereit gestellt. Dieser kann folgende Parameter enthalten: url (= url zum Video), hd (=url zum hochauflösenden Video), typ (=typ des videos z.B. text/mp4), bild (=url zu einem Vorschaubild anstelle des Videoanfangs). Bis auf url sind alle Parameter optional.
Version: 1.0.1
Author: Marco Heizmann
*/

// Videplayer Scripte einbinden
function wpml_html5_videoplayer_anzeigen(){

      // WPML VideoJS (HTML5 Video Player)
      wp_enqueue_script( 'VideoJs', plugin_dir_url(__FILE__) . 'inc/js/video.min.js' , array(), '',  false );
      wp_enqueue_style( 'VideoJsCSS', plugin_dir_url(__FILE__) . 'inc/css/videojs.min.css');
      
      // Custom Style
      wp_enqueue_style( 'VideoJsCSSCustom', plugin_dir_url(__FILE__) . 'inc/css/videojs-custom.css');

      // VideoJS Plugins
      wp_enqueue_script( 'VideoJsHdPlugin', plugin_dir_url(__FILE__) . 'inc/js/videojs-hd-plugin.js' , array(), '',  false );
      wp_enqueue_style( 'VideoJsHdPluginCSS', plugin_dir_url(__FILE__) . 'inc/css/videojs-hd-plugin.css');
      
      // Init
      wp_enqueue_script( 'VideoJsInit', plugin_dir_url(__FILE__) . 'inc/js/videojs-init.js' , array(), '',  false );

}

// Shortcode erzeugen
function wpml_html5_videoplayer_shortcode($atts) {
  
      // Video-Shortcode wird genutzt daher notwendige Scripte laden
      add_action( 'wp_enqueue_scripts', 'wpml_html5_videoplayer_anzeigen' );
  
      // Attribute aus dem Shortcode lesen und Platzhalter bereit stellen wenn nicht gesetzt
      $a = shortcode_atts( 
        array(
          'url' => '#',
          'hd'  => '',
          'typ' => 'video/mp4',
          'bild'=> '',
        ), 
        $atts );
      
      // Variablen vorbereiten
      $returner_text = '';
      $videojs_poster = '';
      
      // Wurde ein Bild (Poster) mitgegeben?
      if( !empty($a['bild']) ) {
          $videojs_poster = ' poster="'.esc_attr($a['bild']).'" ';
      }
      
      // Wurde ein HD Link eingebunden?
      if( !empty($a['hd']) ) {
        
            $returner_text = '
            <video width="640" height="268" '.$videojs_poster.' class="video-js vjs-default-skin" controls data-setup=\'{"fluid": true}\'>
              <source src="'.esc_attr($a['url']).'" type="'.esc_attr($a['typ']).'" label="SD" selected="true">
              <source src="'.esc_attr($a['hd']).'" type="'.esc_attr($a['typ']).'" label="HD">
            </video>
            ';
          
      } else {

            $returner_text = '
            <video width="640" height="268" '.$videojs_poster.' class="video-js vjs-default-skin" controls data-setup=\'{"fluid": true}\'>
              <source src="'.esc_attr($a['url']).'" type="'.esc_attr($a['typ']).'">
            </video>
            ';

      }
      
      return $returner_text;
  
  
}
add_shortcode('wpmlvideo', 'wpml_html5_videoplayer_shortcode');
