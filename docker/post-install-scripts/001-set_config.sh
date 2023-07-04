#!/bin/bash
if [[ -n "${SEQURA_USER}" ]]; then
    echo "* Setting SEQURA_USER ...";
    echo "define('_SEQURA_USER', '$SEQURA_USER');" >> /var/www/html/config/defines.inc.php
    echo "delete from ${DB_PREFIX}configuration where name='SEQURA_USER';"|mysql -h $DB_SERVER -u $DB_USER -p$DB_PASSWD $DB_NAME
    echo "insert into ${DB_PREFIX}configuration (date_add,date_upd,name,value) values (now(),now(),'SEQURA_USER','${SEQURA_USER}');"|mysql -h $DB_SERVER -u $DB_USER -p$DB_PASSWD $DB_NAME
fi
if [[ -n "${SEQURA_PASS}" ]]; then
    echo "* Setting SEQURA_PASS ...";
    echo "define('_SEQURA_PASS', '$SEQURA_PASS');" >> /var/www/html/config/defines.inc.php
    echo "delete from ${DB_PREFIX}configuration where name='SEQURA_PASS';"|mysql -h $DB_SERVER -u $DB_USER -p$DB_PASSWD $DB_NAME
    echo "insert into ${DB_PREFIX}configuration (date_add,date_upd,name,value) values (now(),now(),'SEQURA_PASS','${SEQURA_PASS}');"|mysql -h $DB_SERVER -u $DB_USER -p$DB_PASSWD $DB_NAME
fi
if [[ -n "${SEQURA_MERCHANT_ID}" ]]; then
    echo "* Setting SEQURA_MERCHANT_ID ...";
    echo "define('_SEQURA_MERCHANT_ID_ES', '$SEQURA_MERCHANT_ID');" >> /var/www/html/config/defines.inc.php
    echo "delete from ${DB_PREFIX}configuration where name='SEQURA_MERCHANT_ID_ES';"|mysql -h $DB_SERVER -u $DB_USER -p$DB_PASSWD $DB_NAME
    echo "insert into ${DB_PREFIX}configuration (date_add,date_upd,name,value) values (now(),now(),'SEQURA_MERCHANT_ID_ES','${SEQURA_MERCHANT_ID}');"|mysql -h $DB_SERVER -u $DB_USER -p$DB_PASSWD $DB_NAME
fi
if [[ -n "${SEQURA_ASSETS_KEY}" ]]; then
    echo "* Setting SEQURA_ASSETS_KEY ...";
    echo "define('_SEQURA_ASSETS_KEY', '$SEQURA_ASSETS_KEY');" >> /var/www/html/config/defines.inc.php
    echo "delete from ${DB_PREFIX}configuration where name='SEQURA_ASSETS_KEY';"|mysql -h $DB_SERVER -u $DB_USER -p$DB_PASSWD $DB_NAME
    echo "insert into ${DB_PREFIX}configuration (date_add,date_upd,name,value) values (now(),now(),'SEQURA_ASSETS_KEY','${SEQURA_ASSETS_KEY}');"|mysql -h $DB_SERVER -u $DB_USER -p$DB_PASSWD $DB_NAME
fi
if [[ -n "${SEQURA_SANDBOX_SCRIPT_BASE_URI}" ]]; then
    echo "* Setting SEQURA_SANDBOX_SCRIPT_BASE_URI ...";
    echo "define('_SEQURA_SANDBOX_SCRIPT_BASE_URI', '$SEQURA_SANDBOX_SCRIPT_BASE_URI');" >> /var/www/html/config/defines.inc.php
    echo "delete from ${DB_PREFIX}configuration where name='SEQURA_SANDBOX_SCRIPT_BASE_URI';"|mysql -h $DB_SERVER -u $DB_USER -p$DB_PASSWD $DB_NAME
    echo "insert into ${DB_PREFIX}configuration (date_add,date_upd,name,value) values (now(),now(),'SEQURA_SANDBOX_SCRIPT_BASE_URI','${SEQURA_SANDBOX_SCRIPT_BASE_URI}');"|mysql -h $DB_SERVER -u $DB_USER -p$DB_PASSWD $DB_NAME
fi
if [[ -n "${SEQURA_SANDBOX_ENDPOINT}" ]]; then
    echo "* Setting SEQURA_SANDBOX_ENDPOINT ...";
    echo "define('_SEQURA_SANDBOX_ENDPOINT', '$SEQURA_SANDBOX_ENDPOINT');" >> /var/www/html/config/defines.inc.php
    echo "delete from ${DB_PREFIX}configuration where name='SEQURA_SANDBOX_ENDPOINT';"|mysql -h $DB_SERVER -u $DB_USER -p$DB_PASSWD $DB_NAME
    echo "insert into ${DB_PREFIX}configuration (date_add,date_upd,name,value) values (now(),now(),'SEQURA_SANDBOX_ENDPOINT','${SEQURA_SANDBOX_ENDPOINT}');"|mysql -h $DB_SERVER -u $DB_USER -p$DB_PASSWD $DB_NAME
fi
echo "delete from ${DB_PREFIX}configuration where name in ('SEQURA_ALLOW_IP','PS_MAIL_METHOD','PS_MAIL_SERVER','PS_MAIL_SMTP_PORT');"|mysql -h $DB_SERVER -u $DB_USER -p$DB_PASSWD $DB_NAME
echo "insert into ${DB_PREFIX}configuration (date_add,date_upd,name,value) values
    (now(),now(),'PS_MAIL_METHOD','2'),
    (now(),now(),'PS_MAIL_SMTP_PORT','1025'),
    (now(),now(),'PS_MAIL_SERVER','mailhog')
    ;"|mysql -h $DB_SERVER -u $DB_USER -p$DB_PASSWD $DB_NAME
