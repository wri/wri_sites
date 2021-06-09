/**
 * @file
 *
 * WRI GTM data.
 */
(function ($, drupalSettings) {

  if (typeof drupalSettings.wri_seo.node_type !== 'undefined' || drupalSettings.wri_seo.node_type !== null) {
    let dataLayer = window.dataLayer = window.dataLayer || [];

    switch (drupalSettings.wri_seo.node_type) {
    // Article detail pages.
      case 'article':
      $(document).ready(function () {
        let articleSettings = drupalSettings.wri_seo.article_details
        let regions = $(".regions a").map(function () {
          return $(this).text();
        }).get().join(', ');
        articleSettings['insights region'] = regions;
        dataLayer.push(articleSettings);
      });

      break;

      // Project detail pages.
      case 'project_detail':
      // Regions are formatted at the field display level and as a result, not easy to
      // get from the backend.
      $(document).ready(function () {
        let regions = $(".regions a").map(function () {
          return $(this).text();
        }).get().join(', ');
        dataLayer.push({
          "project topic": drupalSettings.wri_seo.project_details.topic ,
          "project region": regions,
        });
      });
      break;
    }
  }

  // Check for sitewide filter
  if (typeof drupalSettings.wri_filter.currentFilterName !== 'undefined' || drupalSettings.wri_filter.currentFilterName !== null) {
    let sitewideFilter = drupalSettings.wri_filter.currentFilterName;
    sitewideFilter = sitewideFilter.charAt(0).toUpperCase() + sitewideFilter.slice(1)
    dataLayer.push({
      "site filter": sitewideFilter,
    });
  }

})(jQuery, drupalSettings);
