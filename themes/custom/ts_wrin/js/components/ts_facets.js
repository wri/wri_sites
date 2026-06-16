/**
 * @file
 *
 * TS Facet js
 */
export default function (context) {
  const $ = jQuery;
  $(".button.filters")
    .off("click")
    .on("click", function (e) {
      e.preventDefault();
      $(this)
        .closest(
          ".view-resources, .view-experts-staff, .view-events, .view-paying-for-paris-resources",
        )
        .toggleClass("open");
    });

  if ($(".resources-container [name=query]").val()) {
    $(".view-header .query").show();
    $(".view-header .query")
      .once()
      .append($(".resources-container [name=query]").val());
  }
}
