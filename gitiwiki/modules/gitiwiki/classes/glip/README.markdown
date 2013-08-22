## About ##

glip is a Git Library In PHP. It allows you to access bare git repositories
from PHP scripts, even without having git installed. This project is a fork of
Patrik Fimml's original one that can still be found a the project's homepage
located at <http://fimml.at/glip>.

This project also includes some changes made by the community at large.


## Usage ##

Include the autoload file, as shown below:

```php5
<?php

require_once __DIR__.'lib/autoload.php';

```

Create a new Git repository:

```php5
<?php

use Glip\Git

$repo = new Git('project/.git');

```


## Contributing ##

Please feel free to help make glip stable, fast and easy to use, based on the KISS principle.
I've created a public wiki on Github and activated the issue system. Feel free to send pull
requests.


## Project history ##

The original glip library was split off eWiki on May 31, 2009. An attempt was
made to preserve commit history by using git filter-branch; this also means that
commit messages before May 31, 2009 may seem weird (esp. wrt file names).

