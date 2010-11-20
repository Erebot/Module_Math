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

class   Erebot_Module_math
extends Erebot_Module_Base
{
    static protected $_metadata = array(
        'requires'  =>  array(
            'Erebot_Module_TriggerRegistry',
            'Erebot_Module_Helper',
        ),
    );
    protected $_trigger;
    protected $_handler;

    public function reload($flags)
    {
        if (!($flags & self::RELOAD_INIT)) {
            $registry   = $this->_connection->getModule(
                'Erebot_Module_TriggerRegistry',
                Erebot_Connection::MODULE_BY_NAME
            );
            $matchAny  = Erebot_Utils::getVStatic($registry, 'MATCH_ANY');
            $this->_connection->removeEventHandler($this->_handler);
            $registry->freeTriggers($this->_trigger, $matchAny);
        }

        if ($flags & self::RELOAD_HANDLERS) {
            $registry   = $this->_connection->getModule(
                'Erebot_Module_TriggerRegistry',
                Erebot_Connection::MODULE_BY_NAME
            );
            $matchAny  = Erebot_Utils::getVStatic($registry, 'MATCH_ANY');

            $trigger        = $this->parseString('trigger', 'math');
            $this->_trigger = $registry->registerTriggers($trigger, $matchAny);
            if ($this->_trigger === NULL) {
                $translator = $this->getTranslator(FALSE);
                throw new Exception($translator->gettext(
                    'Could not register Math trigger'));
            }

            $filter         = new Erebot_TextFilter(
                                    $this->_mainCfg,
                                    Erebot_TextFilter::TYPE_WILDCARD,
                                    $trigger.' *', TRUE);
            $this->_handler = new Erebot_EventHandler(
                                    array($this, 'handleMath'),
                                    'Erebot_Interface_Event_TextMessage',
                                    NULL, $filter);
            $this->_connection->addEventHandler($this->_handler);
            $this->registerHelpMethod(array($this, 'getHelp'));
        }
    }

    public function getHelp(Erebot_Interface_Event_TextMessage &$event, $words)
    {
        if ($event instanceof Erebot_Interface_Event_Private) {
            $target = $event->getSource();
            $chan   = NULL;
        }
        else
            $target = $chan = $event->getChan();

        $translator = $this->getTranslator($chan);
        $trigger    = $this->parseString('trigger', 'math');

        $bot        =&  $this->_connection->getBot();
        $moduleName =   strtolower($bot->moduleClassToName($this));
        $nbArgs     =   count($words);

        if ($nbArgs == 1 && $words[0] == $moduleName) {
            $msg = $translator->gettext('
Provides the <b><var name="trigger"/></b> command which allows you
to submit formulae to the bot for computation.
');
            $formatter = new Erebot_Styling($msg, $translator);
            $formatter->assign('trigger', $trigger);
            $this->sendMessage($target, $formatter->render());
            return TRUE;
        }

        if ($nbArgs < 2)
            return FALSE;

        if ($words[1] == $trigger) {
            $msg = $translator->gettext("
<b>Usage:</b> !<var name='trigger'/> &lt;<u>formula</u>&gt;.
Computes the given formula and displays the result.
The four basic operators (+, -, *, /), parenthesis, exponentiation (^)
and modules (%) are supported.
");
            $formatter = new Erebot_Styling($msg, $translator);
            $formatter->assign('trigger', $trigger);
            $this->sendMessage($target, $formatter->render());

            return TRUE;
        }
    }

    public function handleMath(Erebot_Interface_Event_TextMessage &$event)
    {
        if ($event instanceof Erebot_Interface_Event_Private) {
            $target = $event->getSource();
            $chan   = NULL;
        }
        else
            $target = $chan = $event->getChan();

        $formula    = Erebot_Utils::gettok($event->getText(), 1);
        $translator = $this->getTranslator($chan);

        try {
            $fp     = new Erebot_Module_Math_Lexer($formula);
            $msg    = '<var name="formula"/> = <b><var name="result"/></b>';
            $tpl    = new Erebot_Styling($msg, $translator);
            $tpl->assign('formula', $formula);
            $tpl->assign('result',  $fp->getResult());
            $this->sendMessage($target, $tpl->render());
        }
        catch (Erebot_Module_Math_DivisionByZeroException $e) {
            $this->sendMessage($target,
                $translator->gettext('Division by zero'));
        }
        catch (Erebot_Module_Math_ExponentTooBigException $e) {
            $this->sendMessage($target,
                $translator->gettext('Exponent is too big for computation'));
        }
        catch (Erebot_Module_Math_NegativeExponentException $e) {
            $this->sendMessage($target,
                $translator->gettext('^ is undefined for negative exponents'));
        }
        catch (Erebot_Module_Math_NoModulusOnRealsException $e) {
            $this->sendMessage($target,
                $translator->gettext('% is undefined on real numbers'));
        }
        catch (Erebot_Module_Math_SyntaxErrorException $e) {
            $this->sendMessage($target,
                $translator->gettext('Syntax error'));
        }
    }
}

