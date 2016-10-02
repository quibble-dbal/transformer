<?php

namespace Quibble\Tests;

use Quibble\Transformer\Transformer;
use stdClass;

/**
 * Tests for transformers.
 */
class TransformerTest
{
    /**
     * It correctly strips numeric indices.
     */
    public function testStripIndices(Transformer $transformer)
    {
        $result = [0 => 1, 'id' => 1];
        $result = $transformer->resource($result, function () {});
        yield assert(count($result) == 1);
    }

    /**
     * It correctly casts when requested to integer {?}, float {?}, boolean {?}
     * or string {?}.
     */
    public function testCasts(Transformer $transformer)
    {
        $result = ['integer' => '1', 'float' => '1.2', 'boolean' => '1', 'string' => 1];
        $result = $transformer->resource($result, function (int $integer, float $float, bool $boolean, string $string) {});
        yield assert($result['integer'] === 1);
        yield assert($result['float'] === 1.2);
        yield assert($result['boolean'] === true);
        yield assert($result['string'] === '1');
    }

    /**
     * We can also specify a return type {?} and cast to an object {?}.
     */
    public function testObjects(Transformer $transformer)
    {
        $result = ['foo' => ['bar' => 1]];
        $result = $transformer->resource($result, function (stdClass $foo) : stdClass {});
        yield assert($result instanceof stdClass);
        yield assert($result->foo instanceof stdClass);
    }

    /**
     * If actually callable, anything passed as a reference can be modified {?}
     * {?} but if passed by value is left alone {?}.
     */
    public function testModification(Transformer $transformer)
    {
        $result = ['foo' => 1, 'bar' => ['baz' => 2], 'fizz' => 3];
        $result = $transformer->resource(
            $result,
            function (int &$foo, stdClass &$bar, int $fizz) : stdClass {
                $foo++;
                $bar = (array) $bar;
                $fizz++;
            }
        );
        yield assert($result->foo === 2);
        yield assert(gettype($result->bar) === 'array');
        yield assert($result->fizz === 3);
    }
}

