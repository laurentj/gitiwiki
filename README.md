# GitiWiki

This web application reads and display content from a Git repository.
Features to create and modify wiki page with the browser will be provided later (Contributions are welcomed).

## Features

Main existing features:

- Support of the Dokuwiki syntax and extended tags (support of others wiki syntaxes is planned of course);
- user protocols for links: you can define "protocols" for urls to have aliases to real urls;
- store anything in your repository and where you want: images, pdf, xml files etc.. ;
- hidden files: files or directory begining by a dot are not accessible with a browser;
- support of several syntaxes or type file: assign a rendering engine to specific file extensions;
- multiviews: in an URL, the extension part of a filename is not required, Gitiwiki will find the right file.
  So later you can modify the extension (and so the wiki syntax for example) without modifying URL in other files;
- support of redirections: you rename a file or move your page, even to another site? Indicate it to Gitiwiki.
- support of books: define a page including a summary, the files list of your book, and Gitiwiki adds
navigation bar automatically on web pages.
- Docbook generation: a command is available to generate a docbook file from a "book". You can then generate a PDF
  file with external tools, from this docbook file.

A demo ? Go to the web site of [Jelix manuals](http://docs.jelix.org/en) to see Gitiwiki in action, and
on [this github repository](https://github.com/jelix/jelix-manual-en) to see its wiki content.

## Documentation

- [Installation](docs/installation.md)
- [Adding a repository](docs/repository.md) and what it should contain.
- [URL in wiki content](docs/url-support.md)
- [Wiki syntax](docs/syntax.md)
- [Writing books](docs/books)
- [Customization: design](docs/design)
