<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Modules;

use Logger;
use LogicException;
use pocketmine\plugin\Plugin;
use PrefixedLogger;
use ReflectionClass;
use ReflectionNamedType;

trait InjectArgsTrait
{
    public static function get(Context $context): static
    {
        /** @var ?static $module */
        $module = $context->tryGet(self::class);
        if (null === $module) {
            $module = self::create($context);
            $context->put($module);
        }

        return $module;
    }

    private static function create(Context $context): static
    {
        $reflect = new ReflectionClass(self::class);
        $ctor = $reflect->getConstructor();

        if (null === $ctor) {
            /** @var static $module */
            $module = $reflect->newInstance();

            return $module;
        }

        $params = $ctor->getParameters();
        $ctorArgs = [];
        foreach ($params as $param) {
            $type = $param->getType();
            if (!$type instanceof ReflectionNamedType) {
                throw new LogicException('('.self::class.")'s constructor must have single-type parameters!");
            }

            $typeName = $type->getName();
            if (!class_exists($typeName) && !interface_exists($typeName)) {
                throw new LogicException('Error creating module arguments: Class does not exist! ('.$type->getName().')');
            }
            $typeClass = new ReflectionClass($typeName);

            switch (true) {
                case $typeClass->implementsInterface(Module::class):
                    $ctorArgs[] = $typeClass->getMethod('get')->invoke(null, $context);
                    break;
                case Plugin::class === $typeName:
                    $ctorArgs[] = $context->getOwningPlugin();
                    break;
                case Logger::class === $typeName:
                    $start = strrpos(self::class, '\\', -1);
                    $end = strrpos(self::class, 'Module', -1);
                    if (false === $start) {
                        $start = 0;
                    } else {
                        ++$start;
                    }
                    if (false === $end) {
                        $end = strlen(self::class);
                    }

                    $pluginLogger = $context->getOwningPlugin()->getLogger();
                    $prefix = substr(self::class, $start, $end - $start);

                    $ctorArgs[] = new PrefixedLogger($pluginLogger, $prefix);
                    break;
                default:
                    throw new LogicException('('.self::class.")'s constructor's parameters must only be type Module, Plugin or Logger!");
            }
        }

        /** @var static $module */
        $module = $reflect->newInstanceArgs($ctorArgs);

        return $module;
    }
}
