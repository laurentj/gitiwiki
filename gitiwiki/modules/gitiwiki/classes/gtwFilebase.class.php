<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/


abstract class gtwFileBase {

    /**
     * the path of the file into the repository
     */
    protected $path;

    /**
     * the repository
     * @var gtwRepo
     */
    protected $repo;

    /**
     * the hash of the object in the git repo
     */
    protected $hash;

    /**
     * @var GitTree
     */
    protected $treeGitObject;

    /**
     * @param gtwRepo $repo
     * @param gitTree $treeGitObject
     * @param string $path
     */
    function __construct($repo, $treeGitObject, $path ) {
        $this->path = $path;
        $this->repo = $repo;
        $this->treeGitObject = $treeGitObject;
    }

    abstract function exists();

    abstract function isStaticContent();

    abstract function getHtmlContent();

    abstract function getContent();

    abstract function save($message, $authorName, $authorMail);

    abstract function moveTo($newPath, $message, $authorName, $authorMail, $commit = null);

    abstract function remove($message, $authorName, $authorMail);
}