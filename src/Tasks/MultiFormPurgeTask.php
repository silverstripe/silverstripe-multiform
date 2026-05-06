<?php

namespace SilverStripe\MultiForm\Tasks;

use DateInterval;
use DateTimeImmutable;
use SilverStripe\Dev\BuildTask;
use SilverStripe\MultiForm\Models\MultiFormSession;
use SilverStripe\ORM\DataList;
use Symfony\Component\Console\Input\InputInterface;
use SilverStripe\PolyExecution\PolyOutput;

/**
 * Task to clean out all {@link MultiFormSession} objects from the database.
 *
 * Setup Instructions:
 * You need to create an automated task for your system (cronjobs on unix)
 * which triggers the process() method through cli-script.php:
 * `php framework/cli-script.php MultiFormPurgeTask`
 * or
 * `framework/sake MultiFormPurgeTask`
 *
 */
class MultiFormPurgeTask extends BuildTask
{
    /**
     * Days after which sessions expire and are automatically deleted.
     */
    private static int $session_expiry_days = 7;

    protected static string $commandName = 'multiform-purge';

    protected static string $description = 'Purge expired MultiForm sessions';

    /**
     * Run this cron task.
     *
     * Go through all MultiFormSession records that
     * are older than the days specified in $session_expiry_days
     * and delete them.
     */
    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $sessions = $this->getExpiredSessions();
        $delCount = 0;
        foreach ($sessions as $session) {
            $session->delete();
            $delCount++;
        }

        $output->writeln(sprintf(
            '%s session records deleted that were older than %s days.',
            $delCount,
            $this->config()->get('session_expiry_days')
        ));

        return 0;
    }

    /**
     * Return all MultiFormSession database records that are older than
     * the days specified in $session_expiry_days
     *
     * @return DataList<MultiFormSession>
     */
    protected function getExpiredSessions(): DataList
    {
        $interval = new DateInterval('P' . $this->config()->get('session_expiry_days') . 'D');

        /** @var DataList<MultiFormSession> $sessions */
        $sessions = MultiFormSession::get()
            ->filter([
                "Created:LessThan" => (new DateTimeImmutable())->sub($interval)
            ]);
        return $sessions;
    }
}
