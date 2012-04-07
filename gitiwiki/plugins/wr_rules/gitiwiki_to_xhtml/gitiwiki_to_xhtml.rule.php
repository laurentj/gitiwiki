<?php


/**
* @package   gitiwiki
* @subpackage
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/

require_once(LIB_PATH.'wikirenderer/rules/dokuwiki_to_xhtml.php');



class  gitiwiki_to_xhtml extends dokuwiki_to_xhtml  {

    public $defaultTextLineContainer = 'WikiHtmlTextLine';

    public $textLineContainers = array(
            'WikiHtmlTextLine'=>array( 'dkxhtml_strong','dkxhtml_emphasis','dkxhtml_underlined','dkxhtml_monospaced',
        'dkxhtml_subscript', 'dkxhtml_superscript', 'dkxhtml_del', 'dkxhtml_link', 'dkxhtml_footnote', 'dkxhtml_image',
        'dkxhtml_nowiki_inline',),
            'dkxhtml_table_row'=>array( 'dkxhtml_strong','dkxhtml_emphasis','dkxhtml_underlined','dkxhtml_monospaced',
        'dkxhtml_subscript', 'dkxhtml_superscript', 'dkxhtml_del', 'dkxhtml_link', 'dkxhtml_footnote', 'dkxhtml_image',
        'dkxhtml_nowiki_inline',));

   /**
   * liste des balises de type bloc reconnus par WikiRenderer.
   */
   public $bloctags = array('dkxhtml_title', 'dkxhtml_list', 'dkxhtml_blockquote','dkxhtml_table', 'dkxhtml_pre',
         'dkxhtml_syntaxhighlight', 'dkxhtml_file', 'dkxhtml_nowiki', 'dkxhtml_html', 'dkxhtml_php', 'dkxhtml_para',
         'dkxhtml_macro', 'gtwxhtml_bookcontents'
   );

    public $basePath;
    
    public $pagePath;
}





class gtwxhtml_bookcontents extends WikiRendererBloc {

    public $type='bookcontents';
    protected $_openTag='<div class="book-contents">';
    protected $_closeTag='</div>';
    protected $isOpen = false;
    protected $dktag='bookcontents';

    public function open(){
       $this->isOpen = true;
       return $this->_openTag;
    }
 
    public function close(){
       $this->isOpen=false;
       return $this->_closeTag;
    }

    public function getRenderedLine(){
        return $this->_htmlcontent;
    }

    protected $currentLevel = false;
    protected $levelStack = array();
    protected $currentContents = array();
    protected $_htmlcontent = '';
    
    public function detect($string){
        if ($this->isOpen) {
            $this->_htmlcontent = '';
            if(preg_match('/^\s*<\/'.$this->dktag.'>\s*$/',$string,$m)){
                // end tag
                $this->isOpen = false;
                // merge all contents stored in the stack
                for ($i=count($this->levelStack)-1; $i >= 0; $i --) {
                    $this->levelStack[$i][1] = $this->currentContents;
                    if ($i>0) {
                        $j = count($this->levelStack[$i-1][1]) -1;
                        $this->levelStack[$i-1][1][$j][3] = $this->currentContents;
                        $this->currentContents = $this->levelStack[$i-1][1];
                        $this->_htmlcontent .= '</li></ul>';
                    }
                    else {
                        $contents = $this->currentContents;
                    }
                    unset($this->levelStack[$i]);
                }
                $this->_htmlcontent .= '</li></ul>';
            }
            else if(preg_match("/^(\s*)\-\s*(foreword|part|chapter|section)\s*\:\s*\[\[([\w\-\/\.]+)\s*\|(.*)\]\]/", $string, $m)) {
                list(,$level, $type, $pageId, $title) = $m;

                $level = strlen($level);
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
);*/

                if ($this->currentLevel === false) {
                    // first line 
                    $this->currentLevel = $level;
                    $this->levelStack[0] = array($this->currentLevel, $this->currentContents);
                    $this->currentContents[] = array($type, $pageId, $title, array());
                    $this->_htmlcontent = '<li class="'.htmlspecialchars($type).'"><a href="'.$this->createLink($pageId).'">'.htmlspecialchars($title).'</a>';
                }
                else {
                    if ($this->currentLevel == $level) {
                        // same level, we add the content in the current list of item
                        $this->currentContents[] = array($type, $pageId, $title, array());
                        $this->_htmlcontent = '</li><li class="'.htmlspecialchars($type).'"><a href="'.$this->createLink($pageId).'">'.htmlspecialchars($title).'</a>';
                    }
                    else if ($this->currentLevel < $level) {
                        // level increases
                        // we store in the stack the current values
                        $l = count($this->levelStack) -1;
                        $this->levelStack[$l][1] = $this->currentContents;
                        // new list of items
                        $this->currentContents = array( array($type, $pageId, $title, array()));
                        // we start a new level 
                        $this->levelStack[$l+1] = array( $level, $this->currentContents);
                        $this->currentLevel = $level;
                        $this->_htmlcontent = '<ul><li class="'.htmlspecialchars($type).'"><a href="'.$this->createLink($pageId).'">'.htmlspecialchars($title).'</a>';
                        
                    }
                    else {
                        // level decreases
                        // update contents of all contents in stack that have a higher level
                        for ($i=count($this->levelStack)-1; $i >= 0; $i --) {
                            if ($this->levelStack[$i][0] > $level) {
                                $this->levelStack[$i][1] = $this->currentContents;
                                if ( $i > 0) {
                                    $this->currentLevel = $this->levelStack[$i-1][0];
                                    $j = count($this->levelStack[$i-1][1]) -1;
                                    $this->levelStack[$i-1][1][$j][3] = $this->currentContents;
                                    $this->currentContents = $this->levelStack[$i-1][1];
                                    $this->_htmlcontent .= '</li></ul>';
                                }
                                else {
                                    $contents = $this->currentContents;
                                    $this->_htmlcontent .= '</li></ul>';
                                }
                                unset($this->levelStack[$i]);
                                continue;
                            }
                            else if ($this->levelStack[$i][0] == $level) {
                                $this->currentContents [] = array($type, $pageId, $title, array());
                                $this->_htmlcontent .= '</li><li class="'.htmlspecialchars($type).'"><a href="'.$this->createLink($pageId).'">'.htmlspecialchars($title).'</a>';
                                break;
                            }
                            else {
                                $this->levelStack[$i+1] = array( $level, array());
                                $this->currentContents = array(array($type, $pageId, $title, array()));
                                $this->currentLevel = $level;
                                break;
                            }
                        }
                    }
                }
            }
            return true;
        }
        else if(preg_match('/^\s*<'.$this->dktag.'( \w+)?>\s*$/',$string,$m)){
            $this->_closeNow = false;
            $this->_htmlcontent = '<ul class="bookcontents">';
            return true;
        }
        else {
            return false;
        }
    }

    protected function createLink($url) {
        if(preg_match("/^[a-zA-Z]+\:\/\//", $url)) {
            return htmlspecialchars($url);
        }
        else if (substr($url, 0,2) == '//') {
            return htmlspecialchars( substr($url, 1));
        }
        else  if (substr($url, 0,1) == '/') {
            $url = $this->engine->getConfig()->basePath . ltrim($url, '/');
        }
        else {
            $c = $this->engine->getConfig();
            $url = $c->basePath . ltrim($c->pagePath, '/') . $url;
        }
        return htmlspecialchars($url);
    }
}
