<?php

namespace CWP\Cwp\Extension;

use SilverStripe\ORM\DataExtension,
    SilverStripe\Forms\FieldList,
    CWP\Cwp\Extension\SynonymValidator,
    SilverStripe\Security\Permission,
    SilverStripe\Forms\TextareaField,
    SilverStripe\ORM\ValidationResult,
    \Traversable;

/**
 * Allows siteconfig to configure synonyms for fulltext search
 * Requires silverstripe/fulltextsearch 1.1.1 or above
 */
class SynonymsSiteConfig extends DataExtension
{
    /**
     *
     * @var array
     * @config
     */
    private static $db = array(
        'SearchSynonyms' => 'Text', // fulltextsearch synonyms.txt content
    );

    /**
     *
     * @param  SilverStripe\Forms\FieldList $fields
     * @return mixed void | null
     */
    public function updateCMSFields(FieldList $fields)
    {
        // Don't show this field if you're not an admin
        if (!Permission::check('ADMIN')) {
            return;
        }

        // Search synonyms
        $fields->addFieldToTab(
            'Root.FulltextSearch', TextareaField::create('SearchSynonyms', _t('CwpConfig.SearchSynonyms', 'Search Synonyms'))
                ->setDescription(_t(
                    'CwpConfig.SearchSynonyms_Description', 'Enter as many comma separated synonyms as you wish, where ' .
                    'each line represents a group of synonyms.<br /> ' .
                    'You will need to run <a rel="external" target="_blank" href="dev/tasks/Solr_Configure">Solr_Configure</a> if you make any changes'
                ))
        );
    }

    /**
     * @inheritdoc
     *
     * @param  SilverStripe\ORM\ValidationResult $validationResult
     * @return void
     */
    public function validate(ValidationResult $validationResult)
    {
        $validator = new SynonymValidator(array(
            'SearchSynonyms',
        ));

        $validator->php(array(
            'SearchSynonyms' => $this->owner->SearchSynonyms
        ));

        $errors = $validator->getErrors();

        if (is_array($errors) || $errors instanceof Traversable) {
            foreach ($errors as $error) {
                $validationResult->error($error['message']);
            }
        }
    }

}
