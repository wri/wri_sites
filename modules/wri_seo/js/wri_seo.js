/**
 * @file
 *
 * WRI GTM data.
 */
(function ($, drupalSettings) {

  // Get region values from current page
  function getRegions() {
    return $(".regions a").map(function () {
      return $(this).text();
    }).get().join(', ');
  }

  let dataLayer = window.dataLayer = window.dataLayer || [];
  // Check variables before using them. The "!= null" checks for null or undefined.
  if (drupalSettings.wri_seo != null && drupalSettings.wri_seo.node_type != null) {
    switch (drupalSettings.wri_seo.node_type) {
      // Article detail pages.
      case 'article':
        $(document).ready(function () {
          let articleSettings = drupalSettings.wri_seo.article_details
          articleSettings['insights region'] = getRegions();
          dataLayer.push(articleSettings);
        });
        break;

      // Project detail pages.
      case 'project_detail':
        // Regions are formatted at the field display level and as a result, not easy to
        // get from the backend.
        $(document).ready(function () {
          let projectSettings = drupalSettings.wri_seo.project_details;
          projectSettings['project region'] = getRegions();
          dataLayer.push(projectSettings);
        });
        break;
      default:
        dataLayer.push(drupalSettings.wri_seo.default_details);
        break;

    }
  }

  // Check for sitewide filter.
  if (drupalSettings.wri_filter != null && drupalSettings.wri_filter.currentFilterName != null) {
    let sitewideFilter = drupalSettings.wri_filter.currentFilterName;
    sitewideFilter = sitewideFilter.charAt(0).toUpperCase() + sitewideFilter.slice(1);
    dataLayer.push({
      "site filter": sitewideFilter,
    });
  }

})(jQuery, drupalSettings);
