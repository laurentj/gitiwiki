
# GitiWiki

This will be a wiki system, storing page into a Git repository.

For the moment, it only read content from a repository.

No release yet. Work in progress.


## installation

Gitiwiki is a PHP application, using the framework [Jelix](http://jelix.org).

WARNING: The installation is probably a bit complicated for a non-developer, but don't worry!
For the moment, GitiWiki targets only developers who want to contribute to the project.
The first release will contain of course a wizard which will simplify everything!

Here are steps:

1. checkout the source code with `git clone`
2. put "write" rights for your web server on these directories
  - temp/gitiwiki
  - gitiwiki/var/log/
3. configure a virtual host with `gitiwiki/www` as a document root
4. configure your virtual host with an alias `/jelix/` to `lib/jelix-www`
5. in gitiwiki/var/config, copy defaultconfig.ini.php.dist to defaultconfig.ini.php and profiles.ini.php.dist to profiles.ini.php
6. open a console and go into gitiwiki/install, and launch `php installer.php`

In next instructions, we assume that the domain name of your virtual host is gitiwiki.local (you can configure it in /etc/hosts/)

Gitiwiki is now almost ready. It needs now a Git repository.

Let's use the repository used for tests :

1. in the console, go into gitiwiki/var/repositories
2. unzip testrepos.zip. You should have a "default" directory.
3. open gitiwiki/var/config/defaultconfig.ini.php, and go at the end of file
4. in the gwrepo_default section, set the `path` property, by indicating the full path to gitiwiki/var/repositories/default/

And that's all.

If you type http://gitiwiki.local/index.php/wiki/default/, you should see a page with a "hello world" ;-)


## Adding a repository

TODO

## Content of a repository

TODO
