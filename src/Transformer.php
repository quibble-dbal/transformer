<?php

namespace Quibble\Transformer;

use Monomelodies\Reflex\AnyCallable;
use ReflectionFunctionAbstract;

class Transformer
{
    /**
     * @var bool
     */
    private $stripNumericIndices;

    /**
     * @param bool $stripNumericIndices
     */
    public function __construct(bool $stripNumericIndices = true)
    {
        $this->stripNumericIndices = $stripNumericIndices;
    }

    /**
     * @param array $collection
     * @param ...$transformers One or more transformers.
     * @return array
     * @throws DomainException if any transformer is not callable.
     */
    public function collection(array $collection, ...$transformers)
    {
        if (!$collection) {
            return [];
        }
        $transformers = array_map(
            [AnyCallable::class, 'reflect'],
            $transformers
        );
        $last = count($transformers);
        $transformers = array_map(
            function ($transformer) {
                if (!($transformer instanceof ReflectionFunctionAbstract)) {
                    $transformer = AnyCallable::reflect($transformer);
                }
                return $transformer;
            },
            $transformers
        );
        $transforms = [];
        foreach ($transformers as $transformer) {
            foreach ($transformer->getParameters() as $parameter) {
                $name = $parameter->getName();
                if (!isset($transforms[$name])) {
                    $transforms[$name] = [];
                }
                if (!$parameter->isOptional()) {
                    $transforms[$name][] = function ($value) {
                        return "$value";
                    };
                }
                $type = $parameter->getType();
                if ($type->isBuiltin()) {
                    $transforms[$name][] = function ($value) use ($type) {
                        settype($value, $type->__toString());
                        return $value;
                    };
                } elseif (class_exists($type->__toString())) {
                    $transforms[$name][] = function ($value) use ($type) {
                        $class = $type->__toString();
                        return new $class($value);
                    };
                }
            }
        }
        return array_map(function ($resource) use ($transforms) {
            if ($this->stripNumericIndices) {
                $resource = array_filter(
                    $resource,
                    function ($key) {
                        return !is_numeric($key);
                    },
                    ARRAY_FILTER_USE_KEY
                );
            }
            foreach ($transforms as $name => $transformers) {
                if (!array_key_exists($name, $resource)) {
                    continue;
                }
                foreach ($transformers as $transform) {
                    $resource[$name] = $transform($resource[$name]);
                }
            }
            return $resource;
        }, $collection);
    }

    public function resource(array $resource, ...$transformers)
    {
        return $this->collection([$resource], ...$transformers)[0];
    }
}

