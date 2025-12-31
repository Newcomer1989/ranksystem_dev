<?PHP
## Uncomment the command you need to run PHP
## This are only examples
## If you miss one, feel free to add it
## Only one single command should be active at the same time!
##
## Default, which should normally working on Linux and Windows
$phpcommand = 'php';
## 
## LINUX
#$phpcommand = 'php84';
#$phpcommand = '/usr/bin/php8.3';
#$phpcommand = '/usr/bin/php8.4';
#$phpcommand = '/opt/plesk/php/8.3/bin/php';
#$phpcommand = '/opt/plesk/php/8.4/bin/php';
##
##
## WINDOWS
#$phpcommand = 'C:\PHP8\php.exe';
#$phpcommand = 'C:\wamp\bin\php\php.exe';
#$phpcommand = 'C:\xampp\php84\php.exe';
# On blanks or special characters inside the path, you need to escape these with special marks -->  \"  <-- at the beginning and end of the path, see example below
#$phpcommand = '\"C:\Program Files (x86)\PHP\php.exe\"';
#$phpcommand = '\"C:\Program Files (x86)\Plesk\Additional\PHP84\php.exe\"';
##
##
## OTHER
## Synology NAS
#$phpcommand = '/volume1/@appstore/PHP8.4/usr/local/bin/php84';
?>