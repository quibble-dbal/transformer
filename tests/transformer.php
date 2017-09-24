<?php

use Quibble\Transformer\Transformer;
use Gentry\Gentry\Wrapper;

/**
 * Tests for transformers.
 */
return function () : Generator {
    $transformer = Wrapper::createObject(Transformer::class);

    /**
     * It correctly strips numeric indices.
     */
    yield function () use ($transformer) {
        $result = [0 => 1, 'id' => 1];
        $result = $transformer->resource($result, function () {});
        assert(count($result) == 1);
    };

    /**
     * It correctly casts when requested to integer, float, boolean or string.
     */
    yield function () use ($transformer) {
        $result = ['integer' => '1', 'float' => '1.2', 'boolean' => '1', 'string' => 1];
        $result = $transformer->resource($result, function (int $integer, float $float, bool $boolean, string $string) {});
        assert($result['integer'] === 1);
        assert($result['float'] === 1.2);
        assert($result['boolean'] === true);
        assert($result['string'] === '1');
    };

    /**
     * We can also specify a return type and cast to an object.
     */
    yield function () use ($transformer) {
        $result = ['foo' => ['bar' => 1]];
        $result = $transformer->resource($result, function (stdClass $foo) : stdClass {});
        assert($result instanceof stdClass);
        assert($result->foo instanceof stdClass);
    };

    /**
     * If actually callable, anything passed as a reference can be modified
     * but if passed by value is left alone.
     */
    yield function () use ($transformer) {
        $result = ['foo' => 1, 'bar' => ['baz' => 2], 'fizz' => 3];
        $result = $transformer->resource(
            $result,
            function (int &$foo, stdClass &$bar, int $fizz) : stdClass {
                echo 'transofmring';
                $foo++;
                $bar = (array) $bar;
                $fizz++;
                return new stdClass;
            }
        );
        var_dump($result);
        assert($result->foo === 2);
        assert(gettype($result->bar) === 'array');
        assert($result->fizz === 3);
    };
};

