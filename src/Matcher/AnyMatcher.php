<?php

declare(strict_types=1);
/**
 * This file is part of the Ray.Aop package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Aop\Matcher;

use Ray\Aop\AbstractMatcher;

final class AnyMatcher extends AbstractMatcher
{
    /**
     * @var array
     */
    private static $builtinMethods = [];

    public function __construct()
    {
        parent::__construct();
        if (self::$builtinMethods === []) {
            $this->setBuildInMethods();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function matchesClass(\ReflectionClass $class, array $arguments) : bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function matchesMethod(\ReflectionMethod $method, array $arguments) : bool
    {
        unset($arguments);

        return ! ($this->isMagicMethod($method->name) || $this->isBuiltinMethod($method->name));
    }

    private function setBuildInMethods()
    {
        $methods = (new \ReflectionClass('\ArrayObject'))->getMethods();
        foreach ($methods as $method) {
            self::$builtinMethods[] = $method->name;
        }
    }

    private function isMagicMethod(string $name) : bool
    {
        return strpos($name, '__') === 0;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function isBuiltinMethod(string $name) : bool
    {
        $isBuiltin = in_array($name, self::$builtinMethods, true);

        return $isBuiltin;
    }
}
