<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012-2018 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/

use \Gitiwiki\Storage as gtw;

class wikiCtrl extends jController {

    function page() {
        $repoName = $this->param('repository');
        $pageName = $this->param('page');
        try {
            $repo = new gtw\Repository($repoName);
            if ($repoName != $repo->getNameForUrl()) {
                // redirect to the official url of the repository
                $rep = $this->getResponse( 'redirect' );
                $rep->action = 'gitiwiki~wiki:page';
                $rep->params = array( 'repository'=>$repo->getNameForUrl(), 'page'=>$pageName,);
                return $rep;
            }
        }
        catch(jException $e) {
            $rep = $this->getResponse('html');
            $rep->body->assign('MAIN', '<p>not found</p>');
            $rep->setHttpStatus('404', 'Not Found');
            return $rep;
        }

        $repoConfig = $repo->config();
        if (isset($repoConfig['locale']))
            jApp::config()->locale = $repoConfig['locale'];

        $page = $repo->findFile($pageName);

        if ($page === null) {
            $rep = $this->getResponse('html');
            $rep->body->assign('MAIN', '<p>not found</p>');
            $rep->setHttpStatus('404', 'Not Found');
            return $rep;
        }

        if ($page instanceof gtw\Redirection) {
            if (!$page->isWikiUrl()) {
                $rep = $this->getResponse('redirectUrl');
                $rep->url = $page->url;
            }
            else {
                $rep = $this->getResponse('redirect');
                $rep->action = 'gitiwiki~wiki:page';
                $rep->params = array('repository'=>  $repoName ,'page'=> $page->url);
            }
        }
        elseif($page instanceof gtw\File) {
            if ($page->isStaticContent()) {
                $resp = $this->getResponse('binary');
                $resp->fileName = $page->getName();
                $resp->content = $page->getContent();
                $resp->mimeType = $page->getMimeType();
                $resp->doDownload = false;
                if ($repoConfig['robotsNoIndex']) {
                    $resp->addHttpHeader("X-Robots-Tag", "noindex");
                }
                return $resp;
            }

            $rep = $this->getResponse('html');

            // let's generate the HTML content
            $basePath = jUrl::get('gitiwiki~wiki:page', array('repository'=>$repoName, 'page'=>''));
            $html = $page->getHtmlContent($basePath);

            $extraData = $page->getExtraData();
            $books = new gtw\Books;

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
            $tpl->assign('repository', $repo->getNameForUrl());
            $tpl->assign('pageName', $page->getName());
            $tpl->assign('pageContent', $html);
            $tpl->assign('extraData', $page->getExtraData());
            $tpl->assign('bookPageInfo', $bookPageInfo);

            $sourceEditURL = '';
            $sourceViewURL = '';
            $pathFileName = ltrim($page->getPathFileName(), '/');
            if (isset($repoConfig['gitSourceEditURL'])) {
                $sourceEditURL = str_replace(array('%branch%', '%file%'), array($repoConfig['branch'],$pathFileName), $repoConfig['gitSourceEditURL'] );
            }
            if (isset($repoConfig['gitSourceViewURL'])) {
                $sourceViewURL = str_replace(array('%branch%', '%file%'), array($repoConfig['branch'],$pathFileName), $repoConfig['gitSourceViewURL'] );
            }

            $tpl->assign('sourceEditURL', $sourceEditURL);
            $tpl->assign('sourceViewURL', $sourceViewURL);

            $rep->body->assign('MAIN', $tpl->fetch('wikipage'));
            $rep->body->assign('currentRepoName', $repo->getNameForUrl());
        }
        else { // directory index
            $basePath = jUrl::get('gitiwiki~wiki:page', array('repository'=>$repoName, 'page'=>''));
            $rep = $this->getResponse('html');
            $rep->title = $page->getName(). ' - '.$repoConfig['title'];
            $rep->body->assign('MAIN', '<h2>'.htmlspecialchars($page->getName()).'</h2>'.$page->getHtmlContent($basePath));
        }
        if ($repoConfig['robotsNoIndex']) {
            $rep->addHttpHeader("X-Robots-Tag", "noindex");
        }
        return $rep;
    }

    function history() {
        $rep = $this->getResponse('html');
        $rep->body->assign('MAIN', '<p>Feature not available yet.</p>');
        return $rep;
    }

    function details() {
        $rep = $this->getResponse('html');
        $rep->body->assign('MAIN', '<p>Feature not available yet.</p>');
        return $rep;
        
    }

    function conflicts() {
        $rep = $this->getResponse('html');
        $rep->body->assign('MAIN', '<p>Feature not available yet.</p>');
        return $rep;
    }

}
