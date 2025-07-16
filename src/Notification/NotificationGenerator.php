<?php

declare(strict_types=1);

/*
 * This file is part of SAC Event Registration Reminder.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-registration-reminder
 */

namespace Markocupic\SacEventRegistrationReminder\Notification;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Contao\UserModel;
use Markocupic\SacEventRegistrationReminder\Stopwatch\Stopwatch;
use Markocupic\SacEventRegistrationReminder\String\Sanitizer;
use Markocupic\SacEventToolBundle\Model\CalendarEventsMemberModel;
use Safe\Exceptions\StringsException;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class NotificationGenerator
{
    private array|null $data;

    private UserModel|null $user;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly LocaleSwitcher $localeSwitcher,
        private readonly Environment $twig,
        private readonly TranslatorInterface $translator,
        private readonly Sanitizer $sanitizer,
        private readonly Stopwatch $stopwatch,
        private readonly string $defaultLocale,
    ) {
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws StringsException
     * @throws SyntaxError
     */
    public function generate(array $arrData, int $userId): string
    {
        $this->initialize($arrData, $userId);

        return $this->render($this->prepareTwigData());
    }

    /**
     * @throws StringsException
     * @throws \Exception
     */
    private function initialize(array $arrData, int $userId): void
    {
        $this->data = $arrData;

        $userModelAdapter = $this->framework->getAdapter(UserModel::class);

        if (null === ($this->user = $userModelAdapter->findById($userId))) {
            throw new \Exception(\sprintf('User with ID %d not found', $userId));
        }
    }

    private function prepareTwigData(): array
    {
        // If this function is called via a cron request, it is possible that the
        // language cannot be determined. Therefore, the language has to be forced
        // manually. See: https://symfony.com/blog/new-in-symfony-6-1-locale-switcher
        $this->localeSwitcher->setLocale($this->defaultLocale);
        $system = $this->framework->getAdapter(System::class);
        $system->loadLanguageFile('default');

        $arrData = [];

        // Use predictive time of processing start (+ 60s for rounding issues)
        $currentTime = $this->stopwatch->getRequestTime() + 60;

        $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

        foreach ($this->data as $eventId => $arrEvent) {
            $event = $calendarEventsModelAdapter->findById($eventId);

            if (null === $event) {
                continue;
            }

            $rowEvent = [];
            $rowEvent['event_title'] = $this->sanitizer->sanitize($event->title);
            $rowEvent['trans']['event_type'] = $this->translator->trans('MSC.'.$event->eventType, [], 'contao_default');

            $rowEvent['registrations_outside_deadline'] = [];
            $rowEvent['registrations_within_deadline'] = [];

            foreach (['outside_deadline', 'within_deadline'] as $deadlineKey) {
                $rowEvent['has_registrations_'.$deadlineKey] = !empty($arrEvent[$deadlineKey]);

                foreach ($arrEvent[$deadlineKey] as $registrationId) {
                    $registration = CalendarEventsMemberModel::findById($registrationId);

                    if (null === $registration) {
                        continue;
                    }

                    $elapsedSeconds = $currentTime - (int) $registration->dateAdded;

                    if ($elapsedSeconds < 0) {
                        $elapsedSeconds = 0;
                    }

                    $daysRegistered = floor($elapsedSeconds / 86400);

                    $rowEvent['registrations_'.$deadlineKey][] = [
                        'firstname' => $registration->firstname,
                        'lastname' => $registration->lastname,
                        'trans' => [
                            'days_registered' => $this->translator->trans('MSC.serr_days_registered', [$daysRegistered], 'contao_default'),
                            'participant' => $this->translator->trans('MSC.serr_participant_'.$registration->gender, [], 'contao_default'),
                            'sac_member_id' => $this->translator->trans('MSC.serr_sac_member_id', [(int) $registration->sacMemberId], 'contao_default'),
                        ],
                    ];
                }
            }

            $arrData[] = $rowEvent;
        }

        return $arrData;
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function render(array $arrData): string
    {
        return $this->twig->render('@MarkocupicSacEventRegistrationReminder/message_partial.twig', [
            'user' => $this->user->row(),
            'events' => $arrData,
        ]);
    }
}
