<?php namespace CODERS\Tiers;
defined('ABSPATH') || exit;
/**
 * Plugin Name: Coder Tiers
 * Description: Lightweight ACL tiers service. use coder_tiers, coder_role and coder_acl filters to map tiers to other plugins.
 * Version:     0.1.0
 * Author:      Coder#1
 * Text Domain: coder_tiers
 */
define('CODER_TIERS_DIR', plugin_dir_path(__FILE__));
define('CODER_TIERS_URL', plugin_dir_url(__FILE__));

// Activation hook
register_activation_hook(__FILE__, function(){
    \CODERS\Tiers\CoderTiers::install();
});
register_deactivation_hook(__FILE__, function(){
    \CODERS\Tiers\CoderTiers::uninstall();
});

add_action('init', function () {
    // Bootstrap
    \CODERS\Tiers\CoderTiers::instance();
    /// REQUEST ACCESS TO THE CURRENT SERVICE/RESOURCE
    add_filter('coder_acl', function( $ignore = false , $tier = '' ){
        //IMPORT THE CURRENT LOADED ROLE, EMPTY BY DEFAULT
        $role = apply_filters('coder_role', '');
        //var_dump([$role,$tier]);
        return \CODERS\Tiers\CoderTiers::instance()->tier($role)->can($tier);
    } , 10 , 2);
    //LIST ALL AVAILABLE TIERS DEFINED IN TIERS PLUGIN
    add_filter('coder_tiers', function(){
        return \CODERS\Tiers\CoderTiers::instance()->list();
    }, 10, 1);
    
    //include the tier list for rewards among different plugins
    add_filter('coder_rewards',function( $rewards ){
        if(is_array($rewards)){
            $rewards['coder_tiers'] = array(
                'title' => __('Rewards','coder_tiers'),
                'type' => 'string',
                'content' => apply_filters('coder_tiers',array()),
            );
        }
        return $rewards;
    });

    if (is_admin()) {
        // Admin hooks
        require_once sprintf('%s/admin.php',CODER_TIERS_DIR);
    }
    
    //coder_tiers_test();
});

function coder_tiers_test(){
    
    
    add_filter('coder_role', function(){ return 'diamond'; } );
    //add_filter('coder_role', function(){ return 'silver'; } );
    //add_filter('coder_role', function(){ return 'quicksilver'; } );
    
    var_dump(apply_filters('coder_role',''));
    var_dump(apply_filters('coder_acl', 'obsidian'));
    var_dump(apply_filters('coder_tiers', array()));
    die;
}


/**
 * 
 */
class CoderTiers {

    /** Singleton instance */
    private static $instance = null;

    /** Cached tiers array (id => row) */
    private $_tiers = array();

    /** Prevent direct construction. */
    private function __construct() {
        $this->_tiers = self::load();
    }

    /**
     * @param bool $map
     * @return String[]|\CODERS\Tiers\Tier
     */
    public function tiers( $map = false ) {
        return $map ?
                array_map(function($tier){
                    return new Tier($tier);
                }, array_keys( $this->_tiers ) ):
                $this->_tiers;
    }
    /**
     * @return array
     */
    public function list(){
        $tiers = array();
        foreach ($this->tiers() as $tier => $data ){
            $tiers[$tier] = $data['title'];
        }
        return $tiers;
    }
    /**
     * @param string $tier
     * @return string[]
     */
    public function roles( $tier = '' ){
        return $this->has($tier) ? $this->tiers()[$tier]['roles'] : array();
    }
    
    /**
     * @param String $tier
     * @return bool
     */
    public function has($tier = '') {
        return array_key_exists($tier, $this->tiers());
    }
    /**
     * @param String $tier
     * @return \CODERS\Tiers\Tier
     */
    public function tier( $tier = '' ){
        return new Tier($tier,true);
    }
    

    /**
     * @return \CODERS\Tiers\CoderTiers
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @global wpdb $wpdb
     * @return String
     */
    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'coder_tiers';
    }
    /**
     * @param bool $remove
     */
    public  static function uninstall($remove = false ) {
        //add deactivation methods here, remove table data, resources etc.
    }
    /** Activation: create DB table */
    public static function install() {
        global $wpdb;
        $table = self::table();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            tier VARCHAR(24) NOT NULL,
            roles VARCHAR(256) DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (tier),
            UNIQUE KEY tier (tier)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $results = dbDelta($sql, true);
        error_log(print_r($results, true));
    }

    /**
     * @global wpdb $wpdb
     * @return Array
     */
    private static function load() {
        $tiers = [];
        global $wpdb;
        $table = self::table();
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY `tier` ASC", ARRAY_A);
        if ($rows) {
            foreach ($rows as $r) {
                $tiers[$r['tier']] = array(
                    'tier' => $r['tier'],
                    //'title' => $r['title'],
                    'roles' => array_filter(explode(':',$r['roles'])),
                );
            }
        }
        return $tiers;
    }
}
/**
 * 
 */
class Tier{
    /**
     * @var String
     */
    private $_tier = '';
    /**
     * @var String[]
     */
    private $_roles = array();
    /**
     * @param String $tier
     * @param bool $preload
     */
    function __construct( $tier = '' , $preload = false ) {
        
        $this->_tier = $tier;
        
        if( $preload ){
            $this->load($this->tier(), $this->_roles);
        }
        else{
            $this->manager()->roles($this->tier());
        }
    }
    /**
     * @param string $name
     * @return string
     */
    public function __get( $name ){
        $get = sprintf('get%s', ucfirst($name));
        return method_exists($this, $get) ? $this->$get() : '';
    }
    /**
     * @return string
     */
    public function getName(){
        return $this->_tier;
    }
    /**
     * @return string
     */
    public function getTitle(){
        return ucfirst($this->_tier);
    }
    /**
     * @return string
     */
    public function getRoles(){
        return implode(', ', $this->_roles);
    }
    /**
     * @return array
     */
    public function listRoles(){
        return $this->roles();
    }

    
    /**
     * @return \CODERS\Tiers\CoderTiers
     */
    private function manager(){
        return \CODERS\Tiers\CoderTiers::instance();
    }
    /**
     * @param String $tier
     * @param String[] $list
     */
    private function load( $tier = '' , &$list = array() ){
        $db = $this->manager()->tiers();
        if( !in_array($tier, $list)){
            if( $tier !== $this->tier()){
                $list[] = $tier;
            }
            //$tiers = array_key_exists($tier, $db) ? $db[$tier]['roles'] : array();
            $roles = $this->manager()->roles($tier);
            foreach ($roles as $t ) {
                $this->load($t,$list);                    
            }
        }
    }
    /**
     * @return String
     */
    public function tier(){ return $this->_tier; }
    /**
     * @return String[]
     */
    public function roles(){ return $this->_roles; }
    /**
     * @param String $tier
     * @return bool
     */
    public function can( $tier = ''){
        return strlen($tier) && ($tier === $this->tier() || in_array($tier, $this->roles()));
    }
}



