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

class GitTree extends GitPathObject implements \ArrayAccess, \IteratorAggregate, \Countable
{
  protected
    $data = array(
      'nodes' => array()         // (array of $name => GitPathObject) The nodes referenced by this object
      ),
    $mode = 040000;              // the default mode for a tree object

  public function unserialize($data)
  {
    $this->data['nodes'] = array();
    $start = 0;
    while ($start < strlen($data))
    {
      $pos = strpos($data, "\0", $start);

      list($mode, $name) = explode(' ', substr($data, $start, $pos-$start), 2);

      $mode = intval($mode, 8);
      $is_dir = !!($mode & 040000);
      $class = "Glip\\" . ($is_dir ? "GitTree" : "GitBlob");

      $sha = new SHA(substr($data, $pos+1, 20));

      $start = $pos+21;

      $this->data['nodes'][$name] = new $class($this->git, $sha, $mode);
    }
    unset($data);
  }

  /**
   * serialize serializes all objects
   * it calls ->getSha() on all nodes, which will also lock all the subnodes recursively
   *
   * @return string The serialized representation of the tree
   * @author The Young Shepherd
   **/
  protected function _serialize()
  {
    $s = '';

    /* git requires nodes to be sorted */
    $trees = array();
    $blobs = array();
    foreach ($this->nodes as $path => $node)
    {
      if ($node instanceof GitTree)
      {
        $trees[$path] = $node;
      }
      else
      {
        $blobs[$path] = $node;
      }
    }
    ksort($trees);
    ksort($blobs);
    $this->data['nodes'] = array_merge($trees, $blobs);

    foreach ($this->nodes as $name => $node)
    {
      $s .= sprintf("%s %s\0%s",
              base_convert($node->getMode(), 10, 8),
              $name,
              $node->getSha()->bin()
            );
    }

    return $s;
  }

  /**
   * returns the relative path of an object in this
   *
   * @param $obj (GitPathObject) The object to find the path for
   * @return GitPath or null if not found
   **/
  public function getPath(GitPathObject $obj)
  {
    $nodes = $this->listRecursive(true);
    $path = array_search($obj, $nodes, true);
    return false === $path ? null : new GitPath($path);
  }

  /**
   * @brief Recursively list the contents of a tree.
   *
   * @returns (array mapping string to GitPathObject) An array where the keys are
   * paths relative to the current tree, and the values are GitPathObjects of
   * the corresponding blobs in binary representation.
   */
  public function listRecursive($listDirs = false)
  {
    $r = array();

    foreach ($this->nodes as $name => $node)
    {
      if ($node instanceof GitTree)
      {
        if ($listDirs)
        {
          $r[$name] = $node;
        }
        foreach ($node->listRecursive($listDirs) as $entry => $blob)
        {
          $r[$name . '/' . $entry] = $blob;
        }
      }
      else
      {
        $r[$name] = $node;
      }
    }

    return $r;
  }

  public function write()
  {
    if (!$this->exists)
    {
      foreach ($this->data['nodes'] as $node)
      {
        $node->write();
      }
    }
    return parent::write();
  }

  const TREEDIFF_A = 0x01;
  const TREEDIFF_B = 0x02;

  const TREEDIFF_REMOVED = self::TREEDIFF_A;
  const TREEDIFF_ADDED = self::TREEDIFF_B;
  const TREEDIFF_CHANGED = 0x03;

  static public function treeDiff($a_tree, $b_tree)
  {
    $a_blobs = $a_tree ? $a_tree->listRecursive() : array();
    $b_blobs = $b_tree ? $b_tree->listRecursive() : array();

    $a_files = array_keys($a_blobs);
    $b_files = array_keys($b_blobs);

    $changes = array();

    sort($a_files);
    sort($b_files);
    $a = $b = 0;
    while ($a < count($a_files) || $b < count($b_files))
    {
      if ($a < count($a_files) && $b < count($b_files))
        $cmp = strcmp($a_files[$a], $b_files[$b]);
      else
        $cmp = 0;
      if ($b >= count($b_files) || $cmp < 0)
      {
        $changes[$a_files[$a]] = self::TREEDIFF_REMOVED;
        $a++;
      }
      else if ($a >= count($a_files) || $cmp > 0)
      {
        $changes[$b_files[$b]] = self::TREEDIFF_ADDED;
        $b++;
      }
      else
      {
        if ($a_blobs[$a_files[$a]] != $b_blobs[$b_files[$b]])
            $changes[$a_files[$a]] = self::TREEDIFF_CHANGED;

        $a++;
        $b++;
      }
    }

    return $changes;
  }

