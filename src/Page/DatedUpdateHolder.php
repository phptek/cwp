<?php

namespace CWP\Cwp\Page;

use Page,
    SilverStripe\Taonomy\TaxonomyTerm,
    SilverStripe\Core\Config\Config,
    SilverStripe\Core\ClassInfo,
    SilverStripe\Core\Convert,
    SilverStripe\Control\Director,
    SilverStripe\Control\HTTP,
    SilverStripe\ORM\ArrayList;

// Meant as an abstract base class.
class DatedUpdateHolder extends Page
{

    /**
     *
     * @var string
     * @config
     */
    private static $hide_ancestor = 'DatedUpdateHolder';
    private static $update_name = 'Updates';
    private static $update_class = 'DatedUpdatePage';
    private static $singular_name = 'Dated Update Holder';
    private static $plural_name = 'Dated Update Holders';
    private static $table_name = 'DatedUpdateHolder';

    /**
     * Find all distinct tags (TaxonomyTerms) associated with the DatedUpdatePages under this holder.
     */
    public function UpdateTags()
    {
        $tags = TaxonomyTerm::get()
                ->innerJoin('BasePage_Terms', '"TaxonomyTerm"."ID"="BasePage_Terms"."TaxonomyTermID"')
                ->innerJoin(
                        'SiteTree', sprintf('"SiteTree"."ID" = "BasePage_Terms"."BasePageID" AND "SiteTree"."ParentID" = \'%d\'', $this->ID)
                )
                ->sort('Name');

        return $tags;
    }

    /**
     * Wrapper to find all updates belonging to this holder, based on some filters.
     *
     * @return DataList | PaginatedList
     */
    public function Updates($tagID = null, $dateFrom = null, $dateTo = null, $year = null, $monthNumber = null)
    {
        $className = Config::inst()->get($this->ClassName, 'update_class');

        return static::AllUpdates($className, $this->ID, $tagID, $dateFrom, $dateTo, $year, $monthNumber);
    }

    /**
     * Find all site's updates, based on some filters.
     * Omitting parameters will prevent relevant filters from being applied. The filters are ANDed together.
     *
     * @param string $className The name of the class to fetch.
     * @param int|null $parentID The ID of the holder to extract the updates from.
     * @param int|null $tagID The ID of the tag to filter the updates by.
     * @param string|null $dateFrom The beginning of a date filter range.
     * @param string|null $dateTo The end of the date filter range. If empty, only one day will be searched for.
     * @param int|null $year Numeric value of the year to show.
     * @param int|null $monthNumber Numeric value of the month to show.
     *
     * @return DataList | PaginatedList
     */
    public static function AllUpdates($className = 'DatedUpdatePage', $parentID = null, $tagID = null, $dateFrom = null, $dateTo = null, $year = null, $monthNumber = null)
    {

        $items = $className::get();
        $dbTableName = ClassInfo::table_for_object_field($className, 'Date');
        if (!$dbTableName) {
            $dbTableName = 'DatedUpdatePage';
        }

        // Filter by parent holder.
        if (isset($parentID)) {
            $items = $items->filter(array('ParentID' => $parentID));
        }

        // Filter down to a single tag.
        if (isset($tagID)) {
            $items = $items->innerJoin(
                            'BasePage_Terms', sprintf('"%s"."ID" = "BasePage_Terms"."BasePageID"', $className)
                    )->innerJoin(
                    'TaxonomyTerm', sprintf('"BasePage_Terms"."TaxonomyTermID" = "TaxonomyTerm"."ID" AND "TaxonomyTerm"."ID" = \'%d\'', $tagID)
            );
        }

        // Filter by date
        if (isset($dateFrom)) {

            if (!isset($dateTo)) {
                $dateTo = $dateFrom;
            }

            $items = $items->where(array(
                sprintf('"%s"."Date" >= \'%s\'', $dbTableName, Convert::raw2sql("$dateFrom 00:00:00")),
                sprintf('"%s"."Date" <= \'%s\'', $dbTableName, Convert::raw2sql("$dateTo 23:59:59"))
            ));
        }

        // Filter down to single month.
        if (isset($year) && isset($monthNumber)) {
            $year = (int) $year;
            $monthNumber = (int) $monthNumber;

            $beginDate = sprintf("%04d-%02d-01 00:00:00", $year, $monthNumber);
            $endDate = date('Y-m-d H:i:s', strtotime("{$beginDate} +1 month"));

            $items = $items->where(array(
                sprintf('"%s"."Date" >= \'%s\'', $dbTableName, Convert::raw2sql($beginDate)),
                sprintf('"%s"."Date" < \'%s\'', $dbTableName, Convert::raw2sql($endDate))
            ));
        }

        // Unpaginated DataList.
        return $items;
    }

