# BOIPA - Magento 2.4

This plugin is provided for BOIPA merchants using Magento 2.x.

[![GitHub license](https://img.shields.io/github/license/BOIPA/Magento2)](https://github.com/BOIPA/Magento2/blob/master/LICENSE) ![Version](https://img.shields.io/badge/version-1.1.0-informational)

 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Support](#markdown-header-support)

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/BOIPA`
 - Enable the module by running `php bin/magento module:enable BOIPA_Payment`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require boipa/module-payment`
 - enable the module by running `php bin/magento module:enable BOIPA_Payment`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration

- Enable (The status of this payment gateway on the frontend of magento).
- General
	- Title (Displayed on the checkout page under the payment methods section).
	- Merchant ID (You will have received from your account manager).
	- Merchant API Password	 (You will have received from your account manager).
	- Merchant Brand ID (You will have received from your account manager).	Admin > Stores > Settings > Configuration > Sales > Payment Methods

- Advanced Settings 
	- Payment Method (Sale / Authorization)
	- Display Mode (iFrame / Hosted Payment Page / Redirect)
	- Environment (Sandbox / Production)
	

## Support

Got a question? 
For support email <ecommerce@boipa.com>

For additonal infromation and instructions please go to our [Wiki](https://github.com/BOIPA/Magento_2/wiki/Installation-of-BOIPA-plugin-for-Magento-2.x).