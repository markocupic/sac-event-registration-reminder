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

namespace Markocupic\SacEventRegistrationReminder\DependencyInjection\Compiler;

use Markocupic\SacEventRegistrationReminder\Cron\NotificationCron;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
class AddCronSchedulePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(NotificationCron::class)) {
            return;
        }

        if (!$container->hasParameter('sac_evt_reg_reminder.cron_schedule')) {
            return;
        }

        $cronSchedule = $container->getParameter('sac_evt_reg_reminder.cron_schedule');

        $cron = $container->findDefinition(NotificationCron::class);

        $cron->clearTag('contao.cronjob');

        $cron->addTag('contao.cronjob', [
            'interval' => $cronSchedule,
        ]);
    }
}
