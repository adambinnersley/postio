# Postio PHP address lookup

UK postcode to address lookup on the [Postio](https://postio.co.uk/docs/address) API, with the county filled in (Postio does not return one) and the same methods and properties as `adamb/afd`, so a form built on AFD only changes how it creates the object.

## Installation

```sh
composer require adamb/postio
```

Requires PHP 8.1+, Guzzle 7 and `psr/simple-cache` 3. `Postio\Cache\RedisCache` needs the `redis` extension.

## Usage

```php
use Postio\Postio;
use Postio\Cache\RedisCache;

$redis = new Redis();
$redis->connect('127.0.0.1');
$postio = new Postio('pk_...', new RedisCache($redis));
$postio->setMaxLines(2); // the form has two address lines

// 1. List the addresses on a postcode
$addresses = $postio->findAddresses('LS1 4AP');
// [['address' => '1 Example Street, Leeds', 'key' => 'LS14AP:12345678'], ...]
if ($addresses === false && $postio->getLastError()) {
    // Postio failed (bad key, out of credit, unreachable): offer manual entry
}

// 2. Fill the form from the address picked
$postio->setAddress($_POST['key']);
$postio->address1; $postio->address2; $postio->address3;
$postio->town;     // "Newcastle upon Tyne", not "NEWCASTLE UPON TYNE"
$postio->county;   // "Tyne and Wear"
$postio->postcode; $postio->getLatitude(); $postio->getLongitude();
```

`lookup($postcode)` and `find($key)` return `Postio\Address` objects and throw `Postio\Exception\PostioException` instead of returning `false`. `programActive()` checks the key without being billed.

### Billing and the cache

Postio charges for every postcode or UDPRN lookup that finds something; searches with no result and `programActive()` are free. A postcode's results are cached (30 days by default), and the key returned by `findAddresses()` carries the postcode, so picking an address is answered from the cache: one charge per postcode searched. Without a cache, or if the entry has gone, `setAddress()` makes a second, billed UDPRN lookup.

Any PSR-16 cache works. The package ships `RedisCache` (share it between web servers) and `FileCache` (one server or development).

### Address lines

Postio's three address lines are used as they come, except that a bare building number or range left on its own line ("3-9") joins the street. `setMaxLines(n)` folds longer addresses to fit `n` form fields by joining the first lines with a comma ("Flat 2, Rose House" / "12 High Street").

### County

The county names returned are the ones in `adamb/uk-counties`, so `Counties::getCountyID($postio->county)` gives the dropdown id.

| Country | Resolved from | Result |
|---|---|---|
| England | district | ceremonial county (`data/england.php`); Stockton-on-Tees south of the Tees resolves by outcode to North Yorkshire |
| Wales | district | principal area (Cardiff, Gwynedd, ...) |
| Scotland | outcode | traditional county (`data/scotland.php`), with "City of Edinburgh" for the Edinburgh council area and a per-council fallback for new outcodes |
| Northern Ireland | | empty string |

Postio names districts the Ordnance Survey way ("City of Westminster London Boro", "Leeds District (B)", "Caerdydd - Cardiff"); each is matched in several spellings against the ONS names in `data/england.php`. When ONS creates or renames a district, add it to that file under its county.

## Tests

```sh
composer install
vendor/bin/phpunit
```
