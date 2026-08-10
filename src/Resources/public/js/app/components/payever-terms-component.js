define([
    'oroui/js/app/components/base/component',
    'jquery'
], function (BaseComponent, $) {
    'use strict';

    return BaseComponent.extend({
        initialize: function (options) {
            var script = document.createElement('script');
            script.src = options.settings.terms_js;
            script.type = 'module';
            script.onload = async function () {
                for (const item of options.settings.data) {
                    const terms = await createTerms(item.env, item.payment);
                    terms.mount('.container-terms-' + item.payment_key);
                }
            };
            document.head.appendChild(script);
        },
    });
});
