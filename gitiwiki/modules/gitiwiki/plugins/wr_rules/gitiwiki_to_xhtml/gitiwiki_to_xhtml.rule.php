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
        'dkxhtml_nowiki_inline', 'gtwxhtml_code'),
            'dkxhtml_table_row'=>array( 'dkxhtml_strong','dkxhtml_emphasis','dkxhtml_underlined','dkxhtml_monospaced',
        'dkxhtml_subscript', 'dkxhtml_superscript', 'dkxhtml_del', 'dkxhtml_link', 'dkxhtml_footnote', 'dkxhtml_image',
        'dkxhtml_nowiki_inline', 'gtwxhtml_code',));

    /**
    * liste des balises de type bloc reconnus par WikiRenderer.
    */
    public $bloctags = array('gtwxhtml_title', 'dkxhtml_list', 'dkxhtml_blockquote','dkxhtml_table', 'dkxhtml_pre',
          'dkxhtml_syntaxhighlight', 'dkxhtml_file', 'dkxhtml_nowiki', 'dkxhtml_html', 'dkxhtml_php', 'dkxhtml_para',
          'gtwxhtml_alternatelang', 'gtwxhtml_bookcontents', 'gtwxhtml_bookinfos', 'gtwxhtml_notinbook',
          'gtwxhtml_bookpagelegalnotice', 'gtwxhtml_booklegalnotice'
    );

    /**
     * @var string the path to the wiki content, relative the domain name (ends with a slash)
     */
    public $basePath;

    /**
     * @var string  the path to the current page (without page name), relative to $basePath (ends with a slash)
     */
    public $pagePath;

    public $extractedData = array();
    
    public $protocolAliases = array();

    public function processLink($url, $tagName='') {
        list($url, $label) = parent::processLink($url, $tagName);
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
            $url = substr($url, 1);
        }
        else  if (substr($url, 0,1) == '/') {
            $url = $this->basePath . ltrim($url, '/');
        }
        else {
            $url = $this->basePath . ltrim($this->pagePath, '/') . $url;
        }
        return array($url, $label);
    }

    public $pageTitles = array();
    public $tableOfContent = array();

    public function onStart($texte){
        $this->pageTitles = array();
        $this->extractedData = array();
        $this->tableOfContent = array();
        return parent::onStart($texte);
    }

    public function onParse($finalTexte){
        $finalTexte = parent::onParse($finalTexte);
        if (count($this->tableOfContent)) {
            $this->extractedData['toc'] = $this->tableOfContent;
        }
        return $finalTexte;
    }
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
        $this->engine->getConfig()->extractedData['bookContent'] = $this->currentContents;
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
            $this->_htmlcontent = '<h2>'.jLocale::get('gitiwiki~wikipage.book.summary').'</h2><ul class="bookcontents">';
            return true;
        }
        else {
            return false;
        }
    }

    protected function createLink($url) {
        list($href, $label) = $this->engine->getConfig()->processLink($url);
        return htmlspecialchars($href);
    }
}


class gtwxhtml_bookinfos extends WikiRendererBloc {

    public $type='bookinfo';
    protected $_openTag='<div class="bookinfos">';
    protected $_closeTag='</div>';
    protected $isOpen = false;
    protected $dktag='bookinfo';
    protected $bookInfos;

    public function open(){
        $this->isOpen = true;
        $this->bookInfos = array(
            'title'=>'',
            'subtitle'=>'',
            'title_short'=>'',
            'authors'=>array(),
            'edition'=>'',
            'copyright'=>array('years'=>array(), 'holders'=>array()),
        );
        return $this->_openTag;
    }

    public function close(){
        $this->isOpen=false;
        $this->engine->getConfig()->extractedData['bookInfos'] = $this->bookInfos;
        return $this->_closeTag;
    }

    public function getRenderedLine(){
        return $this->_htmlcontent;
    }

    protected $_htmlcontent = '';

