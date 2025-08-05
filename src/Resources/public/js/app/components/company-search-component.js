define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const AddressAutocomplete = require('payeverpayment/js/app/components/address-autocomplete-component');
    const CountryPicker = require('payeverpayment/js/app/components/country-picker-component');
    const CompanySearchPopup = require('payeverpayment/js/app/components/company-search-popup-component');

    const CompanySearchComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            /**
             * @typedef defaultAddressFields
             * @type {Object}
             * @property {('[name*=street]')} street
             * @property {('[name*=zipcode]')} zipcode
             * @property {('[name*=city]')} city
             * @property {('[name*=countryId]')} country
             * @property {('[name*=countryStateId]')} countryState
             */
            /**
             * @typedef defaultAddressFieldsArray
             * @type {('street', 'zipcode', 'city', 'country', 'countryState')}
             */
            /**
             * Selector to find all default address fields to show/hide them
             * @type defaultAddressFields
             */
            selectorDefaultAddressFields: {
                street: '[name*="[street]"]',
                zipcode: '[name*="postalCode]"]',
                city: '[name*="[city]"]',
                country: '[name*="[country]"]',
                countryState: '[name*="[region]"]',
            },
            /**
             * Specifies the element where the user types his company
             * @type string
             */
            selectorCompanyInput: '[name*="[organization]"]',

            /**
             * Specifies the country iso2 code element
             * @type string
             */
            selectorCountryCode: '[name*=country_selector_code]',

            /**
             * Specifies the company id element
             * @type string
             */
            selectorCompanyId: '[name*="[payever_external_id]"]',

            /**
             * b2b status
             */
            enabled: false,

            /**
             * Type of company search
             * @type string
             */
            companySearchType: 'dropdown',

            /**
             * b2b countries
             */
            b2bCountries: [],

            /**
             * default country
             */
            defaultCountry: 'de'
        },

        /**
         * @inheritDoc
         */
        constructor: function CompanySearchComponent(options) {
            console.log('CompanySearchComponent constructor');
            console.log(options);

            CompanySearchComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            console.log('CompanySearchComponent initialize');

            this.options = _.extend({}, this.options, options);
            console.log(this.options);

            if (!this.options.enabled) {
                console.log('CompanySearchComponent disabled');
                return;
            }

            if (this.options.b2bCountries.length === 0) {
                console.log('CompanySearchComponent disabled, missing b2b countries');
                return;
            }

            let self = this;
            self.onWidgetReady(function (element) {
                const elementId = element.getAttribute('id'),
                    elementClass = element.getAttribute('class'),
                    elementName = element.getAttribute('name');

                const countryOptions = '[]';
                const placeHolder = __('payever.frontend.companyPlaceholder');

                const template = document.createRange().createContextualFragment(`
<div class="payever-company" data-payever-company-search="true" id="payever-company">
    <input data-payever-country-picker="true" data-payever-country-options="${countryOptions}" type="text"
           class="${elementClass}"
           id="${elementId}"
           placeholder="${placeHolder}"
           name="${elementName}"
           value=""
           autocomplete="off"
           data-form-validation-required
           data-form-validation-required-message="Company should not be empty." required="required">
    <div class="payever-company-autocomplete" data-payever-company-autocomplete="true">
        <div class="payever-company-autocomplete-loading">
            <div class="payever-loading-animation">
                <div class="payever-loader"></div>
            </div>
        </div>
        <ul class="payever-company-autocomplete-items"></ul>
    </div>
    <div class="payever-company-autocomplete-items-for-popup"></div>
    <input type="hidden" id="country_selector_code" data-countrycodeinput="1" name="country_selector_code" value="" />
</div>
<input class="payever-buyer-id" type="hidden" name="buyer_id" value="">
        `);

                // Create wrapper
                element.parentNode.insertBefore(template, element);
                element.remove();
                element = document.getElementById(elementId);
                self.el = element;

                self.registerDomEvents();
                self.getAutocomplete().setAddressCallback(self.fillAddress.bind(self));
                self.getAutocomplete().setClearFieldsCallback(self.clearFields.bind(self));

                if (self.options.companySearchType === 'dropdown' || self.options.companySearchType === 'mixed') {
                    self.getCountryPicker();
                }
                if (self.options.companySearchType === 'popup') {
                    const countryInput = self.getCountryElement();
                    const countryElement = self.getCountryPickerElement();
                    if (countryElement) {
                        countryElement.value = countryInput.value;
                    }

                    $(countryInput).on('change', function () {
                        if (countryElement) {
                            countryElement.value = countryInput.value;
                        }
                        self.doSearch(1150);
                    });
                }

                self.getCompanySearchPopup();
            });
        },
        onWidgetReady: function (callback) {
            const self = this;
            const intervalId = setInterval(function () {
                let nodeList = document.querySelectorAll(self.options.selectorCompanyInput);
                if (nodeList.length > 0) {
                    clearInterval(intervalId);
                    callback(nodeList[0]);
                }
            }, 1000);
        },

        /**
         * Add event to get changes from the suggest input field
         * @return void
         */
        registerDomEvents() {
            console.log('registerDomEvents');

            let self = this;
            const companyInput = this.getCompanyInputElement();
            if (companyInput) {
                companyInput.addEventListener('keyup', function () {
                    self.doSearch(1150);
                });
            }
        },

        doSearch(timeout = 0) {
            const companyInput = this.getCompanyInputElement();
            const searchValue = companyInput.value.trim();
            if (searchValue.length < 3) {
                this.currentTimer && window.clearTimeout(this.currentTimer);
                this.getAutocomplete().abortLastApiRequest();
            }

            if (searchValue.length >= 3) {
                if (this.currentTimer && this.getLastSearch != searchValue) {
                    window.clearTimeout(this.currentTimer);
                    this.getAutocomplete().abortLastApiRequest();
                }

                let self = this;
                this.currentTimer = window.setTimeout(function() {
                    self.getAutocomplete().clearSearchItems();
                    self.getAutocomplete().showLoadingIndicator();
                    self.getAutocomplete().show();

                    // Get Country
                    let country = self.getCountryPickerElement() ? self.getCountryPickerElement().value : '';

                    self.getAutocomplete().search(
                        searchValue,
                        country
                    );

                    self.getLastSearch = searchValue;
                    self.currentTimer = null;
                }, timeout);
            }
        },

        /**
         * Returns the autocomplete js plugin
         *
         * @return {AddressAutocompleteComponent}
         */
        getAutocomplete() {
            if (this.autocompleteComponent) {
                return this.autocompleteComponent;
            }

            const autocompleteElement = document.querySelector('[data-payever-company-autocomplete*=true]'),
                autocompleteItemsElement = autocompleteElement.querySelector('.payever-company-autocomplete-items'),
                autocompleteItemsForPopupElement = autocompleteElement.parentNode.querySelector('.payever-company-autocomplete-items-for-popup');

            let self = this;
            this.autocompleteComponent = AddressAutocomplete.prototype;
            this.autocompleteComponent.initialize({
                'element': autocompleteElement,
                'autocompleteItemsElement': autocompleteItemsElement,
                'autocompleteItemsForPopupElement': autocompleteItemsForPopupElement,
                'companySearchType': self.options.companySearchType,
                'searchUrl': self.options.searchUrl,
                'companyRetrieveUrl': self.options.companyRetrieveUrl
            });

            return this.autocompleteComponent;
        },

        getCountryPicker() {
            if (this.countryPickerComponent) {
                return this.countryPickerComponent;
            }

            let self = this;
            this.countryPickerComponent = CountryPicker.prototype;
            this.countryPickerComponent.initialize({
                'element': document.querySelector('[data-payever-country-picker*=true]'),
                'searchCallback': self.doSearch.bind(self),
                'defaultCountry': self.options.defaultCountry.toString().toLocaleLowerCase(),
                'onlyCountries': _.map(self.options.b2bCountries, function(value) { return value.toString().toLocaleLowerCase() }),
            });

            return this.countryPickerComponent;
        },

        getCompanySearchPopup() {
            if (this.countrySearchPopupComponent) {
                return this.countrySearchPopupComponent;
            }

            let self = this;
            this.countrySearchPopupComponent = CompanySearchPopup.prototype;
            this.countrySearchPopupComponent.initialize({
                'form': self.getParentForm(),
                'companySearchType': self.options.companySearchType,
                'searchCallback': self.doSearch.bind(self),
                'setAddressCallback': self.fillAddress.bind(self),
                'companySearchComponent': self
            });

            return this.countrySearchPopupComponent;
        },

        /**
         * Returns the parent form html element
         *
         * @return {HTMLElement}
         */
        getParentForm() {
            return this.el.closest('form');
        },

        /**
         * Returns the country picker element
         * @return {HTMLElement}
         */
        getCountryPickerElement() {
            const form = this.getParentForm();
            if (!form) {
                return null;
            }

            return form.querySelector(this.options.selectorCountryCode);
        },

        /**
         * Returns the CompanyId element
         * @return {HTMLElement}
         */
        getCompanyIdElement() {
            const form = this.getParentForm();
            if (!form) {
                return null;
            }

            return form.querySelector(this.options.selectorCompanyId);
        },

        /**
         * Returns the street element
         * @return {HTMLElement}
         */
        getStreetElement() {
            const form = this.getParentForm();
            if (!form) {
                return null;
            }

            return form.querySelector(this.options.selectorDefaultAddressFields.street);
        },

        /**
         * Returns the postcode element
         * @return {HTMLElement}
         */
        getZipElement() {
            const form = this.getParentForm();
            if (!form) {
                return null;
            }

            return form.querySelector(this.options.selectorDefaultAddressFields.zipcode);
        },

        /**
         * Returns the city element
         * @return {HTMLElement}
         */
        getCityElement() {
            const form = this.getParentForm();
            if (!form) {
                return null;
            }

            return form.querySelector(this.options.selectorDefaultAddressFields.city);
        },

        /**
         * Returns the company element
         * @return {HTMLElement}
         */
        getCompanyInputElement() {
            const form = this.getParentForm();
            if (!form) {
                return null;
            }

            return form.querySelector(this.options.selectorCompanyInput);
        },

        /**
         * Returns the country element
         * @return {HTMLElement}
         */
        getCountryElement() {
            const form = this.getParentForm();
            if (!form) {
                return null;
            }

            return form.querySelector(this.options.selectorDefaultAddressFields.country);
        },

        /**
         * Returns the state element
         * @return {HTMLElement}
         */
        getCountryStateElement() {
            const form = this.getParentForm();
            if (!form) {
                return null;
            }

            return form.querySelector(this.options.selectorDefaultAddressFields.countryState);
        },

        /**
         * Fill address in default orocommerce fields
         * @param company{companyDetails}
         * @return void
         */
        fillAddress(company) {
            const streetElement = this.getStreetElement(),
                zipElement = this.getZipElement(),
                cityElement = this.getCityElement(),
                companyElement = this.getCompanyInputElement(),
                companyIdElement = this.getCompanyIdElement(),
                countryElement = this.getCountryElement(),
                countryState = this.getCountryStateElement();

            if (streetElement) {
                streetElement.value = company.address.street_name + ' ' + company.address.street_number;
                streetElement.dispatchEvent(new Event('change'));
            }

            if (zipElement) {
                zipElement.value = company.address.post_code;
                zipElement.dispatchEvent(new Event('change'));
            }

            if (cityElement) {
                cityElement.value = company.address.city;
                cityElement.dispatchEvent(new Event('change'));
            }

            if (companyElement) {
                companyElement.value = company.name;
                companyElement.dispatchEvent(new Event('change'));
            }

            if (companyIdElement) {
                companyIdElement.value = company.id;
                companyIdElement.dispatchEvent(new Event('change'));
            }

            if (countryElement && countryElement.value !== company.address.country_code) {
                // change event need to be fired because the state field get only visible after selecting a country
                $(countryElement).on('value:changed', function () {
                    console.log('Value changed');

                    let checking = window.setInterval(function () {
                        let $countryState = $(countryState),
                            $options = $countryState.find('option').filter(function(){return $(this).val() !== ''});

                        if (!$countryState.prop('disabled') && $options.length > 0) {
                            window.clearInterval(checking);
                            // Set to the first item
                            $countryState.val($options.first().prop('value')).change();

                            // Set to the selected item
                            if (company.address.state_code && company.address.state_code !== '') {
                                const stateOption = countryState.querySelector(`option[value="${company.address.state_code}"]`);
                                if (stateOption && stateOption.length > 0) {
                                    countryState.value = company.address.state_code;
                                    countryState.dispatchEvent(new Event('change'));
                                }
                            }
                        }
                    }, 500);
                });

                countryElement.value = company.address.country_code;
                countryElement.dispatchEvent(new Event('change'));
            }
        },

        clearFields: function () {
            let companyIdElement = this.getCompanyIdElement();
            companyIdElement.value = '';
        }
    });

    return CompanySearchComponent;
});
