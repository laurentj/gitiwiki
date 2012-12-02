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

        jClasses::inc('gtwRepo');
        $repo = new gtwRepo($this->param('repository'));
        $repoConfig = $repo->config();
        if (isset($repoConfig['locale']))
            jApp::config()->locale = $repoConfig['locale'];

        $page = $repo->findFile($this->param('page'));
        if ($page === null) {
            $rep = $this->getResponse('html');
            $rep->body->assign('MAIN', '<p>not found</p>');
            $rep->setHttpStatus('404', 'Not Found');
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

            $rep = $this->getResponse('html');

            // let's generate the HTML content
            $basePath = jUrl::get('gitiwiki~wiki:page', array('repository'=>$this->param('repository'), 'page'=>''));
            $html = $page->getHtmlContent($basePath);

            $extraData = $page->getExtraData();
            $books = jClasses::create('gitiwiki~gtwBooks');

            // for book index
            if (isset($extraData['bookContent']) && isset($extraData['bookInfos'])) {
                $books->saveBook($page->getCommitId(), $repo->getName(), $page->getPathFileName(), $extraData);
                $bookPageInfo = null;
                $rep->title =  $extraData['bookInfos']['title'].' - '.$repoConfig['title'];
            }
            else {
                // is the file belongs to a book ? If yes, we will display navigation bars
                $bookPageInfo = $books->isPageBelongsToBook($page->getCommitId(), $repo->getName(), $page->getPathFileName());
                if ($bookPageInfo)
                    $rep->title = $bookPageInfo['title']. ' - '.$repoConfig['title'];
                else
                    $rep->title = $page->getName(). ' - '.$repoConfig['title'];
            }

            $tpl = new jTpl();
            $tpl->assign('repository', $repo->getName());
            $tpl->assign('pageName', $page->getName());
            $tpl->assign('pageContent', $html);
            $tpl->assign('extraData', $page->getExtraData());
            $tpl->assign('bookPageInfo', $bookPageInfo);

            $sourceEditURL = '';
            $sourceViewURL = '';
            if (isset($repoConfig['gitSourceEditURL'])) {
                $sourceEditURL = str_replace(array('%branch%', '%file%'), array($repoConfig['branch'],$page->getPathFileName()), $repoConfig['gitSourceEditURL'] );
            }
            if (isset($repoConfig['gitSourceViewURL'])) {
                $sourceViewURL = str_replace(array('%branch%', '%file%'), array($repoConfig['branch'],$page->getPathFileName()), $repoConfig['gitSourceViewURL'] );
            }

            $tpl->assign('sourceEditURL', $sourceEditURL);
            $tpl->assign('sourceViewURL', $sourceViewURL);

            $rep->body->assign('MAIN', $tpl->fetch('wikipage'));
        }
        else { // directory index
            $basePath = jUrl::get('gitiwiki~wiki:page', array('repository'=>$this->param('repository'), 'page'=>''));
            $rep = $this->getResponse('html');
            $rep->title = $page->getName(). ' - '.$repoConfig['title'];
            $rep->body->assign('MAIN', '<h2>'.htmlspecialchars($page->getName()).'</h2>'.$page->getHtmlContent($basePath));
        }
        return $rep;
    }

}
