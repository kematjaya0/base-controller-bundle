<?php

namespace Kematjaya\BaseControllerBundle\Tests\AST;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Kematjaya\BaseControllerBundle\AST\DateFunction;
use Kematjaya\BaseControllerBundle\AST\TextFunction;
use PHPUnit\Framework\TestCase;

/**
 * Covers the ORM 2 / ORM 3 bridge that TokenTypeResolverTrait provides.
 *
 * The contract under test is not "does the code run" but "is the value the
 * shim resolves actually acceptable to the *installed* ORM", because that is
 * exactly what broke when ORM 3 turned Lexer::T_* integers into a TokenType
 * enum and started typing Parser::match().
 */
final class FunctionNodeCompatibilityTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string, 1: string, 2: string, 3: string}>
     */
    public function functionNodeProvider(): array
    {
        return [
            'DateFunction' => [DateFunction::class, 'date', 'walkArithmeticPrimary', 'DATE(%s)'],
            'TextFunction' => [TextFunction::class, 'stringPrimary', 'walkStringPrimary', 'TEXT(%s)'],
        ];
    }

    /**
     * FunctionNode::__construct() requires a function name on ORM 2.20 and
     * ORM 3.x alike, so the node is always built with one.
     *
     * @param class-string $class
     */
    private function makeNode(string $class): object
    {
        return new $class('test_function');
    }

    /**
     * dqlToken() is private on the trait; reach it the same way the AST does.
     */
    private function resolveToken(object $node, string $name): mixed
    {
        $method = new \ReflectionMethod($node, 'dqlToken');

        return $method->invoke(null, $name);
    }

    /**
     * @dataProvider functionNodeProvider
     */
    public function testGetSqlWrapsTheWalkedExpression(
        string $class,
        string $property,
        string $walkerMethod,
        string $template
    ): void {
        $node = $this->makeNode($class);
        // A plain string is accepted by walkArithmeticPrimary()/walkStringPrimary()
        // on both ORM 2 and ORM 3; ORM 3 narrowed the parameter to Node|string.
        $node->{$property} = 't.foo';

        $walker = $this->getMockBuilder(SqlWalker::class)
            ->disableOriginalConstructor()
            ->onlyMethods([$walkerMethod])
            ->getMock();
        $walker->method($walkerMethod)->willReturn('t.foo');

        $this->assertSame(sprintf($template, 't.foo'), $node->getSql($walker));
    }

    /**
     * ORM 3 declares FunctionNode::getSql(): string. A missing or widened
     * return type is a fatal LSP error there, and a silent BC break here.
     *
     * @dataProvider functionNodeProvider
     */
    public function testGetSqlDeclaresStringReturnType(string $class): void
    {
        $type = (new \ReflectionMethod($class, 'getSql'))->getReturnType();

        $this->assertNotNull($type, $class . '::getSql() must declare a return type');
        $this->assertSame('string', $type->getName(), $class . '::getSql() must return string');
    }

    /**
     * ORM 3 declares FunctionNode::parse(): void for the same reason.
     *
     * @dataProvider functionNodeProvider
     */
    public function testParseDeclaresVoidReturnType(string $class): void
    {
        $type = (new \ReflectionMethod($class, 'parse'))->getReturnType();

        $this->assertNotNull($type, $class . '::parse() must declare a return type');
        $this->assertSame('void', $type->getName(), $class . '::parse() must return void');
    }

    /**
     * The load-bearing test. Read the signature the installed Parser::match()
     * actually has, then assert dqlToken() produces a value of that type.
     *
     * On ORM 2 match() is untyped and consumes Lexer::T_* integers; on ORM 3 it
     * is match(TokenType $token) and consumes enum cases. A shim that hardcodes
     * either table breaks the other line.
     */
    public function testResolvedTokenSatisfiesInstalledParserMatchSignature(): void
    {
        $parameter = (new \ReflectionMethod(Parser::class, 'match'))->getParameters()[0];
        $type = $parameter->getType();

        $node = $this->makeNode(DateFunction::class);

        foreach (['T_IDENTIFIER', 'T_OPEN_PARENTHESIS', 'T_CLOSE_PARENTHESIS'] as $name) {
            $token = $this->resolveToken($node, $name);

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                // ORM 3.x: match(TokenType $token)
                $this->assertInstanceOf(
                    $type->getName(),
                    $token,
                    sprintf('dqlToken(%s) must return a %s case on this ORM', $name, $type->getName())
                );
                $this->assertSame(
                    $name,
                    $token->name,
                    sprintf('dqlToken(%s) resolved to the wrong case', $name)
                );
            } else {
                // ORM 2.x: untyped match() fed Lexer::T_* integers
                $this->assertIsInt(
                    $token,
                    sprintf('dqlToken(%s) must return an int on this ORM', $name)
                );
                $this->assertSame(
                    Lexer::{$name},
                    $token,
                    sprintf('dqlToken(%s) does not match Lexer::%s', $name, $name)
                );
            }
        }
    }

    /**
     * The resolver memoises per class, so a second resolution must be
     * identical to the first and must not re-resolve from a different table.
     */
    public function testTokenResolutionIsStableAcrossCalls(): void
    {
        $node = $this->makeNode(DateFunction::class);

        $first = $this->resolveToken($node, 'T_IDENTIFIER');
        $second = $this->resolveToken($node, 'T_IDENTIFIER');

        $this->assertSame($first, $second);
    }
}
