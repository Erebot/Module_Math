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
 *      A module that provides a very basic calculator.
 *
 * The calculator implemented by this module only supports
 * the four basic operators (+, -, /, *), exponentiation (^),
 * modules (%) and parenthesis.
 */
class   Erebot_Module_Math
extends Erebot_Module_Base
{
    /// Trigger registered by this module.
    protected $_trigger;

    /// Handler defined by this module.
    protected $_handler;

    /**
     * This method is called whenever the module is (re)loaded.
     *
     * \param int $flags
     *      A bitwise OR of the Erebot_Module_Base::RELOAD_*
     *      constants. Your method should take proper actions
     *      depending on the value of those flags.
     *
     * \note
     *      See the documentation on individual RELOAD_*
     *      constants for a list of possible values.
     */
    public function _reload($flags)
    {
        if (!($flags & self::RELOAD_INIT)) {
            $registry   = $this->_connection->getModule(
                'Erebot_Module_TriggerRegistry'
            );
            $matchAny  = Erebot_Utils::getVStatic($registry, 'MATCH_ANY');
            $this->_connection->removeEventHandler($this->_handler);
            $registry->freeTriggers($this->_trigger, $matchAny);
        }

        if ($flags & self::RELOAD_HANDLERS) {
            $registry   = $this->_connection->getModule(
                'Erebot_Module_TriggerRegistry'
            );
            $matchAny  = Erebot_Utils::getVStatic($registry, 'MATCH_ANY');

            $trigger        = $this->parseString('trigger', 'math');
            $this->_trigger = $registry->registerTriggers($trigger, $matchAny);
            if ($this->_trigger === NULL) {
                $fmt = $this->getFormatter(FALSE);
                throw new Exception($fmt->_('Could not register Math trigger'));
            }

            $this->_handler = new Erebot_EventHandler(
                new Erebot_Callable(array($this, 'handleMath')),
                new Erebot_Event_Match_All(
                    new Erebot_Event_Match_InstanceOf(
                        'Erebot_Interface_Event_Base_TextMessage'
                    ),
                    new Erebot_Event_Match_TextWildcard($trigger.' *', TRUE)
                )
            );
            $this->_connection->addEventHandler($this->_handler);

            $cls = $this->getFactory('!Callable');
            $this->registerHelpMethod(new $cls(array($this, 'getHelp')));
        }
    }

    /**
     * Provides help about this module.
     *
     * \param Erebot_Interface_Event_Base_TextMessage $event
     *      Some help request.
     *
     * \param Erebot_Interface_TextWrapper $words
     *      Parameters passed with the request. This is the same
     *      as this module's name when help is requested on the
     *      module itself (in opposition with help on a specific
     *      command provided by the module).
     */
    public function getHelp(
        Erebot_Interface_Event_Base_TextMessage $event,
        Erebot_Interface_TextWrapper            $words
    )
    {
        if ($event instanceof Erebot_Interface_Event_Base_Private) {
            $target = $event->getSource();
            $chan   = NULL;
        }
        else
            $target = $chan = $event->getChan();

        $fmt        = $this->getFormatter($chan);
        $trigger    = $this->parseString('trigger', 'math');

        $moduleName = strtolower(get_class());
        $nbArgs     = count($words);

        if ($nbArgs == 1 && $words[0] == $moduleName) {
            $msg = $fmt->_(
                'Provides the <b><var name="trigger"/></b> command which '.
                'allows you to submit formulae to the bot for computation.',
                array('trigger' => $trigger)
            );
            $this->sendMessage($target, $msg);
            return TRUE;
        }

        if ($nbArgs < 2)
            return FALSE;

        if ($words[1] == $trigger) {
            $msg = $fmt->_(
                "<b>Usage:</b> !<var name='trigger'/> &lt;<u>formula</u>&gt;. ".
                "Computes the given formula and displays the result. ".
                "The four basic operators (+, -, *, /), parenthesis, ".
                "exponentiation (^) and modules (%) are supported.",
                array('trigger' => $trigger)
            );
            $this->sendMessage($target, $msg);
            return TRUE;
        }
    }

    /**
     * Handles a request to do some calculation.
     *
     * \param Erebot_Interface_EventHandler $handler
     *      Handler that triggered this event.
     *
     * \param Erebot_Interface_Event_Event_Base_TextMessage $event
     *      Formula to calculate.
     *
     * \return
     *      This method does not return anything.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handleMath(
        Erebot_Interface_EventHandler           $handler,
        Erebot_Interface_Event_Base_TextMessage $event
    )
    {
        if ($event instanceof Erebot_Interface_Event_Base_Private) {
            $target = $event->getSource();
            $chan   = NULL;
        }
        else
            $target = $chan = $event->getChan();

        $formula    = $event->getText()->getTokens(1);
        $fmt        = $this->getFormatter($chan);

        try {
            $fp  = new Erebot_Module_Math_Lexer($formula);
            $msg = $fmt->_(
                '<var name="formula"/> = <b><var name="result"/></b>',
                array(
                    'formula' => $formula,
                    'result' => $fp->getResult(),
                )
            );
            $this->sendMessage($target, $msg);
        }
        catch (Erebot_Module_Math_DivisionByZeroException $e) {
            $this->sendMessage(
                $target,
                $fmt->_('Division by zero')
            );
        }
        catch (Erebot_Module_Math_ExponentTooBigException $e) {
            $this->sendMessage(
                $target,
                $fmt->_('Exponent is too big for computation')
            );
        }
        catch (Erebot_Module_Math_NegativeExponentException $e) {
            $this->sendMessage(
                $target,
                $fmt->_('^ is undefined for negative exponents')
            );
        }
        catch (Erebot_Module_Math_NoModulusOnRealsException $e) {
            $this->sendMessage(
                $target,
                $fmt->_('% is undefined on real numbers')
            );
        }
        catch (Erebot_Module_Math_SyntaxErrorException $e) {
            $this->sendMessage(
                $target,
                $fmt->_('Syntax error')
            );
        }
    }
}

