# Aenderungen

## Mainscript: 

- Aenderung der URL:

~~~
        public function setup_admin_scripts() {
            
            wp_register_script( 'cmb-tabs', plugins_url( 'js/tabs.js', __FILE__ ), array( 'jquery' ), self::VERSION, true );
            wp_enqueue_script( 'cmb-tabs' );

            wp_enqueue_style( 'cmb-tabs', plugins_url( 'css/tabs.css', __FILE__ ), array(), self::VERSION );
            wp_enqueue_style( 'cmb-tabs' );

        }
~~~

## CSS:

- Nahezu alles...

## JS:

- Gruppenbox-Funktion eingebaut
- Automatischer Titel