<?php

namespace CWP\Cwp\Controller;

use PageController,
    SilverStripe\Forms\FormAction,
    SilverStripe\CMS\Model\SiteTree,
    SilverStripe\Control\HTTPRequest;

class SitemapPageController extends PageController
{
    /**
     *
     * @var array
     * @config
     */
    private static $allowed_actions = array(
        'showpage',
    );
    private static $url_handlers = array(
        'page/$ID' => 'showpage',
    );

    /**
     *
     * @param \CWP\Cwp\Controller\SS_HTTPRequest $link
     * @return SiteTree
     */
    public function Page($link)
    {
        if ($link instanceof HTTPRequest) {
            Deprecation::notice('2.0', 'Using page() as a url handler is deprecated. Use showpage() action instead');
            return $this->showpage($link);
        }

        return parent::Page($link);
    }

    /**
     * @param HTTPRequest
     * @return boolean
     */
    public function showpage(HTTPRequest $request)
    {
        $id = (int) $request->param('ID');

        if (!$id) {
            return false;
        }

        $page = SiteTree::get()->byId($id);

        // does the page exist?
        if (!($page && $page->exists())) {
            return $this->httpError(404);
        }

        // can the page be viewed?
        if (!$page->canView()) {
            return $this->httpError(403);
        }

        $viewer = $this->customise(array(
            'IsAjax' => $request->isAjax(),
            'SelectedPage' => $page,
            'Children' => $page->Children()
        ));

        if ($request->isAjax()) {
            return $viewer->renderWith('SitemapNodeChildren');
        }

        return $viewer;
    }

}
