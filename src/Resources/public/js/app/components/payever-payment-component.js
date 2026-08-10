define(function (require) {
    'use strict';

    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const PayeverPaymentComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            paymentUrl: null,
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
        initialize: function (options) {
            console.log('PayeverPaymentComponent initialize');
            console.log(options);

            this.options = _.extend({}, this.options, options);
            mediator.on('checkout:before-submit', this.handleBeforeSubmit, this);
            mediator.on('checkout:place-order:response', this.handleSubmit, this);
        },

        /**
         * @param {Object} eventData
         */
        handleSubmit: function (eventData) {
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

        /**
         * @param {Object} eventData
         */
        handleBeforeSubmit: function (eventData) {
            eventData.stopped = true;
            mediator.execute('showLoading');

            $.ajax(this.prepareAjaxData())
                .done(this.onSuccess.bind(this))
                .fail(this.onFail.bind(this));
        },

        /**
         * @returns {Object}
         */
        prepareAjaxData: function () {
            return {
                method: 'POST',
                url: this.options.paymentUrl,
                errorHandlerMessage: false,
                contentType: false,
                processData: false,
            };
        },

        onSuccess: function (response) {
            mediator.execute('hideLoading');

            if (response.result === 'error') {
                mediator.execute('showFlashMessage', 'error', response.message);
                return;
            }

            window.location = response.redirectUrl;
        },

        onFail: function () {
            mediator.execute('hideLoading');
            mediator.execute('showFlashMessage', 'error', 'Could not perform transition');
        },

        dispose: function () {
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
