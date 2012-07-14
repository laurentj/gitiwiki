<?php
/**
* @package   gitiwiki
* @subpackage gtwdocbook
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license   GNU PUBLIC LICENCE
*/

require_once(WIKIRENDERER_PATH.'rules/dokuwiki_to_docbook.php');

class gitiwiki_to_docbook extends dokuwiki_to_docbook  {

    public $textLineContainers = array(
            'WikiXmlTextLine'=>array( 'dkdbk_strong','dkdbk_emphasis','dkdbk_underlined','dkdbk_monospaced',
        'dkdbk_subscript', 'dkdbk_superscript', 'dkdbk_del', 'dkdbk_link', 'dkdbk_footnote', 'dkdbk_image',
        'gtwdbk_code', 'dkdbk_nowiki_inline', ),
            'dkdbk_table_row'=>array( 'dkdbk_strong','dkdbk_emphasis','dkdbk_underlined','dkdbk_monospaced',
        'dkdbk_subscript', 'dkdbk_superscript', 'dkdbk_del', 'dkdbk_link', 'dkdbk_footnote', 'dkdbk_image',
        'gtwdbk_code', 'dkdbk_nowiki_inline',)
    );
    public $bloctags = array('dkdbk_title', 'dkdbk_list', 'dkdbk_blockquote','dkdbk_table', 'dkdbk_pre',
        'dkdbk_syntaxhighlight', 'dkdbk_file', 'dkdbk_nowiki', 'dkdbk_html', 'dkdbk_php', 'dkdbk_para',
        'dkdbk_macro', 'gtwdbk_notinbook'
    );

    public $docbookGen = null;

    /**
     * @var string the url of the web site (ends with a slash)
     */
    public $siteURL;

    /**
     * @var string  the path to the current page (without page name), relative to $basePath (ends with a slash)
     */
    public $pagePath;

    public function processLink($url, $tagName='') {
        if ($tagName == 'image') {
            return $this->processImageLink($url);
        }

        list($url, $label) = parent::processLink($url, $tagName);
        return $this->docbookGen->getFullLink($url, $label, $this->pagePath);
    }

    protected function processImageLink($url) {
        $filename = $this->docbookGen->getImageFile($url, $this->pagePath);
        if ($filename == '')
            throw new Exception('Image not found: '.$url. ' on page '.$this->pagePath);
        return array($filename, $filename);
    }

    public function getSectionId($title) {
        return $this->docbookGen->getSectionId($title);
    }
}


// ===================================== inline tags

class gtwdbk_code extends WikiTag {
    protected $name='code';
    public $beginTag='@@';
    public $endTag='@@';

    public function getContent(){
        $match = $this->wikiContentArr[0];
        
        $tag='<code>';
        $endtag ='</code>';
        if(strlen($match) > 2 && $match[1] == '@') {
            $code = substr($match,2);
            $tag=$endtag='';
            $type= $match[0];
            if($type=='A') {
                $tag='<tag class="attribute">';
                $endtag='</tag>';
            }
            else if($type=='E'){
                $tag='<tag class="element">';
                $endtag='</tag>';
            }
            else if(isset($this->code_types[$type])) {
                $tag = '<'.$this->code_types[$type].'>';
                $endtag ='</'.$this->code_types[$type].'>';
            }
            else {
                $code = $match;
                $tag='<code>';
                $endtag ='</code>';
            }
        }
        else {
            $code = $match;
            $tag='<code>';
            $endtag ='</code>';
        }
        return $tag.htmlspecialchars($code, ENT_NOQUOTES).$endtag;
    }
    protected $code_types = array(
        'A'=>'attribute', //tag class="attribute"
        'C'=>'classname',
        'T'=>'constant',
        'c'=>'command',
        'E'=>'element', //tag class="element"
        'e'=>'envar',
        'F'=>'filename', // class="devicefile|directory"
        'f'=>'function',
        'I'=>'interfacename',
        'K'=>'keycode',
        'L'=>'literal',
        'M'=>'methodname',
        'P'=>'property',
        'R'=>'returnvalue',
        'V'=>'varname',
    );
    public function isOtherTagAllowed() {
        return false;
    }
}

/**
 * ignore content in <notinbook> tag
 */
class gtwdbk_notinbook extends WikiRendererBloc {

    public $type='notinbook';
    protected $_openTag='';
    protected $_closeTag='';
    protected $isOpen = false;
    protected $dktag='notinbook';

    public function open(){
        $this->isOpen = true;
        return $this->_openTag;
    }

    public function close(){
        $this->isOpen=false;
        return $this->_closeTag;
    }

    public function detect($string){
        $this->_detectMatch = '';
        if($this->isOpen){
            if(preg_match('/(.*)<\/'.$this->dktag.'>\s*$/',$string,$m)){
                $this->isOpen=false;
            }
            return true;

        }else{
            if(preg_match('/^\s*<'.$this->dktag.'( \w+)?>(.*)/',$string,$m)){
                if(preg_match('/(.*)<\/'.$this->dktag.'>\s*$/',$m[2],$m2)){
                    $this->_closeNow = true;
                }
                else {
                    $this->_closeNow = false;
                }
                return true;
            }else{
                return false;
            }
        }
    }

    public function getRenderedLine(){
       return '';
    }
}


