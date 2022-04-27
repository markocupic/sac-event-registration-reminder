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

namespace Markocupic\SacEventRegistrationReminder\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Markocupic\SacEventRegistrationReminder\Data\Data;
use Markocupic\SacEventRegistrationReminder\Data\DataCollector;
use Markocupic\SacEventRegistrationReminder\Notification\NotificationGenerator;
use Markocupic\SacEventRegistrationReminder\Notification\NotificationHelper;
use Markocupic\SacEventRegistrationReminder\Stopwatch\Stopwatch;
use Markocupic\SacEventToolBundle\Config\EventSubscriptionLevel;
use NotificationCenter\Model\Notification;
use Psr\Log\LoggerInterface;
use Safe\Exceptions\StringsException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * @Route("/_event_registration_reminder/{sid}", name=EventRegistrationReminderController::class, methods={"GET"}, defaults={"_locale":"%sac_evt_reg_reminder.default_locale%"})
 */
class EventRegistrationReminderController extends AbstractController
{
    private ContaoFramework $framework;
    private Connection $connection;
    private DataCollector $dataCollector;
    private NotificationGenerator $messageGenerator;
    private NotificationHelper $notificationHelper;
    private Stopwatch $stopwatch;
    private bool $disable;
    private string $sid;
    private int $notificationLimitPerRequest;
    private string $defaultLocale;
    private ?LoggerInterface $logger;

    public function __construct(ContaoFramework $framework, Connection $connection, DataCollector $dataCollector, NotificationGenerator $messageGenerator, NotificationHelper $notificationHelper, Stopwatch $stopwatch, bool $disable, string $sid, int $notificationLimitPerRequest, string $defaultLocale, ?LoggerInterface $logger)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->dataCollector = $dataCollector;
        $this->messageGenerator = $messageGenerator;
        $this->notificationHelper = $notificationHelper;
        $this->stopwatch = $stopwatch;
        $this->disable = $disable;
        $this->sid = $sid;
        $this->notificationLimitPerRequest = $notificationLimitPerRequest;
        $this->defaultLocale = $defaultLocale;
        $this->logger = $logger;
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

        $state = EventSubscriptionLevel::SUBSCRIPTION_NOT_CONFIRMED;

        // Get the data array (time-consuming!)
        $arrData = $this->dataCollector->getData($state);

        $itCalendar = (new Data($arrData))->getIterator();

        $notificationCount = 0;
        $userCount = 0;

        // Process each calendar
        while ($itCalendar->valid()) {
            $calendarId = (int) $itCalendar->key();
            $arrUsers = $itCalendar->current();
            // Get the reminder interval in days
            $reminderIntervalD = (int) $this->connection->fetchOne('SELECT sendReminderEach FROM tl_calendar WHERE id = ?', [$calendarId]);
            // Process each backend user
            if (\is_array($arrUsers)) {
                $itUsers = (new Data($arrUsers))->getIterator();

                while ($itUsers->valid()) {
                    ++$userCount;

                    $userId = (int) $itUsers->key();

                    $arrUser = $itUsers->current();

                    // Generate the guest list
                    $strRegistrations = $this->messageGenerator
                        ->generate($arrUser, $userId)
                    ;

                    // Get the notification
                    if (null !== ($notification = $this->getNotification($calendarId))) {
                        $arrTokens = [
                            'registrations' => $strRegistrations,
                        ];

                        // The notification limit is adjustable (Symfony Friendly Configuration)
                        ++$notificationCount;

                        if ($notificationCount > $this->notificationLimitPerRequest) {
                            break 2;
                        }

                        $arr = $this->notificationHelper->send($notification, $userId, $calendarId, $arrTokens, $this->defaultLocale);

                        if (!empty($arr) && \is_array($arr)) {
                            $userName = $this->connection->fetchOne('SELECT name FROM tl_user WHERE id = ?', [$userId]);

                            // Get the previous reminder added-on timestamp, if there is one
                            $arrReminder = $this->connection->fetchAssociative(
                                'SELECT * FROM tl_event_registration_reminder_notification WHERE user = ? AND calendar = ?',
                                [$userId, $calendarId]
                            );

                            $hasRecord = \is_array($arrReminder);

                            // Get the previous reminder added-on timestamp, if there is one
                            $prevReminderTstamp = $hasRecord ? (int) $arrReminder['dateAdded'] : 0;

                            // Add a suffix to the title to point out lazy instructors/tour guides ;-)
                            $blnAddSuffix = $prevReminderTstamp && ($prevReminderTstamp + 2 * $reminderIntervalD * 86400) > $this->stopwatch->getRequestTime();
                            $strSuffix = $blnAddSuffix ? sprintf(' (last time %s)', date('d.m.Y', $prevReminderTstamp)) : '';
                            $strTitle = sprintf('Sent a reminder to %s%s.', $userName, $strSuffix);

                            // Get the previous reminder added-on timestamp, if there is one
                            // and append it to the history
                            $arrHistory = $hasRecord ? explode("\n", (string) $arrReminder['history']) : [];

                            // Add the latest record to the top
                            array_unshift($arrHistory, sprintf('Sent a reminder to %s on %s;', $userName, date('d.m.Y H:i:s', $this->stopwatch->getRequestTime())));

                            // The history contains the latest 10 records only
                            $arrHistory = \array_slice($arrHistory, 0, 10);

                            $set = [
                                'tstamp' => $this->stopwatch->getRequestTime(),
                                'dateAdded' => $this->stopwatch->getRequestTime(),
                                'prevReminderTstamp' => $prevReminderTstamp,
                                'title' => $strTitle,
                                'user' => $userId,
                                'calendar' => $calendarId,
                                'history' => implode("\n", $arrHistory),
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

    /**
     * @throws DbalException
     */
    private function getNotification(int $calendarId): ?Notification
    {
        if ($id = $this->connection->fetchOne('SELECT sendReminderNotification from tl_calendar WHERE id = ?', [$calendarId])) {
            $notificationAdapter = $this->framework->getAdapter(Notification::class);

            if (null !== ($notification = $notificationAdapter->findByPk($id))) {
                return $notification;
            }
        }

        return null;
    }

    private function log(string $text): void
    {
        if (null !== $this->logger) {
            $this->logger->info(
                $text,
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::CRON)]
            );
        }
    }
}
