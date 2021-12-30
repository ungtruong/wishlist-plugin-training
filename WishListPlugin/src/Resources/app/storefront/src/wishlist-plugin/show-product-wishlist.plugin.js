import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

export default class CheckProductAddedWishlist extends Plugin {

    static options = {
        productId : '',
        text : '',
    }
    
    init() {
        
    }
}




