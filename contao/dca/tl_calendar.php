<?php

declare(strict_types=1);

/*
 * This file is part of SAC Event Registration Reminder.
 *
 * (c) Marko Cupic 2024 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-registration-reminder
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_calendar']['palettes']['__selector__'][] = 'enableInstructorReminderNotification';
$GLOBALS['TL_DCA']['tl_calendar']['subpalettes']['enableInstructorReminderNotification'] = 'sendFirstReminderAfter,sendReminderEach,sendReminderNotification';

PaletteManipulator::create()
    ->addLegend('event_registration_reminder_legend', 'protected_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField('enableInstructorReminderNotification', 'event_registration_reminder_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_calendar');

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_calendar']['fields']['enableInstructorReminderNotification'] = [
    'inputType' => 'checkbox',
    'exclude'   => true,
    'filter'    => true,
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'w50'],
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['sendFirstReminderAfter'] = [
    'inputType' => 'select',
    'exclude'   => true,
    'options'   => range(1, 30, 1),
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => 'smallint(5) unsigned NOT NULL default 0',
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['sendReminderEach'] = [
    'inputType' => 'select',
    'exclude'   => true,
    'options'   => range(1, 30, 1),
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => 'smallint(5) unsigned NOT NULL default 0',
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['sendReminderNotification'] = [
    'exclude'   => true,
    'search'    => true,
    'inputType' => 'select',
    'eval'      => ['mandatory' => true, 'includeBlankOption' => false, 'tl_class' => 'w50'],
    'sql'       => "varchar(64) NOT NULL default ''",
];
