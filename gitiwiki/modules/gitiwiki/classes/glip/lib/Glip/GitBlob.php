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

class GitBlob extends GitPathObject
{
  protected
    $data = array(
      'data' => null         // The data contained in this blob
      ),
    $mode = 0100640;         // The default mode for a BLOB

  public function __construct(Git $git, $sha = null, $mode = null, $data = null)
  {
    parent::__construct($git, $sha, $mode);
    if (!is_null($data))
    {
      $this->data['data'] = $data;
    }
  }

  public function unserialize($data)
  {
  	$this->data['data'] = $data;
  }

  protected function _serialize()
  {
  	return $this->data['data'];
  }
}