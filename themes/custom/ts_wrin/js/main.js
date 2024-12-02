/**
 * @file
 * Main theme behaviors.
 */
import tsHeaderNav from "./components/ts_header_nav.js";
import tsTabs from "./components/ts_tabs.js";
import tsTrayNav from "./components/ts_tray_nav.js";
import tsButtonSvg from "./components/ts_button_svg.js";
import tsForms from "./components/ts_forms.js";
import tsFacets from "./components/ts_facets.js";
import wriMaps from "./components/wri_maps.js";
import wriModals from "./components/wri_modals.js";
import wriMoreOn from "./components/wri_more_on.js";
import wriMedia from "./components/wri_media.js";
import wriCtaBanners from "./components/wri_cta_banners.js";
import tsExternalLinks from "./components/ts_external_links.js";
import wriDatetime from "./components/wri_datetime.js";

(($, Drupal) => {
  /**
   * This calls the tsHamburger Nav behavior into the main.js global file.
   **/
  Drupal.behaviors.tsHeaderNav = {
    attach: tsHeaderNav
  };

  Drupal.behaviors.tsTabs = {
    attach: tsTabs
  };

  Drupal.behaviors.tsTrayNav = {
    attach: tsTrayNav
  };

  Drupal.behaviors.tsButtonSvg = {
    attach: tsButtonSvg
  };

  Drupal.behaviors.tsForms = {
    attach: tsForms
  };

  Drupal.behaviors.tsFacets = {
    attach: tsFacets
  };

  Drupal.behaviors.wriMaps = {
    attach: wriMaps
  };

  Drupal.behaviors.wriModals = {
    attach: wriModals
  };

  Drupal.behaviors.wriMoreOn = {
    attach: wriMoreOn
  };

  Drupal.behaviors.wriCtaBanners = {
    attach: wriCtaBanners
  };

  Drupal.behaviors.wriMedia = {
    attach: wriMedia
  };

  Drupal.behaviors.tsExternalLinks = {
    attach: tsExternalLinks
  };

  Drupal.behaviors.wriDatetime = {
    attach: wriDatetime
  };
})(jQuery, Drupal);
