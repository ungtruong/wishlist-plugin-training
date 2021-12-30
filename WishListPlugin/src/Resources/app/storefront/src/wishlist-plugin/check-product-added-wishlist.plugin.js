import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

export default class CheckProductAddedWishlist extends Plugin {

    static options = {
        productId : '',
        text : '',
    }
    
    init() {
        this.classList = {
            isLoading: 'product-wishlist-loading',
            addedState: 'product-wishlist-added',
            notAddedState: 'product-wishlist-not-added',
        };
        this._getWishlistStorage();
        this.initStateClasses();
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

        this._wishlistStorage = window.PluginManager.getPluginInstanceFromElement(wishlistBasketElement, 'MyWishlistStorage');
    }
    initStateClasses() {

        if (this._wishlistStorage.has(this.options.productId)) {
            this._addActiveStateClasses();
        } else {
            this._removeActiveStateClasses();
        }

        this.el.classList.remove(this.classList.isLoading);
    }
    /**
     * @private
     */
     _addActiveStateClasses() {
        this.el.classList.remove(this.classList.notAddedState);
        this.el.classList.add(this.classList.addedState);
    }

    /**
     * @private
     */
    _removeActiveStateClasses() {
        this.el.classList.remove(this.classList.addedState);
        this.el.classList.add(this.classList.notAddedState);
    }
}




