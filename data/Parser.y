%declare_class {class Parser}
%syntax_error { throw new \Erebot\Module\Math\SyntaxErrorException(); }
%token_prefix TK_
%include {
    // @codingStandardsIgnoreFile
    namespace Erebot\Module\Math;
}
%include_class {
    private $formulaResult = NULL;
    public function getResult() { return $this->formulaResult; }
}

%left OP_ADD OP_SUB.
%left OP_MUL OP_DIV OP_MOD.
%left OP_POW.

formula ::= exprPar(e).                         { $this->formulaResult = e; }

exprPar(res) ::= PAR_OPEN exprPar(e) PAR_CLOSE.         { res = e; }
exprPar(res) ::= exprPar(opd1) OP_ADD exprPar(opd2).    { res = opd1 + opd2; }
exprPar(res) ::= exprPar(opd1) OP_SUB exprPar(opd2).    { res = opd1 - opd2; }
exprPar(res) ::= exprPar(opd1) OP_MUL exprPar(opd2).    { res = opd1 * opd2; }

exprPar(res) ::= exprPar(opd1) OP_DIV exprPar(opd2).    {
    if (!opd2) {
        throw new \Erebot\Module\Math\DivisionByZeroException();
    }

    res = opd1 / opd2;
}

exprPar(res) ::= exprPar(opd1) OP_MOD exprPar(opd2).    {
    if (!is_int(opd1) || !is_int(opd2)) {
        throw new \Erebot\Module\Math\NoModulusOnRealsException();
    }

    if (!opd2) {
        throw new \Erebot\Module\Math\DivisionByZeroException();
    }

    res = opd1 % opd2;
}

exprPar(res) ::= exprPar(opd1) OP_POW exprPar(opd2).       {
    if (opd2 < 0) {
        throw new \Erebot\Module\Math\NegativeExponentException();
    }

    /// \FIXME This is quite silly! Should we use gmp for big numbers ?
    if (opd2 > 30) {
        throw new \Erebot\Module\Math\ExponentTooBigException();
    } else {
        res = pow(opd1, opd2);
    }
}

exprPar(res)    ::= OP_ADD NUMBER(x).   { res = x; }
exprPar(res)    ::= OP_SUB NUMBER(x).   { res = -x; }
exprPar(res)    ::= NUMBER(x).          { res = x; }

