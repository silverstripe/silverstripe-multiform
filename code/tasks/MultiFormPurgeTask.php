<?php

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
 * @package multiform
 */
class MultiFormPurgeTask extends BuildTask {
	
	/**
	 * Days after which sessions expire and
	 * are automatically deleted.
	 * 
	 * @var int
	 */
	public static $session_expiry_days = 7;

	/**
	 * Run this cron task.
	 * 
	 * Go through all MultiFormSession records that
	 * are older than the days specified in $session_expiry_days
	 * and delete them.
	 */
	public function run($request) {
		$sessions = $this->getExpiredSessions();
		$delCount = 0;
		if($sessions) foreach($sessions as $session) {
			$session->delete();
			$delCount++;
		}
		echo $delCount . ' session records deleted that were older than ' . self::$session_expiry_days . ' days.';
	}

	/**
	 * Return all MultiFormSession database records that are older than
	 * the days specified in $session_expiry_days
	 *
	 * @return DataObjectSet
	 */
	protected function getExpiredSessions() {
		return DataObject::get(
			'MultiFormSession',
			"DATEDIFF(NOW(), \"MultiFormSession\".\"Created\") > " . self::$session_expiry_days
		);
	}
	
}
