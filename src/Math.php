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

namespace Erebot\Module;

/**
 * \brief
 *      A module that provides a very basic calculator.
 *
 * The calculator implemented by this module only supports
 * the four basic operators (+, -, /, *), exponentiation (^),
 * modules (%) and parenthesis.
 */
class Math extends \Erebot\Module\Base implements \Erebot\Interfaces\HelpEnabled
{
    /// Trigger registered by this module.
    protected $trigger;

    /// Handler defined by this module.
    protected $handler;

    /**
     * This method is called whenever the module is (re)loaded.
     *
     * \param int $flags
     *      A bitwise OR of the Erebot::Module::Base::RELOAD_*
     *      constants. Your method should take proper actions
     *      depending on the value of those flags.
     *
     * \note
     *      See the documentation on individual RELOAD_*
     *      constants for a list of possible values.
     */
    public function reload($flags)
    {
        if (!($flags & self::RELOAD_INIT)) {
            $registry   = $this->connection->getModule(
                '\\Erebot\\Module\\TriggerRegistry'
            );
            $this->connection->removeEventHandler($this->handler);
            $registry->freeTriggers($this->trigger, $registry::MATCH_ANY);
        }

        if ($flags & self::RELOAD_HANDLERS) {
            $registry   = $this->connection->getModule(
                '\\Erebot\\Module\\TriggerRegistry'
            );
            $trigger        = $this->parseString('trigger', 'math');
            $this->trigger  = $registry->registerTriggers($trigger, $registry::MATCH_ANY);
            if ($this->trigger === null) {
                $fmt = $this->getFormatter(false);
                throw new \Exception($fmt->_('Could not register Math trigger'));
            }

            $this->handler = new \Erebot\EventHandler(
                \Erebot\CallableWrapper::wrap(array($this, 'handleMath')),
                new \Erebot\Event\Match\All(
                    new \Erebot\Event\Match\Type(
                        '\\Erebot\\Interfaces\\Event\\Base\\TextMessage'
                    ),
                    new \Erebot\Event\Match\TextWildcard($trigger.' *', true)
                )
            );
            $this->connection->addEventHandler($this->handler);
        }
    }

    /**
     * Provides help about this module.
     *
     * \param Erebot::Interfaces::Event::Base::TextMessage $event
     *      Some help request.
     *
     * \param Erebot::Interfaces::TextWrapper $words
     *      Parameters passed with the request. This is the same
     *      as this module's name when help is requested on the
     *      module itself (in opposition with help on a specific
     *      command provided by the module).
     */
    public function getHelp(
        \Erebot\Interfaces\Event\Base\TextMessage   $event,
        \Erebot\Interfaces\TextWrapper              $words
    ) {
        if ($event instanceof \Erebot\Interfaces\Event\Base\PrivateMessage) {
            $target = $event->getSource();
            $chan   = null;
        } else {
            $target = $chan = $event->getChan();
        }

        $fmt        = $this->getFormatter($chan);
        $trigger    = $this->parseString('trigger', 'math');
        $nbArgs     = count($words);

        if ($nbArgs == 1 && $words[0] === get_called_class()) {
            $msg = $fmt->_(
                'Provides the <b><var name="trigger"/></b> command which '.
                'allows you to submit formulae to the bot for computation.',
                array('trigger' => $trigger)
            );
            $this->sendMessage($target, $msg);
            return true;
        }

        if ($nbArgs < 2) {
            return false;
        }

        if ($words[1] === $trigger) {
            $msg = $fmt->_(
                "<b>Usage:</b> !<var name='trigger'/> &lt;<u>formula</u>&gt;. ".
                "Computes the given formula and displays the result. ".
                "The four basic operators (+, -, *, /), parenthesis, ".
                "exponentiation (^) and modules (%) are supported.",
                array('trigger' => $trigger)
            );
            $this->sendMessage($target, $msg);
            return true;
        }
    }

    /**
     * Handles a request to do some calculation.
     *
     * \param Erebot::Interfaces::EventHandler $handler
     *      Handler that triggered this event.
     *
     * \param Erebot::Interfaces::Event::Base::TextMessage $event
     *      Formula to calculate.
     *
     * \return
     *      This method does not return anything.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handleMath(
        \Erebot\Interfaces\EventHandler             $handler,
        \Erebot\Interfaces\Event\Base\TextMessage   $event
    ) {
        if ($event instanceof \Erebot\Interfaces\Event\Base\PrivateMessage) {
            $target = $event->getSource();
            $chan   = null;
        } else {
            $target = $chan = $event->getChan();
        }

        $formula    = $event->getText()->getTokens(1);
        $fmt        = $this->getFormatter($chan);

        try {
            $fp  = new \Erebot\Module\Math\Lexer($formula);
            $msg = $fmt->_(
                '<var name="formula"/> = <b><var name="result"/></b>',
                array(
                    'formula' => $formula,
                    'result' => $fp->getResult(),
                )
            );
            $this->sendMessage($target, $msg);
        } catch (\Erebot\Module\Math\DivisionByZeroException $e) {
            $this->sendMessage(
                $target,
                $fmt->_('Division by zero')
            );
        } catch (\Erebot\Module\Math\ExponentTooBigException $e) {
            $this->sendMessage(
                $target,
                $fmt->_('Exponent is too big for computation')
            );
        } catch (\Erebot\Module\Math\NegativeExponentException $e) {
            $this->sendMessage(
                $target,
                $fmt->_('^ is undefined for negative exponents')
            );
        } catch (\Erebot\Module\Math\NoModulusOnRealsException $e) {
            $this->sendMessage(
                $target,
                $fmt->_('% is undefined on real numbers')
            );
        } catch (\Erebot\Module\Math\SyntaxErrorException $e) {
            $this->sendMessage(
                $target,
                $fmt->_('Syntax error')
            );
        }
    }
}
