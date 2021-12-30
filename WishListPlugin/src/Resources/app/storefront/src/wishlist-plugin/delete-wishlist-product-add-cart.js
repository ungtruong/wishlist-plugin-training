import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

export default class DeleteWishlistProductAddCard extends Plugin {

    static options = {
        wishlistId : '',
        productId : ''

    }
    
    init() {
        this._registerEvents();
        this.httpClient = new HttpClient();
    }
    _registerEvents() {
        this.el.addEventListener('click', this._onClick.bind(this));
    }

    _onClick(event) {
        if (event.target.closest('.btn-buy')) {
            let form  = DomAccess.querySelector(document, `#detete-add-cart-${this.options.productId}`, false);
            if (form) {
                let url = form.action;
                let parent = form.closest('.cms-listing-col');

                var data = new FormData(form);
                this.httpClient.post(url, data, response => {
                    parent.remove();
                });
            }

        }
    }
}