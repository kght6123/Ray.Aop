<?php
namespace Ray\Aop;

use PhpParser\Builder\Method;
use PhpParser\BuilderFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

class CodeGenTest extends TestCase
{
    public function testAddNullDefaultWithAssisted()
    {
        $codeGen = new CodeGen((new ParserFactory)->newInstance(), new BuilderFactory, new Standard);
        $bind = new Bind;
        $bind->bindInterceptors('run', []);
        $code = $codeGen->generate('a', new \ReflectionClass(FakeAssistedConsumer::class), $bind);
        $expected = 'function run($a, $b = null, $c = null)';
        $this->assertContains($expected, $code);
    }

    public function testTypeDeclarations()
    {
        $codeGen = new CodeGen((new ParserFactory)->newInstance(), new BuilderFactory, new Standard);
        $bind = new Bind;
        $bind->bindInterceptors('run', []);
        $code = $codeGen->generate('a', new \ReflectionClass(FakePhp7Class::class), $bind);
        $isOverPhpParserVer2 = method_exists(Method::class, 'setReturnType');
        $expected = $isOverPhpParserVer2 ? 'function run(string $a, int $b, float $c, bool $d) : array' : 'function run(string $a, int $b, float $c, bool $d)';
        $this->assertContains($expected, $code);
    }

    public function testReturnType()
    {
        $codeGen = new CodeGen((new ParserFactory)->newInstance(), new BuilderFactory, new Standard);
        $bind = new Bind;
        $bind->bindInterceptors('returnTypeArray', []);
        $code = $codeGen->generate('a', new \ReflectionClass(FakePhp7ReturnTypeClass::class), $bind);
        $isOverPhpParserVer2 = method_exists(Method::class, 'setReturnType');
        $expected = $isOverPhpParserVer2 ? 'function returnTypeArray() : array' : 'function returnTypeArray()';
        $this->assertContains($expected, $code);
    }

    public function testReturnTypeVoid()
    {
        $codeGen = new CodeGen((new ParserFactory)->newInstance(), new BuilderFactory, new Standard);
        $bind = new Bind;
        $bind->bindInterceptors('returnTypeVoid', []);
        $code = $codeGen->generate('a', new \ReflectionClass(FakePhp71ReturnTypeClass::class), $bind);
        $expected = 'function returnTypeVoid() : void';
        $this->assertContains($expected, $code);
    }

    public function testReturnTypeNullable()
    {
        $codeGen = new CodeGen((new ParserFactory)->newInstance(), new BuilderFactory, new Standard);
        $bind = new Bind;
        $bind->bindInterceptors('returnNullable', []);
        $code = $codeGen->generate('a', new \ReflectionClass(FakePhp71ReturnTypeClass::class), $bind);
        $expected = 'function returnNullable() : ?';
        $this->assertContains($expected, $code);
    }
}
