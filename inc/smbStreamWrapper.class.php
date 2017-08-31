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


   //
   // SMB_STREAM_WRAPPER - class to be registered for smb:// URLs
   //

class PluginGlpi2mdtSmbStreamWrapper extends PluginGlpi2mdtSmb {

    // variables

    var $stream, $url, $parsed_url = array (), $mode, $tmpfile;
    var $need_flush = FALSE;
    var $dir = array (), $dir_index = -1;


    // directories

   function dir_opendir ($url, $options) {
      if ($d = $this->getdircache ($url)) {
         $this->dir = $d;
         $this->dir_index = 0;
         return TRUE;
      }
      $pu = smb::parse_url ($url);
      switch ($pu['type']) {
         case 'host':
            if ($o = smb::look ($pu)) {
               $this->dir = $o['disk'];
               $this->dir_index = 0;
            } else {
               trigger_error ("dir_opendir(): list failed for host '{$pu['host']}'", E_USER_WARNING);
            }
            break;
         case 'share':
         case 'path':
            if ($o = smb::execute ('dir "'.$pu['path'].'\*"', $pu)) {
               $this->dir = array_keys($o['info']);
               $this->dir_index = 0;
               $this->adddircache ($url, $this->dir);
               foreach ($o['info'] as $name => $info) {
                   smb::addstatcache($url . '/' . urlencode($name), $info);
               }
            } else {
               trigger_error ("dir_opendir(): dir failed for path '{$path}'", E_USER_WARNING);
            }
            break;
         default:
             trigger_error ('dir_opendir(): error in URL', E_USER_ERROR);
      }
      return TRUE;
   }

   function dir_readdir () {
      return ($this->dir_index < count($this->dir)) ? $this->dir[$this->dir_index++] : FALSE; }

   function dir_rewinddir () {
      $this->dir_index = 0; }

   function dir_closedir () {
      $this->dir = array(); $this->dir_index = -1; return TRUE; }


    // cache

   function adddircache ($url, $content) {
      global $__smb_cache;
      return $__smb_cache['dir'][$url] = $content;
   }

   function getdircache ($url) {
      global $__smb_cache;
      return isset ($__smb_cache['dir'][$url]) ? $__smb_cache['dir'][$url] : FALSE;
   }

   function cleardircache ($url='') {
      global $__smb_cache;
      if ($url == '') {
         $__smb_cache['dir'] = array (); } else {
         unset ($__smb_cache['dir'][$url]);
         }
   }


    // streams

   function stream_open ($url, $mode, $options, $opened_path) {
      $this->url = $url;
      $this->mode = $mode;
      $this->parsed_url = $pu = smb::parse_url($url);
      if ($pu['type'] <> 'path') {
         trigger_error('stream_open(): error in URL', E_USER_ERROR);
      }
      switch ($mode) {
         case 'r':
         case 'r+':
         case 'rb':
         case 'a':
         case 'a+': $this->tmpfile = tempnam('/tmp', 'smb.down.');
                    smb::execute ('get "'.$pu['path'].'" "'.$this->tmpfile.'"', $pu);
                    break;
         case 'w':
         case 'w+':
         case 'wb':
         case 'x':
         case 'x+': $this->cleardircache();
                    $this->tmpfile = tempnam('/tmp', 'smb.up.');
      }
      $this->stream = fopen ($this->tmpfile, $mode);
      return TRUE;
   }

   function stream_close () {
      return fclose($this->stream); }

   function stream_read ($count) {
      return fread($this->stream, $count); }

   function stream_write ($data) {
      $this->need_flush = TRUE; return fwrite($this->stream, $data); }

   function stream_eof () {
      return feof($this->stream); }

   function stream_tell () {
      return ftell($this->stream); }

   function stream_seek ($offset, $whence=null) {
      return fseek($this->stream, $offset, $whence); }

   function stream_flush () {
      if ($this->mode <> 'r' && $this->need_flush) {
         smb::clearstatcache ($this->url);
         smb::execute ('put "'.$this->tmpfile.'" "'.$this->parsed_url['path'].'"', $this->parsed_url);
         $this->need_flush = FALSE;
      }
   }

   function stream_stat () {
      return smb::url_stat ($this->url); }

   function __destruct () {
      if ($this->tmpfile <> '') {
         if ($this->need_flush) {
            $this->stream_flush ();
         }
         unlink ($this->tmpfile);

      }
   }

}
