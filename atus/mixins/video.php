<?php

// metaFiles will always be an array with a length of 1
function getVideoHTML($metaFiles)
{
  $html = '<link href="atus/3rd-party/video-js-7.20.2/video-js.css" rel="stylesheet" />';

  // If you'd like to support IE8 (for Video.js versions prior to v7)
  $html .= '<script src="atus/3rd-party/video-js-7.20.2/videojs-ie8.min.js"></script>';

  $src = ATUS__REVERSE_PROXY_URL . '/data/' . $metaFiles[0]['releaseUID'] . '/' . htmlspecialchars($metaFiles[0]['fileName']);

  $html .=
    '<video id="my-video" class="video-js" controls preload="auto" width="640" height="360" data-setup="{ }">
      <source src="' . $src . '" type="application/dash+xml">
      <p class="vjs-no-js">
        To view this video please enable JavaScript, and consider upgrading to a
        web browser that
        <a href="https://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a>
      </p>
    </video>
    <script src="atus/3rd-party/video-js-7.20.2/video.min.js"></script>';

  // We have some more informations you might want to show to the user
  // uncomment the following lines and take a look :)
  // $html .= '<small>' . implode('<br>', array_map(function ($k, $v) {
  //   return '<b>' . htmlspecialchars($k) . '</b>: ' . htmlspecialchars($v);
  // }, array_keys($metaFiles[0]['info']), $metaFiles[0]['info'])) . '</small>';

  return $html;
}
