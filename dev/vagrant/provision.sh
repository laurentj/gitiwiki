#!/bin/bash

ROOTDIR="/jelixapp"
VAGRANTDIR="$ROOTDIR/dev/vagrant"
APPNAME="gitiwiki"
APPDIR="$ROOTDIR/$APPNAME"
APPHOSTNAME="$APPNAME.local"
PHP_VERSION="7.2"
FPM_SOCK="php\\/php7.2-fpm.sock"


source $VAGRANTDIR/system.sh

initsystem

resetComposer $APPDIR


su vagrant -c $VAGRANTDIR/reset_app.sh

echo "Done."
