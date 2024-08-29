define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const Modal = require('oroui/js/modal');

    const CompanySearchPopupComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            /**
             * @type Node
             */
            form: null,

            /**
             * @type string
             */
            companySearchType: 'popup',

            /**
             * Specifies the element where the user types his company
             * @type string
             */
            selectorCompanyInput: '[name*=company]',

            selectorCountryState: '[name*="oro_workflow_transition[billing_address][region]"]',

            /**
             * Specifies the element where the user chooses his company from radio button
             * @type string
             */
            selectorCompanySearchRadio: 'input[name="search_company_id"]',

            /**
             * Specifies the checked company element
             * @type string
             */
            selectorCompanySearchCheckedRadio: 'input[name="search_company_id"]:checked',

            /**
             * Specifies the element for applying company
             * @type string
             */
            selectorApplyCompany: '[name*=apply_company]',

            /**
             * Specifies the element for discart button
             * @type string
             */
            selectorDiscardCompany: '[name*=discard_company]',

            /**
             * @type function
             */
            setAddressCallback: null,
        },

        /**
         * Modal
         */
        modal: null,

        /**
         * Form submission possibility flag
         */
        formSubmissionAllowed: null,

        /**
         * @inheritDoc
         */
        constructor: function CompanySearchPopupComponent(options) {
            console.log('CompanySearchPopupComponent constructor');
            console.log(options);

            CompanySearchPopupComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            console.log('CompanySearchPopupComponent initialize');
            console.log(options);

            this.options = _.extend({}, this.options, options);

            if (this.options.companySearchType === 'popup' || this.options.companySearchType === 'mixed') {
                this.registerDomEvents();
            }
        },

        registerDomEvents: function () {
            console.log('registerDomEvents');
            $(this.options.form).bindFirst('submit', this.openModal.bind(this))
        },

        /**
         * Returns the company search result element for popup
         *
         * @return {HTMLElement}
         */
        getCompanySearchContentElement() {
            return this.options.form.querySelector('.payever-company-autocomplete-items-for-popup');
        },

        /**
         * Returns the company search result html content for popup
         */
        getCompanySearchContentForPopup() {
            return this.getCompanySearchContentElement().innerHTML.trim();
        },

        /**
         * Sets the company search result html content for popup
         */
        setCompanySearchContentForPopup(html) {
            this.getCompanySearchContentElement().innerHTML = html;
        },

        openModal(event) {
            console.log('openModal');
            if (this.formSubmissionAllowed) {
                console.log('formSubmissionAllowed')
                return;
            }

            // Country region must be filled
            const regionField = this.options.form.querySelector(this.options.selectorCountryState);
            if (regionField && regionField.value === '') {
                console.log('State is required');
                event.preventDefault();
                event.stopImmediatePropagation();
                $(this.options.form).validate();
                return;
            }

            const companies = this.getCompanySearchContentForPopup();
            if (!companies) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            // Disable Submit button on the form
            let self = this;
            let button = this.options.form.querySelector('button');
            if (button) {
                button.disabled = true;
            }

            const content = `
                <div class="payever-company-input">` + companies + `</div>
                    <br/>
                    <div class="apply-button-parent">
                        <div class="apply-button">
                            <button name="apply_company" class="apply-options">
                                <div class="apply">` + __('payever.company_search_popup.apply') + `</div>
                            </button>
                            <button name="discard_company" class="apply-options1">
                                <div class="i-did-not-container">
                                    <span>` + __('payever.company_search_popup.discard') + `</span>
                                </div>
                            </button>
                        </div>
                        <div class="payever-icon"></div>
                    </div>
                </div>
            `;

            this.modal = new Modal({
                autoRender: true,
                content: content,
                title: __('payever.company_search_popup.title'),
                allowOk: false,
                allowCancel: false,
                allowClose: false,
                className: 'modal oro-modal-normal modal--fullscreen-small-device',
                disposeOnHidden: false,
            });

            this.setCompanySearchContentForPopup('');

            self.listenTo(this.modal, {
                shown: self.onModalShown.bind(self, this.modal),
                close: self.onModalClose.bind(self, this.modal)
            });

            this.modal.open();
        },

        onModalShown: function() {
            console.log('onModalShown');
            let modal = this.modal.el;
            let self = this;

            const firstRadio = modal.querySelector(this.options.selectorCompanySearchRadio);
            if (firstRadio) {
                firstRadio.checked = true;
            }

            const applyCompanyElement = modal.querySelector(this.options.selectorApplyCompany);
            applyCompanyElement.addEventListener('click', function () {
                const selectedRadio = modal.querySelector(self.options.selectorCompanySearchCheckedRadio);
                if (selectedRadio) {
                    const selectedCompanyElement = modal.querySelector('[name*=company_search_item_' + selectedRadio.value + ']');
                    const companyItem = JSON.parse(decodeURIComponent(selectedCompanyElement.value));
                    self.options.setAddressCallback && self.options.setAddressCallback(companyItem);
                    self.setCompanySearchContentForPopup('');
                }

                setTimeout(function () {
                    self.modal.close();
                    const $form = $(self.options.form);
                    $form.validate();
                    if ($form.valid()) {
                        self.formSubmissionAllowed = true;
                        self.options.form.submit();
                    }
                }, 3000);
            });

            const discardCompanyElement = modal.querySelector(this.options.selectorDiscardCompany);
            discardCompanyElement.addEventListener('click', function () {
                self.modal.close();
                const $form = $(self.options.form);
                $form.validate();
                if ($form.valid()) {
                    self.formSubmissionAllowed = true;
                    self.options.form.submit();
                }
            });
        },
        onModalClose: function () {
            console.log('onModalClose');
            let button = this.options.form.querySelector('button');
            if (button) {
                button.disabled = false;
            }
        },
    });

    return CompanySearchPopupComponent;
});
