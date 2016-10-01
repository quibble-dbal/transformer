# Quibble/Transformer
Transformers for query result( sets)

Easily cast and transform a single result set or a collection of results from a
database query.

## Installation

```sh
$ cd /path/to/your/project
$ composer require quibble/transformer

```

## Usage
Instantiate a transformer and either call the `collection` or `resource` method.
The first parameter is the query result, the second a callable used to determine
how to transform the result(s):

```php
<?php

use Quibble\Transformer\Transformer;

$transformer = new Transformer;

$results = $statement->fetchAll();
$results = $transformer->collection($results, function () {});

$result = $statement->fetch();
$result = $transformer->resource($result, function () {});

```

> Internally, `collection` is simply a convenient front to
> `array_map(Transformer::resource)`. In all other respects they work
> identically. The rest of this README will therefore only use `resource`.

The default behaviour is to remove all numeric keys from resources as if one had
fetched it using `PDO::FETCH_ASSOC`. To override this you may instantiate the
transformer with a `false` parameter.

You can only transform results returned as an array. The assumption is that if
you fetch "into" objects (models) these would do their own massaging. You will
however see later on how we can transform into objects as well.

## Defining a transformation
The key to specifying the transformation is to _type hint_ the named parameters
you want to have transformed. By default PDO returns most things as strings. We
can simply specify a few fields we want to be cast:

```php
<?php

$result = $transformer->resource($result, function (int $id, bool $active) {});

```

We can also type hint any object. The assumption is that such an object will be
instantiated with the original value as its first and only argument. E.g.:

```php
<?php

use Carbon\Carbon;

$result = $transformer->resource($result, function (Carbon $datecreated) {});

```

Any fields not specifically transformed are passed verbatim, and any fields
mentioned in the transformed not present in the result are simply ignored. The
idea is that you specify transformers with _all_ transformable fields for a
table, but your query doesn't necessarily do `"SELECT *"`. This way you can
easily reuse transformers.

## Modifying the return type
PHP7+ allows us to type-hint a return type. If a transformer does this, an
object of that type (or an array of them, in the case of `collection`) is
returned instead:

```php
<?php

$result = $transformer->resource($result, function () : stdClass {});
$result typeof stdClass; // true

```

## Specifying multiple transformers
More than one transformer may be given; just add additional arguments. They are
applied in order and if more than one specifies a return type, _only the last is
used_. This allows you to quickly specify overrides for certain query results.
If a field would be transformed twice using this method, it is first cast back
to a string before being passed to the next transformer. If you instead would
like to pass the _transformed_ value (because your decorator uses type hinting
in its constructor itself) specify it with a default `null` value.

## Centralizing your code (DRY)
Instead of manually passing lambdas around, a much cleaner pattern would be to
specify your transformers as _class methods_. You might do this on a model, or
in a central _interface_. Remember, we're not actually calling these methods, we
just want to inspect them!

```php
<?php

use Carbon\Carbon;

interface MySchema
{
    public function foo(int $id, Carbon $datecreated) : MyAwesomeModel;
    // A return type of array is actually the default.
    public function bar(int $id, bool $valid) : array;
}

$fooResult = $transformer->resource($fooResult, [MySchema::class, 'foo']);
$barResult = $transformer->resource($barResult, [MySchema::class, 'bar']);

```

## FAQ

### Do I need any other Quibble module to use this?
Nope. You don't even need PDO as long as you're dealing with arrays.

