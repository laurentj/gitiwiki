<?php

/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/
namespace Gitiwiki\ContentRender;

class WikiRenderer {
    
    protected $config;
    
    function __construct() {
    }

    protected $rules = null;
    
    function init($params, $wikiConfig) {
        if (is_array($params) && count($params)) {
            $this->rules = $params[0];
        }
        $this->config = $wikiConfig;
    }

    protected $extraData = array();

    /**
     * @param string $source the wiki content of the page
     * @param string $basePath the path to the wiki content, relative the domain name (ends with a slash)
     * @param string $pagePath the path to the current page (without page name), relative to $basePath (ends with a slash)
     */
    function generate($source, $basePath, $pagePath) {
        $wr = new \jWiki($this->rules);
        $conf = $wr->getConfig();
        $conf->basePath = $basePath;
        $conf->pagePath = $pagePath;
        $conf->protocolAliases = $this->config['protocol-aliases'];
        $content = $wr->render($source);
        $this->extraData = $conf->extractedData;
        return $content;
    }

    function getExtraData() {
        return $this->extraData;
    }
}