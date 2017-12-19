<?php

namespace CWP\Cwp\Controller;

use SilverStripe\CMS\Controllers\ContentController,
    SilverStripe\Versioned\Versioned,
    SilverStripe\Core\Config,
    SilverStripe\Forms\FieldList,
    SilverStripe\Forms\TextField,
    SilverStripe\Forms\FormAction,
    SilverStripe\CMS\Search\SearchForm,
    CWP\Cwp\Search\CwpSearchEngine,
    CWP\Core\Model\SolrSearchIndex,
    SilverStripe\Control\HTTPRequest,
    SilverStripe\ORM\FieldType\DBDatetime,
    SilverStripe\Core\Environment;

class BasePageController extends ContentController
{
    /**
     *
     * @var array
     * @config
     */
    private static $allowed_actions = [
        'downloadpdf',
        'SearchForm',
        'results'
    ];

    /**
     * How many search results should be shown per-page?
     *
     * @var int
     */
    public static $results_per_page = 10;
    public static $search_index_class = SolrSearchIndex::class;

    /**
     * If spelling suggestions for searches are given, enable
     * suggested searches to be followed immediately
     *
     * @config
     * @var bool
     */
    private static $search_follow_suggestions = true;

    /**
     * Which classes should be queried when searching?
     *
     * @var array
     */
    public static $classes_to_search = [
        [
            'class' => 'Page',
            'includeSubclasses' => true
        ]
    ];

    /**
     * Serve the page rendered as PDF.
     *
     * @return mixed boolean | HTTPRequest
     */
    public function downloadpdf()
    {
        if (!Config::inst()->get('BasePage', 'pdf_export')) {
            return false;
        }

        // We only allow producing live pdf. There is no way to secure the draft files.
        Versioned::reading_stage('Live');

        $path = $this->dataRecord->getPdfFilename();
        if (!file_exists($path)) {
            $this->generatePDF();
        }

        return HTTPRequest::send_file(file_get_contents($path), basename($path), 'application/pdf');
    }

    /*
     * This will return either pdf_base_url from YML, CWP_SECURE_DOMAIN
     * from .env, or blank. In that order of importance.
     *
     * @return string
     */
    public function getPDFBaseURL()
    {
        // If base url YML is defined in YML, use that
        if (Config::inst()->get('BasePage', 'pdf_base_url')) {
            $pdf_base_url = Config::inst()->get('BasePage', 'pdf_base_url') . '/';
            //otherwise, if we are CWP use the secure domain
        } elseif ($domain = Environment::getEnv('CWP_SECURE_DOMAIN')) {
            $pdf_base_url = $domain . '/';
            //or if neither, leave blank
        } else {
            $pdf_base_url = '';
        }

        return $pdf_base_url;
    }

    /*
     * Don't use the proxy if the pdf domain is the CWP secure domain
     * Or if we aren't on a CWP server.
     *
     * @return string
     */
    public function getPDFProxy($pdf_base_url)
    {
        $domain = Environment::getEnv('CWP_SECURE_DOMAIN');
        $outbound = Environment::getEnv('SS_OUTBOUND_PROXY');
        $port = Environment::getEnv('SS_OUTBOUND_PROXY_PORT');

        if (!$domain || $pdf_base_url == $domain . '/') {
            $proxy = '';
        } else {
            $proxy = ' --proxy ' . $outbound . ':' . $port;
        }

        return $proxy;
    }

    /**
     * Render the page as PDF using wkhtmltopdf.
     *
     * @return mixed void | boolean
     */
    public function generatePDF()
    {
        if (!Config::inst()->get('BasePage', 'pdf_export')) {
            return false;
        }

        $binaryPath = Config::inst()->get('BasePage', 'wkhtmltopdf_binary');
        if (!$binaryPath || !is_executable($binaryPath)) {
            if (defined('WKHTMLTOPDF_BINARY') && is_executable(WKHTMLTOPDF_BINARY)) {
                $binaryPath = WKHTMLTOPDF_BINARY;
            }
        }

        if (!$binaryPath) {
            user_error('Neither WKHTMLTOPDF_BINARY nor BasePage.wkhtmltopdf_binary are defined', E_USER_ERROR);
        }

        if (Versioned::get_reading_mode() == 'Stage.Stage') {
            user_error('Generating PDFs on draft is not supported', E_USER_ERROR);
        }

        set_time_limit(60);

        // prepare the paths
        $pdfFile = $this->dataRecord->getPdfFilename();
        $bodyFile = str_replace('.pdf', '_pdf.html', $pdfFile);
        $footerFile = str_replace('.pdf', '_pdffooter.html', $pdfFile);

        // make sure the work directory exists
        if (!file_exists(dirname($pdfFile))) {
            Filesystem::makeFolder(dirname($pdfFile));
        }

        //decide the domain to use in generation
        $pdf_base_url = $this->getPDFBaseURL();

        // Force http protocol on CWP - fetching from localhost without using the proxy, SSL terminates on gateway.
        if (defined('CWP_ENVIRONMENT')) {
            Config::inst()->nest();
            Config::inst()->update('Director', 'alternate_protocol', 'http');

            //only set alternate protocol if CWP_SECURE_DOMAIN is defined OR pdf_base_url is
            if ($pdf_base_url) {
                Config::inst()->update('Director', 'alternate_base_url', 'http://' . $pdf_base_url);
            }
        }

        $bodyViewer = $this->getViewer('pdf');

        // write the output of this page to HTML, ready for conversion to PDF
        file_put_contents($bodyFile, $bodyViewer->process($this));

        // get the viewer for the current template with _pdffooter
        $footerViewer = $this->getViewer('pdffooter');

        // write the output of the footer template to HTML, ready for conversion to PDF
        file_put_contents($footerFile, $footerViewer->process($this));

        $environment = Environment::getEnv('CWP_ENVIRONMENT');
        if ($environment) {
            Config::inst()->unnest();
        }

        //decide what the proxy should look like
        $proxy = $this->getPDFProxy($pdf_base_url);

        // finally, generate the PDF
        $command = $binaryPath . $proxy . ' --outline -B 40pt -L 20pt -R 20pt -T 20pt --encoding utf-8 --orientation Portrait --disable-javascript --quiet --print-media-type ';
        $retVal = 0;
        $output = array();
        exec($command . " --footer-html \"$footerFile\" \"$bodyFile\" \"$pdfFile\" &> /dev/stdout", $output, $return_val);

        // remove temporary file
        unlink($bodyFile);
        unlink($footerFile);

        // output any errors
        if ($return_val != 0) {
            user_error('wkhtmltopdf failed: ' . implode("\n", $output), E_USER_ERROR);
        }

        // serve the generated file
        return HTTPRequest::send_file(file_get_contents($pdfFile), basename($pdfFile), 'application/pdf');
    }

