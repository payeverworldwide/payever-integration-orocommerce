define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const CountryPickerComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            /**
             * @type Node
             */
            element: null,

            // Default country
            defaultCountry: '',
            // Position the selected flag inside or outside of the input
            defaultStyling: "inside",
            // Display only these countries
            onlyCountries: ["us", "gb", "de"],
            // The countries at the top of the list. Defaults to United States and United Kingdom
            preferredCountries: [],
            // localized country names e.g. { 'de': 'Deutschland' }
            localizedCountries: null,
            // Set the dropdown's width to be the same as the input. This is automatically enabled for small screens.
            responsiveDropdown: (document.querySelector('html').clientWidth < 768),
            // all countries
            allCountries: [ {
                name: "Afghanistan (‫افغانستان‬‎)",
                iso2: "af"
            }, {
                name: "Åland Islands (Åland)",
                iso2: "ax"
            }, {
                name: "Albania (Shqipëri)",
                iso2: "al"
            }, {
                name: "Algeria (‫الجزائر‬‎)",
                iso2: "dz"
            }, {
                name: "American Samoa",
                iso2: "as"
            }, {
                name: "Andorra",
                iso2: "ad"
            }, {
                name: "Angola",
                iso2: "ao"
            }, {
                name: "Anguilla",
                iso2: "ai"
            }, {
                name: "Antarctica",
                iso2: "aq"
            }, {
                name: "Antigua and Barbuda",
                iso2: "ag"
            }, {
                name: "Argentina",
                iso2: "ar"
            }, {
                name: "Armenia (Հայաստան)",
                iso2: "am"
            }, {
                name: "Aruba",
                iso2: "aw"
            }, {
                name: "Australia",
                iso2: "au"
            }, {
                name: "Austria (Österreich)",
                iso2: "at"
            }, {
                name: "Azerbaijan (Azərbaycan)",
                iso2: "az"
            }, {
                name: "Bahamas",
                iso2: "bs"
            }, {
                name: "Bahrain (‫البحرين‬‎)",
                iso2: "bh"
            }, {
                name: "Bangladesh (বাংলাদেশ)",
                iso2: "bd"
            }, {
                name: "Barbados",
                iso2: "bb"
            }, {
                name: "Belarus (Беларусь)",
                iso2: "by"
            }, {
                name: "Belgium (België)",
                iso2: "be"
            }, {
                name: "Belize",
                iso2: "bz"
            }, {
                name: "Benin (Bénin)",
                iso2: "bj"
            }, {
                name: "Bermuda",
                iso2: "bm"
            }, {
                name: "Bhutan (འབྲུག)",
                iso2: "bt"
            }, {
                name: "Bolivia",
                iso2: "bo"
            }, {
                name: "Bosnia and Herzegovina (Босна и Херцеговина)",
                iso2: "ba"
            }, {
                name: "Botswana",
                iso2: "bw"
            }, {
                name: "Bouvet Island (Bouvetøya)",
                iso2: "bv"
            }, {
                name: "Brazil (Brasil)",
                iso2: "br"
            }, {
                name: "British Indian Ocean Territory",
                iso2: "io"
            }, {
                name: "British Virgin Islands",
                iso2: "vg"
            }, {
                name: "Brunei",
                iso2: "bn"
            }, {
                name: "Bulgaria (България)",
                iso2: "bg"
            }, {
                name: "Burkina Faso",
                iso2: "bf"
            }, {
                name: "Burundi (Uburundi)",
                iso2: "bi"
            }, {
                name: "Cambodia (កម្ពុជា)",
                iso2: "kh"
            }, {
                name: "Cameroon (Cameroun)",
                iso2: "cm"
            }, {
                name: "Canada",
                iso2: "ca"
            }, {
                name: "Cape Verde (Kabu Verdi)",
                iso2: "cv"
            }, {
                name: "Caribbean Netherlands",
                iso2: "bq"
            }, {
                name: "Cayman Islands",
                iso2: "ky"
            }, {
                name: "Central African Republic (République Centrafricaine)",
                iso2: "cf"
            }, {
                name: "Chad (Tchad)",
                iso2: "td"
            }, {
                name: "Chile",
                iso2: "cl"
            }, {
                name: "China (中国)",
                iso2: "cn"
            }, {
                name: "Christmas Island",
                iso2: "cx"
            }, {
                name: "Cocos (Keeling) Islands (Kepulauan Cocos (Keeling))",
                iso2: "cc"
            }, {
                name: "Colombia",
                iso2: "co"
            }, {
                name: "Comoros (‫جزر القمر‬‎)",
                iso2: "km"
            }, {
                name: "Congo (DRC) (Jamhuri ya Kidemokrasia ya Kongo)",
                iso2: "cd"
            }, {
                name: "Congo (Republic) (Congo-Brazzaville)",
                iso2: "cg"
            }, {
                name: "Cook Islands",
                iso2: "ck"
            }, {
                name: "Costa Rica",
                iso2: "cr"
            }, {
                name: "Côte d’Ivoire",
                iso2: "ci"
            }, {
                name: "Croatia (Hrvatska)",
                iso2: "hr"
            }, {
                name: "Cuba",
                iso2: "cu"
            }, {
                name: "Curaçao",
                iso2: "cw"
            }, {
                name: "Cyprus (Κύπρος)",
                iso2: "cy"
            }, {
                name: "Czech Republic (Česká republika)",
                iso2: "cz"
            }, {
                name: "Denmark (Danmark)",
                iso2: "dk"
            }, {
                name: "Djibouti",
                iso2: "dj"
            }, {
                name: "Dominica",
                iso2: "dm"
            }, {
                name: "Dominican Republic (República Dominicana)",
                iso2: "do"
            }, {
                name: "Ecuador",
                iso2: "ec"
            }, {
                name: "Egypt (‫مصر‬‎)",
                iso2: "eg"
            }, {
                name: "El Salvador",
                iso2: "sv"
            }, {
                name: "Equatorial Guinea (Guinea Ecuatorial)",
                iso2: "gq"
            }, {
                name: "Eritrea",
                iso2: "er"
            }, {
                name: "Estonia (Eesti)",
                iso2: "ee"
            }, {
                name: "Ethiopia",
                iso2: "et"
            }, {
                name: "Falkland Islands (Islas Malvinas)",
                iso2: "fk"
            }, {
                name: "Faroe Islands (Føroyar)",
                iso2: "fo"
            }, {
                name: "Fiji",
                iso2: "fj"
            }, {
                name: "Finland (Suomi)",
                iso2: "fi"
            }, {
                name: "France",
                iso2: "fr"
            }, {
                name: "French Guiana (Guyane française)",
                iso2: "gf"
            }, {
                name: "French Polynesia (Polynésie française)",
                iso2: "pf"
            }, {
                name: "French Southern Territories (Terres australes françaises)",
                iso2: "tf"
            }, {
                name: "Gabon",
                iso2: "ga"
            }, {
                name: "Gambia",
                iso2: "gm"
            }, {
                name: "Georgia (საქართველო)",
                iso2: "ge"
            }, {
                name: "Germany (Deutschland)",
                iso2: "de"
            }, {
                name: "Ghana (Gaana)",
                iso2: "gh"
            }, {
                name: "Gibraltar",
                iso2: "gi"
            }, {
                name: "Greece (Ελλάδα)",
                iso2: "gr"
            }, {
                name: "Greenland (Kalaallit Nunaat)",
                iso2: "gl"
            }, {
                name: "Grenada",
                iso2: "gd"
            }, {
                name: "Guadeloupe",
                iso2: "gp"
            }, {
                name: "Guam",
                iso2: "gu"
            }, {
                name: "Guatemala",
                iso2: "gt"
            }, {
                name: "Guernsey",
                iso2: "gg"
            }, {
                name: "Guinea (Guinée)",
                iso2: "gn"
            }, {
                name: "Guinea-Bissau (Guiné Bissau)",
                iso2: "gw"
            }, {
                name: "Guyana",
                iso2: "gy"
            }, {
                name: "Haiti",
                iso2: "ht"
            }, {
                name: "Heard Island and Mcdonald Islands",
                iso2: "hm"
            }, {
                name: "Honduras",
                iso2: "hn"
            }, {
                name: "Hong Kong (香港)",
                iso2: "hk"
            }, {
                name: "Hungary (Magyarország)",
                iso2: "hu"
            }, {
                name: "Iceland (Ísland)",
                iso2: "is"
            }, {
                name: "India (भारत)",
                iso2: "in"
            }, {
                name: "Indonesia",
                iso2: "id"
            }, {
                name: "Iran (‫ایران‬‎)",
                iso2: "ir"
            }, {
                name: "Iraq (‫العراق‬‎)",
                iso2: "iq"
            }, {
                name: "Ireland",
                iso2: "ie"
            }, {
                name: "Isle of Man",
                iso2: "im"
            }, {
                name: "Israel (‫ישראל‬‎)",
                iso2: "il"
            }, {
                name: "Italy (Italia)",
                iso2: "it"
            }, {
                name: "Jamaica",
                iso2: "jm"
            }, {
                name: "Japan (日本)",
                iso2: "jp"
            }, {
                name: "Jersey",
                iso2: "je"
            }, {
                name: "Jordan (‫الأردن‬‎)",
                iso2: "jo"
            }, {
                name: "Kazakhstan (Казахстан)",
                iso2: "kz"
            }, {
                name: "Kenya",
                iso2: "ke"
            }, {
                name: "Kiribati",
                iso2: "ki"
            }, {
                name: "Kosovo (Kosovë)",
                iso2: "xk"
            }, {
                name: "Kuwait (‫الكويت‬‎)",
                iso2: "kw"
            }, {
                name: "Kyrgyzstan (Кыргызстан)",
                iso2: "kg"
            }, {
                name: "Laos (ລາວ)",
                iso2: "la"
            }, {
                name: "Latvia (Latvija)",
                iso2: "lv"
            }, {
                name: "Lebanon (‫لبنان‬‎)",
                iso2: "lb"
            }, {
                name: "Lesotho",
                iso2: "ls"
            }, {
                name: "Liberia",
                iso2: "lr"
            }, {
                name: "Libya (‫ليبيا‬‎)",
                iso2: "ly"
            }, {
                name: "Liechtenstein",
                iso2: "li"
            }, {
                name: "Lithuania (Lietuva)",
                iso2: "lt"
            }, {
                name: "Luxembourg",
                iso2: "lu"
            }, {
                name: "Macau (澳門)",
                iso2: "mo"
            }, {
                name: "Macedonia (FYROM) (Македонија)",
                iso2: "mk"
            }, {
                name: "Madagascar (Madagasikara)",
                iso2: "mg"
            }, {
                name: "Malawi",
                iso2: "mw"
            }, {
                name: "Malaysia",
                iso2: "my"
            }, {
                name: "Maldives",
                iso2: "mv"
            }, {
                name: "Mali",
                iso2: "ml"
            }, {
                name: "Malta",
                iso2: "mt"
            }, {
                name: "Marshall Islands",
                iso2: "mh"
            }, {
                name: "Martinique",
                iso2: "mq"
            }, {
                name: "Mauritania (‫موريتانيا‬‎)",
                iso2: "mr"
            }, {
                name: "Mauritius (Moris)",
                iso2: "mu"
            }, {
                name: "Mayotte",
                iso2: "yt"
            }, {
                name: "Mexico (México)",
                iso2: "mx"
            }, {
                name: "Micronesia",
                iso2: "fm"
            }, {
                name: "Moldova (Republica Moldova)",
                iso2: "md"
            }, {
                name: "Monaco",
                iso2: "mc"
            }, {
                name: "Mongolia (Монгол)",
                iso2: "mn"
            }, {
                name: "Montenegro (Crna Gora)",
                iso2: "me"
            }, {
                name: "Montserrat",
                iso2: "ms"
            }, {
                name: "Morocco (‫المغرب‬‎)",
                iso2: "ma"
            }, {
                name: "Mozambique (Moçambique)",
                iso2: "mz"
            }, {
                name: "Myanmar (Burma) (မြန်မာ)",
                iso2: "mm"
            }, {
                name: "Namibia (Namibië)",
                iso2: "na"
            }, {
                name: "Nauru",
                iso2: "nr"
            }, {
                name: "Nepal (नेपाल)",
                iso2: "np"
            }, {
                name: "Netherlands (Nederland)",
                iso2: "nl"
            }, {
                name: "New Caledonia (Nouvelle-Calédonie)",
                iso2: "nc"
            }, {
                name: "New Zealand",
                iso2: "nz"
            }, {
                name: "Nicaragua",
                iso2: "ni"
            }, {
                name: "Niger (Nijar)",
                iso2: "ne"
            }, {
                name: "Nigeria",
                iso2: "ng"
            }, {
                name: "Niue",
                iso2: "nu"
            }, {
                name: "Norfolk Island",
                iso2: "nf"
            }, {
                name: "North Korea (조선 민주주의 인민 공화국)",
                iso2: "kp"
            }, {
                name: "Northern Mariana Islands",
                iso2: "mp"
            }, {
                name: "Norway (Norge)",
                iso2: "no"
            }, {
                name: "Oman (‫عُمان‬‎)",
                iso2: "om"
            }, {
                name: "Pakistan (‫پاکستان‬‎)",
                iso2: "pk"
            }, {
                name: "Palau",
                iso2: "pw"
            }, {
                name: "Palestine (‫فلسطين‬‎)",
                iso2: "ps"
            }, {
                name: "Panama (Panamá)",
                iso2: "pa"
            }, {
                name: "Papua New Guinea",
                iso2: "pg"
            }, {
                name: "Paraguay",
                iso2: "py"
            }, {
                name: "Peru (Perú)",
                iso2: "pe"
            }, {
                name: "Philippines",
                iso2: "ph"
            }, {
                name: "Pitcairn Islands",
                iso2: "pn"
            }, {
                name: "Poland (Polska)",
                iso2: "pl"
            }, {
                name: "Portugal",
                iso2: "pt"
            }, {
                name: "Puerto Rico",
                iso2: "pr"
            }, {
                name: "Qatar (‫قطر‬‎)",
                iso2: "qa"
            }, {
                name: "Réunion (La Réunion)",
                iso2: "re"
            }, {
                name: "Romania (România)",
                iso2: "ro"
            }, {
                name: "Russia (Россия)",
                iso2: "ru"
            }, {
                name: "Rwanda",
                iso2: "rw"
            }, {
                name: "Saint Barthélemy (Saint-Barthélemy)",
                iso2: "bl"
            }, {
                name: "Saint Helena",
                iso2: "sh"
            }, {
                name: "Saint Kitts and Nevis",
                iso2: "kn"
            }, {
                name: "Saint Lucia",
                iso2: "lc"
            }, {
                name: "Saint Martin (Saint-Martin (partie française))",
                iso2: "mf"
            }, {
                name: "Saint Pierre and Miquelon (Saint-Pierre-et-Miquelon)",
                iso2: "pm"
            }, {
                name: "Saint Vincent and the Grenadines",
                iso2: "vc"
            }, {
                name: "Samoa",
                iso2: "ws"
            }, {
                name: "San Marino",
                iso2: "sm"
            }, {
                name: "São Tomé and Príncipe (São Tomé e Príncipe)",
                iso2: "st"
            }, {
                name: "Saudi Arabia (‫المملكة العربية السعودية‬‎)",
                iso2: "sa"
            }, {
                name: "Senegal (Sénégal)",
                iso2: "sn"
            }, {
                name: "Serbia (Србија)",
                iso2: "rs"
            }, {
                name: "Seychelles",
                iso2: "sc"
            }, {
                name: "Sierra Leone",
                iso2: "sl"
            }, {
                name: "Singapore",
                iso2: "sg"
            }, {
                name: "Sint Maarten",
                iso2: "sx"
            }, {
                name: "Slovakia (Slovensko)",
                iso2: "sk"
            }, {
                name: "Slovenia (Slovenija)",
                iso2: "si"
            }, {
                name: "Solomon Islands",
                iso2: "sb"
            }, {
                name: "Somalia (Soomaaliya)",
                iso2: "so"
            }, {
                name: "South Africa",
                iso2: "za"
            }, {
                name: "South Georgia & South Sandwich Islands",
                iso2: "gs"
            }, {
                name: "South Korea (대한민국)",
                iso2: "kr"
            }, {
                name: "South Sudan (‫جنوب السودان‬‎)",
                iso2: "ss"
            }, {
                name: "Spain (España)",
                iso2: "es"
            }, {
                name: "Sri Lanka (ශ්‍රී ලංකාව)",
                iso2: "lk"
            }, {
                name: "Sudan (‫السودان‬‎)",
                iso2: "sd"
            }, {
                name: "Suriname",
                iso2: "sr"
            }, {
                name: "Svalbard and Jan Mayen (Svalbard og Jan Mayen)",
                iso2: "sj"
            }, {
                name: "Swaziland",
                iso2: "sz"
            }, {
                name: "Sweden (Sverige)",
                iso2: "se"
            }, {
                name: "Switzerland (Schweiz)",
                iso2: "ch"
            }, {
                name: "Syria (‫سوريا‬‎)",
                iso2: "sy"
            }, {
                name: "Taiwan (台灣)",
                iso2: "tw"
            }, {
                name: "Tajikistan",
                iso2: "tj"
            }, {
                name: "Tanzania",
                iso2: "tz"
            }, {
                name: "Thailand (ไทย)",
                iso2: "th"
            }, {
                name: "Timor-Leste",
                iso2: "tl"
            }, {
                name: "Togo",
                iso2: "tg"
            }, {
                name: "Tokelau",
                iso2: "tk"
            }, {
                name: "Tonga",
                iso2: "to"
            }, {
                name: "Trinidad and Tobago",
                iso2: "tt"
            }, {
                name: "Tunisia (‫تونس‬‎)",
                iso2: "tn"
            }, {
                name: "Turkey (Türkiye)",
                iso2: "tr"
            }, {
                name: "Turkmenistan",
                iso2: "tm"
            }, {
                name: "Turks and Caicos Islands",
                iso2: "tc"
            }, {
                name: "Tuvalu",
                iso2: "tv"
            }, {
                name: "Uganda",
                iso2: "ug"
            }, {
                name: "Ukraine (Україна)",
                iso2: "ua"
            }, {
                name: "United Arab Emirates (‫الإمارات العربية المتحدة‬‎)",
                iso2: "ae"
            }, {
                name: "United Kingdom",
                iso2: "gb"
            }, {
                name: "United States",
                iso2: "us"
            }, {
                name: "U.S. Minor Outlying Islands",
                iso2: "um"
            }, {
                name: "U.S. Virgin Islands",
                iso2: "vi"
            }, {
                name: "Uruguay",
                iso2: "uy"
            }, {
                name: "Uzbekistan (Oʻzbekiston)",
                iso2: "uz"
            }, {
                name: "Vanuatu",
                iso2: "vu"
            }, {
                name: "Vatican City (Città del Vaticano)",
                iso2: "va"
            }, {
                name: "Venezuela",
                iso2: "ve"
            }, {
                name: "Vietnam (Việt Nam)",
                iso2: "vn"
            }, {
                name: "Wallis and Futuna",
                iso2: "wf"
            }, {
                name: "Western Sahara (‫الصحراء الغربية‬‎)",
                iso2: "eh"
            }, {
                name: "Yemen (‫اليمن‬‎)",
                iso2: "ye"
            }, {
                name: "Zambia",
                iso2: "zm"
            }, {
                name: "Zimbabwe",
                iso2: "zw"
            } ],
            ns: '.countrySelect1',
            keys: {
                UP: 38,
                DOWN: 40,
                ENTER: 13,
                ESC: 27,
                BACKSPACE: 8,
                PLUS: 43,
                SPACE: 32,
                A: 65,
                Z: 90
            },
            searchCallback: null
        },

        /**
         * @inheritDoc
         */
        constructor: function CountryPickerComponent(options) {
            console.log('CountryPickerComponent constructor');
            console.log(options);

            CountryPickerComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            console.log('CountryPickerComponent initialize');

            this.options = _.extend({}, this.options, options);

            /** @var HTMLInputElement */
            this.countryInput = this.options.element;
            if (!this.countryInput instanceof Node) {
                throw new Error(`Unable to generate country`);
            }

            let self = this;
            self._setInstanceCountryData(function () {
                self._processCountryData();

                // Generate the markup
                self._generateMarkup();

                // Set the initial state of the input value and the selected flag
                self._setInitialState();

                // Start all of the event listeners: input keyup, selectedFlag click
                self._initListeners();

                // Return Deferred object when the auto country is resolved.
                self.autoCountryDeferred = null;

                // Get auto country.
                self._initAutoCountry();

                // Keep track as the user types
                self.typedLetters = '';
            });
        },

        /**
         * Prepares all of the country data, including onlyCountries, preferredCountries and defaultCountry options
         */
        _processCountryData() {
            // set the preferredCountries property
            this._setPreferredCountries();
            // translate countries according to localizedCountries option
            if (this.options.localizedCountries) this._translateCountriesByLocale();
            // sort countries by name
            if (this.options.onlyCountries.length || this.options.localizedCountries) {
                this.countries.sort(this._countryNameSort);
            }
        },

        /**
         * Processes onlyCountries array if present
         * @param callback
         */
        _setInstanceCountryData(callback) {
            const that = this;

            if (this.options.onlyCountries.length) {
                const newCountries = [];
                this.options.onlyCountries.forEach(function (countryCode) {
                    const countryData = that._getCountryData(countryCode, true);
                    if (countryData) {
                        newCountries.push(countryData);
                    }
                });

                this.countries = newCountries;
                callback();
                return;
            }

            callback();
        },

        /**
         * Processes preferred countries - iterate through the preferences, fetching the country data for each one
         */
        _setPreferredCountries() {
            const that = this;
            this.preferredCountries = [];
            this.options.preferredCountries.forEach(function (countryCode) {
                const countryData = that._getCountryData(countryCode, false);
                if (countryData) {
                    that.preferredCountries.push(countryData);
                }
            });
        },

        /**
         * Translates Countries by object literal provided on config
         */
        _translateCountriesByLocale() {
            for (let i = 0; i < this.countries.length; i++) {
                const iso = this.countries[i].iso2.toLowerCase();
                if (this.options.localizedCountries.hasOwnProperty(iso)) {
                    this.countries[i].name = this.options.localizedCountries[iso];
                }
            }
        },

        /**
         * Sorts by country name
         *
         * @param a
         * @param b
         * @returns {number}
         */
        _countryNameSort(a, b) {
            return a.name.localeCompare(b.name);
        },

        /**
         * Generates all of the markup for the plugin: the selected flag overlay, and the dropdown
         */
        _generateMarkup() {
            // containers (mostly for positioning)
            let mainClass = 'country-select';
            if (this.options.defaultStyling) {
                mainClass += ' ' + this.options.defaultStyling;
            }

            // Create wrapper
            let wrapper = document.createElement('div');
            wrapper.className = mainClass;

            // Move it into wrapper
            this.countryInput.parentNode.insertBefore(wrapper, this.countryInput);
            wrapper.appendChild(this.countryInput);

            const flagTemplate = `
            <div class="flag-dropdown">
                <div class="selected-flag">
                    <div class="flag"></div>
                    <div class="arrow"></div>
                </div>
                <ul class="country-list hide"></ul>
            </div>
        `;

            this.countryInput.insertAdjacentHTML('afterend', flagTemplate);
            this.selectedFlagInner = this.countryInput.parentNode.querySelector('.flag');

            // country list contains: preferred countries, then divider, then all countries
            this.countryList = this.countryInput.parentNode.querySelector('.country-list');

            if (this.preferredCountries.length) {
                this._appendListItems(this.preferredCountries, 'preferred');

                let dividerEl = document.createElement('li');
                dividerEl.className = 'divider';
                this.countryList.appendChild(dividerEl);
            }

            this._appendListItems(this.countries, '');

            // Add the hidden input for the country code
            this.countryCodeInput = document.getElementById('country_selector_code');
            if (!this.countryCodeInput) {
                this.countryCodeInput.insertAdjacentHTML(
                    'afterend',
                    '<input type="hidden" id="country_selector_code" name="country_selector_code" value="" />'
                );
            }

            // now we can grab the dropdown height, and hide it properly
            //this.dropdownHeight = this.countryList.outerHeight();
            this.dropdownHeight = this.countryList.offsetHeight

            // set the dropdown width according to the input if responsiveDropdown option is present or if it's a small screen
            if (this.options.responsiveDropdown) {
                window.addEventListener(
                    'resize',
                    function () {
                        document.querySelectorAll('.country-select').forEach(function (countrySelect) {
                            const dropdownWidth = countrySelect.offsetWidth;
                            let countryList = countrySelect.querySelector('.country-list');
                            countryList.style.width = dropdownWidth + 'px';
                        });
                    }
                );

                window.dispatchEvent(new Event('resize'));
            }

            // this is useful in lots of places
            /** @var NodeList */
            this.countryListItems = this.countryList.querySelectorAll('.country');
        },

        /**
         * Adds a country <li> to the countryList <ul> container
         *
         * @param countries
         * @param className
         */
        _appendListItems(countries, className) {
            // Generate DOM elements as a large temp string, so that there is only
            // one DOM insert event
            let tmp = '';
            // for each country
            // @todo add li active class
            countries.forEach(function (c) {
                // open the list item
                tmp += '<li class="country ' + className + '" data-country-code="' + c.iso2 + '">';
                // add the flag
                tmp += '<div class="flag ' + c.iso2 + '"></div>';
                // and the country name
                tmp += '<span class="country-name">' + c.name + '</span>';
                // close the list item
                tmp += '</li>';
            });
            this.countryList.innerHTML += tmp;
        },

        /**
         * Sets the initial state of the input value and the selected flag
         */
        _setInitialState() {
            const flagIsSet = false;

            // If the country code input is pre-populated, update the name and the selected flag
            const selectedCode = this.countryCodeInput.value;
            if (selectedCode) {
                this.selectCountry(selectedCode);
            }
            if (!flagIsSet) {
                // flag is not set, so set to the default country
                let defaultCountry;
                // check the defaultCountry option, else fall back to the first in the list
                if (this.options.defaultCountry) {
                    defaultCountry = this._getCountryData(this.options.defaultCountry, false);
                    // Did we not find the requested default country?
                    if (!defaultCountry) {
                        defaultCountry = this.preferredCountries.length ? this.preferredCountries[0] : this.countries[0];
                    }
                } else {
                    defaultCountry = this.preferredCountries.length ? this.preferredCountries[0] : this.countries[0];
                }
                this.defaultCountry = defaultCountry.iso2;
            }
        },

        /**
         * Initialises event listener - click selected flag
         */
        _initListeners() {
            // toggle country dropdown on click
            const selectedFlag = this.selectedFlagInner.parentNode;
            // only intercept this event if we're opening the dropdown
            // else let it bubble up to the top ("click-off-to-close" listener)
            // we cannot just stopPropagation as it may be needed to close another instance
            selectedFlag.addEventListener('click', this._showDropdown.bind(this));
        },

        /**
         * Sets default country
         */
        _initAutoCountry() {
            if (this.defaultCountry) {
                this.selectCountry(this.defaultCountry);
            }

            this.autoCountryDeferred && this.autoCountryDeferred.resolve();
        },

        /**
         * Focus input and put the cursor at the end
         */
        _focus() {
            this.countryInput.focus();
            // works for Chrome, FF, Safari, IE9+
            if (this.countryInput.setSelectionRange) {
                const len = this.countryInput.value.length;
                this.countryInput.setSelectionRange(len, len);
            }
        },

        /**
         * Shows the dropdown
         */
        _showDropdown() {
            this._setDropdownPosition();
            // update highlighting and scroll to active list item
            let activeListItem = this.countryList.querySelector('li.active');
            if (!activeListItem) {
                activeListItem = this.countryList.querySelector('li:first-child');
            }

            this._highlightListItem(activeListItem);
            //this._highlightListItem.bind(this, activeListItem)();
            this.countryList.classList.remove('hide', 'v-hide');
            this._scrollTo(activeListItem);
            // bind all the dropdown-related listeners: mouseover, click, click-off, keydown
            this._bindDropdownListeners();
            // update the arrow
            let arrow = this.selectedFlagInner.parentNode.querySelector('.arrow');
            arrow.classList.add('up');
        },

        /**
         * Decides where to position dropdown (depends on position within viewport, and scroll)
         */
        _setDropdownPosition() {
            const rect = this.countryInput.getBoundingClientRect(),
                inputTop = rect.top + window.scrollY,
                windowTop = document.scrollingElement.scrollTop,
                dropdownFitsBelow = inputTop + this.countryInput.outerHeight + this.dropdownHeight < windowTop + window.innerHeight,
                dropdownFitsAbove = inputTop - this.dropdownHeight > windowTop;
            // dropdownHeight - 1 for border
            this.countryList.style.top = !dropdownFitsBelow && dropdownFitsAbove ? '-' + (this.dropdownHeight - 1) + 'px' : '';
        },

        /**
         * we only bind dropdown listeners when the dropdown is open
         */
        _bindDropdownListeners() {
            const that = this;
            // when mouse over a list item, just highlight that one
            // we add the class "highlight", so if they hit "enter" we know which one to select
            let countryItems = this.countryList.querySelectorAll('.country');
            countryItems.forEach(function (countryItem) {
                countryItem.addEventListener('mouseover', that._highlightListItem.bind(that, countryItem));

                // listen for country selection
                countryItem.addEventListener('click', that._selectListItem.bind(that, countryItem));
            });

            // click off to close
            // (except when this initial opening click is bubbling up)
            // we cannot just stopPropagation as it may be needed to close another instance
            that.isOpening = true;
            that.clickEventHandler = that._click.bind(that);
            window.addEventListener('click', that.clickEventHandler);

            // Listen for up/down scrolling, enter to select, or letters to jump to country name.
            // Use keydown as keypress doesn't fire for non-char keys and we want to catch if they
            // just hit down and hold it to scroll down (no keyup event).
            // Listen on the document because that's where key events are triggered if no input has focus
            that.keydownEventHandler = that._keydown.bind(that);
            window.addEventListener('keydown', that.keydownEventHandler);
        },

        _click(event) {
            event.preventDefault();
            if (!this.isOpening) {
                this._closeDropdown();
            }
            this.isOpening = false;
        },

        _keydown(event) {
            // prevent down key from scrolling the whole page,
            // and enter key from submitting a form etc
            event.preventDefault();
            if (event.which === this.options.keys.UP || event.which === this.options.keys.DOWN) {
                // up and down to navigate
                this._handleUpDownKey(event.which);
            } else if (event.which === this.options.keys.ENTER) {
                // enter to select
                this._handleEnterKey();
            } else if (event.which === this.options.keys.ESC) {
                // esc to close
                this._closeDropdown();
            } else if (event.which >= this.options.keys.A && event.which <= this.options.keys.Z || event.which === this.options.keys.SPACE) {
                this.typedLetters += String.fromCharCode(event.which);
                this._filterCountries(this.typedLetters);
            } else if (event.which === this.options.keys.BACKSPACE) {
                this.typedLetters = this.typedLetters.slice(0, -1);
                this._filterCountries(this.typedLetters);
            }
        },

        /**
         * Highlight the next/prev item in the list (and ensure it is visible)
         *
         * @param key
         */
        _handleUpDownKey(key) {
            // Convert `HTMLCollection` to `Array`
            const countryItems = Array.from(this.countryList.children);
            let that = this;
            let found = null;
            // Check for` `li` items
            countryItems.forEach(function (countryItem) {
                if (found) {
                    // Skip if item is found
                    return;
                }

                if (countryItem.classList.contains('highlight')) {
                    let next = key === that.options.keys.UP ? countryItem.previousElementSibling : countryItem.nextElementSibling;
                    // skip the divider
                    if (next && next.classList.contains('divider')) {
                        next = key === that.options.keys.UP ? next.previousElementSibling : next.nextElementSibling;
                    }

                    found = next;
                    next && that._highlightListItem(next);
                    next && that._scrollTo(next);
                }
            });
        },

        /**
         * Selects the currently highlighted item
         */
        _handleEnterKey() {
            // Convert `HTMLCollection` to `Array`
            const countryItems = Array.from(this.countryList.children);
            let that = this;
            let found = null;
            // Check for` `li` items
            countryItems.forEach(function (countryItem) {
                if (found) {
                    // Skip if item is found
                    return;
                }

                found = countryItem;
                countryItem && that._selectListItem(countryItem);
            });
        },

        /**
         * @param letters
         */
        _filterCountries(letters) {
            const countryItems = Array.from(this.countryListItems);
            const countries = countryItems.filter(function (countryListItem) {
                return (!countryListItem.classList.contains('preferred') && countryListItem.textContent.toLowerCase().indexOf(letters.toLowerCase()) > -1);
            });

            let that = this;
            let found = null;
            countries.forEach(function (countryListItem) {
                if (found) { return; } // Skip if item is found

                let highlighted = countries.filter(function (countryListItem) {
                    return (countryListItem.classList.contains('highlighted'));
                });

                found = countryListItem;

                // if one is already highlighted, then we want the next one
                if (highlighted.length > 0 &&
                    highlighted[0].nextElementSibling &&
                    highlighted[0].nextElementSibling.toLowerCase().indexOf(letters.toLowerCase()) > -1
                ) {
                    found = highlighted[0].nextElementSibling
                }

                // update highlighting and scroll
                that._highlightListItem(found);
                that._scrollTo(found);
            });
        },

        /**
         * Removes highlighting from other list items and highlight the given item
         * @param listItem
         */
        _highlightListItem(listItem) {
            console.log('_highlightListItem');
            this.countryListItems.forEach(function (countryListItem) {
                console.log(countryListItem);
                countryListItem.classList.remove('highlight');
            });
            listItem.classList.add('highlight');
        },

        /**
         * Finds the country data for the given country code
         * the ignoreOnlyCountriesOption is only used during init() while parsing the onlyCountries array
         * @param countryCode
         * @param ignoreOnlyCountriesOption
         */
        _getCountryData(countryCode, ignoreOnlyCountriesOption) {
            const countryList = ignoreOnlyCountriesOption ? this.options.allCountries : this.countries;
            for (let value of countryList) {
                if (value.iso2 === countryCode) {
                    return value;
                }
            }
            return null;
        },

        /**
         * Updates the selected flag and the active list item
         *
         * @param countryCode
         * @returns {boolean}
         */
        _selectFlag(countryCode) {
            console.log('_selectFlag', countryCode);
            if (!countryCode) {
                return false;
            }

            this.selectedFlagInner.setAttribute('class', '');
            this.selectedFlagInner.classList.add('flag', countryCode);

            // update the title attribute
            const countryData = this._getCountryData(countryCode);
            this.selectedFlagInner.parentNode.setAttribute('title', countryData.name);

            // update the active list item
            this.countryListItems.forEach(function (countryListItem) {
                countryListItem.classList.remove('active');
                const itemCountryCode = countryListItem.dataset['countryCode'];
                if (itemCountryCode === countryCode) {
                    countryListItem.classList.add('active');
                }
            });

            this.options.searchCallback && this.options.searchCallback();
        },

        /**
         * Called when the user selects a list item from the dropdown
         *
         * @param listItem
         */
        _selectListItem(listItem) {
            console.log('_selectListItem', listItem);
            // update selected flag and active list item
            const countryCode = listItem.dataset['countryCode'];
            this._selectFlag(countryCode);
            this._closeDropdown();
            // update input value
            this._updateName(countryCode);
            this.countryInput.dispatchEvent(new Event('change'));
            this.countryCodeInput.dispatchEvent(new Event('change'));
            // focus the input
            this._focus();
        },

        /**
         * Closes the dropdown and unbind any listeners
         */
        _closeDropdown() {
            let that = this;
            this.countryList.classList.add('hide');
            // update the arrow
            this.selectedFlagInner.parentNode.querySelector('.arrow').classList.remove('up');
            // unbind event listeners
            window.removeEventListener('keydown', that.keydownEventHandler);
            window.removeEventListener('click', that.clickEventHandler);

            // unbind both hover and click listeners
            let countryItems = this.countryList.querySelectorAll('.country');
            countryItems.forEach(function (countryItem) {
                countryItem.removeEventListener('mouseover', that._highlightListItem.bind(this, countryItem));
                countryItem.removeEventListener('click', that._selectListItem.bind(this, countryItem));
            });

            this.typedLetters = '';
        },

        /**
         * Checks if an element is visible within its container, else scroll until it is
         *
         * @param element
         * @private
         */
        _scrollTo(element) {
            if (!element) {
                return;
            }

            const container = this.countryList,
                containerRect = container.getBoundingClientRect(),
                containerWin = container.ownerDocument.defaultView,
                containerHeight = containerRect.height,
                containerTop = containerRect.top + containerWin.pageYOffset,
                containerBottom = containerTop + containerHeight,
                elementRect = element.getBoundingClientRect(),
                elementWin = element.ownerDocument.defaultView,
                elementHeight = element.outerHeight,
                elementTop = elementRect.top + elementWin.pageYOffset,
                elementBottom = elementTop + elementHeight,
                newScrollTop = elementTop - containerTop + container.scrollTop;

            if (elementTop < containerTop) {
                // scroll up
                container.scrollTop = newScrollTop;
            } else if (elementBottom > containerBottom) {
                // scroll down
                const heightDifference = containerHeight - elementHeight;
                container.scrollTop = newScrollTop - heightDifference;
            }

            container.scrollTo(null, newScrollTop);
        },

        /**
         * Replaces any existing country name with the new one
         *
         * @param countryCode
         */
        _updateName(countryCode) {
            console.log('_updateName', countryCode);
            this.countryCodeInput.value = countryCode;
            this.countryCodeInput.dispatchEvent(new Event('change'));
        },

        /**
         * Updates the selected flag
         * @param countryCode
         */
        selectCountry(countryCode) {
            console.log('selectCountry', countryCode);
            countryCode = countryCode.toLowerCase();
            // check if already selected
            if (!this.selectedFlagInner.classList.contains(countryCode)) {
                this._selectFlag(countryCode);
                this._updateName(countryCode);
            }
        }
    });

    return CountryPickerComponent;
});
