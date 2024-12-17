/**
 * @file
 *
 * TS External Links
 */

export default function(context) {
  const $ = jQuery;

  const svgIcon =
    '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="11.3" fill="none" viewBox="0 0 17 16"><path fill="currentColor" d="M2.278 16c-.49 0-.908-.174-1.256-.522A1.712 1.712 0 0 1 .5 14.222V1.778c0-.49.174-.908.522-1.256A1.712 1.712 0 0 1 2.278 0H8.5v1.778H2.278v12.444h12.444V8H16.5v6.222c0 .49-.174.908-.522 1.256a1.712 1.712 0 0 1-1.256.522H2.278Zm4.178-4.711L5.21 10.044l8.267-8.266h-3.2V0H16.5v6.222h-1.778v-3.2L6.456 11.29Z"/></svg>';

  // Loop over all external links.
  $("a[href^=http]", context).each(function() {
    let $this = $(this);
    // If the url doesn't contain the hostname treat it as external.
    // Also count if it is an external sharing url that contains the domain as a parameter.
    let externalMatch = this.href.indexOf(location.hostname);
    if (externalMatch === -1 || externalMatch > 8) {
      // Open link in new page.
      $this.attr("target", "_blank");
      // If this is a button add class to indicate that it is offsite.
      if ($this.hasClass("button")) {
        $this.addClass("offsite");
      } else if (
        $this.attr("target") === "_blank" &&
        !$this.hasClass("button")
      ) {
        // Ensure the link is within #main-content and does not contain HTML.
        if (
          $this.closest("#main-content").length > 0 &&
          $this.contents().filter(function() {
            return this.nodeType === Node.ELEMENT_NODE;
          }).length === 0
        ) {
          if (!$this.html().includes(svgIcon)) {
            $this.append("&nbsp;" + svgIcon);
          }
        }
      }
    }
  });

  // Loop over internal links that have target="_blank", e.g. projects.
  $("#main-content a[href^='/']", context).each(function() {
    let $this = $(this);
    if ($this.attr("target") === "_blank" && !$this.hasClass("button")) {
      if (!$this.html().includes(svgIcon)) {
        $this.append("&nbsp;" + svgIcon);
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
}
