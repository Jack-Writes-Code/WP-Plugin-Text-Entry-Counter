<?php
/*
Plugin Name: Text Entry Counter
Plugin URI: https://github.com/Jack-Writes-Code/WP-Plugin-Text-Entry-Counter
Description: Track number of times text is entered in a field on your website.
Version: 1.0.0.1
Author: Jack Whitworth
Author URI: https://jackwhitworth.co.uk
License: None
Text Domain: Text Entry Counter
*/


class TEC_Main {
    public function __construct() {
        $this->initialise_button();
        $this->initialise_endpoints();
    }
    
    private function initialise_button() {
        $FH = new TEC_Form_Handler;
        add_shortcode( 'text_entry_form', array( $FH, 'text_entry_form' ) );
    }
    
    private function initialise_endpoints() {
        $EP = new TEC_Endpoints;
    }
}


class TEC_Database_Handler {
    private $t_name = "tec_text_entries";
    
    public function create_table_if_doesnt_exist () {
        global $wpdb;
        global $charset_collate;
        $table_name = $wpdb->prefix.$this->t_name;
        $charset_collate = $wpdb->get_charset_collate();
        
        if( $wpdb->get_var("SHOW TABLES LIKE '" . $table_name . "'") !=  $table_name)
        {   $create_sql = "CREATE TABLE " . $table_name . " (
                id INT(26) NOT NULL auto_increment,
                text VARCHAR(255) NOT NULL UNIQUE,
                calls INT(26) NOT NULL DEFAULT 0,
                PRIMARY KEY (id))$charset_collate;";
        }
        require_once(ABSPATH . "wp-admin/includes/upgrade.php");
        dbDelta( $create_sql );
    }
    
    public function add_value( $value ) {
        global $wpdb;
        $table_name = $wpdb->prefix. $this->t_name;
        $data = array('text' => $value, 'calls' => 1 );
        $format = array( '%s', '%d' );
        return $wpdb->insert($table_name, $data, $format);
    }
    
    public function read_value( $value ) {
        /* GETS ENTRY FROM THE DATABASE AND RETURNS IT AS AN ARRAY. IF NON FOUND RETURNS 0 CALLS */
        global $wpdb;
        $table_name = $wpdb->prefix. $this->t_name;
        $results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$table_name} WHERE text = %s", $value) );
        if ($results != NULL) {
            $this_result = get_object_vars($results[0]);
            $data = array( 'calls'=>$this_result['calls'], 'text'=>$this_result['text']);
            return $data;
        }
        return array('calls'=>0, 'text'=>$value);
    }
    
    public function incriment_value( $value ) {
        global $wpdb;
        $table_name = $wpdb->prefix. $this->t_name;
        $total = $this->read_value( $value )['calls'] + 1;
        $results = $wpdb->update( $table_name, ['calls'=>$total], ['text'=>$value]);
        return $results; //RETURNS INT OF NUM OF ROWS MODIFIED
    }
    
    public function test_value ( WP_REST_Request $req ) {
        $value = $req['value'];
        if ( $value == NULL ) {
            return array('status'=>'error', 'description'=>'please provide a value');
        }
        
        $results = $this->read_value( $value );
        if ( $results['calls'] > 0 ) {
            $this->incriment_value( $value );
            $results['calls']++;
        }
        else {
            $this->add_value( $value );
        }
        return array('status'=>'success', 'description'=>'data retrieved', 'data'=>$results);
    }
}


class TEC_Endpoints {
    public function __construct() {
        $this->create_check_value();
    }
    
    public function create_check_value() {
        //https://www.ismypasswordgood.com/wp-json/passwords/v1/check 
        add_action( 'rest_api_init', function () {
            register_rest_route( 'passwords/v1', '/check', array(
                'methods' => 'GET',
                'callback' => array(new TEC_Database_Handler, 'test_value')
            ));
        });
    }
}


class TEC_Form_Handler {
    public function text_entry_form() {
        $html = file_get_contents( plugin_dir_url( __FILE__ ).'html/tec-form.html' );
        return $html;
    }
}


//SETUP SECTION
function tec_run_at_activation () {
    $DH = new TEC_Database_Handler;
    $DH->create_table_if_doesnt_exist();
}
function tec_load_scripts() {
    wp_enqueue_script( 'tec_script_1', plugin_dir_url( __FILE__ ).'js/tec-button-onclick.js', array('jquery') );
    wp_enqueue_style( 'tec_stylesheet_1', plugin_dir_url( __FILE__ ).'css/tec-styles.css', array() );
}
register_activation_hook( __FILE__, 'tec_run_at_activation');
add_action( 'wp_enqueue_scripts', 'tec_load_scripts' );
//END SETUP SECTION


new TEC_Main;