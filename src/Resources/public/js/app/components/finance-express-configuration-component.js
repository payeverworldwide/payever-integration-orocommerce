define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');
    const Select2 = require('jquery.select2');
    require('oroui/js/select2-l10n');
    const mediator = require('oroui/js/mediator');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');


    return function(options) {
        const $source = options._sourceElement;
        const element = $(options.target);
        const section = $source.closest('.control-group-wrapper');
        let loadingMaskView = new LoadingMaskView({container: section});

        mediator.execute('showLoading');
        loadingMaskView.show();

        $.getJSON(
            options.url,
            {},
            function(response) {
                mediator.execute('hideLoading');
                loadingMaskView.hide();
                if (_.isUndefined(response.success) || !response.success) {
                    mediator.execute('showFlashMessage', 'error', response.message);
                    return;
                }

                element.select2({
                    query: function (query){
                        var data = {
                            results: response.result
                        };

                        query.callback(data);
                    }
                });
                element.on('change', function () {
                    // Save selected value
                    loadingMaskView.show();

                    $.post(
                        options.saveUrl,
                        {
                            'widgetId': element.val(),
                        },
                        function () {
                            loadingMaskView.hide();
                        }
                    );
                });

                // Set saved value
                setTimeout(function () {
                    element.select2('data', {id: response.widget_id, text: response.widget_title});
                }, 500);
            }
        ).fail(function() {
            mediator.execute('hideLoading');
            loadingMaskView.hide();
            mediator.execute('showFlashMessage', 'error', 'Could not perform transition');
        });
    };
});
