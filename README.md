# Iriven PHP GeoIPCountry Library

A PHP IP Address Geolocation library to help you identify visitors geographical location. 
This component is Build with an eye to keeping it as lightweight and lookups as fast as possible. 
And there are no external requests being made during runtime. So, if you need to get your website visitor's 
country and you don't want to use any API then this is the best solution for you.
The project include methods to quickly update the files providing ip ranges with the least possible human intervention.


## Requirements

php_curl, zlib, ziparchive (for install or update)

## Usage:

These instructions will get you a copy of the project up and running on your local machine. 

### Installation And Initialisation

To utilize GeoIPCountry, first import and require GeoIPCountry.php file in your project.

```php
require_once 'GeoIPCountry.php';
$IP2Country = new \iriven\GeoIPCountry(); //Initialisation
/* 
* NOTE: Initialisation may take a while if GeoIP data directory is missing or is corrupted (some files missing). 
* If so, it will download the last available zip package from software77 website and rebuild GeoIP data directory files,
* dont close the page until it finished.
*/
```

### Getting Country code from given IP address:

```php
$ip = '63.140.250.97';
$CountryCode = $IP2Country->resolve($ip);
echo 'Country Code: '.$countryCode;
```

### Retrieving Country name:

Because one of my publications already deals with the recovery of a country's name from its ISO code. 
I chose in this project to limit myself to the only search of the country's ALPHA2 ISO code from a given ip address. 
So, to retrieve the country name (and much more), you must instantiate the "WorldCountriesDatas" class available form [HERE](https://github.com/iriven/WorldCountriesDatas), 
and pass the result of the previous command as follows:

```php
$ip = '63.140.250.97';
$CountryName ='n/a';
$CountryCode = $IP2Country->resolve($ip);
if(!$IP2Country->isReservedIP()) //isReservedIP() method called with no argument
{
  require_once 'WorldCountriesDatas.php';
  $DataProvider = new \Iriven\WorldCountriesDatas(); 
  $CountryName = $DataProvider->getCountryName($CountryCode);
}
```
or as follows:

```php
$ip = '63.140.250.97';
$CountryName ='n/a';
if(!$IP2Country->isReservedIP($ip))  //isReservedIP() method called with $ip as argument
{
  $CountryCode = $IP2Country->resolve($ip);
  require_once 'WorldCountriesDatas.php';
  $DataProvider = new \Iriven\WorldCountriesDatas(); 
  $CountryName = $DataProvider->getCountryName($CountryCode);
}
```

### Updating GeoIP datas:

```php
$IP2Country->Admin()->updateDatabase();
/*
* NOTE: calling the Admin() method help enter in edit Mode;
* the command: $IP2Country->updateDatabase(); will have no effect
*/
```

### Compatibility:

- [x] IPV4
- [x] IPV6

## Authors

* **Alfred TCHONDJO** - *Project Initiator* - [iriven France](https://www.facebook.com/Tchalf)

## License

This project is licensed under the GNU General Public License V3 - see the [LICENSE](LICENSE) file for details

## Acknowledgments

* This project uses GeoIp data by Software77, available from [Here](http://software77.net/geo-ip)

## Disclaimer

If you use this library in your project please add a backlink to this page by this code.

```html

<a href="https://github.com/iriven/GeoIPCountry" target="_blank">This Project Uses Alfred's TCHONDJO GeoIPCountry PHP Library.</a>
```
