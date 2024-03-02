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

namespace Markocupic\SacEventRegistrationReminder\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Types\Types;
use Markocupic\SacEventRegistrationReminder\Data\Data;
use Markocupic\SacEventRegistrationReminder\Data\DataCollector;
use Markocupic\SacEventRegistrationReminder\Notification\NotificationGenerator;
use Markocupic\SacEventRegistrationReminder\Notification\NotificationHelper;
use Markocupic\SacEventRegistrationReminder\Stopwatch\Stopwatch;
use Markocupic\SacEventToolBundle\Config\EventSubscriptionState;
use Psr\Log\LoggerInterface;
use Safe\Exceptions\StringsException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('/_event_registration_reminder/{sid}', name: EventRegistrationReminderController::class, defaults: ['_locale' => '%sac_evt_reg_reminder.default_locale%'], methods: ['GET'])]
class EventRegistrationReminderController extends AbstractController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly DataCollector $dataCollector,
        private readonly NotificationGenerator $messageGenerator,
        private readonly NotificationHelper $notificationHelper,
        private readonly Stopwatch $stopwatch,
        private readonly bool $disable,
        private readonly string $sid,
        private readonly int $notificationLimitPerRequest,
        private readonly string $defaultLocale,
        private readonly LoggerInterface|null $logger,
    ) {
    }

    /**
     * @throws DbalException
     * @throws StringsException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(string $sid): Response
    {
        if ($sid !== $this->sid) {
            return new Response('You\'re not  allowed. Check your sid, please.');
        }

        return $this->run();
    }

    /**
     * @throws DbalException
     * @throws StringsException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function run(): Response
    {
        if ($this->disable) {
            return new Response('Application is disabled.', Response::HTTP_FORBIDDEN);
        }

        $this->framework->initialize();

        $state = EventSubscriptionState::SUBSCRIPTION_NOT_CONFIRMED;

        // Get the data array (time-consuming!)
        $arrData = $this->dataCollector->getData($state);

        $itCalendar = (new Data($arrData))->getIterator();

        $notificationCount = 0;
        $userCount = 0;

        // Process each calendar
        while ($itCalendar->valid()) {
            $calendarId = (int)$itCalendar->key();
            $arrUsers = $itCalendar->current();

            // Get the reminder interval in days
            $reminderIntervalD = (int)$this->connection->fetchOne('SELECT sendReminderEach FROM tl_calendar WHERE id = ?', [$calendarId]);

            // Process each backend user
            if (\is_array($arrUsers)) {
                $itUsers = (new Data($arrUsers))->getIterator();

                while ($itUsers->valid()) {
                    ++$userCount;

                    $userId = (int)$itUsers->key();

                    $arrUser = $itUsers->current();

                    // Generate the guest list
                    $strRegistrations = $this->messageGenerator
                        ->generate($arrUser, $userId);

                    // Get the notification
                    if (null !== ($notificationId = $this->getNotificationId($calendarId))) {
                        $arrTokens = [
                            'registrations' => $strRegistrations,
                        ];

                        // The notification limit is adjustable (Symfony Friendly Configuration)
                        ++$notificationCount;

                        if ($notificationCount > $this->notificationLimitPerRequest) {
                            break 2;
                        }

                        $receiptCollection = $this->notificationHelper->send($notificationId, $userId, $calendarId, $arrTokens, $this->defaultLocale);

                        if ($receiptCollection->count()) {
                            $userName = $this->connection->fetchOne('SELECT name FROM tl_user WHERE id = ?', [$userId]);

                            // Get the previous reminder added-on timestamp, if there is one
                            $arrReminder = $this->connection->fetchAssociative(
                                'SELECT * FROM tl_event_registration_reminder_notification WHERE user = ? AND calendar = ?',
                                [$userId, $calendarId]
                            );

                            $hasRecord = \is_array($arrReminder);

                            // Get the previous reminder added-on timestamp, if there is one
                            $prevReminderTstamp = $hasRecord ? (int)$arrReminder['dateAdded'] : 0;

                            // Add a suffix to the title to point out lazy instructors/tour guides ;-)
                            $blnAddSuffix = $prevReminderTstamp && ($prevReminderTstamp + 2 * $reminderIntervalD * 86400) > $this->stopwatch->getRequestTime();
                            $strSuffix = $blnAddSuffix ? sprintf(' (last time %s)', date('d.m.Y', $prevReminderTstamp)) : '';
                            $strTitle = sprintf('Sent a reminder to %s%s.', $userName, $strSuffix);

                            // Get the previous reminder added-on timestamp, if there is one
                            // and append it to the history
                            $arrHistory = $hasRecord ? explode("\n", (string)$arrReminder['history']) : [];

                            // Add the latest record to the top
                            array_unshift($arrHistory, sprintf('Sent a reminder to %s on %s;', $userName, date('d.m.Y H:i:s', $this->stopwatch->getRequestTime())));

                            // The history contains the latest 10 records only
                            $arrHistory = \array_slice($arrHistory, 0, 10);

                            $set = [
                                'tstamp'             => $this->stopwatch->getRequestTime(),
                                'dateAdded'          => $this->stopwatch->getRequestTime(),
                                'prevReminderTstamp' => $prevReminderTstamp,
                                'title'              => $strTitle,
                                'user'               => $userId,
                                'calendar'           => $calendarId,
                                'history'            => implode("\n", $arrHistory),
                            ];

                            // Create a new record that prevents
                            // the user from being notified again and again
                            // before the expiry of the "remindEach" limit
                            $affectedRows = $this->connection->insert('tl_event_registration_reminder_notification', $set);

                            if ($affectedRows) {
                                $lastInsertId = $this->connection->lastInsertId();

                                // Delete the old record
                                $this->connection->executeStatement(
                                    'DELETE FROM tl_event_registration_reminder_notification WHERE id != ? AND user = ? AND calendar = ?',
                                    [$lastInsertId, $userId, $calendarId],
                                );
                            }
                        }
                    }

                    $itUsers->next();
                }
            }

            $itCalendar->next();
        }

        // Log and send a response
        $responseMsg = sprintf('SAC event registration reminder: Processed %d users and sent %d notifications. Script runtime: %d s.', $userCount, $notificationCount, $this->stopwatch->getDuration());

        $this->log($responseMsg);

        return new Response($responseMsg);
    }

    private function getNotificationId(int $calendarId): int|null
    {
        $notificationId = $this->connection->fetchOne(
            'SELECT sendReminderNotification from tl_calendar WHERE id = :calendarId',
            [
                'calendarId' => $calendarId,
            ],
            [
                'calendarId' => Types::INTEGER,
            ],
        );

        if (false === $notificationId) {
            return null;
        }

        $notificationId = $this->connection->fetchOne(
            'SELECT id FROM tl_nc_notification WHERE id = :id',
            [
                'id' => (int)$notificationId,
            ],
            [
                'id' => Types::INTEGER,
            ]
        );

        if (false !== $notificationId) {
            return $notificationId;
        }

        return null;
    }

    private function log(string $text): void
    {
        $this->logger?->info(
            $text,
            ['contao' => new ContaoContext(__METHOD__, ContaoContext::CRON)]
        );
    }
}
