document.addEventListener('DOMContentLoaded', function () {

    CoderTiers.create('tier-list');

});


/**
 * Main Client Tier Manager class 
 */
class CoderTiers {
    /**
     * 
     * @param {String} container 
     * @returns {CoderTiers}
     */
    constructor(container = '') {

        if (CoderTiers.__instance) {
            return CoderTiers.__instance;
        }
        CoderTiers.__instance = this;
        this.initialize(container);
    }
    /**
     * @param {String} container
     */
    initialize(container) {
        this._tiers = [];
        this._view = new CoderView(container);
        this._client = new CoderClient(CoderTierApi || null);
        this.populate();
    }
    /**
     * @returns {CoderTiers}
     */
    populate() {
        this.client().tiers( response => {
            const data = response.tiers || {};
            this._tiers = Object.keys(data).map(tier => new CoderTier(tier, data[tier])) || [];
            this.view().refresh(this._tiers, true);
        });
        return this;
    }
    /**
     * UL list element to hold all tiers
     * @returns {CoderView}
     */
    view() { return this._view; }
    /**
     * @returns {CoderClient}
     */
    client() { return this._client; }
    /**
     * @returns {Object[]}
     */
    log() { return this.client().log(); }
    /**
     * @returns {CoderTier[]}
     */
    tiers() { return this._tiers; }
    /**
     * @param {CoderTier[]} tier 
     * @returns {CoderTiers}
     */
    add(tier = null) {
        if (tier instanceof CoderTier) {
            this.tiers().push(tier);
        }
        return this;
    }

    /**
     * AJAX update call here, append all tiers to be updated in a formfield and send
     * @param {*} tiers 
     * @returns {CoderTiers}
     */
    update(tiers = []) {

        return this;
    }

    /**
     * @returns {CoderClient}
     */
    static server(){
        return this.instance().client();
    }
    /**
     * @returns {CoderTiers}
     */
    static instance() {
        return CoderTiers.__instance || new CoderTiers();
    }
    /**
     * @param {String} content 
     * @returns {CoderTiers}
     */
    static create(content = '') {
        return new CoderTiers(content);
    }
}
/**
 * Tier Data to handle inner tier hierarchy operations (remove duplicates, remove looping dependencies)
 */
class CoderTier {
    /**
     * @param {String} tier 
     * @param {String[]} roles 
     */
    constructor(tier = '', roles = []) {
        this._tier = tier || '';
        this._roles = roles || [];
    }
    /**
     * @returns {String}
     */
    toString() { return this._tier; }
    /**
     * @returns {String}
     */
    name() { return this._tier; }
    /**
     * @returns {CoderTier[]}
     */
    roles() { return this._roles.map(role => new CoderTier(role)); }
    /**
     * Use this to append the ajax call content
     * @returns {Object}
     */
    tierdata() {
        return {
            tier: this._tier,
            roles: this._roles,
        };
    }
    /**
     * @returns {CoderTiers}
     */
    manager() {
        return CoderTiers.instance();
    }
}
/**
 * Handle server response here
 */
class CoderClient {
    /**
     * 
     */
    constructor(api = null) {
        //define required client ajax attributes
        this._url = api && api.url || ajaxurl || '';
        this._nonce = api && api.nonce || '';
        this._response = {};
    }
    /**
     * @param {Object} content 
     * @returns {CoderClient}
     */
    capture( content = null ){
        this._response = content || {};
        console.log('RESPONSE',this._response);
        return this;
    }
    /**
     * @returns {Boolean}
     */
    success(){ return !!this.response()._response; }
    /**
     * @returns {Boolean}
     */
    type(){ return this.response()._type || ''; }
    /**
     * @returns {Object[]}
     */
    log() { return Array.isArray(this.response().message) && this.response().message || []; }
    /**
     * @returns {Object}
     */
    response() { return this._response; }
    /**
     * @returns {String}
     */
    url() { return this._url; }
    /**
     * @returns {String}
     */
    nonce() { return this._nonce; }
    /**
     * 
     * @param {String} error 
     */
    error(error) {
        console.error("AJAX error:", error);
    }
    /**
     * @param {String} task
     * @param {Object} data 
     * @returns {CoderClient}
     */
    request(data = {}, callback = null) {

        data.action = 'coder_tiers';
        data.nonce = this.nonce();
        if(!data.task){
            data.task = '';
        }
        console.log('REQUEST',data);
        fetch(this.url(), {
            method: 'POST',
            body: new URLSearchParams(data)
        })
        .then(r => r.json())
            .then(response => {
                this.capture(response);
                callback && callback(this.response(),this.success());
            })
        .catch(err => this.error(err));


        //implement ajax call and callback

        return this;
    }
    /**
     * @returns {Object[]}
     */
    fake() {
        return {
            tiers: [
                { tier: 'copper' },
                { tier: 'silver', roles: ['copper'] },
                { tier: 'gold', roles: ['silver'] },
            ]
        };
    }


