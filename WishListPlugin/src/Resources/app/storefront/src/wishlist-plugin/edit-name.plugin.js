import Plugin from 'src/plugin-system/plugin.class';

export default class EditNamePlugin  extends Plugin {
    static options = {
        btnClickId: "edit-name-btn",
        showInput: "show-input",
        showElement : "show-element",
        input : 'wishlist-name',
        cancelBtnId : 'btn-cancel',
    };

    init() {
        const that = this;
        window.addEventListener('DOMContentLoaded', (event) => {
            document.getElementById(that.options.btnClickId).addEventListener("click", this.showInput.bind(that, that.options.showInput, that.options.showElement, that.options.input));
            document.getElementById(that.options.cancelBtnId).addEventListener("click", this.showInput.bind(that, that.options.showElement, that.options.showInput, null));
        });
    }
    showInput(showElement, hideElement, input = null) {
 
        document.getElementById(showElement).style.display = 'block';
        document.getElementById(hideElement).style.display = 'none';

        if (input != null) {
            document.getElementById(input).focus();
        }
        

    }
}
