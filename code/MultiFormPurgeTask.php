<?php

/**
 * Task to clean out all {@link MultiFormSession} objects from the database.
 * 
 * Setup Instructions:
 * You need to create an automated task for your system (cronjobs on unix)
 * which triggers the run() method through cli-script.php:
 * /your/path/sapphire/cli-script.php MultiFormPurgeTask/run
 * 
 * @package multiform
 */
class MultiFormPurgeTask extends DailyTask {
	
	/**
	 * Days after which unfinished sessions
	 * expire and are automatically deleted
	 * by a cronjob/ScheduledTask.
	 * 
	 * @usedby {@link MultiFormPurgeTask}
	 * @var int
	 */
	public static $session_expiry_days = 7;
	
	
	public function run() {
		$controllers = ClassInfo::subclassesFor('MultiForm');
		
		if($controllers) foreach($controllers as $controllerClass) {
			$controller = new $controllerClass();
			$sessions = $controller->getExpiredSessions();
			$sessionDeleteCount = 0;
			if($sessions) foreach($sessions as $session) {
				$session->purgeStoredData();
				if($session->delete()) $sessionDeleteCount++;
			}
		}
	}
	
	protected function getExpiredSessions() {
		$sessions = new DataObjectSet();
		
		$implementors = Object::implementors_for_extension('MultiFormObjectDecorator');
		if($implementors) foreach($implementors as $implementorClass) {
			$sessions->merge(
				DataObject::get(
					$implementorClass, 
					"`{$implementorClass}`.`MultiFormIsTemporary` = 1
						AND DATEDIFF(NOW(), `{$implementorClass}`.`Created`) > " . self::$session_expiry_days
				)
			);
		}
		
		return $sessions;
	}
	
}

?>