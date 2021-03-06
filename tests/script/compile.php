<?php

declare(strict_types=1);
/**
 * This file is part of the Ray.Aop package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Aop;

$loader = require dirname(dirname(__DIR__)) . '/vendor/autoload.php';
$loader->addPsr4('Ray\Aop\\', dirname(__DIR__) . '/Fake');

$tmpDir = dirname(__DIR__) . '/tmp';
$compiler = new Compiler($tmpDir);
$bind = new Bind;
$pointcut = new Pointcut(
    (new Matcher)->any(),
    (new Matcher)->any(),
    [new FakeInterceptor]
);
$bind->bind(FakeMock::class, [$pointcut]);
$class = $compiler->compile(FakeMock::class, $bind);

return $class;
