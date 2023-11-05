#!/bin/bash
ROOTDIR="/srv/gitiwiki"
TEMPDIR="$ROOTDIR/temp/"
APPDIR="$ROOTDIR/gitiwiki/"
APPTEMPDIR="$TEMPDIR/gitiwiki"

DATADIR="$ROOTDIR/tests/data"


APP_USER=userphp
APP_GROUP=groupphp

COMMAND="$1"
shift

if [ "$COMMAND" == "" ]; then
    echo "Error: command is missing"
    exit 1;
fi

function resetJelixTemp() {
    echo "--- Reset temp files"
    local appdir="$1"
    local tempdir="$2"
    local apptempdir="$3"
    if [ ! -d $appdir/var/log ]; then
        mkdir $appdir/var/log
        chown $APP_USER:$APP_GROUP $appdir/var/log
    fi
    if [ ! -d $apptempdir/ ]; then
        mkdir -p $apptempdir/
        chown $APP_USER:$APP_GROUP $apptempdir
    else
        rm -rf $apptempdir/*
    fi
    touch $tempdir/.dummy
    chown $APP_USER:$APP_GROUP $tempdir
    chown $APP_USER:$APP_GROUP $tempdir/.dummy
    chmod ug+w $tempdir $apptempdir
}

function resetApp() {
    echo "--- Reset configuration files in $1"
    local appdir="$1"
    local apptempdir="$2"
    if [ -f $appdir/var/config/CLOSED ]; then
        rm -f $appdir/var/config/CLOSED
    fi

    for vardir in log mails uploads; do
      if [ ! -d $appdir/var/$vardir ]; then
          mkdir $appdir/var/$vardir
      else
          rm -rf $appdir/var/$vardir/*
      fi
      touch $appdir/var/$vardir/.dummy
    done

    if [ -f $appdir/var/config/profiles.dev.ini.php ]; then
        cp $appdir/var/config/profiles.dev.ini.php $appdir/var/config/profiles.ini.php
    fi
    if [ -f $appdir/var/config/localconfig.dev.ini.php ]; then
        cp $appdir/var/config/localconfig.dev.ini.php $appdir/var/config/localconfig.ini.php
    fi
    chown -R $APP_USER:$APP_GROUP $appdir/var/config/profiles.ini.php $appdir/var/config/localconfig.ini.php

    if [ -f $appdir/var/config/installer.ini.php ]; then
        rm -f $appdir/var/config/installer.ini.php
    fi
    if [ -f $appdir/var/config/liveconfig.ini.php ]; then
        rm -f $appdir/var/config/liveconfig.ini.php
    fi

    if [ -f $appdir/var/config/localframework.ini.php ]; then
        rm -f $appdir/var/config/localframework.ini.php
    fi

    setRights $appdir $apptempdir
    launchInstaller $appdir
}

function resetMysql() {
    echo "--- Reset mysql database for database $1"
    local base="$1"
    local login="$2"
    local pass="$3"

    mysql -h mysql -u $login -p$pass -Nse 'show tables' $base | while read table; do mysql -h mysql -u $login -p$pass -e "drop table $table" $base; done
}

function launchInstaller() {
    echo "--- Launch app installer in $1"
    local appdir="$1"
    su $APP_USER -c "php $appdir/install/configurator.php --no-interaction --verbose"
    su $APP_USER -c "php $appdir/install/installer.php --verbose"
}

function setRights() {
    echo "--- Set rights on directories and files in $1 and $2"
    local appdir="$1"
    local apptempdir="$2"
    USER="$3"
    GROUP="$4"

    if [ "$USER" = "" ]; then
        USER="$APP_USER"
    fi

    if [ "$GROUP" = "" ]; then
        GROUP="$APP_GROUP"
    fi

    DIRS="$appdir/var/config $appdir/var/db $appdir/var/log $appdir/var/mails $appdir/var/uploads $apptempdir $apptempdir/../"
    for VARDIR in $DIRS; do
      if [ ! -d $VARDIR ]; then
        mkdir -p $VARDIR
      fi
      chown -R $USER:$GROUP $VARDIR
      chmod -R ug+w $VARDIR
    done

}

function composerInstall() {
    echo "--- Install Composer packages"
    local appdir="$1"
    if [ -f $appdir/composer.json ]; then
      if [ -f $appdir/composer.lock ]; then
          rm -f $appdir/composer.lock
      fi
      composer install --prefer-dist --no-progress --no-ansi --no-interaction --working-dir=$appdir
      chown -R $APP_USER:$APP_GROUP $appdir/vendor $appdir/composer.lock
    fi
}

function composerUpdate() {
    echo "--- Update Composer packages"
    local appdir="$1"
    if [ -f $appdir/composer.json ]; then
      if [ -f $appdir/composer.lock ]; then
          rm -f $appdir/composer.lock
      fi
      composer update --prefer-dist --no-progress --no-ansi --no-interaction --working-dir=$appdir
      chown -R $APP_USER:$APP_GROUP $appdir/vendor $appdir/composer.lock
    fi
}

function setupTestData() {


  if [ ! -d $DATADIR/books ]; then
      mkdir $DATADIR/books
  else
      rm -rf $DATADIR/books/*
  fi

  if [ ! -d $DATADIR/repositories ]; then
      mkdir $DATADIR/repositories
  else
      rm -rf $DATADIR/repositories/*
  fi

  (cd $DATADIR/repositories && unzip $DATADIR/testrepos.zip)

}


function launch() {
    echo "--- Launch setup in $1"
    local appdir="$1"
    local apptempdir="$2"
    if [ ! -f $appdir/var/config/profiles.ini.php ]; then
        cp $appdir/var/config/profiles.dev.ini.php.dist $appdir/var/config/profiles.ini.php
    fi
    if [ ! -f $appdir/var/config/localconfig.ini.php ]; then
        cp $appdir/var/config/localconfig.dev.ini.php.dist $appdir/var/config/localconfig.ini.php
    fi
    chown -R $APP_USER:$APP_GROUP $appdir/var/config/profiles.ini.php $APPDIR/var/config/localconfig.ini.php

    if [ ! -d $appdir/vendor ]; then
      composerInstall
    fi

    resetApp $appdir $apptempdir
    launchInstaller $appdir
    setRights $appdir $apptempdir
}


case $COMMAND in
    clean_tmp)
        resetJelixTemp $APPDIR $TEMPDIR $APPTEMPDIR
        ;;
    reset)
        resetJelixTemp $APPDIR $TEMPDIR $APPTEMPDIR
        composerInstall $APPDIR
        setupTestData
        resetApp $APPDIR $APPTEMPDIR
        ;;
    install)
        launchInstaller $APPDIR
        ;;
    rights)
        setRights $APPDIR $APPTEMPDIR
        ;;
    composer_install)
        composerInstall $APPDIR
        ;;
    composer_update)
        composerUpdate $APPDIR
        ;;
    unit-tests)
        UTCMD="cd $APPDIR/tests/ && php runtests.php --all-modules"
        su $APP_USER -c "$UTCMD"
        ;;
    *)
        echo "wrong command"
        exit 2
        ;;
esac

