<?php

function getImagesHTML($metaFiles, $styles = [])
{
  $html = '';

  $stylesComputed = array_map(function ($k, $v) {
    return "$k:$v";
  }, array_keys($styles), $styles);


  foreach ($metaFiles as $metaFile) {
    $filename = htmlspecialchars($metaFile['fileName']);

    $url = ATUS__REVERSE_PROXY_URL . '/data/' . $metaFile['releaseUID'] . '/' . $filename;

    $html .= '<div style="' . implode(';', $stylesComputed) . '">';

    $html .=
      '<a href="' . $url . '" target="_blank">
        <img style="max-width: 100%;max-height:400px;" src="' . $url . '" alt="' . $filename . '" />
      </a>';

    // Uncomment to show some informations about this image that might be useful
    // if (isset($metaFile['info'])) {
    //   $html .= '<small>' . implode('<br>', array_map(function ($k, $v) {
    //     return '<b>' . htmlspecialchars($k) . '</b>: ' . htmlspecialchars($v);
    //   }, array_keys($metaFile['info']), $metaFile['info'])) . '</small>';
    // }

    $html .= '</div>';
  }


  return $html;
}
