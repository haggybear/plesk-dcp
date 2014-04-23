<?php

// Plesk Database Settings 
define("DB_USR", "admin");
define("DB_PWD", trim(exec("cat /etc/psa/.psa.shadow")));
define("DB_NAME", "psa");
define("DB_HOST", "localhost");

// Define your language e.g. de=german / en=english 
define("LANG","de");

// Allow Autoupdate
define("AUTOUPDATE",1);

//Grab PSA-Version
define("PSA_PATH",trim(exec ("grep PRODUCT_ROOT_D /etc/psa/psa.conf | sed s/^[t]*[A-Z_]*[t]*//"))."/");
define("VHOSTS_PATH",trim(exec ("grep HTTPD_VHOSTS_D /etc/psa/psa.conf | sed s/^[t]*[A-Z_]*[t]*//"))."/");
define("PSA_VERSION",doubleval(substr(trim(exec ("cat ".PSA_PATH."version")),0,4)));

//The path within the plesk docroot where the DCP ist installed
define("DCP_PATH","/dcp");

?>
