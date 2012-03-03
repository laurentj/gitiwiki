<?php
/**
* @package     jelix
* @subpackage  forms
* @author      Laurent Jouanneau
* @contributor Loic Mathaud, Dominique Papin
* @copyright   2006-2007 Laurent Jouanneau, 2007 Dominique Papin
* @copyright   2007 Loic Mathaud
* @link        http://www.jelix.org
* @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*/

/**
 * base class of all builder form classes generated by the jform compiler.
 *
 * a builder form class is a class which help to generate a form for the output
 * (html form for example)
 * @package     jelix
 * @subpackage  forms
 */
abstract class jFormsBuilderBase {
    /**
     * a form object
     * @var jFormsBase
     */
    protected $_form;

    /**
     * the action selector
     * @var string
     */
    protected $_action;

    /**
     * params for the action
     * @var array
     */
    protected $_actionParams = array();

    /**
     * form name
     */
    protected $_name;

    protected $_endt = '/>';
    /**
     * @param jFormsBase $form a form object
     */
    public function __construct($form){
        $this->_form = $form;
    }

    /**
     * @param string $action action selector where form will be submit
     * @param array $actionParams  parameters for the action
     */
    public function setAction( $action, $actionParams){
        $this->_action = $action;
        $this->_actionParams = $actionParams;
        $this->_name = jFormsBuilderBase::generateFormName($this->_form->getSelector());
        if(jApp::coord()->response!= null && jApp::coord()->response->getType() == 'html'){
            $this->_endt = (jApp::coord()->response->isXhtml()?'/>':'>');
        }
    }

    public function getName(){ return  $this->_name; }

    /**
     * called during the meta content processing in templates
     * This method should set things on the response, like adding
     * css styles, javascript links etc.
     * @param jTpl $tpl the template object
     */
    abstract public function outputMetaContent($tpl);

    /**
     * output the header content of the form
     * @param array $params some parameters, depending of the type of builder
     */
    abstract public function outputHeader($params);

    /**
     * output the footer content of the form
     */
    abstract public function outputFooter();

    /**
     * displays all the form. outputMetaContent, outputHeader and outputFooters are also called 
     * @since 1.1
     */
    abstract public function outputAllControls();

    /**
     * displays the content corresponding of the given control
     * @param jFormsControl $ctrl the control to display
     * @param array $attributes  attribute to add on the generated code (html attributes for example)
     */
    abstract public function outputControl($ctrl, $attributes=array());

    /**
     * displays the label corresponding of the given control
     * @param jFormsControl $ctrl the control to display
     */
    abstract public function outputControlLabel($ctrl);

    /**
     * generates a name for the form
     */
    protected static function generateFormName($sel){
        static $forms = array();
        $name = 'jforms_'.str_replace('~','_',$sel);
        if (isset($forms[$sel])) {
            return $name.(++$forms[$sel]);
        } else 
            $forms[$sel] = 0;
        return $name;
    }
}
