<?php

declare(strict_types=1);

/*
 * This file is part of Contao Calculator Form.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/contao-calculator-form
 */

namespace Markocupic\SacEventRegistrationReminder\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
class AddCronSchedulePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('Markocupic\SacEventRegistrationReminder\Cron\NotificationCron')) {
            return;
        }

        if(!$container->hasParameter('sac_evt_reg_reminder.cron_schedule'))
        {
            return;
        }

        $cronSchedule = $container->getParameter('sac_evt_reg_reminder.cron_schedule');

        $cron = $container->findDefinition('Markocupic\SacEventRegistrationReminder\Cron\NotificationCron');

        $cron->clearTag('contao.cronjob');

        $cron->addTag('contao.cronjob',[
            'interval' => $cronSchedule,
        ]);

        //die(print_r($cron->getTags(),true));

    }
}
