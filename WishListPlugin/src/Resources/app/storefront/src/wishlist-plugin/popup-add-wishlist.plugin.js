import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

export default class PopupAddWishlistPlugin extends Plugin {

    static options = {
        path : '',
        productId : '',
    }
    
    init() {
        this.httpClient = new HttpClient();
        console.log("create");
        
    }
    load() {
        this.modal = DomAccess.querySelector(document, `#wishlistAll-${this.options.productId}`, false);
        this.selectWishlist = DomAccess.querySelector(this.modal, '.append-wishlist-data', false);
        this.submit = DomAccess.querySelector(this.modal, '.submit-to-add', false);
        this._registerEvents();
    }
    getModal() {
        return this.modal;
    }
    _registerEvents() {
        this.selectWishlist.addEventListener('change', this._onChange.bind(this));
        this.submit.addEventListener('click', this._onSubmit.bind(this));
    }



    _onChange(event) {
        let disableInput = DomAccess.querySelector(document, 'input[name="name"]', false);
        disableInput.disabled =  (!event.target.value) ? false : true;
    }

    _onSubmit(event) {
        event.preventDefault();
        let form  = DomAccess.querySelector(this.el, 'form', false);
        let url = form.getAttribute("action");
        var data = new FormData(form);
        this.httpClient.post(url, data, response => {
            const wishlistBasket = DomAccess.querySelector(document, '#wishlist-basket', false);
            let _wishlistStorage = window.PluginManager.getPluginInstanceFromElement(wishlistBasket, 'MyWishlistStorage');
            _wishlistStorage.load();
            const wistlistList = DomAccess.querySelector(document, '#remove-wishlist-product-list', false);
            if (wistlistList) {
                let newList = window.PluginManager.getPluginInstanceFromElement(wistlistList, 'GetWishlistBelongs');
                if (newList) {
                    newList._getDataWishlist();
                }
            }
            

            this.hideModal();
        });
    }

    showModal() {
        // execute all needed scripts for the slider
        const $modal = $(this.modal);

        $modal.off('shown.bs.modal');
        $modal.on('shown.bs.modal', () => {
            this.$emitter.publish('modalShow', { $modal });
        });

        $modal.modal('show');
    }
    hideModal() {
        // execute all needed scripts for the slider
        const $modal = $(this.modal);
        console.log($modal);
        $modal.off('hide.bs.modal');
        $modal.off('hide.bs.modal', () => {
            this.$emitter.publish('hideModal', { $modal });
        });

        $modal.modal('hide');
    }
}




