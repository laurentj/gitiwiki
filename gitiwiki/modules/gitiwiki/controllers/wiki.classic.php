<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/


//
//jClasses::inc('wikiFile');

class wikiCtrl extends jController {
    /**
    *
    */
    function index() {
        $rep = $this->getResponse('html');

        $rep->body->assign('MAIN', '<p> <a href="'.jUrl::get('gitiwiki~wiki:page', array('repository'=>'default', 'page'=>'index.wiki'), jUrl::XMLSTRING).'">a page</a></p>');

        return $rep;
    }


    function page() {
        $rep = $this->getResponse('html');
        jClasses::inc('gtwRepo');
        $repo = new gtwRepo($this->param('repository'));
        $page = $repo->getFile($this->param('page'));
        if ($page === null) {
            $rep->body->assign('MAIN', '<p>not found</p>');
        }
        elseif(isset($page['redirection'])) {
            $rep = $this->getResponse('redirect');
            $rep->action = 'gitiwiki~wiki:page';
            $rep->params = array('repository'=>  $this->param('repository') ,'page'=> $page['redirection']);
        }
        else {
            list($name, $content) = $page;
            $rep->body->assign('MAIN', '<h2>'.htmlspecialchars($name).'</h2><pre>'.htmlspecialchars($content).'</pre>');
        }
        return $rep;
    }

}
