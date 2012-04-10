<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/

class gtwBooks {

    protected $booksPath = '';

    function __construct() {
        if (isset(jApp::config()->gitiwiki)) {
            $this->booksPath = jApp::config()->gitiwiki['booksPath'];
            $this->booksPath = rtrim(str_replace(array('app:'), array(jApp::appPath()), $this->booksPath), '/');
        }
        else {
            $this->booksPath = jApp::appPath('var/books');
        }
    }

    /**
     * @param string $commitId
     * @param string $repoName
     * @param string $indexPath the path of the book in the repository, including the filename of the index page (without extension)
     * @param array $data all book datas
     */
    function saveBook($commitId, $repoName, $indexPath, $data) {
        if (!file_exists($this->booksPath)) {
            return false;
        }

        if (!isset($data['bookContent']) || !isset($data['bookInfos']))
            return false;

        $bookPath =   $this->booksPath.'/'.$repoName.'/books/'.$indexPath.'/';

        if (file_exists($bookPath))
            return true; // already saved

        $bookPagesPath =   $this->booksPath.'/'.$repoName.'/pages/';

        if (isset($data['bookPageLegalNotice'])) {
           $data['bookInfos']['bookPageLegalNotice']=$data['bookPageLegalNotice'];
        }
        else {
            $data['bookInfos']['bookPageLegalNotice'] = '';
        }
        if (isset($data['bookLegalNotice'])) {
           $data['bookInfos']['bookLegalNotice']=$data['bookLegalNotice'];
        }
        else {
            $data['bookInfos']['bookLegalNotice'] = '';
        }

        $fileContent = '<'."?php\n".'$BOOK='.var_export($data['bookInfos'], true).";\n";
        jFile::createDir($bookPath);
        file_put_contents($bookPath.'book.php', $fileContent);

        $fileContent = '<'."?php\n".'$BOOKINDEX='.var_export($data['bookContent'], true).";\n";
        file_put_contents($bookPath.'index.php', $fileContent);

        $this->pages = array();
        $this->bookId = ltrim($indexPath, '/');
        $this->bookBasePath = dirname($indexPath);
        $this->preparePageContents($data['bookInfos'], $data['bookContent']);
        jFile::createDir($bookPagesPath);
        foreach ($this->pages as $path=>$info) {
            $fileContent = '<'."?php\n".'$PAGE='.var_export($info, true).";\n";
            $pagePath = $bookPagesPath.ltrim($path, '/');
            jFile::createDir($pagePath);
            file_put_contents($pagePath.'/page.php', $fileContent);
            jFile::createDir($pagePath.'/index/');
            file_put_contents($pagePath.'/index/page.php', $fileContent);
        }
        return true;
    }

    protected $pages = array();
    protected $bookid = '';
    protected $bookBasePath = '';

    protected function preparePageContents(&$bookInfos, &$indexContent) {
/*
array(
    array( type, pageId, title,
            array(
                array(type, pageId, title,
                    array(
                        array(type, pageId, title,
                            array(
                            )
                        )
                    )
                )
            )
        ),
);
*/
        $siblingPages = array();
        foreach($indexContent as $k=>$item) {
            list($type, $pageId, $title) = $item;
            if(preg_match("/^[a-zA-Z]+\:\/\//", $pageId)) {
                continue;
            }
            else if (substr($pageId, 0,2) == '//') {
                continue;
            }
            else  if (substr($pageId, 0,1) == '/') {
                $pagePath = $pageId;
            }
            else {
                $pagePath = $this->bookBasePath.$pageId;
            }
            $siblingPages[] = array($pagePath, $title);
        }

        $prevPage = null;
        $hierarchyPath = array();

        foreach($indexContent as $k=>$item) {
            $this->setContentItem($k, $item, $hierarchyPath, $siblingPages, null);
        }
    }

    protected function setContentItem($order, $item, $hierarchyPath, &$siblingPages, $parent=null) {
        $pagePath = ltrim($siblingPages[$order][0], '/');

        $this->pages[$pagePath] = array(
            'path'=>$pagePath,
            'book'=>$this->bookId,
            'type'=>$item[0],
            'title'=>$item[2],
            'children'=>array(),
            'parent'=>null,
            'sisters'=>$siblingPages,
            'next'=>null,
            'prev'=>null,
            'hierarchyPath'=>$hierarchyPath,
        );

        if ($order>0) {
            $this->pages[$pagePath]['prev'] = $siblingPages[$order-1];
        }
        if ($order+1<count($siblingPages)) {
            $this->pages[$pagePath]['next'] = $siblingPages[$order+1];
        }
        if ($parent) {
            $this->pages[$pagePath]['parent'] = array($parent['path'], $parent['title']);
        }

        $hierarchyPath[] = array($pagePath, $item[2]);

        $childrenPages = array();
        foreach($item[3] as $k=>$it) {
            list($type, $pageId, $title) = $it;
            if(preg_match("/^[a-zA-Z]+\:\/\//", $pageId)) {
                continue;
            }
            else if (substr($pageId, 0,2) == '//') {
                continue;
            }
            else  if (substr($pageId, 0,1) == '/') {
                $childPagePath = $pageId;
            }
            else {
                $childPagePath = $this->bookBasePath.$pageId;
            }
            $childrenPages[] = array(ltrim($childPagePath,'/'), $title);
        }

        $this->pages[$pagePath]['children'] = $childrenPages;

        foreach($item[3] as $k=>$it) {
            $this->setContentItem($k, $it, $hierarchyPath, $childrenPages, $this->pages[$pagePath]);
        }
    }

    /**
     * @param string $commitId
     * @param string $repoName
     * @param string $pagePath  the path of the page inside the given repo, including filename (with or without extension)
     */
    function isPageBelongsToBook($commitId, $repoName, $pagePath) {

        if (!file_exists($this->booksPath)) {
            return false;
        }

        $bookBasePath = $this->booksPath.'/'.$repoName.'/'; //.sha1_hex($commitId).'/';

        if (!file_exists($bookBasePath))
            return false;

        $PAGE = false;
        $pagePath = trim($pagePath, '/');
        $bookPagePath = $bookBasePath.'pages/'.$pagePath.'/page.php';
        if (file_exists($bookPagePath)) {
            require_once($bookPagePath);
        }

        $extPos = strrpos($pagePath, '.');
        if ($extPos !== false) {
            $bookPagePath = $bookBasePath.'pages/'.substr($pagePath, 0, $extPos).'/page.php';
            if (file_exists($bookPagePath)) {
                require_once($bookPagePath);
            }
        }
        if ($PAGE) {
            require_once($bookBasePath.'books/'.$PAGE['book'].'/book.php');
            $PAGE['bookInfo'] = $BOOK;
        }
        return $PAGE;
    }
}