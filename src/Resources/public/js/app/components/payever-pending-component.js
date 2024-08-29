define([
    'oroui/js/app/components/base/component',
    'jquery'
], function (BaseComponent, $) {
    'use strict';

    return BaseComponent.extend({
        initialize: function () {
            this.getOrderUpdateStatus();
        },

        getOrderUpdateStatus: function () {
            let self = this;
            $.ajax({
                url: api_order_update_status,
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response && response.url) {
                        window.location.href = response.url;
                    } else {
                        setTimeout(function() {
                            self.getOrderUpdateStatus();
                        }, 10000);
                    }
                },
                error: function (xhr, status, error) {
                    document.getElementById('message').innerText = "Error: ". error;
                }
            });
        },
    });
});