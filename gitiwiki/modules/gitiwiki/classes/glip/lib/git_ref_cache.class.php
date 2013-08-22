<?php
/*
 * Copyright (C) 2009 Michael Vigovsky
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

require_once('git.class.php');

class GitRefCache
{
    public $refs = array(); // array of refs that already resolved to specific sha1 hash

    public function __construct($dir)
    {
        $this->dir = $dir;
        $this->srefs = array(); // array of symbolic refs that are waiting to be resolved
        $this->brefs = array(); // array of bad refs that cannot be resolved

        $this->read_packedrefs();
        $this->read_refs("refs");
        $this->resolve_refs();
    }

    /**
     * @brief reads all packed references
     */
    protected function read_packedrefs()
    {
        $path = sprintf('%s/packed-refs', $this->dir);
        if (!file_exists($path)) return;
        $f = fopen($path, 'r');
        flock($f, LOCK_SH);
        while (($line = fgets($f)) !== false)
        {
            $line = rtrim(ltrim($line),"\r\n");
            if ($line{0} == '#' || $line{0} == '^') continue;
            if (strpos($line,' ') != 40) continue;
            $this->refs[substr($line,41)] = sha1_bin(substr($line,0,40));
        }
        fclose($f);
    }

    /**
     * @brief reads all references in specific directory
     * @param $basedir (string) directory to be searched
     */
    protected function read_refs($basedir)
    {
        $path = sprintf("%s/%s", $this->dir, $basedir);
         // symbolic links in refs directory is strange thing, refuse to traverse it
        if (is_link($path)) return;
        $d = opendir($path);
        if (!$d) return;
        while (($dir = readdir($d)) !== false)
        {
            if ($dir{0}=='.') continue;
            if (is_dir(sprintf("%s/%s/%s", $this->dir, $basedir, $dir)))
                $this->read_refs(sprintf("%s/%s", $basedir, $dir));
            else
                $this->read_ref(sprintf("%s/%s", $basedir, $dir));
        }
    }

    /**
     * @brief reads a single ref from a file
     * @param $ref (string) reference to be loaded
     * @returns (integer)
     * 0 if reference is bad
     * 1 if reference sha1 is loaded
     * 2 if reference is need to be resolved
     */
    protected function read_ref($ref)
    {
        // safety: refuse to resolve reference that points to parent directory
        if (substr($ref,3)=='../' || strpos($ref,'/../'))
        {
            $this->brefs[$ref] = true;
            return 0;
        }
        $data = trim(@file_get_contents(sprintf("%s/%s", $this->dir, $ref)));
        if (substr($data,0,4) == "ref:")
        {
            $this->srefs[$ref] = trim(substr($data,4));
            return 2;
        }
        if (is_valid_sha1($data))
        {
            $this->refs[$ref] = sha1_bin($data);
            return 1;
        }
        $this->brefs[$ref] = true; //mark reference as bad
        return 0;
    }

    /**
     * @brief resolve all symbolic refereces
     */
    protected function resolve_refs()
    {
        do
        {
            $flag = false;
            $unresolved = $this->srefs;
            foreach ($unresolved as $key => $value)
            {
                if (isset($this->brefs[$value])) //reference is just bad
                {
                    $this->brefs[$key] = true;
                    unset($this->srefs[$key]);
                } elseif (isset($this->refs[$value])) //reference can be resolved now
                {
                    $this->refs[$key] = $this->refs[$value];
                    unset($this->srefs[$key]);
                    $flag=true;
                } elseif (!isset($this->srefs[$value]))
                { // reference is unknown. try to load it
                    if ($this->read_ref($value)==2) $flag = true;
                }
            }
        } while ($flag && count($this->srefs) > 0);

        //mark all refs that are still unresolved as bad
        foreach ($this->srefs as $key => $value)
        {
            $this->brefs[$key] = true;
        }
        $this->srefs = array();
    }

    /**
     * @brief gets a single ref. If it's not cached, load it
     *
     * @returns (string) binary sha1 hash or false if ref is bad
     */
    public function getRef($ref)
    {
        if (isset($this->refs[$ref])) return $this->refs[$ref];
        if ($this->read_ref($ref) == 2) $this->resolve_refs();
        if (isset($this->refs[$ref])) return $this->refs[$ref];
        return false;
    }
}

