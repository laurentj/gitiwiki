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
    public $bloctags = array('dkdbk_title', 'gtwdbk_list', 'dkdbk_blockquote','dkdbk_table', 'gtwdbk_definition', 'dkdbk_pre',
        'dkdbk_syntaxhighlight', 'dkdbk_file', 'dkdbk_nowiki', 'dkdbk_html', 'dkdbk_php', 'dkdbk_para',
        'dkdbk_macro', 'gtwdbk_notinbook'
    );

    public $docbookGen = null;

    /**
     * @var string  the path to the current page (without page name), relative to $basePath (ends with a slash)
     */
    public $pagePath;

    public $pageName;

    public function processLink($url, $tagName='') {
        if ($tagName == 'image') {
            return $this->processImageLink($url);
        }

        list($url, $label) = parent::processLink($url, $tagName);
        return $this->docbookGen->getFullLink($url, $label, $this->pagePath.'/'.$this->pageName);
    }

    protected function processImageLink($url) {
        $filename = $this->docbookGen->getImageFile($url, $this->pagePath);
        if ($filename == '')
            throw new Exception('Image not found: '.$url. ' on page '.$this->pagePath.'/'.$this->pageName);
        return array($filename, $filename);
    }

    public function getSectionId($title) {
        return $this->docbookGen->getSectionId($this->pagePath.'/'.$this->pageName.'_'.$title);
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
    protected $closeTagDetected = false;

    public function open(){
        $this->isOpen = true;
        $this->closeTagDetected = false;
        return $this->_openTag;
    }

    public function close(){
        $this->isOpen=false;
        return $this->_closeTag;
    }

    public function detect($string){
        if ($this->closeTagDetected) {
            return false;
        }
        $this->_detectMatch = '';
        if($this->isOpen){
            if(preg_match('/(.*)<\/'.$this->dktag.'>\s*$/',$string,$m)){
                $this->isOpen=false;
                $this->closeTagDetected = true;
            }
            return true;

        }else{
            if(preg_match('/^\s*<'.$this->dktag.'( \w+)?>(.*)/',$string,$m)){
                if(preg_match('/(.*)<\/'.$this->dktag.'>\s*$/',$m[2],$m2)){
                    $this->_closeNow = true;
                    $this->closeTagDetected = true;
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



class gtwdbk_list extends dkdbk_list {

    protected $_opened = false;

    public function detect($string){
        if ($this->_opened) {
            if (preg_match('/^(\s{2,})([\*\-]|[^\=\|\^>;<=~ ])(.*)/', $string, $this->_detectMatch)) {
                $tag = $this->_detectMatch[2];
                if ( $tag == '*' || $tag == '-') { // ok, this is a new item
                    return true;
                }
                // if we are here, we have a simple line with indentation, so just check the indentation.
                // if it is equals or higher than the previous one, this is a line of the current list item
                // otherwise this is a new paragraph outside the list.
                $t = end($this->_stack);
                $indent = strlen($this->_detectMatch[1]);
                if ($indent >= $t[0]) {
                    return true;
                }
            }
            return false;
        }
        else {
            return preg_match($this->regexp, $string, $this->_detectMatch);
        }
    }

    public function open(){
        $this->_opened = true;
        return parent::open();
    }

    public function close(){
        $this->_opened = false;
        return parent::close();
    }

    public function getRenderedLine(){
        if ($this->_detectMatch[2] != '*' && $this->_detectMatch[2] != '-') {
            return '<para>'.$this->_renderInlineTag(trim($this->_detectMatch[2].$this->_detectMatch[3])).'</para>';
        }
        return parent::getRenderedLine();
    }
}


class gtwdbk_definition extends WikiRendererBloc {

    public $type='dfn';
    protected $regexp="/^(\s*);\s*(.*) : (.*)/i";
    protected $_openTag='<variablelist>';
    protected $_closeTag='</variablelist>';
    protected $_previousIndentation = -1;
    protected $_firstItem = true;

    protected $_opened = false;

    public function detect($string){
        if ($this->_opened) {
            if (preg_match('/^(\s*)([^\=\|\^><=~])(.*)/', $string, $this->_detectMatch)) {
                if ($this->_detectMatch[2] != ';') {
                    if (strlen($this->_detectMatch[1]) > $this->_previousIndentation) {
                        $this->_detectMatch[1] = '+;';
                        $this->_detectMatch[2] .= $this->_detectMatch[3];
                        return true;
                    }
                    return false;
                }
            }
            else
                return false;
        }
        if (preg_match($this->regexp, $string, $this->_detectMatch)) {
            $this->_previousIndentation = strlen($this->_detectMatch[1]);
            return true;
        }
        return false;
    }

    public function open(){
        $this->_opened = true;
        $this->_firstItem = true;
        return parent::open();
    }

    public function close(){
        $this->_opened = false;
        return "</para></listitem></varlistentry>\n".parent::close();
    }

    public function getRenderedLine(){
        if ($this->_detectMatch[1] == '+;') {
            return $this->_renderInlineTag($this->_detectMatch[2]);
        }
        $dt = $this->_renderInlineTag($this->_detectMatch[2]);
        $dd = $this->_renderInlineTag($this->_detectMatch[3]);

        if (!$this->_firstItem) {
            return "</para></listitem></varlistentry>\n<varlistentry><term>$dt</term>\n<listitem><para>$dd\n";
        }
        $this->_firstItem = false;
        return "<varlistentry><term>$dt</term>\n<listitem><para>$dd\n";
    }
}

