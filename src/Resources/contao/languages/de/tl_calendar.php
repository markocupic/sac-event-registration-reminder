<?php

declare(strict_types=1);

/*
 * This file is part of SAC Event Registration Reminder.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-registration-reminder
 */

/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_calendar']['event_registration_reminder_legend'] = 'Einstellungen für Reminder-Nachricht an Event Leiter';

/*
 * Fields
 */
$GLOBALS['TL_LANG']['tl_calendar']['enableInstructorReminderNotification'] = ['Reminder-Tool aktivieren', 'Bei aktiviertem Remider Tool werden Event-Leiter in regelmässigen Abstand per E-Mail informiert, dass sie im Event-Tool noch unbearbeitete Teilnehmeranfragen haben.'];
$GLOBALS['TL_LANG']['tl_calendar']['sendFirstReminderAfter'] = ['Sende den 1. Reminder nach x Tagen', 'Bitte stellen Sie hier ein, nach wie vielen Tagen die erste Benachrichtiung bei unbearbeiteter Event-Teilnahme-Anfrage an den Leiter versendet werden soll.'];
$GLOBALS['TL_LANG']['tl_calendar']['sendReminderEach'] = ['Benachrichtigungs-Intervall in Tagen', 'Stellen Sie hier das Benachrichtigungs-Intervall in Anzahl Tagen ein.'];
$GLOBALS['TL_LANG']['tl_calendar']['sendReminderNotification'] = ['Benachrichtigung', 'Wählen Sie die Benachrichtigung aus.'];
