<?php
/*
Plugin Name: IC Administrator
Plugin URI: http://www.icristiana.org/
Description: Customise WordPress with powerful, professional and intuitive fields
Version: 0.0.1
Author: Luis Alvarez
Author URI: https://github.com/luisalvarez
Copyright: Luis Alvarez
*/

namespace IC;
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if (!class_exists("ic")) :

class ic {
    // settings
    var $settings;

    protected $actions;
    protected $filters;
    protected $version;

    public function __construct($version){
        $this->actions = array();
        $this->filters = array();
        $this->version = $version;
        /** Step 2 (from text above). */
        add_action( 'admin_menu', array($this, "main_menu"));
        register_activation_hook( __FILE__, array($this,'db_setup') );
    }

    /** Step 1. */
    public function main_menu() {
	    add_options_page( 'My Plugin Options', __("IC Admin","IC"), 'manage_options', 'ic-plugin', array($this,"my_plugin_options") );
    }

    /** Step 3. */
    public function my_plugin_options() {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        echo '<div class="wrap">';
        echo '<p>Here is where the form would go if I actually had options.</p>';
        echo '</div>';
    }


    public function enqueue_styles(){
        wp_enqueue_style(
            'ic-plugin-styles',
            plugin_dir_url( __FILE__ ) . 'css/ic-plugin-styles-admin.css',
            array(),
            $this->version,
            FALSE
        );
    }

    public function db_setup() {
        global $wpdb;
     
        $table_persons = $wpdb->prefix . "persons"; 
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_persons (
            id INT NOT NULL AUTO_INCREMENT,
            first_name VARCHAR(45) NOT NULL,
            last_name VARCHAR(45) NOT NULL,
            nickname VARCHAR(45) NULL,
            gender ENUM('M', 'F') DEFAULT 'M',
            civil_state ENUM('S', 'C', 'U', 'D', 'V') DEFAULT 'S',
            occupation VARCHAR(45) NULL,
            profile_picture TEXT NULL,
            lives_in INT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        $table_relationships = $wpdb->prefix . "relationships"; 

        $sql = "CREATE TABLE IF NOT EXISTS $table_relationships (
            person_a int(11) NOT NULL,
            person_b int(11) NOT NULL,
            relation VARCHAR(45) DEFAULT NULL,
            PRIMARY KEY (person_a,person_b),
            KEY fk_Persons_has_Persons_Persons1_idx (person_b),
            KEY fk_Persons_has_Persons_Persons_idx (person_a),
            CONSTRAINT fk_Persons_has_Persons_Persons FOREIGN KEY (person_a) REFERENCES $table_persons (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
            CONSTRAINT fk_Persons_has_Persons_Persons1 FOREIGN KEY (person_b) REFERENCES $table_persons (id) ON DELETE NO ACTION ON UPDATE NO ACTION
            ) $charset_collate;";

        dbDelta( $sql );

        $table_occupation = $wpdb->prefix . "occupations";

        $sql = "CREATE TABLE IF NOT EXISTS $table_occupation (
            id INT NOT NULL AUTO_INCREMENT,
            persons_id INT NOT NULL,
            name VARCHAR(45) NULL,
            PRIMARY KEY (id),
            KEY fk_occupations_persons_idx (persons_id),
            CONSTRAINT fk_occupations_persons FOREIGN KEY (persons_id) REFERENCES $table_persons (id) ON DELETE NO ACTION ON UPDATE NO ACTION
            ) $charset_collate;";

        dbDelta( $sql );    
        
        $table_contact = $wpdb->prefix . "contact_info";
        $sql = "CREATE TABLE IF NOT EXISTS $table_contact (
            id INT NOT NULL AUTO_INCREMENT,
            type VARCHAR(45) NOT NULL,
            info VARCHAR(45) NULL,
            persons_id INT NOT NULL,
            PRIMARY KEY (id),
            KEY fk_contact_info_persons_idx (persons_id),
            CONSTRAINT fk_contact_info_persons FOREIGN KEY (persons_id) REFERENCES $table_persons (id) ON DELETE NO ACTION ON UPDATE NO ACTION
            ) $charset_collate;";

        dbDelta( $sql ); 

        $table_sectors = $wpdb->prefix . "sectors";

