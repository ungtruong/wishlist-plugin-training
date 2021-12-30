import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

export default class popupAllWishList extends Plugin {

    static options = {
        router : {
            path : ''
        }
    }
    
    init() {
        //super.init();
        this.httpClient = new HttpClient();
        this._registerEvents();
        
    }

    /**
     * @returns popupAddWishList
     * @private
     */
     _getpopupAddWishList() {
        const popupElement = DomAccess.querySelector(document, `#wishlistAll-${this.options.router.productId}`, true);
        this.popupAddWishList = window.PluginManager.getPluginInstanceFromElement(popupElement, 'PopupAddWishlist');

    }
    _registerEvents() {
        this.$emitter.subscribe('Wishlist/onWishListPopupAddLoaded', () => {

        });
        
        this.el.addEventListener('click', this._onClick.bind(this));

    }

    _onClick() {
        if (!this.popupAddWishList) {
            this._getpopupAddWishList()
            this.popupAddWishList.load();
        }
        this._getDataWishlist()
        
    }
    _getDataWishlist() {
        console.log("_getDataWishlist")
        this.httpClient.get(this.options.router.path, response => {
            this.wishlist = JSON.parse(response);
            const selectWishlist = DomAccess.querySelector(this.popupAddWishList.getModal(), '.append-wishlist-data', false);
            this.popupAddWishList.showModal();
            this._appendDataWishlist(selectWishlist);
        });
    }

    // _showModal(modal) {
    //     // execute all needed scripts for the slider
    //     const $modal = $(modal);

    //     $modal.off('shown.bs.modal');
    //     $modal.on('shown.bs.modal', () => {
    //         this.$emitter.publish('modalShow', { modal });
    //     });

    //     $modal.modal('show');
    // }
    _appendDataWishlist(el) {
        let html = '<option value="">Please select a wishlist</option>';
        for(const value in this.wishlist) {
           html += `<option value="${value}">${this.wishlist[value]}</option>`;
        }
        $(el).html(html);
    }
}




