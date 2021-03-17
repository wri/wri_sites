/**
 * @file
 *
 * WRI Modals handling that turns overlays into bottom bars on mobile.
 */
export default function(context) {
  const $ = jQuery;
  function modalType(windowWidth) {
    if (windowWidth <= 768) {
      $(".spb-popup-main-wrapper")
        .not(".spb_bottom_bar")
        .each(function() {
          let $this = $(this);
          $this.attr("data-class-orig", $this.attr("class"));
          $this.attr("data-style-orig", $this.attr("style"));
          $this.attr("style", "");
          $this.attr("class", "spb-popup-main-wrapper spb_bottom_bar");
          $this
            .parent(".spb_overlay")
            .addClass("has_overlay")
            .removeClass("spb_overlay");
          // startTheScroll.
          $("body").css({
            overflow: ""
          });
        });
    } else {
      $("[data-class-orig]").each(function() {
        let $this = $(this);
        $this.attr("class", $(this).attr("data-class-orig"));
        $this.attr("style", $(this).attr("data-style-orig"));
        $this
          .parent(".has_overlay", $this)
          .addClass("spb_overlay")
          .removeClass(".has_overlay");
        $("body").css({
          overflow: "hidden"
        });
      });
    }
  }

  function debounce(func, wait, immediate) {
    var timeout;
    return function() {
      var context = this,
        args = arguments;
      var later = function() {
        timeout = null;
        if (!immediate) func.apply(context, args);
      };
      var callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
      if (callNow) func.apply(context, args);
    };
  }

  var debouncedModals = debounce(function() {
    let windowSize = function() {
      return window.innerWidth;
    };
    modalType(windowSize());
  }, 250);

  window.addEventListener("resize", debouncedModals);
  debouncedModals();
}
