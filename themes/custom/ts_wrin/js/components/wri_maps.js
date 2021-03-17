/**
 * @file
 *
 * WRI Maps
 */
export default function(context) {
  const $ = jQuery;

  $(".wri-region-map", context).each(function() {
    let $map = $(this);
    let nids = [];

    $map.find("svg > g").each(function() {
      let matches = $(this)
        .attr("id")
        .match(/^node-(\d+)/, "");
      if (matches && matches.length > 1) {
        nids.push(matches[1]);
      }
    });

    if (nids.length) {
      $.ajax("/wri_maps/region-map/json", {
        data: {
          nids: nids
        },
        success: function(result) {
          $.each(result, function(nid, data) {
            $map
              .find("svg > g[id='node-" + nid + "']")
              .addClass(data.type)
              .attr("aria-label", data.title)
              .attr("tabindex", "0")
              .on("click keypress", function(event) {
                if (event.type == "click" || event.keyCode == 13) {
                  if ($(window).width() < 768) {
                    // Open region map popup on small screens.
                    $map.find(".wri-region-map-popup-button").trigger("click");
                    $map.find(".wri-region-map-popup").html(data.popup);
                  } else {
                    // Go directly to the node on larger screens.
                    window.location = data.url;
                  }
                }
              });
          });
        }
      });
    }
  });
}
