<?php

namespace CWP\Cwp\Page;

use SilverStripe\CMS\Model\SiteTree,
    CWP\Cwp\Page\FooterHolder,
    SilverStripe\ORM\DataObject,
    SilverStripe\Taxonomy\TaxonomyTerm,
    CWP\Cwp\Page\BasePage,
    SilverStripe\Core\Config\Config,
    SilverStripe\Control\Director,
    SilverStripe\Versioned\Versioned,
    SilverStripe\Forms\FieldList,
    SilverStripe\Forms\GridField\GridField,
    SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor,
    SilverStripe\Forms\TreeMultiselectField,
    SilverStripe\ORM\ArrayList,
    SilverStripe\Forms\GridField\GridFieldDataColumns;

/**
 * **BasePage** is the foundation which can be used for constructing your own pages.
 * By default it is hidden from the CMS - we rely on developers creating their own
 * `Page` class in the `mysite/code` which will extend from the **BasePage**.
 */
class BasePage extends SiteTree
{
    /**
     *
     * @config
     * @var string
     */
    private static $icon = 'cwp/images/icons/sitetree_images/page.png';
    // Hide this page type from the CMS. hide_ancestor is slightly misnamed, should really be just "hide"
    private static $hide_ancestor = 'BasePage';

    /**
     *
     * @config
     * @var boolean
     */
    private static $pdf_export = false;

    /*
     *
     * Domain to generate PDF's from, DOES not include protocol
     * i.e. google.com not http://google.com
     *
     * @config
     * @var string
     */
    private static $pdf_base_url = "";

    /**
     *
     * Allow custom overriding of the path to the WKHTMLTOPDF binary, in cases
     * where multiple versions of the binary are available to choose from. This
     * should be the full path to the binary (e.g. /usr/local/bin/wkhtmltopdf)
     * @see BasePage_Controller::generatePDF();
     *
     * @config
     * @var string
     */
    private static $wkhtmltopdf_binary = null;
    private static $generated_pdf_path = 'assets/_generated_pdfs';

    /*
     *
     * @config
     * @var array
     */
    private static $api_access = array(
        'view' => array('Locale', 'URLSegment', 'Title', 'MenuTitle', 'Content', 'MetaDescription', 'ExtraMenu', 'Sort', 'Version', 'ParentID', 'ID'),
        'edit' => array('Locale', 'URLSegment', 'Title', 'MenuTitle', 'Content', 'MetaDescription', 'ExtraMenu', 'Sort', 'Version', 'ParentID', 'ID')
    );

    /*
     *
     * @var string
     */
    public static $related_pages_title = 'Related pages';

    /*
     *
     * @config
     * @var array
     */
    private static $many_many = array(
        'Terms' => TaxonomyTerm::class,
        'RelatedPages' => BasePage::class
    );

    /*
     *
     * @config
     * @var array
     */
    private static $many_many_extraFields = array(
        'RelatedPages' => array(
            'SortOrder' => 'Int'
        )
    );

    /*
     *
     * @config
     * @var string
     */
    private static $plural_name = 'Base Pages';

    /*
     *
     * @var string
     */
    public $pageIcon = 'images/icons/sitetree_images/page.png';

    /**
     * Get the footer holder.
     *
     * @return FooterHolder
     */
    public function getFooter()
    {
        return DataObject::get_one(FooterHolder::class);
    }

    /**
     * Return the full filename of the pdf file, including path & extension.
     *
     * @return string
     */
    public function getPdfFilename()
    {
        $baseName = sprintf('%s-%s', $this->URLSegment, $this->ID);

        $folderPath = Config::inst()->get('BasePage', 'generated_pdf_path');
        if ($folderPath[0] != '/') {
            $folderPath = BASE_PATH . '/' . $folderPath;
        }

        return sprintf('%s/%s.pdf', $folderPath, $baseName);
    }

    /**
     * Build pdf link for template.
     *
     * @return string
     */
    public function PdfLink()
    {
        if (!Config::inst()->get('BasePage', 'pdf_export'))
            return false;

        $path = $this->getPdfFilename();

        if ((Versioned::current_stage() == 'Live') && file_exists($path)) {
            return Director::baseURL() . preg_replace('#^/#', '', Director::makeRelative($path));
        }

        return $this->Link('downloadpdf');
    }

    /**
     *
     * @return ManyManyList
     */
    public function RelatedPages()
    {
        return $this->getManyManyComponents('RelatedPages')->sort('SortOrder');
    }

    /**
     *
     * @return string
     */
    public function RelatedPagesTitle()
    {
        return $this->stat('related_pages_title');
    }

