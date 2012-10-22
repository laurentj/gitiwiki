
{if $bookPageInfo}
<div id="book-page-header">
    <div class="book-title">
        <a href="{jurl 'gitiwiki~wiki:page', array('repository'=>$repository, 'page'=> $bookPageInfo['book'])}">{$bookPageInfo['bookInfo']['title']|eschtml}</a>
        <img src="/design/2011/icons/ui-menu-blue.png" alt="" />
        <div class="book-hierarchy">
            <ul>
            {assign $last = end($bookPageInfo['hierarchyPath'])}
            {assign $hasChildren = count($bookPageInfo['children'])}
            {foreach $bookPageInfo['hierarchyPath'] as $item}
                <li>^ <a href="{jurl 'gitiwiki~wiki:page', array('repository'=>$repository, 'page'=> $item[0])}">{$item[1]|eschtml}</a>
                {if $last == $item && !$hasChildren}
                    <ul>
                        {foreach $bookPageInfo['sisters'] as $sister}
                        <li {if $sister[0] == $bookPageInfo['path']}class="active"{/if}><a href="{jurl 'gitiwiki~wiki:page', array('repository'=>$repository, 'page'=> $sister[0])}">{$sister[1]|eschtml}</a></li>
                        {/foreach}
                    </ul>
                {/if}
                </li>
            {/foreach}
            {if $hasChildren}
                <li class="active">
                    <a href="{jurl 'gitiwiki~wiki:page', array('repository'=>$repository, 'page'=> $bookPageInfo['path'])}">{$bookPageInfo['title']|eschtml}</a>
                    <ul>
                        {foreach $bookPageInfo['children'] as $child}
                        <li><a href="{jurl 'gitiwiki~wiki:page', array('repository'=>$repository, 'page'=> $child[0])}">{$child[1]|eschtml}</a></li>
                        {/foreach}
                    </ul>
                </li>
            {elseif !count($bookPageInfo['hierarchyPath'])}
                <li class="active"><a href="{jurl 'gitiwiki~wiki:page', array('repository'=>$repository, 'page'=> $bookPageInfo['path'])}">{$bookPageInfo['title']|eschtml}</a></li>
                <li class="active">{@gitiwiki~wikipage.book.no.pages@}</li>
            {/if}
            </ul>
        </div>
    </div>
    <div class="book-edition">{$bookPageInfo['bookInfo']['edition']|eschtml}</div>
    <h1 class="book-current">{assign $type=$bookPageInfo['type']}{@gitiwiki~wikipage.book.section.$type@}: {$bookPageInfo['title']|eschtml}</h1>

    <table class="book-nav">
    <tr>
        <td class="book-nav-prev">{if $bookPageInfo['prev']}
            &laquo; <a href="{jurl 'gitiwiki~wiki:page', array('repository'=>$repository, 'page'=> $bookPageInfo['prev'][0])}"
                    title="{@gitiwiki~wikipage.book.previous.page@}">{$bookPageInfo['prev'][1]|eschtml}</a>{/if}
        </td>
        <td class="book-nav-up">{if $bookPageInfo['parent']}
            ^  <a href="{jurl 'gitiwiki~wiki:page', array('repository'=>$repository, 'page'=> $bookPageInfo['parent'][0])}"
                    title="{@gitiwiki~wikipage.book.parent.page@}">{$bookPageInfo['parent'][1]|eschtml}</a>{/if}
        </td>
        <td class="book-nav-next">{if $bookPageInfo['next']}
            <a href="{jurl 'gitiwiki~wiki:page', array('repository'=>$repository, 'page'=> $bookPageInfo['next'][0])}"
                    title="{@gitiwiki~wikipage.book.next.page@}">{$bookPageInfo['next'][1]|eschtml}</a> &raquo;{/if}
        </td>
    </tr></table>
    <div class="lang">{if isset($extraData['relative_page_lang'])} 
        {@gitiwiki~wikipage.switch.lang@}
        {foreach $extraData['relative_page_lang'] as $lang=>$page}
            <a href="{$page|eschtml}">{$lang}</a>
        {/foreach}
        {/if}
    </div>
    
