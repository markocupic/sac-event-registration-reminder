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

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Doctrine\DBAL\Connection;

class Calendar
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @Callback(table="tl_calendar", target="fields.sendReminderNotification.options")
     */
    public function getNotifications(): array
    {
        $arrOptions = [];

        $stmt = $this->connection->executeQuery('SELECT * FROM tl_nc_notification', []);

        while (false !== ($row = $stmt->fetchAssociative())) {
            $arrOptions[$row['id']] = $row['title'];
        }

        return $arrOptions;
    }
}
