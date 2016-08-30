<?php

/**
 * DoctrineExtensions Mysql Function Pack
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace Ladb\CoreBundle\DoctrineExtensions\Query\Mysql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

// limited support for GROUP_CONCAT
class GroupConcat extends FunctionNode
{
    public $isDistinct = false;
    public $expression = null;
    public $orderByExpression = null;
    public $orderByOrder = null;

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'GROUP_CONCAT('.
            ($this->isDistinct ? 'DISTINCT ' : '').
            $this->expression->dispatch($sqlWalker).
			(null !== $this->orderByExpression ? ' ORDER BY '.$this->orderByExpression->dispatch($sqlWalker).(null !== $this->orderByOrder ? ' '.$this->orderByOrder : '') : '').
        ')';
    }

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {

        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        
        $lexer = $parser->getLexer();
        if ($lexer->isNextToken(Lexer::T_DISTINCT)) {
            $parser->match(Lexer::T_DISTINCT);
            $this->isDistinct = true;
        }

        $this->expression = $parser->SingleValuedPathExpression();

		if ($lexer->isNextToken(Lexer::T_ORDER)) {
			$parser->match(Lexer::T_ORDER);
			if ($lexer->isNextToken(Lexer::T_BY)) {
				$parser->match(Lexer::T_BY);
				$this->orderByExpression = $parser->SingleValuedPathExpression();
				if ($lexer->isNextToken(Lexer::T_ASC)) {
					$parser->match(Lexer::T_ASC);
					$this->orderByOrder = 'ASC';
				} else if ($lexer->isNextToken(Lexer::T_DESC)) {
					$parser->match(Lexer::T_DESC);
					$this->orderByOrder = 'DESC';
				}
			}
		}

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

}
