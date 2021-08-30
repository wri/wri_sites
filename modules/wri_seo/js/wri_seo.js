/**
 * @file
 *
 * WRI GTM data.
 */
(function ($, drupalSettings) {

  let dataLayer = window.dataLayer = window.dataLayer || [];
  // Check variables before using them. The "!= null" checks for null or undefined.
  if (drupalSettings.wri_seo != null && drupalSettings.wri_seo.node_type != null) {
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

  // Check for sitewide filter.
  if (drupalSettings.wri_filter != null && drupalSettings.wri_filter.currentFilterName != null) {
    let sitewideFilter = drupalSettings.wri_filter.currentFilterName;
    sitewideFilter = sitewideFilter.charAt(0).toUpperCase() + sitewideFilter.slice(1);
    dataLayer.push({
      "site filter": sitewideFilter,
    });
  }

})(jQuery, drupalSettings);
