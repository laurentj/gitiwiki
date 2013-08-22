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

class Git implements \ArrayAccess
{
  protected
    $dir = "",          // (string) location of the git repository
    $packs = array(),   // (array of SHA) all packs in the repository
    $branchCache = array(), // (array of GitBranch) cache for branches
    $stash = array();   // (array of data strings) cache for stash for branches

  const
    OBJ_NONE      = 0,
    OBJ_COMMIT    = 1,
    OBJ_TREE      = 2,
    OBJ_BLOB      = 3,
    OBJ_TAG       = 4,
    OBJ_OFS_DELTA = 6,
    OBJ_REF_DELTA = 7;

  /**
   * Gets the directory of this repository
   *
   * @return (string) the directory of this git repository
   * @author The Young Shepherd
   **/
  public function getDir()
  {
    return $this->dir;
  }

  /**
   * returns the integer type id of the object
   *
   * @param (string) $name The name of the object
   * @return (integer) The type of the object
   * @author Sjoerd de Jong
   **/
  static public function getTypeID($name)
  {
    switch ($name)
    {
      case 'commit': return Git::OBJ_COMMIT;
      case 'tree':   return Git::OBJ_TREE;
      case 'blob':   return Git::OBJ_BLOB;
      case 'tag':    return Git::OBJ_TAG;
    }
    throw new \Exception(sprintf('unknown type name: %s', $name));
  }

  /**
   * returns the type of the object
   *
   * @param (integer) id of the object
   * @return (string) type of object
   * @author Sjoerd de Jong
   **/
  static public function getTypeName($id)
  {
    switch ($id)
    {
      case Git::OBJ_COMMIT: return 'commit';
      case Git::OBJ_TREE:   return 'tree';
      case Git::OBJ_BLOB:   return 'blob';
      case Git::OBJ_TAG:    return 'tag';
    }
    throw new \Exception(sprintf('unknown type id: %s', $id));
  }

  /**
   * Takes a mandatory directory and sets up the internal structure
   *
   * @param (string) $dir The location of the GIT repository
   * @param (array) $stashSource An array for storing the cache
   * @return void
   * @author Sjoerd de Jong
   **/
  public function __construct($dir, &$stashSource = null, $stashKey = 'git_stash')
  {
    $this->dir = $dir.'/.git';

    if (is_array($stashSource))
    {
      if (isset($stashSource[$stashKey]))
      {
        $this->stash =& $stashSource[$stashKey];
      }
      else
      {
        $stashSource[$stashKey] =& $this->stash;
      }
    }

    $this->packs = array();
    $dir = sprintf('%s/objects/pack', $this->dir);
    if (is_dir($dir) and $dh = opendir(sprintf('%s/objects/pack', $this->dir)))
        while (($entry = readdir($dh)) !== FALSE)
        if (preg_match('#^pack-([0-9a-fA-F]{40})\.idx$#', $entry, $m))
            $this->packs[] = new SHA($m[1]);
  }

  /**
   * @brief Tries to find $object_name in the fanout table in $f at $offset.
   *
   * @return array The range where the object can be located (first possible
   * location and past-the-end location)
   */
  protected function readFanout($f, $object_name, $offset)
  {
    $object_name = (string)$object_name;
    if ($object_name{0} == "\x00")
    {
      $cur = 0;
      fseek($f, $offset);
      $after = Binary::fuint32($f);
    }
    else
    {
      fseek($f, $offset + (ord($object_name{0}) - 1)*4);
      $cur = Binary::fuint32($f);
      $after = Binary::fuint32($f);
    }

    return array($cur, $after);
  }

