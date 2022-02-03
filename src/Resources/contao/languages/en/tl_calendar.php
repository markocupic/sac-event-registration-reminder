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
$GLOBALS['TL_LANG']['tl_calendar']['event_registration_reminder_legend'] = 'Settings for reminder message to event organizer';

/*
 * Fields
 */
$GLOBALS['TL_LANG']['tl_calendar']['enableInstructorReminderNotification'] = ['Enable reminder tool', 'If the Remider Tool is activated, event managers will be informed at regular intervals by e-mail that they still have unprocessed participant requests in the event tool.'];
$GLOBALS['TL_LANG']['tl_calendar']['sendFirstReminderAfter'] = ['Send  first reminder after x days', 'Please set the number of days after which the first notification should be sent to the event organizer in case he has unprocessed event participation requests.'];
$GLOBALS['TL_LANG']['tl_calendar']['sendReminderEach'] = ['Notification interval in days', 'Set the notification interval in number of days here.'];
$GLOBALS['TL_LANG']['tl_calendar']['sendReminderNotification'] = ['Notification', 'Please select the notification.'];
