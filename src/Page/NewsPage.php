<?php

namespace CWP\Cwp\Page;

use CWP\Cwp\Page\DatedUpdatePage,
    SilverStripe\Assets\Image,
    SilverStripe\Forms\TextField,
    SilverStripe\AssetAdmin\Forms\UploadField,
    SilverStripe\Forms\FieldList;

class NewsPage extends DatedUpdatePage
{

    /**
     *
     * @var string
     * @config
     */
    private static $description = 'Describes an item of news';
    private static $default_parent = 'NewsHolderPage';

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
    private static $icon = 'cwp/images/icons/sitetree_images/news.png';
    private static $singular_name = 'News Page';
    private static $plural_name = 'News Pages';

    /**
     *
     * @var array
     * @config
     */
    private static $db = array(
        'Author' => 'Varchar(255)'
    );
    private static $has_one = array(
        'FeaturedImage' => Image::class
    );

    /**
     *
     * @var string
     * @config
     */
    private static $table_name = 'NewsPage';

    /**
     *
     * @var string
     */
    public $pageIcon = 'images/icons/sitetree_images/news.png';

    /**
     *
     * @param  boolean $includerelations
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['Author'] = _t('DateUpdatePage.AuthorFieldLabel', 'Author');
        $labels['FeaturedImageID'] = _t('DateUpdatePage.FeaturedImageFieldLabel', 'Featured Image');

        return $labels;
    }

    /**
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->addFieldToTab(
                'Root.Main', TextField::create('Author', $this->fieldLabel('Author')), 'Abstract'
            );

            $fields->addFieldToTab(
                'Root.Main', UploadField::create('FeaturedImage', $this->fieldLabel('FeaturedImageID')), 'Abstract'
            );
        });

        return parent::getCMSFields();
    }

}
