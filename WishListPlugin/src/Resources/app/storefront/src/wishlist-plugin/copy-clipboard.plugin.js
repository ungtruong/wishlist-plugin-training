import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

export default class CopyClipboardPlugin extends Plugin {

    static options = {
        inputCopy : '',
        text : '',
    }
    
    init() {
        this._registerEvents();
    }
    _registerEvents() {
        this.el.addEventListener('click', this._onClick.bind(this));
    }

    _onClick(event) {
        let copyText  = DomAccess.querySelector(document, `#${this.options.inputCopy}`, false);
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);
    }
}




