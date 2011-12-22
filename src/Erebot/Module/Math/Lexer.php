<?php
/*
    This file is part of Erebot.

    Erebot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Erebot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Erebot.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * \brief
 *      A lexer for simple formulae.
 */
class Erebot_Module_Math_Lexer
{
    /// Formula to parse.
    protected $_formula;

    /// A parser for the formula.
    protected $_parser;

    /// Allow stuff such as "1234".
    const PATT_INTEGER  = '/^[0-9]+/';

    /// Allow stuff such as "1.23", "1." or ".23".
    const PATT_REAL     = '/^[0-9]*\.[0-9]+|^[0-9]+\.[0-9]*/';

    /**
     * Creates a new lexer for a formula.
     *
     * \param string $formula
     *      A formula to tokenize.
     */
    public function __construct($formula)
    {
        $this->_formula = strtolower($formula);
        $this->_parser  = new Erebot_Module_Math_Parser();
        $this->_tokenize();
    }

    /**
     * Returns the result of the formula
     * after calculation.
     *
     * \retval float
     *      Result of the formula.
     */
    public function getResult()
    {
        return $this->_parser->getResult();
    }

    /**
     * Does all the heavy work of tokenizing
     * and parsing the formula.
     *
     * \throw Erebot_Module_Math_Exception
     *      Some exception occurred while
     *      tokenizing or parsing the formula.
     */
    protected function _tokenize()
    {
        $operators = array(
            '(' =>  Erebot_Module_Math_Parser::TK_PAR_OPEN,
            ')' =>  Erebot_Module_Math_Parser::TK_PAR_CLOSE,
            '+' =>  Erebot_Module_Math_Parser::TK_OP_ADD,
            '-' =>  Erebot_Module_Math_Parser::TK_OP_SUB,
            '*' =>  Erebot_Module_Math_Parser::TK_OP_MUL,
            '/' =>  Erebot_Module_Math_Parser::TK_OP_DIV,
            '%' =>  Erebot_Module_Math_Parser::TK_OP_MOD,
            '^' =>  Erebot_Module_Math_Parser::TK_OP_POW,
        );

        $position   = 0;
        $length     = strlen($this->_formula);
        while ($position < $length) {
            $c          = $this->_formula[$position];
            $subject    = substr($this->_formula, $position);

            if (isset($operators[$c])) {
                $this->_parser->doParse($operators[$c], $c);
                $position++;
            }

            else if (preg_match(self::PATT_REAL, $subject, $matches)) {
                $position += strlen($matches[0]);
                $this->_parser->doParse(
                    Erebot_Module_Math_Parser::TK_NUMBER,
                    (double) $matches[0]
                );
            }

            else if (preg_match(self::PATT_INTEGER, $subject, $matches)) {
                $position += strlen($matches[0]);
                $this->_parser->doParse(
                    Erebot_Module_Math_Parser::TK_NUMBER,
                    (int) $matches[0]
                );
            }

            else if (strpos(" \t", $c) !== FALSE)
                $position++;
            else {
                $this->_parser->doParse(
                    Erebot_Module_Math_Parser::YY_ERROR_ACTION,
                    $c
                );
            }
        }

        // End of tokenization.
        $this->_parser->doParse(0, 0);
    }
}

