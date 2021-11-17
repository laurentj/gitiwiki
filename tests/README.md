
A Docker configuration is available to execute the web site on your computer, in
order to help you to develop and test it.

You have to install Docker on your computer.

build
-----
Before launching containers, you have to run these commands:

```
./run-docker build
```


launch
-------

To launch containers, just run `./run-docker`. Then you can launch some other
commands.

The first time you run the containers, you have to initialize databases and
application configuration by executing these commands:

```
./app-ctl reset
```

For some specific changes (database changes for example), you may want to execute
this command.

You can execute some commands into containers, by using this script:

```
./app-ctl <command>
```

Available commands:

* `reset`: to reinitialize the application (It reinstall the configuration files,
  remove temp files, create tables in databases, and it launches the jelix installer...) 
* `composer-update` and `composer-install`: to install update PHP packages 
* `clean-temp`: to delete temp files 
* `install`: to launch the jelix installer, if you changed the version of a module,
   or after you reset all things by hand.

browsing the application
------------------------

You can view the application at `http://localhost:8051` in your browser. 
You will see only the jelix.org web site.

To view other web sites, you should add this into your `/etc/hosts`:
`127.0.0.1 gitiwiki.local`

Then you could launch your browser at `http://gitiwiki:8051`.

You can change the port by setting the environment variable `APP_WEB_PORT`
before launching `run-docker`.

```
export APP_WEB_PORT=12345
./run-docker
```

Using a specific php version
-----------------------------

By default, PHP 7.4 is installed. If you want to use an other PHP version,
set the environment variable `PHP_VERSION`, and rebuild the containers:

```
export PHP_VERSION=7.3

./run-docker stop # if containers are running
./run-docker build
./run-docker
```
