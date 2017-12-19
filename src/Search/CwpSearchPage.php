<?php

namespace CWP\Cwp\Search;

use Page,
    SilverStripe\Security\Permission;

/**
 * Dummy page to assist with display of search results
 */
class CwpSearchPage extends Page
{

    /**
     *
     * @var string
     * @config
     */
    private static $hide_ancestor = 'CwpSearchPage';

    /**
     *
     * @param  string $stage
     * @param  mixed Member | null $member
     * @return boolean
     */
    public function canViewStage($stage = 'Live', $member = null)
    {
        if (Permission::checkMember($member, 'VIEW_DRAFT_CONTENT')) {
            return true;
        }

        return parent::canViewStage($stage, $member);
    }

}
