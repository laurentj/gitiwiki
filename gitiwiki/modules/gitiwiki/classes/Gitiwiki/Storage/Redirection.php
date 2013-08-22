<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012-2013 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/
namespace Gitiwiki\Storage;

class Redirection {

    public $url = '';

    protected $_isWikiUrl = false;

    /**
     * @param string $url  the url to redirect to. It follows gitiwiki url rules
     *    - if it begins with //, it is an url reltaive to the website
     *    - if it begins with or without /, it is an url relative to the wiki content
     *    - if it begins with http://, it is an absolute url
     * @param string $path the current path from which the redirection has been read. it should not have an ending /
     */
    function __construct($url, $path = '') {
        if(preg_match("/^[a-zA-Z]+\:\/\//", $url)) {
            $this->url = $url;
        }
        else if (substr($url, 0,2) == '//') {
            $this->url = substr($url, 1);
        }
        else  if (substr($url, 0,1) == '/') {
            $this->url = ltrim($url, '/');
            $this->_isWikiUrl = true;
        }
        else {
            $path = ltrim($path, '/');
            if ($path)
                $this->url = $path.'/'.ltrim($url,'/');
            else
                $this->url = ltrim($url,'/');
            $this->_isWikiUrl = true;
        }
    }

    function isWikiUrl() {
        return $this->_isWikiUrl;
    }
}