    public function detect($string){
        if ($this->isOpen) {
            $this->_htmlcontent = '';
            if(preg_match('/^\s*<\/'.$this->dktag.'>\s*$/',$string,$m)){
                // end tag
                $this->isOpen = false;
                $this->_htmlcontent = "<h1>".htmlspecialchars($this->bookInfos['title'])."</h1>\n";
                if ($this->bookInfos['subtitle'])
                    $this->_htmlcontent .= "<h2>".htmlspecialchars($this->bookInfos['subtitle'])."</h2>\n";
                $this->_htmlcontent .=  "<div class=\"authors\">".jLocale::get('gitiwiki~wikipage.book.writtenby')." <ul>";
                foreach($this->bookInfos['authors'] as $author) {
                    $this->_htmlcontent .=  '<li>'.$author[0].' '.$author[1];
                    $this->_htmlcontent .=  '</li>';
                }
                $this->_htmlcontent .=  '</ul></div>';

                $this->_htmlcontent .=  "<div class=\"copyright\">Copyright ";
                $this->_htmlcontent .=  implode(', ', $this->bookInfos['copyright']['years'])."<br/>";
                $this->_htmlcontent .=  implode(', ', $this->bookInfos['copyright']['holders'])." </div>\n";
            }
            else if(preg_match("/^\s*(title|subtitle|title_short|author|edition|copyright_years|copyright_holder)\s*=\s*(.*)/", $string, $m)){
                list(,$name,$value)=$m;
                if ($name == 'title') {
                    $this->bookInfos['title'] = $value;
                }elseif($name == 'subtitle') {
                    $this->bookInfos['subtitle'] = $value;
                }elseif($name == 'title_short') {
                    $this->bookInfos['title_short'] = $value;
                }else if($name == 'author') {
                    $this->bookInfos['authors'][] =explode('|', $value);
                }else if($name == 'edition') {
                    $this->bookInfos['edition'] = $value;
                }else if($name == 'copyright_years') {
                    $this->bookInfos['copyright']['years'] = preg_split("/\s*,\s*/", $value);
                }else if($name == 'copyright_holder') {
                    $this->bookInfos['copyright']['holders'][] = $value;
                }
            }
            return true;
        }
        else if(preg_match('/^\s*<'.$this->dktag.'( \w+)?>\s*$/',$string,$m)){
            $this->_closeNow = false;
            $this->_htmlcontent =  "";
            return true;
        }
        else {
            return false;
        }
    }
}


/**
 * ignore <notinbook> tag, only relevant for docbook convertion
 */
class gtwxhtml_notinbook extends WikiRendererBloc {

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
        if($this->isOpen){
            if(preg_match('/(.*)<\/'.$this->dktag.'>\s*$/',$string,$m)){
                $this->_detectMatch=$m[1];
                $this->isOpen=false;
            }else{
                $this->_detectMatch=$string;
            }
            return true;

        }else{
            if(preg_match('/^\s*<'.$this->dktag.'( \w+)?>(.*)/',$string,$m)){
                if(preg_match('/(.*)<\/'.$this->dktag.'>\s*$/',$m[2],$m2)){
                    $this->_closeNow = true;
                    $this->_detectMatch=$m2[1];
                }
                else {
                    $this->_closeNow = false;
                    $this->_detectMatch=$m[2];
                }
                return true;
            }else{
                return false;
            }
        }
    }

    public function getRenderedLine(){
       return $this->_renderInlineTag($this->_detectMatch);
    }
}

class gtwxhtml_bookpagelegalnotice extends gtwxhtml_notinbook {

    public $type='bookpagelegalnotice';
    protected $_openTag='<div class="booklegalnotice bookpagelegalnotice">';
    protected $_closeTag='</div>';
    protected $dktag='bookpagelegalnotice';
    protected $storageName = 'bookPageLegalNotice';
    protected $legalNotice = '';

    public function open(){
        $this->isOpen = true;
        $this->legalNotice = '';
        return $this->_openTag;
    }

    public function close(){
        $this->isOpen=false;
        $this->engine->getConfig()->extractedData[$this->storageName] = $this->legalNotice;
        return $this->_closeTag;
    }

    public function getRenderedLine(){
        $html = $this->_renderInlineTag($this->_detectMatch);
        $this->legalNotice .= $html;
        return ''; // we don't want display on the first page
    }
}

