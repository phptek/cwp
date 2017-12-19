<?php

namespace CWP\Cwp\Controller;

use PageController,
    SilverStripe\Core\Config\Config,
    SilverStripe\Control\RSS\RSSFeed,
    SilverStripe\Control\Session,
    SilverStripe\Taxononmy\TaxonomyTerm,
    SilverStripe\ORM\ArrayList,
    CWP\Cwp\Page\DatedUpdateHolder,
    SilverStripe\ORM\FieldType\DBField,
    SilverStripe\Core\Director,
    SilverStripe\ORM\PaginatedList,
    SilverStripe\Forms\DateField,
    SilverStrip\Forms\FieldList,
    SilverStrip\Forms\Form,
    CWP\Core\Atom\CwpAtomFeed;

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
class DatedUpdateHolderController extends PageController
{
    /**
     *
     * @var array
     * @config
     */
    private static $allowed_actions = array(
        'rss',
        'atom',
        'DateRangeForm'
    );

    /**
     *
     * @var array
     * @config
     */
    private static $casting = array(
        'MetaTitle' => 'Text',
        'FilterDescription' => 'Text'
    );

    /**
     * Get the meta title for the current action
     *
     * @return string
     */
    public function getMetaTitle()
    {
        $title = $this->data()->getTitle();
        $filter = $this->FilterDescription();
        if ($filter) {
            $title = "{$title} - {$filter}";
        }

        $this->extend('updateMetaTitle', $title);

        return $title;
    }

    /**
     * Returns a description of the current filter
     *
     * @return string
     */
    public function FilterDescription()
    {
        $params = $this->parseParams();

        $filters = array();
        if ($params['tag']) {
            $term = TaxonomyTerm::get_by_id('TaxonomyTerm', $params['tag']);
            if ($term) {
                $filters[] = _t('DatedUpdateHolder.FILTER_WITHIN', 'within') . ' "' . $term->Name . '"';
            }
        }

        if ($params['from'] || $params['to']) {
            if ($params['from']) {
                $from = strtotime($params['from']);
                if ($params['to']) {
                    $to = strtotime($params['to']);
                    $filters[] = _t('DatedUpdateHolder.FILTER_BETWEEN', 'between') . ' '
                            . date('j/m/Y', $from) . ' and ' . date('j/m/Y', $to);
                } else {
                    $filters[] = _t('DatedUpdateHolder.FILTER_ON', 'on') . ' ' . date('j/m/Y', $from);
                }
            } else {
                $to = strtotime($params['to']);
                $filters[] = _t('DatedUpdateHolder.FILTER_ON', 'on') . ' ' . date('j/m/Y', $to);
            }
        }

        if ($params['year'] && $params['month']) {
            $timestamp = mktime(1, 1, 1, $params['month'], 1, $params['year']);
            $filters[] = _t('DatedUpdateHolder.FILTER_IN', 'in') . ' ' . date('F', $timestamp) . ' ' . $params['year'];
        }

        if ($filters) {
            return $this->getUpdateName() . ' ' . implode(' ', $filters);
        }
    }

    public function getUpdateName()
    {
        return Config::inst()->get($this->data()->ClassName, 'update_name');
    }

    public function init()
    {
        parent::init();
        RSSFeed::linkToFeed($this->Link() . 'rss', $this->getSubscriptionTitle());
    }

    /**
     * Parse URL parameters.
     *
     * @param $produceErrorMessages Set to false to omit session messages.
     * @return array
     */
    public function parseParams($produceErrorMessages = true)
    {
        $tag = $this->request->getVar('tag');
        $from = $this->request->getVar('from');
        $to = $this->request->getVar('to');
        $year = $this->request->getVar('year');
        $month = $this->request->getVar('month');

        if ($tag == '') {
            $tag = null;
        }
        if ($from == '') {
            $from = null;
        }
        if ($to == '') {
            $to = null;
        }
        if ($year == '') {
            $year = null;
        }
        if ($month == '') {
            $month = null;
        }

        if (isset($tag)) {
            $tag = (int) $tag;
        }

        if (isset($from)) {
            $from = urldecode($from);
            $parser = new SS_Datetime;
            $parser->setValue($from);
            $from = $parser->Format('Y-m-d');
        }
        if (isset($to)) {
            $to = urldecode($to);
            $parser = new SS_Datetime;
            $parser->setValue($to);
            $to = $parser->Format('Y-m-d');
        }
        if (isset($year)) {
            $year = (int) $year;
        }
        if (isset($month)) {
            $month = (int) $month;
        }

        // If only "To" has been provided filter by single date. Normalise by swapping with "From".
        if (isset($to) && !isset($from)) {
            list($to, $from) = array($from, $to);
        }

        // Flip the dates if the order is wrong.
        if (isset($to) && isset($from) && strtotime($from) > strtotime($to)) {
            list($to, $from) = array($from, $to);

            if ($produceErrorMessages) {
                Session::setFormMessage(
                        'Form_DateRangeForm', _t('DateUpdateHolder.FilterAppliedMessage', 'Filter has been applied with the dates reversed.'), 'warning'
                );
            }
        }

        // Notify the user that filtering by single date is taking place.
        if (isset($from) && !isset($to)) {
            if ($produceErrorMessages) {
                Session::setFormMessage(
                        'Form_DateRangeForm', _t('DateUpdateHolder.DateRangeFilterMessage', 'Filtered by a single date.'), 'warning'
                );
            }
        }

        return array(
            'tag' => $tag,
            'from' => $from,
            'to' => $to,
            'year' => $year,
            'month' => $month
        );
    }