    /**
     * Remove linked pdf when publishing the page,
     * as it would be out of date.
     *
     * @return void
     */
    public function onAfterPublish()
    {
        $filepath = $this->getPdfFilename();

        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    /**
     * Remove linked pdf when unpublishing the page,
     * so it's no longer valid.
     *
     * @return boolean
     */
    public function doUnpublish()
    {
        if (!parent::doUnpublish()) {
            return false;
        }

        $filepath = $this->getPdfFilename();

        if (file_exists($filepath)) {
            unlink($filepath);
        }

        return true;
    }

    /**
     * @todo Remove once CWP moves to 3.3 core (which includes this in SiteTree)
     * @return self
     *
     * @return SiteTree
     */
    public function doRestoreToStage()
    {
        $this->invokeWithExtensions('onBeforeRestoreToStage', $this);
        $result = parent::doRestoreToStage();
        $this->invokeWithExtensions('onAfterRestoreToStage', $this);

        return $result;
    }

    /**
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            // Related Pages
            $components = GridFieldConfig_RelationEditor::create();
            $components->removeComponentsByType('GridFieldAddNewButton');
            $components->removeComponentsByType('GridFieldEditButton');
            $components->removeComponentsByType('GridFieldFilterHeader');

            $dataColumns = $components->getComponentByType(GridFieldDataColumns::class);
            $dataColumns->setDisplayFields(array(
                'Title' => _t('BasePage.ColumnTitle', 'Title'),
                'ClassName' => _t('BasePage.ColumnPageType', 'Page Type')
            ));

            $fields->findOrMakeTab(
                    'Root.RelatedPages', _t('BasePage.RelatedPages', 'Related pages')
            );
            $fields->addFieldToTab(
                    'Root.RelatedPages', GridField::create(
                            'RelatedPages', _t('BasePage.RelatedPages', 'Related pages'), $this->RelatedPages(), $components
                    )
            );

            // Taxonomies - Unless they have their own 'Tags' field (such as in Blog, etc)
            if (!$this->hasMany('Tags') && !$this->manyMany('Tags')) {
                $components = GridFieldConfig_RelationEditor::create();
                $components->removeComponentsByType('GridFieldAddNewButton');
                $components->removeComponentsByType('GridFieldEditButton');

                $autoCompleter = $components->getComponentByType('GridFieldAddExistingAutocompleter');
                $autoCompleter->setResultsFormat('$Name ($TaxonomyName)');

                $dataColumns = $components->getComponentByType('GridFieldDataColumns');
                $dataColumns->setDisplayFields(array(
                    'Name' => _t('BasePage.Term', 'Term'),
                    'TaxonomyName' => _t('BasePage.Taxonomy', 'Taxonomy')
                ));

                $fields->findOrMakeTab('Root.Tags', _t('BasePage.TagsTabTitle', 'Tags'));
                $fields->addFieldToTab(
                        'Root.Tags', TreeMultiselectField::create(
                                'Terms', _t('BasePage.Terms', 'Terms'), 'TaxonomyTerm'
                        )->setDescription(_t('BasePage.TermsDescription', 'Click to search for additional terms'))
                );
            }
        });

        return parent::getCMSFields();
    }

    /**
     * Provides data for translation navigation.
     * Collects all site translations, marks the current one, and redirects
     * to the translated home page if a. there is a translated homepage and b. the
     * translation of the specific page is not available.
     *
     * @return mixed null | boolean
     */
    public function getAvailableTranslations()
    {

        if (!class_exists('Translatable')) {
            return false;
        }

        $translations = new ArrayList();
        $globalTranslations = Translatable::get_existing_content_languages();

        foreach ($globalTranslations as $loc => $langName) {

            // Find out the language name in native language.
            $nativeLangName = i18n::get_language_name($loc, true);
            if (!$nativeLangName) {
                $nativeLangName = i18n::get_language_name(i18n::get_lang_from_locale($loc), true);
            }
            if (!$nativeLangName) {
                // Fall back to the locale name.
                $nativeLangName = $langName;
            }

            // Eliminate the part in brackets (e.g. [mandarin])
            $nativeLangName = preg_replace('/ *[\(\[].*$/', '', $nativeLangName);

            // Find out the link to the translated page.
            $link = null;
            $page = $this->getTranslation($loc);
            if ($page) {
                $link = $page->Link();
            }
            if (!$link) {
                // Fall back to the home page
                $link = Translatable::get_homepage_link_by_locale($loc);
            }
            if (!$link) {
                continue;
            }

            // Assemble the table for the switcher.
            $translations->push(new ArrayData(array(
                'Locale' => i18n::convert_rfc1766($loc),
                'LangName' => $nativeLangName,
                'Link' => $link,
                'Current' => (Translatable::get_current_locale() == $loc)
            )));
        }

        if ($translations->count() > 1) {
            return $translations;
        }

        return null;
    }

    /**
     * Returns the native language name for the selected locale/language, empty string if Translatable is not available
     *
     * @return string
     */
    public function getSelectedLanguage()
    {
        if (!class_exists('Translatable')) {
            return '';
        }

        $language = explode('_', Translatable::get_current_locale());
        $languageCode = array_shift($language);
        $nativeName = i18n::get_language_name($languageCode, true);

        return $nativeName;
    }

}
