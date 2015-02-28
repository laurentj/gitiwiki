<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Laurent Jouanneau
 */

use \Gitiwiki\Storage as gtw;
 
class gtwDocbookGenerator {

    protected $bookPath;

    /**
     *
     */
    protected $book;
    
    protected $bookIndex;
    
    protected $protocolAliases;
    
    protected $siteURL;

    /**
     * @var \Gitiwiki\Storage\Repository
     */
    protected $repository;

    public function __construct($repoId, $bookId) {
        $books = new gtw\Books();
        $bookinfo = $books->getBookInfo($repoId, $bookId);
        if ($bookinfo === false)
            throw new Exception("Unknown book or repository");

        list($this->book, $this->bookIndex, $this->bookPath) = $bookinfo;

        $conf = jApp::config();
        $this->siteURL = 'http://'.$conf->domainName.$conf->urlengine['basePath'];

        $this->repository = new gtw\Repository($repoId);
        $config = $this->repository->getBranchConfig();
        $this->protocolAliases = $config['protocol-aliases'];
    }

    function getBook() {
        return $this->book;
    }

    function getBookPath() {
        return $this->bookPath;
    }

    function getLegalNotice() {
        $wiki = new jWiki('gitiwiki_to_docbook');
        $conf = $wiki->getConfig();
        $conf->docbookGen = $this;
        $conf->siteURL = $this->siteURL;
        $conf->pagePath = 'index.gtw';
        return $wiki->render($this->book['bookLegalNoticeSrc']);
    }

    public function generate() {

        $content = '';

        try {
            $cover = $this->getImageFile('pdf_cover_illustration.png', '/');
        }
        catch(Exception $e) {
            echo "Cover not found\n";
        }

        foreach($this->bookIndex as $k=>$item) {
            if($k==0 && $item[0] == 'foreword') {
                $item[0]='section';
                $item[3]=array();
            } else if($item[0] == 'foreword') {
                continue;
            }
            $content .= $this->_renderItem($item, '    ');
        }

        return $content;
    }

   protected function _renderItem($item, $indent) {
        list($tag, $urlPage, $title, $subsections) = $item;
        if (!$title)
            $title = $urlPage;

        $id = $this->getSectionId($urlPage);
        $c = $indent.'<'.$tag. ' xml:id="'.$id.'"><title>'. htmlspecialchars($title, ENT_NOQUOTES)."</title>\n";

        $emptyContent = ($tag == 'part'? '':'<para> </para>');
        // here insert content of the item
        $file = $this->repository->findFile($urlPage);

        if ($file == null)
            $c .= $emptyContent;

        elseif ($file instanceof gtwRedirection) {
            $c .= $emptyContent;
        }
        elseif($file instanceof gtwFile) {
            if ($file->isStaticContent()) {
                $c .= $emptyContent;
            }
            else {
                $content = $file->getContent();
                $wiki = new jWiki('gitiwiki_to_docbook');
                $conf = $wiki->getConfig();
                $conf->docbookGen = $this;
                $conf->siteURL = $this->siteURL;
                $conf->pagePath = $file->getPath().'/';
                $conf->pageName = $file->getName();
                $dbk = $wiki->render($content);
                if (trim($dbk) == '') {
                    if ($tag == 'part') {
                        $dbk = '';
                    }
                    else {
                        $dbk = $emptyContent;
                    }
                }
                else {
                    if ($tag == 'part') {
                        $dbk = '<partintro>'.$dbk.'</partintro>';
                    }
                }
                $c .= $dbk;
            }
        }
        else {
            $c .= '<para> </para>';
        }

        // loop over children
        foreach($subsections as $k=>$i) {
            $c .= $this->_renderItem($i, $indent.'    ');
        }
        $c.=$indent.'</'.$tag. '>'."\n";

        return $c;
    }

