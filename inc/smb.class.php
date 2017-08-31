<?php
 //
 // smb.php
 // This class implements a SMB stream wrapper based on 'smbclient'
 //
 // Date: lun oct 22 10:35:35 CEST 2007
 //
 // Homepage: http://www.phpclasses.org/smb4php
 //
 // Copyright (c) 2007 Victor M. Varela <vmvarela@gmail.com>
 // 
 // Modifications by Blaise Thauvin for GLPI module Glpi2mdt based on the original file version 0.8
 // August 2017
 //
 // This program is free software; you can redistribute it and/or
 // modify it under the terms of the GNU General Public License
 // as published by the Free Software Foundation; either version 2
 // of the License, or (at your option) any later version.
 //
 // This program is distributed in the hope that it will be useful,
 // but WITHOUT ANY WARRANTY; without even the implied warranty of
 // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 // GNU General Public License for more details.
 //
 //

 define ('SMB4PHP_VERSION', '0.8.1 for gpi2mdt');

 //
 // CONFIGURATION SECTION - Change for your needs
 //

 define ('SMB4PHP_SMBCLIENT', 'smbclient');
 define ('SMB4PHP_SMBOPTIONS', 'TCP_NODELAY IPTOS_LOWDELAY SO_KEEPALIVE SO_RCVBUF=8192 SO_SNDBUF=8192');
 define ('SMB4PHP_AUTHMODE', 'arg'); // set to 'env' to use USER enviroment variable

 //
 // SMB - commands that does not need an instance
 //

 $GLOBALS['__smb_cache'] = array ('stat' => array (), 'dir' => array ());

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginGlpi2mdtSmb extends CommonDBTM {

   function parse_url ($url) {
   /// Expected format is "smb://[[domain;]user[%password]@]server/sharename/path/to/file"
       $pu = parse_url (trim($url));
      foreach (array ('domain', 'user', 'pass', 'host', 'port', 'path') as $i) {
         if (! isset($pu[$i])) {
            $pu[$i] = '';
         }
      }
      if (count ($userdomain = split (';', urldecode ($pu['user']))) > 1) {
          @list ($pu['domain'], $pu['user']) = $userdomain;
      }
            $path = preg_replace (array ('/^\//', '/\/$/'), '', urldecode ($pu['path']));
            list ($pu['share'], $pu['path']) = (preg_match ('/^([^\/]+)\/(.*)/', $path, $regs))
            ? array ($regs[1], preg_replace ('/\//', '\\', $regs[2]))
            : array ($path, '');
            $pu['type'] = $pu['path'] ? 'path' : ($pu['share'] ? 'share' : ($pu['host'] ? 'host' : '**error**'));
      if (! ($pu['port'] = intval(@$pu['port']))) {
         $pu['port'] = 139;
      }
            return $pu;
   }


   function look ($purl) {
       return PluginGlpi2mdtSmb::client ('-L ' . escapeshellarg ($purl['host']), $purl);
   }


   function execute ($command, $purl) {
       return PluginGlpi2mdtSmb::client ('-d 0 '
           . escapeshellarg ('//' . $purl['host'] . '/' . $purl['share'])
           . ' -c ' . escapeshellarg ($command), $purl
       );
   }

   function client ($params, $purl) {

      static $regexp = array (
       '^added interface ip=(.*) bcast=(.*) nmask=(.*)$' => 'skip',
       'Anonymous login successful' => 'skip',
       '^Domain=\[(.*)\] OS=\[(.*)\] Server=\[(.*)\]$' => 'skip',
       '^\tSharename[ ]+Type[ ]+Comment$' => 'shares',
       '^\t---------[ ]+----[ ]+-------$' => 'skip',
       '^\tServer [ ]+Comment$' => 'servers',
       '^\t---------[ ]+-------$' => 'skip',
       '^\tWorkgroup[ ]+Master$' => 'workg',
       '^\t(.*)[ ]+(Disk|IPC)[ ]+IPC.*$' => 'skip',
       '^\tIPC\\\$(.*)[ ]+IPC' => 'skip',
       '^\t(.*)[ ]+(Disk)[ ]+(.*)$' => 'share',
       '^\t(.*)[ ]+(Printer)[ ]+(.*)$' => 'skip',
       '([0-9]+) blocks of size ([0-9]+)\. ([0-9]+) blocks available' => 'skip',
       'Got a positive name query response from ' => 'skip',
       '^(session setup failed): (.*)$' => 'error',
       '^(.*): ERRSRV - ERRbadpw' => 'error',
       '^Error returning browse list: (.*)$' => 'error',
       '^tree connect failed: (.*)$' => 'error',
       '^(Connection to .* failed)$' => 'error',
       '^NT_STATUS_(.*) ' => 'error',
       '^NT_STATUS_(.*)\$' => 'error',
       'ERRDOS - ERRbadpath \((.*).\)' => 'error',
       'cd (.*): (.*)$' => 'error',
       '^cd (.*): NT_STATUS_(.*)' => 'error',
       '^\t(.*)$' => 'srvorwg',
       '^([0-9]+)[ ]+([0-9]+)[ ]+(.*)$' => 'skip',
       '^Job ([0-9]+) cancelled' => 'skip',
       '^[ ]+(.*)[ ]+([0-9]+)[ ]+(Mon|Tue|Wed|Thu|Fri|Sat|Sun)[ ](Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[ ]+([0-9]+)[ ]+([0-9]{2}:[0-9]{2}:[0-9]{2})[ ]([0-9]{4})$' => 'files',
       '^message start: ERRSRV - (ERRmsgoff)' => 'error'
       );

      if (SMB4PHP_AUTHMODE == 'env') {
           putenv("USER={$purl['user']}%{$purl['pass']}");
           $auth = '';
      } else {
           $auth = ($purl['user'] <> '' ? (' -U ' . escapeshellarg ($purl['user'] . '%' . $purl['pass'])) : '');
      }
      if ($purl['domain'] <> '') {
           $auth .= ' -W ' . escapeshellarg ($purl['domain']);
      }
        $port = ($purl['port'] <> 139 ? ' -p ' . escapeshellarg ($purl['port']) : '');
        $options = '-O ' . escapeshellarg(SMB4PHP_SMBOPTIONS);
        $output = popen (SMB4PHP_SMBCLIENT." -N {$auth} {$options} {$port} {$options} {$params} 2>/dev/null", 'r');
        $info = array ();
      while ($line = fgets ($output, 4096)) {
           list ($tag, $regs, $i) = array ('skip', array (), array ());
           reset ($regexp);
         foreach ($regexp as $r => $t) {
            if (preg_match ('/'.$r.'/', $line, $regs)) {
                 $tag = $t;
                 break;
            }
         }
         switch ($tag) {
            case 'skip': continue;
            case 'shares': $mode = 'shares'; break;
            case 'servers': $mode = 'servers'; break;
            case 'workg': $mode = 'workgroups'; break;
            case 'share':
               list($name, $type) = array (
                trim(substr($line, 1, 15)),
                trim(strtolower(substr($line, 17, 10)))
               );
               $i = ($type <> 'disk' && preg_match('/^(.*) Disk/', $line, $regs))
                 ? array(trim($regs[1]), 'disk')
                 : array($name, 'disk');
                break;
            case 'srvorwg':
               list ($name, $master) = array (
                strtolower(trim(substr($line, 1, 21))),
                strtolower(trim(substr($line, 22)))
               );
               $i = ($mode == 'servers') ? array ($name, "server") : array ($name, "workgroup", $master);
                break;
            case 'files':
               list ($attr, $name) = preg_match ("/^(.*)[ ]+([D|A|H|S|R]+)$/", trim ($regs[1]), $regs2)
                ? array (trim ($regs2[2]), trim ($regs2[1]))
                : array ('', trim ($regs[1]));
               list ($his, $im) = array (
               split(':', $regs[6]), 1 + strpos("JanFebMarAprMayJunJulAugSepOctNovDec", $regs[4]) / 3);
               $i = ($name <> '.' && $name <> '..')
                 ? array (
                    $name,
                    (strpos($attr, 'D') === FALSE) ? 'file' : 'folder',
                    'attr' => $attr,
                    'size' => intval($regs[2]),
                    'time' => mktime ($his[0], $his[1], $his[2], $im, $regs[5], $regs[7])
                  )
                 : array();
                break;
            case 'error': trigger_error($regs[1], E_USER_WARNING);
         }
         if ($i) {
            switch ($i[1]) {
               case 'file':
               case 'folder': $info['info'][$i[0]] = $i;
               case 'disk':
               case 'server':
               case 'workgroup': $info[$i[1]][] = $i[0];
            }
         }
      }
        pclose($output);
        return $info;
   }


      // stats

   function url_stat ($url, $flags = STREAM_URL_STAT_LINK) {
      if ($s = PluginGlpi2mdtSmb::getstatcache($url)) {
         return $s; }
       list ($stat, $pu) = array (array (), PluginGlpi2mdtSmb::parse_url ($url));
      switch ($pu['type']) {
         case 'host':
            if ($o = PluginGlpi2mdtSmb::look ($pu)) {
               $stat = stat ("/tmp");
            } else {
               trigger_error ("url_stat(): list failed for host '{$host}'", E_USER_WARNING);
            }
            break;
         case 'share':
            if ($o = PluginGlpi2mdtSmb::look ($pu)) {
               $found = FALSE;
               $lshare = strtolower ($pu['share']); // fix by Eric Leung
               foreach ($o['disk'] as $s) {
                  if ($lshare == strtolower($s)) {
                        $found = TRUE;
                        $stat = stat ("/tmp");
                        break;
                  }
               }
               if (! $found) {
                  trigger_error ("url_stat(): disk resource '{$share}' not found in '{$host}'", E_USER_WARNING);
               }
            }
            break;
         case 'path':
            if ($o = PluginGlpi2mdtSmb::execute ('dir "'.$pu['path'].'"', $pu)) {
               $p = split ("[\\]", $pu['path']);
               $name = $p[count($p)-1];
               if (isset ($o['info'][$name])) {
                  $stat = PluginGlpi2mdtSmb::addstatcache ($url, $o['info'][$name]);
               } else {
                  trigger_error ("url_stat(): path '{$pu['path']}' not found", E_USER_WARNING);
               }
            } else {
               trigger_error ("url_stat(): dir failed for path '{$pu['path']}'", E_USER_WARNING);
            }
            break;
         default: trigger_error ('error in URL', E_USER_ERROR);
      }
       return $stat;
   }

   function addstatcache ($url, $info) {
       global $__smb_cache;
       $is_file = (strpos ($info['attr'], 'D') === FALSE);
       $s = ($is_file) ? stat ('/etc/passwd') : stat ('/tmp');
       $s[7] = $s['size'] = $info['size'];
       $s[8] = $s[9] = $s[10] = $s['atime'] = $s['mtime'] = $s['ctime'] = $info['time'];
       return $__smb_cache['stat'][$url] = $s;
   }

   function getstatcache ($url) {
       global $__smb_cache;
       return isset ($__smb_cache['stat'][$url]) ? $__smb_cache['stat'][$url] : FALSE;
   }

   function clearstatcache ($url='') {
       global $__smb_cache;
      if ($url == '') {
         $__smb_cache['stat'] = array (); } else {
         unset ($__smb_cache['stat'][$url]);
         }
   }


      // commands

   function unlink ($url) {
       $pu = PluginGlpi2mdtSmb::parse_url($url);
      if ($pu['type'] <> 'path') {
         trigger_error('unlink(): error in URL', E_USER_ERROR);
      }
       PluginGlpi2mdtSmb::clearstatcache ($url);
       return PluginGlpi2mdtSmb::execute ('del "'.$pu['path'].'"', $pu);
   }

   function rename ($url_from, $url_to) {
       list ($from, $to) = array (PluginGlpi2mdtSmb::parse_url($url_from), PluginGlpi2mdtSmb::parse_url($url_to));
      if ($from['host'] <> $to['host'] ||
         $from['share'] <> $to['share'] ||
         $from['user'] <> $to['user'] ||
         $from['pass'] <> $to['pass'] ||
         $from['domain'] <> $to['domain']) {
          trigger_error('rename(): FROM & TO must be in same server-share-user-pass-domain', E_USER_ERROR);
      }
      if ($from['type'] <> 'path' || $to['type'] <> 'path') {
         trigger_error('rename(): error in URL', E_USER_ERROR);
      }
       PluginGlpi2mdtSmb::clearstatcache ($url_from);
       return PluginGlpi2mdtSmb::execute ('rename "'.$from['path'].'" "'.$to['path'].'"', $to);
   }

   function mkdir ($url, $mode, $options) {
       $pu = PluginGlpi2mdtSmb::parse_url($url);
      if ($pu['type'] <> 'path') {
         trigger_error('mkdir(): error in URL', E_USER_ERROR);
      }
       return PluginGlpi2mdtSmb::execute ('mkdir "'.$pu['path'].'"', $pu);
   }

   function rmdir ($url) {
       $pu = PluginGlpi2mdtSmb::parse_url($url);
      if ($pu['type'] <> 'path') {
         trigger_error('rmdir(): error in URL', E_USER_ERROR);
      }
       PluginGlpi2mdtSmb::clearstatcache ($url);
       return PluginGlpi2mdtSmb::execute ('rmdir "'.$pu['path'].'"', $pu);
   }

}

