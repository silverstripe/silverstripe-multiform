<?php

namespace SilverStripe\MultiForm\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\MultiForm\Models\MultiFormSession;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Queries\SQLSelect;

/**
 * Decorate {@link DataObject}s which are required to be saved to the database directly by a {@link MultiFormStep}.
 * Only needed for objects which aren't stored in the session, which is the default.
 *
 * @extends Extension<DataObject>
 */
class MultiFormObjectExtension extends Extension
{
    private static array $db = [
        'MultiFormIsTemporary' => 'Boolean',
    ];

    private static array $has_one = [
        'MultiFormSession' => MultiFormSession::class,
    ];

    /**
     * Augment any queries to MultiFormObjectExtension and only return anything that isn't considered temporary.
     */
    public function augmentSQL(SQLSelect $query, ?DataQuery $dataQuery = null): void
    {
        $where = $query->getWhere();
        if (!$where && !$this->wantsTemporary($query)) {
            $from = array_values($query->getFrom());
            $query->addWhere("{$from[0]}.\"MultiFormIsTemporary\" = '0'");
            return;
        }
        $filterKey = key($where[0]);
        if (
            strpos($filterKey, ".`ID` = ") === false
            && strpos($filterKey, ".ID = ") === false
            && strpos($filterKey, "ID = ") !== 0
            && !$this->wantsTemporary($query)
        ) {
            $from = array_values($query->getFrom());
            $query->addWhere("{$from[0]}.\"MultiFormIsTemporary\" = '0'");
        }
    }

    /**
     * Determines if the current query is supposed to be exempt from the automatic filtering out of temporary records.
     */
    protected function wantsTemporary(SQLSelect $query): bool
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