    public function getFullLink($url, $label, $currentPagePath) {
        if(preg_match("/^([a-zA-Z]+)\:(.*)$/", $url, $m)) {
            $proto = strtolower($m[1]);
            if($proto == 'http' || !isset($this->protocolAliases[$proto])) {
                return array($url, $label);
            }
            $url = sprintf($this->protocolAliases[$proto], $m[2]);
            $label = $m[2];
            if(preg_match("/^([a-zA-Z]+)\:(.*)$/", $url, $m)) {
                return array($url, $label);
            }
        }

        if (substr($url, 0,2) == '//') {
            $url = $this->siteURL.substr($url, 1);
        }
        else {
            if ($url[0] == '/') {
                $url = ltrim($url, '/');
            }
            else  if ($url[0] == '#') {
                 $url = $currentPagePath.$url;
            }

            $hash = '';
            if (strpos($url, '#') !== false) {
                list($url, $hash) = explode("#", $url);
            }

            if (strpos($url, '..') !== false) {
                $path = explode('/', $url);
                $goodpath = array();
                foreach($path as $dir) {
                    if ($dir == '..') {
                        array_pop($goodpath);
                    }
                    else
                        $goodpath[] = $dir;
                }
                $url = implode('/', $goodpath);
            }
            if ($hash)
                $url .= '_'.$hash;
            $url = '#'.$this->getSectionId($url, true);
        }
        return array($url, $label);
    }
    
    public function getImageFile($url, $currentPagePath) {
        if (preg_match("/^([a-zA-Z]+)\:(.*)$/", $url, $m)) {
            $proto = strtolower($m[1]);
            if($proto == 'http' || !isset($this->protocolAliases[$proto])) {
                return $this->downloadImage($url);
            }
            $url = sprintf($this->protocolAliases[$proto], $m[2]);
            $label = $m[2];
            if(preg_match("/^([a-zA-Z]+)\:(.*)$/", $url, $m)) {
                return $this->downloadImage($url);
            }
        }

        if (substr($url, 0,2) == '//') {
            return $this->downloadImage($this->siteURL.substr($url, 1));
        }
        else  if ($url[0] == '/') {
            $url = ltrim($url, '/');
        }
        else {
            $url = ltrim($currentPagePath, '/') . $url;
        }
        return $this->loadImage($url);
    }
    
    protected function loadImage($url, $recurCounter = 0) {
        $image = $this->repository->findFile($url);
        if ($image == null) {
            return '';
        }
        elseif($image instanceof gtw\Redirection) {
            if (!$image->isWikiUrl()) {
                return $this->downloadImage($image->url);
            }
            else {
                if ($recurCounter < 3)
                    return $this->loadImage($image->url, $recurCounter+1);
            }
        }
        elseif($image instanceof gtw\File) {
            if ($image->isStaticContent()) {
                $filename = $this->bookPath.'medias/'.$image->getPathFileName();
                jFile::createDir(dirname($filename));
                file_put_contents($filename, $image->getContent());
                return $filename;
            }
        }
        // directory
        return '';
    }

    protected function downloadImage($url) {

        if(!jHttp::readURL($url,$ssl,$host,$port,$path,$user,$pass))
            return false;
        $url= parse_url($url);

        $http = new jHttp($host, $port);
        if(!$http->get($path)) {
            return false;
        }

        $filename = strtr($path,'?&=#','----');
        if(substr($filename, 0,1) == '/')
            $filename = substr($filename, 1);

        $filename = $this->bookPath.'medias/'.$filename;
        jFile::createDir(dirname($filename));
        file_put_contents($filename, $http->getContent());

        return $filename;
    }


    protected $sectionId = array();

    public function getSectionId($title, $forUrl=false) {
        static $url_escape_from = null;
        static $url_escape_to = null;
        if ($url_escape_from == null) {
            $url_escape_from = explode(' ',"à â ä é è ê ë ï î ô ö ù ü û À Â Ä É È Ê Ë Ï Î Ô Ö Ù Ü Û ç Ç");
            $url_escape_to = explode(' ',"a a a e e e e i i o o u u u A A A E E E E I I O O U U U c c");
        }
        // first, we do transliteration.
        // we don't use iconv because it is system dependant
        // we don't use strtr because it is not utf8 compliant
        $title = str_replace($url_escape_from, $url_escape_to, $title);
        // then we replace all non word characters by a space
        $title = preg_replace("/([^\w])/", " ", $title);
        $title = preg_replace("!(/)!", " ", $title);
        // then we replace all spaces by a -
        $title = preg_replace("/( +)/", "-", trim($title));
        // we convert all character to lower case
        $title = urlencode(strtolower($title));

        if (!$forUrl) {
            if (isset($this->sectionId[$title]))
                $title .= '-'. (++$this->sectionId[$title]);
            else
                $this->sectionId[$title] = 0;
        }

        return $title;
    }
}
