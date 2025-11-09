<?php namespace CODERS\Tiers;

defined('ABSPATH') or die;

add_action('admin_menu', function () {
    add_menu_page(
            __('Coder Tiers','coder_tiers'),
            __('Coder Tiers','coder_tiers'),
            'manage_options',
            'coder-tiers',
            function () { \CODERS\Tiers\Controller::run(); },
            'dashicons-screenoptions', 40
    );    
});
add_action('admin_post_coder_tiers_save', function () {
    \CODERS\Tiers\Controller::run('save');
});
add_action('admin_post_coder_tiers_create', function () {
    \CODERS\Tiers\Controller::run('create');
});

/**
 * 
 */
class Controller {
    /**
     * @var array
     */
    private static $_mailbox = array();
    
    /**
     * @return string
     */
    public static function mailbox(){
        return self::$_mailbox; 
    }
    /**
     * @param string $content
     * @param string $type
     */
    public static function notify($content = '' , $type = 'info'){
        self::$_mailbox[] = array('content' => $content , 'type' => $type );
    }


    /**
     * @return \CoderTiers
     */
    public static function content(){
        return \CoderTiers::instance();
    }
    /**
     * @param string $action
     * @return bool
     */
    private function action(){
        $input = CoderTiersInput::request();
        $action = $input->action;
        $call = sprintf('%sAction', strlen($action) ? $action : 'default' );
        return method_exists($this, $call) ?
            $this->$call( $input ) :
                $this->error($action);
    }    
    /**
     * @param string $action
     * @return bool
     */
    protected function error( $action = '' ){
        var_dump($action);
        self::notify(sprintf('Invalid action %s',$action), 'error');
        View::create('error')->view();
        return false;
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function saveAction( $input = array()) {
        var_dump($input);
        return $this->defaultAction( array (
            //redirected
        ) );
//        return true;
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function createAction( $input = array()){
        var_dump($input);
        return $this->defaultAction( array (
            //redirected
        ) );
    }

    /**
     * @param array $input
     * @return bool
     */
    protected function defaultAction( $input = array() ){

        View::create('main')->view('tiers');
        
        return true;
    }
    
    /**
     * @param string $context (default to tiers)
     * @return type
     */
    public static function run( $context = 'main' ) {
        if (!current_user_can('manage_options')) {
            return;
        }
        $controller = new Controller($context);
        $controller->action( );
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
       $this->_input = $this->import($this->_type);
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
     * @return \CODERS\Tiers\View
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
            case preg_match('/^list_/', $name):
                return $this->__list(
                        substr($name, 5),
                        isset($args[0]) ? $args[0]: '');
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
     * @param string $action
     * @return bool
     */
    private function __action( $action = 'default '){
        return $action;
    }
    /**
     * @param string $name
     * @param string $arg
     * @return array
     */
    private function __list($name , $arg = ''){
        $list = sprintf('list%s', ucfirst($name));
        return method_exists($this, $list) ? $this->$list($arg) : array();
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
     * @return bool
     */
    protected function hasTiers(){
        return count($this->listTiers()) > 0;
    }

    /**
     * @return Array[]
     */
    protected function listTiers() {
        $tiers = Controller::content()->tiers();
        return $tiers;
    }
    /**
     * @param string $tier
     * @return array
     */
    protected function listAvailable( $tier = '' ){
        $selected = array_keys($this->content()->tiers());
        $roles = $this->content()->roles($tier);
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
        return Controller::mailbox();
    }
    /**
     * @return \CODERS\Tiers\View
     */
    protected function viewMessages(){
        foreach( $this->listMessages() as $message ){
            printf('<div class="notice is-dismissible %s">%s</div>',
                    $message['type'],
                    $message['content']);
        }
        return $this;
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




