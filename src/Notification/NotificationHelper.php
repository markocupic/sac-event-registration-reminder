<?php

declare(strict_types=1);

/*
 * This file is part of SAC Event Registration Reminder.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-registration-reminder
 */

namespace Markocupic\SacEventRegistrationReminder\Notification;

use Contao\CalendarModel;
use Contao\UserModel;
use Terminal42\NotificationCenterBundle\NotificationCenter;
use Terminal42\NotificationCenterBundle\Receipt\ReceiptCollection;

class NotificationHelper
{
    private CalendarModel|null $calendar = null;
    private UserModel|null $user = null;
    private int|null $notificationId = null;
    private array|null $tokens = null;

    public function __construct(
        private readonly NotificationCenter $notificationCenter,
    ){

    }

    public function send(int $notificationId, int $userId, int $calendarId, array $arrTokens, string $defaultLocale): ReceiptCollection
    {
        $this->initialize($notificationId, $userId, $calendarId, $arrTokens);

        $this->prepareTokens();

        $lang = $this->user->language ?: $defaultLocale;

        return $this->notificationCenter->sendNotification($this->notificationId,$this->tokens,$lang);

    }

    private function initialize(int $notificationId, int $userId, int $calendarId, array $arrTokens): void
    {
        $this->notificationId = $notificationId;

        if (null === ($this->user = UserModel::findByPk($userId))) {
            throw new \Exception(sprintf('User with ID %s not found', $userId));
        }

        if (null === ($this->calendar = CalendarModel::findByPk($calendarId))) {
            throw new \Exception(sprintf('Calendar with ID %s not found', $calendarId));
        }

        $this->tokens = $arrTokens;
    }

    private function prepareTokens(): void
    {
        $this->tokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'] ?? '';
        $this->tokens['instructor_email'] = $this->user->email;
        $this->tokens['instructor_firstname'] = $this->user->firstname;
        $this->tokens['instructor_lastname'] = $this->user->lastname;
        $this->tokens['instructor_name'] = $this->user->name;
        $this->tokens['send_reminder_each'] = $this->calendar->sendReminderEach;
    }
}
