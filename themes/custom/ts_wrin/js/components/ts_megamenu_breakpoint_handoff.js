/**
 * @file
 * Keeps an open mega menu panel open across the desktop/mobile breakpoint.
 *
 * Listens for the breakpoint crossing via matchMedia and hands state
 * between ts_megamenu.js (desktop drilldown) and ts_megamenu_mobile.js
 * (mobile sliding-panel overlay) through the small APIs each exposes:
 *   - window.WRIMegaMenuDesktop.getOpenIndex() / .openIndex(i) / .closeAllPanels()
 *   - window.WRIMegaMenuMobile.getOpenTopLevelIndex() / .openAtTopLevelIndex(i) / .closeOverlay()
 *
 * "Index" is position among the .paragraph--type--submenu elements —
 * both files iterate them in that same order, so no translation is
 * needed between the two systems.
 *
 * Scope: only which top-level section (Explore, Research & Data, etc.)
 * is open carries across a crossing, not exact flyout/drill depth.
 * Focus is left wherever it is; the handoff doesn't move it.
 */
(function (Drupal, once) {
  "use strict";

  var mq = window.matchMedia("(min-width: 1024px)");

  Drupal.behaviors.wriMegaMenuBreakpointHandoff = {
    attach: function (context) {
      once("wri-megamenu-breakpoint-handoff", "body", context).forEach(
        function () {
          mq.addEventListener("change", handleCrossing);
        },
      );
    },
  };

  function handleCrossing(event) {
    var desktop = window.WRIMegaMenuDesktop;
    var mobile = window.WRIMegaMenuMobile;
    if (!desktop || !mobile) {
      return; // one of the two behaviors hasn't attached (e.g. no .wri-megamenu on this page) — nothing to hand off
    }

    if (event.matches) {
      // Crossed from mobile width to desktop width.
      var mobileOpenIndex = mobile.getOpenTopLevelIndex();
      mobile.closeOverlay();
      if (mobileOpenIndex !== null) {
        desktop.openIndex(mobileOpenIndex);
      }
    } else {
      // Crossed from desktop width to mobile width.
      var desktopOpenIndex = desktop.getOpenIndex();
      desktop.closeAllPanels();
      if (desktopOpenIndex !== null) {
        mobile.openAtTopLevelIndex(desktopOpenIndex);
      }
    }
  }
})(Drupal, once);
