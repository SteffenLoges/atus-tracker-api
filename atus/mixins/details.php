<?php

if (!isset($row['id'])) {
  die('atus could not find the requested torrent.');
}

require_once __DIR__ . '/../config.php';

$atusReleaseFile = __DIR__ . '/../data/' . $row['id'] . '.json';
if (file_exists($atusReleaseFile)) {
  require_once __DIR__ . '/video.php';
  require_once __DIR__ . '/images.php';
  $atusRelease = json_decode(file_get_contents($atusReleaseFile), true);

  // tbdev stores html in $HTMLOUT, netvision echo's immediately
  $printHTML = false;
  if (!isset($HTMLOUT)) {
    $HTMLOUT = '';
    $printHTML = true;
  }

  // =====================================================
  // == comment out the sections you don't want to show ==
  // =====================================================


  // -- pre info ----------------------------------------------------------------------------------
  if (isset($atusRelease['pre'])) {
    // We display the time "as is" in UTC+0. Depending on your timezone, you might want to change this.
    $HTMLOUT .= tr("Pre", $atusRelease['pre']);
  }


  // -- sample video ------------------------------------------------------------------------------
  $sampleVideos = array_values(array_filter($atusRelease['metaFiles'], function ($metaFile) {
    return $metaFile['type'] === 'SAMPLE_VIDEO' && $metaFile['state'] === 'PROCESSED';
  }));

  if (count($sampleVideos) > 0) {
    $HTMLOUT .= tr("Sample", getVideoHTML($sampleVideos), true);
  }


  // -- in release included screenshots -----------------------------------------------------------
  $screenImages = array_values(array_filter($atusRelease['metaFiles'], function ($metaFile) {
    return $metaFile['type'] === 'SCREEN_IMAGE' && $metaFile['state'] === 'PROCESSED';
  }));

  if (count($screenImages) > 0) {
    $HTMLOUT .= tr("Screenshots", getImagesHTML($screenImages, ['width' => '640px', 'margin-bottom' => '10px']), true);
  }


  // -- self generated sample screenshots ---------------------------------------------------------
  $showSelfGeneratedScreenImages = count($screenImages) === 0;

  // uncomment this line to hide self generated screenshots if the release has screenshots
  $showSelfGeneratedScreenImages = true;

  if ($showSelfGeneratedScreenImages) {
    $screenImagesGenerated = array_values(array_filter($atusRelease['metaFiles'], function ($metaFile) {
      return $metaFile['type'] === 'SCREEN_IMAGE__FROM_SAMPLE' && $metaFile['state'] === 'PROCESSED';
    }));

    if (count($screenImagesGenerated) > 0) {
      $screenImagesGeneratedHTML = getImagesHTML($screenImagesGenerated, ['width' => '640px', 'margin-bottom' => '10px']);
      $screenImagesGeneratedHTML .= '<small>*Screenshots automatically extracted from the included sample video.</small>';
      $HTMLOUT .= tr("Screenshots", $screenImagesGeneratedHTML, true);
    }
  }


  // -- cover, proof and other included images ----------------------------------------------------
  $images = array_values(array_filter($atusRelease['metaFiles'], function ($metaFile) {
    return in_array($metaFile['type'], ['SOURCE_IMAGE', 'IMAGE', 'PROOF_IMAGE']) && $metaFile['state'] === 'PROCESSED';
  }));

  $sumImages = count($images);
  if (count($images) > 0) {
    $imageWidth = $sumImages === 1 ? '640px' : '250px';
    $HTMLOUT .= tr("Images", getImagesHTML($images, ['display' => 'inline-block', 'width' => $imageWidth, 'margin' => '5px']), true);
  }


  // echo out the html if we're not in tbdev
  if ($printHTML) {
    echo $HTMLOUT;
  }
}
