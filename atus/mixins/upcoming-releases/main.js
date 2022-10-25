"use strict";

/**
 * Format bytes as human-readable text.
 *
 * @param bytes Number of bytes.
 * @param si True to use metric (SI) units, aka powers of 1000. False to use
 *           binary (IEC), aka powers of 1024.
 * @param dp Number of decimal places to display.
 *
 * @return Formatted string.
 */
// credits: https://stackoverflow.com/a/14919494
function bytesHumanReadable(bytes, si = false, dp = 1) {
  const thresh = si ? 1000 : 1024;

  if (Math.abs(bytes) < thresh) {
    return bytes + " B";
  }

  const units = si
    ? ["kB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"]
    : ["KiB", "MiB", "GiB", "TiB", "PiB", "EiB", "ZiB", "YiB"];
  let u = -1;
  const r = 10 ** dp;

  do {
    bytes /= thresh;
    ++u;
  } while (
    Math.round(Math.abs(bytes) * r) / r >= thresh &&
    u < units.length - 1
  );

  return bytes.toFixed(dp) + " " + units[u];
}

function dateHumanReadable(date) {
  return new Date(date).toLocaleString(undefined, {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "numeric",
    minute: "numeric",
    second: "numeric",
  });
}

// credits: https://stackoverflow.com/a/37096512
function secondsToHms(d) {
  d = Number(d);
  var h = Math.floor(d / 3600);
  var m = Math.floor((d % 3600) / 60);
  var s = Math.floor((d % 3600) % 60);

  var hDisplay = h > 0 ? h + "h" : "";
  var mDisplay = m > 0 ? m + "m" : "";
  var sDisplay = s > 0 ? s + "s" : "";
  return hDisplay + mDisplay + sDisplay;
}
// ------------------------------------------------------------------------------------------------

const query = [
  "state=downloading",
  `limit=${ATUS__UPCOMING_RELEASES__MAX_ENTRIES}`,
];

const url = ATUS__REVERSE_PROXY_URL + "/releases?" + query.join("&");

const tableEl = document.querySelector("#atus--upcoming-releases-table tbody");

if (!tableEl) {
  throw new Error("[ATUS Upcoming Releases] Table element not found.");
}

function loadUpcomingReleases() {
  fetch(url)
    .then((r) => r.json())
    .then((r) => renderResponse(r))
    .catch((e) => {
      console.error(e);
      tableEl.innerHTML = `<tr><td colspan="4">Error loading upcoming releases</td></tr>`;
    });
}

function renderResponse(r) {
  let relHTMLArr = (r.releases || [])
    .filter((r) => r.downloadState?.state === "STARTED") // only show releases that are currently downloading
    .map((r) => {
      const eta = secondsToHms(r.downloadState.eta || 0);

      const done = r.downloadState.done || 0;

      const dlRate = bytesHumanReadable(
        r.downloadState.downloadRate || 0,
        true,
        1
      );

      let html = `<tr>
        <td class="atus--category">${r.category}</td>
        <td>
          <div class="atus--name">${r.name}</div>
          <div class="atus--pre"><b>Pre:</b> ${dateHumanReadable(r.pre)}</div>
        </td>
        <td class="atus--download-status">
          <span class="atus--capitalize">${r.state.state.toLowerCase()}</span>`;

      if (eta) {
        html += ` @${dlRate}/s - ${eta} remaining`;
      }

      html += `<div class="atus--progress-bar">
            <div class="atus--progress-bar--progress" style="width: ${done}%"></div>
            <div class="atus--progress-bar--label">${done}%</div>
          </div>
        </td>
        <td class="atus--size">${bytesHumanReadable(r.size, false, 2)}</td>
      </tr>`;

      return html;
    });

  if (relHTMLArr.length === 0) {
    relHTMLArr = ['<tr><td colspan="4">No upcoming releases</td></tr>'];
  }

  tableEl.innerHTML = relHTMLArr.join("");
}

loadUpcomingReleases();

setInterval(loadUpcomingReleases, ATUS__UPCOMING_RELEASES__REFRESH_INTERVAL);
