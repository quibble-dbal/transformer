<?php

namespace Quibble\Transformer;

use ReflectionMethod;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use DomainException;

class AnyCallable
{
    /**
     * Get a reflection for any callable.
     *
     * @param mixed $callable Anything callable.
     * @return ReflectionFunctionAbstract
     */
    public static function reflect($callable) : ReflectionFunctionAbstract
    {
        if (is_array($callable) && isset($callable[0], $callable[1])) {
            return new ReflectionMethod($callable[0], $callable[1]);
        } elseif (is_callable($callable)) {
            return new ReflectionFunction($callable);
        }
        throw new DomainException("$callable isn't callable");
    }

    public static function invokeWithArguments(ReflectionFunctionAbstract $transformer, $object) : void
    {
        $args = [];
        foreach ($transformer->getParameters() as $param) {
            $name = $param->getName();
            if (property_exists($object, $name)) {
                $args[] =& $object->$name;
            }
        }
        $transformer->invoke(...$args);
    }
}

