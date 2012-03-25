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
    
    function generate($source) {
        $wr = new jWiki($this->rules);
        return $wr->render($source);
    }
}