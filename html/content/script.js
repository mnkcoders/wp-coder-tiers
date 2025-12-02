/**
 * TEST THIS GPT PROPOSAL.
 * PREPARE THE SERVER'S AJAX RESPONSE TO:
 * task: 'tiers'
 * send: CoderTier[...]
 */

document.addEventListener('DOMContentLoaded', function () {
    CoderTiers.instance();
});


/**
 * ============================================================
 *  MAIN CLIENT MANAGER
 * ============================================================
 */
class CoderTiers {

    constructor(container = 'coder-tiers') {

        if (CoderTiers.__instance) {
            return CoderTiers.__instance;
        }

        CoderTiers.__instance = this;

        this.initialize(container);
        this.fill();
    }

    initialize(container = '') {
        this._view = new CoderBlueprint(container);
        this._client = new CoderClient();
        this._tiers = [];
    }

    fill() {
        this.client().request('tiers', response => {

            const tiers = (response?.tiers || []).map(row =>
                new CoderTier(row.tier, row.roles)
            );

            this._tiers = tiers;
            this.view().refresh(tiers, true);
        });
        return this;
    }

    client() { return this._client; }
    view() { return this._view; }
    tiers() { return this._tiers; }

    /**
     * Send new tier structure to server
     */
    update(tiers = []) {
        this.client().send(tiers.map(t => t.tierdata()), resp => {
            console.log("Server updated:", resp);
        });
        return this;
    }

    static instance() {
        return CoderTiers.__instance || new CoderTiers();
    }
}



/**
 * ============================================================
 *  TIER DATA HOLDER
 * ============================================================
 */
class CoderTier {

    constructor(tier = '', roles = []) {
        this._tier = tier || '';
        this._roles = Array.isArray(roles) ? roles : [];
    }

    toString() { return this._tier; }
    tier() { return this._tier; }

    roles() {
        return this._roles.map(role => new CoderTier(role));
    }

    tierdata() {
        return {
            tier: this._tier,
            roles: this._roles
        };
    }
}



/**
 * ============================================================
 *  AJAX CLIENT
 * ============================================================
 */
class CoderClient {

    constructor() {
        this.ajaxurl = window.ajaxurl || '/wp-admin/admin-ajax.php';
        this.nonce = window.coderTiersNonce || '';
    }

    ajax(data = {}, callback = null) {

        data.action = 'coder_tiers_action';
        data._nonce = this.nonce;

        fetch(this.ajaxurl, {
            method: 'POST',
            body: new URLSearchParams(data)
        })
        .then(r => r.json())
        .then(response => {
            callback && callback(response);
        })
        .catch(err => console.error("AJAX error:", err));

        return this;
    }

    request(task = '', callback = null) {
        return this.ajax({task}, callback);
    }

    send(tiers = [], callback = null) {
        return this.ajax({task: 'save', tiers: JSON.stringify(tiers)}, callback);
    }
}



/**
 * ============================================================
 *  VIEW / BLUEPRINT HANDLER
 * ============================================================
 */
class CoderBlueprint {

    constructor(container = '') {
        this._content = document.querySelector('.' + container);
        if (!this._content) {
            console.error("CoderBlueprint: container not found:", container);
            return;
        }
        this.initialize();
    }

    initialize() {
        // Drag drop handlers can be bound here if needed
    }

    content() { return this._content; }

    list() {
        return Array.from(this._content.querySelectorAll('li.tier'));
    }

    tiers() {
        return this.list().map(item => item.dataset.tier);
    }

    roles(tier = '') {
        const item = this.get(tier);
        if (!item) return [];
        return Array.from(item.querySelectorAll('.role'))
                    .map(span => span.dataset.role);
    }

    /**
     * Safe element builder
     */
    html(type = 'div', attributes = {}, content = '') {
        const el = document.createElement(type);
        Object.entries(attributes).forEach(([k, v]) => el.setAttribute(k, v));
        if (content) el.innerHTML = content;
        return el;
    }

    /**
     * Create a row in the UI
     */
    add(tierObj = null) {
        if (!(tierObj instanceof CoderTier)) return false;

        const li = this.html('li', {
            class: 'tier',
            'data-tier': tierObj.tier()
        });

        // Tier label
        const label = this.html('div', {
            class: 'tier-header',
            draggable: 'true',
            'data-tier': tierObj.tier()
        }, tierObj.tier());

        li.appendChild(label);

        // Roles container
        const rolesWrap = this.html('div', {class: 'tier-roles'});
        tierObj._roles.forEach(r => {
            const span = this.html('span', {
                class: 'role',
                'data-role': r,
                draggable: 'true'
            }, r + ' <span class="remove-role">×</span>');
            rolesWrap.appendChild(span);
        });

        li.appendChild(rolesWrap);

        // Append to list
        this._content.appendChild(li);

        return true;
    }

    get(tier = '') {
        return this._content.querySelector(`li[data-tier="${tier}"]`);
    }

    has(tier = '') {
        return !!this.get(tier);
    }

    /**
     * Refresh or update list
     */
    refresh(content = [], clear = false) {

        if (clear) this.clear();

        const existing = this.tiers();

        // Add or update
        content.forEach(tierObj => {

            if (this.has(tierObj.tier())) {
                this.updateTier(tierObj);
            } else {
                this.add(tierObj);
            }
        });

        // Remove missing tiers
        const newList = content.map(t => t.tier());
        const toRemove = existing.filter(t => !newList.includes(t));
        this.clear(toRemove);

        return this;
    }

    /**
     * Replace inner HTML for an existing tier
     */
    updateTier(tierObj) {
        const li = this.get(tierObj.tier());
        if (!li) return;

        li.innerHTML = ""; // reset

        const label = this.html('div', {
            class: 'tier-header',
            draggable: 'true',
            'data-tier': tierObj.tier()
        }, tierObj.tier());

        const rolesWrap = this.html('div', {class: 'tier-roles'});

        tierObj._roles.forEach(r => {
            const span = this.html('span', {
                class: 'role',
                'data-role': r,
                draggable: 'true'
            }, r + ' <span class="remove-role">×</span>');
            rolesWrap.appendChild(span);
        });

        li.appendChild(label);
        li.appendChild(rolesWrap);
    }

    /**
     * Remove tiers
     */
    clear(tiers = []) {

        if (!tiers.length) {
            this._content.innerHTML = '';
            return this;
        }

        tiers.forEach(t => {
            const item = this.get(t);
            item && item.remove();
        });

        return this;
    }
}
