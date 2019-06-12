<?php

/*
#####################################################################################################
#####################################################################################################
############################ Datenbank-Tabelle anlegen / entfernen ##################################
#####################################################################################################
#####################################################################################################
*/



// Bei der Aktivierung des Plugins wird die benoetigte Tabelle fuer die Blackliste angelegt
register_activation_hook( __FILE__, 'rueckruf_plugin_create_db' );
function rueckruf_plugin_create_db() {
    
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'wpml_rueckruf_blocks';

	$sql = "CREATE TABLE $table_name (
            `unixtimestamp` int(11) NOT NULL,
            `themengebiet` longtext NOT NULL
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
     dbDelta( $sql );
     

	$table_name = $wpdb->prefix . 'wpml_rueckruf_tage_blocks';

	$sql = "CREATE TABLE $table_name (
            `p_id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `unixtimestampVon` int(11) NOT NULL,
            `unixtimestampBis` int(11) NOT NULL,
            `themengebiet` longtext NOT NULL
	) $charset_collate;";

	dbDelta( $sql );
        
}


// Bei der Deaktivieren des Plugins wird die benoetigte Tabelle fuer die Blackliste entfernt
register_deactivation_hook( __FILE__, 'rueckruf_plugin_remove_db' );
function rueckruf_plugin_remove_db() {
    
     global $wpdb;
     $table_name = $wpdb->prefix . 'wpml_rueckruf_blocks';
     $sql = "DROP TABLE IF EXISTS $table_name";
     $wpdb->query($sql);


     $table_name = $wpdb->prefix . 'wpml_rueckruf_tage_blocks';
     $sql = "DROP TABLE IF EXISTS $table_name";
     $wpdb->query($sql);

     delete_option("my_plugin_db_version");
     
}
