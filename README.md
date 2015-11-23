```
sudo snmpttconvertmib --in=./cpqhost-mib.txt --out=/etc/snmp/snmptt.conf
sudo snmpttconvertmib --in=/usr/share/snmp/mibs/NET-SNMP-EXAMPLES-MIB.txt --out=/etc/snmp/snmptt.conf
snmptrap -v 1 -c public 192.168.56.103 NET-SNMP-EXAMPLES-MIB::netSnmpExampleHeartbeatNotification "" 6 17 "" netSnmpExampleHeartbeatRate i 123456
snmptrap -v 1 -c public 192.168.56.103 .1.3.6.1.4.1.637.64.0.10.1.2 192.168.42.234 6 3 1000 1.3.6.1.4.1.637.64.0.10.1.2.3 I 2
```
