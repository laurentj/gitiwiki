{meta_htmlmodule css 'sphinxsearch', 'sphinxsearch.css'}

<div class="searchResults">
    <h2 class="searchResultsHeader">
    {jlocale 'sphinxsearch~search.results.searchString', array($string)}&nbsp;: 
    {if count($results) < 1}
        {@sphinxsearch~search.results.noResults@} 
        </h2>
    {else}
        {jlocale 'sphinxsearch~search.results.pageCount', array($page, $maxPage)} 
        ({jlocale 'sphinxsearch~search.results.totalCount', array($total)})
        </h2>

        <ul class="searchResultsList">
        {foreach $results as $infos}
            <li class="searchResultsItem">
                <a class="searchResultsTitle" href="{$infos['url']}">{$infos['title']}</a>
                <span class="searchResultsUrl">{$infos['url']}</span>
                <p class="searchResultsExtract">{$infos['extract']}</p>
            </li>
        {/foreach}
        </ul>

        <div class="searchResultsNav">
            {if $page > 1}
                <a class="searchResultsNavPrev" href="{jurl $searchSel, array_merge($searchParams, array('page'=>$page-1))}">{@sphinxsearch~search.results.navPrev@}</a>
            {/if}

            {for $i=1; $i<$maxPage+1; $i++}
                {if $i == $page}
                    <span class="searchResultsNavCurr">{=$i}</span>
                {else}
                    <a class="searchResultsNavLink" href="{jurl $searchSel, array_merge($searchParams, array('page'=>$i))}">{=$i}</a>
                {/if}
            {/for}

            {if $page < $maxPage}
                <a class="searchResultsNavNext" href="{jurl $searchSel, array_merge($searchParams, array('page'=>$page+1))}">{@sphinxsearch~search.results.navNext@}</a>
            {/if}
        </div>
    {/if}
</div>
