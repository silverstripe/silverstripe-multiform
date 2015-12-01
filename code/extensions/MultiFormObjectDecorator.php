<?php

/**
 * Decorate {@link DataObject}s which are required to be saved
 * to the database directly by a {@link MultiFormStep}.
 * Only needed for objects which aren't stored in the session,
 * which is the default.
 *
 * This decorator also augments get() requests to the datalayer
 * by automatically filtering out temporary objects.
 * You can override this filter by putting the following statement
 * in your WHERE clause:
 * `<MyDataObjectClass>`.`MultiFormIsTemporary` = 1
 *
 * @package multiform
 */
class MultiFormObjectDecorator extends DataExtension {

	private static $db = array(
		'MultiFormIsTemporary' => 'Boolean',
	);

	private static $has_one = array(
		'MultiFormSession' => 'MultiFormSession',
	);

	/**
	 * Augment any queries to MultiFormObjectDecorator and only
	 * return anything that isn't considered temporary.
	 */
	public function augmentSQL(SQLQuery &$query) {
		$where = $query->getWhere();
		if(!$where && !$this->wantsTemporary($query)) {
			$from = array_values($query->getFrom());
			$query->addWhere("{$from[0]}.\"MultiFormIsTemporary\" = '0'");
			return;
		}

		if(
			strpos($where[0], ".`ID` = ") === false
			&& strpos($where[0], ".ID = ") === false
			&& strpos($where[0], "ID = ") !== 0
			&& !$this->wantsTemporary($query)
		) {
			$from = array_values($query->getFrom());
			$query->addWhere("{$from[0]}.\"MultiFormIsTemporary\" = '0'");
		}
	}

	/**
	 * Determines if the current query is supposed
	 * to be exempt from the automatic filtering out
	 * of temporary records.
	 *
	 * @param SQLQuery $query
	 * @return boolean
	 */
	protected function wantsTemporary($query) {
		foreach($query->getWhere() as $whereClause) {
			$from = array_values($query->getFrom());
			// SQLQuery will automatically add double quotes and single quotes to values, so check against that.
			if($whereClause == "{$from[0]}.\"MultiFormIsTemporary\" = '1'") {
				return true;
			}
		}

		return false;
	}
}
