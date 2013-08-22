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
* SHA regulates all SHA strings used in Glip
*/
class SHA
{
  protected
    $bin = null;

  function __construct($sha = null)
  {
    if (!is_null($sha))
    {
      if (is_numeric("0x".$sha) && strlen($sha) == 40)
      {
        // hex sha value
        $this->bin = (string)pack('H40', $sha);
      }
      else
      {
        $hex = bin2hex($sha);
        if (is_numeric("0x".$hex) && strlen($hex) == 40)
        {
          $this->bin = (string)$sha;
        }
        else
        {
          throw new \Exception("SHA accepts only a valid hex or bin SHA string as argument, supplied '".$sha."'");
        }
      }
    }
  }

  public function fromData($data)
  {
    $this->bin = (string)self::hash($data);
  }

  public function h($count = null)
  {
    return is_null($count) ? $this->hex() : substr($this->hex(),0,$count);
  }

  public function b64()
  {
    return base64_encode($this->bin());
  }

  public function b()
  {
    return $this->bin();
  }

  public function __toString()
  {
    return $this->bin();
  }

  public function bin()
  {
    if (is_null($this->bin))
    {
      throw new \Exception("The SHA hash is not computed");
    }
    return $this->bin;
  }

  public function hex()
  {
    return bin2hex($this->bin);
  }

  static public function hash($data, $raw = true)
  {
    return new SHA(hash('sha1',$data, $raw));
  }
}
