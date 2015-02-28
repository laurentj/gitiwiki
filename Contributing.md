Contributing.md
===============


Filling issues and feature requests
-----------------------------------

Go into our github account https://github.com/laurentj/gitiwiki to fill issues or to do pull request.


Installing a development environment
------------------------------------

The prefered way is to use the Vagrant configuration you have in the dev/ folder.
Vagrant is a tool which allow to create easily a Virtual Machine.

Install Vagrant, go into the dev/ folder and type ```vagrant up```. A linux server
is then configured in a virtual machine and Gitiwiki is installed. The first time
you do ```vagrant up```, it may take few minutes.

You can then go on http://localhost:8051 to see Gitiwiki in action.

If you don't want to use vagrant, install Gitiwiki on a web server. See the provision_app.sh script
in dev/vagrant/ folder to see what you should do to finish the configuration.


Launching test
--------------

Go into the VM, then into the tests directory of gitiwiki and run runtests.php

```
vagrant ssh
cd /jelixapp/gitiwiki/tests
php runtests.php --all-modules
```
