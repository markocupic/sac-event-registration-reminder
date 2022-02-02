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

namespace Markocupic\SacEventRegistrationReminder\Cron;

use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\ServiceAnnotation\CronJob;
use Doctrine\DBAL\Exception;
use Markocupic\SacEventRegistrationReminder\Controller\EventRegistrationReminderController;
use Safe\Exceptions\StringsException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * We use a real cronjob:
 * wget -q -O /dev/null 'https://<domain>/_contao/cron' >/dev/null 2>&1.
 */
class NotificationCron extends AbstractController
{
    private EventRegistrationReminderController $eventRegistrationReminderController;
    private bool $allowWebScope;

    public function __construct(EventRegistrationReminderController $eventRegistrationReminderController, bool $allowWebScope)
    {
        $this->eventRegistrationReminderController = $eventRegistrationReminderController;
        $this->allowWebScope = $allowWebScope;
    }

    /**
     * @CronJob("10 * * * *")
     *
     * @throws Exception
     * @throws StringsException
     */
    public function cron1(string $scope): Response
    {
        // Do not execute this cron job in the web scope
        if (Cron::SCOPE_WEB === $scope && !$this->allowWebScope) {
            return new Response('Application not allowed in web mode.');
        }

        return $this->eventRegistrationReminderController->run();
    }

    /**
     * @CronJob("40 * * * *")
     *
     * @throws Exception
     * @throws StringsException
     */
    public function cron(string $scope): Response
    {
        // Do not execute this cron job in the web scope
        if (Cron::SCOPE_WEB === $scope && !$this->allowWebScope) {
            return new Response('Application not allowed in web mode.');
        }

        return $this->eventRegistrationReminderController->run();
    }
}
