<?php

/* Load CMB2 */
if (file_exists(__DIR__ . '/cmb2/init.php')) {
    require_once __DIR__ . '/cmb2/init.php';
} elseif (file_exists(__DIR__ . '/CMB2/init.php')) {
    require_once __DIR__ . '/CMB2/init.php';
}


/* Load CMB2 Addon: Tabs */
if (file_exists(__DIR__ . '/cmb2-tabs/cmb2-tabs.php')) {
    require_once __DIR__ . '/cmb2-tabs/cmb2-tabs.php';
}



/*
#####################################################################################################
#####################################################################################################
######################################## Konfigurations-Seite #######################################
#####################################################################################################
#####################################################################################################
*/


/**
 * CMB2 Theme Options
 * @version 0.1.0
 */

class Wpmlrueckruf_Admin {

	/* Option key, and option page slug */
	protected $key = 'wpml_rueckruf_optionen';

	/* Options page metabox id */
	protected $metabox_id = 'wpml_rueckruf_option_metabox';

	/* Options Page title */
	protected $title = '';

	/* Options Page hook */
	protected $options_page = '';

        /* ... */
        
	/* Holds an instance of the object */
	protected static $instance = null;

	/* Returns the running object */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	/* Constructor */
	protected function __construct() {
		// Set our title
		$this->title = __( 'Rückruf-System', 'wpmlrueckruf' );
	}

	/* Initiate our hooks */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );
	}


	/* Register our setting to WP */
	public function init() {
		register_setting( $this->key, $this->key );
	}

	/* Add menu options page */
	public function add_options_page() {
		$this->options_page = add_menu_page( 
                    $this->title,  // page_title
                    $this->title,  // menu_title
                    'manage_options',  // capability
                    $this->key,  // menu_slug
                    array( $this, 'admin_page_display' ), // function
                    'dashicons-phone' // icon_url
                );
                
		// Include CMB CSS in the head to avoid FOUC
		add_action( "admin_print_styles-{$this->options_page}", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );
	}

	/* Admin page markup. Mostly handled by CMB2 */
	public function admin_page_display() {
		?>
		<div class="wrap cmb2-options-page <?php echo $this->key; ?>">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php cmb2_metabox_form( $this->metabox_id, $this->key ); ?>
		</div>
		<?php
	}

