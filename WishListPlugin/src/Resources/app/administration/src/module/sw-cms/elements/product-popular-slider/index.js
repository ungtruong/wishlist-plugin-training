import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'product-popular-slider',
    label: 'product popular slider',
    component: 'sw-cms-el-product-popular-slider',
    configComponent: 'sw-cms-el-config-product-popular-slider',
    previewComponent: 'sw-cms-el-preview-product-popular-slider',
    defaultConfig: {
        content: {
            source: 'static',
            value: `
                <h2>Lorem Ipsum dolor sit amet 11111</h2>
                <p>Lorem ipsum dolor sit amet</p>
            `.trim(),
        },
        verticalAlign: {
            source: 'static',
            value: null,
        },
    },
});
