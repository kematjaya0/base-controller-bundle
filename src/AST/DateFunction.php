<?php

namespace Kematjaya\BaseControllerBundle\AST;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class DateFunction extends FunctionNode 
{
    use TokenTypeResolverTrait;
    
    public $date;

    // ORM 3 declares FunctionNode::getSql(): string and ::parse(): void.
    // ORM 2 declares both untyped; adding return types here is legal against
    // an untyped parent and required by ORM 3.
    public function getSql(SqlWalker $sqlWalker): string
    {
        return "DATE(" . $sqlWalker->walkArithmeticPrimary($this->date) . ")";
    }
    
    public function parse(Parser $parser): void
    {
        $parser->match(self::dqlToken('T_IDENTIFIER'));
        $parser->match(self::dqlToken('T_OPEN_PARENTHESIS'));

        $this->date = $parser->ArithmeticPrimary();

        $parser->match(self::dqlToken('T_CLOSE_PARENTHESIS'));
    }
}
