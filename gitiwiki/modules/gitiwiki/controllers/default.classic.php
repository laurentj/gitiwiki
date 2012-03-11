<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/

class defaultCtrl extends jController {
    /**
    *
    */
    function index() {
        $rep = $this->getResponse('html');
        $rep->body->assign('MAIN', '<h3>Wiki list</h3><ul><li><a href="'.jUrl::get('gitiwiki~wiki:index', array('repository'=>'default'), jUrl::XMLSTRING).'">default wiki</a></<li></ul>');
        return $rep;
    }
}
