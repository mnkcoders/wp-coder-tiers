document.addEventListener('DOMContentLoaded',function(){

    CoderTiers.create('tier-list');

});


/**
 * Main Client Tier Manager class 
 */
class CoderTiers{
    /**
     * 
     * @param {String} container 
     * @returns {CoderTiers}
     */
    constructor( container = '' ){

        if( CoderTiers.__instance){
            return CoderTiers.__instance;
        }
        CoderTiers.__instance = this;

        this._view = new CoderBlueprint(container);
        this._client = new CoderClient( CoderTierApi || null );

        this.initialize();

        console.log(this);
    }
    /**
     * 
     */
    initialize(  ){
        this._tiers = [];
        this.fill();
    }
    /**
     * @returns {CoderTiers}
     */
    fill(){
        this.client().request( 'tiers' , response => {
            const tiers = response && response.tiers && response.tiers.map( data => new CoderTier(...Object.values(data))) || [];
            tiers.forEach( t => this.add(t ));
            this.view().refresh(tiers,true);
        });
        return this;
    }
    /**
     * UL list element to hold all tiers
     * @returns {CoderBlueprint}
     */
    view(){ return this._view; }
    /**
     * @returns {CoderClient}
     */
    client(){ return this._client; }
    /**
     * @returns {CoderTier[]}
     */
    tiers(){ return this._tiers; }
    /**
     * @param {CoderTier[]} tier 
     * @returns {CoderTiers}
     */
    add(tier = null ){
        if( tier instanceof CoderTier ){
            this.tiers().push(tier);
        }
        return this;
    }

    /**
     * AJAX update call here, append all tiers to be updated in a formfield and send
     * @param {*} tiers 
     * @returns {CoderTiers}
     */
    update( tiers = [] ){
        
        return this;
    }

    /**
     * @returns {CoderTiers}
     */
    static instance(){
        return CoderTiers.__instance || new CoderTiers();
    }
    /**
     * @param {String} content 
     * @returns {CoderTiers}
     */
    static create( content = ''){
        return new CoderTiers( content );
    }
}
/**
 * Tier Data to handle inner tier hierarchy operations (remove duplicates, remove looping dependencies)
 */
class CoderTier{
    /**
     * @param {String} tier 
     * @param {String[]} roles 
     */
    constructor( tier = '' , roles = []){
        this._tier = tier || '';
        this._roles = roles || [];
    }
    /**
     * @returns {String}
     */
    toString(){ return this._tier; }
    /**
     * @returns {String}
     */
    tier(){ return this._tier;}
    /**
     * @returns {CoderTier[]}
     */
    roles(){ return this._roles.map( role => new CoderTier(role)); }
    /**
     * Use this to append the ajax call content
     * @returns {Object}
     */
    tierdata(){
        return {
            tier: this._tier,
            roles: this._roles,
        };
    }
    /**
     * @returns {CoderTiers}
     */
    manager(){
        return CoderTiers.instance();
    }
}
/**
 * Handle server response here
 */
class CoderClient{
    /**
     * 
     */
    constructor( api = null ){
        //define required client ajax attributes
        this._url = api && api.url || ajaxurl || '';
        this._nonce = api && api.nonce || '';
        (this);
    }
    /**
     * @returns {String}
     */
    url(){ return this._url; }
    /**
     * @returns {String}
     */
    nonce(){ return this._nonce; }
    /**
     * 
     * @param {String} error 
     */
    error( error ){
        console.error("AJAX error:", error);
    }
    /**
     * @param {Object} data 
     * @returns {CoderClient}
     */
    ajax( data = {}, callback = null ){

        data.action = 'coder_tiers';
        data._nonce = this.nonce();

        fetch(this.url(), {
            method: 'POST',
            body: new URLSearchParams(data)
        })
        .then(r => r.json())
        .then(response => {
            console.log(response);
            callback && callback(response);
        })
        .catch(err => this.error( err ) );


        //implement ajax call and callback

        return this;
    }
    /**
     * @param {String} action 
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    request( action = '' , callback = null){
        //send ajax action

        callback && callback( this.fake());

        return this.ajax( {task:action}, response => {
            console.log(response);
        });
        //return this.ajax( {task:action},callback);
    }
    /**
     * @returns {Object[]}
     */
    fake(){
        return {tiers:[
            {tier:'copper'},
            {tier:'silver',roles:['copper']},
            {tier:'gold',roles:['silver']},
        ]};
    }

