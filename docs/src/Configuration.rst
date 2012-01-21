Configuration
=============

..  _`configuration options`:

Options
-------

This module provides several configuration options.

..  table:: Options for |project|

    +----------+--------+---------------+-------------------------------------+
    | Name     | Type   | Default value | Description                         |
    +==========+========+===============+=====================================+
    | trigger  | string | "math"        | The command to use to ask the bot   |
    |          |        |               | to compute a new formula.           |
    |          |        |               | The trigger should only contain     |
    |          |        |               | alpha-numeric characters and should |
    |          |        |               | not be prefixed.                    |
    +----------+--------+---------------+-------------------------------------+


Example
-------

In this example, we configure the bot to compute formulae when the ``!calc``
command is used.

..  parsed-code:: xml

    <?xml version="1.0"?>
    <configuration
      xmlns="http://localhost/Erebot/"
      version="0.20"
      language="fr-FR"
      timezone="Europe/Paris">

      <modules>
        <!-- Other modules ignored for clarity. -->

        <module name="|project|">
          <param name="trigger" value="calc" />
        </module>
      </modules>
    </configuration>

..  vim: ts=4 et
