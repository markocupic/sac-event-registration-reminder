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

namespace Markocupic\SacEventRegistrationReminder\Stopwatch;

class Stopwatch
{
    public const FLOOR_SECONDS = 'Y-m-d H:i:s';
    public const FLOOR_MINUTES = 'Y-m-d H:i';
    public const FLOOR_HOURS = 'Y-m-d H';
    public const FLOOR_DAY = 'Y-m-d';

    public function getRequestTime(string $strFloor = self::FLOOR_SECONDS): int
    {
        return strtotime(date($strFloor, (int) $_SERVER['REQUEST_TIME']));
    }

    public function getDuration(): int
    {
        return time() - $this->getRequestTime();
    }
}
