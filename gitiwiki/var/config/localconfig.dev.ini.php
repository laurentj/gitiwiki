;<?php die(''); ?>
;for security reasons , don't remove or modify the first line
;this file doesn't list all possible properties. See lib/jelix/core/defaultconfig.ini.php for that

[jResponseHtml]
; list of active plugins for jResponseHtml
plugins=debugbar

[mailer]
webmasterEmail="root@localhost"
webmasterName=
mailerType=file

[logger]
; list of loggers for each categories of log messages
; available loggers : file, syslog, firebug, mail, memory. see plugins for others

; _all category is the category containing loggers executed for any categories
_all=memory

; default category is the category used when a given category is not declared here
default=file
error=file
warning=file
notice=file
deprecated=
strict=
debug=file
sql=
soap=


[gitiwiki]
booksPath="/srv/gitiwiki/tests/data/books"

[gitiwikiGenerators]
gtw="gitiwiki~gtwWikiRenderer,gitiwiki_to_xhtml"
wiki="gitiwiki~gtwWikiRenderer,gitiwiki_to_xhtml"
