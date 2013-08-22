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

class File extends FileAbstract {

    protected $name;

    /**
     * the original git object for the content
     * @var \Glip\GitBlob
     */
    protected $fileGitObject;

    /**
     * the git object for the new content
     * @var \Glip\GitBlob
     */
    protected $newFileGitObject;

    protected $generator = null;

    /**
     * @param \Gitiwiki\Storage\Repository $repo
     * @param \Glip\SHA $commitHash the hash of the commit of the version of the file
     * @param \Glip\GitTree $treeGitObject
     * @param string $path the path, without ending slash
     * @param string $name the filename (real filename)
     */
    function __construct(Repository $repo, \Glip\SHA $commitHash, \Glip\GitTree $treeGitObject, $path, $name ) {
        parent::__construct($repo, $commitHash, $treeGitObject, $path);
        $this->name = $name;

        $pos = strrpos($name, '.');
        if ($pos !== false) {
            $ext = substr($name, $pos+1);
            $conf = $this->repo->config();
            $generatorsList = $conf['generators'];
            if (isset($generatorsList[$ext])) {
                $genParams = explode(',',$generatorsList[$ext]);
                $class = array_shift($genParams);
                $this->generator = \jClasses::create($class);
                $this->generator->init($genParams, $conf['branches'][$commitHash->hex()]);
            }
        }

        if (isset($treeGitObject[$name])) {
            $this->fileGitObject = $treeGitObject[$name];
        }
    }

    function getName() {
        return $this->name;
    }

    function getPathFileName() {
        return $this->path.'/'.$this->name;
    }

    function exists() {
        return ($this->fileGitObject != null);
    }

    /**
     * @var \Glip\GitTree
     */
    protected $metaDirObject;

    /**
     * @var \Glip\GitBlob
     */
    protected $metaFileObject;

    protected $metaContent = array();

    function setMetaDirObject(\Glip\GitTree $metaDirObject) {
        $this->metaDirObject = $metaDirObject;
        if (!isset($metaDirObject->nodes[$this->name.'.ini']))
            return;
        $this->metaFileObject = $metaDirObject->nodes[$this->name.'.ini'];

        $ini = @parse_ini_string($this->metaFileObject->data, true);
        if ($ini)
            $this->metaContent = $ini;
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
        throw new \Exception('not implemented');
        // FIXME : save also meta data if it has changed
        if (!$this->newFileGitObject
            || $this->fileGitObject->getSha() != $this->newFileGitObject->getSha())
            return false;

        $conf = $this->repo->config();
        $repo = $this->repo->git();
        $commit = $repo->getObject($this->commitHash);

        $branch = $repo[$conf['branch']];
        $branchTip = $branch->getTip();

        $howToMerge = $this->_hasNewVersion($repo, $branchTip);

/*

        $pending = $this->_createCommit($repo, $commit, $message, $authorName, $authorMail);

        $lastcommit = $pending[0];
        $blob = $pending[1];

        if ($howToMerge == self::CAN_FASTMERGE) {
            list($lastcommit, $newtree) = $this->_createMergeCommit($repo, $newcommit, $lastCommitId, $blob);
            $pending[] = $newtree;
            $pending[] = $lastcommit;
        }

        if ($showToMerge == self::MERGE_NEEDED) {
            fclose($f);

            // create conflict branch
            $dir = $repo->getDir().'/refs/heads/gtwconflict';
            if (!file_exists($dir))
                mkdir($dir, 0755);
            if (!is_dir($dir))
                throw new Exception(sprintf('%s is not a directory', $dir));
            if (!is_writable($dir))
                throw new Exception(sprintf('cannot write to %s', $dir));

            $f = FALSE;
            for ($i = 1; !$f; $i++)
            {
                $branch = sprintf('gtwconflict-%02d', $i);
                if (file_exists($branch))
                    continue;
                try
                {
                    $f = fopen($repo->getDir().'/refs/heads/'.$branch, 'xb');
                }
                catch (Exception $e)
                {
                    
                    // fopen() will raise a warning if the file already
                    // exists, which Core will make into an Exception.
                }
            }
            flock($f, LOCK_EX);
        }
        foreach ($pending as $obj)
            $obj->write();
        ftruncate($f, 0);
        fwrite($f, sha1_hex($lastcommit->getName()));
        fclose($f);
        */
        return true;
    }

