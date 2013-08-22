<?php
/**
 * Some lines of code are taken from ewiki, made by Patrik Fimml
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012-2013 laurent Jouanneau,  2008 Patrik Fimml
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/
namespace Gitiwiki\Storage;

abstract class FileAbstract {

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
     * @var \Glip\GitTree
     */
    protected $treeGitObject;

    /**
     * the commit id
     * @var \Glip\SHA
     */
    protected $commitHash;

    /**
     * @param \Gitiwiki\Storage\Repository $repo
     * @param \Glip\SHA $commitHash the hash of the commit of the version of the file
     * @param \Glip\GitTree $treeGitObject The tree git object on which the blob object of the file is attached
     * @param string $path The directory path
     */
    function __construct(Repository $repo, \Glip\SHA $commitHash, \Glip\GitTree $treeGitObject, $path ) {
        $this->path = $path;
        $this->repo = $repo;
        $this->commitHash = $commitHash;
        $this->treeGitObject = $treeGitObject;
    }

    function getCommitId() {
        return $this->commitHash->hex();
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
        $lastCommit = $repo[$conf['branch']]->getTip();
        return ($this->_hasNewVersion($repo, $lastCommit) == self::MERGE_NEEDED);
    }

    /**
     * value returned by _hasNewVersion().
     * indicate that changes can be committed directly
     */
    const CAN_FASTFORWARD = 2;

    /**
     * value returned by _hasNewVersion().
     * indicate that commits occured but not about the file we want to commit
     * we can commit it directly
     */
    const CAN_FASTMERGE = 1;

    /**
     * value returned by _hasNewVersion().
     * indicate that commits occured on the file we want to commit
     */
    const MERGE_NEEDED = 0;

    protected function _hasNewVersion(\Glip\Git $repo, \Glip\GitCommit $lastCommit) {

        $lastCommitHash = $lastCommit->getSha();
        $hash = $lastCommitHash->hex();
        if ($hash == '')
            return self::CAN_FASTFORWARD;

        if ($hash == $this->commitHash->hex()) {
            // no new commits
            return self::CAN_FASTFORWARD;
        }

        // there was new commits since the retrieve of the current content
        // let's see if commits are about the file
        $commit = $repo->getObject($this->commitHash);
        try {
            $path = $this->getRealPathFileName();
            // if the hash of the file at the tip is equals to
            // the hash of the file at the commit from wich we readed the commit,
            // then the file didn't changed
            if ($lastCommit[$path]->getSha()->hex() == $commit[$path]->getSha()->hex()) {
                return self::CAN_FASTMERGE;
            }
        }
        catch (\Glip\GitTreeError $e) {

        }
        return self::MERGE_NEEDED;
    }
}