    /**
     * @todo implement server call and response
     * @param {CoderTier[]} tiers 
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    send( tiers = [] , callback = null ){

        return this.ajax({
            'tiers': tiers,
        }, callback );
    }
}
/**
 * 
 */
class CoderBlueprint{
    /**
     * @param {String} type 
     * @param {Object} attributes 
     */
    constructor( content = '' ){
        this._content = [...document.getElementsByClassName(content)][0] || null;
        this.initialize();
    }

    initialize(){
        //setup here all required events bound to the list element (content) if required
    }


    /**
     * @returns {Element}
     */
    content(){
        return this._content;
    }
    /**
     * Get the list's items to sync the view's contents
     * @returns {Element[]}
     */
    list(){ return this.content() && Array.from(this.content().children) || []; }
    /**
     * List all tiers in the list view
     * @returns {String[]}
     */
    tiers(){ return this.list().map( item => item.getAttribute('data-tier')); }
    /**
     * List a tier's roles in the list view
     * @param {String} tier 
     * @returns {String[]}
     */
    roles( tier = '' ){
        const item = this.list().find( item => item.getAttribute('data-tier') === tier ) || null;
        return item && item.children().map( t => t.getAttribute('data-role') || '').filter( t => !!t);
    }
    /**
     * @param {String} type
     * @param {Object} attributes 
     * @param {*} content 
     * @returns {Element}
     */
    html( type = '', attributes = null, content = null){
        const element = document.createElement(type);
        attributes instanceof Object && Object.keys(attributes).forEach( att => element.setAttribute(att,attributes[att]));
        if( type ){
            element.id = type;
        }
        if( content instanceof Element){
            element.appendChild(content);
        }
        else{
            element.innerHTML = content || '';
        }
        return element;
    }
    /**
     * @todo  implement drag-drop events on tiers and roles
     * @param {CoderTier} content
     * @returns {Boolean} 
     */
    add(content = null){
        if( content instanceof CoderTier){
            const item = this.html('li',{'class':'item','id':content.tier()});
            const tier = this.html('span',{'class':'tier button-primary','data-tier':content.tier()},content.tier());
            const roles = content.roles().map( role => this.html('span',{'class':'role button','data-role':role},role));
            const remove = this.html('a',{'class':'remove right','href':'','target':'_self'},this.html('span',{'class':'dashicons dashicons-remove'}));
            //add drag-drop events here
            
            item.appendChild(tier);
            roles.forEach( role => item.append(role));
            this.content().appendChild(item);
            item.appendChild(remove);
            return true;
        }
        return false;
    }
    /**
     * @param {String} tier 
     * @returns {Element}
     */
    get( tier = '' ){ return this.list().find( item => item.id === tier ) || null; }
    /**
     * @param {String} tier 
     * @returns {Boolean}
     */
    has( tier = '' ){ return !!this.get(tier); }
    /**
     * Main event to handle the tier list from server's response
     * @todo implement update (refresh) or add new to the list
     * @param {CoderTier[]} content 
     * @returns {CoderBlueprint}
     */
    refresh( content = [] ,clear = false){
        //fetch a tier name list
        const list = content.map( t => t.tier());
        //remove non existing tiers
        console.log(content,list);
        clear && this.clear();
        content.forEach( tier => {
            //get the tier's  content and rewrite it all
            if( this.has(tier.tier())){
                //update
            }
            else{
                //add
                this.add(tier);
            }
        });
        return this;
    }
    /**
     * @todo Implement remove
     * Remove all or selected items in the list
     * @param {String[]} tiers 
     * @returns {CoderBlueprint}
     */
    clear( tiers = []){
        if( tiers.length ){
            //remove all children in tiers list
            tiers.forEach( t => {
                //remove from UL content
            } );
        }
        else if(this.content()){
            //reset
            this.content().innerHTML = '';
        }
        return this;
    }
}



