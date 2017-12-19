<?php

namespace CWP\Cwp\Page;

use Page,
    SilverStripe\Forms\TreeDropdownField,
    SilverStripe\Forms\GridField\GridField,
    SilverStripe\Forms\ToggleCompositeField,
    SilverStripe\Forms\DropdownField,
    SilverStripe\Forms\TextField,
    SilverStripe\Forms\HTMLEditor\HTMLEditorField,
    SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor,
    SilverStripe\Forms\FieldList;

/**
 * **BaseHomePage** is the basic home page.
 * By default it is hidden from the CMS - we rely on developers creating their own
 * `HomePage` class in the `mysite/code` which will extend from the **BaseHomePage**.
 */
class BaseHomePage extends Page
{

    /**
     *
     * @var string
     * @var config
     */
    private static $icon = 'cwp/images/icons/sitetree_images/home.png';

    /**
     *
     * @var string
     * @var config
     */
    private static $hide_ancestor = 'BaseHomePage';

    /**
     *
     * @var string
     * @var config
     */
    private static $singular_name = 'Home Page';

    /**
     *
     * @var string
     * @var config
     */
    private static $plural_name = 'Home Pages';

    /**
     *
     * @var array
     * @var config
     */
    private static $db = array(
        'FeatureOneTitle' => 'Varchar(255)',
        'FeatureOneCategory' => "Enum('bell,comments,film,flag,globe,group,list,phone,rss,time,user','comments')",
        'FeatureOneContent' => 'HTMLText',
        'FeatureOneButtonText' => 'Varchar(255)',
        'FeatureTwoTitle' => 'Varchar(255)',
        'FeatureTwoCategory' => "Enum('bell,comments,film,flag,globe,group,list,phone,rss,time,user','comments')",
        'FeatureTwoContent' => 'HTMLText',
        'FeatureTwoButtonText' => 'Varchar(255)'
    );

    /**
     *
     * @var array
     * @var config
     */
    private static $has_one = array(
        'LearnMorePage' => SiteTree::class,
        'FeatureOneLink' => SiteTree::class,
        'FeatureTwoLink' => SiteTree::class
    );

    /**
     *
     * @var array
     * @var config
     */
    private static $has_many = array(
        'Quicklinks' => 'Quicklink.Parent'
    );

    /**
     *
     * @var string
     * @config
     */
    private static $table_name = 'BaseHomePage';

    /**
     *
     * @var string
     * @var config
     */
    public $pageIcon = 'images/icons/sitetree_images/home.png';

    /**
     *
     * @return HasManyList
     */
    public function Quicklinks()
    {
        return $this->getComponents('Quicklinks')->sort('SortOrder');
    }

    /**
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            // Main Content tab
            $fields->addFieldToTab(
                    'Root.Main', TreeDropdownField::create(
                            'LearnMorePageID', _t('BaseHomePage.LearnMoreLink', 'Page to link the "Learn More" button to:'), 'SiteTree'
                    ), 'Metadata'
            );

            $gridField = GridField::create(
                            'Quicklinks', 'Quicklinks', $this->Quicklinks(), GridFieldConfig_RelationEditor::create()
            );
            $gridConfig = $gridField->getConfig();
            $gridConfig->getComponentByType('GridFieldAddNewButton')->setButtonName(
                    _t('BaseHomePage.AddNewButton', 'Add new')
            );
            $gridConfig->removeComponentsByType('GridFieldAddExistingAutocompleter');
            $gridConfig->removeComponentsByType('GridFieldDeleteAction');
            $gridConfig->addComponent(new GridFieldDeleteAction());
            $gridConfig->addComponent(new GridFieldSortableRows('SortOrder'));
            $gridField->setModelClass('Quicklink');

            $fields->addFieldToTab('Root.Quicklinks', $gridField);
            $fields->removeByName('Import');
            $fields->addFieldToTab(
                    'Root.Features', ToggleCompositeField::create('FeatureOne', _t('SiteTree.FeatureOne', 'Feature One'), array(
                        TextField::create('FeatureOneTitle', _t('BaseHomePage.Title', 'Title')),
                        $dropdownField = DropdownField::create(
                                'FeatureOneCategory', _t('BaseHomePage.FeatureCategoryDropdown', 'Category icon'), singleton('BaseHomePage')->dbObject('FeatureOneCategory')->enumValues()
                        ),
                        HTMLEditorField::create(
                                'FeatureOneContent', _t('BaseHomePage.FeatureContentFieldLabel', 'Content')
                        ),
                        TextField::create(
                                'FeatureOneButtonText', _t('BaseHomePage.FeatureButtonText', 'Button text')
                        ),
                        TreeDropdownField::create(
                                'FeatureOneLinkID', _t('BaseHomePage.FeatureLink', 'Page to link to'), 'SiteTree'
                        )->setDescription(_t('BaseHomePage.ButtonTextRequired', 'Button text must be filled in'))
                            )
                    )->setHeadingLevel(3)
            );
            $dropdownField->setEmptyString('none');

            $fields->addFieldToTab('Root.Features', ToggleCompositeField::create('FeatureTwo', _t('SiteTree.FeatureTwo', 'Feature Two'), array(
                        TextField::create('FeatureTwoTitle', _t('BaseHomePage.Title', 'Title')),
                        $dropdownField = DropdownField::create(
                                'FeatureTwoCategory', _t('BaseHomePage.FeatureCategoryDropdown', 'Category icon'), singleton('BaseHomePage')->dbObject('FeatureTwoCategory')->enumValues()
                        ),
                        HTMLEditorField::create(
                                'FeatureTwoContent', _t('BaseHomePage.FeatureContentFieldLabel', 'Content')
                        ),
                        TextField::create(
                                'FeatureTwoButtonText', _t('BaseHomePage.FeatureButtonText', 'Button text')
                        ),
                        TreeDropdownField::create(
                                'FeatureTwoLinkID', _t('BaseHomePage.FeatureLink', 'Page to link to'), 'SiteTree'
                        )->setDescription(_t('BaseHomePage.ButtonTextRequired', 'Button text must be filled in'))
                            )
                    )->setHeadingLevel(3)
            );
            $dropdownField->setEmptyString('none');
        });

        return parent::getCMSFields();
    }

}

