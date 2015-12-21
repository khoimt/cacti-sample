#!/bin/bash
p=$(pwd `dirname $0`);
cd $p;

echo 'Re-configure rsyslog...'
sed -i -r -e 's/plugin_iper_syslog_incoming/plugin_iper_syslog/g' /etc/rsyslog.d/mysql.conf
sed -i -r -e 's/plugin_camm_syslog_incoming/plugin_iper_syslog/g' /etc/rsyslog.d/mysql.conf
echo 'Restart rsyslog...'
service rsyslog restart

echo 'Re-configure snmptt...'
sed -i -r -e 's/plugin_camm_snmptt/plugin_iper_snmptt/g' /etc/snmp/snmptt.ini
echo 'Restart snmptt...'
service snmptt restart

echo 'Restart snmp daemon...'
sudo service snmpd restart

