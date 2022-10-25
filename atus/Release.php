<?php

namespace ATUS;

if (!defined('ATUS')) {
  die('Direct access not permitted');
}

use ATUS\libs\ATUS;
use ATUS\libs\ReleaseBase;


class Release extends ReleaseBase
{

  protected $bitBucketFolder;

  protected $atusDataFolder;

  protected $torrentsFolder;

  public function __construct()
  {
    parent::__construct(...func_get_args());


    // path to the bitbucket directory. 
    // Only Netvision, ignore this for TBDEV
    $this->bitBucketFolder = __DIR__ . '/../bitbucket';

    // path to your torrents folder
    $this->torrentsFolder = __DIR__ . '/../torrents';

    // path where atus stores release metadata
    $this->atusDataFolder = __DIR__ . '/data';


    // make sure the folders exist and are writable
    $checkFolders = [
      $this->torrentsFolder,
      $this->atusDataFolder,
    ];

    if (ATUS__DERIVED_FROM === 'NETVISION') {
      $checkFolders[] = $this->bitBucketFolder;
    }

    foreach ($checkFolders as $folder) {
      if (!is_writable($folder)) {
        throw new \Exception('Folder not writable: ' . $folder);
      }
    }
  }

  static function getCategoryID($releaseName, $category, $categoryRaw)/*: int*/
  {
    // Assign a fitting category to the torrent
    // See the example below for how to do this
    // ------------------------------------------
    // $category can be one of the following:
    //    MOVIE, TV, DOCU, APP, GAME, AUDIO, EBOOK, XXX, UNKNOWN
    // ------------------------------------------
    // $categoryRaw is the raw category we got from predb.ovh (e.g. "TV > HD")
    // ------------------------------------------

    // simple category mapping
    if ($category === 'XXX') {
      return 6; // 6 is the default category in TBDEV for XXX torrents
    }

    // more complex category mapping based on the category and release name
    // will match
    //   release.XviD-RLSGroup
    //   release_xvid_rls_group
    //   release.xvid.rls.group
    if ($category === 'MOVIE' && preg_match('/[.\-_]XVID[.\-_]/i', $releaseName)) {
      return 10; // 10 is the default category in TBDEV for Movies/XviD torrents
    }

    // if no category was found, return a default category
    return 13;
  }


