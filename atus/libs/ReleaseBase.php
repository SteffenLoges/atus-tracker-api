<?php

namespace ATUS\libs;

if (!defined('ATUS')) {
  die('Direct access not permitted');
}

interface IReleaseBase
{
  function uploadRelease()/*: void*/;
}


class ReleaseBase implements IReleaseBase
{
  protected $torrentFile;
  protected $nfoFile;
  protected $hash;
  protected $name;
  protected $category;
  protected $categoryRaw;
  protected $pre;
  protected $fileList;
  protected $metaFiles;
  protected $userID;

  protected function __construct($torrentFile, $nfoFile, $hash, $name, $category, $categoryRaw, $pre, $fileList, $metaFiles, $userID)
  {
    $this->torrentFile = $torrentFile;
    $this->nfoFile = $nfoFile;
    $this->hash = $hash;
    $this->name = $name;
    $this->category = $category;
    $this->categoryRaw = $categoryRaw;
    $this->pre = $pre;
    $this->fileList = $fileList;
    $this->metaFiles = $metaFiles;
    $this->userID = $userID;
  }

  // depending on the tracker software, the hash is stored either as sha1 or hex
  // By default, TBDev uses sha1, netvision uses hex
  // if you are unsure, open your takeupload.php and seach for
  // 
  //   -  $infohash = sha1($info["string"]);             // stored as sha1, set $hexHash to FALSE.
  // or
  //   -  $infohash = pack("H*", sha1($info["string"]))  // stored as hex, set $hexHash to TRUE.
  function getHash()/*: string*/
  {
    $hexHash = ATUS__DERIVED_FROM === 'NETVISION';

    if ($hexHash) {
      return hex2bin($this->hash);
    }

    return $this->hash;
  }

  // default filter used by both tbdev and netvision
  // unless you are using a different tracker software or customised the default filter, you should not need to change this
  function searchfield()
  {
    return preg_replace(['/[^a-z0-9]/si', '/^\s*/s', '/\s*$/s', '/\s+/s'], [" ", "", "", " "], $this->name);
  }

  function uploadRelease()/*: void*/
  {
    throw new \Exception('Not implemented');
  }
}
