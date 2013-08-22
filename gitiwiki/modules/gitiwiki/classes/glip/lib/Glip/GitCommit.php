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

/**
 * GitCommit resembles commit objects of a git repos
 *
 * The Array access allows manipulation of the tree inside the commit
 *
 * @package default
 * @author The Young Shepherd
 **/
class GitCommit extends GitObject implements \ArrayAccess, \IteratorAggregate, \Countable
{
  protected
    $data = array(
      'tree' => null,         // (GitTree) The tree referenced by this commit
      'parents' => array(),   // (array of GitCommit) Parent commits of this commit
      'author' => null,       // (GitCommitStamp) The author of this commit
      'committer' => null,    // (GitCommitStamp) The committer of this commit
      'summary' => "",        // (string) Commit summary, i.e. the first line of the commit message
      'detail' => ""          // (string) Everything after the first line of the commit message
      ),
    $commitHistory = null;    // cache for history of this object

  /**
   * Constructor, takes extra arguments for lazy loading git objects
   *
   * @return void
   * @author Sjoerd de Jong
   **/
  public function __construct($git, $sha = null)
  {
    if ($git instanceof GitCommit || is_array($git))
    {
      //assume a new commit to be based on a previous commit
      //$git represents the parents of the new object
      $parents = is_array($git) ? $git : array($git);
      $firstParent = $parents[0];
      parent::__construct($firstParent->getGit());
      $this->parents = $parents;
      $this->tree = clone $firstParent->tree;
    }
    else
    {
      parent::__construct($git, $sha);
    }
  }

  public function unserialize($data)
  {
  	$lines = explode("\n", $data);
  	unset($data);

  	$meta = array('parent' => array());
  	while (($line = array_shift($lines)) != '')
  	{
	    $parts = explode(' ', $line, 2);
	    if (!isset($meta[$parts[0]]))
    		$meta[$parts[0]] = array($parts[1]);
  	  else
  		  $meta[$parts[0]][] = $parts[1];
  	}

  	$this->data['tree'] = new GitTree($this->git, $meta['tree'][0]);

  	$parents = array();
  	foreach ($meta['parent'] as $sha)
  	{
  	  $parents[] = new GitCommit($this->git, $sha);
  	}
  	$this->data['parents'] = $parents;

  	$this->data['author'] = new GitCommitStamp();
  	$this->data['author']->unserialize($meta['author'][0]);

  	$this->data['committer'] = new GitCommitStamp();
  	$this->data['committer']->unserialize($meta['committer'][0]);

  	$this->data['summary'] = array_shift($lines);
  	$this->data['detail'] = implode("\n", $lines);
  }

  public function setMessage($message)
  {
    $message = explode("\n",$message,2);
    $this->summary = isset($message[0]) ? $message[0] : "";
    $this->detail = isset($message[1]) ? $message[1] : "";
  }

  protected function _serialize()
  {
  	$s = sprintf("tree %s\n", $this->tree->getSha()->hex());

  	foreach ($this->parents as $parent)
  	{
	    $s .= sprintf("parent %s\n", $parent->getSha()->hex());
  	}

  	$s .= sprintf("author %s\n", $this->author->serialize());
  	$s .= sprintf("committer %s\n", $this->committer->serialize());

  	$s .= "\n";

  	$s .= $this->summary."\n".$this->detail;

  	return $s;
  }

  /**
   * returns path of an object
   *
   * @param $commitTip The commit from where to start searching
   * @return array of strings for each part of the path, empty array if not found
   **/
  public function getPath(GitPathObject $obj)
  {
    return $this->tree->getPath($obj);
  }

  /**
   * @brief Get commit history in topological order.
   *
   * @returns (array of GitCommit)
   */
  public function getHistory(GitCommit $commitTip = null)
  {
    if (is_null($this->commitHistory))
    {
      /* count incoming edges */
      $inc = array();

      $queue = array($this);
      while (($commit = array_shift($queue)) !== NULL)
      {
        foreach ($commit->parents as $parent)
        {
          if (!isset($inc[(string)$parent]))
          {
            $inc[(string)$parent] = 1;
            $queue[] = $parent;
          }
          else
          {
            $inc[(string)$parent]++;
          }
        }
      }

      $queue = array($this);
      $this->commitHistory = array();
      while (($commit = array_pop($queue)) !== NULL)
      {
        array_unshift($this->commitHistory, $commit);
        foreach ($commit->parents as $parent)
        {
          if (--$inc[(string)$parent] == 0)
          {
            $queue[] = $parent;
          }
        }
      }
    }

    return $this->commitHistory;
  }

  /**
   * writes the object to disc
   * also writes the subtree & parents to disk
   *
   * @return void
   * @author The Young Shepherd
   **/
  public function write()
  {
    if (is_null($this->author))
    {
      $this->author = new GitCommitStamp();
    }
    if (is_null($this->committer))
    {
      $this->committer = new GitCommitStamp();
    }
    if (!$this->exists())
    {
      $this->data['tree']->write();
      foreach ($this->data['parents'] as $parent)
      {
        $parent->write();
      }
    }
    return parent::write();
  }

  public function __clone()
  {
    parent::__clone();
    $this->data['tree'] = clone $this->tree;
  }

  static public function treeDiff($a, $b)
  {
      return GitTree::treeDiff($a ? $a->tree : NULL, $b ? $b->tree : NULL);
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
    return $this->tree->offsetExists($path);
  }

  /**
   * Returns the node associated with the supplied path (implements the ArrayAccess interface).
   *
   * @param  string $index  The path of the object to get
   *
   * @return string
   */
  public function offsetGet($path)
  {
    return $this->tree->offsetGet($path);
  }

  /**
   * Sets the object at path (implements the ArrayAccess interface).
   *
   * @param string $path
   * @param string $object
   *
   */
  public function offsetSet($path, $object)
  {
    if (!$object instanceof GitBlob)
    {
      $blob = new GitBlob($this->git);
      $blob->data = (string)$object;
      $object = $blob;
    }
    $this->tree->offsetSet($path, $object);
  }

  /**
   * Removes the node from the path recursively
   *
   * @param string $path
   */
  public function offsetUnset($path)
  {
    $this->tree->offsetUnset($path);
  }

  /**
   * implements iterator interface
   *
   * @return void
   * @author The Young Shepherd
   **/
  public function getIterator() {
    return $this->tree->getIterator();
  }

  /**
   * implements countable interface
   *
   * @return void
   * @author The Young Shepherd
   **/
  public function count()
  {
    return $this->tree->count();
  }
}