<?php

/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/


class gtwWikiRenderer {
    
    
    
    function __construct() {
    }

    protected $rules = null;
    
    function init($params) {
        if (is_array($params) && count($params)) {
            $this->rules = $params[0];
        }
    }

    protected $extraData = array();

    /**
     * @param string $source the wiki content of the page
     * @param string $basePath the path to the wiki content, relative the domain name (ends with a slash)
     * @param string $pagePath the path to the current page (without page name), relative to $basePath (ends with a slash)
     */
    function generate($source, $basePath, $pagePath) {
        $wr = new jWiki($this->rules);
        $conf = $wr->getConfig();
        $conf->basePath = $basePath;
        $conf->pagePath = $pagePath;
        $content = $wr->render($source);
        $this->extraData = $conf->extractedData;
        return $content;
    }

    function getExtraData() {
        return $this->extraData;
    }
}