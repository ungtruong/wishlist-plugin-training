import './page/wishlist-plugin-list';
import './page/wishlist-plugin-detail';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Module } = Shopware;

Module.register('wishlist-plugin', {
    type: 'plugin',
    name: 'Wishlist',
    title: 'wishlist.general.mainMenuItemGeneral',
    description: 'wishlist.general.descriptionTextModule',
    color: '#ff3d58',
    icon: 'default-basic-shape-heart',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'wishlist-plugin-list',
            path: 'list'
        },
        detail: {
            component: 'wishlist-plugin-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'wishlist.plugin.list'
            }
        }
    },

    navigation: [{
        label: 'wishlist.general.mainMenuItemGeneral',
        color: '#ff3d58',
        path: 'wishlist.plugin.list',
        icon: 'default-basic-shape-heart',
        position: 98,
        parent: 'sw-extension',
    }]
});
