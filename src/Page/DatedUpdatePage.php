<?php

namespace CWP\Cwp\Page;

use Page,
    SilverStripe\ORM\FieldType\DBDatetime,
    SilverStripe\Forms\DatetimeField,
    SilverStripe\Forms\TextareaField;

// Meant as an abstract base class.
class DatedUpdatePage extends Page
{

    /**
     *
     * @var string
     * @config
     */
    private static $hide_ancestor = 'DatedUpdatePage';
    private static $singular_name = 'Dated Update Page';
    private static $plural_name = 'Dated Update Pages';

    /**
     *
     * @var array
     * @config
     */
    private static $defaults = array(
        'ShowInMenus' => false
    );

    /**
     *
     * @var array
     * @config
     */
    private static $db = array(
        'Abstract' => 'Text',
        'Date' => 'Datetime'
    );

    /**
     *
     * @var string
     * @config
     */
    private static $table_name = 'DateUpdatePage';

    /**
     * Add the default for the Date being the current day.
     *
     * @return void
     */
    public function populateDefaults()
    {
        parent::populateDefaults();

        if (!isset($this->Date) || $this->Date === null) {
            $this->Date = DBDatetime::now()->Format('Y-m-d 09:00:00');
        }
    }

    /**
     *
     * @param  boolean $includerelations
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['Date'] = _t('DateUpdatePage.DateLabel', 'Date');
        $labels['Abstract'] = _t('DateUpdatePage.AbstractTextFieldLabel', 'Abstract');

        return $labels;
    }

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->addFieldToTab(
                    'Root.Main', $dateTimeField = DatetimeField::create('Date', $this->fieldLabel('Date')), 'Content'
            );
            $dateTimeField->getDateField()->setConfig('showcalendar', true);

            $fields->addfieldToTab(
                    'Root.Main', $abstractField = TextareaField::create('Abstract', $this->fieldLabel('Abstract')), 'Content'
            );
            $abstractField->setAttribute('maxlength', '160');
            $abstractField->setRightTitle(
                    _t('DateUpdatePage.AbstractDesc', 'The abstract is used as a summary on the listing pages. It is limited to 160 characters.')
            );
            $abstractField->setRows(6);
        });
        return parent::getCMSFields();
    }

}
