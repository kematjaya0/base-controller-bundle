<?php

namespace Kematjaya\BaseControllerBundle\AST;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\TokenType;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class TextFunction extends FunctionNode
{
    /**
     * @var \Doctrine\ORM\Query\AST\Node
     */
    public $stringPrimary;
    
    public function getSql(SqlWalker $sqlWalker): string
    {
        $stringPrimary  = $sqlWalker->walkStringPrimary($this->stringPrimary);
        //$platform       = $sqlWalker->getConnection()->getDatabasePlatform();
        return 'TEXT('.$stringPrimary.')';
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->stringPrimary = $parser->StringPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
