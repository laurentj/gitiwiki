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

    protected $generator = null;

    /**
     * @param gtwRepo $repo
     * @param gitTree $treeGitObject
     * @param string $path the path, without ending slash
     * @param string $name the filename
     */
    function __construct($repo, $treeGitObject, $path, $name ) {
        parent::__construct($repo, $treeGitObject, $path);
        $this->name = $name;

        $pos = strrpos($name, '.');
        if ($pos !== false) {
            $ext = substr($name, $pos+1);
            $conf = $this->repo->config();
            $generatorsList = $conf['generators'];
            if (isset($generatorsList[$ext])) {
                $genParams = explode(',',$generatorsList[$ext]);
                $class = array_shift($genParams);
                $this->generator = jClasses::create($class);
                if ($genParams)
                    $this->generator->init($genParams);
            }
        }

        if (isset($treeGitObject->nodes[$name])) {
            $node = $treeGitObject->nodes[$name];
            $this->fileGitObject = $this->repo->git()->getObject($node->object);
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
        $this->metaFileObject = $this->repo->git()->getObject($metaDirObject->nodes[$this->name.'.ini']->object);

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

    function isStaticContent() {
        return ($this->generator === null);
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

    function getHtmlContent() {
        if ($this->fileGitObject) {
            if ($this->generator) {
                return $this->generator->generate($this->fileGitObject->data);
            } else
                return '<pre>'.htmlspecialchars($this->fileGitObject->data).'</pre>';
        }
        return '';
    }

    function getContent() {
        if ($this->fileGitObject)
            return $this->fileGitObject->data;
        return '';
    }

    function setContent($content) {
        throw new Exception('not implemented');
    }

    function getTitle() {
        throw new Exception('not implemented');
    }

    function setTitle($title) {
        throw new Exception('not implemented');
    }

    function getDescription() {
        throw new Exception('not implemented');
    }

    function setDescription() {
        throw new Exception('not implemented');
    }

    function getMimeType() {
        if ($this->generator) {
            return 'text/html';
        }
        else {
            return jFile::getMimeTypeFromFilename($this->name);
        }
    }

    function setMimeType($title) {
        throw new Exception('not implemented');
    }
}