<?php namespace CODERS\Tiers\Admin;

defined('ABSPATH') or die;

add_action('admin_menu', function () {
    add_menu_page(
            __('Coder Tiers','coder_tiers'),
            __('Coder Tiers','coder_tiers'),
            'manage_options',
            'coder-tiers',
            function () { \CODERS\Tiers\Admin\Controller::run(); },
            //function () { \CODERS\Tiers\Admin\Controller::run(); },
            'dashicons-admin-network', 40
    );    
});
add_action('admin_post_coder_tiers_save', function () {
    \CODERS\Tiers\Admin\Controller::run('save');
});
add_action('admin_post_coder_tiers_create', function () {
    \CODERS\Tiers\Admin\Controller::run('create');
});
add_action('admin_enqueue_scripts',function(){
    $style = sprintf('%shtml/content/style.css', CODER_TIERS_URL);
    $style_path = sprintf('%shtml/content/style.css', CODER_TIERS_DIR);
    
    $script = sprintf('%shtml/content/script.js', CODER_TIERS_URL);
    $script_path = sprintf('%shtml/content/script.js', CODER_TIERS_DIR);
    // Register and enqueue CSS
    wp_enqueue_style('tiers-admin-style', $style, [], filemtime($style_path));
    // Register and enqueue JS
    wp_enqueue_script('tiers-admin-script', $script, ['jquery'], filemtime($script_path), true);

    // Optional: Pass variables to JS
    wp_localize_script('tiers-admin-script', 'CoderTierApi', [
        'url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('coder_nonce')
    ]);
});

add_action('wp_ajax_coder_tiers', function(){
    $controller = \CODERS\Tiers\Admin\AjaxController::run();
    wp_send_json($controller->response());
});
// if non-logged-in allowed:
add_action('wp_ajax_nopriv_coder_tiers', function(){
    $controller = \CODERS\Tiers\Admin\AjaxController::run();
    wp_send_json($controller->response());
});

/**
 * 
 */
class Controller {
    
    const POST = INPUT_POST;
    const GET = INPUT_GET;
    const REQUEST = 3;
    const SERVER = INPUT_SERVER;
    const COOKIE = INPUT_COOKIE;
    
    /**
     * @var array
     */
    private static $_log = array();
    /**
     * @var array
     */
    private $_input = array();
    /**
     * @param array $input
     */
    protected function __construct( array $input = array() ) {
        $this->_input = $input;
    }
    /**
     * @param String $name
     * @return String
     */
    public function __get($name) {
        return $this->input()[$name] ?? '';
    }
    /**
     * @return array
     */
    protected function input(){
        return $this->_input;
    }
    /**
     * @return String
     */
    protected function task(){
        return $this->input()['action'] ?? 'default';
    }

    /**
     * @return string
     */
    public static function log(){
        return self::$_log; 
    }
    /**
     * @param string $content
     * @param string $type
     */
    public static function notify($content = '' , $type = 'info'){
        self::$_log[] = array('content' => $content , 'type' => $type );
    }

    /**
     * @return \CODERS\Tiers\CoderTiers
     */
    public static function manager(){
        return \CODERS\Tiers\CoderTiers::instance();
    }
    
    
    /**
     * @param string $action
     * @return bool
     */
    protected function action(){
        $action = $this->task();
        $call = sprintf('%sAction', $action );
        return method_exists($this, $call) ?
            $this->$call( ) :
                $this->error($action);
    }    
    /**
     * @param string $action
     * @return bool
     */
    protected function error( $action = '' ){
        self::notify(sprintf('Invalid action %s',$action), 'error');
        View::create('error')->view();
        return false;
    }
    /**
     * @return bool
     */
    protected function saveAction( $input = array()) {
        var_dump($input);
        return $this->defaultAction( );
    }
    /**
     * @return bool
     */
    protected function createAction( $input = array()){
        var_dump($input);
        return $this->defaultAction();
    }

    /**
     * @return bool
     */
    protected function defaultAction( ){
        View::create('main')->view('tiers');
        return true;
    }
    /**
     * @return null|\CODERS\Tiers\Admin\Controller
     */
    public static function run(  ) {
        if (!current_user_can('manage_options')) {
            return null;
        }
        $controller = new Controller( self::request() );
        $controller->action( );
        return $controller;
    }
    
   /**
    * @param Int $type
    * @return array
    */
   protected static function request($type = self::REQUEST ){
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
}
/**
 * 
 */
class AjaxController extends Controller{
    /**
     * @var array
     */
    private $_response = array();
    /**
     * @param array $input
     */
    protected function __construct(array $input = array()) {
       $input['action'] = $input['task'] ?? 'default';
       unset($input['task']);
       parent::__construct($input);
    }
    /**
     * @return bool
     */
    protected function action(): bool {
        $result = parent::action();
        $this->set($this->task(),$result);
        $this->set('message',self::log());
        return $result;
    }

