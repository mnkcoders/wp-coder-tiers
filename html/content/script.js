document.addEventListener('DOMContentLoaded', function () {

    CoderTiers.create('tier-list', 'coder-tiers-log');

});


/**
 * Main Client Tier Manager class 
 */
class CoderTiers {
    /**
     * 
     * @param {String} container 
     * @param {String} log
     * @returns {CoderTiers}
     */
    constructor(container = '', log = '') {

        if (CoderTiers.__instance) {
            return CoderTiers.__instance;
        }
        CoderTiers.__instance = this;
        this.initialize(container, log);
        //console.log(this);
    }
    /**
     * @param {String} container
     */
    initialize(container = '', log = '') {
        this._tiers = [];
        this._view = new ListView(container, log);
        this._client = new CoderClient(CoderTierApi || null);
        this.populate();
    }
    /**
     * @returns {CoderTiers}
     */
    populate() {
        this.client().tiers(response => {
            const data = response.tiers || {};
            this._tiers = Object.keys(data).map(tier => new CoderTier(tier, data[tier])) || [];
            this.view().refresh(this._tiers, true);
        });
        return this;
    }
    /**
     * UL list element to hold all tiers
     * @returns {ListView}
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
    static server() {
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
    static create(content = '', log = '') {
        return new CoderTiers(content, log);
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
    capture(content = null) {
        this._response = content || {};
        const view = CoderTiers.instance().view();
        this.log().forEach(msg => view.log(msg.content || '', msg.type || 'info'));
        console.log('RESPONSE', this._response);
        return this;
    }
    /**
     * @returns {Boolean}
     */
    success() { return !!this.response()._response; }
    /**
     * @returns {Boolean}
     */
    type() { return this.response()._type || ''; }
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
        if (!data.task) {
            data.task = '';
        }
        console.log('REQUEST', data);
        fetch(this.url(), {
            method: 'POST',
            body: new URLSearchParams(data)
        })
            .then(r => r.json())
            .then(response => {
                this.capture(response);
                callback && callback(this.response(), this.success());
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
     * @param {String} tier 
     * @param {String} move 
     * @returns {CoderClient}
     */
    sort( tier = '' , move = 'up') {
        return this.request({ 'task': 'sort' ,'tier': tier, 'move': move || 'up' });
    }

    /**
     * @todo implement server call and response
     * @param {CoderTier[]} tiers 
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    save(tiers = [], callback = null) {
        return this.request({ 'task': 'save', 'tiers': tiers }, callback);
    }
    /**
     * @param {String} tier 
     * @param {String} role 
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    add(tier, role, callback = null) {
        return this.request({ 'task': 'add', 'tier': tier, 'role': role }, callback);
    }
    /**
     * @param {String} tier 
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    create(tier = '', callback) {
        return this.request({ 'task': 'create', 'tier': tier }, callback);
    }
    /**
     * @param {String} role tier to attack to a parent
     * @param {String} tier parent tier
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    set(role = '', tier = '', callback) {
        return this.request({ 'task': 'set', 'tier': tier, 'role': role }, callback);
    }
    /**
     * @param {String} tier 
     * @param {String} role
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    remove(tier = '', role = '', callback = null) {
        return this.request({ 'task': 'remove', 'tier': tier, 'role': role }, callback);
    }
    /**
     * @param {String} tier 
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    roles(tier = '', callback = null) {
        return this.request({ 'task': 'roles', 'tier': tier }, callback);
    }
    /**
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    tiers(callback = null) {
        return this.request({ 'task': 'list' }, callback);
    }
}

/**
 * Base View class
 */
class CoderView {
    /**
     * 
     */
    constructor() {
        this.initialize();
    }
    /**
     * 
     */
    initialize() {
        //override
    }
    /**
     * @returns {Element}
     */
    render() {
        //override
        return this.html('div', { 'class': 'empty' }, '');
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
    ___link(action = '', target = '') {
        const url = CoderTiers.instance().client().url();
        return `${url}&action=${action || 'default'}&target=${target}`;
    }
    /**
     * @param {Object} request
     * @param {String|Element} content 
     * @param {String} className 
     * @param {String} target _self|_blank
     * @returns {Element}
     */
    link(request = {}, content, className = '', target = '_self') {
        const base = CoderTiers.instance().client().url();
        const data = Object.keys(request)
            .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(request[key])}`);
        const url = data.length ? `${base}&${data.join('&')}` : base;
        return this.html('a', { 'class': className, 'href': url, 'target': target }, content || '');
    }
    /**
     * @param {String} action 
     * @param {Element|String} content 
     * @param {String} className 
     * @returns {Element}
     */
    action(action = '', content, className = '') {
        return this.link({ action: action }, content, className, '_self');
    }
}


/**
 * 
 */
class ListView extends CoderView {
    /**
     * @param {String} content
     * @param {String} log
     */
    constructor(content = '', log = '') {
        super();
        this._content = [...document.getElementsByClassName(content)][0] || null;
        this._page = [...document.getElementsByClassName(log)][0] || null;
    }

    /**
     * @param {String} message 
     * @param {String} type 
     * @returns {ListView}
     */
    log(message = '', type = 'info') {
        const frame = this.page();
        if (frame) {
            const content = this.html('div', { 'class': `notice is-dismissible ${type}` }, message);
            frame.prepend(content);
            setTimeout(() => content.remove(), 2000);
        }
        return this;
    }

    page() { return this._page; }
    /**
     * @returns {Element}
     */
    content() { return this._content; }
    /**
     * @returns {Boolean}
     */
    ready() { return this.content() !== null; }
    /**
     * Get the list's items to sync the view's contents
     * @returns {Element[]}
     */
    items() { return this.content() && Array.from(this.content().children) || []; }
    /**
     * @param {String} tier 
     * @returns {Element}
     */
    get(tier = '') {
        return tier && this.items().find(item => item.getAttribute('data-tier') === tier) || null;
    }
    /**
     * @param {String} tier 
     * @param {String} direction 
     */
    move(tier = '', direction = 'up') {
        //
    }
    /**
     * List a tier's roles in the list view
     * @param {String} tier 
     * @returns {String[]}
     */
    /*roles(tier = '') {
        const item = this.list().find(item => item.getAttribute('data-tier') === tier) || null;
        return item && item.children().map(t => t.getAttribute('data-role') || '').filter(t => !!t);
    }*/
    /**
     * List all tiers in the list view
     * @returns {String[]}
     */
    tiers() { return this.items().map(item => item.getAttribute('data-tier')); }
    /**
     * @param {String} tier 
     * @returns {Element[]}
     */
    roles(tier = '') {
        const item = this.get(tier);
        return item &&
            Array.from(item.getElementsByClassName('role'))
                .filter(item => !!item.getAttribute('data-role')) || [];
    }
    /**
     * @param {String} tier 
     * @param {String} role 
     * @returns {CoderClient}
     */
    remove(tier = '') {
        const tierdata = tier.split('.');
        if (tierdata.length > 1) {
            this.roles(tierdata[0])
                .filter(item => item.getAttribute('data-role') === tier)
                .forEach(item => item.remove());
        }
        else {
            const t = this.get(tierdata[0]);
            t && t.remove();
        }
        return this;
    }
  
    /**
     * @todo  implement drag-drop events on tiers and roles
     * @param {CoderTier} content
     * @returns {Boolean} 
     */
    add(content = null) {
        if (this.ready() && content instanceof CoderTier) {
            const item = new ItemView(content).render();
            this.content().appendChild(item);
            return true;
        }
        return false;
    }
    /**
     * @param {String} tier 
     * @returns {Boolean}
     */
    has(tier = '') { return !!this.get(tier); }
    /**
     * Main event to handle the tier list from server's response
     * @todo implement update (refresh) or add new to the list
     * @param {CoderTier[]} content 
     * @returns {ListView}
     */
    refresh(content = [], clear = false) {
        if (!this.ready()) { return this; }
        //remove non existing tiers
        clear && this.clear();
        content.forEach(tier => {
            //get the tier's  content and rewrite it all
            if (this.has(tier.name())) {
                //update
                this.roles(tier.name()).forEach(e => e.remove());
                const container = this.get(tier.name());
                tier.roles()
                    .map(role => role.name())
                    //.map(role => this.rolebox(role, tier.name()))
                    .map(role => new RoleView(tier.name(), role).render())
                    .forEach(item => container.appendChild(item));
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
     * @returns {ListView}
     */
    clear(tiers = []) {
        if (tiers.length) {
            //remove all children in tiers list
            tiers.map(tier => this.get(tier)).forEach(t => t && t.remove());
        }
        else if (this.content()) {
            //reset
            this.items().forEach(t => t.remove());
        }
        return this;
    }
}

/**
 * 
 */
class ItemView extends CoderView {
    /**
     * @param {CoderTier} tier 
     */
    constructor(tier = null) {
        super();
        this._tier = tier;
    }
    /**
     * @param {Element} element 
     */
    over(element){
        element.addEventListener("dragover", function (e) {
            e.preventDefault(); // REQUIRED
            e.dataTransfer.dropEffect = "move";
            const role = e.dataTransfer.getData("text/plain")
                || e.dataTransfer.getData("text")
                || '';
            const tier = this.dataset.tier || ''; // target tier
            //console.log(`role: ${role} | tier: ${tier}`);
            if (role !== tier) {
                this.classList.add("drag-over");
            }
        });
    }
    /**
     * @param {Element} element 
     */
    drop( element){
        element.addEventListener("drop", e => {
            e.preventDefault();
            element.classList.remove("drag-over");
            const role = e.dataTransfer.getData("text/plain")
                || e.dataTransfer.getData("text")
                || '';
            const tier = element.dataset.tier || ''; // target tier
            if (tier !== role) {
                CoderTiers.server().add(tier, role, r => {
                    !!r._response && element.appendChild(new RoleView(tier,role).render());
                    //!!r._response && element.appendChild(this.rolebox(role, tier));
                });
            }
        });
    }
    /**
     * @param {Element} element 
     */
    leave( element){
        element.addEventListener("dragleave", function (e) {
            this.classList.remove("drag-over");
        });
    }
    /**
     * @param {String} tier 
     * @returns {Element}
     */
    linkRemove(tier = '') {
        const icon = this.html('span', { 'class': 'dashicons dashicons-remove' });
        const link = this.action('remove', icon, 'remove right');
        link.setAttribute('data-tier', tier);
        return link;
    }    

    /**
     * @param {String} tier 
     * @returns {Element}
     */
    removeButton(tier = '') {
        const icon = this.html('span', { 'class': 'dashicons dashicons-remove' });
        const element = this.html('span',{ 'class': 'remove right', 'data-tier': tier }, icon);
        element.addEventListener('click', function (e) {
            e.preventDefault();
            const tier = (this.getAttribute('data-tier') || '').toString();
            CoderTiers.server().remove(tier, '', r => {
                r._response && CoderTiers.instance().view().remove(tier);
            } );
            return true;
        });
        return element;
    }      
    /**
     * @param {String} move 
     * @returns {Element}
     */
    sortButton(move = 'up') {
        const iconClass = move === 'up' ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2';
        const icon = this.html('span', { 'class': `dashicons ${iconClass}` });
        const tier = this._tier && this._tier.name() || '';
        const element = this.html('span', { 'class': `sort sort-${move}`, 'data-tier': tier }, icon);
        element.setAttribute('data-tier', tier);
        element.addEventListener('click', function (e) {
            e.preventDefault();
            const tier = (this.getAttribute('data-tier') || '').toString();
            const action = move === 'up' ? 'up' : 'down';
            CoderTiers.server().sort(tier, action, r => {
                r._response && CoderTiers.instance().view().move(tier, action);
            });
            return true;
        });
        return element;
    }
    /**
     * @returns {Element}
     */
    render() {
        const tier = this._tier;
        if (!(tier instanceof CoderTier)) {
            return this.html('div', { 'class': 'empty' }, 'No Tier Data');
        }
        const name = tier.name();
        const element = this.html('li', { 'class': 'item', 'data-tier': name });
        this.drop(element);
        this.over(element);
        this.leave(element);

        //const element = new TierView(name).render();
        element.appendChild(new TierView(name).render());
        tier.roles()
            .map(role => new RoleView(name,role).render())
            .forEach(role => element.append(role));
        
        //implemend drag and drop to sort tier blocks
        //element.appendChild(this.sortButton('up'));
        element.appendChild(this.removeButton(name));
        //element.appendChild(this.sortButton('down'));
        return element;
    }
}
/**
 *  
 */
class TierView extends CoderView {
    /**
     * @param {String} tier 
     */
    constructor( tier = '' ) {
        super();
        this._tier = tier || '';
    }
    /**
     * @param {Element} element 
     */
    drag( element ){
        //add drag drop events
        element.addEventListener("dragstart", e => {
            try{
                e.dataTransfer.setData("text/plain", element.dataset.tier);
                //e.dataTransfer.setData("text", element.dataset.tier);
                //e.dropEffect = "move";
                e.dataTransfer.effectAllowed = "move";
                //console.log(`${e.dataTransfer.effectAllowed}: ${element.dataset.tier || ''}`);
                element.classList.add("dragging");
            }
            catch(err){
                CoderTiers.instance().view().log('Drag operation not supported in this browser.', 'error');
            }
        });
    }
    /**
     * @param {Element} element 
     */
    leave( element){
        element.addEventListener("dragend", e => {
            element.classList.remove("dragging");
            try{
                //e.dataTransfer.setData("text/plain", '');
                e.dataTransfer.dropEffect = "none";
            }
            catch(err){
                CoderTiers.instance().view().log(err.toString(), 'error');
            }
        });
    }

    /**
     * @returns {Element}
     */
    render(){
        const tier = this._tier;
        const element = this.html('span',
            { draggable: 'true', 'class': 'tier button-primary', 'data-tier': tier },
            tier);
        this.drag(element);
        this.leave(element);
        return element;
    }
}
/**
 * 
 */
class RoleView extends CoderView {
    /**
     * @param {String} tier 
     * @param {String} role
     */
    constructor(tier = '' , role = ''){
        super();
        this._tier = tier || '';
        this._role = role || '';
    }
    /**
     * @returns {Element}
     */
    render() {
        const tier = this._tier;
        const role = this._role;
        const roledata = tier && `${tier}.${role}` || role;
        const element = this.html('span', { 'class': 'role button', 'data-role': roledata }, role);
        element.addEventListener('click', function (e) {
            e.preventDefault();
            const content = (this.getAttribute('data-role') || '').toString();
            const td = content.split('.');
            CoderTiers.server().remove(td[0], td[1] || '', r => {
                r.tier && r.role && element.remove();
            });
            return true;
        });
        return element
    }
}


