<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brice Tencé
 * @contributor Laurent Jouanneau
 */
use \Gitiwiki\Storage as gtw;

class gtwSphinxSource {

    protected $bookPath;

    /**
     *
     */
    protected $book;

    protected $repo;

    protected $basePath;
    
    protected $bookIndex;

    protected $repository;

    public function __construct($repoId, $bookId) {
        $this->repo = new gtw\Repo($repoId);
        $repoConfig = $this->repo->config();
        if (isset($repoConfig['locale'])) {
            jApp::config()->locale = $repoConfig['locale'];
        }
        $this->basePath = jUrl::get('gitiwiki~wiki:page@classic', array('repository'=>$this->repo->getName(), 'page'=>''));
        $books = gtw\Books();
        $bookinfo = $books->getBookInfo($repoId, $bookId);
        if ($bookinfo === false)
            throw new Exception("Unknown book or repository");

        list($this->book, $this->bookIndex, $this->bookPath) = $bookinfo;
    }

    public function listContent() {

        $content = array();

        foreach($this->bookIndex as $k=>$item) {
            if($k==0 && $item[0] == 'foreword') {
                $item[0]='section';
                $item[3]=array();
            } else if($item[0] == 'foreword') {
                continue;
            }
            $content = array_merge( $content, $this->_getItemContentRecursive($item) );
        }

        return $content;
    }

   protected function _getItemContentRecursive($item) {
        list($tag, $urlPage, $title, $subsections) = $item;
        if (!$title)
            $title = $urlPage;

        // here insert content of the item
        $file = $this->repo->findFile($urlPage);

        $c = array();

        if ($file == null) {
            trigger_error( "File not found for url " . $urlPage , E_USER_WARNING );
            return array();
        } elseif ($file instanceof gtw\Redirection) {
            return array();
        } elseif($file instanceof gtw\File) {
            if ($file->isStaticContent()) {
                return array();
            } else {
                $html = $file->getHtmlContent($this->basePath);
                $path = $file->getPath() . '/' . $file->getName();
                $c[] = array( 'content' => array( 'title' => $title, 'page' => $html ),
                              'infos' => array( 'repo' => $this->repo->getName(), 'path' => $path, 'title' => $title ) );
            }
        } else {
            return array();
        }

        // loop over children
        foreach($subsections as $k=>$i) {
            $c = array_merge( $c, $this->_getItemContentRecursive($i) );
        }

        return $c;
    }
}
