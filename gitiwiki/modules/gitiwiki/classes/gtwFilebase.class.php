<?php
/**
 * Some lines of code are taken from ewiki, made by Patrik Fimml
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau,  2008 Patrik Fimml
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
     * The tree git object on which the blob object of the file is attached
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
     * @param string $commitId the bin hash of the commit of the version of the file
     * @param gitTree $treeGitObject The tree git object on which the blob object of the file is attached
     * @param string $path The directory path
     */
    function __construct($repo, $commitId, $treeGitObject, $path ) {
        $this->path = $path;
        $this->repo = $repo;
        $this->commitId = $commitId;
        $this->treeGitObject = $treeGitObject;
    }

    function getCommitId() {
        return sha1_hex($this->commitId);
    }

    /**
     * @return string the path to the directory (relative to the basepath indicated in the configuration)
     */
    function getPath() {
        return $this->path;
    }

    /**
     * @return string the real path of the directory into the repository (basepath + path)
     */
    function getRealPath() {
        $conf = $this->repo->config();
        return $conf['basepath'].$this->path;
    }

    /**
     * @return string the real path + the file name into the repository (basepath + path + name)
     */
    function getRealPathFileName() {
        $conf = $this->repo->config();
        return $conf['basepath'].ltrim($this->path.'/'.$this->name,'/');
    }
    
    /**
     * @return string the relative path + the file name
     */
    abstract function getPathFileName();

    /**
     * @return string the name of the file
     */
    abstract function getName();

    /**
     * @return boolean true if the file exists in the repository
     */
    abstract function exists();

    /**
     * @return boolean true if the content is not a wiki content for example
     */
    abstract function isStaticContent();

    /**
     * returns the content of the file as HTML content. If the original content
     * is a wiki content, it could be transformed to HTML by an appropriate library
     * @param string $webBasePath the path to the wiki content, relative the domain name
     * @return string the html content
     */
    abstract function getHtmlContent($webBasePath);

    /**
     * @return string the original content of the page
     */
    abstract function getContent();

    abstract function save($message, $authorName, $authorMail);

    abstract function moveTo($newPath, $message, $authorName, $authorMail, $commit = null);

    abstract function remove($message, $authorName, $authorMail);

    const IS_LAST_VERSION = 0;
    const HAS_NEW_VERSION = 1;
    
    function hasNewVersion() {
        $conf = $this->repo->config();
        $repo = $this->repo->git();
        $lastCommitId = file_get_contents($repo->dir.'/refs/heads/'.$conf['branch'], 'a+b');
        return ($this->_hasNewVersion($repo, $lastCommitId) == self::MERGE_NEEDED);
    }

    const CAN_FASTFORWARD = 2;
    const CAN_FASTMERGE = 1;
    const MERGE_NEEDED = 0;

    protected function _hasNewVersion($repo, $lastCommitId) {

        if ($lastCommitId == '')
            return self::CAN_FASTFORWARD;

        $ref = sha1_bin($lastCommitId);
        if ($ref == $this->commitId) {
            // no new commits
            return self::CAN_FASTFORWARD;
        }

        // there was new commits since the retrieve of the current content
        // let's see if commits are about the file
        $tip = $repo->getObject($ref);
        $commit = $repo->getObject($this->commitId);
        try {
            $path = $this->getRealPathFileName();
            // if the hash of the file at the tip is equals to
            // the hash of the file at the commit from wich we readed the commit,
            // then the file didn't changed
            if ($tip->find($path) == $commit->find($path)) {
                return self::CAN_FASTMERGE;
            }
        }
        catch (GitTreeError $e) {

        }
        return self::MERGE_NEEDED;
    }
}