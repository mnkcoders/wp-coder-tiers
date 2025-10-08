<?php
defined('ABSPATH') or exit;
/**
 * Plugin Name: Coder Tiers
 * Description: Lightweight ACL tiers service. 
 * Version:     0.1.0
 * Author:      Coder#1
 * Text Domain: coder_tiers
 */
define('CODER_TIERS_DIR', plugin_dir_path(__FILE__));
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
        $this->_tiers = $this->load();
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
     * @param string $tier
     * @return string[]
     */
    public function roles( $tier = '' ){
        return $this->has($tier) ? $this->tiers()['roles'] : array();
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
        dbDelta($sql);
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
            $this->load($this->tier(),$this->_tiers);
        }
        else{
            $this->ct()->roles($this->tier());
        }
    }
    /**
     * @return \CoderTiers
     */
    private function ct(){
        return \CoderTiers::instance();
    }
    /**
     * @return \CoderTiers
     */
    private function tierdata(){
        return $this->ct()->tiers();
    }
    /**
     * @param String $tier
     * @param String[] $list
     */
    private function load( $tier = '' , $list = array() ){
        $db = $this->tierdata();
        if( !in_array($tier, $list)){
            $list[] = $tier;
            $tiers = array_key_exists($tier, $db) ? $db[$tier]['roles'] : array();
            foreach ($tiers as $t ) {
                $this->load($t,&$list);
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
        return !empty($tier) && in_array($tier, $this->tiers());
    }
}
/**
 * 
 */
class CoderTiersAdmin {

    private $_attributes = array(
        //define controller-view attributes here
    );
    
    /**
     * @return \CoderTiers
     */
    private function content(){
        return \CoderTiers::instance();
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        switch(true){
            case preg_match('/^list_/', $name):
                return $this->__list(substr($name, 5));
            case preg_match('/^is_/', $name):
                return $this->__is(substr($name, 3));
            case preg_match('/^has_/', $name):
                return $this->__has(substr($name, 4));
            case preg_match('/^view_/', $name):
                return $this->__view(substr($name, 5));
        }
        return array_key_exists($name,$this->_attributes) ? $this->_attributes[$name] : '';
    }
    /**
     * @param string $name
     * @return bool Description
     */
    private function __view($name = ''){
        $view = $this->viewPath($name);
        if(!empty($view) && file_exists($view)){
            require $view;
            return true;
        }
        printf('<!-- INVALID VIEW %s -->',$name);
        return false;
    }
    /**
     * @param string $name
     * @return array
     */
    private function __list($name){
        $list = sprintf('list%s', ucfirst($name));
        return method_exists($this, $list) ? $this->$list() : array();
    }
    /**
     * @param string $name
     * @return bool
     */
    private function __is($name){
        $is = sprintf('is%s', ucfirst($name));
        return method_exists($this, $is) ? $this->$is() : false;
    }
    /**
     * @param string $name
     * @return bool
     */
    private function __has($name){
        $has = sprintf('has%s', ucfirst($name));
        return method_exists($this, $has) ? $this->$has() : false;
    }
    /**
     * @return Array[]
     */
    protected function listTiers() {
        return $this->content()->tiers();
    }
    /**
     * @param String $view
     * @return String
     */
    private function viewPath($view = 'tiers') {
        return !empty($view) ? sprintf('%s/html/%s.php', CODER_TIERS_DIR, $view) : '';
    }
    /**
     * 
     */
    public function viewTiers() {
        $this->__view('tiers');
    }


    /**
     * @param string $action
     * @return bool
     */
    private function __action( $action = 'default '){
        $call = sprintf('%sAction',$action);
        $input = CoderTiersInput::request();
        return method_exists($this, $call) ?
            $this->$call( $input ) :
                $this->errorAction($input);
    }
    /**
     * @param array $input
     * @return bool
     */
    private function errorAction( $input = array() ){
        var_dump($input);
        return $this->__view('error');
    }
    /**
     * @param array $input
     * @return bool
     */
    public function saveAction( $input = array()) {
        var_dump($input);
        return $this->defaultAction( array (
            //redirected
        ) );
    }
    /**
     * @param array $input
     * @return bool
     */
    public function defaultAction( $input = array() ){
        return $this->__view('tiers');
    }
    
    
    
    
    
    

    /** Admin UI: add menu */
    public static function registerMenu() {
        add_management_page(
                'Coder Tiers',
                'Coder Tiers',
                'manage_options',
                'coder-tiers',
                function () {
                    CoderTiersAdmin::run();
                }
        );
    }
    /**
     * @param string $context (default to tiers)
     * @return type
     */
    public static function run( $context = 'default' ) {
        if (!current_user_can('manage_options')) {
            return;
        }
        $controller = new CoderTiersAdmin();
        $controller->__action($context);
    }
    /**
     * @return array
     */
    private static function input( $input = 3 ){
        switch($input){
            case INPUT_POST:
                return filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: [];
            case INPUT_GET:
                return filter_input_array(INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: [];
            case INPUT_COOKIE:
                return filter_input_array(INPUT_COOKIE,FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: [];
            default:
                return array_merge(
                    filter_input_array(INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: [],
                    filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: []
            );
        }
    }
}
/**
 * 
 */
class CoderTiersInput{
    
    const POST = INPUT_POST;
    const GET = INPUT_GET;
    const REQUEST = 3;
    const SERVER = INPUT_SERVER;
    const COOKIE = INPUT_COOKIE;
   /**
    * @var int
    */
   private $_type = self::REQUEST;
   /**
    * @var String[]
    */
   private $_input = array();
   /**
    * @param String $type
    */
   private function __construct($type = self::REQUEST ) {
       $this->_type =  $type;
       $this->_input = $this->import();
   }
   /**
    * @param String $name
    * @return String
    */
   public function __get($name = '') {
       return $this->has($name) ? $this->input()[$name] : '';
   }
   /**
    * @param String $type
    * @return array
    */
   private function import($type = ''){
        switch($type){
            case self::SERVER:
                return filter_input_array(INPUT_SERVER, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            case self::POST:
                return filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: [];
            case self::GET:
                return filter_input_array(INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: [];
            case self::COOKIE:
                return filter_input_array(INPUT_COOKIE,FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: [];
            case self::REQUEST:
                return array_merge(
                    filter_input_array(INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: [],
                    filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: []
            );
            default:
                return array();
        }
   }
   /**
    * @return String[]
    */
   public function input() {
       return $this->_input;
   }
   /**
    * @param String $name
    * @return bool
    */
   public function has($name) {
       return array_key_exists($name, $this->input());
   }
   /**
    * @param String $name
    * @return int
    */
   public function value($name = '' ){
       return $this->has($name) ? intval($this->get($name)) : 0;
   }
   /**
    * @param String $name
    * @param String $separator
    * @return String[]
    */
   public function list( $name , $separator = '|'){
       return $this->has($name) ? explode($separator, $this->get($name)) : array();
   }
   /**
    * @param String $name
    * @return String
    */
   public static function cookie($name = ''){
       return  !empty($name) ? filter_input(INPUT_COOKIE, $name) ?? '' : '';
   }
   /**
    * @param String $name
    * @return mixed
    */
   public static function server($name = '' ){
       return !empty($name) ? filter_input(INPUT_SERVER, $name) ?? '' : '';
   }
   /**
    * @return \CoderTiersInput
    */
   public static function get() {
       return new CoderTiersInput(self::GET);
   }
   /**
    * @return \CoderTiersInput
    */
   public static function request(){
       return new CoderTiersInput(self::REQUEST);
   }
   /**
    * @return \CoderTiersInput
    */
   public static function post(){
       return new CoderTiersInput(self::POST);
   }
}

add_action('init', function () {
    // Bootstrap
    CoderTiers::instance();
    /// REQUEST ACCESS TO THE CURRENT SERVICE/RESOURCE
    add_filter('coder_acl', function( $tier = '' ){
        //IMPORT THE CURRENT LOADED ROLE, EMPTY BY DEFAULT
        $role = apply_filters('coder_tier', function($role = ''){
            //catch the session loaded tier by other plugins
            return $role;
        });
        return CoderTiers::instance()->tier($role)->can($tier);
    });
    //LIST ALL AVAILABLE TIERS DEFINED IN TIERS PLUGIN
    add_filter('coder_tiers', function(){
        return CoderTiers::instance()->tiers();
    }, 10, 0);

    if (is_admin()) {
        // Admin hooks
        add_action('admin_menu', function () {
            CoderTiersAdmin::registerMenu();
        });
        add_action('admin_post_coder_tiers_save', function () {
            CoderTiersAdmin::run('save');
        });
    }
});

// Activation hook
register_activation_hook(__FILE__, function(){
    CoderTiers::install();
});
register_deactivation_hook(__FILE__, function(){
    CoderTiers::uninstall();
});


