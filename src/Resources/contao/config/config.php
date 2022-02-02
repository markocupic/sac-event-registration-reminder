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

/*
 * Notification center
 */
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['sac_event_tool']['event_registration_reminder'] = [
    // Field in tl_nc_language
    'email_sender_name' => [],
    'email_sender_address' => ['admin_email'],
    'recipients' => ['instructor_email', 'admin_email'],
    'email_replyTo' => [],
    'email_subject' => ['send_reminder_each'],
    'email_text' => ['instructor_firstname', 'instructor_lastname', 'instructor_name', 'registrations', 'send_reminder_each'],
    'email_html' => ['instructor_firstname', 'instructor_lastname', 'instructor_name', 'registrations', 'send_reminder_each'],
];
