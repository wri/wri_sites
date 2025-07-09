/**
 * @file
 *
 * TS Facet js
 */
export default function(context) {
  const $ = jQuery;

  $(".block-facets h3")
    .off("click")
    .on("click", function() {
      $(this)
        .parent()
        .toggleClass("open");
    });

  $(".block-facets input[type=checkbox]").each(function() {
    var el = $(this);
    if (el.is(":checked")) {
      el.closest(".facets-widget-checkbox").addClass("open");
    }
  });

  if ($(".button.filters").css("display") == "inline-block") {
    $(".facets-widget-checkbox").removeClass("open");
  }

  if ($(".block-facet-blocklanguages-spoken-taxonomy-term-name").length > 0) {
    $(
      ".block-facet-blocklanguages-spoken-taxonomy-term-name .facets-widget-checkbox"
    )
      .once()
      .removeClass("open");
  }

  $(".button.filters")
    .off("click")
    .on("click", function() {
      $(this)
        .closest(
          ".view-resources, .view-experts-staff, .view-events, .view-paying-for-paris-resources"
        )
        .toggleClass("open");
    });

  if ($(".resources-container [name=query]").val()) {
    $(".view-header .query").show();
    $(".view-header .query")
      .once()
      .append($(".resources-container [name=query]").val());
  }

  // Global facet limit across all blocks.
  const maxTotalSelections = 3;

  // Helper: counts total selected facets from both sets
  function getTotalFacetCount() {
    const checkboxCount = $(".block-facets input[type='checkbox']:checked").length;
    const linkCount = $(".facets-widget-links a[data-drupal-facet-item-id].is-active").length;
    return checkboxCount + linkCount;
  }

  // Show warning message
  function showFacetLimitWarning() {
    $(".facet-limit-warning").remove();

    const translatedMsg = Drupal.t('You can select up to @max filters total, including the topic.', {
      '@max': (maxTotalSelections),
    });

    const $msg = $(
      `<div class="facet-limit-warning" style="color: #cd4443; font-weight: bold; margin-bottom: 1em;">
        ${translatedMsg}
      </div>`
    );

    $(".view-filters").append($msg);

    $("html, body").animate({
      scrollTop: $(".view-filters").offset().top - 20,
    }, 300);
  }

  // Handle checkbox facets
  $(".facets-widget-checkbox input[type='checkbox']", context).each(function () {
    const $checkbox = $(this);

    $checkbox.off("click.limit").on("click.limit", function (e) {
      const checkedCount = getTotalFacetCount();
      const willBeChecked = $checkbox.prop("checked");

      if (willBeChecked && checkedCount > maxTotalSelections) {
        e.preventDefault();
        e.stopImmediatePropagation();
        $checkbox.prop("checked", false);
        showFacetLimitWarning();
      }
    });
  });

  // Handle link facets
  $(".facets-widget-links a[data-drupal-facet-item-id]", context).each(function () {
    const $link = $(this);

    // Only intercept inactive links (i.e., not already selected)
    if (!$link.hasClass("is-active")) {
      $link.off("click.limit").on("click.limit", function (e) {
        const activeLinkCount = $(".facets-widget-links a[data-drupal-facet-item-id].is-active").length;
        const checkboxCount = $(".block-facets input[type='checkbox']:checked").length;

        const willExceedLimit = activeLinkCount === 0 && checkboxCount >= maxTotalSelections;

        if (willExceedLimit) {
          e.preventDefault();
          e.stopImmediatePropagation();
          showFacetLimitWarning();
        }
      });
    }
  });

}
