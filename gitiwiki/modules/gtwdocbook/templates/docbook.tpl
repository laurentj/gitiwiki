<!DOCTYPE book PUBLIC "-//OASIS//DTD DocBook XML V4.3//EN" "http://www.oasis-open.org/docbook/xml/4.3/docbookx.dtd">
<book>
<bookinfo>
    <title>{$book['title']|escxml}</title>
    <subtitle>{$book['subtitle']|escxml}</subtitle>
    <edition>{$edition|escxml}</edition>

    <releaseinfo>{$releaseInfo|escxml}</releaseinfo>

    {if count($book['authors'])}
    <authorgroup>
        {foreach $book['authors'] as $author}
        <author><firstname>{$author[0]|escxml}</firstname><surname>{$author[1]|escxml}</surname></author>
        {/foreach}
    </authorgroup>
    {else}
    <author><firstname>{$book['authors'][0][0]|escxml}</firstname><surname>{$book['authors'][0][1]|escxml}</surname></author>
    {/if}

    <pubdate>{$pubdate}</pubdate>
    <publisher><publishername>Jelix.org</publishername></publisher>
    <!-- <graphic /> -->
    <copyright>
        {foreach $book['copyright_years'] as $y}
        <year>{$y|escxml}</year>
        {/foreach}
        {foreach $book['copyright_holders'] as $h}
        <holder>{$h|escxml}</holder>
        {/foreach}
    </copyright>

    <legalnotice>{$legalnotice}</legalnotice>
</bookinfo>

{$content}

</book>
