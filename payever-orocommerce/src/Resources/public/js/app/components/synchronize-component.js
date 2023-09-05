define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');

    return function(options) {
        const $source = options._sourceElement;
        const clientId = $('input.pe-client-id');
        const clientSecret = $('input.pe-client-secret');
        const businessUuid = $('input.pe-business-uuid');
        const mode = $('select.pe-mode');
        const status = $source.find('.synchronization-status');
        const btn = $source.find('button').first();

        setInterval(function () {
            btn.html('Synchronize').prop('disabled', false);
        }, 1000);

        const onError = function(message) {
            message = message || __('payever.admin.synchronization.error');
            status.removeClass('alert-info')
                .addClass('alert-error')
                .html(message);
        };

        btn.on('click', function() {
            $.getJSON(
                options.synchronizationUrl,
                {
                    clientId: clientId.val(),
                    clientSecret: clientSecret.val(),
                    businessUuid: businessUuid.val(),
                    mode: mode.val()
                },
                function(response) {
                    if (_.isUndefined(response.error)) {
                        status.removeClass('alert-error')
                            .addClass('alert-info')
                            .html(response.message);
                    } else {
                        onError(response.message);
                    }
                }
            ).always(
                function() {
                    status.show();
                }
            ).fail(
                onError
            );
        });
    };
});
