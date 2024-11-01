=== 2Checkout ===
Contributors: quantumcloud
Donate link: https://www.quantumcloud.com
Tags: woocommerce, 2checkout, 2co, credit card payment, payment gateway
Requires at least: 4.6
Tested up to: 5.6
Stable tag: 1.1.1
Requires PHP: 5.6.0
License: GPLv2 or later
License URI: https://www.quantumcloud.com

2Checkout Payment Gateway for WooCommerce provides both 2CO hosted and inline credit card payment gateways. 

== Description ==

WoowPay is a 2Checkout payment gateway for WooCommerce. This 2CO payment gateway supports WordPress and WooCommerce latest versions. This payment gateway is also 2Checkout latest API 2.0 compatible. 

Please see the <strong>2Checkout set up instructions below </strong>for details on how to set up 2Checkout for woocommerce.

WoowPay supports <strong>2Checkout HOSTED payment</strong> and <strong>Inline credit cards</strong>. 2checkout hosted payment and Inline credit cards are available as separate payment gateways in woocommerce settings. You can turn them on and off separately - use just one of them or both.

= 2Checkout Payment Gateway Features =

* 2CheckOut Hosted Payment
* 2Checkout Inline Credit Card Payment
* Use hosted or inline credit card payment or both!
* 2CO API Version 2.0 Compatible
* SandBox mode for testing
* 2Checkout Event Logging


= How to Set Up the 2Chekcout Payment Gateway =

Log onto your 2CO Account and you Click on <strong>Integration</strong> link from left menu. On this page you will find:

* Merchant Code (Seller ID): You will need it for both hosted and inline credit card payments.
* Publishable Key: <strong>Required ONLY for the Inline Credit Card Payment</strong>
* Private Key: <strong>Required ONLY for the Inline Credit Card Payment</strong>

INS Settings:

Under the same Integrations page scroll down to Instant Notification System (INS) section and set the following options:

* <strong>Enable INS</strong>
* <strong>Enable Global INS </strong>and Set URL as Following: https://yoursite.com/?wc-api=WC_Gateway_QC_TwoCheckout (replace yoursite.com with your own wordpress installation link). This URL can also be found inside the payment settings page.


Redirect URL:

Under same page on Integrations scroll down to Redirect URL section and set the options as following:
* Approve URL https://yoursite.com/?wc-api=WC_Gateway_QC_TwoCheckout (replace yoursite.com with your own wordpress installation link). This URL can also be found inside the payment settings page.
* <strong>Return Method: Header redirect</strong>

++ <strong>Each of the steps above are important!</strong>


= 2Checokout payment gateway Support, Bug Fix, Feature Request =

This is a new plugin and we want to improve this plugin's features based on <strong>your feedback</strong> and suggestions. Let us know if you face any problem or need help with this plugin in the comments section. 

Please leave the plugin a great rating to encourage us so we can keep working on it and continue giving you the support you deserve.

== Installation ==

Unzip and Upload the woopay folder to /wp-content/plugins/
Activate the plugin through the 'Plugins' menu in WordPress
Navigate to Woocommerce setting->Payments in wp-admin and configure
Follow the 2CO set up steps outlined above


== Frequently Asked Questions ==

= How to Set Up the 2Chekcout Payment Gateway =
Log onto your 2CO Account and you Click on Integration Menu from left menu. On this page you will find:
Merchant Code (Seller ID): You will need it for both hosted and inline credit card payments.
Publishable Key: Needed for the Inline Credit Card Payment
Private Key: Needed for the Inline Credit Card Payment

INS Settings:
Under same page on Integrations scroll down to Instant Notification System (INS) section and set as following
Enable INS
Enable Global INS and Set URL as Following:
https://yoursite.com/?wc-api=WC_Gateway_QC_TwoCheckout

Redirect URL
Under same page on Integrations scroll down to Redirect URL section and set as following
Approve URL (replace yoursite.com with your own site name)
https://yoursite.com/?wc-api=WC_Gateway_QC_TwoCheckout

= Do I need SSL? =
If you are going to use the Inline credit card payment option, then you do need SSL on your website. Hosted payment option does not need it because users will be redirected to the 2Checkout website to complete the payment.


== Screenshots ==
1. 2CO Inline Credit card back end
2. 2CO Inline Credit card front end
3. 2CO Hosted payment back end
4. 2CO Hosted payment front end
5. Payment Gateways

== Changelog ==

= 1.1.2 =
# Removed the word Woocommerce and logo from plugin name and banner as per WordPress team's feedback.

= 1.1.1 =
# Improved deactivation module

= 1.1.0 =
# Fixed a PHP class conflict

= 1.0.0 =
# Make All Texts Translatable 

= 0.9.9 =
# Added Sandbox Deprecated notice below the sadbox enable option

= 0.9.8 =
# Add Credit Card and Hosted Checkout Settings Link on Plugin Meta Row
# Change Order status completed from pending payment after checkout with credit card
# Fix Images not displaying on checkout

= 0.9.6 =
# Updated deactivation module

= 0.9.5 =
# Updated some languages and images.

= 0.9.1 =
# Updated some languages for clarifications.

= 0.9.0 =
# Inception