  /**
   * @brief Try to find an object in a pack.
   *
   * @param $object_name (string) name of the object (binary SHA1)
   * @return (array) an array consisting of the name of the pack (SHA) and
   * the byte offset inside it, or NULL if not found
   */
  protected function findPackedObject(SHA $objectSha)
  {
    foreach ($this->packs as $packSha)
    {
      $index = fopen(sprintf('%s/objects/pack/pack-%s.idx', $this->dir, $packSha->hex()), 'rb');
      flock($index, LOCK_SH);

      /* check version */
      $magic = fread($index, 4);
      if ($magic != "\xFFtOc")
      {
        /* version 1 */
        /* read corresponding fanout entry */
        list($cur, $after) = $this->readFanout($index, $objectSha, 0);

        $n = $after-$cur;
        if ($n == 0)
          continue;

        /*
         * TODO: do a binary search in [$offset, $offset+24*$n)
         */
        fseek($index, 4*256 + 24*$cur);
        for ($i = 0; $i < $n; $i++)
        {
          $off = Binary::fuint32($index);
          $name = fread($index, 20);
          if ($name === (string)$objectSHA)
          {
            /* we found the object */
            fclose($index);
            return array($packSha, $off);
          }
        }
      }
      else
      {
        /* version 2+ */
        $version = Binary::fuint32($index);
        if ($version == 2)
        {
          list($cur, $after) = $this->readFanout($index, $objectSha, 8);

          if ($cur == $after)
            continue;

          fseek($index, 8 + 4*255);
          $total_objects = Binary::fuint32($index);

          /* look up sha1 */
          fseek($index, 8 + 4*256 + 20*$cur);
          for ($i = $cur; $i < $after; $i++)
          {
            $name = fread($index, 20);
            if ($name === (string)$objectSha)
              break;
          }
          if ($i == $after)
            continue;

          fseek($index, 8 + 4*256 + 24*$total_objects + 4*$i);
          $off = Binary::fuint32($index);
          if ($off & 0x80000000)
          {
            /* packfile > 2 GB. Gee, you really want to handle this
             * much data with PHP?
             */
            throw new \Exception('64-bit packfiles offsets not implemented');
          }

          fclose($index);
          return array($packSha, $off);
        }
        else
        {
          throw new \Exception('unsupported pack index format');
        }
      }
      fclose($index);
    }
    /* not found */
    return NULL;
  }

  /**
   * @brief Apply the git delta $delta to the byte sequence $base.
   *
   * @param $delta (string) the delta to apply
   * @param $base (string) the sequence to patch
   * @return (string) the patched byte sequence
   */
  protected function applyDelta($delta, $base)
  {
    $pos = 0;

    $base_size = Binary::git_varint($delta, $pos);
    $result_size = Binary::git_varint($delta, $pos);

    $r = '';
    while ($pos < strlen($delta))
    {
      $opcode = ord($delta{$pos++});
      if ($opcode & 0x80)
      {
        /* copy a part of $base */
        $off = 0;
        if ($opcode & 0x01) $off = ord($delta{$pos++});
        if ($opcode & 0x02) $off |= ord($delta{$pos++}) <<  8;
        if ($opcode & 0x04) $off |= ord($delta{$pos++}) << 16;
        if ($opcode & 0x08) $off |= ord($delta{$pos++}) << 24;
        $len = 0;
        if ($opcode & 0x10) $len = ord($delta{$pos++});
        if ($opcode & 0x20) $len |= ord($delta{$pos++}) <<  8;
        if ($opcode & 0x40) $len |= ord($delta{$pos++}) << 16;
        $r .= substr($base, $off, $len);
      }
      else
      {
        /* take the next $opcode bytes as they are */
        $r .= substr($delta, $pos, $opcode);
        $pos += $opcode;
      }
    }
    return $r;
  }

  /**
   * @brief Unpack an object from a pack.
   *
   * @param $pack (resource) open .pack file
   * @param $object_offset (integer) offset of the object in the pack
   * @return (array) an array consisting of the object type name (string) and the
   * binary representation of the object (string)
   */
  protected function unpackObject($pack, $object_offset)
  {
    fseek($pack, $object_offset);

    /* read object header */
    $c = ord(fgetc($pack));
    $type = ($c >> 4) & 0x07;
    $size = $c & 0x0F;
    for ($i = 4; $c & 0x80; $i += 7)
    {
      $c = ord(fgetc($pack));
      $size |= ($c << $i);
    }

    /* compare sha1_file.c:1608 unpack_entry */
    if ($type == Git::OBJ_COMMIT || $type == Git::OBJ_TREE || $type == Git::OBJ_BLOB || $type == Git::OBJ_TAG)
    {
      /*
       * We don't know the actual size of the compressed
       * data, so we'll assume it's less than
       * $object_size+512.
       *
       * FIXME use PHP stream filter API as soon as it behaves
       * consistently
       */
      $data = gzuncompress(fread($pack, $size+512), $size);
    }
    else if ($type == Git::OBJ_OFS_DELTA)
    {
      /* 20 = maximum varint length for offset */
      $buf = fread($pack, $size+512+20);

      /*
       * contrary to varints in other places, this one is big endian
       * (and 1 is added each turn)
       * see sha1_file.c (get_delta_base)
       */
      $pos = 0;
      $offset = -1;
      do
      {
          $offset++;
          $c = ord($buf{$pos++});
          $offset = ($offset << 7) + ($c & 0x7F);
      }
      while ($c & 0x80);

      $delta = gzuncompress(substr($buf, $pos), $size);
      unset($buf);

      $base_offset = $object_offset - $offset;
      assert($base_offset >= 0);
      list($type, $base) = $this->unpackObject($pack, $base_offset);

      $data = $this->applyDelta($delta, $base);
    }
    else if ($type == Git::OBJ_REF_DELTA)
    {
      //TODO the following line is untested
      $base_name = new SHA(fread($pack, 20));
      list($type, $base) = $this->getRawObject($base_name);

      // $size is the length of the uncompressed delta
      $delta = gzuncompress(fread($pack, $size+512), $size);

      $data = $this->applyDelta($delta, $base);
    }
    else
    {
      throw new \Exception(sprintf('object of unknown type %d', $type));
    }

    if (is_numeric($type))
    {
      $type = self::getTypeName($type);
    }
    return array($type, $data);
  }