        $sql = "CREATE TABLE IF NOT EXISTS $table_sectors (
            id INT NOT NULL COMMENT 'INPOSDOM',
            name VARCHAR(100) NOT NULL,
            PRIMARY KEY (id))
          $charset_collate;";

        dbDelta( $sql );
        
        $table_structure = $wpdb->prefix . "structures"; 
        $sql = "CREATE TABLE IF NOT EXISTS $table_structure (
            id int NOT NULL AUTO_INCREMENT,
            name varchar(45) NOT NULL,
            type ENUM('Small Group','Zone','Network','Ministry') DEFAULT 'Small Group',
            sectors_id int NOT NULL,
            parent_id int DEFAULT NULL,
            PRIMARY KEY (id),
            KEY fk_structures_sectors_idx (sectors_id),
            KEY fk_structures_structures_idx (parent_id),
            CONSTRAINT fk_structures_sectors FOREIGN KEY (sectors_id) REFERENCES $table_sectors (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
            CONSTRAINT fk_structures_structures FOREIGN KEY (parent_id) REFERENCES $table_structure (id) ON DELETE NO ACTION ON UPDATE NO ACTION
          ) $charset_collate;";

        dbDelta( $sql );  
        
        $table_memberships = $wpdb->prefix . "memberships";

        $sql = "CREATE TABLE IF NOT EXISTS $table_memberships (
            id int(11) NOT NULL AUTO_INCREMENT,
            persons_id int(11) NOT NULL,
            role varchar(45) NOT NULL,
            structures_id int(11) NOT NULL,
            PRIMARY KEY (id),
            KEY fk_memberships_persons_idx (persons_id),
            KEY fk_memberships_structures_idx (structures_id),
            CONSTRAINT fk_memberships_persons FOREIGN KEY (persons_id) REFERENCES $table_persons (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
            CONSTRAINT fk_memberships_structures1 FOREIGN KEY (structures_id) REFERENCES $table_structure (id) ON DELETE NO ACTION ON UPDATE NO ACTION
          ) $charset_collate;";

        dbDelta( $sql ); 

        $table_events = $wpdb->prefix . "events";

        $sql = "CREATE TABLE IF NOT EXISTS $table_events (
            id INT NOT NULL AUTO_INCREMENT,
            date TIMESTAMP NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id))
          $charset_collate;";

        dbDelta( $sql ); 

        $table_milestone_type = $wpdb->prefix . "milestone_types";

        $sql = "CREATE TABLE IF NOT EXISTS $table_milestone_type (
            id INT NOT NULL,
            `name` VARCHAR(45) NOT  NULL,
            PRIMARY KEY (id))
          $charset_collate;";

        dbDelta( $sql );

        $table_milestone = $wpdb->prefix . "milestones";   

        $sql = "CREATE TABLE IF NOT EXISTS $table_milestone (
            id int(11) NOT NULL AUTO_INCREMENT,
            `date`timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            persons_id int(11) NOT NULL,
            events_id int(11) DEFAULT NULL,
            milestone_type_id int(11) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY fk_milestones_persons_idx (persons_id),
            KEY fk_milestones_events_idx (events_id),
            KEY fk_milestones_milestone_type_idx (milestone_type_id),
            CONSTRAINT fk_milestones_events FOREIGN KEY (events_id) REFERENCES $table_events (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
            CONSTRAINT fk_milestones_milestone_type FOREIGN KEY (milestone_type_id) REFERENCES $table_milestone_type (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
            CONSTRAINT fk_milestones_persons FOREIGN KEY (persons_id) REFERENCES $table_persons (id) ON DELETE NO ACTION ON UPDATE NO ACTION
          ) $charset_collate;";

        dbDelta( $sql );

        $table_event_types = $wpdb->prefix . "event_types"; 

        $sql = "CREATE TABLE IF NOT EXISTS $table_event_types (
            id int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(45) NOT NULL,
            events_id int(11) NOT NULL,
            PRIMARY KEY (id),
            KEY fk_event_types_events_idx (events_id),
            CONSTRAINT fk_event_types_events FOREIGN KEY (events_id) REFERENCES $table_events (id) ON DELETE NO ACTION ON UPDATE NO ACTION
          ) $charset_collate;";

        dbDelta( $sql );        
        
    }

    public function add_action( $hook, $component, $callback ) {
        $this->actions = $this->add( $this->actions, $hook, $component, $callback );
    }
 
    public function add_filter( $hook, $component, $callback ) {
        $this->filters = $this->add( $this->filters, $hook, $component, $callback );
    }
 
    private function add( $hooks, $hook, $component, $callback ) {
 
        $hooks[] = array(
            'hook'      => $hook,
            'component' => $component,
            'callback'  => $callback
        );
 
        return $hooks;
 
    }
 
    public function run() {
 
        foreach ( $this->filters as $hook ) {
            add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ) );
        }
 
        foreach ( $this->actions as $hook ) {
            add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ) );
        }
 
    }
}


/*
*  acf
*
*  The main function responsible for returning the one true acf Instance to functions everywhere.
*  Use this function like you would a global variable, except without needing to declare the global.
*
*  Example: <?php $acf = acf(); ?>
*
*  @type	function
*  @date	4/09/13
*  @since	4.3.0
*
*  @param	N/A
*  @return	(object)
*/

function ic()
{
	global $ic;
	
	if( !isset($ic) )
	{
		$ic = new ic("0.0.1");
	}
	
	return $ic;
}


// initialize
ic();


endif; // class_exists check

?>
