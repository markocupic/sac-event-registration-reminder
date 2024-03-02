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

use Markocupic\SacEventRegistrationReminder\Model\EventRegistrationReminderNotificationModel;

/*
 * Backend modules
 */
$GLOBALS['BE_MOD']['sac_be_modules']['event_registration_reminder_notification'] = [
    'tables' => ['tl_event_registration_reminder_notification'],
];

/*
 * Models
 */
$GLOBALS['TL_MODELS']['tl_event_registration_reminder_notification'] = EventRegistrationReminderNotificationModel::class;
