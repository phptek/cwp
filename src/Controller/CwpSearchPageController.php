<?php

namespace CWP\Cwp\Controller;

use PageController,
    CWP\Cwp\Search\CwpSearchPage;

class CwpSearchPageController extends PageController
{

    /**
     * Create the dummy search record for this page
     *
     * @return CwpSearchPage
     */
    protected function generateSearchRecord()
    {
        $searchPage = CwpSearchPage::create();
        $searchPage->URLSegment = 'search';
        $searchPage->Title = _t('SearchForm.SearchResults', 'Search Results');
        $searchPage->ID = -1;

        return $searchPage;
    }

    /**
     * @param  CwpSearchPage
     * @return CwpSearchPage
     */
    public function __construct($dataRecord = null)
    {
        if (!$dataRecord) {
            $dataRecord = $this->generateSearchRecord();
        }

        parent::__construct($dataRecord);
    }

}
