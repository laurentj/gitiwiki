# URL support

## Links

URL of links in the wiki content have a specific syntax (no matter of which wiki syntax you use).

You have four types of links:

1. Links relative to the current page: it does not start with a slash.
  ex: `[[foo/bar|my page]]`. If the current page is `http://localhost/index.php/wiki/mywiki/myarticle`,
  the link targets `http://localhost/index.php/wiki/mywiki/foo/bar`
2. Links relative to the wiki content: it should start with a slash.
  ex: `[[/foo/bar|my page]]`.  If the current page is `http://localhost/index.php/wiki/mywiki/dir/subdir/myarticle`,
  the link targets `http://localhost/index.php/wiki/mywiki/foo/bar`
3. Links relative to the domain name: it should start with two slashes.
  ex: `[[//foo/bar|my page]]`.  If the current page is `http://localhost/index.php/wiki/mywiki/dir/myarticle`,
  the link targets `http://localhost/foo/bar` (so it targets a page outside the wiki).
4. Absolute links: it should start with the `http://`. ex: `[[http://jelix.org|Jelix framework]]`. Use them
  to link to external web sites.

## URL: protocol aliases

In some documentation, you may have links to an other web site that are used intensively,
and only a part of the URL changes. Gitiwiki provides a way to use some kind of shorcuts,
avoiding to type the entire URL. It saves time to type them, it avoids errors, and it allows
to change quickly the URLs if the domain or some part of the URL change.

For example, you use many URL pointing to `https://github.com/laurentj/gitiwiki/issues/`
with a number at the end: `https://github.com/laurentj/gitiwiki/issues/1234`.

In the .config.ini file, in a `[protocol-aliases]` section, indicate an alias, for example 'issue',
and the real urls:

    [protocol-aliases]
    issue = "https://github.com/laurentj/gitiwiki/issues/%s"

Then, in your wiki page, instead of using the real url, use the alias, as an URI protocol:

    [[issue:1234|The issue 1234]]

The "%s" in the URL will be replaced by the value after the colon (the tag "%s" can be placed anywhere in the URL).

Of course, you can define several aliases in the `[protocol-aliases]` section.