    /**
     * @return Array
     */
    public function response() { return $this->_response; }
    /**
     * @param string $att
     * @param string $value
     * @return \CODERS\Tiers\Admin\AjaxController
     */
    private function set($att = '' , $value = ''){
        if(strlen($att)){
            $this->_response[$att] = $value;
        }
        return $this;
    }
    /**
     * @param array $data
     * @return \CODERS\Tiers\Admin\AjaxController
     */
    private function fill( array $data = array()) {
        $this->_response = $data;
        return $this;
    }
    /**
     * 
     * @return \CODERS\Tiers\Admin\AjaxController
     */
    public function send() {
        wp_send_json($this->response());
        return $this;
    }
    /**
     * 
     */
    protected function defaultAction() {
        return $this->tiersAction();
    }
    /**
     * @return bool
     */
    protected function listAction() {
        $tiers = array();
        foreach ( self::manager()->tiers(true) as $tier ){
            $tiers[$tier->tier()] = $tier->roles();
        }
        $this->fill(array('tiers'=>$tiers));
        return true;
    }

    /**
     * @return \CODERS\Tiers\Admin\AjaxController
     */
    public static function run() {
        $controller = new AjaxController(self::request(self::POST));
        $controller->action();
        return $controller;
    }
}

/**
 * 
 */
class View{
    /**
     * @var string
     */
    private $_context = '';
    
    /**
     * @var array
     */
    private $_attributes = array(
        //define controller-view attributes here
    );
    
    /**
     * @param string $context
     */
    protected function __construct( $context = 'main' ) {
        $this->_context = $context;
    }
    /**
     * @param string $context
     * @return \CODERS\Tiers\Admin\View
     */
    public static function create( $context = '' ){
        return new View($context);
    }
    /**
     * @return string
     */
    public function context(){
        return $this->_context;
    }

    /**
     * @param String $view
     * @return String
     */
    private function path($view = '') {
        return !empty($view) ? sprintf('%s/html/%s.php', CODER_TIERS_DIR, $view) : '';
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return $this->$name();
    }
    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name , $arguments ) {
        $args = is_array($arguments) ? $arguments : array();
        switch(true){
            case preg_match('/^get_/', $name):
                $get = sprintf('get%s', ucfirst(substr($name, 4)));
                return method_exists($this, $get) ? $this->$get() : '';
            case preg_match('/^list_/', $name):
                $list = sprintf('list%s', ucfirst(substr($name,5)));
                return method_exists($this, $list) ? $this->$list(...$args) : array();
            case preg_match('/^is_/', $name):
                $is = sprintf('is%s', ucfirst(substr($name, 3)));
                return method_exists($this, $is) ? $this->$is(...$args) : false;
            case preg_match('/^has_/', $name):
                $has = sprintf('has%s', ucfirst(substr($name, 4)));
                return method_exists($this, $has) ? $this->$has(...$args) : false;
            case preg_match('/^show_/', $name):
                $show = $this->path(sprintf('templates/%s',substr($name, 5)) );
                if(file_exists($show)) {
                    require $show;
                    printf('<!-- %s -->',$name);
                    return true;
                }
                return false;
        }
        return array_key_exists($name,$this->_attributes) ? $this->_attributes[$name] : '';
    }
    /**
     * @return String
     */
    protected function getFormurl(){
        return esc_url(admin_url('admin-post.php'));
    }
    /**
     * @return bool
     */
    protected function hasTiers(){
        return count($this->listTiers()) > 0;
    }

    /**
     * @return Array[]
     */
    protected function listTiers() {
        $tiers = Controller::manager()->tiers();
        return $tiers;
    }
    /**
     * @param string $tier
     * @return array
     */
    protected function listAvailable( $tier = '' ){
        $manager = Controller::manager();
        $selected = array_keys($manager->tiers());
        $roles = $manager->roles($tier);
        $available = array();
        foreach( $selected as $t ){
            if( $tier !== $t && !in_array($t, $roles)){
                $available[] = $t;
            }
        }
        return $available;
    }

    /**
     * @return array
     */
    protected function listMessages(){
        return Controller::log();
    }

    /**
     * @param string $name
     * @return bool Description
     */
    public function view($name = ''){
        $view = $this->path( strlen($name ) ? $name : $this->context());
        if(!empty($view) && file_exists($view)){
            $this->viewMessages();
            require $view;
            return true;
        }
        printf('<!-- INVALID VIEW %s -->',$name);
        return false;
    }
}




