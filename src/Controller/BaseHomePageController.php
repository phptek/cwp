<?php

namespace CWP\Cwp\Controller;

use PageController;

class BaseHomePageController extends PageController
{

    /**
     *
     * @return NewsHolder
     */
    public function getNewsPage()
    {
        return NewsHolder::get_one('NewsHolder');
    }

    /**
     * @param int $amount The amount of items to provide.
     * @return DataList
     */
    public function getNewsItems($amount = 2)
    {
        $newsHolder = $this->getNewsPage();
        if ($newsHolder) {
            $controller = new NewsHolder_Controller($newsHolder);
            return $controller->Updates()->limit($amount);
        }
    }

}