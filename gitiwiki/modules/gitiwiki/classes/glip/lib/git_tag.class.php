<?php
/*
 * Copyright (C) 2008, 2009 Patrik Fimml
 * Copyright (C) 2010 Michael Vigovsky
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
require_once('git_object.class.php');
require_once('git_commit_stamp.class.php');

class GitTag extends GitObject
{
    /**
     * @brief (string) The object referenced by this tag, as binary sha1
     * string.
     */
    public $object = null;

    /**
     * @brief (int) Type of referenced object
     */
    public $objtype = null;

    /**
     * @brief (string) Tag name
     * string.
     */
    public $tag = null;

    /**
     * @brief (GitCommitStamp) The tagger of this tag.
     */
    public $tagger = null;

    /**
     * @brief (string) Tag summary, i.e. the first line of the tag message.
     */
    public $summary;

    /**
     * @brief (string) Everything after the first line of the tag message.
     */
    public $detail;

    public function __construct($repo)
    {
	parent::__construct($repo, Git::OBJ_TAG);
    }

    public function _unserialize($data)
    {
	$lines = explode("\n", $data);
	unset($data);
	$meta = array();
	while (($line = array_shift($lines)) != '')
	{
	    $parts = explode(' ', $line, 2);
	    if (!isset($meta[$parts[0]]))
		$meta[$parts[0]] = array($parts[1]);
	    else
		$meta[$parts[0]][] = $parts[1];
	}

	if (isset($meta['object'][0]))
	{
	    $this->object = sha1_bin($meta['object'][0]);
	    $this->objtype = Git::getTypeID($meta['type'][0]);
	}
	if (isset($meta['tag'][0])) $this->tag = $meta['tag'][0];

	if (isset($meta['tagger'][0]))
	{
	    $this->tagger = new GitCommitStamp;
	    $this->tagger->unserialize($meta['tagger'][0]);
	}

	$this->summary = array_shift($lines);
	$this->detail = implode("\n", $lines);

    }

    public function _serialize()
    {
	$s = '';
	if ($this->object !== null)
	{
	    $s .= sprintf("object %s\n", sha1_hex($this->object));
	    $s .= sprintf("type %s\n", Git::getTypeName($this->objtype));
	}
	if ($this->tag    !== null) $s .= sprintf("tag %s\n", $this->tagger->serialize());
	if ($this->tagger !== null) $s .= sprintf("tagger %s\n", $this->tagger->serialize());
	$s .= "\n".$this->summary."\n".$this->detail;
	return $s;
    }

    /**
     * @brief Get the object referenced by this tag.
     *
     * @returns The GitObject referenced by this tag.
     */
    public function getObject()
    {
        return $this->repo->getObject($this->object);
    }
}
