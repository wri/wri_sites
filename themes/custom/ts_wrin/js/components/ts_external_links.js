/**
 * @file
 *
 * TS External Links
 */

export default function(context) {
  const $ = jQuery;

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
