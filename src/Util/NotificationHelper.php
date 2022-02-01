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

namespace Markocupic\SacEventRegistrationReminder\Util;

use Contao\UserModel;
use Doctrine\DBAL\Connection;
use NotificationCenter\Model\Notification;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;

class NotificationHelper
{
    private Connection $connection;

    private ?Notification $notification;

    private ?UserModel $user;

    private ?array $tokens;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws StringsException
     *
     * @return array
     */
    public function send(Notification $notification, int $userId, array $arrTokens)
    {
        $this->initialize($notification, $userId, $arrTokens);

        $this->prepareTokens();

        return $this->notification->send($this->tokens);
    }

    /**
     * @throws StringsException
     */
    private function initialize(Notification $notification, int $userId, array $arrTokens): void
    {
        $this->notification = $notification;

        if (null === ($this->user = UserModel::findByPk($userId))) {
            throw new \Exception(sprintf('User with ID %s not found', $userId));
        }

        $this->tokens = $arrTokens;
    }

    private function prepareTokens(): void
    {
        $this->tokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];
        $this->tokens['instructor_email'] = $this->user->email;
        $this->tokens['instructor_firstname'] = $this->user->firstname;
        $this->tokens['instructor_lastname'] = $this->user->lastname;
        $this->tokens['instructor_name'] = $this->user->name;
    }
}
