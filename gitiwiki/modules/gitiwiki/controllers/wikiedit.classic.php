<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/


class wikiCtrl extends jController {


    function edit() {
        $rep = $this->getResponse('html');
        $rep->body->assign('MAIN', '<p>Feature not available yet.</p>');
        return $rep;
    }

    function save() {
        $rep = $this->getResponse('html');
        $rep->body->assign('MAIN', '<p>Feature not available yet.</p>');
        return $rep;
    }

    function create() {
        $rep = $this->getResponse('html');
        $rep->body->assign('MAIN', '<p>Feature not available yet.</p>');
        return $rep;
    }

    function delete() {
        $rep = $this->getResponse('html');
        $rep->body->assign('MAIN', '<p>Feature not available yet.</p>');
        return $rep;
    }

}
