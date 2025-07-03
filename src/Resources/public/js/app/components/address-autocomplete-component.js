define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const AddressAutocompleteComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            /**
             * @type string|null
             */
            searchUrl: null,

            /**
             * @type string|null
             */
            companyRetrieveUrl: null,

            /**
             * @type Node
             */
            element: null,

            /**
             * This elements will contain single suggestions.
             * @type Node
             */
            autocompleteItemsElement: null,

            /**
             * @type Node
             */
            autocompleteItemsForPopupElement: null,

            /**
             * Type of company search
             * @type string
             */
            companySearchType: 'dropdown',

            /**
             * Selector to find the loading indicator
             * @type string
             */
            selectorLoadingIndicator: '.payever-company-autocomplete-loading',

            invalidCompanyId: '0000000000',
        },

        /**
         * @inheritDoc
         */
        constructor: function AddressAutocompleteComponent(options) {
            AddressAutocompleteComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);
        },

        /**
         * Set the address callback. Will be fired if the user select a specific address
         *
         * @param addressCallback{function(addressCallback)}
         */
        setAddressCallback(addressCallback) {
            this.addressCallback = addressCallback;
        },

        /**
         * Set the clear fields callback
         *
         * @param clearFieldsCallback{function()}
         */
        setClearFieldsCallback(clearFieldsCallback) {
            this.clearFieldsCallback = clearFieldsCallback;
        },

        /**
         * Start the search for an address
         *
         * @param needle{string}
         * @param country{string}
         */
        search(needle, country) {
            let self = this;
            this.abortLastApiRequest();

            this.searchTimeoutId = window.setTimeout(() => {
                // prepare autocomplete dropdown for new search
                this.clearSearchItems();
                this.showLoadingIndicator();
                this.show();

                // fire request to api
                this.showLoadingIndicator.bind(this);
                this.lastRequest = $.getJSON(
                    self.options.searchUrl,
                    {
                        term: needle,
                        country: country
                    },
                    function(response) {
                        const firstFiveCompanies = response.results.slice(0, 5);

                        if (!firstFiveCompanies.length) {
                            this.clearFieldsCallback && this.clearFieldsCallback();

                            return;
                        }

                        firstFiveCompanies.forEach(function(value) {
                            if (self._isDropdown()) {
                                self.addSearchItem(value);
                            }

                            if (self._isPopup()) {
                                self.addSearchItemForPopup(value);
                            }
                        });

                        this.clearFieldsCallback && this.clearFieldsCallback();
                    }
                ).always(self.hideLoadingIndicator.bind(self));
            }, 50);
        },

        /**
         * Retrieve company details
         *
         * @param item{searchResultItem}
         */
        retrieveCompany(item) {
            const me = this;
            this.abortLastApiRequest();

            this.searchTimeoutId = window.setTimeout(() => {
                // prepare autocomplete dropdown for new search
                this.clearSearchItems();
                this.showLoadingIndicator();
                this.show();

                // fire request to api
                this.showLoadingIndicator.bind(this);
                this.lastRequest = $.getJSON(
                    me.options.companyRetrieveUrl,
                    {
                        term: item.company_identifier.id_value,
                        country: item.address.country_code,
                        type: item.company_identifier.id_type_code
                    },
                    function(response) {
                        if (Array.isArray(response.results) && response.results.length > 0) {
                            item = response.results.shift();
                        }
                        me.addressCallback && me.addressCallback(item);
                        me.hide();
                    }
                ).always(me.hideLoadingIndicator.bind(me));
            }, 50);
        },

        /**
         * Abort a running search
         */
        abortLastApiRequest() {
            // clear the input timeout
            clearTimeout(this.searchTimeoutId);

            // stop running api requests
            if (this.lastRequest) {
                this.lastRequest.abort();
            }
        },

        /**
         * Add a search item to the autocomplete dropdown. The item contains one of the following keys:
         *
         * @param item{searchResultItem}
         * @return void
         */
        addSearchItem(item) {
            const typeAsClass = item.Type === 'Address' ? 'is-single' : 'is-group';
            const itemId = 'search_company_' + item.id;

            // Add item to DOM
            const template = document.createRange().createContextualFragment(`
            <li class="payever-company-autocomplete-item ${typeAsClass}" id="${itemId}"> 
                <a class="payever-company-autocomplete-item-link ${typeAsClass}" href="#">
                    ${item.name},
                    <span class="payever-company-autocomplete-item-link-secondary-text"> -
                        ${item.address.street_name} ${item.address.street_number}, ${item.address.post_code}, ${item.address.city} 
                    </span>
                </a>
            </li>
        `);
            this.options.autocompleteItemsElement.append(template);
            const itemElement = this.options.autocompleteItemsElement.querySelector('#' + itemId);

            // Add click event in the added item
            let self = this;
            itemElement.addEventListener('click', function (event) {
                event.preventDefault();
                self._handleSearchItemClick(item);
            })
        },

        addSearchItemForPopup(item) {
            const itemId = 'search_company_for_popup_' + item.id;
            const itemJson = encodeURIComponent(JSON.stringify(item));

            // Add item to DOM
            const template = document.createRange().createContextualFragment(`
            <label class="download-links-wrapper">
                <div class="download-links">
                    <div class="download-buttons">
                        <input type="radio"
                               id="${itemId}"
                               name="search_company_id"
                               value="${item.id}"
                               class="radio-btn">
                        <div class="payever-mark"></div>

                        <div class="payever-company-address-block">
                            <input type="hidden" id="company_search_item_${item.id}" name="company_search_item_${item.id}" value="${itemJson}" />
                            <div class="payever-company-title">${item.name}</div>
                            <div class="payever-company-address">
                                ${item.address.street_name} ${item.address.street_number}, ${item.address.post_code}, ${item.address.city}
                            </div>
                        </div>
                    </div>
                </div>
            </label>
        `);
            this.options.autocompleteItemsForPopupElement.append(template);
        },

        /**
         * Handle a click on a search item in the autocomplete dropdown
         * @param item{searchResultItem}
         * @private
         */
        _handleSearchItemClick(item) {
            if (this.options.invalidCompanyId === item.id && typeof item.company_identifier !== "undefined") {
                this.retrieveCompany(item);

                return;
            }
            this.addressCallback && this.addressCallback(item);
            this.hide();
        },

        /**
         * Remove all search result items
         */
        clearSearchItems() {
            let elements = this.options.autocompleteItemsElement.querySelectorAll('li');
            if (elements) {
                elements.forEach(element => element.remove());
            }

            let elementsForPopup = this.options.autocompleteItemsForPopupElement.querySelectorAll('label');
            if (elementsForPopup) {
                elements.forEach(element => element.remove());
            }
        },

        /**
         * Show autocomplete dropdown
         * @return void
         */
        show() {
            if (this._isDropdown()) {
                this.options.element.style.display = 'block';
            }
        },

        /**
         * Hide autocomplete dropdown
         * @return void
         */
        hide() {
            this.options.element.style.display = 'none';
        },

        /**
         * Show/Hide the loading indicator
         */
        showLoadingIndicator() {
            if (this._isDropdown()) {
                const indicator = this.options.element.querySelector(this.options.selectorLoadingIndicator);
                if (indicator) {
                    indicator.style.display = 'block';
                }
            }

            const formButton = this.options.element.closest('form').querySelector('[type*=submit]');
            if (formButton) {
                const display = formButton.style.display;
                formButton.style.display = 'none';

                const parentBlock = formButton.closest('div');
                let loader = parentBlock.querySelector('.fake-button-for-animation');
                if (!loader) {
                    loader = formButton.cloneNode(true);
                    loader.innerText = '';
                    const animation = document.createRange().createContextualFragment(`
                        <div class="payever-loading-animation"><div class="payever-loader-white"></div></div>
                    `);
                    loader.append(animation);
                    loader.classList.add('fake-button-for-animation');
                    loader.style.display = display;
                    loader.disabled = true;

                    formButton.after(loader);
                }
            }
        },

        hideLoadingIndicator() {
            const indicator = this.options.element.querySelector(this.options.selectorLoadingIndicator);
            if (indicator) {
                indicator.style.display = 'none';
            }

            const formButton = this.options.element.closest('form').querySelector('[type*=submit]');
            if (formButton) {
                let display = 'block';
                const parentBlock = formButton.closest('div');
                const loader = parentBlock.querySelector('.fake-button-for-animation');
                if (loader) {
                    display = loader.style.display;
                    loader.remove();
                }

                formButton.style.display = display;
            }
        },

        /**
         * Checks if company search popup type is active
         */
        _isPopup() {
            return (this.options.companySearchType === 'popup' || this.options.companySearchType === 'mixed');
        },

        /**
         * Checks if company search dropdown type is active
         */
        _isDropdown() {
            return (this.options.companySearchType === 'dropdown' || this.options.companySearchType === 'mixed');
        }
    });

    return AddressAutocompleteComponent;
});