    /**
     * @todo implement server call and response
     * @param {CoderTier[]} tiers 
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    save(tiers = [], callback = null) {
        return this.request({'task':'save','tiers': tiers}, callback);
    }
    /**
     * @param {String} tier 
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    create( tier ='' , callback ){
        return this.request({'task':'create','tier':tier},callback);
    }
    /**
     * @param {String} role tier to attack to a parent
     * @param {String} tier parent tier
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    set( role = '' , tier = '' , callback ){
        return this.request({'task':'set','tier':tier,'role':role},callback);
    }
    /**
     * @param {String} tier 
     * @param {String} role
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    remove( tier = '' , role = '',callback = null){
        return this.request({'task':'remove', 'tier': tier, 'role': role },callback);
    }
    /**
     * @param {String} tier 
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    roles( tier = '' , callback = null ){
        return this.request({'task':'roles','tier':tier},callback);
    }
    /**
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    tiers( callback = null ){
        return this.request({'task':'list'},callback);
    }
}
/**
 * 
 */
class CoderView {
    /**
     * @param {String} type 
     * @param {Object} attributes 
     */
    constructor(content = '') {
        this._content = [...document.getElementsByClassName(content)][0] || null;
        this.initialize();
    }

    initialize() {
        //setup here all required events bound to the list element (content) if required
    }


    /**
     * @returns {Element}
     */
    content() {
        return this._content;
    }
    /**
     * Get the list's items to sync the view's contents
     * @returns {Element[]}
     */
    list() { return this.content() && Array.from(this.content().children) || []; }
    /**
     * List all tiers in the list view
     * @returns {String[]}
     */
    tiers() { return this.list().map(item => item.getAttribute('data-tier')); }
    /**
     * @param {String} tier 
     * @returns {Element}
     */
    tier(tier = '' ){
        return tier && this.list().find( item => item.getAttribute('data-tier') === tier) || null;
    }
    /**
     * List a tier's roles in the list view
     * @param {String} tier 
     * @returns {String[]}
     */
    roles(tier = '') {
        const item = this.list().find(item => item.getAttribute('data-tier') === tier) || null;
        return item && item.children().map(t => t.getAttribute('data-role') || '').filter(t => !!t);
    }
    /**
     * @param {String} tier 
     * @returns {Element[]}
     */
    roles( tier = ''){
        const item = this.tier(tier);
        return item &&
            Array.from(item.getElementsByClassName('role'))
            .filter( item => !!item.getAttribute('data-role')) || [];
    }
    /**
     * @param {String} tier 
     * @param {String} role 
     * @returns {CoderClient}
     */
    remove( tier = ''){
        const tierdata = tier.split('.');
        if( tierdata.length > 1 ){
            this.roles(tierdata[0])
                .filter( item => item.getAttribute('data-role') === tier)
                .forEach( item => item.remove() );
        }
        else{
            const t = this.tier(tierdata[0]);
            t && t.remove();
        }
        return this;
    }


