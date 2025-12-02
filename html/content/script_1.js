
document.addEventListener('DOMContentLoaded',function(){

    CoderTiers.instance();

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
        this.initialize( container);
        this.fill();
    }
    /**
     * 
     */
    initialize( container = '' ){
        this._view = new CoderBlueprint(container);
        this._client = new CoderClient();
        this._tiers = [];
    }
    /**
     * @returns {CoderTiers}
     */
    fill(){
        this.client().request( 'tiers' , response => {
            const tiers = response && response.tiers && response.tiers.map( data => new CoderTier(...Object.values(data))) || [];
            this.view().refresh(tiers);
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
    constructor(){
        //define required client ajax attributes
    }
    /**
     * @param {Object} data 
     * @returns {CoderClient}
     */
    ajax( data = {}, callback = null ){

        data.action = ''; //add wordpress admin ajax action here


        //implement ajax call and callback

        return this;
    }

    request( action = '' , callback = null){
        //send ajax action

        return this.ajax( {task:action},callback);
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
        this._type = type || 'div';
        this._atts = attributes instanceof Object && attributes || {};

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
    list(){ return this.content() && this.content().children() || []; }
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
        element.innerHTML = content || '';
        return element;
    }
    /**
     * @todo  implement drag-drop events on tiers and roles
     * @param {CoderTier} content
     * @returns {Boolean} 
     */
    add(content = null){
        if( content instanceof CoderTier){
            const item = this.html('li',{'class':'tier container','id':content.tier()});
            const tier = this.html('span',{'class':'tier','data-tier':content.tier()},content.tier());
            const roles = content.roles().forEach( role => this.html('span',{'class':'role','data-role':role},role));
            
            //add drag-drop events here
            
            item.appendChild(tier);
            item.append(roles);
            this.content().appendChild(item);
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
        clear && this.clear(this.tiers().filter( t => !list.includes(t) ));
        content.forEach( tier => {
            //get the tier's  content and rewrite it all
            if( this.has(tier.tier())){
                //update
            }
            else{
                //add
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
        else{
            //reset
            this.content().innerHTML = '';
        }
        return this;
    }
}



