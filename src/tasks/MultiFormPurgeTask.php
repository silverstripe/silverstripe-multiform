<?php

namespace SilverStripe\MultiForm\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\MultiForm\Models\MultiFormSession;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
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
     * Days after which sessions expire and
     * are automatically deleted.
     *
     * @var int
     */
    private static $session_expiry_days = 7;

    private static $segment = 'MultiFormPurgeTask';

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
        if ($sessions) {
            foreach ($sessions as $session) {
                $session->delete();
                $delCount++;
            }
        }
        $output->writeln($delCount . ' session records deleted that were older than '
            . $this->config()->get('session_expiry_days') . ' days.');
        return 0;
    }

    /**
     * Return all MultiFormSession database records that are older than
     * the days specified in $session_expiry_days
     *
     * @return DataList
     */
    protected function getExpiredSessions()
    {
        return DataObject::get(
            MultiFormSession::class,
            "DATEDIFF(NOW(), \"MultiFormSession\".\"Created\") > " . $this->config()->get('session_expiry_days')
        );
    }
}
