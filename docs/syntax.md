# Wiki Syntax

The supported syntax is almost the same as the Dokuwiki syntax. More syntax (markdown etc)
will be supported later.

However there are some specific tags and syntax.

## LANG tag

This is a tag indicating a list of URL where the user can find the content of
the page translated in an other language.

The syntax is: `~~LANG:{lang-code}@{url},{lang-code}@{url},...~~`

- replace `{lang-code}` by a language code. For example: EN, FR, IT...
- replace `{url}` by an url. The syntax of the URL follow [the rules of any Gitiwiki URL](url-support.md).
  You can even use protocol aliases.
- You can define several language/url, separated by a coma.

Example with only one alternate language:

```
   ~~LANG:FR@/fr/manuel/chapitre/installation~~
```

Example with only two alternate languages, and the use of an alias:

```
   ~~LANG:FR@manfr:chapitre/installation, IT@/it/manuale/installazione/~~
```

Gitiwiki will then show this list of URL in the page.


## Special tag for technical keywords, instructions, filename, code etc..

To highlight some keyword, piece of code in a paragraph etc, you can use `@@here a keyword@@`.
It will generate an HTML `<code>` element.

You can indicate more semantics by adding a special letter followed by a `@`: `@@L@here a keyword@@`.
The generated code element will then have a specific class, or for some type, it could be
a more appropriate HTML element like `<kbd>`, `<var>` etc...

Here are the possible letters with their corresponding meanings:

- `A`: XML/HTML attribute
- `C`: class name
- `T`: constant
- `c`: command
- `E`: XML/HTML element
- `e`: environnement variable
- `F`: filename, directory
- `f`: function
- `I`: interface name
- `K`: key code
- `L`: literal
- `M`: method
- `P`: property
- `R`: return value
- `V`: varname

Example: `@@C@myClass@@`.

