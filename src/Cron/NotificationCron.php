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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * We use a real cronjob:
 * wget -q -O /dev/null 'https://<domain>/_contao/cron' >/dev/null 2>&1.
 */
class NotificationCron extends AbstractController
{
    private RequestStack $requestStack;
    private EventRegistrationReminderController $eventRegistrationReminderController;
    private string $defaultLocale;
    private bool $allowWebScope;

    public function __construct(RequestStack $requestStack, EventRegistrationReminderController $eventRegistrationReminderController, string $defaultLocale, bool $allowWebScope)
    {
        $this->requestStack = $requestStack;
        $this->eventRegistrationReminderController = $eventRegistrationReminderController;
        $this->defaultLocale = $defaultLocale;
        $this->allowWebScope = $allowWebScope;
    }

    /**
     * @CronJob("10 * * * *", defaults={"_locale":"%sac_evt_reg_reminder.default_locale%"})
     *
     * @throws Exception
     * @throws StringsException
     */
    public function cron1(string $scope): Response
    {
        return;
        // Do not execute this cron job in the web scope
        if (Cron::SCOPE_WEB === $scope && !$this->allowWebScope) {
            return new Response('Application not allowed in web mode.');
        }

        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $request->setLocale($this->defaultLocale);

            return $this->eventRegistrationReminderController->run();
        }

        return new Response('No request detected');
    }

    /**
     * @CronJob("40 * * * *" defaults={"_locale":"%sac_evt_reg_reminder.default_locale%"})
     *
     * @throws Exception
     * @throws StringsException
     */
    public function cron(string $scope): Response
    {
        return;
        // Do not execute this cron job in the web scope
        if (Cron::SCOPE_WEB === $scope && !$this->allowWebScope) {
            return new Response('Application not allowed in web mode.');
        }

        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $request->setLocale($this->defaultLocale);

            return $this->eventRegistrationReminderController->run();
        }

        return new Response('No request detected');
    }
}
