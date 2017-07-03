# Phantasy Types [![Build Status](https://travis-ci.org/mckayb/phantasy-types.svg?branch=master)](https://travis-ci.org/mckayb/phantasy-types) [![Coverage Status](https://coveralls.io/repos/github/mckayb/phantasy-types/badge.svg?branch=master)](https://coveralls.io/github/mckayb/phantasy-types)
Library for creating Sum Types and Product Types in PHP

## Product Types
```php
use function Phantasy\Types\product;

$Point3D = product('Point3D', ['x', 'y', 'z']);
echo $Point3D; // 'Point3D'

$a = $Point3D(1, 2, 3);

echo $a; // 'Point3D(1, 2, 3)'
$Point3D->scale = function ($n) {
    return $this->Point3D($n * $this->x, $n * $this->y, $n * $this->z);
};

/*
Could also do
$Point3D->scale = function ($n) use ($Point3D) {
    return $Point3D($n * $this->x, $n * $this->y, $n * $this->z);
};
*/

$b = $a->scale(2);
echo $b; // 'Point3D(2, 4, 6)'
```

## Sum Types
```php
use function Phantasy\Types\sum;

$Option = sum('Option', [
    'Some' => ['x'],
    'None' => []
]);

$a = $Option->Some(1);
$b = $Option->None();

echo $a; // "Option.Some(1)"
echo $b; // "Option.None()"

$Option->map = function ($f) {
    return $this->cata([
        'Some' => function ($x) use ($f) {
            return $this->Some($f($x));
        },
        'None' => function () {
            return $this->None();
        }
    ]);
};

$c = $a->map(function ($x) {
    return $x + 1;
});

$d = $b->map(function ($x) {
    return $x + 1;
});

echo $c; // "Option.Some(2)"
echo $d; // "Option.None()"
```

## Contributing
Find a bug? Want to make any additions?
Just create an issue or open up a pull request.

## Inspiration
  * [Daggy](https://github.com/fantasyland/daggy)
