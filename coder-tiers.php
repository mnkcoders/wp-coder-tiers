<?php namespace CODERS\Tiers;
defined('ABSPATH') || exit;
/**
 * Plugin Name: Coder Tiers
 * Description: Lightweight ACL tiers service. use coder_tiers, coder_role and coder_acl filters to map tiers to other plugins.
 * Version:     1.0.0
 * Author:      Coder#1
 * Text Domain: coder_tiers
 */
define('CODER_TIERS_DIR', plugin_dir_path(__FILE__));
define('CODER_TIERS_URL', plugin_dir_url(__FILE__));

// Activation hook
register_activation_hook(__FILE__, function(){
    \CODERS\Tiers\Data::install();
});
register_deactivation_hook(__FILE__, function(){
    //\CODERS\Tiers\Data::uninstall();
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
    private static $_instance = null;

    /** Cached tiers array (id => row) */
    private $_tiers = array();
    /**
     * @var array
     */
    private $_log = array();
    /**
     * 
     */
    private function __construct() {}
    /**
     * @return array
     */
    public function log(){
        return $this->_log;
    }
    /**
     * @param string $message
     * @param string $type
     * @return \CODERS\Tiers\CoderTiers
     */
    public function notify($message,$type = 'info'){
        if( $message ){
            $this->_log[] = array(
                'content' => $message,
                'type' => $type,
            );
        }
        return $this;
    }

    /**
     * @return \CODERS\Tiers\Data
     */
    public function db(){
        return new Data();
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
            $tiers[$tier] = $data['tier'];
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
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->load();
        }
        return self::$_instance;
    }

    /**
     * @return \CODERS\Tiers\CoderTiers
     */
    private function load() {
        $this->_tiers = $this->db()->load();
        return $this;
    }
    /**
     * @param string $tier
     * @param string $role
     * @return boolean
     */
    public function remove( $tier = '', $role = ''){
        return $this->db()->delete( $tier );
    }
    /**
     * @param string $tier
     * @return \CODERS\Tiers\Tier
     */
    public function create( $tier = ''){
        return $this->db()->create($tier) ? new Tier($tier) : null;
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
            $this->_roles = $this->manager()->roles($this->tier());
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
     * @param string $role
     * @param boolean $save
     * @return boolean
     */
    public function add($role = '' , $save = false){
        if(strlen($role) && $role !== $this->tier() && !$this->has($role)){
            $this->_roles[] = $role;
            if( $save) {
                return $this->manager()->db()->save(
                        $this->tier(),
                        $this->roles()
                );
            }
            return true;
        }
        return false;
    }
    /**
     * @param string $role
     * @param boolean $save
     * @return boolean
     */
    public function drop( $role = '' , $save = false ){
        if(strlen($role) && $this->has($role)){
            $roles = array();
            foreach($this->_roles as $r ){
                if( $role !== $r){ $roles[] = $r; }
            }
            $this->_roles = $roles;
            return $save ? $this->save() : true;
        }
        return false;
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
     * @param string  $role
     * @return bool
     */
    public function has( $role = ''){
        return in_array($role, $this->roles());
    }
    /**
     * @param String $role
     * @return bool
     */
    public function can( $role = ''){
        return strlen($role) && ($role === $this->tier() || $this->has($role));
    }
    /**
     * @return bool
     */
    public function save(){
        return $this->manager()->db()->save($this->tier(),$this->roles());
    }
}


class Data{
    /**
     * @param string $message
     * @param string $type
     * @return \CODERS\Tiers\Data
     */
    public function notify($message,$type='info'){
        CoderTiers::instance()->notify($message, $type);
        return $this;
    }

    /**
     * @global \wpdb $wpdb
     * @return \wpdb
     */
    public static function db(){
        global $wpdb;
        return $wpdb;
    }
    /**
     * @return string
     */
    public static function table(){
        return self::db()->prefix . 'coder_tiers';
    }
    /**
     * @return string
     */
    protected static function charset(){
        return self::db()->get_charset_collate();
    }
    /**
     * 
     */
    public static function install(){
        $table = self::table();
        $charset_collate = self::charset();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            tier VARCHAR(24) NOT NULL,
            roles VARCHAR(256) DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (tier),
            UNIQUE KEY tier (tier)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $results = dbDelta($sql, true);
        //$this->notify(print_r($results,true));
        foreach($results as $r ){
            CoderTiers::instance()->notify($r);
        }
        //error_log(print_r($results, true));        
    }
    /**
     * @param string $tier
     * @return array
     */
    public function load($tier = ''){
        $tiers = [];
        $sql = array(sprintf("SELECT * FROM %s",self::table()));
        if( $tier){
            $sql[] = sprintf("WHERE `tier`='%s'",$tier);
        }
        //$select[] = "ORDER BY `tier` ASC";
        $rows = self::db()->get_results(implode(' ', $sql), ARRAY_A);
        if ($rows) {
            foreach ($rows as $r) {
                $tiers[$r['tier']] = array(
                    'tier' => $r['tier'],
                    //'title' => $r['title'],
                    'roles' => array_filter(explode(':',$r['roles'])),
                );
            }
        }
        $this->notify(self::db()->error,'error');
        return $tiers;        
    }
    /**
     * @param string $tier
     * @param array $roles
     * @return int
     */
    public function save( $tier = '', array $roles = array()){
        if( $tier ){
            $update = self::db()->update(
                    self::table(),
                    array('roles'=>implode(':', $roles)),
                    array('tier'=>$tier));
            $this->notify(self::db()->error,'error');
            return $update !== false && $update > 0;
        }
        
        return false;
    }
    /**
     * @param string $tier
     * @return bool
     */
    public function create( $tier = ''){
        $output = self::db()->insert(self::table(), array('tier'=>$tier,'roles'=>array()));
        return $output ? $output > 0 : false;
    }
    /**
     * @param string $tier
     * @return boolean
     */
    public function delete( $tier = ''){
        if( $tier ){
            $delete = self::db()->delete(self::table(), array('tier'=>$tier));
            $this->notify(self::db()->error,'error');
            return $delete ? $delete > 0 : false;
        }
        return false;
    }
}



