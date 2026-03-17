/**
 * @file
 * Populate the `returnTo` query param on hub links at runtime.
 *
 * We intentionally leave `returnTo` empty in PHP to avoid cached/stale values.
 */

(function (Drupal, once) {
  Drupal.behaviors.wriSpokeReturnTo = {
    attach(context) {
      // Full URL of the current page, without the hash.
      const currentUrl = window.location.origin + window.location.pathname + window.location.search;

      once("wri-spoke-return-to", 'a[href*="returnTo"]', context).forEach((link) => {
        const href = link.getAttribute("href");
        if (!href) return;

        let url;
        try {
          // Support relative and absolute URLs.
          url = new URL(href, window.location.origin);
        } catch (e) {
          return;
        }

        // Only update links that already have `returnTo` in the query string.
        if (!url.searchParams.has("returnTo")) return;

        url.searchParams.set("returnTo", currentUrl);

        // Preserve relative URLs if the original was relative.
        const isAbsolute = /^[a-zA-Z][a-zA-Z\d+\-.]*:/.test(href);
        link.setAttribute("href", isAbsolute ? url.toString() : url.pathname + url.search + url.hash);
      });
    },
  };
})(Drupal, once);
