<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/

class wikiCtrl extends jControllerCmdLine {

    /**
    * Options to the command line
    *  'method_name' => array('-option_name' => true/false)
    * true means that a value should be provided for the option on the command line
    */
    protected $allowed_options = array(
            'generateBook' => array()
    );

    /**
     * Parameters for the command line
     * 'method_name' => array('parameter_name' => true/false)
     * false means that the parameter is optionnal. All parameters which follow an optional parameter
     * is optional
     */
    protected $allowed_parameters = array(
            'generateBook' => array('repository'=>true, 'bookindex'=>true)
    );
    /**
    *
    */
    function generateBook() {
        $rep = $this->getResponse();

        jClasses::inc('gtwRepo');
        $repo = new gtwRepo($this->param('repository'));
        $page = $repo->findFile($this->param('bookindex'));
        if ($page === null) {
            throw new Exception('Book index is not found');
        }        
        elseif($page instanceof gtwFile) {
            if ($page->isStaticContent()) {
                throw new Exception('The given path is not a book index');
            }

            $basePath = jUrl::get('gitiwiki~wiki:page@classic', array('repository'=>$this->param('repository'), 'page'=>''));
            // FIXME: do rules for wikirenderer that just extract book info contents.
            $html = $page->getHtmlContent($basePath);

            $extraData = $page->getExtraData();

            // for book index
            if (isset($extraData['bookContent']) && isset($extraData['bookInfos'])) {
                $books = jClasses::create('gitiwiki~gtwBooks');
                $books->saveBook($page->getCommitId(), $repo->getName(), $page->getPathFileName(), $extraData, true);
            }
            else {
                throw new Exception('The given path is not a book index');
            }
            $rep->addContent("Book is generated\n");
        }
        else {
            throw new Exception('The given path is not a page');
        }

        return $rep;
    }
}
