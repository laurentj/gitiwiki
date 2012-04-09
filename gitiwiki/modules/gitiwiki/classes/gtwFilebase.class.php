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
     * the commit id
     * @var string
     */
    protected $commitId;

    /**
     * @param gtwRepo $repo
     * @param gitTree $treeGitObject
     * @param string $path
     */
    function __construct($repo, $commitId, $treeGitObject, $path ) {
        $this->path = $path;
        $this->repo = $repo;
        $this->commitId = $commitId;
        $this->treeGitObject = $treeGitObject;
    }

    function getCommitId() {
        return $this->commitId;
    }

    abstract function exists();

    abstract function isStaticContent();

    /**
     * @param string $basePath the path to the wiki content, relative the domain name
     */
    abstract function getHtmlContent($basePath);

    abstract function getContent();

    abstract function save($message, $authorName, $authorMail);

    abstract function moveTo($newPath, $message, $authorName, $authorMail, $commit = null);

    abstract function remove($message, $authorName, $authorMail);
}