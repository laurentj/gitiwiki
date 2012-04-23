
# Design

For the moment, Gitiwiki doesn't really provide a design (but it's planned of course).
Since Gitiwiki is a Jelix application, to have your own design, simply
[follow instructions](http://docs.jelix.org/en/manual-1.3/themes)
to create a new theme, in the Jelix documentation.

Just copy the file `gitiwiki/modules/gitiwiki/templates/main.tpl` to 
`gitiwiki/var/themes/default/gitiwiki/main.tpl` and modify it. It must contains only
HTML content of the `<body>` element. If you want to add style sheets or javascript,
add these kind of tags at the begining of your templates:

```
    {meta_html css '/mystyles/my.css'}
    {meta_html js '/myscripts/fooscript.js'}
```

Store these CSS/js files into `gitiwiki/www/mystyles/my.css` and `gitiwiki/www/myscripts/fooscript.js`.

