<?php
   //
   // Register 'smb' protocol !
   //

include ("/var/www/html/glpi/plugins/glpi2mdt/inc/smb.class.php");
include ("/var/www/html/glpi/plugins/glpi2mdt/inc/smbStreamWrapper.class.php");


stream_wrapper_register('smb', 'PluginGlpi2mdtSmbStreamWrapper')
    or die ('Failed to register protocol');

$handle = fopen ('smb://saintmaur;admin-bla%Taratata17@wsus01-p.saintmaur.local/DeploymentShare$/Control/Applications.xml', 'r');
print_r($handle);

$contents1 = fread($handle, 100);

$contents2 = stream_get_contents($handle);

fclose($handle);

?>
~                                                                                                                                                                
~                                                                      
