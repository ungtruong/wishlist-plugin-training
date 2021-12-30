import SearchWidgetPlugin from 'src/plugin/header/search-widget.plugin.js';
import DomAccess from 'src/helper/dom-access.helper';


export default class SearchWishlist extends SearchWidgetPlugin {
    static options = {
        searchWidgetSelector: '.js-search-form',
        searchWidgetResultSelector: '.js-search-result-wishlist',
        searchWidgetResultItemSelector: '.js-result',
        searchWidgetInputFieldSelector: 'input[type=search]',
        searchWidgetButtonFieldSelector: 'button[type=submit]',
        searchWidgetUrlDataAttribute: 'data-url',
        searchWidgetCollapseButtonSelector: '.js-search-toggle-btn',
        searchWidgetCollapseClass: 'collapsed',
        searchCancelSubmit: 'cancel-submit',
        searchWidgetDelay: 250,
        searchWidgetMinChars: 3,
    };
    init() {
        super.init();
    }
    /**
     * Close/remove the search results from DOM if user
     * clicks outside the form or the results popover
     * @param {Event} e
     * @private
     */
     _onBodyClick(e) {
        // early return if click target is the search form or any of it's children

        if (e.target.closest(this.options.searchWidgetSelector)) {
            return;
        }

        // early return if click target is the search result or any of it's children
        if (e.target.closest(this.options.searchWidgetResultSelector)) {
            return;
        }

        if (e.target.closest(this.options.searchWidgetButtonFieldSelector)) {
            if (this.options.isAddProduct) {
                this._getWishlistStorage();
                this.checkbox = DomAccess.querySelectorAll(this.el, 'input[type=checkbox]:checked', false);
                if (this.checkbox) {
                    this.checkbox.forEach( el => {
                        this._wishlistStorage.add(el.value, "#");
                    });
                   
                }
            }
            return;
        }
        // remove existing search results popover
        this._clearSuggestResults();

        this.$emitter.publish('onBodyClick');
    }

    /**
     * @returns WishlistWidgetPlugin
     * @private
     */
     _getWishlistStorage() {
        const wishlistBasketElement = DomAccess.querySelector(document, '#wishlist-basket', false);

        if (!wishlistBasketElement) {
            return;
        }

        this._wishlistStorage = window.PluginManager.getPluginInstanceFromElement(wishlistBasketElement, 'WishlistStorage');
    }
}