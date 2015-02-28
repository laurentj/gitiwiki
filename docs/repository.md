# A repository for Gitiwiki

This chapter explain how to register a Git repository  and what it should contain.


## Create a repository

1. give a name to this repository (ex: mywiki)
2. create a new section in profiles.ini.php, with this name and a prefix
   "gtwrepo:" (ex: "gtwrepo:mywiki")
3. indicate the path to this repository in a "path" parameter. This should be a
   bare repository or the .git directory of your repository. You can use the alias "app:" as
   prefix to indicate a path relative to the gitiwiki directory. There is a gitiwiki/var/repositories/
   where you can put your repository. However it can be anywhere on your hard drive.
4. in the "branch" parameter, indicate the branch that gitiwiki should use
5. in the "basepath" parameter, indicate the directory inside the repository from which file
   will be retrieve. By default, its value is "/".
  

```ini
    [gtwrepo:mywiki]
    path=app:var/repositories/mywiki/.git
    ; is equals to 
    ; path=/home/myaccount/mysite/mywiki/gitiwiki/var/repositories/mywiki/.git
    branch=master
    title= A title
    basepath="/"
```

You can also indicate a title for the list of wikis.

## Content of a repository

Your repository can contain many type of files:

1. wiki pages, with the `.wiki` or `.gtw` extension. The syntax is the dokuwiki syntax.
   Later, gitiwiki will supports markdown and other syntaxes etc. 
2. hidden files (see below)
3. a .config.ini file at the root of the repository, containing some
   configuration values for gitiwiki.
4. meta files: file that can contain meta information about a file (useful for images, redirections...)
5. and any other files. Gitiwiki simply send them to the browser, with the right mime-type if it knows it.

## The .config.ini file

This is a file (in the ini format) that can contain some parameters for Gitiwiki:

- "multiviews" to indicate multiview extensions (see below)
- global redirections informations (see below)
- and protocol aliases, [see urls](url-support.md)

## Multiviews

Notice that you don't have to indicate the extension for wiki page, in URLs. For example,
if you target article.wiki, you can indicate simply `article`.

Gitiwiki will then try different extensions, indicated into a `.config.ini` file at
the root of your repository.

Here is an example of `.config.ini`

```ini
    multiviews=".wiki, .html, .txt"
```

With this configuration, when the url `myarticle` is given, Gitiwiki try to load first
the file myarticle.wiki. If it doesn't exist, it tries then to load myarticle.html,
and then myarticle.txt. If none of file are existing, a 404 error is returned.


## Home page and directory indexes

The home page should be stored into a file named "index" with one of the extension
indicated into the multiviews parameter. For example: index.wiki.

When the url correspond to a directory, Gitiwiki tries to load first a file with the same
name + a prefix indicated into the multiviews option, in the parent directory.

For exemple, if the url is `dir/subdir/`, it searches the file `dir/subdir.wiki` (and
then dir/subdir.html etc).

If this file doesn't exist, it searches an `index.wiki` (or `index.html` etc) file
inside the directory, so `dir/subdir/index.wiki`, then `dir/subdir/index.html` and so on.

## Redirections

Gitiwiki supports redirection. When you rename a file or move it into an other directory,
it is a good practice to do a HTTP redirection when the browser tries to load the old file.
It is better for search engines like Google for example.

When a file does not exist, Gitiwiki doesn't search into the git repository if it was
moved or renamed, because Git does not have a quick way to know it, and so it could be take
times and ressources to search. When there are several merge, it can be very difficult
and it could even fails. If the file have been moved and modified at the same time,
it is impossible to find this rename.

So you have to indicate these changes into the `.config.ini` file, in the `redirection`
parameter. In the future, when editing/renaming a file will be possible with the browser,
this parameter will be filled automatically of course.

Here an example (since there are several redirection parameters, you should use `[]`):

```ini
    redirection[] = "^manual2\.old/(.*)$ -> manual2/%s"
    redirection[] = "^manual2/unexistant -> manual2/article2"
    redirection[] = "^manual/moved-page-outside.txt -> //new-page.txt"
    redirection[] = "^something/elsewhere.txt -> http://jelix.org/new-page.txt"
```

A redirection information begins with a regular expression that matches the old url,
followed by an arrow "->", followed by the new url (which will be processed by sprintf
to do replacements). Not that new urls used the same rules as urls in links (see above).

The first redirection, redirects all URLS starting with "manual2.old/" to URLS starting
with "manual2/". It means: all files into the manual2.old directory were moved into the
manual2 directory.

The second redirection, redirects an URL of a simple file, to a new URL. It means:
manual2/unexistant has been renamed to manual2/article2

The third redirection means that the file moved-page-outside.txt of the wiki
have been moved to new-page.txt which is now outside the wiki, but it is still
in the same web site.

The  fourth redirection means that elsewhere.txt have been moved to an other
web site.

You can also indicate redirections in meta files. See below.


## Hidden files

All files that have names starting with a dot, will be ignored. All files inside a
directory that have a name starting with a dot, will be ignored two.

'ignored' means that a user cannot access to it, a simple "404" error will be returned.


## Meta files

Meta files are files containing extra-information about a specific file. A meta
file is stored in a `.meta` directory in the same directory of the target file.
its name is the same name of the target file + '.ini'.

For example, the meta file of mydir/article.wiki is mydir/.meta/article.wiki.ini.

You can have meta file for any file: images, pdf, text etc.

A meta file is an "ini" file, and could contain a title, a description, some keywords
etc..

For the moment, only a "redirection" parameter is supported. It is an alternative
way to the .config.ini file, to indicate a redirection. It means too that you
can have a meta file for an unexistant file.

For example, you moved a file mydir/article.wiki to otherdir/article.wiki, you
can have a mydir/.meta/article.wiki.ini file indicating:

```ini
     redirection="/otherdir/article.wiki"
```
