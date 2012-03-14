<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/

require_once(dirname(__FILE__).'/glip/glip.php');

class gtwRepo {
    /**
     * @var array
     * configuration parameters of the repository
     */
    protected $config;
    
    /**
     * @var Git
     * the git object representing the repository
     */
    protected $repo;


    function __construct($repoName) {
        $conf = jApp::config();
        if (!isset($conf->{'gwrepo_'.$repoName})) {
            throw new Exception ('the repository does not exists');
        }

        $this->config = $conf->{'gwrepo_'.$repoName};

        $this->repo = new Git($this->config['path']);
    }

    function getFile($path, $commitId = null) {

        // verify that the path does not contain names begining by '.' -> not found
        foreach (explode('/', $path) as $p) {
            if (strlen($p) && $p[0] == '.')
                return null;
        }

        if (!$commitId) {
            $commitId = $this->repo->getTip($this->config['branch']);
        }

        // extract the dir path and the file name from the given path
        if (substr($path, -1,1) == '/') {
            $name = 'index';
            $implicitName  = true;
        }
        else {
            $name = basename($path);
            $path = dirname($path);
            if ($path == '.')
                $path = '';
            $implicitName = false;
        }

        // retrieve the object corresponding to the dir
        $commit = $this->repo->getObject($commitId);
        $hash = $commit->find($path);
        if (!$hash)
            return null;

        $object = $this->repo->getObject($hash);
        if (!$object)
            return null;

        if ($object instanceof GitTree) {
            // ok, the dir path is really a directory
            $node = null;
            
            // does the given filename correspond to a file/dir ?
            if (isset($object->nodes[$name])) {
                $node = $object->nodes[$name];
                if ($node->is_dir) {
                    if ($implicitName)
                        // error we don't expect to find a directory 'index' under the given path
                        return null;

                    $object = $this->repo->getObject($node->object);
                    $name = 'index';
                    $node = null;
                }
                else {
                    $obj = $this->repo->getObject($node->object);
                    return array($name, $obj->data);
                }
            }

            // multiview : try to find a file with the given name + a known extension
            $extList = array('.html', '.htm', '.wiki', '.md');
            foreach($extList as $ext) {
                if (isset($object->nodes[$name.$ext])) {
                    $node = $object->nodes[$name.$ext];
                    break;
                }
            }

            if ($node) {
                if ($node->is_dir) {
                    // error, we don't expect to have a dir here
                    return null;
                }
                else {
                    $obj = $this->repo->getObject($node->object);
                    return array($node->name, $obj->data);
                }
            }
            else {
                return null;
            }
        }
        else {
            if ($implicitName) {
                return array(basename($path), $object->data);
            }
            else {
                return null;
            }
        }
/*

lecture :
    on a un chemin
    
        explode -> path / name
        si name = ''
            name = index -> name automatique

        on trouve l'objet correspondant à path

            trouvé: on cherche alors {name}(.html|.wiki|....)?
                trouvé :
                    si repertoire
                        on cherche path/name/
                    sinon
                        affichage fichier trouvé
                pas trouvé :
                    si name = automatique
                        affichage liste fichiers du répertoire
                    sinon
                        404
            pas trouvé: 404

        /
        /foo
        /foo.html
        /foo/
        /foo/bar
            si repertoire -> affichage bar/index.html
            si fichier -> multiview

creation
        / -> /index.ext
        /foo -> /foo.ext
        /foo.ext
        /foo/ -> /foo/index.ext
        /foo/bar -> /foo/bar.ext
 
modification
 
*/



    }
    
    
}