    /**
     * @param {String} type
     * @param {Object} attributes 
     * @param {*} content 
     * @returns {Element}
     */
    html(type = '', attributes = null, content = null) {
        const element = document.createElement(type);
        attributes instanceof Object && Object.keys(attributes).forEach(att => element.setAttribute(att, attributes[att]));
        if (content instanceof Element) {
            element.appendChild(content);
        }
        else {
            element.innerHTML = content || '';
        }
        return element;
    }
    /**
     * @param {String} action 
     * @param {String} target 
     * @returns {String}
     */
    link(action = '', target = '') {
        const url = CoderTiers.instance().client().url();
        return `${url}&action=${action || 'default'}&target=${target}`;
    }
    /**
     * @param {String} tier 
     * @returns {Element}
     */
    linkRemove( tier = ''){
            const link = this.link('remove', tier);
            const icon = this.html('span', { 'class': 'dashicons dashicons-remove' });
            return this.html('a',
                { 'class': 'remove right', 'href': link, 'target': '_self' ,'data-tier': tier },
                icon);
    }
    /**
     * @param {String} tier 
     * @returns {Element}
     */
    buttonRemove( tier = '' ){
            const icon = this.html('span', { 'class': 'dashicons dashicons-remove' });
            const button = this.html('span',
                { 'class': 'remove right','data-tier': tier }, icon);
            const view = this;
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const tier = (this.getAttribute('data-tier') || '').toString();
                CoderTiers.server().remove(tier,'', r => {
                    console.log(r);
                    view.remove(r.tier || '');
                });
                return true;
            });

            return button;
    }
    /**
     * @param {String} tier 
     * @returns {Element}
     */
    tierbox(tier = ''){
        const content = this.html('span', { 'class': 'tier button-primary', 'data-tier': tier }, tier);
        //add drag drop events
        return content;
    }
    /**
     * @param {String} role 
     * @returns {Element}
     */
    rolebox(role = '',tier = ''){
        const roledata = tier && `${tier}.${role}` || role;
        const content = this.html('span', { 'class': 'role button', 'data-role': roledata }, role);
        const view = this;
        content.addEventListener('click', function(e) {
            e.preventDefault();
            const content = (this.getAttribute('data-role') || '').toString();
            const td = content.split('.');
            CoderTiers.server().remove(td[0],td[1] || '', r =>{
                r.tier && r.role && view.remove( tier + '.' + role );
            });
            return true;
        });
        return content
    }
    /**
     * @param {String} role 
     * @returns {Element}
     */
    itembox( role = ''){
        return this.html('li', { 'class': 'item', 'data-tier': role });
    }
    /**
     * @todo  implement drag-drop events on tiers and roles
     * @param {CoderTier} content
     * @returns {Boolean} 
     */
    add(content = null) {
        if (content instanceof CoderTier) {
            const name = content.name();
            const item = this.itembox(name);
            const roles = content.roles().map(role => this.rolebox(role,name));

            item.appendChild(this.tierbox(name));
            roles.forEach(role => item.append(role));
            this.content().appendChild(item);
            item.appendChild(this.buttonRemove(name));
            return true;
        }
        return false;
    }
    /**
     * @param {String} tier 
     * @returns {Boolean}
     */
    has(tier = '') { return !!this.tier(tier); }
    /**
     * Main event to handle the tier list from server's response
     * @todo implement update (refresh) or add new to the list
     * @param {CoderTier[]} content 
     * @returns {CoderView}
     */
    refresh(content = [], clear = false) {
        //remove non existing tiers
        clear && this.clear();
        content.forEach(tier => {
            //get the tier's  content and rewrite it all
            if (this.has(tier.name())) {
                //update
                this.roles(tier.name()).forEach( e => e.remove() );
                tier.roles()
                    .map(role => role.name())
                    .map( role => this.rolebox(role,tier.name()))
                    .forEach( item => container.appendChild(item));
            }
            else {
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
     * @returns {CoderView}
     */
    clear(tiers = []) {
        if (tiers.length) {
            //remove all children in tiers list
            tiers.map( tier => this.tier(tier)).forEach( t => t && t.remove());
        }
        else if (this.content()) {
            //reset
            this.tiers().forEach( t => t.remove());
        }
        return this;
    }
}



