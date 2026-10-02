<?php

namespace Kematjaya\BaseControllerBundle\AST;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\TokenType;

/**
 * Resolves DQL token identifiers across Doctrine ORM 2.x and 3.x.
 *
 * ORM 2.x exposes its token table as `Lexer::T_*` integer constants and leaves
 * `Parser::match()` untyped. ORM 3.x dropped those constants and replaced them
 * with the backed `TokenType` enum, typing `Parser::match(TokenType $token)`.
 *
 * Rather than maintaining two code paths, the token is resolved once per
 * process from whichever table the installed ORM actually provides.
 *
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
trait TokenTypeResolverTrait
{
    /**
     * @var array<string, int|\BackedEnum>
     */
    private static array $resolvedTokens = [];

    private static function dqlToken(string $name): int|\BackedEnum
    {
        if (isset(self::$resolvedTokens[$name])) {
            return self::$resolvedTokens[$name];
        }

        // ORM 3.x (TokenType exists and is a backed enum).
        if (enum_exists(TokenType::class)) {
            return self::$resolvedTokens[$name] = TokenType::{$name};
        }

        // ORM 2.x (integer constants on the Lexer).
        return self::$resolvedTokens[$name] = Lexer::{$name};
    }
}
