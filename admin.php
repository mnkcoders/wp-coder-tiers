<?php namespace CODERS\Tiers\Admin;

defined('ABSPATH') or die;

add_action('admin_menu', function () {
    add_menu_page(
            __('Coder Tiers','coder_tiers'),
            __('Coder Tiers','coder_tiers'),
            'manage_options',
            'coder-tiers',
            function () { \CODERS\Tiers\Admin\Controller::redirect(); },
            'dashicons-admin-network', 40
    );    
});
add_action('admin_post_coder_tiers', function () {
    \CODERS\Tiers\Admin\Controller::redirect(INPUT_POST);
});

add_action('wp_ajax_coder_tiers', function(){
    $request = \CODERS\Tiers\Admin\AjaxController::redirect(4);
    wp_send_json($request->response());
});
// if non-logged-in allowed:
add_action('wp_ajax_nopriv_coder_tiers', function(){
    $request = \CODERS\Tiers\Admin\Controller::redirect(4);
    wp_send_json($request->response());
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


/**
 * 
 */
class Controller {
    
    const POST = INPUT_POST;
    const GET = INPUT_GET;
    const COOKIE = INPUT_COOKIE;
    const REQUEST = 3;
    const AJAX = 4;
    const SERVER = INPUT_SERVER;
    
    /**
     * @var array
     */
    private static $_log = array();
    /**
     * @var array
     */
    private $_content = array();
    /**
     * @var array
     */
    private $_response = array();
    
    /**
     * @param array $input
     */
    protected function __construct( array $input = array() ) {
        $this->_content = $input;
    }
    /**
     * @param String $name
     * @return String
     */
    public function __get($name) {
        return $this->content()[$name] ?? '';
    }
    /**
     * @return String
     */
    public function type(){
        $type = explode('\\',get_called_class());
        return $type[count($type)-1];
    }

    /**
     * @return array
     */
    private function content(){
        return $this->_content;
    }
    /**
     * @return String
     */
    protected function action(){
        return $this->content()['action'] ?? 'main';
    }

    /**
     * @return Array
     */
    public function response() { return $this->_response; }
    
    /**
     * @param string $att
     * @param string $value
     * @return \CODERS\Tiers\Admin\Controller
     */
    protected function put($att = '' , $value = ''){
        if(strlen($att)){
            $this->_response[$att] = $value;
        }
        return $this;
    }
    /**
     * @param array $data
     * @return \CODERS\Tiers\Admin\Controller
     */
    protected function fill( array $data = array()) {
        foreach($data as $var => $val ){
            $this->_response[$var] = $val;
        }
        return $this;
    }
    /**
     * @param string $context main as default
     * @return \CODERS\Tiers\Admin\View
     */
    protected function layout( $context = 'main' ){
        return View::create( strlen($context) ? $context : $this->action());
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
     * @return \CODERS\Tiers\CoderTiers
     */
    protected function data(){
        return self::manager();
    }


    /**
     * @param string $action
     * @return \CODERS\Tiers\Admin\Controller
     */
    protected function run(){
        $action = $this->action();
        $call = sprintf('%sAction', $action );
        $this->put('_type',$this->type())->put('_action',$action);
        return $this->put('_response',method_exists($this, $call) ?
            $this->$call( ) :
                $this->error($action));
    }    
    /**
     * @return \CODERS\Tiers\Admin\Controller
     */
    protected function error( ){
        self::notify(sprintf('Invalid action <strong>[ %s ]</strong>',$this->action()), 'error');
        $this->layout()->view('empty');
        return $this;
    }
    /**
     * @return boolean
     */
    protected function mainAction(){
        //implement in subclasses ;)
        self::notify('Implement Controller subclass ;)');
        return true;
    }

    /**
     * @param String $context
     * @param array $input
     * @return \CODERS\Tiers\Admin\Controller
     */
    private static final function create( $context = '' ,array $input = array()){
        $class = sprintf('\CODERS\Tiers\Admin\%sController', ucfirst($context));
        return class_exists($class) && is_subclass_of($class, self::class,true) ?
                new $class( $input ) :
                    new Controller($input);
    }

    /**
     * @param int $type
     * @return \CODERS\Tiers\Admin\Controller
     */
    public static final function redirect( $type = self::REQUEST ) {
        if (!current_user_can('manage_options')) {
            return null;
        }
        switch($type){
            case self::AJAX:
                return self::create('ajax',self::input(self::POST,true))->run();
            case self::POST:
                return self::create('form',self::input($type,true))->run();
            default:
                return self::create('admin',self::input($type))->run( );
        }
    }
    
   /**
    * @param Int $type POST,GET,REQUEST,SERVER,COOKIE
    * @param bool $maskaction parse task to action
    * @return array
    */
   protected static function input($type = self::REQUEST , $maskaction = false ){
        switch($type){
            case self::SERVER:
                return filter_input_array(INPUT_SERVER, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            case self::POST:
                $input = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: [];
                if( $maskaction ){
                    $input['action'] = $input['task'] ?? 'main';
                    unset($input['task']);
                }
                return $input;
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
 * Default view controller
 */
class AdminController extends Controller{
    /**
     * @return bool
     */
    protected function saveAction( ) {
        return $this->mainAction( );
    }
    /**
     * @return bool
     */
    protected function createAction(){
        return $this->mainAction();
    }

    /**
     * @return bool
     */
    protected function mainAction( ){
        $this->layout('main')->view('tiers');
        return true;
    }    
}
/**
 * 
 */
class FormController extends Controller{

}
/**
 * 
 */
class AjaxController extends Controller{

    /**
     * @return \CODERS\Tiers\Admin\AjaxController
     */
    protected function run() {
        return parent::run()->put('message',self::log());
    }
    /**
     * @return boolean
     */
    protected function removeAction(){
        $tier = $this->tier;
        $role = $this->role;
        //$this->data()->
        $this->put('role',$role)->put('tier',$tier);
        $this->notify(strlen($role) ?
                sprintf('%s.%s removed',$tier,$role) :
                sprintf('%s removed!',$tier));
        return true;
    }

    /**
     * @return boolean
     */
    protected function createAction(){
        $tier = $this->tier;
        //$this->data()->
        $this->notify(sprintf('%s created!',$tier));
        return true;
    }

    /**
     * @return boolean
     */
    protected function saveAction(){
        $tier = $this->tier;
        $roles = $this->data()->roles($tier);
        $this->notify(sprintf('%s saved! (%s)',$tier, implode(',', $roles)));
        return true;
    }
    /**
     * @return bool
     */
    protected function mainAction() {
        return $this->listAction();
    }
    /**
     * @return bool
     */
    protected function defaultAction() {
        return $this->listAction();
    }
    /**
     * @return bool
     */
    protected function listAction() {
        $tiers = array();
        foreach ( $this->data()->tiers(true) as $tier ){
            $tiers[$tier->tier()] = $tier->roles();
        }
        $this->fill(array('tiers'=>$tiers));
        return true;
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




