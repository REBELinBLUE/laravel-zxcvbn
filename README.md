# Laravel Zxcvbn validator

[![Build Status](https://img.shields.io/travis/REBELinBLUE/laravel5-zxcvbn/master.svg?style=flat-square&label=Travis+CI)](https://travis-ci.org/REBELinBLUE/laravel5-zxcvbn)
[![Code Coverage](https://img.shields.io/codecov/c/github/REBELinBLUE/laravel5-zxcvbn/master.svg?style=flat-square&label=Coverage)](https://codecov.io/gh/REBELinBLUE/laravel5-zxcvbn)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square&label=License)](/LICENSE.md)

This package provides a validator which uses Dropbox's [zxcvbn](https://github.com/dropbox/zxcvbn) 
password strength estimator; it uses the [PHP implementation](https://github.com/bjeavons/zxcvbn-php) from
[bjeavons](https://github.com/bjeavons). 

## Installation

This package can be installed through Composer.

``` bash
composer require rebelinblue/laravel5-zxcvbn
```

In Laravel 5.5 the package will auto-register the service provider. In Laravel 5.4 you must register this 
service provider manually in `config/app.php` by adding `REBELinBLUE\Zxcvbn\ZxcvbnServiceProvider::class` to the 
`providers` array


There is also an optional facade for Zxcvbn; in Laravel 5.5 it will be auto-registered. In Laravel 5.4
you must register the facade manually by adding the following to the `aliases` array in `config/app.php`

```php
    'Zxcvbn' => REBELinBLUE\Zxcvbn\ZxcvbnFacade::class,
```

Optionally, you can publish the translations for this package with, however it is only required if you wish to change them

``` bash
php artisan vendor:publish --provider="REBELinBLUE\Zxcvbn\ZxcvbnServiceProvider"
```

## Usage

If you have added the alias you can access Zxcvbn from anyone in your code using the façade

```php
<?php

use Zxcvbn;

class MyCustomClass
{
    public function someMethod()
    {
        $strength = Zxcvbn::passwordStrength('Pa$$w0rd');
        dd($strength);
    }    
}

```

However, you probably want to use it as a validator. The package add a single rule "zxcvbn"

### Example
```php
<?php

use Illuminate\Foundation\Http\Request;

class SavePasswordRequest extends Request
{
    public function rules()
    {
        return [
            'password' => 'required|min:6|zxcvbn',
        ];
    }
}


// alternatively

$input = [ /* user input */ ];
$validator = Validator::make($input, [
    'password' => 'required|min:6|zxcvbn',
]); 
```

There are 2 optional parameters, the required score from 0 to 4 and a comma separate list of other fields to compare
against, for example to ensure a strong password which doesn't contain the username or email you would use

```php
'password' => 'required|min:6|zxcvbn:4,username,email',
```

The scores are rated as follows:

* 0 - Too guessable: risky password. (guesses < 10^3)
* 1 - Very guessable: protection from throttled online attacks. (guesses < 10^6)
* 2 - Somewhat guessable: protection from unthrottled online attacks. (guesses < 10^8)
* 3 - Safely unguessable: moderate protection from offline slow-hash scenario. (guesses < 10^10)
* 4 - Very unguessable: strong protection from offline slow-hash scenario. (guesses >= 10^10)
