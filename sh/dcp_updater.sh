#!/bin/sh

if [ "$1" = "0" ]; then
wget http://www.haggybear.de/download/dcp_update.zip
fi

if [ "$1" = "2" ]; then
unzip -o dcp_update.zip
chmod 755 -R *
chmod 777 -R *.txt dbs/
chown root:psaadm -R *
chown root:psaadm sh/wrapper
chmod 4755 sh/wrapper
fi

if [ "$1" = "2a" ]; then
echo "$2" > config.inc.php
rm config.new.txt
fi

if [ "$1" = "3" ]; then
chmod 755 -R *
chmod 777 -R *.txt dbs/
chown root:psaadm -R *
chown root:psaadm sh/wrapper
chmod 4755 sh/wrapper
rm dcp_update.zip
fi

