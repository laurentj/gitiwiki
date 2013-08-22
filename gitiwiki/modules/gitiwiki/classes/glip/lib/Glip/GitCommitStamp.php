<?php
/*
 * Copyright (C) 2008 Patrik Fimml
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

class GitCommitStamp
{
    public $name;
    public $email;
    public $time;
    public $offset;

  public function __construct($name = null, $email = null)
  {
    $name = trim($name);
    if (empty($name))
    {
      $this->name = "Anonymous User";
    }
    else
    {
      $this->name = $name;
    }
    if (empty($email))
    {
      $this->email = "anonymous@".(isset($_SERVER) && isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "unknown");
    }
    else
    {
      $this->email = $email;
    }
    $this->time = time();
    $this->offset = idate('Z', $this->time);
  }

  public function unserialize($data)
  {
  	assert(preg_match('/^(.+?)\s+<(.+?)>\s+(\d+)\s+([+-]\d{4})$/', $data, $m));
  	$this->name = $m[1];
  	$this->email = $m[2];
  	$this->time = intval($m[3]);
  	$off = intval($m[4]);
  	$this->offset = intval($off/100) * 3600 + ($off%100) * 60;
  }

  public function serialize()
  {
  	if ($this->offset%60)
  	    throw new \Exception('cannot serialize sub-minute timezone offset');
  	return sprintf('%s <%s> %d %+05d', $this->name, $this->email, $this->time, intval($this->offset/3600)*100 + intval($this->offset/60)%60);
  }
}