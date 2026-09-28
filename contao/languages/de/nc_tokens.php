<?php

declare(strict_types=1);

/*
 * This file is part of SAC Event Registration Reminder.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-registration-reminder
 */

use Markocupic\SacEventRegistrationReminder\NotificationType\EventRegistrationReminderNotificationType;

$type = EventRegistrationReminderNotificationType::NAME;

// Instructor
$GLOBALS['TL_LANG']['nc_tokens'][$type]['instructor_name'] = 'Name des Leiters.';
$GLOBALS['TL_LANG']['nc_tokens'][$type]['instructor_firstname'] = 'Vorname des Leiters.';
$GLOBALS['TL_LANG']['nc_tokens'][$type]['instructor_lastname'] = 'Nachname des Leiters.';
$GLOBALS['TL_LANG']['nc_tokens'][$type]['instructor_email'] = 'E-Mail-Adresse des Leiters.';

// Registrations
$GLOBALS['TL_LANG']['nc_tokens'][$type]['registrations'] = 'Auflistung der Event-Anmeldungen, die der Leiter noch nicht bearbeitet hat (generierter Text, gruppiert nach Event).';
$GLOBALS['TL_LANG']['nc_tokens'][$type]['send_reminder_each'] = 'Intervall in Tagen, in dem die Erinnerung wiederholt wird (Einstellung im Kalender).';

// Admin
$GLOBALS['TL_LANG']['nc_tokens'][$type]['admin_email'] = 'E-Mail-Adresse des Systemadministrators (Contao-Einstellung "E-Mail-Adresse des Systemadministrators").';
