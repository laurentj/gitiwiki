<?php
/**
* @package   sphinxsearch
* @subpackage sphinxsearch
* @author    Brice Tencé
* @copyright 2012 Brice Tencé
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/

class sphinxSourceCtrl extends jControllerCmdLine {

    /**
    * Options to the command line
    *  'method_name' => array('-option_name' => true/false)
    * true means that a value should be provided for the option on the command line
    */
    protected $allowed_options = array(
            'sphinxSearchClear' => array(),
            'sphinxSearchExport' => array()
    );

    /**
     * Parameters for the command line
     * 'method_name' => array('parameter_name' => true/false)
     * false means that the parameter is optionnal. All parameters which follow an optional parameter
     * is optional
     */
    protected $allowed_parameters = array(
            'sphinxSearchClear' => array(),
            'sphinxSearchExport' => array('repository'=>true, 'bookindex'=>true)
    );
     
    function sphinxSearchClear() {
        $sphinxSrv = jClasses::getService('sphinxsearch~sphinx');
        $sphinxSrv->resetSources();
    }


    function sphinxSearchExport() {

        $paramRepo = $this->param('repository');
        $paramBookIndex = $this->param('bookindex');

        $rep = $this->getResponse();

        $sphinxSrv = jClasses::getService('sphinxsearch~sphinx');
        $rep->addContent(
            $sphinxSrv->xmlpipe2source( array(
                'type' => 'gitiwiki',
                'repo' => $paramRepo,
                'bookindex' => $paramBookIndex ) )
            );
        return $rep;
    }
}
