/**
 * @file
 * Legacy hamburger nav JS.
 *
 * Drives the old .hamburger-content / .hamburger-slider system (toggle,
 * mega-menu slide-out panels, and the quicklinks side-scroll arrow) that
 * ts_header_nav.js used to own before #532 split the new .wri-megamenu /
 * megamenu-mobile.js system out on its own.
 *
 * Kept as its own library (rather than folded back into the main webpack
 * bundle) and attached only from region--hamburger-nav.html.twig, so it
 * only loads on pages that still render the hamburger_nav region — the
 * old and new nav systems are both live in the theme "until the swap"
 * (see #532), and this keeps this file's .menu-toggle click binding from
 * loading on pages that only use the new megamenu.
 */
(function ($, Drupal) {
  Drupal.behaviors.tsHamburgerNavLegacy = {
    attach: function (context) {
      if (context !== document) {
        return;
      }

      // Hamburger Nav.
      function toggleMenu() {
        var toggle = $(".menu-toggle:not(.mobile-menu-toggle)");
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
      $(".menu-toggle:not(.mobile-menu-toggle)", context)
        .once("ts-menu-toggle")
        .on("click", function (e) {
          toggleMenu();
          $("body").addClass("fixed");
        })
        .keyup(function (e) {
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
        .on("click", function (e) {
          toggleMenu();
          $("body").removeClass("fixed");
          sliderCleanUp();
        });

      // Mega-Menu sliders.
      var hamburgerContent = $(".hamburger-content"),
        hamburgerSlider = $(".hamburger-slider"),
        flexibleRowsClass = "flexible-row-submenu";

      function slideOut(menuParent) {
        var clone = $(menuParent.target).parents("li").clone();
        $(".hamburger-slider-contents ul", hamburgerSlider).html(clone);
        $(".hamburger-slider-contents").addClass("active");
        // Hack to get the our-work link to have flexed rows.
        if (
          $(menuParent.target).hasClass(flexibleRowsClass) ||
          $(menuParent.target).children("a").hasClass(flexibleRowsClass)
        ) {
          $(".hamburger-slider-contents").addClass(flexibleRowsClass);
        }
        hamburgerContent.addClass("left");
        hamburgerSlider.addClass("active");
      }

      function sliderCleanUp() {
        hamburgerContent.removeClass("left");
        hamburgerSlider.removeClass("active");
        $(".hamburger-slider-contents").removeClass(flexibleRowsClass);
        setTimeout(function () {
          $(".hamburger-slider-contents ul", hamburgerSlider).html("");
        }, 500);
      }

      function sliderMenus(windowWidth) {
        if (windowWidth <= 768) {
          var burger = $(".hamburger-content");
          burger
            .find(".menu--footer-secondary > ul > li > .menu-item-title")
            .on("click", function (e) {
              e.preventDefault();
              slideOut(e);
            });
          burger
            .find(".menu--mega-menu > ul > li > .menu-item-title")
            .on("click", function (e) {
              e.preventDefault();
              slideOut(e);
            });

          // Close the sliders, then reset.
          $(".hamburger-slider .back").on("click", function (e) {
            e.preventDefault();
            sliderCleanUp();
          });
        } else {
          $(
            ".hamburger-content .menu--footer-secondary > ul > li > a, \
            .hamburger-content .menu--mega-menu > ul > li:nth-child(1) > a, \
            .hamburger-content .menu--mega-menu > ul > li:nth-child(2) > a, \
            .hamburger-content .menu--mega-menu > ul > li:nth-child(3) > a",
          ).unbind();
        }
      }

      function debounce(func, wait, immediate) {
        var timeout;
        return function () {
          var context = this,
            args = arguments;
          var later = function () {
            timeout = null;
            if (!immediate) func.apply(context, args);
          };
          var callNow = immediate && !timeout;
          clearTimeout(timeout);
          timeout = setTimeout(later, wait);
          if (callNow) func.apply(context, args);
        };
      }

      var debouncedSliderMenus = debounce(function () {
        let windowSize = function () {
          return window.innerWidth;
        };
        sliderMenus(windowSize());
      }, 250);

      window.addEventListener("resize", debouncedSliderMenus);
      debouncedSliderMenus();

      // Mega-menu side-scrolling quicklink nav.
      let quickLinksParent = $(".menu--quick-links .menu-wrapper");
      let quickLinksMenu = $(".menu--quick-links .menu-wrapper > ul.menu");

      var debouncedQuickLinksScroll = debounce(function () {
        if (quickLinksParent.width() > quickLinksMenu.width()) {
          quickLinksParent.addClass("no-scroll");
        } else {
          quickLinksParent.removeClass("no-scroll");
        }
      }, 250);

      window.addEventListener("resize", debouncedQuickLinksScroll);
      debouncedQuickLinksScroll();

      $(".hamburger-content .nav-arrow").click(function (e) {
        e.preventDefault();
        var leftPos = quickLinksParent.scrollLeft();
        quickLinksParent.animate(
          { scrollLeft: leftPos + quickLinksParent.width() / 2 },
          500,
        );
        $(this)
          .once()
          .clone()
          .insertBefore("#block-quicklinks")
          .removeClass("nav-arrow")
          .addClass("back-arrow")
          .click(function (f) {
            f.preventDefault();
            var leftPosBack = quickLinksParent.scrollLeft();
            quickLinksParent.animate(
              { scrollLeft: leftPosBack - quickLinksParent.width() / 2 },
              500,
            );
          });
      });
    },
  };
})(jQuery, Drupal);
