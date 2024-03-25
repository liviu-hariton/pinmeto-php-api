# PinMeToAPI PHP Library

PinMeToAPI is a PHP library that provides convenient access to the [PinMeTo](https://www.pinmeto.com/) API, allowing users to interact with PinMeTo's locations data and metrics.

## Overview
Integration with PinMeTo offers the ability to fetch information and send updates through PinMeTo API for:

* Locations
* Insights (Google&trade; and Facebook&trade;)
* Keywords (Google&trade;)
* Reviews (Google&trade; and Facebook&trade;)

## Table Of Content
* [Requirements](#requirements)
* [Installation](#installation)
* [Usage](#usage)
  * [Get all available locations](#get-all-available-locations)
  * [Get a specific location](#get-a-specific-location)
  * [Create a new location](#create-a-new-location)
  * [Update an existing location](#update-an-existing-location)
  * [Get locations metrics data](#metrics)
  * [Get locations Google keywords](#google-keywords)
  * [Get locations ratings](#ratings)
* [License](#license)
* [PinMeTo official API documentation](#pinmeto-official-api-documentation)

## Requirements

* a [PinMeTo](https://www.pinmeto.com/login) user account with API access enabled
* PHP >= 8.0
* PinMeToAPI uses curl extension for handling HTTP calls. So you need to have the [curl](https://www.php.net/manual/en/book.curl.php) extension installed and enabled with PHP.
* [json](https://secure.php.net/manual/en/book.json.php) support enabled with PHP

## Installation

You can install the PinMeToAPI PHP library via Composer. Run the following command in your terminal:

```bash
composer require liviu-hariton/pinmeto-php-api
```

## Usage

```php
require_once 'vendor/autoload.php';
```

Initialize the PinMeToAPI object with your PinMeTo `Account ID`, `App ID`, and `App Secret` values. You can obtain these credentials from your PinMeTo Account Settings [here](https://places.pinmeto.com/account-settings/).

```php
$pinmeto = new PinMeToAPI([
    'app_id' => 'PINMETO_APP_ID',
    'app_secret' => 'PINMETO_APP_SECRET',
    'account_id' => 'PINMETO_ACCOUNT_ID',
    'mode' => 'test' // or 'live' for production
]);
```
Once initialized, you can use various methods provided by the PinMeToAPI class to interact with the PinMeTo API.

### Get all available locations

```php
$locations = $pinmeto->getLocations();
```

Optionally, you can also pass an array of parameters

```php
$parameters = [
    'pagesize' => '2' // Number of locations that the request returns, default 100, max 250
    'next' => '569652a91151474860f5e173', // (string) Id of starting point to next page
    'before' => '569649b49c5ec8685e11175e', // (string) Id of starting point to previous page
];

$locations = $pinmeto->getLocations($parameters);
```

### Get a specific location

```php
$store_id = 8;

$location = $pinmeto->getLocation($store_id);
```

### Create a new location

```php
$parameters = [
    'name' => 'Your store name',
    'storeId' => 'your_store_id',
    'address' => [
        'street' => 'Store address',
        'zip' => 'Zipcode',
        'city' => 'The City',
        'country' => 'The Country',
    ],
    'location' => [
        'lat' => 59.333755678571,
        'lon' => 18.056143908447,
    ],
];

$pinmeto->createLocation($parameters);
```

You can also use the "Upsert" option by passing an additional parameter

```php
$pinmeto->createLocation($parameters, true);
```

### Update an existing location

```php
$store_id = 8;

$parameters = [
    'name' => 'The new store name',
    'address' => [
        'street' => 'The new store address',
        'zip' => 'Some other zipcode',
        'city' => 'In some other city',
        'country' => 'In some other country',
    ],
];

$pinmeto->updateLocation($store_id, $parameters);
```

### Metrics

Get the Google&trade; or Facebook&trade; metrics data for all locations

```php
$metrics = $pinmeto->getMetrics(
    source: 'google', // the source can be either `facebook` or `google`
    from_date: '2024-01-01', // the format is `YYYY-MM-DD`
    to_date: '2024-03-31', // the format is `YYYY-MM-DD`
    fields: [
        'businessImpressionsDesktopMaps', 'businessImpressionsDesktopSearch'
    ] // All available fields are described here https://api.pinmeto.com/documentation/v3/
);
```

or for a specific location by passing the Store ID

```php
$metrics = $pinmeto->getMetrics(
    source: 'facebook', // the source can be either `facebook` or `google`
    from_date: '2024-01-01', // the format is `YYYY-MM-DD`
    to_date: '2024-03-31', // the format is `YYYY-MM-DD`
    store_id: 8
);
```

### Google keywords

Get the Google&trade; keywords data for all locations

```php
$keywords = $pinmeto->getKeywords(
    from_date: '2024-01', // the format is `YYYY-MM`
    to_date: '2024-03' // the format is `YYYY-MM`
);
```

or for a specific location by passing the Store ID

```php
$keywords = $pinmeto->getKeywords(
    from_date: '2024-01', // the format is `YYYY-MM`
    to_date: '2024-03', // the format is `YYYY-MM`
    store_id: 8
);
```

### Ratings

Get the Google&trade; or Facebook&trade; ratings data for all locations

```php
$ratings = $pinmeto->getRatings(
    source: 'google', // the source can be either `facebook` or `google`
    from_date: '2024-01-01', // the format is `YYYY-MM-DD`
    to_date: '2024-03-31' // the format is `YYYY-MM-DD`
);
```

or for a specific location by passing the Store ID

```php
$ratings = $pinmeto->getRatings(
    source: 'facebook', // the source can be either `facebook` or `google`
    from_date: '2024-01-01', // the format is `YYYY-MM-DD`
    to_date: '2024-03-31', // the format is `YYYY-MM-DD`
    store_id: 8
);
```
## Response

For every method described here, the response will be a JSON data format. Please find al the details in the [PinMeTo official API documentation](#pinmeto-official-api-documentation).

## License
This library is licensed under the MIT License. See the [LICENSE.md](LICENSE.md) file for details.

## PinMeTo official API documentation
* The V2 documentation (locations data) is available on [PinMeTo GitHub](https://github.com/PinMeTo/documentation)
* The V3 documentation (locations metrics) is available on [PinMeTo API - Documentation](https://api.pinmeto.com/documentation/v3/)