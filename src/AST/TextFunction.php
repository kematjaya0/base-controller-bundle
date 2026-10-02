<?php

namespace Kematjaya\BaseControllerBundle\AST;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class TextFunction extends FunctionNode
{
    use TokenTypeResolverTrait;

    public Node|string|null $stringPrimary = null;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $stringPrimary  = $sqlWalker->walkStringPrimary($this->stringPrimary);
        //$platform       = $sqlWalker->getConnection()->getDatabasePlatform();
        return 'TEXT(' . $stringPrimary . ')';
    }

    public function parse(Parser $parser): void
    {
        $parser->match(self::dqlToken('T_IDENTIFIER'));
        $parser->match(self::dqlToken('T_OPEN_PARENTHESIS'));
        $this->stringPrimary = $parser->StringPrimary();
        $parser->match(self::dqlToken('T_CLOSE_PARENTHESIS'));
    }
}
