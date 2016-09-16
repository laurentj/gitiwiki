#!/bin/bash

ROOTDIR="/jelixapp"
APPDIR="$ROOTDIR/"
VAGRANTDIR="$ROOTDIR/dev/vagrant"
APPNAME="gitiwiki"
HOSTNAME="$APPNAME.local"
APACHEPORT="8051"

# create hostname
HOST=`grep "$HOSTNAME" /etc/hosts`
if [ "$HOST" == "" ]; then
    echo "127.0.0.1 $HOSTNAME" >> /etc/hosts
fi
hostname $HOSTNAME
echo "$HOSTNAME" > /etc/hostname

# local time
echo "Europe/Paris" > /etc/timezone
cp /usr/share/zoneinfo/Europe/Paris /etc/localtime
locale-gen fr_FR.UTF-8
update-locale LC_ALL=fr_FR.UTF-8

# activate multiverse repository to have libapache2-mod-fastcgi
sed -i "/^# deb.*multiverse/ s/^# //" /etc/apt/sources.list

# install all packages
apt-get update
apt-get -y upgrade
apt-get -y install debconf-utils
export DEBIAN_FRONTEND=noninteractive
#echo "mysql-server-5.5 mysql-server/root_password password jelix" | debconf-set-selections
#echo "mysql-server-5.5 mysql-server/root_password_again password jelix" | debconf-set-selections
#echo "phpmyadmin phpmyadmin/dbconfig-install boolean true" | debconf-set-selections
#echo "phpmyadmin phpmyadmin/reconfigure-webserver multiselect apache2" | debconf-set-selections
#echo "phpmyadmin phpmyadmin/mysql/admin-pass password jelix" | debconf-set-selections
#echo "phpmyadmin phpmyadmin/app-password-confirm password jelix" | debconf-set-selections
#echo "phpmyadmin phpmyadmin/mysql/app-pass password jelix" | debconf-set-selections
#echo "phpmyadmin phpmyadmin/password-confirm password jelix" | debconf-set-selections
#echo "phpmyadmin phpmyadmin/setup-password password jelix" | debconf-set-selections

apt-get -y install apache2 libapache2-mod-fastcgi apache2-mpm-worker php5-fpm php5-cli php5-curl php5-intl php5-mcrypt php5-mysql php5-sqlite
#apt-get -y install mysql-server mysql-client phpmyadmin
apt-get -y install git unzip

# create a database into mysql + users
#if [ ! -d /var/lib/mysql/docsjelix/ ]; then
#    echo "setting mysql database.."
#    mysql -u root -pjelix -e "CREATE DATABASE IF NOT EXISTS docsjelix CHARACTER SET utf8;CREATE USER docsuser IDENTIFIED BY 'jelix';GRANT ALL ON docsjelix.* TO docsuser;FLUSH PRIVILEGES;"
#fi


# install default vhost for apache
cp $VAGRANTDIR/appvhost.conf /etc/apache2/sites-available/

if [ ! -f "/etc/apache2/sites-enabled/010-appvhost.conf" ]; then
    ln -s /etc/apache2/sites-available/appvhost.conf /etc/apache2/sites-enabled/010-appvhost.conf
fi
if [ -f "/etc/apache2/sites-enabled/000-default.conf" ]; then
    rm -f "/etc/apache2/sites-enabled/000-default.conf"
fi

cp $VAGRANTDIR/php5_fpm.conf /etc/apache2/conf-available/
echo "Listen $APACHEPORT" > /etc/apache2/conf-available/otherport.conf

a2enconf php5_fpm otherport
a2enmod actions alias fastcgi rewrite

sed -i "/user = www-data/c\user = vagrant" /etc/php5/fpm/pool.d/www.conf
sed -i "/group = www-data/c\group = vagrant" /etc/php5/fpm/pool.d/www.conf
sed -i "/display_errors = Off/c\display_errors = On" /etc/php5/fpm/php.ini
sed -i "/display_errors = Off/c\display_errors = On" /etc/php5/cli/php.ini

service php5-fpm restart

# restart apache
service apache2 reload


echo "Install composer.."
if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
fi

source $VAGRANTDIR/reset_app.sh

echo "Done."
