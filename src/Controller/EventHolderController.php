<?php

namespace CWP\Cwp\Controller;

use CWP\Cwp\Controller\DatedUpdateHolderController,
    SilverStripe\ORM\PaginatedList;

/**
 * The parameters apply in the following preference order:
 *  - Highest priority: Tag & date (or date range)
 *  - Month (and Year)
 *  - Pagination
 *
 * So, when the user click on a tag link, the pagination, and month will be reset, but not the date filter. Also,
 * changing the date will not affect the tag, but will reset the month and pagination.
 *
 * When the user clicks on a month, pagination will be reset, but tags retained. Pagination retains all other
 * parameters.
 */
class EventHolderController extends DatedUpdateHolderController
{

    /**
     *
     * @return string
     */
    public function getUpdateName()
    {
        $params = $this->parseParams();
        if ($params['upcomingOnly']) {
            return _t('EventHolder.Upcoming', 'Upcoming events');
        }

        return 'Events';
    }

    /**
     * Parse URL parameters.
     *
     * @param  $produceErrorMessages Set to false to omit session messages.
     * @return array
     */
    public function parseParams($produceErrorMessages = true)
    {
        $params = parent::parseParams($produceErrorMessages);

        // We need to set whether or not we're supposed to be displaying only upcoming events or all events.
        $params['upcomingOnly'] = !($params['from'] || $params['to'] || $params['year'] || $params['month']);

        return $params;
    }

    /**
     * Get the events based on the current query.
     *
     * @param  int $pageSize
     * @return PaginatedList
     */
    public function FilteredUpdates($pageSize = 20)
    {
        $params = $this->parseParams();

        $items = $this->Updates(
                $params['tag'], $params['from'], $params['to'], $params['year'], $params['month']
        );

        if ($params['upcomingOnly']) {
            $items = $items->filter(array('Date:LessThan:Not' => SS_Datetime::now()->Format('Y-m-d')));
        }

        // Apply pagination
        $list = PaginatedList::create($items, $this->request);
        $list->setPageLength($pageSize);

        return $list;
    }

}
