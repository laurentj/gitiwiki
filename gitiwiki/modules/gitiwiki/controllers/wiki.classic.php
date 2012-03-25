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
        $page = $repo->findFile($this->param('page'));
        if ($page === null) {
            $rep->body->assign('MAIN', '<p>not found</p>');
        }
        elseif($page instanceof gtwRedirection) {
            $rep = $this->getResponse('redirect');
            $rep->action = 'gitiwiki~wiki:page';
            $rep->params = array('repository'=>  $this->param('repository') ,'page'=> $page->url);
        }
        elseif($page instanceof gtwFile) {
            if ($page->isStaticContent()) {
                $resp = $this->getResponse('binary');
                $resp->fileName = $page->getName();
                $resp->content = $page->getContent();
                $resp->mimeType = $page->getMimeType();
                return $resp;
            }
            $rep->body->assign('MAIN', '<h2>'.htmlspecialchars($page->getName()).'</h2>'.$page->getHtmlContent());
        }
        else { // directory index
            $rep->body->assign('MAIN', '<h2>'.htmlspecialchars($page->getName()).'</h2>'.$page->getHtmlContent());
        }
        return $rep;
    }

}
