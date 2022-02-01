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

namespace Markocupic\SacEventRegistrationReminder\DataContainer;

use Doctrine\DBAL\Connection;
use Contao\CoreBundle\ServiceAnnotation\Callback;

class EventRegistrationReminderNotification
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @Callback(table="tl_event_registration_reminder_notification", target="fields.calendar.options")
     */
    public function getCalendars(): array
    {
        return $this->connection->fetchFirstColumn('SELECT id FROM tl_calendar ORDER by pid');
    }
}
