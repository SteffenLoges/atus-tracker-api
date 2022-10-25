<?php


require_once __DIR__ . '/../../config.php';

$content =
  '<table border="1" cellspacing="0" cellpadding="5" id="atus--upcoming-releases-table">
  <thead>
    <tr>
      <td class="colhead tablecat" align="center" style="width: 130px;">Category</td>
      <td class="colhead tablecat">Release</td>
      <td class="colhead tablecat" style="width: 300px;">Status</td>
      <td class="colhead tablecat" style="width: 100px;">Size</td>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td colspan="4">Loading...</td>
    </tr>
  </tbody>
</table>
<script src="atus/mixins/upcoming-releases/main.js" defer></script>
<script>
  var ATUS__REVERSE_PROXY_URL = "' . ATUS__REVERSE_PROXY_URL . '";
  var ATUS__UPCOMING_RELEASES__MAX_ENTRIES = ' . ATUS__UPCOMING_RELEASES__MAX_ENTRIES . ';
  var ATUS__UPCOMING_RELEASES__REFRESH_INTERVAL = ' . ATUS__UPCOMING_RELEASES__REFRESH_INTERVAL . ';
</script>
<link rel="stylesheet" href="atus/mixins/upcoming-releases/main.css" />';


$printHTML = false;
if (!isset($HTMLOUT)) {
  $HTMLOUT = '';
  $printHTML = true;
}

$containerTitle = 'Upcoming Releases';

if (ATUS__DERIVED_FROM === 'TBDEV') {

  $HTMLOUT .=
    '<div style="text-align:left;width:80%;border:1px solid blue;padding:5px;">
      <div style="background:lightgrey;height:25px;">
        <span style="font-weight:bold;font-size:12pt;">' . $containerTitle . '</span>
      </div>
      <br />
      ' . $content . '
    </div>
    <br />';
} else {

  $HTMLOUT .=
    '<table cellpadding="4" cellspacing="1" border="0" style="width:100%" class="tableinborder">
      <tr class="tabletitle" width="100%">
        <td colspan="10" width="100%">
          <span class="normalfont">
            <center><b>' . $containerTitle . '</b></center>
          </span>
        </td>
      </tr>
      <tr>
        <td width="100%" class="tablea">' . $content . '</td>
      </tr>
    </table>
    <br>';
}

if ($printHTML) {
  echo $HTMLOUT;
}
