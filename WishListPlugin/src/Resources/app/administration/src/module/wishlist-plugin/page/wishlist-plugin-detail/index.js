import template from './wishlist-plugin-detail.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('wishlist-plugin-detail', {
    template,

    inject: [
        'repositoryFactory'
    ],

    // mixins: [
    //     Mixin.getByName('notification')
    // ],

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    data() {
        return {
            bundles: null,
            isLoading: false,
            processSuccess: false,
            repository: null
        };
    },

    computed: {
        columns() {
            return [
                // {
                //     property: 'products.id',
                //     dataIndex: 'products',
                //     label: this.$t('wishlist.detail.columnProductId'),
                //     inlineEdit: 'string',
                //     allowResize: true,
                //     primary: true,
                //     //routerLink: 'sw.product.detail'
                // },
            {
                property: 'products.name',
                dataIndex: 'products',
                label: this.$t('wishlist.detail.columnProductName'),
                inlineEdit: 'string',
                allowResize: true,
                primary: true,
                routerLink: 'sw.product.detail'
            },
            {
                property: 'quantity',
                dataIndex: 'quantity',
                label: this.$t('wishlist.detail.columnQuantity'),
                inlineEdit: 'number',
                allowResize: true
            }
        ];
        },
        getCriteria() {
            return new Criteria()
                .addAssociation('products')
                .addAssociation('wishlistPlugin')
                .addFilter(
                    Criteria.equals('wishlistPlugin.id', this.$route.params.id)
                );
        }
    },



    created() {
        this.repository = this.repositoryFactory.create('wishlist_plugin_product');
        this.repository
            .search(this.getCriteria, Shopware.Context.api)
            .then((result) => {
                this.bundles = result;
                console.log(result);
            });
    }
});