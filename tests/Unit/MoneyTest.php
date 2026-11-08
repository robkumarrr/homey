<?php

use App\Support\Money;

test('typed amounts parse to exact cents', function (string $input, int $cents) {
    expect(Money::parseCents($input))->toBe($cents);
})->with([
    'decimal' => ['19.99', 1999],
    'dollar sign and thousands' => ['$1,200', 120000],
    'whole dollars' => ['5', 500],
    'one decimal place' => ['0.5', 50],
    'largest allowed' => ['9,999,999.99', 999999999],
    'surrounding spaces' => [' 42.10 ', 4210],
]);

test('anything that is not a plain amount is rejected', function (string $input) {
    expect(Money::parseCents($input))->toBeNull();
})->with([
    'words' => ['abc'],
    'empty' => [''],
    'three decimals' => ['19.999'],
    'negative' => ['-5'],
    'misplaced commas' => ['12,34'],
    'two dollar signs' => ['$$5'],
    'trailing point' => ['5.'],
    'too large' => ['10000000'],
]);