    protected function _createCommit($repo, $commit, $message, $authorName, $authorMail) {
/*
        // new blob to store the new content
        $blob = new GitBlob($repo);
        $blob->data = $this->fileGitObject->data;
        $blob->rehash();

        // new tree object
        $tree = clone $this->treeGitObject;
        $pending = $tree->updateNode($this->getRealPathFileName(), 0100640, $blob->getName());
        $tree->rehash();
        $pending[] = $tree;

        // new commit object
        $newcommit = new GitCommit($repo);
        $newcommit->tree = $tree->getName();
        $newcommit->parents = array($commit->getName());
        $stamp = new GitCommitStamp;
        $stamp->name = $authorName;
        $stamp->email = $authorMail;
        $stamp->time = time();
        $stamp->offset = idate('Z', $stamp->time);
        $newcommit->author = $stamp;
        $newcommit->committer = $stamp;
        $lines = explode("\n", $message);
        $newcommit->summary = array_shift($lines);
        $newcommit->detail = implode("\n", $lines);
        $newcommit->rehash();

        array_unshift($pending, $blob);
        array_unshift($pending, $newcommit);
        return $pending;
*/
    }

    protected function _createMergeCommit($repo, $newcommit, $tipCommitId, $blob) {
/*
        $ref = sha1_bin($tipCommitId);
        $tip = $repo->getObject($ref);

        $tree = clone $repo->getObject($tip->tree);
        $pending = $tree->updateNode($this->getRealPathFileName(), 0100640, $blob->getName());
        $tree->rehash();

        $mergecommit = new GitCommit($repo);
        $mergecommit->tree = $tree->getName();
        $mergecommit->parents = array($tip->getName(), $newcommit->getName());
        $mergecommit->author = $newcommit->author;
        $mergecommit->committer = $newcommit->committer;
        $mergecommit->summary = 'Fast merge';
        $mergecommit->detail = '';
        $mergecommit->rehash();
        return array($mergecommit, $tree);
*/
    }

    function moveTo($newPath, $message, $authorName, $authorMail, $commit = null) {
        throw new \Exception('not implemented');
    }

    function remove($message, $authorName, $authorMail) {
        throw new \Exception('not implemented');
    }

    protected $extraData = array();

    /**
     * returns the content of the file as HTML content. If a generator is set on the object,
     * it will be used to generate the HTML version of the content
     * @param string $webBasePath the path to the wiki content, relative the domain name
     * @return string the html content
     */
    function getHtmlContent($webBasePath) {
        if ($this->fileGitObject) {
            if ($this->generator) {
                $content = $this->generator->generate($this->fileGitObject->data, $webBasePath, $this->path.'/');
                $this->extraData = $this->generator->getExtraData();
                return $content;
            } else {
                return '<pre>'.htmlspecialchars($this->fileGitObject->data).'</pre>';
            }
        }
        return '';
    }

    function getExtraData() {
        return $this->extraData;
    }
    
    function getContent() {
        if ($this->fileGitObject)
            return $this->fileGitObject->data;
        return '';
    }

    function setContent($content) {
        $this->newFileGitObject = new \Glip\GitBlob($this->repo->git(), null, null, $content);
    }

    function getTitle() {
        throw new \Exception('not implemented');
    }

    function setTitle($title) {
        throw new \Exception('not implemented');
    }

    function getDescription() {
        throw new \Exception('not implemented');
    }

    function setDescription() {
        throw new \Exception('not implemented');
    }

    function getMimeType() {
        if ($this->generator) {
            return 'text/html';
        }
        else {
            return \jFile::getMimeTypeFromFilename($this->name);
        }
    }

    function setMimeType($title) {
        throw new \Exception('not implemented');
    }

}
