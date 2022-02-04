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
use Contao\CoreBundle\Exception\RedirectResponseException;
use Markocupic\SacEventRegistrationReminder\Controller\EventRegistrationReminderController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Define the configuration in the configuration %sac_evt_reg_reminder.cron_schedule%.
 *
 * Use a real cronjob:
 * wget -q -O /dev/null 'https://<domain>/_contao/cron' >/dev/null 2>&1.
 */
class NotificationCron extends AbstractController
{
    private UrlGeneratorInterface $router;
    private bool $allowWebScope;
    private string $sid;

    public function __construct(UrlGeneratorInterface $router, bool $allowWebScope, string $sid)
    {
        $this->router = $router;
        $this->allowWebScope = $allowWebScope;
        $this->sid = $sid;
    }

    public function __invoke(string $scope): void
    {
        // Do not execute this cron job in the web scope
        // if $this->allowWebScope is set to false (configuration)
        if (Cron::SCOPE_WEB === $scope && !$this->allowWebScope) {
            return;
        }

        // Redirect to the controller
        $url = $this->router
            ->generate(
                EventRegistrationReminderController::class,
                ['sid' => $this->sid],
            )
        ;

        throw new RedirectResponseException($url);
    }
}
