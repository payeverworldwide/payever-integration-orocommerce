define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');
    const $ = require('jquery');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const FinanceExpressProductComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: Object.assign({}, BaseComponent.prototype.options, {
            settings: [],
            product: null,
            productDetail: [],
            productPrices: []
        }),

        /**
         * @property {Object}
         */
        listen: {},

        /**
         * @inheritdoc
         */
        constructor: function FinanceExpressComponent(options) {
            FinanceExpressComponent.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize(options) {
            FinanceExpressProductComponent.__super__.initialize.call(this, options);
            this.options = Object.assign({}, this.options, options || {});

            // Make settings
            let settings = this.options.settings;
            settings['data-amount'] = this.options.productDetail.price;
            let productThumbnail = '';
            if (this.options.productDetail.thumbnail) {
                productThumbnail = this.options.productDetail.thumbnail;
            }
            // Make cart
            var cart = [];
            cart.push(
                {
                    name: this.options.productDetail.name,
                    identifier: this.options.productDetail.identifier,
                    amount: this.options.productDetail.amount,
                    price: this.options.productDetail.price,
                    quantity: 1,
                    thumbnail: productThumbnail,
                    unit: 'EACH'
                }
            );

            settings['data-cart'] = JSON.stringify(cart);

            const quoteCallbackUrl = new URL(settings['data-quotecallbackurl']);
            quoteCallbackUrl.searchParams.append('cart', JSON.stringify(cart));
            settings['data-quotecallbackurl'] = quoteCallbackUrl.href;

            const failureUrl = new URL(settings['data-failureurl']);
            failureUrl.searchParams.append('type', 'product');
            failureUrl.searchParams.append('identifier', this.options.productDetail.identifier);
            settings['data-failureurl'] = failureUrl.href;

            // Create div element
            let div = document.createElement('div');
            div.classList.add('payever-widget-finexp');
            for (const [key, value] of Object.entries(settings)) {
                div.setAttribute(key, value);
            }

            // Put it after Qty element
            let qtyElm = document.querySelectorAll('.product-view-quantity')[0];
            qtyElm.after(div);

            PayeverPaymentWidgetLoader.init(
                '.payever-widget-finexp'
            );
        }
    });

    return FinanceExpressProductComponent;
});
