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

namespace Markocupic\SacEventRegistrationReminder\Data;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\SacEventRegistrationReminder\Stopwatch\Stopwatch;

class DataCollector
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Stopwatch $stopwatch,
    ) {
    }

    /**
     * @throws Exception
     */
    public function getData(string $state): array
    {
        $arrData = [];
        $currentTime = $this->stopwatch->getRequestTime(); // Use predictive time of processing start

        $arrCalendars = array_map(static fn ($id) => (int) $id, $this->getCalendars());

        $arrUsers = array_map(static fn ($id) => (int) $id, $this->getUsers());

        foreach ($arrCalendars as $calendarId) {
            $arrData[$calendarId] = [];

            $timeLimitD = $this->connection->fetchOne('SELECT sendFirstReminderAfter FROM tl_calendar WHERE id = ?', [$calendarId]);
            $reminderIntervalD = $this->connection->fetchOne('SELECT sendReminderEach FROM tl_calendar WHERE id = ?', [$calendarId]);

            if (!$timeLimitD) {
                continue;
            }

            if (!$reminderIntervalD) {
                continue;
            }

            $timeLimit = $currentTime - (int) $timeLimitD * 24 * 3600;
            $reminderIntervalS = (int) $reminderIntervalD * 24 * 3600;

            foreach ($arrUsers as $userId) {
                $blnSend = false;

                $arrData[$calendarId][$userId] = [];

                $arrEvents = array_map(static fn ($id) => (int) $id, $this->getEventsByUserAndCalendar($userId, $calendarId, $reminderIntervalS));

                foreach ($arrEvents as $eventId) {
                    $registrationsOutsideDeadline = $this->getRegistrationsByEventAndState($eventId, $state, $timeLimit);
                    $registrationsTotal = $this->getRegistrationsByEventAndState($eventId, $state, $currentTime);
                    $registrationsWithinDeadline = array_diff($registrationsTotal, $registrationsOutsideDeadline);

                    if (!empty($registrationsOutsideDeadline)) {
                        $arrData[$calendarId][$userId][$eventId]['outside_deadline'] = $registrationsOutsideDeadline;
                        $arrData[$calendarId][$userId][$eventId]['within_deadline'] = $registrationsWithinDeadline;
                        $arrData[$calendarId][$userId][$eventId]['total'] = $registrationsTotal;

                        $blnSend = true;
                    }
                }

                if (!$blnSend) {
                    unset($arrData[$calendarId][$userId]);
                }
            }
        }

        $arrData = array_filter($arrData);

        return $arrData;
    }

    /**
     * @throws Exception
     */
    private function getCalendars(): array
    {
        return $this->connection->fetchFirstColumn(
            'SELECT id FROM tl_calendar WHERE enableInstructorReminderNotification = ?',
            ['1'],
        );
    }

    /**
     * @throws Exception
     */
    private function getUsers(): array
    {
        return $this->connection->fetchFirstColumn(
            'SELECT id FROM tl_user WHERE disable = ?',
            [''],
        );
    }

    /**
     * @throws Exception
     */
    private function getEventsByUserAndCalendar(int $userId, int $calendarId, int $reminderIntervalS): array
    {
        // Use predictive time of processing start
        $currentTime = $this->stopwatch->getRequestTime();

        // + 60 s for rounding issues and start tolerance of periodic execution [s]
        $limit = $currentTime - $reminderIntervalS + 60;

        // Do not send reminders if the user is still within the sendReminderEach time limit
        $result = $this->connection->fetchOne(
            'SELECT user FROM tl_event_registration_reminder_notification WHERE dateAdded > ? AND user = ? AND calendar = ?',
            [$limit, $userId, $calendarId],
        );

        if ($result > 0) {
            return [];
        }

        // If the main instructor is not the recipient of event registration notifications
        $arr1 = $this->connection->fetchFirstColumn(
            'SELECT id FROM tl_calendar_events AS t1 WHERE '.
            't1.pid = ? AND t1.published = ? AND t1.registrationGoesTo = ? AND t1.startDate > ?',
            [$calendarId, '1', $userId, $currentTime]
        );

        // If the main instructor is the recipient of event registration notifications.
        $arr2 = $this->connection->fetchFirstColumn(
            'SELECT id FROM tl_calendar_events AS t1 WHERE '.
            't1.pid = ? AND t1.startDate > ? AND t1.published = ? AND NOT t1.registrationGoesTo > ? AND '.
            't1.id IN (SELECT t2.pid FROM tl_calendar_events_instructor AS t2 WHERE t2.isMainInstructor = ? AND t2.userId = ?)',
            [$calendarId, $currentTime, '1', 0, '1', $userId]
        );

        return array_unique(array_merge($arr1, $arr2));
    }

    /**
     * @throws Exception
     */
    private function getRegistrationsByEventAndState(int $intEventId, string $strState, int $intTimeLimit): array
    {
        return $this->connection->fetchFirstColumn('SELECT * FROM tl_calendar_events_member WHERE eventId = ? AND stateOfSubscription = ? AND dateAdded <= ?', [$intEventId, $strState, $intTimeLimit]);
    }
}
