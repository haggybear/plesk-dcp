#!/bin/sh

if [ "$1" = "checkdns" ]; then
nslookup -querytype=ns $2
fi

if [ "$1" = "refreshdns" ]; then
$2 -a $3 -a dummyyuumd -ip 1.1.1.1
$2 -d $3 -a dummyyuumd -ip 1.1.1.1
fi

if [ "$1" = "createdns" ]; then
$2 -c $3 -d $4
touch $7/$3.$4/conf/vhost.conf
echo "DocumentRoot \"$5\"" > $7/$3.$4/conf/vhost.conf
echo "php_admin_flag safe_mode off" >> $7/$3.$4/conf/vhost.conf
echo "RewriteEngine On" >> $7/$3.$4/conf/vhost.conf
echo "RewriteRule ^(.*)$ /index.php" >> $7/$3.$4/conf/vhost.conf

$6/admin/bin/httpdmng --reconfigure-domain $3.$4
fi

if [ "$1" = "deletedns" ]; then
$2 -r $3 
fi

if [ "$1" = "configs" ]; then
cat /etc/psa/.psa.shadow
grep PRODUCT_ROOT_D /etc/psa/psa.conf | sed s/^[t]*[A-Z_]*[t]*//
grep HTTPD_VHOSTS_D /etc/psa/psa.conf | sed s/^[t]*[A-Z_]*[t]*//
fi
