<?php
/**
* @package
* @subpackage 
* @author
* @copyright
* @link
* @licence  http://www.gnu.org/licenses/gpl.html GNU General Public Licence, see LICENCE file
*/

class docbookCtrl extends jControllerCmdLine {

    /**
    * Options to the command line
    *  'method_name' => array('-option_name' => true/false)
    * true means that a value should be provided for the option on the command line
    */
    protected $allowed_options = array(
            'index' => array('-lang'=>true, '-draft'=>false)
    );

    /**
     * Parameters for the command line
     * 'method_name' => array('parameter_name' => true/false)
     * false means that the parameter is optionnal. All parameters which follow an optional parameter
     * is optional
     */
    protected $allowed_parameters = array(
            'index' => array('repo_id'=>true, 'book_id'=>true)
    );
    /**
    *
    */
    function index() {
        $lang = $this->option('-lang');
        if ($lang)
            jApp::config()->locale = $lang;

        $rep = $this->getResponse();
        $rep->addContent("start docbook generation : ".$this->param('book_id')."\n");

        jClasses::inc('gtwDocbookGenerator');
        $gen = new gtwDocbookGenerator($this->param('repo_id'), $this->param('book_id'));
        $book = $gen->getBook();

        $date = new jDateTime();
        $date->now();

        $tpl = new jTpl();
        $tpl->assign('book', $book);
        $tpl->assign('pubdate', $date->toString(jDateTime::LANG_DFORMAT));
        $tpl->assign('legalnotice', $gen->getLegalNotice());

        if($this->option('-draft')) {
            $tpl->assign('edition',jLocale::get('docbook.draft').' - '.date('d').' '.jLocale::get('docbook.month_'.date('m')).' '.date('Y'));
            $tpl->assign('releaseInfo',jLocale::get('docbook.release.info.draft'));
        }else{
            $tpl->assign('edition', $book['edition'].' - '.date('d').' '.jLocale::get('docbook.month_'.date('m')).' '.date('Y'));
            $tpl->assign('releaseInfo',jLocale::get('docbook.release.info.stable'));
        }

        $tpl->assign('content', $gen->generate());

        jFile::write($gen->getBookPath().'docbook.xml', $tpl->fetch('docbook', 'xml'));

        $rep->addContent("docbook built.\n");

        return $rep;
    }

}
