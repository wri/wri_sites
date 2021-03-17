/**
 * @file
 *
 * TS External Links
 */

export default function(context) {
  const $ = jQuery;

  let links = 0;
  // Loop over all external links.
  $("a[href^=http]", context).each(function() {
    // If the url doesn't contain the hostname treat it as external.
    // Also count if it is an external sharing url that contains the domain as a parameter.
    let match = this.href.indexOf(location.hostname);
    if (match === -1 || match > 8) {
      let $this = $(this);
      // Open link in new page.
      $this.attr("target", "_blank");
      // If this is a button add class to indicate that it is offsite.
      if ($this.hasClass("button")) {
        $this.addClass("offsite");
      }
    }
  });
}