class gtwxhtml_booklegalnotice extends gtwxhtml_bookpagelegalnotice {
    public $type='booklegalnotice';
    protected $_openTag='<div class="booklegalnotice">';
    protected $dktag='booklegalnotice';
    protected $storageName = 'bookLegalNotice';
    public function getRenderedLine(){
        $html = $this->_renderInlineTag($this->_detectMatch);
        $this->legalNotice .= $html;
        return $html;
    }
}



class gtwxhtml_alternatelang extends WikiRendererBloc {

    public $type='alternatelang';
    protected $regexp="/^\s*~~LANG:([^~]*)~~\s*$/";

    protected $_openTag='';
    protected $_closeTag='';
    protected $_closeNow = true;



    public function getRenderedLine(){
        // Syntax is :   LANG@id:page,LANG2@id:page2
        $langs = preg_split('/ *, */',trim($this->_detectMatch[1]));
        $data = array();

        $conf = $this->engine->getConfig();

        foreach ($langs as $langdesc){
            if(preg_match('/^(\w+)@(.+)$/', $langdesc, $m)) {
                list($data[$m[1]], $label) = $conf->processLink($m[2]);
            }
        }
        if(isset($conf->extractedData['relative_page_lang']))
            $conf->extractedData['relative_page_lang'] = array_merge( $conf->extractedData['relative_page_lang'], $data);
        else
            $conf->extractedData['relative_page_lang'] = $data;
        return '';
    }
}


class gtwxhtml_code extends WikiTag {
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
            if($type=='V') {
                $tag='<var>';
                $endtag='</var>';
            }
            else if($type=='K'){
                $tag='<kbd>';
                $endtag='</kbd>';
            }
            else if(isset($this->code_types[$type])) {
                $tag = '<code class="'.$this->code_types[$type].'">';
                $endtag ='</code>';
            }
            else {
                $tag='<code>';
                $code = substr($match,2,-2);
                $endtag ='</code>';
            }
        }
        else {
            $code = $match;
            $tag='<code>';
            $endtag ='</code>';
        }
        return $tag.htmlspecialchars($code).$endtag;
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


class gtwxhtml_title extends WikiRendererBloc {
    public $type='title';
    protected $regexp="/^\s*(\={1,6})([^=]*)(\={1,6})\s*$/";
    protected $_closeNow=true;

    public function getRenderedLine(){
        $level = strlen($this->_detectMatch[1]);

        $conf = $this->engine->getConfig();

        $output='';
        if(count($conf->sectionLevel)) {
            $last = end($conf->sectionLevel);
            if($last < $level) {
                while($last = end($conf->sectionLevel) && $last <= $level) {
                    $output.= '</div>';
                    array_pop($conf->sectionLevel);
                }
            }else if($last > $level) {

            }else{
                array_pop($conf->sectionLevel);
                $output.= '</div>';
            }
        }

        $conf->sectionLevel[] = $level;
        $h = 6 - $level + $conf->startHeaderNumber;
        if($h > 5) $h = 5;
        elseif($h < 1) $h = 1;
        

        $htmlTitle = $this->_renderInlineTag(trim($this->_detectMatch[2]));
        $textTitle = strip_tags($htmlTitle);
        $id = $this->titleToID($textTitle);

        $conf->tableOfContent[] = array(6 - $level, $id, $textTitle);
        $output .= '<h'.$h.' id="'.$id.'">'.$htmlTitle;
        $output .= '<a class="anchor" href="#'.$id.'" title="'.jLocale::get('gitiwiki~wikipage.anchor.title').'"> ¶</a>';
        $output .= '</h'.$h.'><div class="level'.$h.'">';
        return $output;
    }

    function titleToID($title) {
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
        $title = preg_replace("/([^\w])/"," ",$title);
        // then we replace all spaces by a -
        $title = preg_replace("/( +)/","-",trim($title));
        // we convert all character to lower case
        $title = urlencode(strtolower($title));


        $conf = $this->engine->getConfig();
        if (isset($conf->pageTitles[$title]))
            $title .= '-'. (++$conf->pageTitles[$title]);
        else
            $conf->pageTitles[$title] = 0;

        return $title;
    }

}