  /**
   * Returns if the supplied path exists (implements the ArrayAccess interface)
   *
   * @param  string $path The relative path to the node
   *
   * @return bool true if the error exists, false otherwise
   */
  public function offsetExists($path)
  {
    $path = new GitPath($path);

    if ($path->isRoot())
    {
      // it's this object, so it exists!
      return true;
    }

    // check if the first element exists
    $exists = isset($this->nodes[$path[0]]);

    if ($exists && !$path->isSingle())
    {
      // this is a path with subdirectories,
      $sub = $this->nodes[$path[0]];
      $exists &= ($sub instanceof GitTree) && $sub->offsetExists((string)$path->getShifted());
    }

    return $exists;
  }

  /**
   * Returns the node associated with the supplied path (implements the ArrayAccess interface).
   *
   * @param  GitPath $path  The path of the object to get
   *
   * @return object at the path, or null if the object does not exist
   */
  public function offsetGet($path)
  {
    $path = new GitPath($path);

    if ($path->isRoot())
    {
      return $this;
    }

    $object = null;
    if (isset($this->nodes[$path[0]]))
    {
      $object = $this->nodes[$path[0]];
    }

    if (is_null($object) || $path->isSingle())
    {

      return $object;
    }

    if (!$object instanceof GitTree)
    {
      throw new \Exception(sprintf('Invalid path supplied: \'%s\', object is of class %s',(string)$path, get_class($object)));
    }

    return $object[$path->getShifted()];
  }

  /**
   * Sets the path to object (implements the ArrayAccess interface).
   *
   * @param string $path
   * @param string $object
   *
   */
  public function offsetSet($path, $object)
  {
    $path = new GitPath($path);

    if (!$object instanceof GitBlob)
    {
      throw new \Exception('Object should be a GitBlob');
    }

    if ($this->isReadOnly())
    {
      throw new \Exception('Can not write to locked object');
    }

    if ($path->isRoot())
    {
      throw new \Exception('Can not set self to another object');
    }

    if ($path->isSingle())
    {
      $beSureDataIsLoaded = $this->nodes;
      $this->data['nodes'][$path[0]] = $object;
    }
    else
    {
      if (isset($this->nodes[$path[0]]))
      {
        $sub = $this->nodes[$path[0]];
        if ($sub->isReadOnly())
        {
          // clone the loaded object, to make it a new one
          $sub = clone $sub;
        }
      }
      else
      {
        $sub = new GitTree($this->git);
      }

      if (!$sub instanceof GitTree)
      {
        throw new \Exception('Invalid path to set');
      }

      $this->data['nodes'][$path[0]] = $sub;

      $sub->offsetSet($path->getShifted(), $object);
    }
  }

  /**
   * Removes the node from the path recursively
   *
   * @param string $path
   */
  public function offsetUnset($path)
  {
    $path = new GitPath($path);

    if ($this->isReadOnly())
    {
      throw new \Exception('Can not write to locked object');
    }

    if (!$path->isSingle() && isset($this->nodes[$path[0]]))
    {
      $sub = $this->nodes[$path[0]];
      if (!$sub instanceof GitTree)
      {
        throw new \Exception('Invalid path');
      }
      if ($sub->isReadOnly())
      {
        $sub = clone $sub;
        $this->data['nodes'][$path[0]] = $sub;
      }
      $sub->offsetUnset($path->getShifted());
    }

    if ($path->isRoot())
    {
      throw new \Exception('Can not unset self');
    }

    if ($path->isSingle() || count($this->data['nodes'][$path[0]]) == 0)
    {
      unset($this->data['nodes'][$path[0]]);
    }
  }

  /**
   * implements iterator interface
   *
   * @return void
   * @author The Young Shepherd
   **/
  public function getIterator() {
    $beSureDataIsLoaded = $this->nodes;
    return new ArrayIterator($this->data['nodes']);
  }

  /**
   * implements countable interface
   *
   * @return void
   * @author The Young Shepherd
   **/
  public function count()
  {
    return count($this->nodes);
  }
}