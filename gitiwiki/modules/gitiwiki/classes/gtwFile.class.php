<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/


class gtwFile extends gtwFileBase {

    protected $name;

    /**
     * @var GitTree|GitBlob
     */
    protected $fileGitObject;

    function __construct($repo, $treeGitObject, $path, $name ) {
        parent::__construct($repo, $treeGitObject, $path);
        $this->name = $name;

        if (isset($treeGitObject->nodes[$name])) {
            $node = $treeGitObject->nodes[$name];
            $this->fileGitObject = $repo->getObject($node->object);
        }
    }

    function getName() {
        return $this->name;
    }

    function exists() {
        return ($this->fileGitObject != null);
    }

    /**
     * @var GitTree
     */
    protected $metaDirObject;

    /**
     * @var GitBlob
     */
    protected $metaFileObject;

    protected $metaContent = array();

    function setMetaDirObject($metaDirObject) {
        $this->metaDirObject = $metaDirObject;
        if (!isset($metaDirObject->nodes[$this->name.'.ini']))
            return;
        $this->metaFileObject = $this->repo->getObject($metaDirObject->nodes[$this->name.'.ini']->object);

        if ($this->metaFileObject) {
            $ini = @parse_ini_string($this->metaFileObject->data, true);
            if ($ini)
                $this->metaContent = $ini;
        }
    }

    function getMeta($name) {
        if (isset($this->metaContent[$name]))
            return $this->metaContent[$name];
        return null;
    }

    function isHtmlContent() {
        return false;
    }

    function save($message, $authorName, $authorMail) {
        throw new Exception('not implemented');
    }

    function moveTo($newPath, $message, $authorName, $authorMail, $commit = null) {
        throw new Exception('not implemented');
    }

    function remove($message, $authorName, $authorMail) {
        throw new Exception('not implemented');
    }

    function getContent() {
        if ($this->fileGitObject)
            return $this->fileGitObject->data;
        return '';
    }

    function setContent($content) {
        
    }

    function getTitle() {
        
    }

    function setTitle($title) {
        
    }

    function getDescription() {
        
    }

    function setDescription() {
        
    }

    function getMimeType() {
        
    }

    function setMimeType($title) {
        
    }
}