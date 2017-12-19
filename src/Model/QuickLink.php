<?php

namespace CWP\Cwp\Model;

use SilverStripe\ORM\DataObject,
    CWP\Cwp\Page\BaseHomePage,
    SilverStripe\CMS\Model\SiteTree,
    SilverStripe\Forms\TreeDropdownField,
    SilverStripe\Forms\CompositeField,
    SilverStripe\Forms\LiteralField;

class Quicklink extends DataObject
{

    /**
     *
     * @var array
     * @var config
     */
    private static $db = array(
        'Name' => 'Varchar(255)',
        'ExternalLink' => 'Varchar(255)',
        'SortOrder' => 'Int'
    );

    /**
     *
     * @var array
     * @var config
     */
    private static $has_one = array(
        'Parent' => BaseHomePage::class,
        'InternalLink' => SiteTree::class
    );

    /**
     *
     * @var array
     * @var config
     */
    private static $summary_fields = array(
        'Name' => 'Name',
        'InternalLink.Title' => 'Internal Link',
        'ExternalLink' => 'External Link'
    );

    /**
     *
     * @var array
     * @var config
     */
    private static $table_name = 'QuickLink';

    /**
     *
     * @param  boolean $includerelations
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['Name'] = _t('Quicklink.NameLabel', 'Name');
        $labels['ExternalLink'] = _t('Quicklink.ExternalLinkLabel', 'External Link');
        $labels['SortOrder'] = _t('Quicklink.SortOrderLabel', 'Sort Order');
        $labels['ParentID'] = _t('Quicklink.ParentRelationLabel', 'Parent');
        $labels['InternalLinkID'] = _t('Quicklink.InternalLinkLabel', 'Internal Link');

        return $labels;
    }

    /**
     *
     * @return string
     */
    public function getLink()
    {
        if ($this->ExternalLink) {
            $url = parse_url($this->ExternalLink);

            // if no scheme set in the link, default to http
            if (!isset($url['scheme'])) {
                return 'http://' . $this->ExternalLink;
            }

            return $this->ExternalLink;
        } else if ($this->InternalLinkID) {
            return $this->InternalLink()->Link();
        }
    }

    /**
     *
     * @param SilverStripe\Security\Member $member
     * @return boolean
     */
    public function canCreate($member = NULL, $context = [])
    {
        return $this->Parent()->canCreate($member);
    }

    /**
     *
     * @param SilverStripe\Security\Member $member
     * @return boolean
     */
    public function canEdit($member = null)
    {
        return $this->Parent()->canEdit($member);
    }

    /**
     *
     * @param SilverStripe\Security\Member $member
     * @return boolean
     */
    public function canDelete($member = null)
    {
        return $this->Parent()->canDelete($member);
    }

    /**
     *
     * @param SilverStripe\Security\Member $member
     * @return boolean
     */
    public function canView($member = null)
    {
        return $this->Parent()->canView($member);
    }

    /**
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('ParentID');

        $externalLinkField = $fields->fieldByName('Root.Main.ExternalLink');

        $fields->removeByName('ExternalLink');
        $fields->removeByName('InternalLinkID');
        $fields->removeByName('SortOrder');
        $externalLinkField->addExtraClass('noBorder');

        $fields->addFieldToTab('Root.Main', CompositeField::create(
            array(
                TreeDropdownField::create(
                        'InternalLinkID', $this->fieldLabel('InternalLinkID'), 'SiteTree'
                ),
                $externalLinkField,
                $wrap = CompositeField::create(
                        $extraLabel = LiteralField::create(
                                'NoteOverride', _t('Quicklink.Note', '<div class="message good notice">Note:  If you specify an External Link, the Internal Link will be ignored.</div>')
                        )
                )
            )
        ));
        $fields->insertBefore(
                LiteralField::create(
                    'Note', _t(
                            'Quicklink.Note2', '<p>Use this to specify a link to a page either on this site (Internal Link) or another site (External Link).</p>'
                    )
                ), 'Name'
        );

        return $fields;
    }

}
