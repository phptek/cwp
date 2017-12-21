<?php

namespace CWP\Cwp\Extension;

use SilverStripe\ORM\DataExtension,
    SilverStripe\Forms\TextField,
    SilverStripe\Forms\FieldList;

/**
 * Adds new global settings.
 */
class CustomSiteConfig extends DataExtension
{

    /**
     *
     * @var array
     * @var config
     */
    private static $db = array(
        'GACode' => 'Varchar(16)',
        'FacebookURL' => 'Varchar(256)', // multitude of ways to link to Facebook accounts, best to leave it open.
        'TwitterUsername' => 'Varchar(16)', // max length of Twitter username 15
    );

    /**
     *
     * @param \CWP\Cwp\Extension\FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.Main', $gaCode = TextField::create(
                    'GACode', _t('CwpConfig.GaField', 'Google Analytics account')
            )
        );

        $gaCode->setRightTitle(
            _t(
                    'CwpConfig.GaFieldDesc', 'Account number to be used all across the site (in the format <strong>UA-XXXXX-X</strong>)'
            )
        );

        $fields->findOrMakeTab('Root.SocialMedia', _t('CustomSiteConfig.SocialMediaTab', 'Social Media'));

        $fields->addFieldToTab(
            'Root.SocialMedia', $facebookURL = TextField::create(
                    'FacebookURL', _t('CwpConfig.FbField', 'Facebook UID or username')
            )
        );
        $facebookURL->setRightTitle(
            _t(
                    'CwpConfig.FbFieldDesc', 'Facebook link (everything after the "http://facebook.com/", eg http://facebook.com/<strong>username</strong> or http://facebook.com/<strong>pages/108510539573</strong>)'
            )
        );

        $fields->addFieldToTab(
            'Root.SocialMedia', $twitterUsername = TextField::create(
                    'TwitterUsername', _t('CwpConfig.TwitterField', 'Twitter username')
            )
        );
        $twitterUsername->setRightTitle(
            _t('CwpConfig.TwitterFieldDesc', 'Twitter username (eg, http://twitter.com/<strong>username</strong>)')
        );
    }

}
