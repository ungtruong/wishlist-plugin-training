import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

export default class GetWishlistByProduct extends Plugin {
    
    init() {
        //super.init();
        this.httpClient = new HttpClient();
        this._registerEvents();
        
    }

    /**
     * @returns popupAddWishList
     * @private
     */
     _getpopupShowWishList() {
        const popupElement = DomAccess.querySelector(document, `#wishlistByProduct-${this.options.router.productId}`, true);
        this.popupDeleteWishList = window.PluginManager.getPluginInstanceFromElement(popupElement, 'PopupDeleteWishlistProduct');

    }
    _registerEvents() {
        this.el.addEventListener('click', this._onClick.bind(this));

    }

    _onClick() {
        if (!this.popupDeleteWishList) {
            this._getpopupShowWishList()
            
            this.popupDeleteWishList.load();
        }
        this._getDataWishlist();
        

    }
    _getDataWishlist() {
        this.httpClient.get(this.options.router.path, response => {
            this.wishlist = JSON.parse(response);
            const elAppend = DomAccess.querySelector(this.popupDeleteWishList.getModal(), '.append-wishlist-data', false);
            const elEmpty = DomAccess.querySelector(this.popupDeleteWishList.getModal(), '.empty-wishlist', false);
            this.popupDeleteWishList.showModal();
            this._appendDataWishlist(elAppend, elEmpty);
        });
    }

    _showModal(modal) {
        // execute all needed scripts for the slider
        const $modal = $(modal);

        $modal.off('shown.bs.modal');
        $modal.on('shown.bs.modal', () => {
            this.$emitter.publish('modalShow', { modal });
        });

        $modal.modal('show');
    }
    _appendDataWishlist(elData, elEmpty) {
        $(elEmpty).hide();
        $(elData).hide();
        let html= '';
        for(const value in this.wishlist) {
            // let href = this.options.router.pathDetail;
            // href = href.replace("0", value);
            // html += `<p><a href="${href}" >${this.wishlist[value].name}</a>`;
            // html += `<input type='hidden' name="wishlistPluginId" value="${this.wishlist[value].wishlistProductId}">`
            // html += '<button type="button" class="remove-product btn btn-light btn-sm pull-right float-end" style="margin-left: 90%"><span aria-hidden="true">&times;</span></button></p>';        
            // html += `<hr>`;
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

        if (html) {
            $(elData).show();
            $(elData).html(html);
        } else {
            $(elData).hide();
            $(elEmpty).show();
        }
         
    }
}




