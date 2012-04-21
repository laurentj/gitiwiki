# Books

Gitiwiki supports the concept of books. You can link multiple pages together to form a book,
even they are not in the same directories.

To link pages, you only have to create a specific page, that we call the "summary", in which
you will list, in the right order, all pages, with their wiki URL and their title.

You can categorize pages by part, chapter and sections.

In this same "summary" page, you can also define:

- book informations, like the title of the book, the author, the copyright etc..
- a legal notice for the entire book
- a legal notice to display on each page

When the summary page is defined, a navigation bar appears on all web pages of the book.
For the summary page, Gitiwiki generates also a specific web page.

Note that you can have several books in the same Git repository.

## Defining the "summary" page

This page should contain only some specific tags in which you will define book informations.

### General book informations

Here is the first tag you should right in the file: `<bookinfo>`.
It contains them some parameters with their values (in a kind of ini format).

    <bookinfo>
    title        = Here the title of the book
    subtitle     = A subtitle
    title_short  = Short title for web pages
    edition      = Edition name
    author       = Author firstname | author last name
    author       = Other author firstname | Other author last name
    copyright_years = 2010-2012
    copyright_holder = A name of a copyright holder 
    copyright_holder = A name of a copyright holder 
    </bookinfo>

As you can see, you can have several authors and copyright holders.


### Table of content

This is the most important part: the list of pages that form the book.
This list is inside a `<bookcontents>` tag. Each item of a list begins with a '-'.
The indentation is important: it represents the hierarchy of pages.

After the "-", an item contain the type of the page, followed by a link in wiki syntax:
this is the URL and the title of the page. Possible type of page are: "part", "chapter", "section".

Here is an example:

    <bookcontents>
    - part: [[introduction|Introduction]]
      - chapter: [[new-features|New features]]
      - chapter: [[installation/migrate|Migrating from Jelix 1.2 to Jelix 1.3]]
    
    - part: [[getting-started|Getting started]]
      - chapter: [[installation/requirements|Install requirements]]
      - chapter: [[installation/jelix|Install Jelix]]
      - chapter: [[jelix-scripts|Using jelix scripts]]
      - chapter: [[create-application|Creating an application]]
      - chapter: [[server-configuration|Server configuration]]
    
    - part: [[main-concepts|Main concepts]]
       - chapter: [[principles|Core workflow]]
         - section: [[selectors|Selectors]]
         - section: [[requests|Request object and entry points]]
         - section: [[coordinator|Coordinator]]
         - section: [[jresponse|Response object]]
         - section: [[call-action|Calling actions]]
       - chapter: [[config|Configuration files]]
       - chapter: [[modules|Developing a module]]
         - section: [[creating-a-module|Creating a module]]
         - section: [[installing-a-module|Installing a module]]
         - section: [[controllers|Developing a controller]]
         - section: [[controllers/retrieving-http-parameters|Retrieving HTTP parameters]]
         - section: [[controllers/crud|Using the CRUD controller]]
         - section: [[restfull|Developing a REST controller]]
       - chapter: [[responses|Responses: generating content]]
         - section: [[responsehtml|Generating HTML content]]
         - section: [[responsetext|Generating plain text]]
         - section: [[responsexml|Generating XML content]]
   
    </bookcontents>

### Legal notice

You can add a legal notice. There are two kind of notice. The first, in a `<booklegalnotice>` tag,
is displayed on the summary page, and the second, `<bookpagelegalnotice>`,
which is supposed shorter, is displayed on all pages.

Examples of legal notice:

    <booklegalnotice>
    This manual is distributed under the terms of [[http://creativecommons.org/licenses/by-nc-sa/3.0/deed.en|licence Creative Commons by-nc-sa 3.0]]. Therefore you're allowed to copy, modify and distribute and transmit it publicly  under the following conditions: 
      * **Attribution**. You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).
      * **Noncommercial**. You may not use this work for commercial purposes.
      * **Share Alike**.If you alter, transform, or build upon this work, you may distribute the resulting work only under the same or similar license to this one.
    </booklegalnotice>
    
    <bookpagelegalnotice>
    This manual is distributed under the terms of [[http://creativecommons.org/licenses/by-nc-sa/3.0/deed.en|licence Creative Commons by-nc-sa 3.0]]. Therefore you're allowed to copy, modify and distribute and transmit it publicly  under the following conditions: **Attribution**, **Noncommercial**, **Share Alike**.
    </bookpagelegalnotice>

## Book initialization

To display navigation bar on all pages of the book, Gitiwiki needs informations
stored into the summary page. And to avoid to parse the summary page each time
it wants to display this navigation bar, it needs to store these informations
in a optimized way. So Gitiwiki needs to read the summary page a first time
in order to generate these informations.

Two ways to do it:

1. when you publish for the first time your book, just browse the summary page
2. or call a specific script in the command line (in a shell). see below.

If the book have been updated into the repository, you can repeat this process.
However, if you use the first way, cached informations should be deleted,
and you need to generate book informations again. Books informations
are stored into gitiwiki/var/books/.

Prefer to generate informations with the script. It's better. You can call
it into a "cron" script (a shell script that is called periodically by your system),
which will update the repository and then regenerate the book informations.

To generate with the command line, open a shell, go into the gitiwiki/scripts/
directory, and type:

   php manage.php gitiwiki~wiki:generateBook {repository_name} {summary_page}

Replace `{repository_name}` by the name you give to the repository in the configuration. And
replace `{summary_page}` by the path of the summary page into the repository.

For example:

   php manage.php gitiwiki~wiki:generateBook mywiki index




