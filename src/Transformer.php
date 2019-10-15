<?php

namespace Quibble\Transformer;

use ReflectionFunctionAbstract;
use Throwable;

class Transformer
{
    /**
     * @var bool
     */
    private $stripNumericIndices;

    /**
     * @param bool $stripNumericIndices Strip numeric indexes on resources
     *  (e.g., for when something is passed w/o PDO::FETCH_ASSOC).
     * @return void
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
    public function collection(array $collection, ...$transformers) : array
    {
        if (!$collection) {
            return [];
        }
        $transformers = array_map(
            [AnyCallable::class, 'reflect'],
            $transformers
        );
        $returnType = 'array';
        $transformers = array_map(
            function ($transformer) use (&$returnType) {
                if ($transformer->hasReturnType()) {
                    $returnType = $transformer->getReturnType()->__toString();
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
                $type = $parameter->getType();
                if ($type->isBuiltin()) {
                    $transforms[$name][] = function ($value) use ($type) {
                        settype($value, $type->__toString());
                        return $value;
                    };
                } elseif (class_exists($type->__toString())) {
                    $transforms[$name][] = function ($value) use ($type) {
                        $class = $type->__toString();
                        try {
                            return new $class($value);
                        } catch (Throwable $e) {
                            return $value;
                        }
                    };
                }
            }
        }

        $collection = array_map(function ($resource) use ($transforms, $returnType) {
            if (is_array($resource)) {
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
                if (strtolower($returnType) == 'stdclass') {
                    $resource = (object)$resource;
                } elseif (strtolower($returnType) == 'array') {
                    $resource = (array)$resource;
                } elseif (class_exists($returnType)) {
                    $data = $resource;
                    $resource = new $returnType;
                    foreach ($data as $key => $value) {
                        $resource->$key = $value;
                    }
                }
            } elseif (is_object($resource)) {
                foreach ($transforms as $name => $transformers) {
                    if (!property_exists($resource, $name)) {
                        continue;
                    }
                    foreach ($transformers as $transform) {
                        $resource->$name = $transform($resource->$name);
                    }
                }
            }
            return $resource;
        }, $collection);

        $collection = array_map(function ($resource) use ($transformers) {
            array_map(function ($transformer) use (&$resource) {
                try {
                    AnyCallable::invokeWithArguments($transformer, $resource);
                } catch (Throwable $e) {
                }
            }, $transformers);
            return $resource;
        }, $collection);

        return $collection;
    }

    /**
     * Transforms a single resource.
     *
     * @param array $resource
     * @param ...$transformers
     * @return mixed
     */
    public function resource(array $resource, ...$transformers)
    {
        return $this->collection([$resource], ...$transformers)[0];
    }
}

