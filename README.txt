
# Commerce Xem module

Provides a Xem cryptocurrency Commerce payment method. 

## Requirements

Commerce Xem is a Commerce payment method. 
You need the Drupal 8 Commerce module with the Drupal Commerce Payment module enabled. 
https://www.drupal.org/project/commerce

## Install

1. Install your Drupal Commerce as usual

2. Go to [admin/commerce/config/payment-gateways] and click on "Add payment gateway"

3. Choose the payment plugin named "QRCode Xem payment method"

4. Fill all fields, like any other payment method. On the mode checkboxes, 
"Test" will use TestNet servers. "Live" will use MainNet servers. 

5. Type your Xem public key, where the customers payment will be send

You will see Xem payment method like others on the checkout page. 

## Features

This modules gives to your Drupal Commerce a Xem cryptocurrency integration. 
This module does not create a "Xem" currency for Drupal Commerce. 

At the moment, Drupal commerce does not give the ability to add "non official" 
currencies. A numeric ISO 4217 code is required, and cryptocurrencies does not 
have this kind of code, even Bitcoin. 
