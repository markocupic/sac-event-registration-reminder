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
$GLOBALS['TL_LANG']['nc_tokens'][$type]['instructor_name'] = 'Name of the instructor.';
$GLOBALS['TL_LANG']['nc_tokens'][$type]['instructor_firstname'] = 'First name of the instructor.';
$GLOBALS['TL_LANG']['nc_tokens'][$type]['instructor_lastname'] = 'Last name of the instructor.';
$GLOBALS['TL_LANG']['nc_tokens'][$type]['instructor_email'] = 'Email address of the instructor.';

// Registrations
$GLOBALS['TL_LANG']['nc_tokens'][$type]['registrations'] = 'List of event registrations the instructor has not yet processed (generated text, grouped by event).';
$GLOBALS['TL_LANG']['nc_tokens'][$type]['send_reminder_each'] = 'Interval in days at which the reminder is repeated (calendar setting).';

// Admin
$GLOBALS['TL_LANG']['nc_tokens'][$type]['admin_email'] = 'Email address of the system administrator (Contao setting "Email address of the system administrator").';
