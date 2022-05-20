<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Modules;

use LogicException;
use ReflectionClass;
use ReflectionNamedType;

trait InjectArgsTrait
{
    public static function get(Context $context): static
    {
        /** @var ?static $module */
        $module = $context->tryGet(self::class);
        if ($module === null) {
            $module = self::create($context);
            $context->put($module);
        }

        return $module;
    }

    private static function create(Context $context): static
    {
        $reflect = new ReflectionClass(self::class);
        $ctor = $reflect->getConstructor();

        if ($ctor === null) {
            /** @var static $module */
            $module = $reflect->newInstance();
            return $module;
        }

        $params = $ctor->getParameters();
        $ctorArgs = [];
        foreach ($params as $param) {
            $type = $param->getType();
            if (!$type instanceof ReflectionNamedType) {
                throw new LogicException("(" . self::class . ")'s constructor must have single-type parameters!");
            }

            $typeName = $type->getName();
            if (!class_exists($typeName)) {
                throw new LogicException("Error creating module arguments: Class does not exist! (" . $type->getName() . ")");
            }
            $typeClass = new ReflectionClass($typeName);

            if (!$typeClass->implementsInterface(Module::class)) {
                throw new LogicException("(" . self::class . ")'s constructor must have only Module-subtypes for parameters!");
            }

            $ctorArgs[] = $typeClass->getMethod("get")->invoke(null, $context);
        }

        /** @var static $module */
        $module = $reflect->newInstanceArgs($ctorArgs);
        return $module;
    }
}
