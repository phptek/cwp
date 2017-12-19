<?php

namespace CWP\Cwp\Extension;

use SilverStripe\ORM\DataExtension,
    CWP\Cwp\Page\BasePage;

class TaxonomyTermExtension extends DataExtension
{

    /**
     *
     * @var boolean
     * @config
     */
    private static $api_access = true;

    /**
     *
     * @var array
     * @config
     */
    private static $belongs_many_many = array(
        'Pages' => BasePage::class
    );

}
