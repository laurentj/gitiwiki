<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE book PUBLIC "-//OASIS//DTD DocBook V5.0//EN"
                   "http://docbook.org/xml/5.0/dtd/docbook.dtd">
<book xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0">
<info>
    <title>{$book['title']|escxml}</title>
    <subtitle>{$book['subtitle']|escxml}</subtitle>
    <edition>{$edition|escxml}</edition>

    <releaseinfo>{$releaseInfo|escxml}</releaseinfo>

    {if count($book['authors'])}
    <authorgroup>
        {foreach $book['authors'] as $author}
        <author>
            <personname>
                <firstname>{$author[0]|escxml}</firstname><surname>{$author[1]|escxml}</surname>
            </personname>
        </author>
        {/foreach}
    </authorgroup>
    {else}
    <author>
        <personname>
            <firstname>{$book['authors'][0][0]|escxml}</firstname><surname>{$book['authors'][0][1]|escxml}</surname>
        </personname>
    </author>
    {/if}

    <pubdate>{$pubdate}</pubdate>
    <publisher><publishername>Jelix.org</publishername></publisher>
    <!-- <graphic /> -->
    <copyright>
        {foreach $book['copyright']['years'] as $y}
        <year>{$y|escxml}</year>
        {/foreach}
        {foreach $book['copyright']['holders'] as $h}
        <holder>{$h|escxml}</holder>
        {/foreach}
    </copyright>

    <legalnotice>{$legalnotice}</legalnotice>
</info>

{$content}

</book>