    /**
     * Build the link - keep the date range, reset the rest.
     */
    public function AllTagsLink()
    {
        $link = HTTP::setGetVar('tag', null, null, '&');
        $link = HTTP::setGetVar('month', null, $link, '&');
        $link = HTTP::setGetVar('year', null, $link, '&');
        $link = HTTP::setGetVar('start', null, $link, '&');

        return $link;
    }

    /**
     * List tags and attach links.
     *
     * @return ArrayList
     */
    public function UpdateTagsWithLinks()
    {
        $tags = $this->UpdateTags();

        $processed = new ArrayList();

        foreach ($tags as $tag) {
            // Build the link - keep the tag, and date range, but reset month, year and pagination.
            $link = HTTP::setGetVar('tag', $tag->ID, null, '&');
            $link = HTTP::setGetVar('month', null, $link, '&');
            $link = HTTP::setGetVar('year', null, $link, '&');
            $link = HTTP::setGetVar('start', null, $link, '&');

            $tag->Link = $link;
            $processed->push($tag);
        }

        return $processed;
    }

    /**
     * Get the TaxonomyTerm related to the current tag GET parameter.
     */
    public function CurrentTag()
    {
        $tagID = $this->request->getVar('tag');

        if (isset($tagID)) {
            return TaxonomyTerm::get_by_id('TaxonomyTerm', (int) $tagID);
        }
    }

    /**
     * Extract the available months based on the current query.
     * Only tag is respected. Pagination and months are ignored.
     */
    public function AvailableMonths()
    {
        $params = $this->parseParams();

        return DatedUpdateHolder::ExtractMonths(
                        $this->Updates($params['tag'], $params['from'], $params['to']), Director::makeRelative($_SERVER['REQUEST_URI']), $params['year'], $params['month']
        );
    }

    /**
     * Get the updates based on the current query.
     */
    public function FilteredUpdates($pageSize = 20)
    {
        $params = $this->parseParams();

        $items = $this->Updates(
                $params['tag'], $params['from'], $params['to'], $params['year'], $params['month']
        );

        // Apply pagination
        $list = PaginatedList::create($items, $this->request);
        $list->setPageLength($pageSize);
        return $list;
    }

    /**
     * @return Form
     */
    public function DateRangeForm()
    {
        $dateFromTitle = DBField::create_field('HTMLText', sprintf(
                                '%s <span class="field-note">%s</span>', _t('DatedUpdateHolder.FROM_DATE', 'From date'), _t('DatedUpdateHolder.DATE_EXAMPLE', '(example: 2017/12/30)')
        ));
        $dateToTitle = DBField::create_field('HTMLText', sprintf(
                                '%s <span class="field-note">%s</span>', _t('DatedUpdateHolder.TO_DATE', 'To date'), _t('DatedUpdateHolder.DATE_EXAMPLE', '(example: 2017/12/30)')
        ));

        $fields = FieldList::create(
                DateField::create('from', $dateFromTitle)
                        ->setConfig('showcalendar', true), DateField::create('to', $dateToTitle)
                        ->setConfig('showcalendar', true), HiddenField::create('tag')
        );

        $actions = FieldList::create(
                FormAction::create("doDateFilter")
                    ->setTitle("Filter")
                    ->addExtraClass('btn btn-primary primary'),
                FormAction::create("doDateReset")
                    ->setTitle("Clear")
                    ->addExtraClass('btn')
        );

        $form = new Form($this, 'DateRangeForm', $fields, $actions);
        $form->loadDataFrom($this->request->getVars());
        $form->setFormMethod('get');

        // Manually extract the message so we can clear it.
        $form->ErrorMessage = $form->Message();
        $form->ErrorMessageType = $form->MessageType();
        $form->clearMessage();

        return $form;
    }

    public function doDateFilter()
    {
        $params = $this->parseParams();

        // Build the link - keep the tag, but reset month, year and pagination.
        $link = HTTP::setGetVar('from', $params['from'], $this->AbsoluteLink(), '&');
        $link = HTTP::setGetVar('to', $params['to'], $link, '&');
        if (isset($params['tag'])) {
            $link = HTTP::setGetVar('tag', $params['tag'], $link, '&');
        }

        $this->redirect($link);
    }

    public function doDateReset()
    {
        $params = $this->parseParams(false);

        // Reset the link - only include the tag.
        $link = $this->AbsoluteLink();
        if (isset($params['tag'])) {
            $link = HTTP::setGetVar('tag', $params['tag'], $link, '&');
        }

        $this->redirect($link);
    }

    public function rss()
    {
        $rss = new RSSFeed(
                $this->Updates()->sort('Created DESC')->limit(20), $this->Link('rss'), $this->getSubscriptionTitle()
        );

        return $rss->outputToBrowser();
    }

    public function atom()
    {
        $atom = new CwpAtomFeed(
                $this->Updates()->sort('Created DESC')->limit(20), $this->Link('atom'), $this->getSubscriptionTitle()
        );
        return $atom->outputToBrowser();
    }

}
