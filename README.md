# payever Checkout - Composable Payment Gateway for OroCommerce

## Introduction
With payever Checkout you can offer your customers a variety of payment methods - from B2C/B2B Buy Now Pay Later, Installments & Financing to Credit Card payments, PayPal, local payment methods and real time bank transfers based on Open banking technologies - with just one plugin.
You can also benefit from an exclusive selection of payment options from our close partners Santander, Openbank and Ivy.
More than 5,000 merchants in 9 countries trust in us already - with a simple integration and just a few clicks you can be part of it too!
Visit us at [www.getpayever.com/checkout](https://www.getpayever.com/checkout) to learn more!

### Highlights
* B2C & B2B Buy Now Pay Later: Pay in 14 or 30 days, split in up to 36 monthly installments as well as traditional financing with best terms and conditions for both costumers and merchants, we offer different types of buy now pay later products - in cooperation with our partners Santander, Openbank and Allianz Trade.
* Sustainability: Together with our exclusive partner Ivy, you and your customers can support certified climate protection projects with every payment - without any additional costs.
* All Payment Options: With payever Checkout you can offer your customers all common payment options via just one partner - without having to install and maintain multiple plugins.
* Free of Charge: payever is not charging any setup or transaction fees - you only bear the costs of the respective payment provider or bank. You can even benefit from lower partner conditions for specific payment options.
* Checkout Branding: You can completely customize your payever Checkout - upload your logo and adjust colors, fields as well as buttons so that the checkout fits your online shop perfectly.

### Our Payment Options:
#### Santander BNPL - You get paid right away, your customers pay in 30 days by bank transfer to our partner Santander.
* Payment period: 30 days
* Special Feature: No loan agreement or identity check necessary.
* Availability: Germany, Norway
* Transaction fees: Please contact support@payever.de to receive an individual offer.

#### Santander Splitpay - You get paid in full right away, your customers pay in affordable installments to our partner Santander.
* Payment period: 3 - 36 Month (customer interest rate between 0% - 7,9%)
* Special Feature: No loan agreement or identity check necessary.
* Availability: Germany
* Transaction fees: Please contact support@payever.de to receive an individual offer.

#### Santander Financing / Installments - You get paid in full right away with zero risk of default, while your customers pay back at their own pace in up to 72 affordable installments to our partner Santander.
* Payment period: 6 - 72 Month (0% financing possible)
* Special Feature: The process is completely digital with video legitimation and electronic signing.
* Availability: Germany, Austria, Netherlands, Belgium, Sweden, Denmark, Norway, Finland, UK
* Transaction fees: Please contact support@payever.de to receive an individual offer.

#### Zinia Buy Now Pay Later - You get paid right away, your customers pay after receiving their order by bank transfer to our partner Zinia.
* Availability: Germany, Netherlands
* Special Feature: No loan agreement or identity check necessary.
* Transaction fees: Please contact support@payever.de to receive an individual offer.

#### B2B Buy Now Pay Later by Allianz Trade - Allows merchants to offer flexible paymet terms to all their business customers.
* Availability: Europe, USA
* Special Feature: Decision in real time. No document check necessary.
* Transaction fees: Please contact support@payever.de to receive an individual offer.

#### Swedbank Pay Invoice - You get paid right away, your customers pay after receiving their order by bank transfer to our partner Swedbank.
* Availability: Sweden, Finland, Norway
* Special Feature: No loan agreement or identity check necessary.
* Transaction fees: Please contact support@payever.de to receive an individual offer.

#### Ivy - Open Banking enables your customers to transfer the purchase amount in real time via online banking access during the checkout process. You will immediately receive the transaction confirmation and can start the delivery process. As a green payment method, Ivy combines the topics payment with sustainability and structurally reduces transaction fees through open banking. Ivy uses these cost savings to offer merchants the lowest transactions fees on the market and to support climate protection projects with every transaction.
* Availability: Germany, Finland, France, Italy, Estonia, Lithuania, Netherlands, Portugal, Austria, Spain, Great Britain, Belgium, Hungary, Ireland, Latvia, Poland, Australia
* Transaction fees: Please contact support@payever.de to receive an individual offer.

#### Santander Instant Payments - Open Banking enables your customers to transfer the purchase amount in real time via online banking access during the checkout process. You will immediately receive the transaction confirmation and can start the delivery process.
* Special Feature: Beneficial terms and conditions in combination with other Santander payment methods.
* Availability: Germany, Austria, Switzerland
* Transactions fees: Please contact support@payever.de to receive an individual offer.

#### Sofort (Klarna) - The modern version of prepayment. The customer transfers the purchase amount via online banking, you immediately receive a transactions confirmation and can start the shipping process.
* Availability: Germany, Austria, Netherlands, Belgium, Spain, Italy, Switzerland, Poland, Great Britain
* Special Feature: Partner Conditions of 0,8% + 0,25€ fix

#### Stripe Credit Card - Via Stripe or you can accept all common credit cards: Visa, Master, American Express, JCB and Diners.
* Availability: Worldwide
* Special Feature: Smooth onboarding for credit card acceptance with Stripe.
* Transaction Fees: 1.4% + € 0.25 on cards issued in Europe, 2.9% + € 0.25 on cards issued outside Europe.

#### Swedbank Pay Credit Card - Via Swedbank Pay or you can accept all common credit cards: Visa, Master, American Express, JCB and Diners.
* Availability: Worldwide
* Special Feature: Smooth onboarding for credit card acceptance with Swedbank Pay.
* Transaction Fees: Please contact support@payever.de to receive an individual offer.

#### Apple Pay - Apple Pay replaces debit cards and cash with a simpler, safer, and more private payment method, wether in store on a website or in an app.
* Availability: Worldwide
* Transaction Fees: 1.4% + € 0.25 on cards issued in Europe, 2.9% + € 0.25 on cards issued outside Europe.

#### Google Pay - The simple way to pay in store, in apps or in online shops. Your customers can also collect bonuses and store their favourite cards - all at one place.
* Availability: Worldwide
* Transaction Fees: 1.4% + € 0.25 on cards issued in Europe, 2.9% + € 0.25 on cards issued outside Europe.

#### PayPal - This payment provider is popular worldwide and offers you and your customers extra protection in case of lost parcels and attempts of fraud.
* Availability: Worldwide
* Special Feature: Smooth onboarding by simply connecting your existing PayPal account.
* Transaction fees: Between 1.49% to 2.49% (depending on the total monthly volume) + € 0.35 fixed fee.

#### Prepayment - The customer transfers the money in advance and you ship the goods as soon as the money has arrived.
* Availability: Worldwide
* Special Feature: Fast onboarding and instant availability with just a few clicks.
* Transaction fees: None - Wire transfer is free of charge!

#### Direct Debit (SEPA): - Direct Debit is still a very common payment method: During the checkout process your customers just give permission to debit the amount from their bank account.
* Availability: 36 countries within the Single Euro Payment Area (SEPA).
* Special Feature: Smooth onboarding for credit card acceptance with Stripe.
* Transactions fees: 0,35€ fix

## Installation
OroCommerce uses the Composer to manage the module package and the library.
To start the installation, browse to your project's root directory and run the following commands:
```sh
composer require payever/payever-integration-orocommerce --prefer-dist --update-no-dev
php bin/console oro:platform:update --force --timeout=0 --skip-translations --skip-download-translations --skip-search-reindexation --env=prod
rm -rf var/cache/prod
php bin/console cache:clear --env=prod
php bin/console oro:assets:install
```

## Configuration
You can configure the payment integration in the System -> Configuration.
Go to System Configuration -> Integrations -> Payever Settings
