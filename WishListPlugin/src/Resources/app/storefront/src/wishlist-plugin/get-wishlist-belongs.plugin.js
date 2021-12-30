import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

export default class GetWishlistBelongs extends Plugin {
    
    init() {
        //super.init();
        this.httpClient = new HttpClient();
        this._registerEvents();
        this._getDataWishlist();
    }


    _registerEvents() {
        this.el.addEventListener('click', this._onClick.bind(this));

    }

    _onClick(event) {
        if (event.target.closest('.remove-product')) {
            let value = event.target.closest('.remove-product').value
            console.log()
            let form  = DomAccess.querySelector(this.el, `#wishlistPlugin-${value}`, false);
            let url = this.options.router.pathDelete;
            var data = new FormData(form);
            this.httpClient.post(url, data, response => {
                const wishlistBasket = DomAccess.querySelector(document, '#wishlist-basket', true);
                let _wishlistStorage = window.PluginManager.getPluginInstanceFromElement(wishlistBasket, 'MyWishlistStorage');
                this._getDataWishlist();
                _wishlistStorage.load();
            });
        }
    }
    _getDataWishlist() {
        this.httpClient.get(this.options.router.path, response => {
            this.wishlist = JSON.parse(response);
            this._appendDataWishlist();
        });
    }


    _appendDataWishlist() {
        let elData = this.el;
        let html= '';
        $(elData).html('');
        for(const value in this.wishlist) {
            let href = this.options.router.pathDetail;
            href = href.replace("0", value);
            html += `<form action="${this.options.router.pathDelete}" method="POST" id="wishlistPlugin-${this.wishlist[value].wishlistProductId}">`;
            html += `<p><a style="width: 80%; display:inline-block;line-height:35px" href="${href}" >${this.wishlist[value].name}</a>`;
            html += `<input type='hidden' name="wishlistPluginId" value="${this.wishlist[value].wishlistProductId}">`
            html += `<span style=" display:inline-block; float:right"><button type="button" value="${this.wishlist[value].wishlistProductId}" 
                    class="remove-product btn btn-light btn-sm pull-right float-end" style="right:0"><span aria-hidden="true">&times;
                    </span></button></span></p>`;
            html += `<hr>`;
            html += `<input type='hidden' name="_csrf_token" value="${this.options.router.tokenDelete}">`
            html += `</form>`;
        }
        $(elData).html(html);
    }
}




