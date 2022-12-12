# PHP GeoIP2Country PRO (v2.0.4)

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/donate/?hosted_button_id=ZCGRGGSB4XS4W)
[![Build Status](https://scrutinizer-ci.com/g/iriven/GeoIP2Country/badges/build.png?b=master)](https://scrutinizer-ci.com/g/iriven/GeoIP2Country/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/iriven/GeoIP2Country/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/iriven/GeoIP2Country/?branch=master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/iriven/GeoIP2Country/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)
[![GitHub license](https://img.shields.io/badge/license-AGPL-blue.svg)](https://github.com/iriven/GeoIP2Country/blob/master/LICENSE)

An IP address is only the beginning of endless possibilities, adventures and developments...

## What's GeoIP2Country PRO?

A PHP IP Address Geolocation library to help you identify visitors geographical location.
This component is Build with an eye to keeping it as lightweight and lookups as fast as possible.
And there are no external requests being made during runtime. So, if you need to get your website visitor's
country and you don't want to use any API then this is the best solution for you.
The project include methods to quickly update your GEOIP DATABASE with the least possible human
intervention (for donors only).

## What's new in this release?

- Complete refactoring and optimization of processing algorithms
- A new database engine (combining security and efficiency)
- A new component dedicated to updating the database?
- A repository based on statistical data from [ICANN](https://www.icann.org) (Internet Corporation for Assigned Names and Numbers)

#### NOTE:
**In order to encourage people to support this project, database update components are not included in this
repository as they are for donors only. Thus any donor will receive a full copy of this software,
including the component that will help him to make his GEOIP database stay up to date.
However, other users can still use the software as published here. Only the database update functionality will not be available**

**Donate here:** [![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/donate/?hosted_button_id=ZCGRGGSB4XS4W)


## Requirements

- pdo_sqlite (runtime deps)
- php_curl (for update only)


## Installation And Initialisation :

These instructions will get you a copy of the project up and running on your local machine.
there are two options:

- [x] import(copy) GeoIP2Country files into a specific folder of your project
- [x] Using Composer installer(Recommended) by typing the following command:

```php

composer require atchondjo/geoip2country

```

## Usage: (using Composer autoloader)

```php

require __DIR__ . '/vendor/autoload.php';

try
{
    $IP2Country = new iriven\GeoIP2Country();

} catch (\Throwable $th) {
    trigger_error($th->getMessage(), E_USER_ERROR);
}

```

#### Getting Country code from given IP address:

```php

    $ipAddress_1='2a00:1450:4007:816::2004';
    $ipAddress_2='37.140.250.97';
    $ipAddress_3='2001:41d0:301::21';
    $ipAddress_4='216.58.201.228';
    $ipAddress_5='188.165.53.185';
    $ipAddress_6='10.10.201.12';

    echo '<pre>';
    echo $IP2Country->resolve($ipAddress_1).PHP_EOL;
    echo $IP2Country->resolve($ipAddress_2).PHP_EOL;
    echo $IP2Country->resolve($ipAddress_3).PHP_EOL;
    echo $IP2Country->resolve($ipAddress_4).PHP_EOL;
    echo $IP2Country->resolve($ipAddress_5).PHP_EOL;
    echo $IP2Country->resolve($ipAddress_6).PHP_EOL;

```

#### Getting current visitor Country code (auto detect his IP address):

```php

    echo '<pre>';
    echo $IP2Country->resolve().PHP_EOL;  /** resolve() method called without any argument */

```

### Retrieving Country name:

Because one of my publications already deals with the recovery of a country's name from its ISO code.
I chose in this project to limit myself to the only search of the country's ALPHA2 ISO code from a given ip address.
So, to retrieve the country name (and much more), you must instantiate the "WorldCountriesDatas" class available from [HERE](https://github.com/iriven/WorldCountriesDatas),
and pass the result of the previous command as follows:

```php

$CountryName ='n/a';
$CountryCode = $IP2Country->resolve($ipAddress);
if(!$IP2Country->isReservedAddress($ipAddress))
{
  require_once 'WorldCountriesDatas.php';
  $DataProvider = new \Iriven\WorldCountriesDatas();
  $CountryName = $DataProvider->getCountryName($CountryCode);
}

```

### Updating GeoIP datas: (for Donors only)

```php

require __DIR__ . '/vendor/autoload.php';

try
{
    $IP2CountryBackend = new iriven\GeoIP2CountryServer();
    $IP2CountryBackend->updateDatabase();

} catch (\Throwable $th) {
    trigger_error($th->getMessage(), E_USER_ERROR);
}

/*
* NOTE: In order to encourage people to support this project, database update components are not included in this
* repository as they are for donors only. Thus any donor will receive a full copy of this software,
* including the component that will help him to make his GEOIP database stay up to date.
*/

```

### Compatibility:

- [x] IPV4
- [x] IPV6

## Authors

* **Alfred TCHONDJO** - *Project Initiator* - [Iriven France](https://www.facebook.com/Tchalf)

## License

This project is licensed under the GNU General Public License V3 - see the [LICENSE](LICENSE) file for details


## Donation

If this project help you reduce time to develop, you can give me a cup of coffee :)

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/donate/?hosted_button_id=ZCGRGGSB4XS4W)

## Acknowledgments

* This project uses GeoIp data by ICANN, available from [Here](https://www.icann.org)

## Disclaimer

If you use this library in your project please add a backlink to this page by this code.

```html
<a href="https://github.com/iriven/GeoIP2Country" target="_blank">This Project Uses Alfred's TCHONDJO GeoIP2Country PRO.</a>
```
