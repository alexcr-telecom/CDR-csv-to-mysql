<?php
/*** process asterisk cdr file (Master.csv) insert usage
* values into a mysql database which is created for use
* with the Asterisk_addons cdr_addon_mysql.so
* The script will only insert NEW records so it is safe
* to run on the same log over-and-over.
*
* Author: John Lange (alexcr.telecom@gmail.com)
* Date: Version 4 Released 2018
*
* Here is what the script does:
*
$locale_db_host = 'localhost';
$locale_db_name = 'asteriskcdrdb';
$locale_db_login = 'root';
$locale_db_pass = 'password';
if($argc == 2) {
$logfile = $argv[1];
} else {
print("Usage ".$argv[0]." <filename>\n");
print("Where filename is the path to the Asterisk csv file to import (Master.csv)\n");
print("This script is safe to run multiple times on a growing log file as it only imports records that are newer than the database\n");
exit(0);
}
// connect to db
$linkmb = mysql_connect($locale_db_host, $locale_db_login, $locale_db_pass) or die("Could not connect : " . mysql_error());
mysql_select_db($locale_db_name, $linkmb) or die("Could not select database $locale_db_name");
//** 1) Find records in the asterisk log file. **
$rows = 0;
$handle = fopen($logfile, "r");
while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
// NOTE: the fields in Master.csv can vary. This should work by default on all installations but you may have to edit the next line to match your configuration
list($accountcode,$src, $dst, $dcontext, $clid, $channel, $dstchannel, $lastapp, $lastdata, $start, $answer, $end, $duration, $billsec, $disposition, $amaflags, $uniqueid, $userfield ) = $data;
/** 2) Test to see if the entry is unique **/
$sql="SELECT calldate, src, duration".
" FROM cdr".
" WHERE calldate='$start'".
" AND src='$src'".
" AND duration='$duration'".
" LIMIT 1";
if(!($result = mysql_query($sql, $linkmb))) {
print("Invalid query: " . mysql_error()."\n");
print("SQL: $sql\n");
die();
}
if(mysql_num_rows($result) == 0) { // we found a new record so add it to the DB
// 3) insert each row in the database
$sql = "INSERT INTO cdr (calldate, answerdate, hangupdate, clid, src, dst, dcontext, channel, dstchannel, lastapp, lastdata, duration, billsec, disposition, amaflags, accountcode, uniqueid, userfield) VALUES('$start', '$answer', '$end', '".mysql_real_escape_string($clid)."', '$src', '$dst', '$dcontext', '$channel', '$dstchannel', '$lastapp', '$lastdata', '$duration', '$billsec', '$disposition', '$amaflags', '$accountcode', '$uniqueid', '$userfield')";
if(!($result2 = mysql_query($sql, $linkmb))) {
print("Invalid query: " . mysql_error()."\n");
print("SQL: $sql\n");
die();
}
print("Inserted: $end $src $duration\n");
$rows++;
} else {
print("Not unique: $end $src $duration\n");
}
}
fclose($handle);
print("$rows imported\n");
?>