  /**
  * @brief Fetch an object in its binary representation by name.
  *
  * Throws an exception if the object cannot be found.
  *
  * @param $object_name (string) name of the object (binary SHA1)
  * @return (array) an array consisting of the object type name (string) and the
  * binary representation of the object (string)
  */
  public function getRawObject(SHA $sha)
  {
    static $cache = array();
    /* FIXME allow limiting the cache to a certain size */

    if (!isset($cache[(string)$sha]))
    {
      $path = sprintf('%s/objects/%s/%s', $this->dir, substr($sha->hex(), 0, 2), substr($sha->hex(), 2));

      if (file_exists($path))
      {
        list($hdr, $object_data) = explode("\0", gzuncompress(file_get_contents($path)), 2);
        sscanf($hdr, "%s %d", $type, $object_size);

        $cache[(string)$sha] = array($type, $object_data);
      }
      else if ($x = $this->findPackedObject($sha))
      {
        list($pack_sha, $object_offset) = $x;

        $pack = fopen(sprintf('%s/objects/pack/pack-%s.pack', $this->dir, $pack_sha->hex()), 'rb');
        flock($pack, LOCK_SH);

        /* check magic and version */
        $magic = fread($pack, 4);
        $version = Binary::fuint32($pack);
        if ($magic != 'PACK' || $version != 2)
          throw new \Exception('unsupported pack format');

        $cache[(string)$sha] = $this->unpackObject($pack, $object_offset);
        fclose($pack);
      }
      else
      {
        throw new \Exception(sprintf('object not found: %s', $sha->hex()));
      }
    }

    return $cache[(string)$sha];
  }

  /**
   * @brief Fetch an object in its PHP representation.
   *
   * @param $sha (SHA) sha of the object (binary SHA1)
   * @return (GitObject) the object
   */
  public function getObject(SHA $sha)
  {
    list($type, $serialized) = $this->getRawObject($sha);
    $class = "Git".ucfirst($type);
    $object = new $class($this, $sha);
    $object->setSerialized($serialized);
    return $object;
  }

  public function getBranch($branchName = 'master')
  {
    if (!isset($this->branchCache['$branchName']))
    {
      $this->branchCache['$branchName'] = new GitBranch($this, $branchName, $this->stash);
    }
    return $this->branchCache['$branchName'];
  }

  /**
   * Returns if the supplied branch exists (implements the ArrayAccess interface)
   *
   * @param string $branchName the name of the branch
   *
   * @return bool true if the branch exists, false otherwise
   */
  public function offsetExists($branchName)
  {
    $branch = $this[$branchName];
    return $branch->exists();
  }

  /**
   * Returns the branch (implements the ArrayAccess interface)
   *
   * @param string $branchName the name of the branch
   *
   * @return GitBranch The existing or a new branch
   */
  public function offsetGet($branchName)
  {
    return $this->getBranch($branchName);
  }

  /**
   * offsetSet updates the tip of branch $branchName to GitCommit $commit (implements  the ArrayAccess interface)
   *
   * @param string $branchName the name of the branch
   * @param GitCommit $commit The commit to update the branch to
   *
   */
  public function offsetSet($branchName, $commit)
  {
    if (!$commit instanceof GitCommit)
    {
      throw new \InvalidArgumentException('No commit object supplied to update the branch to');
    }
    $branch = $this[$branchName];
    $branch->updateTipTo($commit);
  }

  /**
   * Not of use (implements  the ArrayAccess interface)
   *
   * @param string $branchName
   */
  public function offsetUnset($branchName)
  {
    throw new \Exception('Cannot unset a branch');
  }

}