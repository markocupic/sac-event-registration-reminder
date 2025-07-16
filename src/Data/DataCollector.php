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

namespace Markocupic\SacEventRegistrationReminder\Data;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Types;
use Markocupic\SacEventRegistrationReminder\Stopwatch\Stopwatch;

readonly class DataCollector
{
    public function __construct(
        private Connection $connection,
        private Stopwatch $stopwatch,
    ) {
    }

    /**
     * @throws Exception
     */
    public function getData(string $state): array
    {
        $arrData = [];
        $currentTime = $this->stopwatch->getRequestTime(); // Use predictive time of processing start

        $arrCalendarIDS = array_map('intval', $this->getCalendars());

        $arrUserIDS = array_map('intval', $this->getUsers());

        foreach ($arrCalendarIDS as $calendarId) {
            $arrData[$calendarId] = [];

            // time limit in days
            $timeLimitD = $this->connection->fetchOne(
                'SELECT sendFirstReminderAfter FROM tl_calendar WHERE id = ?',
                [
                    $calendarId,
                ],
                [
                    Types::INTEGER,
                ],
            );
            $reminderIntervalD = $this->connection->fetchOne(
                'SELECT sendReminderEach FROM tl_calendar WHERE id = ?',
                [
                    $calendarId,
                ],
                [
                    Types::INTEGER,
                ],
            );

            if (!$timeLimitD) {
                continue;
            }

            if (!$reminderIntervalD) {
                continue;
            }

            $timeLimit = $currentTime - (int) $timeLimitD * 24 * 3600;

            // Convert day to seconds
            $reminderIntervalS = (int) $reminderIntervalD * 24 * 3600;

            foreach ($arrUserIDS as $userId) {
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

        return array_filter($arrData);
    }

    /**
     * @throws Exception
     */
    private function getCalendars(): array
    {
        return $this->connection->fetchFirstColumn(
            'SELECT id FROM tl_calendar WHERE enableInstructorReminderNotification = ?',
            [
                1,
            ],
            [
                Types::INTEGER,
            ],
        );
    }

    /**
     * @throws Exception
     */
    private function getUsers(): array
    {
        return $this->connection->fetchFirstColumn(
            'SELECT id FROM tl_user WHERE disable = ?',
            [
                0,
            ],
            [
                Types::INTEGER,
            ],
        );
    }

    /**
     * @throws Exception
     */
    private function getEventsByUserAndCalendar(int $userId, int $calendarId, int $reminderIntervalS): array
    {
        // Use predictive time of processing start
        $now = $this->stopwatch->getRequestTime();

        // + 60 s for rounding issues and start tolerance of periodic execution [s]
        $limit = $now - $reminderIntervalS + 60;

        // Do not send reminders if the user is still within the sendReminderEach time limit
        $result = $this->connection->fetchOne(
            'SELECT user FROM tl_event_registration_reminder_notification WHERE dateAdded > ? AND user = ? AND calendar = ?',
            [
                $limit,
                $userId,
                $calendarId,
            ],
            [
                Types::INTEGER,
                Types::INTEGER,
                Types::INTEGER,
            ],
        );

        if (false !== $result) {
            return [];
        }

        // If the main instructor is not the recipient of event registration notifications
        $arr1 = $this->connection->fetchFirstColumn(
            'SELECT id FROM tl_calendar_events AS t1 WHERE '.
            't1.pid = ? AND t1.published = 1 AND t1.registrationGoesTo = ? AND t1.startDate > ?',
            [
                $calendarId,
                $userId,
                $now,
            ],
            [
                Types::INTEGER,
                Types::INTEGER,
                Types::INTEGER,
            ],
        );

        // If the main instructor is the recipient of event registration notifications.
        $arr2 = $this->connection->fetchFirstColumn(
            'SELECT id FROM tl_calendar_events AS t1 WHERE '.
            't1.pid = ? AND t1.published = 1 AND t1.registrationGoesTo = 0 AND t1.startDate > ? AND '.
            't1.id IN (SELECT t2.pid FROM tl_calendar_events_instructor AS t2 WHERE t2.isMainInstructor = 1 AND t2.userId = ?)',
            [
                $calendarId,
                $now,
                $userId,
            ],
            [
                Types::INTEGER,
                Types::INTEGER,
                Types::INTEGER,
            ],
        );

        return array_unique(array_merge($arr1, $arr2));
    }

    /**
     * @throws Exception
     */
    private function getRegistrationsByEventAndState(int $intEventId, string $strState, int $intTimeLimit): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from('tl_calendar_events_member', 't')
            ->where('t.firstname != "" AND t.lastname != "" AND t.gender != "" AND t.street != "" AND t.postal != "" AND t.city != ""')
            ->andWhere('t.eventId = :eventId')
            ->andWhere('t.stateOfSubscription = :stateOfSubscription')
            ->andWhere('t.dateAdded <= :dateAdded')
            ->setParameter('eventId', $intEventId, Types::INTEGER)
            ->setParameter('stateOfSubscription', $strState, Types::STRING)
            ->setParameter('dateAdded', $intTimeLimit, Types::INTEGER)
        ;

        return $qb->fetchFirstColumn();
    }
}