    /**
     * Produce an ArrayList of available months out of the updates contained in the DataList.
     *
     * Here is an example of the returned structure:
     * ArrayList:
     *   ArrayData:
     *     YearName => 2013
     *     Months => ArrayList:
     *       MonthName => Jan
     *       MonthNumber => 1
     *       MonthLink => (page URL)year=2012&month=1
     *       Active => true
     *   ArrayData:
     *     YearName => 2012
     *     Months => ArrayList:
     *     ...
     *
     * @param $updates DataList DataList to extract months from.
     * @param $link Link used as abase to construct the MonthLink.
     * @param $currentYear Currently selected year, for computing the link active state.
     * @param $currentMonthNumber Currently selected month, for computing the link active state.
     *
     * @returns ArrayList
     */
    public static function ExtractMonths(DataList $updates, $link = null, $currentYear = null, $currentMonthNumber = null)
    {
        // Set the link to current URL in the same way the HTTP::setGetVar does it.
        if (!isset($link)) {
            $link = Director::makeRelative($_SERVER['REQUEST_URI']);
        }

        $dates = $updates->dataQuery()
                ->groupby('YEAR("Date")')
                ->groupby('MONTH("Date")')
                ->sort('Date', 'DESC')
                ->query()
                ->setSelect(array(
                    'Year' => 'YEAR("Date")',
                    'Month' => 'MONTH("Date")',
                ))
                ->addWhere('"Date" IS NOT NULL')
                ->execute();

        $years = array();
        foreach ($dates as $date) {
            $monthNumber = $date['Month'];
            $year = $date['Year'];
            $dateObj = new \Datetime(implode('-', array($year, $monthNumber, 1)));
            $monthName = $dateObj->Format('M');

            // Set up the relevant year array, if not yet available.
            if (!isset($years[$year])) {
                $years[$year] = array('YearName' => $year, 'Months' => array());
            }

            // Check if the currently processed month is the one that is selected via GET params.
            $active = false;
            if (isset($year) && isset($monthNumber)) {
                $active = (((int) $currentYear) == $year && ((int) $currentMonthNumber) == $monthNumber);
            }

            // Build the link - keep the tag and date filter, but reset the pagination.
            if ($active) {
                // Allow clicking to deselect the month.
                $link = HTTP::setGetVar('month', null, $link, '&');
                $link = HTTP::setGetVar('year', null, $link, '&');
            } else {
                $link = HTTP::setGetVar('month', $monthNumber, $link, '&');
                $link = HTTP::setGetVar('year', $year, $link, '&');
            }
            $link = HTTP::setGetVar('start', null, $link, '&');

            $years[$year]['Months'][$monthNumber] = array(
                'MonthName' => $monthName,
                'MonthNumber' => $monthNumber,
                'MonthLink' => $link,
                'Active' => $active
            );
        }

        // ArrayList will not recursively walk through the supplied array, so manually build nested ArrayLists.
        foreach ($years as &$year) {
            $year['Months'] = ArrayList::create($year['Months']);
        }

        // Reverse the list so the most recent years appear first.
        return ArrayList::create($years);
    }

    /**
     *
     * @return string
     */
    public function getDefaultRSSLink()
    {
        return $this->Link('rss');
    }

    /**
     *
     * @return string
     */
    public function getDefaultAtomLink()
    {
        return $this->Link('atom');
    }

    /**
     *
     * @return string
     */
    public function getSubscriptionTitle()
    {
        return $this->Title;
    }

}