/*
#####################################################################################################
#####################################################################################################
*/
        
	/**
	 * Add the options metabox to the array of metaboxes
	 * @since  0.1.0
	 */
	function add_options_page_metabox() {

            // hook in our save notices
            add_action( "cmb2_save_options-page_fields_{$this->metabox_id}", array( $this, 'settings_notices' ), 10, 2 );
            
            // Variablen definieren
            $prefix = 'wpmlrueckruf';            
            $statistik_tab_nr = "3";
            
            $cmb = new_cmb2_box( array(
                    'id'         => $this->metabox_id,
                    'hookup'     => false,
                    'cmb_styles' => false,
                    'show_on'    => array(
                        // These are important, don't remove
                        'key'   => 'options-page',
                        'value' => array( $this->key, )
                    ),
                    /* Tabs Mod */
                    'vertical_tabs' => false, // Set vertical tabs, default false
                    'tabs' => array(
                        array(
                            'id' => 'tab-1',
                            'icon' => 'dashicons-admin-users',
                            'title' => 'Themen & Zuständigkeiten',
                            'fields' => array(
                                $prefix . '_' . 'themen_zustaendigkeiten',
                            ),
                        ),
                        array(
                            'id' => 'tab-2',
                            'icon' => 'dashicons-clock',
                            'title' => 'Zeiten & Intervalle',
                            'fields' => array(
                                $prefix . '_' . 'rueckrufe_ab_startzeit',
                                $prefix . '_' . 'rueckrufe_bis_endzeit',
                                $prefix . '_' . 'rueckrufe_bis_endzeit_freitag',
                                $prefix . '_' . 'minuten_schritte',
                                $prefix . '_' . 'minuten_vorlauf_puffer',
                                $prefix . '_' . 'tage_vorlauf_sperrliste',
                                $prefix . '_' . 'tage_rueckblick_anrufliste',
                            ),
                        ),
                        array(
                            'id' => 'tab-3',
                            'icon' => 'dashicons-chart-line',
                            'title' => 'Statistiken',
                            'fields' => array(
                                $prefix . '_' . 'statistik',
                            ),
                        ),
                        array(
                            'id' => 'tab-4',
                            'icon' => 'dashicons-info',
                            'title' => 'Informationen',
                            'fields' => array(
                                $prefix . '_' . 'infotext',
                                $prefix . '_' . 'shortcodesinfo',
                                $prefix . '_' . 'cf7info',
                                $prefix . '_' . 'cf7_beispielcode',
                            ),
                        ),
                        array(
                            'id' => 'tab-5',
                            'icon' => 'dashicons-admin-generic',
                            'title' => 'System-Konfiguration',
                            'fields' => array(
                                $prefix . '_' . 'cf7_formular_nummer',
                                $prefix . '_' . 'termine_mit_themen_verknuepfen',
                                $prefix . '_' . 'trigger_element',
                                $prefix . '_' . 'trigger_more_element',
                                $prefix . '_' . 'trigger_more_type',
                                $prefix . '_' . 'themen_auswahlbox_id',
                                $prefix . '_' . 'themen_auswahlbox_class',
                            ),
                        ),
                    )
            ) );

            // Set our CMB2 fields
            
            $group_field_id = $cmb->add_field( array(
                    'name' => __('Themen und Zuständigkeiten', 'cmb2'),
                    'id'          => $prefix . '_' . 'themen_zustaendigkeiten',
                    'type'        => 'group',
                    'description' => '<span style="font-style:normal;">'
                    . 'Hier können die Themengebiete für die Auswahl durch den Besucher definiert werden.<br><br>'
                    . '<span style="color:darkred">Achtung:</span> Termine werden mit dem Themen-Titel verknüpft, '
                    . 'd.h. bei einer Änderung am Themen-Titel werden dessen gebuchte Termine entfernt.<br>'
                    . 'Änderungen an den zuständigen Sachbearbeitern haben auf gebuchte Termine keinen Einfluss und sind jederzeit möglich.'
                    . '</span>',
                    // 'repeatable'  => false, // use false if you want non-repeatable group
                    'options'     => array(
                            'group_title'   => 'Thema {#}',
                            'add_button'    => 'Ein weiteres Thema hinzufügen',
                            'remove_button' => 'Thema entfernen',
                            'sortable'      => true, // beta
                            'closed'     => true, // true to have the groups closed by default
                    ),
            ));

            // Id's for group's fields only need to be unique for the group. Prefix is not needed.
            $cmb->add_group_field( $group_field_id, array(
                    'name' => 'Thema',
                    'id'   => 'thema',
                    'type' => 'text',
                    'default' => 'Firmenwagenlösungen bzw. Fuhrparkverwaltung',
                    // 'repeatable' => true, // Repeatable fields are supported w/in repeatable groups (for most types)
            ));

            $cmb->add_group_field( $group_field_id, array(
                    'name' => 'Zuständige Sachbearbeiter',
                    'description' => 'Hier die E-Mail Adressen der zuständigen Sachbearbeiter eintragen.<br>'
                                    . 'Bitte nur eine Adresse je Zeile.',
                    'id'   => 'sachbearbeiter',
                    'type' => 'textarea_small',
                    'default' => 'info@beispielseite.de',
            ));
            
            // Id's for group's fields only need to be unique for the group. Prefix is not needed.
            $cmb->add_group_field( $group_field_id, array(
                    'name' => 'Sortierung',
                    'description' => 'Bitte eine Zahl eingeben. Es wird von hoch nach niedrig sortiert.',
                    'id'   => 'sortierung',
                    'type' => 'text',
                    'default' => '1',
                    // 'repeatable' => true, // Repeatable fields are supported w/in repeatable groups (for most types)
            ));
            
            
            /* ------- */
            
            $cmb->add_field(array(
                'name' => __('Rückrufe ab / Startzeit', 'cmb2'),
                'id' => $prefix . '_' . 'rueckrufe_ab_startzeit',
                'type' => 'text_time',
                'desc'    => 'Wann erfolgt der frühste Rückruf?',
                'default' => '9:00',
                'time_format' => 'H:i',
                'attributes' => array(
                        'data-timepicker' => json_encode( array(
                                'stepMinute' => 30,
                        )),
                ),
            ));
            
            $cmb->add_field(array(
                'name' => __('Rückrufe bis / Endzeit', 'cmb2'),
                'id' => $prefix . '_' . 'rueckrufe_bis_endzeit',
                'type' => 'text_time',
                'desc'    => 'Wann erfolgt der letzte Rückruf?',
                'default' => '16:00',
                'time_format' => 'H:i',
                'attributes' => array(
                        'data-timepicker' => json_encode( array(
                                'stepMinute' => 30,
                        )),
                ),
            ));
            
            $cmb->add_field(array(
                'name' => __('Rückrufe bis / Endzeit (Freitag)', 'cmb2'),
                'id' => $prefix . '_' . 'rueckrufe_bis_endzeit_freitag',
                'type' => 'text_time',
                'desc'    => 'Wann erfolgt der letzte Rückruf am Freitag?',
                'default' => '14:00',
                'time_format' => 'H:i',
                'attributes' => array(
                        'data-timepicker' => json_encode( array(
                                'stepMinute' => 30,
                        )),
                ),
            ));
            
            $cmb->add_field(array(
                'name' => __('Minuten Intervall', 'cmb2'),
                'id' => $prefix . '_' . 'minuten_schritte',
                'type' => 'text_small',
                'desc'    => 'Zeitlicher Intervall (in Minuten)',
                'default' => '30',
            ));
            
            $cmb->add_field(array(
                'name' => __('Vorlauf / Puffer', 'cmb2'),
                'id' => $prefix . '_' . 'minuten_vorlauf_puffer',
                'type' => 'text_small',
                'desc'    => 'Ab der aktuellen Uhrzeit (in Minuten)',
                'default' => '30',
            ));
            
            $cmb->add_field(array(
                'name' => __('Tag(e) Vorlauf (Sperrliste)', 'cmb2'),
                'id' => $prefix . '_' . 'tage_vorlauf_sperrliste',
                'type' => 'text_small',
                'desc'    => 'Anzahl der Tage für die Vorausplanung in der Sperrliste [intern]',
                'default' => '31',
            ));
            
            $cmb->add_field(array(
                'name' => __('Tag(e) Rückblick (Anrufliste)', 'cmb2'),
                'id' => $prefix . '_' . 'tage_rueckblick_anrufliste',
                'type' => 'text_small',
                'desc'    => 'Anzahl der anzuzeigenden Tage vergangener Anrufe [intern]',
                'default' => '7',
            ));
            
            /* ------- */
            
            /* Alle verfuegbaren CF7 Formulare fuer Select-Box auslesen */
            $args = array('post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1);
            $rs = array();
            if( $data = get_posts($args)){
                foreach($data as $key){
                    $rs[$key->ID] = $key->post_title;
                }
            }else{
                $rs['0'] = esc_html__('Kein Formular gefunden', 'text-domanin');
            }
            
            $cmb->add_field(array(
                'name' => __('CF7 Rückruf-Formular', 'cmb2'),
                'id' => $prefix . '_' . 'cf7_formular_nummer',
                'type' => 'select',
                'desc'    => 'Bitte das Contact Form 7 Rückruf-Formulars auswählen.<br>'
                        . 'Beim Absenden von dem gewählten Formular werden (neben der Standard-Mail) <br>'
                        . 'zusätzlich die gewählten Sachbearbeiter per E-Mail informiert.',
                'default' => 'custom',
                'options' => $rs
            ));
            
            $cmb->add_field(array(
                'name' => __('Termine mit Themen verknüpfen?', 'cmb2'),
                'id' => $prefix . '_' . 'termine_mit_themen_verknuepfen',
                'type' => 'radio_inline',
                'desc'    => 'Bei JA sind mehrere Termine zur selben Uhrzeit am selben Tag möglich (d.h. ein Termin je Thema und Uhrzeit)',
                'options' => array(
                        'yes' => __( 'Ja', 'cmb2' ),
                        'no'   => __( 'Nein', 'cmb2' ),
                ),
            ));
            
            $cmb->add_field(array(
                'name' => __('Trigger Element', 'cmb2'),
                'id' => $prefix . '_' . 'trigger_element',
                'type' => 'text',
                'desc'    => 'ID oder Klasse des Trigger Elements (onChange). <br>'
                . 'Bei einer Änderung am Trigger-Element werden die verfügbaren Uhrzeiten erneut aus der Datenbank gelesen.<br>'
                . 'Das Uhrzeit-Auswahlfeld aktualisiert sich daraufhin.<br>Standard: #themengebiet',
                'default' => '#themengebiet',
            ));
            
            $cmb->add_field(array(
                'name' => __('Weitere Trigger Elemente', 'cmb2'),
                'id' => $prefix . '_' . 'trigger_more_element',
                'type' => 'text',
                'desc'    => 'Zusätzliche Trigger Elemente. <br>'
                . 'Bei einer Änderung am Trigger-Element werden die verfügbaren Uhrzeiten erneut aus der Datenbank gelesen.<br>'
                . 'Das Uhrzeit-Auswahlfeld aktualisiert sich daraufhin.<br>Standard: #rueckruf_heute, #rueckruf_next',
                'default' => '#rueckruf_heute, #rueckruf_next',
            ));
            
            $cmb->add_field(array(
                'name' => __('Trigger-Aktion (weitere)', 'cmb2'),
                'id' => $prefix . '_' . 'trigger_more_type',
                'type' => 'text',
                'desc'    => 'Welche Aktion auf ein weiteres Trigger Element soll auslößen?<br>Standard: focus',
                'default' => 'focus',
            ));
            
            $cmb->add_field(array(
                'name' => __('ID der Themen-Auswahlbox', 'cmb2'),
                'id' => $prefix . '_' . 'themen_auswahlbox_id',
                'type' => 'text',
                'desc'    => 'Standard: themengebiet',
                'default' => 'themengebiet',
            ));
            
            $cmb->add_field(array(
                'name' => __('Klasse(n) der Themen-Auswahlbox', 'cmb2'),
                'id' => $prefix . '_' . 'themen_auswahlbox_class',
                'type' => 'text',
                'desc'    => 'Standard: wpcf7-form-control wpcf7-select wpcf7-validates-as-required form-control',
                'default' => 'wpcf7-form-control wpcf7-select wpcf7-validates-as-required form-control',
            ));
            
            /* ------- */
            
            $cmb->add_field( array(
                'name' => 'Was kann dieses Plugin?',
                'desc' => 'Das Rückruf-Plugin verwaltet die verfügbaren Uhrzeiten, stellt eine Verwaltungsoberfläche und die unten aufgeführten Shortcodes mit Datenbank- und Ajax-Funktionen bereit. '
                        . '<br> Darüber hinaus hängt es sich in den Sende-Prozess von CF7 Formularen ein und übermittelt (sofern die Formular-ID in <i>System-Konfiguration</i> aufgenommen wurde)'
                        . '<br>eine E-Mail an die zuständigen Mitarbeiter des, durch den Besucher ausgewählten Themengebietes.<br>',
                'type' => 'title',
                'id'   => $prefix . '_' . 'infotext'
            ));
            
            $cmb->add_field( array(
                'name' => 'Shortcodes',
                'desc' => '<b>[rueckruf]</b><br>'
                        . 'Zeit-Auswahlfeld mit Ajax-Funktionalität für Contact Form 7. '
                        . 'Erzeugt 3 Dropdown-Felder mit verfügbaren Rückrufzeiten. '
                        . 'Die Zeiten werden aus der Flamingo Datenbank entnommen.'
                        . '<br><br>'
                        . '<b>[rueckrufthema]</b><br>'
                        . 'Der Shortcode wird durch ein Select-Box mit der Themen-Auswahl (aus der Config-Seite) ersetzt.'
                        . 'Zudem erhaelt sie die ID und Klasse(n) aus der Config-Seite.'
                        . '<br><br>'
                        . '<b>[rueckrufliste]</b><br>'
                        . 'Erzeugt eine Verwaltungs-Oberfläche mit einer Übersicht der gewünschten Rückrufe. '
                        . 'Wird in normalen Seiten verwendet.<br>'
                        . '<span style="color:red">Wichtig: Nur auf nicht öffentlichen / Passwort geschützten Seiten nutzen</span>'
                        . '<br><br>'
                        . '<b>[rueckrufblackliste]</b><br>'
                        . 'Erzeugt eine Verwaltungs-Oberfläche für gesperrte Anrufzeiten.'
                        . 'Wird in normalen Seiten verwendet.<br>'
                        . '<span style="color:red">Wichtig: Nur auf nicht öffentlichen / Passwort geschützten Seiten nutzen</span>'
                        . '<br><br>'
                        . '<b>Das Rückruf-Plugin benötigt als weitere Wordpress-Plugins: ContactForm 7 + Flamingo.</b><br>'
                        . '<br>'
                        . '<b style="color:green;">Tipp zur Geschwindigkeits-Optimierung der Rückruf-Liste/Statistik:</b><br>'
                        . 'in der Tabelle <b>wp_postmeta</b> ein weiteres Index/Indize anlegen ( "post_id", "meta_key" (191) )',
                'type' => 'title',
                'id'   => $prefix . '_' . 'shortcodesinfo'
            ));
            
            $cmb->add_field( array(
                'name' => 'Beispiel CF7 Code',
                'desc' => 'Dieser Code kann in einem neuen CF7 Feld genutzt werden',
                'type' => 'title',
                'id'   => $prefix . '_' . 'cf7info'
            ));
            
            $cmb->add_field(array(
                'name' => __('', 'cmb2'),
                'id' => $prefix . '_' . 'cf7_beispielcode',
                'type' => 'textarea',
                
                'desc'    => 'Hier ein Beispielcode zur Verwendung in einem CF7 Kontakt-Feld<br>'
                . 'Dieses Feld kann als Backup-Speicher für die eigene Konfiguration dienen.',
                
                'default' => '<label>Bitte wählen Sie ein Themengebiet aus [rueckrufthema]</label> '
                . '<label>Kalkulationsnummer [text kalkulationsnummer class:form-control] </label> '
                . '<label>Name, Vorname [text* kontaktname class:form-control] </label> '
                . '<label><br>Telefonnummer [text* tel class:form-control] </label> '
                . '<label><br>Arbeitgeber [text company class:form-control] </label> '
                . '<label><br>Betriebliche E-Mail Adresse[email companymail class:form-control] </label> '
                . '<label><br>Anliegen vorab [textarea anliegenvorab class:form-control] </label> '
                . '<label><br>Wann sollen wir Sie anrufen?</label> '
                . '[rueckruf] '
                . '[submit class:btn class:btn-warning "Formular absenden"]',
            ));
            
            /* ------- */
          
            // HTML Content fuer die Box
            $statistic_html = arp_statistics_get($this->metabox_id . '-tab-tab-' . $statistik_tab_nr);
            
            $cmb->add_field( array(
                'name' => 'Statistik',
                'desc' => $statistic_html,
                'type' => 'title',
                'id'   => $prefix . '_' . 'statistik'
            ));
            

            
        }

    /*
#####################################################################################################
#####################################################################################################
*/
        
	/**
	 * Register settings notices for display
	 *
	 * @since  0.1.0
	 * @param  int   $object_id Option key
	 * @param  array $updated   Array of updated fields
	 * @return void
	 */
	public function settings_notices( $object_id, $updated ) {
		if ( $object_id !== $this->key || empty( $updated ) ) {
			return;
		}

		add_settings_error( $this->key . '-notices', '', __( 'Einstellungen gespeichert.', 'wpmlrueckruf' ), 'updated' );
		settings_errors( $this->key . '-notices' );
	}

	/**
	 * Public getter method for retrieving protected/private variables
	 * @since  0.1.0
	 * @param  string  $field Field to retrieve
	 * @return mixed          Field value or exception is thrown
	 */
	public function __get( $field ) {
		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'metabox_id', 'title', 'options_page' ), true ) ) {
			return $this->{$field};
		}

		throw new Exception( 'Invalid property: ' . $field );
	}

}

/**
 * Helper function to get/return the Wpmlrueckruf_Admin object
 * @since  0.1.0
 * @return Wpmlrueckruf_Admin object
 */
function wpmlrueckruf_admin() {
	return Wpmlrueckruf_Admin::get_instance();
}

/**
 * Wrapper function around cmb2_get_option
 * @since  0.1.0
 * @param  string $key     Options array key
 * @param  mixed  $default Optional default value
 * @return mixed           Option value
 */
function wpmlrueckruf_get_option( $key = '', $default = false ) {
	if ( function_exists( 'cmb2_get_option' ) ) {
		// Use cmb2_get_option as it passes through some key filters.
		return cmb2_get_option( wpmlrueckruf_admin()->key, $key, $default );
	}

	// Fallback to get_option if CMB2 is not loaded yet.
	$opts = get_option( wpmlrueckruf_admin()->key, $default );

	$val = $default;

	if ( 'all' == $key ) {
		$val = $opts;
	} elseif ( is_array( $opts ) && array_key_exists( $key, $opts ) && false !== $opts[ $key ] ) {
		$val = $opts[ $key ];
	}

	return $val;
}

// Get it started
wpmlrueckruf_admin();
