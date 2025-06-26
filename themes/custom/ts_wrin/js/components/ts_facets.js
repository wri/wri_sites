/**
 * @file
 *
 * TS Facet js
 */
export default function(context) {
  const $ = jQuery;
  const maxTotalSelections = 4;

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
  $(".facets-widget-checkbox input[type='checkbox']", context).each(function () {
    const $checkbox = $(this);

    $checkbox.off("click.limit").on("click.limit", function (e) {
      // Total selected *before* this one toggles.
      const checkedCount = $(".block-facets input[type='checkbox']:checked").length;
      const willBeChecked = $checkbox.prop("checked");

      if (willBeChecked && checkedCount > maxTotalSelections) {
        e.preventDefault();
        e.stopImmediatePropagation();

        $(".facet-limit-warning").remove();

        const translatedMsg = Drupal.t('You can select up to @max filters total.', {
          '@max': maxTotalSelections,
        });
        const $msg = $(
          `<div class="facet-limit-warning" style="color: red; font-weight: bold; margin-bottom: 1em;">
            ${translatedMsg}
          </div>`
        );

        $(".view-filters").append($msg);
        $("html, body").animate({
            scrollTop: $(".view-filters").offset().top - 20,
          }, 300);
      }
    });
  });
}
