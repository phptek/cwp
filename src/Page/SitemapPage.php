<?php

namespace CWP\Cwp\Page;

use Page;

class SitemapPage extends Page
{

    /**
     *
     * @var string
     * @config
     */
    private static $description = 'Lists all pages on the site';
    private static $singular_name = 'Sitemap Page';
    private static $plural_name = 'Sitemap Pages';
    private static $table_name = 'SitemapPage';
    
}
