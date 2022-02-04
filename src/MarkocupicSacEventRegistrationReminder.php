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

namespace Markocupic\SacEventRegistrationReminder;

use Markocupic\SacEventRegistrationReminder\DependencyInjection\Compiler\AddCronSchedulePass;
use Markocupic\SacEventRegistrationReminder\DependencyInjection\MarkocupicSacEventRegistrationReminderExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MarkocupicSacEventRegistrationReminder extends Bundle
{
    public function getContainerExtension(): MarkocupicSacEventRegistrationReminderExtension
    {
        return new MarkocupicSacEventRegistrationReminderExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Set the cron schedule from configuration
        $container->addCompilerPass(new AddCronSchedulePass());
    }
}
