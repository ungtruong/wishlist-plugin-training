import template from './wishlist-plugin-list.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('wishlist-plugin-list', {
    template,

    inject: [
        'repositoryFactory'
    ],

    data() {
        return {
            repository: null,
            bundles: null
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        columns() {
            return [{
                property: 'name',
                dataIndex: 'name',
                label: this.$t('wishlist.list.columnName'),
                inlineEdit: 'string',
                allowResize: true,
                primary: true,
                routerLink: 'wishlist.plugin.detail',
            },
            {
                property: 'customers.firstName',
                dataIndex: 'customers.lastName,customers.firstName',
                label: this.$t('wishlist.list.columnCustomerName'),
                inlineEdit: 'string',
                allowResize: true,
                primary: true
            },
            {
                property: 'products',
                dataIndex: 'products',
                label: this.$t('wishlist.list.columnCountProduct'),
                inlineEdit: 'string',
                allowResize: true,
                primary: true
            }
        ];
        },
        getCriteria() {
            return new Criteria()
                .addAssociation('products')
                .addAssociation('customers')
                //.addAggregation(Criteria.count('countProducts', 'products'))
        }
    },

    created() {
        this.repository = this.repositoryFactory.create('wishlist_plugin');
        this.repository
            .search(this.getCriteria, Shopware.Context.api)
            .then((result) => {
                this.bundles = result;
                console.log(result);
            });
    }
});