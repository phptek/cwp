<?php

namespace CWP\Cwp\Page;

use CWP\Cwp\Page\DatedUpdatePage,
    SilverStripe\Forms\FieldList,
    SilverStripe\Forms\DateField,
    SilverStripe\Security\Member,
    SilverStripe\Forms\TimeField,
    SilverStripe\Core\Convert,
    SilverStripe\ORM\FieldType\DBDatetime;

class EventPage extends DatedUpdatePage
{

    /**
     *
     * @var string
     * @config
     */
    private static $description = 'Describes an event occurring on a specific date.';

    /**
     *
     * @var string
     * @config
     */
    private static $default_parent = 'EventHolder';

    /**
     *
     * @var boolean
     * @config
     */
    private static $can_be_root = false;

    /**
     *
     * @var string
     * @config
     */
    private static $icon = 'cwp/images/icons/sitetree_images/event_page.png';

    /**
     *
     * @var string
     */
    public $pageIcon = 'images/icons/sitetree_images/event_page.png';

    /**
     *
     * @var string
     * @config
     */
    private static $singular_name = 'Event Page';

    /**
     *
     * @var string
     * @config
     */
    private static $plural_name = 'Event Pages';

    /**
     *
     * @var array
     * @config
     */
    private static $db = array(
        'StartTime' => 'Time',
        'EndTime' => 'Time',
        'Location' => 'Text'
    );

    /**
     *
     * @var string
     * @config
     */
    private static $table_name = 'EventPage';

    /**
     *
     * @param boolean $includerelations
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['StartTime'] = _t('DateUpdatePage.StartTimeFieldLabel', 'Start Time');
        $labels['EndTime'] = _t('DateUpdatePage.EndTimeFieldLabel', 'End Time');
        $labels['Location'] = _t('DateUpdatePage.LocationFieldLabel', 'Location');

        return $labels;
    }

    /**
     * Add the default for the Date being the current day.
     *
     * @return void
     */
    public function populateDefaults()
    {
        if (!isset($this->Date) || $this->Date === null) {
            $this->Date = DBDatetime::now()->Format('Y-m-d');
        }

        if (!isset($this->StartTime) || $this->StartTime === null) {
            $this->StartTime = '09:00:00';
        }

        if (!isset($this->EndTime) || $this->EndTime === null) {
            $this->EndTime = '17:00:00';
        }

        parent::populateDefaults();
    }

    /**
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->removeByName('Date');

            $dateTimeFields = array();

            $dateTimeFields[] = $dateField = DateField::create('Date', 'Date');
            $dateField->setConfig('showcalendar', true);
            $dateField->setConfig('dateformat', Member::currentUser()->getDateFormat());

            $dateTimeFields[] = $startTimeField = TimeField::create('StartTime', '&nbsp;&nbsp;' . $this->fieldLabel('StartTime'));
            $dateTimeFields[] = $endTimeField = TimeField::create('EndTime', $this->fieldLabel('EndTime'));
            // Would like to do this, but the width of the form field doesn't scale based on the time
            // format. OS ticket raised: http://open.silverstripe.org/ticket/8260
            //$startTimeField->setConfig('timeformat', Member::currentUser()->getTimeFormat());
            //$endTimeField->setConfig('timeformat', Member::currentUser()->getTimeFormat());
            $startTimeField->setConfig('timeformat', 'h:ma');
            $endTimeField->setConfig('timeformat', 'h:ma');

            $fields->addfieldToTab('Root.Main', $dateTimeField = new FieldGroup('Date and time', $dateTimeFields), 'Abstract');

            $fields->addfieldToTab('Root.Main', $locationField = TextareaField::create('Location', $this->fieldLabel('Location')), 'Abstract');
            $locationField->setRows(4);
        });

        return parent::getCMSFields();
    }

    /**
     *
     * @return string
     */
    public function NiceLocation()
    {
        return (nl2br(Convert::raw2xml($this->Location), true));
    }

}
