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
                $resp->doDownload = false;
                return $resp;
            }

            // TODO set page title

            // let's generate the HTML content
            $basePath = jUrl::get('gitiwiki~wiki:page', array('repository'=>$this->param('repository'), 'page'=>''));
            $html = $page->getHtmlContent($basePath);

            $extraData = $page->getExtraData();
            $books = jClasses::create('gitiwiki~gtwBooks');

            // for book index
            if (isset($extraData['bookContent']) && isset($extraData['bookInfos'])) {
                $books->saveBook($page->getCommitId(), $repo->getName(), $page->getPathFileName(), $extraData);
                $bookPageInfo = null;
            }
            else {
                // is the file belongs to a book ? If yes, we will display navigation bars
                $bookPageInfo = $books->isPageBelongsToBook($page->getCommitId(), $repo->getName(), $page->getPathFileName());
            }

            $tpl = new jTpl();
            $tpl->assign('repository', $repo->getName());
            $tpl->assign('pageName', $page->getName());
            $tpl->assign('pageContent', $html);
            $tpl->assign('extraData', $page->getExtraData());
            $tpl->assign('bookPageInfo', $bookPageInfo);

            $conf = $repo->config();
            $sourceEditURL = '';
            $sourceViewURL = '';
            if (isset($conf ['gitSourceEditURL'])) {
                $sourceEditURL = str_replace(array('%branch%', '%file%'), array($conf['branch'],$page->getPathFileName()), $conf ['gitSourceEditURL'] );
            }
            if (isset($conf ['gitSourceViewURL'])) {
                $sourceViewURL = str_replace(array('%branch%', '%file%'), array($conf['branch'],$page->getPathFileName()), $conf ['gitSourceViewURL'] );
            }

            $tpl->assign('sourceEditURL', $sourceEditURL);
            $tpl->assign('sourceViewURL', $sourceViewURL);

            $rep->body->assign('MAIN', $tpl->fetch('wikipage'));
        }
        else { // directory index
            $basePath = jUrl::get('gitiwiki~wiki:page', array('repository'=>$this->param('repository'), 'page'=>''));
            $rep->body->assign('MAIN', '<h2>'.htmlspecialchars($page->getName()).'</h2>'.$page->getHtmlContent($basePath));
        }
        return $rep;
    }

}
