import HttpClient from 'src/service/http-client.service';
import BaseWishlistStoragePlugin from 'src/plugin/wishlist/base-wishlist-storage.plugin';
import Storage from 'src/helper/storage/storage.helper';
import DomAccessHelper from 'src/helper/dom-access.helper';

export default class MyWishlistPersistStoragePlugin extends BaseWishlistStoragePlugin {
    init() {
        super.init();
        this.httpClient = new HttpClient();

    }
    load() {
        this._merge(() => {
            this.httpClient.get(this.options.listPath, response => {
                this.products = JSON.parse(response);
                
                super.load();
            });
        });
    }
    getInitProductAdded() {
        if (!this.initProductAdded) {
            this.httpClient.get(this.options.listPath, response => {
                this.initProductAdded = JSON.parse(response);
                return this.initProductAdded;
            });
        } else {
            return this.initProductAdded;
        }
        console.log(this.initProductAdded);
    }
    /**
     * @private
     */
     _merge(callback) {
        this.storage = Storage;
        const key = 'wishlist-' + (window.salesChannelId || '');

        const productStr = this.storage.getItem(key);

        const products = JSON.parse(productStr);

        if (products) {
            this.httpClient.post(this.options.mergePath, JSON.stringify({
                _csrf_token: this.options.tokenMergePath,
                'productIds' : Object.keys(products),
            }), response => {
                if (!response) {
                    throw new Error('Unable to merge product wishlist from anonymous user');
                }

                this.$emitter.publish('Wishlist/onProductMerged', {
                    products: products,
                });

                this.storage.removeItem(key);
                this._block = DomAccessHelper.querySelector(document, '.flashbags');
                this._block.innerHTML = response;
                this._pagelet();
                callback();
            });
        }
        callback();
    }

    getCurrentCounter() {
        return this.products ? Object.keys(this.products).length : 0;
    }
    getProducts() {
        return this.products;
    }
    has(productId) {
        return Object.keys(this.products).find(key => this.products[key] == productId);
    }
}
