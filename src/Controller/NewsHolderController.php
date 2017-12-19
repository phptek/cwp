<?php

namespace CWP\Cwp\Controller;

use CWP\Cwp\Controller\DatedUpdateHolderController,
    CWP\Core\Atom\CwpAtomFeed,
    SilverStripe\Control\RSS\RSSFeed;

class NewsHolderController extends DatedUpdateHolderController
{

    /**
     *
     * @var array
     * @config
     */
    private static $allowed_actions = array(
        'rss',
        'atom'
    );

    /**
     * @return string
     */
    public function rss()
    {
        $rss = new RSSFeed(
            $this->Updates()->sort('Created DESC')->limit(20), $this->Link('rss'), $this->getSubscriptionTitle()
        );
        $rss->setTemplate('NewsHolder_rss');

        return $rss->outputToBrowser();
    }

    /**
     * @return string
     */
    public function atom()
    {
        $atom = new CwpAtomFeed(
            $this->Updates()->sort('Created DESC')->limit(20), $this->Link('atom'), $this->getSubscriptionTitle()
        );
        $atom->setTemplate('NewsHolder_atom');

        return $atom->outputToBrowser();
    }

}
