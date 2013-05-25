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
	
	public function updateDBFields() {
		return array(
			'db' => array(
				'MultiFormIsTemporary' => 'Boolean',
			),
			'has_one' => array(
				'MultiFormSession' => 'MultiFormSession',
			)
		);
	}
	
	public function augmentSQL(SQLQuery &$query) {
		// If you're querying by ID, ignore the sub-site - this is a bit ugly...
		if(
			strpos($query->where[0], ".`ID` = ") === false 
			&& strpos($query->where[0], ".ID = ") === false 
			&& strpos($query->where[0], "ID = ") !== 0
			&& !$this->wantsTemporary($query)
		) {
			$query->where[] = "\"{$query->from[0]}\".\"MultiFormIsTemporary\" = 0"; 
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
		foreach($query->where as $whereClause) {
			if($whereClause == "\"{$query->from[0]}\".\"MultiFormIsTemporary\" = 1") return true;
		}
		return false;
	}
	
}
