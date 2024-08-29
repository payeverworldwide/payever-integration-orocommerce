define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');
    const $ = require('jquery');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const FinanceExpressShoppingListComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: Object.assign({}, BaseComponent.prototype.options, {
            settings: [],
            cartDetails: []
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

            // @todo Update when cart was modified
        },

        /**
         * @inheritdoc
         */
        initialize(options) {
            FinanceExpressShoppingListComponent.__super__.initialize.call(this, options);
            this.options = Object.assign({}, this.options, options || {});

            mediator.on('frontend:item:delete', this.onLineItemDelete);
            mediator.on('product:quantity-unit:update', this.onLineItemUpdate);
            mediator.on('change:shopping_lists', this.onShoppingListsRefresh);

            // Make settings
            let settings = this.options.settings,
                cart = this.options.cartDetails;

            let amount = cart.reduce((s, f) => {
                return s + f.amount;
            }, 0);

            const quoteCallbackUrl = new URL(settings['data-quotecallbackurl']);
            quoteCallbackUrl.searchParams.append('cart', JSON.stringify(cart));
            settings['data-quotecallbackurl'] = quoteCallbackUrl.href;

            const failureUrl = new URL(settings['data-failureurl']);
            failureUrl.searchParams.append('type', 'cart');
            settings['data-failureurl'] = failureUrl.href;

            // Create div element
            let div = document.createElement('div');
            div.classList.add('payever-widget-finexp');
            for (const [key, value] of Object.entries(settings)) {
                div.setAttribute(key, value);
            }

            let self = this;
            $(document).ready(function () {
                self.waitShoppingWidgetReady(function (createOrderBtn) {
                    createOrderBtn.after(div);
                    PayeverPaymentWidgetLoader.init(
                        '.payever-widget-finexp',
                        null,
                        {
                            amount: amount,
                            reference: settings['data-reference'],
                            cart: cart
                        }
                    );
                });
            });
        },

        waitShoppingWidgetReady: function (callback) {
            const intervalId = setInterval(function () {
                let nodeList = document.querySelectorAll('[data-component_name="oro_shopping_list_matrix_to_create_order"]');
                if (nodeList.length > 0) {
                    clearInterval(intervalId);
                    callback(nodeList[0].closest('div'));
                }
            }, 1000);
        },

        onLineItemDelete: function (updateData) {
            console.log('onLineItemDelete', updateData);
        },

        onLineItemUpdate: function(updateData) {
            console.log('onLineItemUpdate', updateData);
        },

        onShoppingListsRefresh: function (updateData) {
            console.log('onShoppingListsRefresh', updateData);
        }
    })

    return FinanceExpressShoppingListComponent;
});
