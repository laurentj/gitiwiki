<?php
/*
 * Copyright (C) 2008 Patrik Fimml, Sjoerd de Jong
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

abstract class GitObject implements \Serializable
{
  protected
    $git = null,        // the git repository this object belongs to
    $data = array(),    // the data this object contains
    $sha = null,        // (SHA) the sha of this object
    $isLoaded = false,  // (bool) is the object loaded from the git repos?
    $exists = false,    // (bool) does the object exist (is written) in the git repos?
    $serialized = null; // (string) containing the serialized version of this object

  /**
   * Constructor, takes extra arguments for lazy loading git objects
   *
   * @return void
   * @author Sjoerd de Jong
   **/
  public function __construct(Git $git, $sha = null)
  {
    $this->git = $git;
    if (!is_null($sha))
    {
      $this->sha = $sha instanceof SHA ? $sha : new SHA($sha);
      $this->exists = true;
    }
  }

  /**
   * Clone makes sure the cloned object is writable again
   *
   * @return void
   * @author Sjoerd de Jong
   **/
  public function __clone()
  {
    if ($this->exists && !$this->isLoaded)
    {
      //load the old object
      $this->load();
    }
    // unlock the object
    $this->sha = null;
    $this->isLoaded = false;
    $this->exists = false;
    $this->serialized = null;
  }

  public function setSerialized($serialized)
  {
    if (!is_null($this->serialized))
    {
      throw new \Exception("Can only set serialization on an uncomputed, not loaded object");
    }
    $this->serialized = $serialized;
  }

  public function getGit()
  {
    return $this->git;
  }

  public function isReadOnly()
  {
    return !is_null($this->sha);
  }

  /**
   * @brief Get the object's cached SHA-1 hash value.
   *
   * @returns (SHA) The hash value (binary sha1).
   */
  public function getSha()
  {
    if (is_null($this->sha))
    {
      $data = $this->serialize();
      $this->sha = SHA::hash(sprintf("%s %d\0%s",$this->getTypeName(),strlen($data),$data));
    }
    return $this->sha;
  }

  public function isLoaded()
  {
    return $this->isLoaded;
  }

  public function exists()
  {
    return $this->exists;
  }

  public function load()
  {
    if (!$this->exists())
    {
      throw new \Exception('Can only load data of a locked object');
    }

    if (is_null($this->serialized))
    {
      list($type, $this->serialized) = $this->git->getRawObject($this->getSha());

      if ($type !== $this->getTypeName())
      {
        //throw new \Exception('Error loading data of type \''.$type.'\' into object of type \''.$this->getTypeName().'\'');
      }
    }

    $this->unserialize($this->serialized);
    $this->isLoaded = true;
  }

  public function __set($name, $value)
  {
    if ($this->isReadOnly())
    {
      throw new \Exception("Cannot set properties on a locked object");
    }

    if (!in_array($name, array_keys($this->data)))
    {
      throw new \Exception("$name is not a settable property of object ".get_class($this));
    }

    $this->data[$name] = $value;
  }

  public function __get($name)
  {
    if ($this->exists() && !$this->isLoaded())
    {
      $this->load();
    }

    if (!in_array($name, array_keys($this->data)))
    {
      throw new \Exception("$name is not a gettable property of object ".get_class($this));
    }

    return isset($this->data[$name]) ? $this->data[$name] : null;
  }

  /**
   * get the objects type name, either 'blob', 'tree', or 'commit'
   *
   * @return (string) the type name
   * @author Sjoerd de Jong
   **/
  public function getTypeName()
  {
    return strtolower(substr(get_class($this),3));
  }

  /**
   * Get the object's type number
   *
   * @returns (integer) One of Git::OBJ_COMMIT, Git::OBJ_TREE or GIT::OBJ_BLOB.
   */
  public function getTypeId()
  {
    return Git::getTypeId($this->getTypeName());
  }

  /**
   * @brief Get the string representation of an object.
   *
   * @returns The serialized representation of the object, as it would be
   * stored by git.
   */
  public function serialize()
  {
    if (is_null($this->serialized))
    {
      $this->serialized = $this->_serialize();
    }
    return $this->serialized;
  }
  abstract protected function _serialize();

  /**
   * @brief Populate this object with values from its string representation.
   *
   * Note that the types of $this and the serialized object in $data have to
   * match.
   *
   * @param $data (string) The serialized representation of an object, as
   * it would be stored by git.
   */
  public function unserialize($serialized)
  {
    throw new \Exception('Unserialize neeeds to be overridden');
  }

  /**
   * __tostring prints the git representation of this object
   * please note that is locks the object, as it calls getSha()
   *
   * @return void
   * @author Sjoerd de Jong
   **/
  public function __tostring()
  {
    return sprintf("%s",$this->getSha()->hex());
  }

  /**
   * @brief Write this object in its serialized form to the git repository
   * given at creation time.
   */
  public function write()
  {
    if ($this->exists())
    {
      return true;
    }

    $sha1 = $this->getSha()->hex();
    $path = sprintf('%s/objects/%s/%s', $this->git->getDir(), substr($sha1, 0, 2), substr($sha1, 2));

    if (file_exists($path))
    {
      return false;
    }

    if (!is_dir(dirname($path)))
    {
      mkdir(dirname($path), 0770);
    }

    $f = fopen($path, 'ab');
    flock($f, LOCK_EX);
    ftruncate($f, 0);
    $data = $this->serialize();
    $data = $this->getTypeName().' '.strlen($data)."\0".$data;
    fwrite($f, gzcompress($data));
    fclose($f);

    $this->exists = true;
    return TRUE;
  }

  /**
   * equalTo compares this object to one or an array of other objects. If all
   * objects are the same it returns true
   *
   * @return (bool) True if all objects are the same
   * @author Sjoerd de Jong
   **/
  public function equalTo($object)
  {
    if (is_array($object))
    {
      $allEqual = true;
      foreach ($object as $obj)
      {
        $allEqual &= $this->equalTo($obj);
      }
      return $allEqual;
    }
    else
    {
      return $object instanceof GitObject && $object->getSha()->hex() === $this->getSha()->hex();
    }
  }
}