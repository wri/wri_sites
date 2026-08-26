/**
 * @file
 * TS Header Nav custom JS.
 *
 * Trimmed down from the original ts_header_nav.js: removed everything that
 * only existed to drive the old .hamburger-content / .hamburger-slider
 * system (now retired in favor of .wri-megamenu + megamenu-mobile.js).
 * That included:
 *   - toggleMenu() and its .menu-toggle / .mega_menu_close click bindings
 *     — this was directly competing with megamenu-mobile.js for clicks on
 *     .menu-toggle, since neither script excluded the other's handler.
 *   - slideOut() / sliderCleanUp() / sliderMenus() and their resize
 *     listener — operated on .hamburger-content, .hamburger-slider,
 *     .menu--footer-secondary, .menu--mega-menu, none of which exist once
 *     .region-hamburger-nav is gone.
 *   - The quicklinks side-scroll-arrow block (.hamburger-content
 *     .nav-arrow acting on #block-quicklinks-2026) — that was scroll-arrow
 *     behavior for the old horizontal quicklinks strip inside the old
 *     hamburger nav. #block-quicklinks-2026 is now permanently display:none
 *     (it's a pure data source for megamenu-mobile.js), so this was
 *     measuring width/scrollLeft on a hidden element on every resize.
 *
 * NOT verified: whether any other CSS on the site keys off
 * .header-wrapper.menu-open or body.fixed for something unrelated to the
 * removed hamburger slider. These were removed on the assumption they
 * existed only to support it — worth a quick grep before deploying.
 *
 * Everything below is untouched from the original file.
 */

