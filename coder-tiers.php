<?php
defined('ABSPATH') || exit;
/**
 * Plugin Name: Coder Tiers
 * Description: Lightweight ACL tiers service. use coder_tiers, coder_role and coder_acl filters to map tiers to other plugins.
 * Version:     0.1.0
 * Author:      Coder#1
 * Text Domain: coder_tiers
 */
define('CODER_TIERS_DIR', plugin_dir_path(__FILE__));

// Activation hook
register_activation_hook(__FILE__, function(){
    CoderTiers::install();
});
register_deactivation_hook(__FILE__, function(){
    CoderTiers::uninstall();
});

add_action('init', function () {
    // Bootstrap
    CoderTiers::instance();
    /// REQUEST ACCESS TO THE CURRENT SERVICE/RESOURCE
    add_filter('coder_acl', function( $tier = '' ){
        //IMPORT THE CURRENT LOADED ROLE, EMPTY BY DEFAULT
        $role = apply_filters('coder_role', '');
        //var_dump([$role,$tier]);
        return CoderTiers::instance()->tier($role)->can($tier);
    });
    //LIST ALL AVAILABLE TIERS DEFINED IN TIERS PLUGIN
    add_filter('coder_tiers', function(){
        return CoderTiers::instance()->list();
    }, 10, 0);

    if (is_admin()) {
        // Admin hooks
        require_once sprintf('%s/admin.php',CODER_TIERS_DIR);
    }
    
    //add_filter('coder_role', function(){ return 'diamond'; } );
    //var_dump(apply_filters('coder_acl', 'obsidian'));
    //die;    
});


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
     * @return String[]|\CoderTier
     */
    public function tiers( $map = false ) {
        return $map ?
                array_map(function($tier){
                    return new CoderTier($tier);
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
     * @return \CoderTier
     */
    public function tier( $tier = '' ){
        return new CoderTier($tier,true);
    }
    

    /**
     * @return \CoderTiers
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
            title VARCHAR(32) NOT NULL,
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
                    'title' => $r['title'],
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
class CoderTier{
    /**
     * @var String
     */
    private $_tier = '';
    /**
     * @var String[]
     */
    private $_tiers = array();
    /**
     * @param String $tier
     * @param bool $preload
     */
    function __construct( $tier = '' , $preload = false ) {
        
        $this->_tier = $tier;
        
        if( $preload ){
            $this->load($this->tier(), $this->_tiers);
        }
        else{
            $this->manager()->roles($this->tier());
        }
    }
    /**
     * @return \CoderTiers
     */
    private function manager(){
        return \CoderTiers::instance();
    }
    /**
     * @return \CoderTiers
     */
    private function tierdata(){
        return $this->manager()->tiers();
    }
    /**
     * @param String $tier
     * @param String[] $list
     */
    private function load( $tier = '' , &$list = array() ){
        $db = $this->tierdata();
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
    public function tiers(){ return $this->_tiers; }
    /**
     * @param String $tier
     * @return bool
     */
    public function can( $tier = ''){
        return strlen($tier) && ($tier === $this->tier() || in_array($tier, $this->tiers()));
    }
}



