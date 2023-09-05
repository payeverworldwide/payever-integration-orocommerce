define(function(require) {
    'use strict';

    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const PayeverPaymentComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            paymentMethod: null
        },

        /**
         * @inheritDoc
         */
        constructor: function PayeverPaymentComponent(options) {
            console.log('PayeverPaymentComponent constructor');
            console.log(options);

            PayeverPaymentComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            console.log('PayeverPaymentComponent initialize');
            console.log(options);

            this.options = _.extend({}, this.options, options);
            mediator.on('checkout:place-order:response', this.handleSubmit, this);
        },

        /**
         * @param {Object} eventData
         */
        handleSubmit: function(eventData) {
            console.log('PayeverPaymentComponent console');
            console.log(eventData);
            console.log(this.options.paymentMethod);

            if (eventData.responseData.paymentMethod === this.options.paymentMethod) {
                eventData.stopped = true;
                if (!eventData.responseData.purchaseRedirectUrl) {
                    mediator.execute('redirectTo', {url: eventData.responseData.errorUrl}, {redirect: true});
                    return;
                }

                window.location = eventData.responseData.purchaseRedirectUrl;
            }
        },

        dispose: function() {
            console.log('PayeverPaymentComponent dispose');
            if (this.disposed) {
                return;
            }
            console.log('PayeverPaymentComponent dispose 1');
            mediator.off('checkout:place-order:response', this.handleSubmit, this);

            console.log('PayeverPaymentComponent dispose 2');
            PayeverPaymentComponent.__super__.dispose.call(this);
        }
    });

    return PayeverPaymentComponent;
});
