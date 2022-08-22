<?php
/*
Plugin Name: Text Entry Counter
Plugin URI: https://github.com/Jack-Writes-Code/WP-Plugin-Text-Entry-Counter
Description: Track number of times text is entered in a field on your website.
Version: 1.0.0.0
Author: Jack Whitworth
Author URI: https://jackwhitworth.co.uk
License: GNU General Public License v3.0 
Text Domain: Text Entry Counter
*/


class JW_Main {
    public function __construct() {
        $this->initialise_button();
        $this->initialise_endpoints();
    }
    
    private function initialise_button() {
        $FH = new JW_Form_Handler;
        add_shortcode( 'text_entry_form', array( $FH, 'text_entry_form' ) );
    }
    
    private function initialise_endpoints() {
        $EP = new JW_Endpoints;
    }
}


class JW_Database_Handler {
    private $t_name = "text_entries";
    
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
        $wpdb->insert($table_name, $data, $format);
        $my_id = $wpdb->insert_id;
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


class JW_Endpoints {
    public function __construct() {
        $this->create_check_value();
    }
    
    public function create_check_value() {
        //https://www.ismypasswordgood.com/wp-json/passwords/v1/check 
        add_action( 'rest_api_init', function () {
            register_rest_route( 'passwords/v1', '/check', array(
                'methods' => 'GET',
                'callback' => array(new JW_Database_Handler, 'test_value')
            ));
        });
    }
}


class JW_Form_Handler {
    public function text_entry_form() {
        $html = '
        <!--HTML FORM-->
        <div class="text_entry_container">
            <input type="text" id="test_text_entry" placeholder="Entry your password here">
            <p id="text_feedback">Enter a password above and click "Check Password" to test it against our database.</p>
            <button id="submit_password_check">Check Password</button>
        </div>
        
        <!--FUNCTION FOR ONCLICK EVENT IN FORM-->
        <script type="text/javascript">
            jQuery("#submit_password_check").click(function(){
                jQuery.get("/wp-json/passwords/v1/check", {value: jQuery("#test_text_entry").val()}, function(data, status){
                    feedback_field = jQuery("#text_feedback")
                    if (data["status"] == "success") {
                        count = data["data"]["calls"];
                        if (count > 0) {
                            feedback_field.html("Password found " + count + " times.");
                        }
                        else {
                            feedback_field.html("Your password wasnt found. However it has now been added to the database.");
                        }
                    }
                    else {
                        feedback_field.html("Please provide a password in the text field.");
                    }
                });
            }); 
        </script>
        ';
        return $html;
    }
}


function run_at_activation () {
    $DH = new JW_Database_Handler;
    $DH->create_table_if_doesnt_exist();
}
register_activation_hook( __FILE__, 'run_at_activation');
new JW_Main;