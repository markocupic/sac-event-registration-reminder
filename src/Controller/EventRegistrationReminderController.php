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
use Doctrine\DBAL\Exception;
use Markocupic\SacEventRegistrationReminder\Data\Data;
use Markocupic\SacEventRegistrationReminder\Data\DataCollector;
use Markocupic\SacEventRegistrationReminder\Notification\NotificationGenerator;
use Markocupic\SacEventRegistrationReminder\Notification\NotificationHelper;
use Markocupic\SacEventToolBundle\Config\EventSubscriptionLevel;
use NotificationCenter\Model\Notification;
use Psr\Log\LoggerInterface;
use Safe\Exceptions\StringsException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
    private bool $disable;
    private string $sid;
    private int $notificationLimitPerRequest;
    private string $defaultLocale;
    private ?LoggerInterface $logger;

    public function __construct(ContaoFramework $framework, Connection $connection, DataCollector $dataCollector, NotificationGenerator $messageGenerator, NotificationHelper $notificationHelper, bool $disable, string $sid, int $notificationLimitPerRequest, string $defaultLocale, ?LoggerInterface $logger)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->dataCollector = $dataCollector;
        $this->messageGenerator = $messageGenerator;
        $this->notificationHelper = $notificationHelper;
        $this->disable = $disable;
        $this->sid = $sid;
        $this->notificationLimitPerRequest = $notificationLimitPerRequest;
        $this->defaultLocale = $defaultLocale;
        $this->logger = $logger;
    }

    /**
     * @param string $sid
     * @return Response
     * @throws Exception
     * @throws StringsException
     */
    public function __invoke(string $sid): Response
    {
        if ($sid !== $this->sid) {
            return new Response('You\'re not  allowed. Check your sid, please.');
        }

        return $this->run();
    }

    /**
     * @throws Exception
     * @throws StringsException
     */
    public function run(): Response
    {
        if ($this->disable) {
            return new Response('Application is disabled.', Response::HTTP_FORBIDDEN);
        }

        $this->framework->initialize();

        $startTime = time();

        $state = EventSubscriptionLevel::SUBSCRIPTION_NOT_CONFIRMED;

        // Get the data array (time-consuming!)
        $arrData = $this->dataCollector->getData($state);

        $itCalendar = (new Data($arrData))->getIterator();

        $emailCount = 0;
        $userCount = 0;

        // Process each calendar
        while ($itCalendar->valid()) {
            $calendarId = (int) $itCalendar->key();
            $arrUsers = $itCalendar->current();

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
                        ++$emailCount;

                        if ($emailCount > $this->notificationLimitPerRequest) {
                            break 2;
                        }

                        $arr = $this->notificationHelper->send($notification, $userId, $calendarId, $arrTokens, $this->defaultLocale);

                        if (!empty($arr) && \is_array($arr)) {
                            $userName = $this->connection->fetchOne('SELECT name FROM tl_user WHERE id = ?', [$userId]);
                            
                            $addedOn = $this->connection->fetchOne('SELECT addedOn FROM tl_event_registration_reminder_notification WHERE user = ? AND calendar = ?', [$userId, $calendarId]);
                            if (false === $addedOn || '' === $addedOn || 0 == $addedOn) {
                                $addedOn = $startTime;
                            } elseif ((time() - (int) $addedOn) > (15 /* days */ * 86400)) {
                                $addedOn = $startTime;  // resets addedOn time after longer period without any notification sent to user
                            } else {
                                // do not update addedOn time to see since when the user has received notifications
                            }

                            $set = [
                                'tstamp' => $startTime,
                                'addedOn' => $addedOn,
                                'title' => 'Sent a reminder to '.$userName.' (since '.date("d.m.Y", $addedOn).').',
                                'user' => $userId,
                                'calendar' => $calendarId,
                            ];

                            // We create a new record that prevents
                            // the user from being notified again
                            // before the expiry of the "remindEach" limit
                            $affectedRows = $this->connection->insert('tl_event_registration_reminder_notification', $set);

                            if ($affectedRows) {
                                $lastInsertId = $this->connection->lastInsertId();

                                // Delete old records
                                $this->connection->executeQuery(
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
        $responseMsg = sprintf('Traversed %s users and sent %s notifications. Script runtime: %s s.', $userCount, $emailCount, time() - $startTime);

        $this->log($responseMsg);

        return new Response($responseMsg);
    }

    /**
     * @throws Exception
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