</div>
{/if} {* end of bookPageInfo *}


{if isset($extraData['toc'])}
<div class="toc">
    <div class="tocheader toctoggle">
        <span class="toc_close"><span>−</span></span>
        {@gitiwiki~wikipage.toc.header@}
    </div>
    <div>
            {assign $currentLevel = 0}
            {foreach $extraData['toc'] as $toc}
                {if $toc[0] > $currentLevel}
                    {for ($i=$currentLevel; $i < $toc[0];$i++) }
                    <ul class="toc">
                        <li>
                    {/for}
                            <div><span><a href="#{$toc[1]}">{$toc[2]|eschtml}</a></span></div>
                    {assign $currentLevel = $toc[0]}
                {elseif $toc[0] < $currentLevel}
                    {for ($i=$currentLevel; $i > $toc[0];$i--) }
                        </li>
                    </ul>
                    {/for}
                    </li>
                    <li>
                        <div><span><a href="#{$toc[1]}">{$toc[2]|eschtml}</a></span></div>
                    {assign $currentLevel = $toc[0]}
                {else}
                    </li>
                    <li>
                        <div><span><a href="#{$toc[1]}">{$toc[2]|eschtml}</a></span></div>
                {/if}
            {/foreach}
            {while $currentLevel-- > 0}
                    </li>
                    </ul>
            {/while}
    </div>
</div>
{/if}



{$pageContent}

{if $bookPageInfo}
    {if $hasChildren}<hr /><ul>
        {foreach $bookPageInfo['children'] as $child}
        <li><a href="{jurl 'gitiwiki~wiki:page', array('repository'=>$repository, 'page'=> $child[0])}">{$child[1]|eschtml}</a></li>
        {/foreach}
    </ul>
    {/if}

<div id="book-page-footer">
    <table class="book-nav">
    <tr>
        <td class="book-nav-prev">{if $bookPageInfo['prev']}
            &laquo; <a href="{jurl 'gitiwiki~wiki:page', array('repository'=>$repository, 'page'=> $bookPageInfo['prev'][0])}"
                    title="{@gitiwiki~wikipage.book.previous.page@}">{$bookPageInfo['prev'][1]|eschtml}</a>{/if}
        </td>
        <td class="book-nav-up">{if $bookPageInfo['parent']}
            ^  <a href="{jurl 'gitiwiki~wiki:page', array('repository'=>$repository, 'page'=> $bookPageInfo['parent'][0])}"
                    title="{@gitiwiki~wikipage.book.parent.page@}">{$bookPageInfo['parent'][1]|eschtml}</a>{/if}
        </td>
        <td class="book-nav-next">{if $bookPageInfo['next']}
            <a href="{jurl 'gitiwiki~wiki:page', array('repository'=>$repository, 'page'=> $bookPageInfo['next'][0])}"
                    title="{@gitiwiki~wikipage.book.next.page@}">{$bookPageInfo['next'][1]|eschtml}</a> &raquo;{/if}
        </td>
    </tr></table>
    <div class="lang">{if isset($extraData['relative_page_lang'])}
        {@gitiwiki~wikipage.switch.lang@}
        {foreach $extraData['relative_page_lang'] as $lang=>$page}
            <a href="{$page|eschtml}">{$lang}</a>
        {/foreach}
        {/if}
    </div>
    <div class="book-legal-notice">{$bookPageInfo['bookInfo']['bookPageLegalNotice']}</div>
</div>
{/if}


{if $sourceEditURL || $sourceViewURL }
<div id="article-footer">
    <div id="info">
        {if $sourceEditURL}
        <a rel="nofollow" href="{$sourceEditURL}">{@gitiwiki~wikipage.edit.source.label@}</a><br/>
        {/if}
        {if $sourceViewURL}
        <a rel="nofollow" href="{$sourceViewURL}">{@gitiwiki~wikipage.view.source.label@}</a>
        {/if}
    </div>
</div>
{/if}
