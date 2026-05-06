<?php

namespace SilverStripe\MultiForm\Extensions;

use SilverStripe\ORM\DataQuery;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\MultiForm\Models\MultiFormSession;

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
 */
class MultiFormObjectDecorator extends Extension
{
    private static $db = [
        'MultiFormIsTemporary' => 'Boolean',
    ];

    private static $has_one = [
        'MultiFormSession' => MultiFormSession::class,
    ];

    /**
     * Augment any queries to MultiFormObjectDecorator and only
     * return anything that isn't considered temporary.
     * @param SQLSelect $query
     * @param DataQuery|null $dataQuery
     */
    public function augmentSQL(SQLSelect $query, DataQuery $dataQuery = null)
    {
        $where = $query->getWhere();
        if (!$where && !$this->wantsTemporary($query)) {
            $from = array_values($query->getFrom());
            $query->addWhere("{$from[0]}.\"MultiFormIsTemporary\" = '0'");
            return;
        }
        $filterKey = key($where[0]);
        if (strpos($filterKey, ".`ID` = ") === false
            && strpos($filterKey, ".ID = ") === false
            && strpos($filterKey, "ID = ") !== 0
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
     * @param SQLSelect $query
     * @return boolean
     */
    protected function wantsTemporary($query)
    {
        foreach ($query->getWhere() as $whereClause) {
            $from = array_values($query->getFrom());
            // SQLQuery will automatically add double quotes and single quotes to values, so check against that.
            $key = key($whereClause);
            if ($key == "{$from[0]}.\"MultiFormIsTemporary\" = ?" && current($whereClause[$key]) == 1) {
                return true;
            }
        }

        return false;
    }
}
