#!/bin/bash

ROOTDIR="/jelixapp"
APPDIR="$ROOTDIR/"
VAGRANTDIR="$ROOTDIR/dev/vagrant"
APPNAME="gitiwiki"
#DISTFILESUFFIX="dist"
DISTFILESUFFIX="dev"
HOMEUSER=/home/vagrant

echo "Install configuration file"
# create  profiles.ini.php
cp -a $APPDIR/$APPNAME/var/config/profiles.ini.php.$DISTFILESUFFIX $APPDIR/$APPNAME/var/config/profiles.ini.php
cp -a $APPDIR/$APPNAME/var/config/localconfig.ini.php.$DISTFILESUFFIX $APPDIR/$APPNAME/var/config/localconfig.ini.php

if [ -f $APPDIR/$APPNAME/var/config/installer.ini.php ]; then
    rm $APPDIR/$APPNAME/var/config/installer.ini.php
fi


# create temp directory
echo "Prepare temp dir"
if [ ! -d $APPDIR/temp/$APPNAME ]; then
    mkdir $APPDIR/temp/$APPNAME
else
    rm -rf $APPDIR/temp/$APPNAME/*
fi

# set rights
#WRITABLEDIRS="$TESTAPPDIR/temp/$APPNAME/ $TESTAPPDIR/$APPNAME/var/log/ $TESTAPPDIR/$APPNAME/var/mails $TESTAPPDIR/$APPNAME/var/db"
#chown -R www-data:www-data $WRITABLEDIRS
#chmod -R g+w $WRITABLEDIRS


if [ ! -d $HOMEUSER/books ]; then
    mkdir $HOMEUSER/books
else
    rm -rf $HOMEUSER/books/*
fi

if [ ! -d $HOMEUSER/repositories ]; then
    mkdir $HOMEUSER/repositories
else
    rm -rf $HOMEUSER/repositories/*
fi

(cd $HOMEUSER/repositories && unzip $VAGRANTDIR/testrepos.zip)

(cd $APPDIR/$APPNAME && composer install)


php $APPDIR/$APPNAME/install/installer.php