  function upload()/*: void*/
  {

    $hash = $this->getHash();

    // -- initial checks --------------------------------------------------------------------------

    // Uncomment the code below to delete the old torrent if it was already uploaded
    // usefull during initial setup, so you don't have to delete the old torrent manually
    // if (ATUS__DEBUG) {
    //   $stmt =  ATUS::getDB()->prepare('DELETE FROM torrents WHERE name = ? OR filename = ? OR info_hash = ?');
    //   $stmt->execute([$this->name, $this->name, $this->getHash($this->hash)]);
    // }

    // check if the torrent already exists
    $stmt = ATUS::getDB()->prepare('SELECT id FROM torrents WHERE name = ? OR filename = ? OR info_hash = ?');
    $stmt->execute([$this->name, $this->name, $hash]);
    $row = $stmt->fetch();
    if ($row && $row['id']) {
      throw new \Exception('Release was already uploaded');
    }

    // -- set description -------------------------------------------------------------------------
    // the nfo file is always present so we can simply extract the description from it
    $nfoData = file_get_contents($this->nfoFile);

    $descriptionRows = [];
    foreach (preg_split("/\R/", $nfoData) as  $line) {

      // filter unwanted characters
      $line = trim(preg_replace('/[^a-z0-9.:\-@\/\(\)\[\] ]/si', ' ', $line));
      if (!$line) {
        continue;
      }

      // remove multiple spaces / dots
      $line = preg_replace('/[\s|\.]{2,}/', ' ', $line);

      // only display the line if it contains a word with at least 3 alphanumeric characters
      if (!preg_match('/[a-z0-9]{3,}/si', $line)) {
        continue;
      }

      $descriptionRows[] = $line;
    }

    $description = implode("\n", $descriptionRows);

    // -- files statistics ------------------------------------------------------------------------
    $size = array_reduce($this->fileList, function ($carry, $item) {
      return $carry + $item['length'];
    }, 0);

    $numfiles = count($this->fileList);

    // -- values to insert into the database ------------------------------------------------------
    $insertParams = [
      'info_hash' => $hash,
      'name' => $this->name,
      'filename' => $this->name . '.torrent',
      'save_as' => $this->name,
      'search_text' => $this->searchfield(),
      'descr' => $description,
      'ori_descr' => $description,
      'size' => $size,
      'added' => ATUS__DERIVED_FROM === 'NETVISION' ? date("Y-m-d H:i:s") : time(),
      'last_action' => time(),
      'type' => $numfiles > 1 ? 'multi' : 'single',
      'numfiles' => $numfiles,
      'owner' => intval($this->userID), // atus sends the user id as a string, both netvision and tbdev expect an int
      'nfo' => str_replace("\x0d\x0d\x0a", "\x0d\x0a", $nfoData),
      'category' => self::getCategoryID($this->name, $this->category, $this->categoryRaw),
    ];

    if (ATUS__DERIVED_FROM == 'TBDEV') {
      // this is set in tbdev but never used
      // we add it anyway in case any mods use it
      $insertParams['client_created_by'] = 'ATUS';
    }

    // -- insert release into database -------------------------------------------------------------
    $stmt = ATUS::getDB()->prepare('INSERT INTO torrents SET ' . implode(', ', array_map(function ($key) {
      return '`' . $key . '` = ?';
    }, array_keys($insertParams))));
    $stmt->execute(array_values($insertParams));

    $internalID = ATUS::getDB()->lastInsertId();

    // -- insert files into database --------------------------------------------------------------
    $stmt = ATUS::getDB()->prepare(
      'INSERT INTO files 
      (torrent, filename, size) 
      VALUES ' . implode(', ', array_fill(0, $numfiles, '(?, ?, ?)'))
    );

    $stmt->execute(array_reduce($this->fileList, function ($carry, $item) use ($internalID) {
      return array_merge($carry, [$internalID, implode("/", $item['path']), $item['length']]);
    }, []));

    // -- move new .torrent file ------------------------------------------------------------------
    move_uploaded_file($this->torrentFile, $this->torrentsFolder . '/' . $internalID . '.torrent');

    // -- image upload (netvision only) -----------------------------------------------------------
    if (ATUS__DERIVED_FROM === 'NETVISION') {
      $this->uploadImage($internalID);
    }

    // -- generate nfo image (netvision only) -----------------------------------------------------
    if (ATUS__DERIVED_FROM === 'NETVISION') {
      $this->generateNFOImage($nfoData, $this->bitBucketFolder . '/nfo-' . $internalID . '.png');
    }

    // -- store data for later use ----------------------------------------------------------------
    // you should store the data in your database for later use but for simplicity we store the data in a json file. 
    // the file doesn't contain any sensitive data so this is fine even if the file is potentially accessible to the public
    file_put_contents($this->atusDataFolder . '/' . $internalID . '.json', json_encode([
      'name' => $this->name,
      'hash' => $this->hash,
      'metaFiles' => $this->metaFiles,
      'category' => $this->category,
      'categoryRaw' => $this->categoryRaw,
      'pre' => $this->pre,
    ]));

    // ============================================================================================
    // -- UPLOAD SUCCESSFUL -----------------------------------------------------------------------
    // ============================================================================================


    // add all the stuff you want to do after a successful upload here (e.g. logging, shoutbox messsages, notifications, etc.)


    // ============================================================================================

  }


  // ==============================================================================================
  // -- YOU DON'T NEED TO EDIT ANYTHING BELOW THIS LINE -------------------------------------------
  // ==============================================================================================

