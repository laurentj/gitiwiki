{foreach $repos as $repo}
    <li{if isset($currentRepoName) && $repo['name']===$currentRepoName} class="selected"{/if}>
        <a href="{jurl 'gitiwiki~wiki:page', array('repository'=>$repo['name'], 'page' => '/')}">
            {$repo['label']|eschtml}
        </a>
    </li>
{/foreach}
