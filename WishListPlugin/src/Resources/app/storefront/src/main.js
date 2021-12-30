// Import all necessary Storefront plugins and scss files
import ExamplePlugin from './example-plugin/example-plugin.plugin';
import EditWishlistName from './wishlist-plugin/edit-name.plugin';
import SearchSuggest from './wishlist-plugin/search-suggest.plugin';
import GetAllWishListPlugin from './wishlist-plugin/get-all-wishlist.plugin.js';
import PopupAddWishlistPlugin from './wishlist-plugin/popup-add-wishlist.plugin.js';
import CopyClipboardPlugin from './wishlist-plugin/copy-clipboard.plugin.js';
import CheckProductAddedWishlistPlugin from './wishlist-plugin/check-product-added-wishlist.plugin.js';
import GetWishlistByProductPlugin from './wishlist-plugin/get-wishlist-by-product.plugin.js';
import PopupDeleteWishlistProductPlugin from './wishlist-plugin/popup-delete-wishlist-product.plugin.js';
import GetWishlistBelongsPlugin from './wishlist-plugin/get-wishlist-belongs.plugin.js';
import DeleteWishlistProductAddCardPlugin from './wishlist-plugin/delete-wishlist-product-add-cart.js';

// Register them via the existing PluginManager


import MyWishlistPersistStoragePlugin from './wishlist-plugin/my-persist-wishlist.plugin.js';
import MyWishlistWidgetPlugin from './wishlist-plugin/my-wishlist-widget.plugin.js';


import WishlistLocalStoragePlugin from 'src/plugin/wishlist/local-wishlist.plugin';
import AddToWishlistPlugin from 'src/plugin/wishlist/add-to-wishlist.plugin';
import WishlistWidgetPlugin from 'src/plugin/header/wishlist-widget.plugin';
import GuestWishlistPagePlugin from 'src/plugin/wishlist/guest-wishlist-page.plugin';

const PluginManager = window.PluginManager;
PluginManager.register('PopupDeleteWishlistProduct', PopupDeleteWishlistProductPlugin, '[data-popup-delete-wishlist-product]');
PluginManager.register('PopupAddWishlist', PopupAddWishlistPlugin, '[data-popup-add-wishlist]');
PluginManager.register('ExamplePlugin', ExamplePlugin, '[data-example-plugin]');
PluginManager.register('EditWishlistName', EditWishlistName, '[data-edit-wishlist-name]');
PluginManager.register('SearchSuggest', SearchSuggest, '[data-search-suggest]');
PluginManager.register('GetAllWishlist', GetAllWishListPlugin, '[data-get-all-wishlist]');
PluginManager.register('CopyClipboard', CopyClipboardPlugin, '[data-copy-clipboard]');
PluginManager.register('DeleteWishlistProductAddCard', DeleteWishlistProductAddCardPlugin, '[data-delete-wishlist-product-add-card]');

PluginManager.register('MyWishlistStorage', MyWishlistPersistStoragePlugin, '[data-my-wishlist-storage]');
PluginManager.register('MyWishlistWidget', MyWishlistWidgetPlugin, '[data-my-wishlist-widget]');
PluginManager.register('CheckProductAddedWishlist', CheckProductAddedWishlistPlugin, '[data-check-product-added-wishlist]');

PluginManager.register('GetWishlistByProduct', GetWishlistByProductPlugin, '[data-get-wishlist-by-product]');
PluginManager.register('GetWishlistBelongs', GetWishlistBelongsPlugin, '[data-get-wishlist-belongs]')

// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}
//product-wishlist-added

//PluginManager.register('WishlistStorage', WishlistPersistStoragePlugin, '[data-wishlist-storage]');
// if (window.customerLoggedInState) {
//     PluginManager.register('WishlistStorage', WishlistPersistStoragePlugin, '[data-wishlist-storage]');
// } else {
//     PluginManager.register('WishlistStorage', WishlistLocalStoragePlugin, '[data-wishlist-storage]');
//     PluginManager.register('GuestWishlistPage', GuestWishlistPagePlugin, '[data-guest-wishlist-page]');
// }

// PluginManager.register('AddToWishlist', AddToWishlistPlugin, '[data-add-to-wishlist]');
// PluginManager.register('WishlistWidget', WishlistWidgetPlugin, '[data-wishlist-widget]');