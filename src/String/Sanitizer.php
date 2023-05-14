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

namespace Markocupic\SacEventRegistrationReminder\String;

class Sanitizer
{
    /**
     * Trim & decode strings.
     */
    public function sanitize(string $strString): string
    {
        $strString = trim($strString);
        $strString = html_entity_decode($strString, ENT_QUOTES);

        return preg_replace('/&(amp;)?/i', '&', $strString);
    }
}
