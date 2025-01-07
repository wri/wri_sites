/**
 * @file
 *
 * TS External Links
 */

export default function(context) {
  const $ = jQuery;
  const svgIcon =
    '<svg xmlns="http://www.w3.org/2000/svg" class="offsite-link" width="12" height="11.3" fill="none" viewBox="0 0 17 16"><path fill="currentColor" d="M2.278 16c-.49 0-.908-.174-1.256-.522A1.712 1.712 0 0 1 .5 14.222V1.778c0-.49.174-.908.522-1.256A1.712 1.712 0 0 1 2.278 0H8.5v1.778H2.278v12.444h12.444V8H16.5v6.222c0 .49-.174.908-.522 1.256a1.712 1.712 0 0 1-1.256.522H2.278Zm4.178-4.711L5.21 10.044l8.267-8.266h-3.2V0H16.5v6.222h-1.778v-3.2L6.456 11.29Z"/></svg>';


  // Loop over all external links.
  $("a[href^=http]", context).each(function() {
    // If the url doesn't contain the hostname treat it as external.
    // Also count if it is an external sharing url that contains the domain as a parameter.
    let externalMatch = this.href.indexOf(location.hostname);
    if (externalMatch === -1 || externalMatch > 8) {
      let $this = $(this);
      // Open link in new page.
      $this.attr("target", "_blank");
      // If this is a button add class to indicate that it is offsite.
      if ($this.hasClass("button")) {
        $this.addClass("offsite");
      } else {
        $this.append(svgIcon);
      }
    }
  });

  // Loop over all file links. If they are not images open as new window.
  $("a[href*='/sites/default/files']", context).each(function() {
    let $this = $(this);
    if (
      $this.attr("href").search(".png") === -1 &&
      $this.attr("href").search(".jpg") === -1
    ) {
      // Open link in new page.
      $this.attr("target", "_blank");
      // If this is a button add class to indicate that it is offsite.
      if ($this.hasClass("button")) {
        $this.addClass("offsite");
      }
    }
  });

  // Loop over all links with target="_blank". Add an svg to indicate they will open in a new window.
  $("a[target='_blank']", context).each(function() {
    let $this = $(this);
    if ($this.hasClass("button")) {
    }
    else {
      $this.append(svgIcon);
    }
  });
}
