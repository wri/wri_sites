/**
 * @file
 *
 * TS External Links
 */

export default function (context) {
  const $ = jQuery;
  const svgIcon =
    '<svg xmlns="http://www.w3.org/2000/svg" class="offsite-link" width="12" height="11.3" fill="none" viewBox="0 0 17 16"><path fill="currentColor" d="M2.278 16c-.49 0-.908-.174-1.256-.522A1.712 1.712 0 0 1 .5 14.222V1.778c0-.49.174-.908.522-1.256A1.712 1.712 0 0 1 2.278 0H8.5v1.778H2.278v12.444h12.444V8H16.5v6.222c0 .49-.174.908-.522 1.256a1.712 1.712 0 0 1-1.256.522H2.278Zm4.178-4.711L5.21 10.044l8.267-8.266h-3.2V0H16.5v6.222h-1.778v-3.2L6.456 11.29Z"/></svg>';

  // Track links that open in new windows
  const linksToDecorate = new Set();

  // External links
  $("a[href^=http]", context).each(function () {
    const $this = $(this);
    const href = $this.attr("href") || "";
    const isInternal =
      href.indexOf(location.hostname) >= 0 &&
      href.indexOf(location.hostname) <= 8;

    // Skip .site-logo links
    if ($this.hasClass("site-logo")) return;

    if (!isInternal) {
      if (!$this.attr("target")) {
        $this.attr("target", "_blank");
      }
      linksToDecorate.add(this);

      if ($this.hasClass("button")) {
        $this.addClass("offsite");
      }
    }
  });

  // File links (not images)
  $("a[href*='/sites/default/files']", context).each(function () {
    const $this = $(this);
    const href = $this.attr("href") || "";

    if (!/\.(png|jpe?g|webp|svg)$/i.test(href)) {
      if (!$this.attr("target")) {
        $this.attr("target", "_blank");
      }
      linksToDecorate.add(this);

      if ($this.hasClass("button")) {
        $this.addClass("offsite");
      }
    }
  });

  // Add *all* links with a target attribute
  $("a[target]", context).each(function () {
    linksToDecorate.add(this);
  });

  // Add SVG icon to links in #main-content that open in new windows
  $("#main-content a", context).each(function () {
    if (
      this.hasAttribute("target") &&
      !this.classList.contains("button") &&
      !$(this).find("svg.offsite-link").length
    ) {
      $(this).append(svgIcon);
    }
  });
}
