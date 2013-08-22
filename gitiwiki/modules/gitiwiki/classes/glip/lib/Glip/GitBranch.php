<?php
/*
 * Copyright (C) 2008, 2009 Patrik Fimml, Sjoerd de Jong
 *
 * This file is part of glip.
 *
 * glip is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.

 * glip is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with glip.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Glip;

class GitBranch implements \ArrayAccess
{
  protected
    $git = null,        // the git repository
    $branchName = null, // the name of this branch
    $tipCache = null,   // cache for the tip commit of the branch
    $stash = array();   // (array of string) stash with mutations

  /**
   * Constructor
   *
   * @return void
   * @author Sjoerd de Jong
   **/
  public function __construct(Git $git, $branchName, &$stashSource = null, $stashKeyPrefix = 'gitbranch_')
  {
    $this->git = $git;
    $this->branchName = $branchName;
    if (is_array($stashSource))
    {
      if (isset($stashSource[$stashKeyPrefix.$branchName]))
      {
        $this->stash =& $stashSource[$stashKeyPrefix.$branchName];
      }
      else
      {
        $stashSource[$stashKeyPrefix.$branchName] =& $this->stash;
      }
    }
    elseif ($stashSource instanceof ArrayAccess)
    {
      $this->stash =& $stashSource;
    }
    elseif (!is_null($stashSource))
    {
      throw new \InvalidArgumentException('StashSource is not an array-cache');
    }
  }

  /**
   * returns the stash of this branch as $path => $data
   *
   * @return (array) a reference to the current stash
   * @author The Young Shepherd
   **/
  public function &getStash()
  {
    return $this->stash;
  }

  /**
   * undocumented function
   *
   * @return void
   * @author The Young Shepherd
   **/
  public function clearStash($key = null)
  {
    if (is_null($key))
    {
      //clear the stash, don't assign it to an empty array as it might be a pointer inside an external store
      foreach (array_keys($this->stash) as $key)
      {
        unset($this->stash[$key]);
      }
    }
    else
    {
      unset($this->stash[(string)$key]);
    }
  }

  /**
   * isDirty returns true if changes are made in this branch which are not committed
   *
   * @return bool
   * @author The Young Shepherd
   **/
  public function isDirty()
  {
    return count($this->stash);
  }

  /**
   * commit commits all changes in the stash to the tip of this branch
   *
   * @param GitStamp $stamp Stamp for the author&committer of this commit
   * @param string $message Summary for this commit
   *
   * @return GitCommit The new commit tip
   * @author The Young Shepherd
   **/
  public function commit(GitCommitStamp $stamp, $message)
  {
    if (!$this->isDirty())
    {
      throw new \RuntimeException('Cannot commit empty stash');
    }
    $commit = new GitCommit($this->getTip());

    foreach ($this->stash as $path => $data)
    {
      if (is_null($data))
      {
        unset($commit[$path]);
      }
      else
      {
        $blob = new GitBlob($this->git);
        $blob->data = $data;
        $commit[$path] = $blob;
      }
    }
    $commit->author = $stamp;
    $commit->committer = $stamp;
    $commit->setMessage($message);

    $this->updateTipTo($commit);

    $this->clearStash();
    return $commit;
  }

  /**
   * @brief get the object at the tip of the branch
   *
   * @returns (GitCommit) The tip of the branch
   */
  public function getTip($noCache = false)
  {
    if (!is_null($this->tipCache) && !$noCache)
    {
      return $this->tipCache;
    }

    $subpath = sprintf('refs/heads/%s', $this->branchName);
    $path = sprintf('%s/%s', $this->git->getDir(), $subpath);
    if (file_exists($path))
    {
      $sha = substr(file_get_contents($path),0,40);
      $this->tipCache = new GitCommit($this->git, new SHA($sha));
      return $this->tipCache;
    }

    $path = sprintf('%s/packed-refs', $this->git->getDir());
    if (file_exists($path))
    {
      $head = NULL;
      $f = fopen($path, 'rb');
      flock($f, LOCK_SH);
      while ($head === NULL && ($line = fgets($f)) !== FALSE)
      {
        if ($line{0} == '#')
          continue;
        $parts = explode(' ', trim($line));
        if (count($parts) == 2 && $parts[1] == $subpath)
          $head = new SHA($parts[0]);
      }
      fclose($f);
      if ($head !== NULL)
      {
        $this->tipCache = new GitCommit($this->git, new SHA($head));
        return $this->tipCache;
      }
    }
    throw new \Exception(sprintf('no such branch: %s', $branch));
  }

  /**
   * branchExists returns if a branch exists in the repos or not
   *
   * @return bool if the branch exists
   * @author Sjoerd de Jong
   **/
  public function exists()
  {
    try {
      $tip = $this->getTip();
      return true;
    } catch (\Exception $e) {}
    return false;
  }

  /**
     * openBranch opens and locks the branch for other writing
     *
     * @return GitCommit the head commit of the branch or null if empty branch
     * @author The Young Shepherd
     **/
  public function setTip(GitCommit $commit)
  {
    $fBranch = fopen(sprintf('%s/refs/heads/%s', $this->git->getDir(), $this->branchName), 'a+b');
    flock($fBranch, LOCK_EX);
    $commit->write();
    ftruncate($fBranch, 0);
    fwrite($fBranch, $commit->getSha()->hex());
    fclose($fBranch);
    $this->tipCache = $commit;
  }

  /**
   * updateTipTo tries to update the tip to the supplied commit
   *
   * @return return (bool) true on success, (int) problem on failure
   * @author The Young Shepherd
   *
   * @throws TODO should throw exceptions if not a normal commit is possible
   **/
  public function updateTipTo(GitCommit $commit, $allowFastForward = true)
  {
    $tip = $this->getTip();
    if ($tip->equalTo($commit->parents))
    {
      $this->setTip($commit);
      return true;
    }
    else
    {
      throw new \Exception('Merge needed! TODO');
    }
  }

  /**
   * Returns if the supplied path exists (implements the ArrayAccess interface)
   *
   * @param  string $index The relative path to the node
   *
   * @return bool true if the error exists, false otherwise
   */
  public function offsetExists($path)
  {
    $path = new GitPath($path);
    return isset($this->stash[(string)$path]) ||
      ($this->getTip()->offsetExists($path) && !array_key_exists((string)$path, $this->stash));
  }

  /**
   * Returns the node associated with the supplied path (implements the ArrayAccess interface).
   *
   * @param  string $index  The path of the object to get
   *
   * @return GitBlob, GitTree
   */
  public function offsetGet($path)
  {
    $path = new GitPath($path);
    $object = array_key_exists((string)$path,$this->stash)
      ? new GitBlob($this->git, null, null, $this->stash[(string)$path])
      : $this->getTip()->offsetGet($path);
    if (is_null($object) || $object instanceof GitTree)
    {
      //make sure it's a cleaned reference, and that it's referencing a path
      $path = new GitPath($path.'/');

      foreach (array_keys($this->stash) as $key)
      {
        $file = new GitPath($key);
        if ($file->hasAncestor($path))
        {
          //remove $path part of the stash item
          $file->splice(0, count($path));

          if (is_null($object))
          {
            $object = new GitTree($this->git);
          }
          elseif ($object->isReadOnly())
          {
            $object = clone $object;
          }

          if (is_null($this->stash[$key]))
          {
            unset($object[$file]);
          }
          else
          {
            $object[$file] = new GitBlob($this->git, null, null, $this->stash[$key]);
          }
        }
      }
    }
    return $object;
  }

  /**
   * Sets the object at path (implements the ArrayAccess interface).
   *
   * @param string $path
   * @param string $data
   *
   */
  public function offsetSet($path, $data)
  {
    $path = new GitPath($path);
    $this->stash[(string)$path] = $data;
  }

  /**
   * Removes the node from the path recursively
   *
   * @param string $path
   */
  public function offsetUnset($path)
  {
    $path = new GitPath($path);
    if ($this->getTip()->offsetExists((string)$path))
    {
     $this->stash[(string)$path] = null;
    }
    else
    {
      unset($this->stash[(string)$path]);
    }
  }
}