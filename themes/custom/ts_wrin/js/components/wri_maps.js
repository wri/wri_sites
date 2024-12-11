/**
 * @file
 *
 * WRI Maps with SVG loading and resize debounce.
 */
export default function (context) {
  const $ = jQuery;

  // SVG loading logic with debounce.
  const mapContainer = document.getElementById('interactive-map');
  const svgUrl = mapContainer?.getAttribute('data-svg-url');

  let debounceTimer;

  function loadSVG() {
    if (!mapContainer || !svgUrl) {
      return;
    }

    if (window.innerWidth >= 765 && !mapContainer.dataset.loaded) {
      fetch(svgUrl)
        .then((response) => {
          if (response.ok) {
            return response.text();
          }
          throw new Error('SVG could not be loaded.');
        })
        .then((svgContent) => {
          mapContainer.innerHTML = svgContent;
          mapContainer.dataset.loaded = true;
          processRegionMapNodes(context);
        })
        .catch((error) => {
          console.error(error);
        });
    }
  }

  function debounceResize() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadSVG, 200);
  }

  // Function to process .wri-region-map nodes.
  function processRegionMapNodes(context) {
    $(".wri-region-map", context).each(function () {
      let $map = $(this);
      let nids = [];

      $map.find("svg > g").each(function () {
        let matches = $(this).attr("id").match(/^node-(\d+)/, "");
        if (matches && matches.length > 1) {
          nids.push(matches[1]);
        }
      });

      if (nids.length) {
        $.ajax("/wri_maps/region-map/json", {
          data: { nids: nids },
          success: function (result) {
            $.each(result, function (nid, data) {
              $map
                .find("svg > g[id='node-" + nid + "']")
                .addClass(data.type)
                .attr("aria-label", data.title)
                .attr("tabindex", "0")
                .on("click keypress", function (event) {
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
          },
        });
      }
    });
  }

  // Initial SVG loading and setup.
  loadSVG();

  // Add debounce for window resize.
  window.addEventListener("resize", debounceResize);

  // Ensure node processing on AJAX or content updates.
  processRegionMapNodes(context);
}
