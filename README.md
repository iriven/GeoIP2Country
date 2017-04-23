# Iriven PHP GeoIPCountry Library

A PHP IPV4 Address Geolocation library to help you identify visitors geographical location. 
This component is Build with an eye to keeping it as lightweight and lookups as fast as possible. 
And there are no external requests being made during runtime. So, if you need to get your website visitor's 
country and you don't want to use any API then this is the best solution for you.


## Requirements

php_curl, ziparchive (for install or update)

## Usage:

These instructions will get you a copy of the project up and running on your local machine. 

### Installation And Initialisation

To utilize GeoIPCountry, first import and require GeoIPCountry.php file in your project.

```php
require_once 'GeoIPCountry.php';
$IP2Country = new \iriven\GeoIPCountry(); //Initialisation
/* 
* NOTE: Initialisation may take a while if GeoIP data directory is missing or is corrupted (some file missing). 
* If so it will rebuild it, dont close the page until it finished.
*/
```

### Getting Country code from given IP address:

```php
$ip = '63.140.250.97';
$CountryCode = $IP2Country->resolve($ip);
echo 'Country Code: '.$countryCode;
```

### Updating GeoIP datas:

```php
$IP2Country->Admin()->updateDatabase();
```

## Authors

* **Alfred TCHONDJO** - *Initial work* - [iriven](https://github.com/iriven)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* This project uses GeoIp data by Software77, available from http://software77.net/geo-ip/?DL=2

## Disclaimer

If you use this library in your project please add a backlink to this page by this code.

```html

<a href="https://github.com/iriven/GeoIPCountry" target="_blank">This Project Uses Alfred's TCHONDJO GeoIPCountry PHP Library.</a>
```
