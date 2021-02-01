<?php

namespace Kematjaya\BaseControllerBundle\AST;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;
/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class DateFunction extends FunctionNode 
{
    public $date;

    public function getSql(SqlWalker $sqlWalker)
    {
        return "DATE(" . $sqlWalker->walkArithmeticPrimary($this->date) . ")";
    }
    
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->date = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
