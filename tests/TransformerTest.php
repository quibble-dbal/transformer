<?php

namespace Quibble\Tests;

use Quibble\Transformer\Transformer;

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
}

