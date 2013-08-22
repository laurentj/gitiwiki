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

abstract class GitPathObject extends GitObject
{
  protected
    $mode = null; // the mode of this object

  public function getMode()
  {
    return $this->mode;
  }

  public function setMode($mode)
  {
    if ($this->isReadOnly())
    {
      throw new \Exception('cannot set mode on a locked object');
    }
    $this->mode = $mode;
  }

  /**
   * Constructor, sets mode of this object
   *
   * @return void
   * @author The Young Shepherd
   **/
  public function __construct(Git $git, $sha = null, $mode = null)
  {
    if (!is_null($mode))
    {
      $this->mode = $mode;
    }
    parent::__construct($git, $sha);
  }

  /**
   * Gets all commits in which this object changed
   *
   * @param $commitTip The commit from where to start searching
   * @return array of GitCommit
   */
  public function getHistory(GitCommit $commitTip)
  {
    $r = array();
    $commits = $commitTip->getHistory();
    $path = $commitTip->getPath($this);
    $last = null;
    foreach ($commits as $commit)
    {
      $sha = (string)$commit[$path];
      foreach ($commit->parents as $parent)
      {
        if ($sha!==(string)$parent[$path])
        {
          $r[] = $commit;
          break;
        }
      }
    }
    return $r;
  }

  /**
   * getCommitForLastModification returns the last commit where this object is modified
   *
   * @return GitCommit
   **/
  public function getCommitForLastModification($from)
  {
    $commit = $this->git->getCommitObject($from);
    $path = $this->getPath($commit);

    $commits = $commit->getHistory();
    $commits = array_reverse($commits);
    $r = NULL;
    $lastblob = $this->getName();
    foreach ($commits as $commit)
    {
        $blobname = $commit[$path];
        if ($blobname != $lastblob)
            break;
        $r = $commit->committer->time;
    }
    assert($r !== NULL); /* something is seriously wrong if this happens */
    return $r;
  }
}
