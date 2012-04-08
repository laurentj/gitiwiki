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



    function page() {
        $rep = $this->getResponse('html');
        jClasses::inc('gtwRepo');
        $repo = new gtwRepo($this->param('repository'));
        $page = $repo->findFile($this->param('page'));
        if ($page === null) {
            $rep->body->assign('MAIN', '<p>not found</p>');
        }
        elseif($page instanceof gtwRedirection) {
            if (!$page->isWikiUrl()) {
                $rep = $this->getResponse('redirectUrl');
                $rep->url = $page->url;
            }
            else {
                $rep = $this->getResponse('redirect');
                $rep->action = 'gitiwiki~wiki:page';
                $rep->params = array('repository'=>  $this->param('repository') ,'page'=> $page->url);
            }
        }
        elseif($page instanceof gtwFile) {
            if ($page->isStaticContent()) {
                $resp = $this->getResponse('binary');
                $resp->fileName = $page->getName();
                $resp->content = $page->getContent();
                $resp->mimeType = $page->getMimeType();
                return $resp;
            }
            $basePath = jUrl::get('gitiwiki~wiki:page', array('repository'=>$this->param('repository'), 'page'=>''));
            $rep->body->assign('MAIN', '<h2>'.htmlspecialchars($page->getName()).'</h2>'.$page->getHtmlContent($basePath));
        }
        else { // directory index
            $basePath = jUrl::get('gitiwiki~wiki:page', array('repository'=>$this->param('repository'), 'page'=>''));
            $rep->body->assign('MAIN', '<h2>'.htmlspecialchars($page->getName()).'</h2>'.$page->getHtmlContent($basePath));
        }
        return $rep;
    }

}