  function uploadImage($internalID)
  {

    // check if php-gd is installed
    if (!function_exists('imagecreatefromjpeg')) {
      throw new \Exception('PHP GD extension is not installed');
    }

    // get source image
    $images = array_values(array_filter($this->metaFiles, function ($metaFile) {
      return $metaFile['type'] === 'SOURCE_IMAGE';
    }));

    // if no source image was found, use the image from the torrent
    if (count($images) === 0) {
      $images = array_values(array_filter($this->metaFiles, function ($metaFile) {
        return $metaFile['type'] === 'IMAGE';
      }));
    }

    // if you like, you can also use non "cover-like" images in case no cover image was found
    // other available types are: PROOF_IMAGE, SCREEN_IMAGE, SCREEN_IMAGE__FROM_SAMPLE

    if (count($images) > 0) {

      // get the image from atus
      $image = file_get_contents(ATUS__REVERSE_PROXY_URL . '/data/' . $images[0]['releaseUID'] . '/' . $images[0]['fileName']);
      if (!$image) {
        throw new \Exception('Could not get image from ATUS');
      }

      $img = $this->resizeImage($image, $this->bitBucketFolder . '/t-' . $internalID . '-1.jpg');
      if (!$img) {
        throw new \Exception('Could not resize image');
      }

      $fullSizeImage = imagejpeg($img, $this->bitBucketFolder . '/f-' . $internalID . '-1.jpg', 85);
      if (!$fullSizeImage) {
        throw new \Exception('Could not save full size image');
      }

      ATUS::getDB()->prepare('UPDATE torrents SET numpics = 1 WHERE id = ? LIMIT 1')->execute([$internalID]);
    }
  }

  // ==============================================================================================  
  // NetVision uses custom functions to handle NFOs and images.
  // Due to the way they are implemented, we can't import them here and have to re-implement them.
  // you can remove all the functions below if you don't need them or replace them with your own.
  // ==============================================================================================

  // @ref https://github.com/SteffenLoges/netvision-tracker/blob/master/nvtracker-20060203/include/global.php#L828
  function resizeImage($tmpfile, $target_filename)
  {

    $img_pic = ImageCreateFromString($tmpfile);
    if (!$img_pic) {
      throw new \Exception('Could not create image from string');
    }

    $size_x = imagesx($img_pic);
    $size_y = imagesy($img_pic);

    $tn_size_x = 150;
    $tn_size_y = (int)((float)$size_y / (float)$size_x * (float)150);

    // create thumbnail
    $img_tn = imagecreatetruecolor($tn_size_x, $tn_size_y);
    imagecopyresampled($img_tn, $img_pic, 0, 0, 0, 0, $tn_size_x, $tn_size_y, $size_x, $size_y);

    // save thumbnail
    imagejpeg($img_tn, $target_filename, 85);

    imagedestroy($img_tn);

    // return gd image resource
    return $img_pic;
  }

  // ref https://github.com/SteffenLoges/netvision-tracker/blob/master/nvtracker-20060203/include/global.php#L939
  function generateNFOImage($nfotext, $target_filename)
  {

    // Make array of NFO lines and break lines at 80 chars
    $nfotext = preg_replace('/\r\n/', "\n", $nfotext);
    $lines = explode("\n", $nfotext);
    for ($I = 0; $I < count($lines); $I++) {
      $lines[$I] = chop($lines[$I]);
      $lines[$I] = wordwrap($lines[$I], 82, "\n", 1);
    }
    $lines = explode("\n", implode("\n", $lines));

    // Get longest line
    $cols = 0;
    for ($I = 0; $I < count($lines); $I++) {

      $lines[$I] = chop($lines[$I]);
      if (strlen($lines[$I]) > $cols) {
        $cols = strlen($lines[$I]);
      }
    }

    // Allow a maximum of 500 lines of text
    $lines = array_slice($lines, 0, 500);

    // Get line count
    $linecnt = count($lines);

    // Load font
    $font = imageloadfont(__DIR__ . "/../terminal.gdf");
    if ($font < 5) {
      throw new \Exception('Could not load font');
    }

    $imagewidth = $cols * imagefontwidth($font) + 1;
    $imageheight = $linecnt * imagefontheight($font) + 1;

    $nfoimage = imagecreate($imagewidth, $imageheight);
    $white = imagecolorallocate($nfoimage, 255, 255, 255);
    $black = imagecolorallocate($nfoimage, 0, 0, 0);

    for ($I = 0; $I < $linecnt; $I++) {
      imagestring($nfoimage, $font, 0, $I * imagefontheight($font), $lines[$I], $black);
    }

    return imagepng($nfoimage, $target_filename);
  }
}