    /**
     * Site search form
     *
     * @return Form
     */
    public function SearchForm()
    {
        $searchText = $this->getRequest()->getVar('Search');

        $fields = FieldList::create(
                TextField::create('Search', false, $searchText)
        );
        $actions = FieldList::create(
                new FormAction('results', _t('SearchForm.GO', 'Go'))
        );

        $form = SearchForm::create($this, 'SearchForm', $fields, $actions);
        $form->setFormAction('search/SearchForm');

        return $form;
    }

    /**
     * Get search form with _header suffix
     *
     * @return SearchForm
     */
    public function HeaderSearchForm()
    {
        return $this->SearchForm()->setTemplate('SearchForm_header');
    }

    /**
     * Process and render search results.
     *
     * @param  array $data The raw request data submitted by user
     * @param  SearchForm $form The form instance that was submitted
     * @param  HTTPRequest $request Request generated for this action
     * @return HTMLText
     */
    public function results($data, $form, $request)
    {
        // Check parameters for terms, pagination, and if we should follow suggestions
        $keywords = empty($data['Search']) ? '' : $data['Search'];
        $start = isset($data['start']) ? $data['start'] : 0;
        $suggestions = isset($data['suggestions']) ? $data['suggestions'] : $this->config()->search_follow_suggestions;

        $results = CwpSearchEngine::create()
                ->search(
                $keywords, $this->getClassesToSearch(), $this->getSearchIndex(), $this->getSearchPageSize(), $start, $suggestions
        );

        // Customise content with these results
        $properties = array(
            'MetaTitle' => _t('CWP_Search.MetaTitle', 'Search {keywords}', array('keywords' => $keywords)),
            'NoSearchResults' => _t('CWP_Search.NoResult', 'Sorry, your search query did not return any results.'),
            'EmptySearch' => _t('CWP_Search.EmptySearch', 'Search field empty, please enter your search query.'),
            'PdfLink' => '',
            'Title' => _t('SearchForm.SearchResults', 'Search Results'),
        );
        $this->extend('updateSearchResults', $results, $properties);

        // Customise page
        $response = $this->customise($properties);
        if ($results) {
            $response = $response
                    ->customise($results)
                    ->customise(array('Results' => $results->getResults()));
        }

        // Render
        $templates = $this->getResultsTemplate($request);

        return $response->renderWith($templates);
    }

    /**
     * Select the template to render search results with
     *
     * @param HTTPRequest $request
     * @return array
     */
    protected function getResultsTemplate($request)
    {
        $templates = array('Page_results', 'Page');
        if ($request->getVar('format') == 'rss') {
            array_unshift($templates, 'Page_results_rss');
        }

        if ($request->getVar('format') == 'atom') {
            array_unshift($templates, 'Page_results_atom');
        }

        return $templates;
    }

    /**
     * Provide scripts as needed by the *default* theme.
     * Override this function if you are using a custom theme based on the *default*.
     *
     * @deprecated 1.6..2.0 Use "starter" theme instead
     */
    public function getBaseScripts()
    {
        $scripts = array();
        $this->extend('updateBaseScripts', $scripts);

        return $scripts;
    }

    /**
     * Provide stylesheets, as needed by the *default* theme assumed by this recipe.
     * Override this function if you are using a custom theme based on the *default*.
     *
     * @deprecated 1.6..2.0 Use "starter" theme instead
     */
    public function getBaseStyles()
    {
        $styles = array();
        $this->extend('updateBaseStyles', $styles);

        return $styles;
    }

    /**
     * Provide current year.
     */
    public function CurrentDatetime()
    {
        return DBDatetime::now();
    }

    /**
     * @todo @upgrade Remove??
     */
    public function getRSSLink()
    {

    }

    /**
     * Get the search index registered for this application
     *
     * @return CwpSearchIndex
     */
    protected function getSearchIndex()
    {
        // Will be a service name in 2.0 and returned via injector
        /** @var CwpSearchIndex $index */
        $index = null;
        if (self::$search_index_class) {
            $index = singleton(self::$search_index_class);
        }

        return $index;
    }

    /**
     * Gets the list of configured classes to search
     *
     * @return array
     */
    protected function getClassesToSearch()
    {
        // Will be private static config in 2.0
        return self::$classes_to_search;
    }

    /**
     * Get page size for search
     *
     * @return int
     */
    protected function getSearchPageSize()
    {
        // Will be private static config in 2.0
        return self::$results_per_page;
    }

}
