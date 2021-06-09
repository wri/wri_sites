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
        .closest(".view-resources, .view-experts-staff, .view-events")
        .toggleClass("open");
    });

  if ($(".resources-container [name=query]").val()) {
    $(".view-header .query").show();
    $(".view-header .query")
      .once()
      .append($(".resources-container [name=query]").val());
  }
}
