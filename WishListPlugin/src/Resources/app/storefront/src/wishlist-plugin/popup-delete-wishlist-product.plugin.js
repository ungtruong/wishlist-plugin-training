import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

export default class PopupDeleteWishlistProductPlugin extends Plugin {


    init() {
        this.httpClient = new HttpClient();
    }
    load() {
        this.modal = DomAccess.querySelector(document, `#wishlistByProduct-${this.options.productId}`, false);
        console.log(`#wishlistByProduct-${this.options.productId}`);
        this.selectWishlist = DomAccess.querySelector(this.modal, '.append-wishlist-data', false);
        this.submit = DomAccess.querySelector(this.modal, '.modal-body', false);
        this._registerEvents();
    }
    getModal() {
        return this.modal;
    }
    _registerEvents() {
        this.submit.addEventListener('click', this._onSubmit.bind(this));
    }
    _onSubmit(event) {
        if (event.target.closest('.remove-product')) {
            let value = event.target.closest('.remove-product').value
            let form  = DomAccess.querySelector(document, `#wishlistPlugin-${value}`, false);
            let url = this.options.pathDelete;
            var data = new FormData(form);
            console.log(data);
            this.httpClient.post(url, data, response => {
                console.log(response);
                const wishlistBasket = DomAccess.querySelector(document, '#wishlist-basket', true);
                let _wishlistStorage = window.PluginManager.getPluginInstanceFromElement(wishlistBasket, 'MyWishlistStorage');
                _wishlistStorage.load();
                this.hideModal();
                let wishlistBtn = DomAccess.querySelector(document, `.product-wishlist-${this.options.productId}`, false);
            });
        }
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




