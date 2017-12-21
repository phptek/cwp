<?php

namespace CWP\Cwp\Extension;

use SilverStripe\ORM\DataExtension,
    SilverStripe\Forms\FieldList,
    SilverStripe\Core\Convert;

class CwpSiteTreeFileExtension extends DataExtension
{
    /**
     *
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        Requirements::css('cwp/css/fieldDescriptionToggle.css');
        Requirements::javascript('cwp/javascript/fieldDescriptionToggle.js');

        $fields->insertAfter(
            ReadonlyField::create(
                'BackLinkCount', _t('AssetTableField.BACKLINKCOUNT', 'Used on:'), $this->owner->BackLinkTracking()->Count() . ' ' . _t('AssetTableField.PAGES', 'page(s)'))
                ->addExtraClass('cms-description-toggle')
                ->setDescription($this->BackLinkHTMLList()), 'LastEdited'
        );
    }

    /**
     * Generate an HTML list which provides links to where a file is used.
     *
     * @return string
     */
    public function BackLinkHTMLList()
    {
        $html = '<em>' . _t('SiteTreeFileExtension.BACKLINK_LIST_DESCRIPTION', 'This list shows all pages where the file has been added through a WYSIWYG editor.') . '</em>';
        $html .= '<ul>';

        foreach ($this->owner->BackLinkTracking() as $backLink) {
            $listItem = '<li>';

            // Add the page link
            $listItem .= '<a href="' . $backLink->Link() . '" target="_blank">' . Convert::raw2xml($backLink->MenuTitle) . '</a> &ndash; ';

            // Add the CMS link
            $listItem .= '<a href="' . $backLink->CMSEditLink() . '">' . _t('SiteTreeFileExtension.EDIT', 'Edit') . '</a>';

            $html .= $listItem . '</li>';
        }

        return $html .= '</ul>';
    }

}
