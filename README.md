PAYMILL-xtCommerce-3
====================

xtCommerce (Version 3.x) Plugin for PAYMILL credit card and elv payments

# Update Note

To update the xt:Commerce PAYMILL plugin you must reinstall the plugin to ensure 
that all needed tables are created.

## Your Advantages
* PCI DSS compatibility
* Payment means: Credit Card (Visa, Visa Electron, Mastercard, Maestro, Diners, Discover, JCB, AMEX), Direct Debit (ELV)
* Optional fast checkout configuration allowing your customers not to enter their payment detail over and over during checkout
* Improved payment form with visual feedback for your customers
* Supported Languages: German, English, Spanish, French, Italian, Portuguese
* Backend Log with custom View accessible from your shop backend

# PayFrame 
 
We’ve introduced a “payment form” option for easier compliance with PCI 
requirements. 
In addition to having a payment form directly integrated in your checkout page, you 
can use our embedded PayFrame solution to ensure that payment data never 
touches your website. 
 
PayFrame is enabled by default, but you can choose between both options in the 
plugin settings. Later this year, we’re bringing you the ability to customise the 
appearance and text content of the PayFrame version. 
 
To learn more about the benefits of PayFrame, please visit our [FAQ](https://www.paymill.com/en/faq/how-does-paymills-payframe-solution-work "FAQ").

# Installation

Download the following file, extract the zip file and upload the content of the copy_this folder in the root directory of your xtCommerce or xtcmodified shop.
There is also a folder named changed_full:

Inside this folder you will find the file changed_full/xtc3/admin/orders.php for xtCommerce 3 
or changed_full/xtcmodified/admin/orders.php for xtcModified

Inside this file you will find some code between the marker "<!-- Paymill begin -->" at the beginning and "<!-- Paymill end -->"
at the end. Find the equivalent point at your catalog/admin/orders.php and paste the code between the paymill markers.

https://github.com/paymill/paymill-xtcommerce-3/archive/master.zip

# Configuration

Afterwards enable PAYMILL in your shop backend and insert your test or live keys.

# In case of errors

In case of any errors check the PAYMILL log entry in the plugin config and 
contact the PAYMILL support (support@paymill.de).

# Notes about the payment process

The payment is processed when an order is placed in the shop frontend.

Fast Checkout: Fast checkout can be enabled by selecting the option in the PAYMILL Basic Settings. If any customer completes a purchase while the option is active this customer will not be asked for data again. Instead a reference to the customer data will be saved allowing comfort during checkout.
