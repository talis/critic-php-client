critic-php-client
====================

A PHP client to manage communication to Critic

[![Build status](https://travis-ci.org/talis/critic-php-client.svg?branch=master)](https://travis-ci.org/talis/critic-php-client)
[![Dependency Status](https://dependencyci.com/github/talis/critic-php-client/badge)](https://dependencyci.com/github/talis/critic-php-client)

Usage
-----

Install the module via composer, by adding the following to your project's `composer.json`

```javascript
{
    "repositories":[
        {
            "type": "vcs",
            "url": "https://github.com/talis/critic-php-client"
        },
    ],
    "require" :{
        "talis/critic-php-client": "~0.1"
    }
}
```
then update composer:

```bash
$ php composer.phar update
```

In your code, do the following to create a review:

```php
$criticClient = new \Critic\Client(CRITIC_BASE_URL, OAUTH_CONNECT_VALUES);
$criticClient->createReview(
  $postFields,
  OAUTH_CLIENT_ID,
  OAUTH_CLIENT_SECRET,
  $headerParams
);
```
