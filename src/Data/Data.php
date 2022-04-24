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

namespace Markocupic\SacEventRegistrationReminder\Data;

class Data
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getIterator(): \ArrayIterator
    {
        return (new \ArrayObject($this->data))->getIterator();
    }
}