export default function (context) {
  // Alias global jQuery object.
  const $ = jQuery;
  let tabbingContext = null;
  if (context == document) {
    // Sticky Nav.
    const stickyNav = document.querySelector("header");

    function stickyScroll() {
      let st = window.pageYOffset || document.documentElement.scrollTop;
      let headerTop = document
        .getElementById("tray-nav-canvas")
        .getBoundingClientRect().top;
      if (st >= headerTop + 30) {
        stickyNav.classList.add("sticky");
      } else {
        stickyNav.classList.remove("sticky");
      }
    }

    function stickyScrollMobile() {
      const mobileStickyNav = document.querySelector(
        ".page-node-type-simple-page .layout__region--menu, .experts-staff-header .internal-menu-pages",
      );
      const mobileStickyParent = document.querySelector(
        ".page-node-type-simple-page .simple-page__title, .experts-staff-header .right",
      );

      if (mobileStickyNav) {
        let tocTop =
          window.pageYOffset + mobileStickyParent.getBoundingClientRect().top;

        let st = window.pageYOffset || document.documentElement.scrollTop;
        if (st >= tocTop) {
          mobileStickyParent.classList.add("sticky");
        } else {
          mobileStickyParent.classList.remove("sticky");
        }
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

    var debouncedStickyNav = debounce(function () {
      window.removeEventListener("scroll", stickyScroll);
      window.removeEventListener("scroll", stickyScrollMobile);
      let windowSize = function () {
        return window.innerWidth;
      };
      if (windowSize() > 768) {
        window.addEventListener("scroll", stickyScroll);
        const mobileStickyParent = document.querySelector(
          ".page-node-type-simple-page .simple-page__title, .experts-staff-header .right",
        );
        if (mobileStickyParent) {
          mobileStickyParent.classList.remove("sticky");
        }
      } else {
        window.addEventListener("scroll", stickyScrollMobile);
      }
    }, 250);

    window.addEventListener("resize", debouncedStickyNav);
    debouncedStickyNav();

    // Side Scroll Nav.
    let mainParent = $("#block-ts-wrin-main-menu");
    let menuItem = $("nav.menu--main li.menu-item--active-trail");
    let scrollMenu = $("nav.menu--main .menu-wrapper > ul.menu");

    var debouncedSideScroll = debounce(function () {
      if (mainParent.width() > menuItem.width() + 75) {
        mainParent.addClass("no-scroll");
      } else {
        mainParent.removeClass("no-scroll");
      }
    }, 250);

    window.addEventListener("resize", debouncedSideScroll);
    debouncedSideScroll();

    $("nav.menu--main .nav-arrow").click(function (e) {
      e.preventDefault();
      var leftPos = scrollMenu.scrollLeft();
      scrollMenu.animate({ scrollLeft: leftPos + mainParent.width() / 2 }, 500);
      $(this)
        .once()
        .clone()
        .insertBefore("nav.menu--main .menu-wrapper")
        .removeClass("nav-arrow")
        .addClass("back-arrow")
        .click(function (f) {
          f.preventDefault();
          var leftPosBack = scrollMenu.scrollLeft();
          scrollMenu.animate(
            { scrollLeft: leftPosBack - mainParent.width() / 2 },
            500,
          );
        });
    });

    // TOC Menus.
    let tocMainParent = $(".toc");
    let tocMenuItem = $(".toc .menu-item--active-trail .menu");
    let tocScrollMenu = $(".toc .menu-item--active-trail .menu");

    let tocDebouncedSideScroll = debounce(function () {
      if (
        tocMenuItem[0] &&
        tocMainParent.width() > tocMenuItem[0].scrollWidth - 30
      ) {
        tocMainParent.addClass("no-scroll");
      } else {
        tocMainParent.removeClass("no-scroll");
      }
    }, 250);

    window.addEventListener("resize", tocDebouncedSideScroll);
    tocDebouncedSideScroll();

    $("nav.toc .nav-arrow").click(function (e) {
      e.preventDefault();
      var leftPos = tocScrollMenu.scrollLeft();
      tocScrollMenu.animate(
        { scrollLeft: leftPos + mainParent.width() / 2 },
        500,
      );
    });

    const tocStickyNav = document.querySelector(".publication__toc");
    const mobileStickyNav = document.querySelector(".mobile__toc");
    if (tocStickyNav) {
      let tocTop =
        window.pageYOffset + tocStickyNav.getBoundingClientRect().top;
      if (tocTop == 0) {
        tocTop =
          window.pageYOffset + mobileStickyNav.getBoundingClientRect().top;
      }

      function tocStickyScroll() {
        let st = window.pageYOffset || document.documentElement.scrollTop;
        if (st >= tocTop) {
          tocStickyNav.classList.add("sticky");
          mobileStickyNav.classList.add("sticky");
        } else {
          tocStickyNav.classList.remove("sticky");
          mobileStickyNav.classList.remove("sticky");
        }
      }

      let tocDebouncedStickyNav = debounce(function () {
        window.addEventListener("scroll", tocStickyScroll);
      }, 250);

      window.addEventListener("resize", tocDebouncedStickyNav);
      tocDebouncedStickyNav();
    }

    // Manage active class for toc links.
    $(document).on("click", ".toc-list > li > a", function () {
      $(".toc-list > li > a").removeClass("active");
      $(this).addClass("active");
    });

    // Check for anchor on page load and click button if available.
    $(function () {
      let hash = location.hash;
      if (hash.length) {
        let checkExist = setInterval(function () {
          if ($(".toc-list > li").length) {
            $(".toc-list > li > a[href='" + hash + "'] ")[0].click();
            clearInterval(checkExist);
          }
        }, 100);
      }
    });

    const menuContainer = document.querySelector(".internal-menu-pages");
    const menuToggle = document.querySelector(
      ".internal-menu-pages .field-label",
    );

    if (menuContainer && menuToggle) {
      // Toggle menu open/closed when clicking the label
      menuToggle.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation(); // Stop event propagation to avoid interfering with links

        // Toggle the 'menu-open' and 'menu-closed' classes
        if (menuContainer.classList.contains("menu-open")) {
          menuContainer.classList.remove("menu-open");
          menuContainer.classList.add("menu-closed");
        } else {
          menuContainer.classList.remove("menu-closed");
          menuContainer.classList.add("menu-open");
        }
      });

      // Prevent clicks within the menu from toggling the menu
      menuContainer
        .querySelector("nav")
        .addEventListener("click", function (e) {
          e.stopPropagation();
        });
    }

    if (menuContainer) {
      // Close the menu if clicking outside of it
      document.addEventListener("click", function () {
        if (menuContainer.classList.contains("menu-open")) {
          menuContainer.classList.remove("menu-open");
          menuContainer.classList.add("menu-closed");
        }
      });
    }

    // Carousel Menu.
    let carouselMenuItem = jQuery(".carousel.tab-titles");

    let carouselDebouncedSideScroll = debounce(function () {
      if (
        carouselMenuItem[0] &&
        carouselMenuItem.width() > carouselMenuItem[0].scrollWidth - 270
      ) {
        carouselMenuItem.addClass("no-scroll");
      } else {
        carouselMenuItem.removeClass("no-scroll");
      }
    }, 250);

    window.addEventListener("resize", carouselDebouncedSideScroll);
    carouselDebouncedSideScroll();

    $(".carousel.tab-titles .nav-arrow").click(function (e) {
      e.preventDefault();

      var leftPos = carouselMenuItem.scrollLeft();
      carouselMenuItem.animate(
        { scrollLeft: leftPos + carouselMenuItem.width() / 2 },
        500,
      );
    });
  }
}
