<?php

declare(strict_types=1);
/**
 * This file is part of the Ray.Aop package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Aop;

use Doctrine\Common\Annotations\AnnotationReader;
use PhpParser\Builder\Class_ as Builder;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinter\Standard;
use Ray\Aop\Exception\InvalidSourceClassException;

final class CodeGen implements CodeGenInterface
{
    /**
     * @var \PhpParser\Parser
     */
    private $parser;

    /**
     * @var \PhpParser\BuilderFactory
     */
    private $factory;

    /**
     * @var \PhpParser\PrettyPrinter\Standard
     */
    private $printer;

    /**
     * @var CodeGenMethod
     */
    private $codeGenMethod;

    /**
     * @var AnnotationReader
     */
    private $reader;

    /**
     * @param \PhpParser\Parser                 $parser
     * @param \PhpParser\BuilderFactory         $factory
     * @param \PhpParser\PrettyPrinter\Standard $printer
     */
    public function __construct(
        Parser $parser,
        BuilderFactory $factory,
        Standard $printer
    ) {
        $this->parser = $parser;
        $this->factory = $factory;
        $this->printer = $printer;
        $this->codeGenMethod = new CodeGenMethod($parser, $factory, $printer);
        $this->reader = new AnnotationReader;
    }

    /**
     * Generate weaved class code
     */
    public function generate($class, \ReflectionClass $sourceClass, BindInterface $bind) : string
    {
        $methods = $this->codeGenMethod->getMethods($sourceClass, $bind);
        $classStmt = $this->buildClass($class, $sourceClass, $methods);
        $classStmt = $this->addClassDocComment($classStmt, $sourceClass);
        $declareStmt = $this->getPhpFileStmt($sourceClass);

        return $this->printer->prettyPrintFile(array_merge($declareStmt, [$classStmt]));
    }

    /**
     * Return "declare()" and "use" statement code
     *
     * @return Stmt[]
     */
    private function getPhpFileStmt(\ReflectionClass $class) : array
    {
        $traverser = new NodeTraverser();
        $visitor = new CodeGenVisitor();
        $traverser->addVisitor($visitor);
        $fileName = $class->getFileName();
        if (is_bool($fileName)) {
            throw new InvalidSourceClassException(get_class($class));
        }
        $file = file_get_contents($fileName);
        $stmts = $this->parser->parse($file);
        if (is_array($stmts)) {
            $traverser->traverse($stmts);
        }

        return $visitor();
    }

    /**
     * Return class statement
     */
    private function getClass(BuilderFactory $factory, string $newClassName, \ReflectionClass $class) : Builder
    {
        $parentClass = $class->name;
        $builder = $factory
            ->class($newClassName)
            ->extend($parentClass)
            ->implement('Ray\Aop\WeavedInterface');
        $builder = $this->addInterceptorProp($builder);
        $builder = $this->addSerialisedAnnotationProp($builder, $class);

        return $builder;
    }

    /**
     * Add class doc comment
     */
    private function addClassDocComment(Class_ $node, \ReflectionClass $class) : Class_
    {
        $docComment = $class->getDocComment();
        if ($docComment) {
            $node->setDocComment(new Doc($docComment));
        }

        return $node;
    }

    private function getClassAnnotation(\ReflectionClass $class) : string
    {
        $classAnnotations = $this->reader->getClassAnnotations($class);

        return serialize($classAnnotations);
    }

    private function addInterceptorProp(Builder $builder) : Builder
    {
        $builder->addStmt(
            $this->factory
                ->property('isIntercepting')
                ->makePrivate()
                ->setDefault(true)
        )->addStmt(
            $this->factory->property('bind')
            ->makePublic()
        );

        return $builder;
    }

    /**
     * Add serialised
     */
    private function addSerialisedAnnotationProp(Builder $builder, \ReflectionClass $class) : Builder
    {
        $builder->addStmt(
            $this->factory
                ->property('methodAnnotations')
                ->setDefault($this->getMethodAnnotations($class))
                ->makePublic()
        )->addStmt(
            $this->factory
                ->property('classAnnotations')
                ->setDefault($this->getClassAnnotation($class))
                ->makePublic()
        );

        return $builder;
    }

    private function getMethodAnnotations(\ReflectionClass $class) : string
    {
        $methodsAnnotation = [];
        $methods = $class->getMethods();
        foreach ($methods as $method) {
            $annotations = $this->reader->getMethodAnnotations($method);
            if ($annotations === []) {
                continue;
            }
            $methodsAnnotation[$method->name] = $annotations;
        }

        return serialize($methodsAnnotation);
    }

    private function buildClass(string $class, \ReflectionClass $sourceClass, array $methods) : Class_
    {
        $stmt = $this
            ->getClass($this->factory, $class, $sourceClass)
            ->addStmts($methods)
            ->getNode();

        return $stmt;
    }
}
