/**
 * @file
 * TS Hamburger Menu custom JS.
 */

export default function(context) {
  // Alias global jQuery object.
  const $ = jQuery;
  let tabbingContext = null;
  if (context == document) {
    // Hamburger Nav.
    function toggleMenu() {
      var toggle = $(".menu-toggle");
      var target = $(".mobile-menu-target");
      var headerNav = $(".header-wrapper");
      toggle.toggleClass("active");
      if (toggle.hasClass("active")) {
        headerNav.addClass("menu-open");
        toggle.attr("aria-label", "Close mobile menu");
      } else {
        headerNav.removeClass("menu-open");
        toggle.attr("aria-label", "Open mobile menu");
      }
      target.toggleClass("expanded");
    }
    // Mobile menu toggle behavior.
    $(".menu-toggle", context)
      .once("ts-menu-toggle")
      .on("click", function(e) {
        toggleMenu();
        $("body").addClass("fixed");
      })
      .keyup(function(e) {
        if (e.keyCode == 27) {
          // escape key maps to keycode `27`
          toggleMenu();
          $(this).blur();
          $("body").removeClass("fixed");
          sliderCleanUp();
        }
      });
    $(".mega_menu_close")
      .once()
      .on("click", function(e) {
        toggleMenu();
        $("body").removeClass("fixed");
        sliderCleanUp();
      });

    // Mega-Menu sliders.
    var hamburgerContent = $(".hamburger-content"),
      hamburgerSlider = $(".hamburger-slider"),
      ourWorkSubmenu = $(".our-work-submenu"),
      aboutUsSubmenu = $(".about-us-submenu"),
      ourApproachSubmenu = $(".our-approach-submenu"),
      joinUsSubmenu = $(".join-us-submenu");

    function slideOut(menuName) {
      menuName.addClass("active");
      hamburgerContent.addClass("left");
      hamburgerSlider.addClass("active");
    }

    function sliderCleanUp() {
      hamburgerContent.removeClass("left");
      hamburgerSlider.removeClass("active");
      setTimeout(function() {
        ourWorkSubmenu.removeClass("active");
        aboutUsSubmenu.removeClass("active");
        ourApproachSubmenu.removeClass("active");
        joinUsSubmenu.removeClass("active");
      }, 500);
    }

    function sliderMenus(windowWidth) {
      if (windowWidth <= 768) {
        var burger = $(".hamburger-content");
        burger
          .find(".menu--footer-secondary > ul > li > .menu-item-title")
          .on("click", function(e) {
            e.preventDefault();
            slideOut(ourWorkSubmenu);
          });
        burger
          .find(".menu--mega-menu > ul > li:nth-child(1) > .menu-item-title")
          .on("click", function(e) {
            e.preventDefault();
            slideOut(aboutUsSubmenu);
          });
        burger
          .find(".menu--mega-menu > ul > li:nth-child(2) > .menu-item-title")
          .on("click", function(e) {
            e.preventDefault();
            slideOut(ourApproachSubmenu);
          });
        burger
          .find(".menu--mega-menu > ul > li:nth-child(3) > .menu-item-title")
          .on("click", function(e) {
            e.preventDefault();
            slideOut(joinUsSubmenu);
          });
        // Close the sliders, then reset.
        $(".hamburger-slider .back").on("click", function(e) {
          e.preventDefault();
          sliderCleanUp();
        });
      } else {
        $(
          ".hamburger-content .menu--footer-secondary > ul > li > a, \
          .hamburger-content .menu--mega-menu > ul > li:nth-child(1) > a, \
          .hamburger-content .menu--mega-menu > ul > li:nth-child(2) > a, \
          .hamburger-content .menu--mega-menu > ul > li:nth-child(3) > a"
        ).unbind();
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

    var debouncedSliderMenus = debounce(function() {
      let windowSize = function() {
        return window.innerWidth;
      };
      sliderMenus(windowSize());
    }, 250);

    window.addEventListener("resize", debouncedSliderMenus);
    debouncedSliderMenus();

    // Sticky Nav.
    const stickyNav = document.querySelector("header");

    function stickyScroll() {
      let st = window.pageYOffset || document.documentElement.scrollTop;
      if (st >= 30) {
        stickyNav.classList.add("sticky");
      } else {
        stickyNav.classList.remove("sticky");
      }
    }

    var debouncedStickyNav = debounce(function() {
      window.removeEventListener("scroll", stickyScroll);
      let windowSize = function() {
        return window.innerWidth;
      };
      if (windowSize() > 768) {
        let scrollDirection = 0;
        window.addEventListener("scroll", stickyScroll);
      }
    }, 250);

    window.addEventListener("resize", debouncedStickyNav);
    debouncedStickyNav();

    // Side Scroll Nav.
    let mainParent = $("#block-ts-wrin-main-menu");
    let menuItem = $("nav.menu--main li.menu-item--active-trail");
    let scrollMenu = $("nav.menu--main .menu-wrapper > ul.menu");

    var debouncedSideScroll = debounce(function() {
      if (mainParent.width() > menuItem.width() + 75) {
        mainParent.addClass("no-scroll");
      } else {
        mainParent.removeClass("no-scroll");
      }
    }, 250);

    window.addEventListener("resize", debouncedSideScroll);
    debouncedSideScroll();

    $("nav.menu--main .nav-arrow").click(function(e) {
      e.preventDefault();
      var leftPos = scrollMenu.scrollLeft();
      scrollMenu.animate({ scrollLeft: leftPos + mainParent.width() / 2 }, 500);
      $(this)
        .once()
        .clone()
        .insertBefore("nav.menu--main .menu-wrapper")
        .removeClass("nav-arrow")
        .addClass("back-arrow")
        .click(function(f) {
          f.preventDefault();
          var leftPosBack = scrollMenu.scrollLeft();
          scrollMenu.animate(
            { scrollLeft: leftPosBack - mainParent.width() / 2 },
            500
          );
        });
    });

    // Mega-menu side-scrolling quicklink nav.
    let quickLinksParent = $(".menu--quick-links .menu-wrapper");
    let quickLinksMenu = $(".menu--quick-links .menu-wrapper > ul.menu");

    var debouncedQuickLinksScroll = debounce(function() {
      if (quickLinksParent.width() > quickLinksMenu.width()) {
        quickLinksParent.addClass("no-scroll");
      } else {
        quickLinksParent.removeClass("no-scroll");
      }
    }, 250);

    window.addEventListener("resize", debouncedQuickLinksScroll);
    debouncedQuickLinksScroll();

    $(".hamburger-content .nav-arrow").click(function(e) {
      e.preventDefault();
      var leftPos = quickLinksParent.scrollLeft();
      quickLinksParent.animate(
        { scrollLeft: leftPos + quickLinksParent.width() / 2 },
        500
      );
      $(this)
        .once()
        .clone()
        .insertBefore("#block-quicklinks")
        .removeClass("nav-arrow")
        .addClass("back-arrow")
        .click(function(f) {
          f.preventDefault();
          var leftPosBack = quickLinksParent.scrollLeft();
          quickLinksParent.animate(
            { scrollLeft: leftPosBack - quickLinksParent.width() / 2 },
            500
          );
        });
    });

    // TOC Menus.
    let tocMainParent = $("#menu-toc");
    let tocMenuItem = $("#menu-toc > .menu-wrapper .menu");
    let tocScrollMenu = $("#menu-toc > .menu-wrapper");

    let tocDebouncedSideScroll = debounce(function() {
      if (tocMainParent.width() > tocMenuItem.width() - 30) {
        tocMainParent.addClass("no-scroll");
      } else {
        tocMainParent.removeClass("no-scroll");
      }
    }, 250);

    window.addEventListener("resize", tocDebouncedSideScroll);
    tocDebouncedSideScroll();

    $("nav.toc .nav-arrow").click(function(e) {
      e.preventDefault();
      var leftPos = tocScrollMenu.scrollLeft();
      tocScrollMenu.animate(
        { scrollLeft: leftPos + mainParent.width() / 2 },
        500
      );
      $(this)
        .once()
        .clone()
        .prependTo("nav.toc")
        .removeClass("nav-arrow")
        .addClass("back-arrow")
        .click(function(f) {
          f.preventDefault();
          var leftPosBack = tocScrollMenu.scrollLeft();
          tocScrollMenu.animate(
            { scrollLeft: leftPosBack - mainParent.width() / 2 },
            500
          );
        });
    });

    const tocStickyNav = document.querySelector(".publication__toc");
    if (tocStickyNav) {
      const tocTop =
        window.pageYOffset + tocStickyNav.getBoundingClientRect().top;

      function tocStickyScroll() {
        let st = window.pageYOffset || document.documentElement.scrollTop;
        if (st >= tocTop) {
          tocStickyNav.classList.add("sticky");
        } else {
          tocStickyNav.classList.remove("sticky");
        }
      }

      let tocDebouncedStickyNav = debounce(function() {
        window.addEventListener("scroll", tocStickyScroll);
      }, 250);

      window.addEventListener("resize", tocDebouncedStickyNav);
      tocDebouncedStickyNav();
    }

    // Manage active class for toc links.
    $(document).on("click", ".toc-list > li > a", function() {
      $(".toc-list > li > a").removeClass("active");
      $(this).addClass("active");
    });

    // Check for anchor on page load and click button if available.
    $(function() {
      let hash = location.hash;
      if (hash.length) {
        let checkExist = setInterval(function() {
          if ($(".toc-list > li").length) {
            $(".toc-list > li > a[href='" + hash + "'] ")[0].click();
            clearInterval(checkExist);
          }
        }, 100);
      }
    });
  }
}
