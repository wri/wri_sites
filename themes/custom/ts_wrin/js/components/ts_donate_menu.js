/**
 * @file
 * WRI "Donate" header dropdown behavior.
 *
 * Enhances the existing #block-donate menu (Drupal's menu--donate — a
 * flat <ul class="menu"> of links, no dropdown behavior of its own) into
 * a click-to-toggle dropdown. Same interaction pattern as megamenu.js's
 * top-level open/close, just without any drill-down levels, since this
 * menu is a single flat list rather than a recursive tree.
 *
 * No Twig changes required — the trigger button and dropdown wrapper are
 * both built here from the block's existing markup (same approach
 * megamenu.js uses to build .mega-grid without needing template changes).
 *
 * Desktop-only by design (matches megamenu.js's 1024px gate). On mobile,
 * "Donate" is already covered by the Quicklinks section inside
 * megamenu-mobile.js's overlay, so this dropdown doesn't get its own
 * mobile treatment.
 */
(function (Drupal, once) {
  "use strict";

  var DESKTOP_QUERY = "(min-width: 1024px)";

  Drupal.behaviors.wriDonateMenu = {
    attach: function (context) {
      once("wri-donate-menu", "#block-donate", context).forEach(
        function (root) {
          initDonateMenu(root);
        },
      );
    },
  };

  function isDesktop() {
    return window.matchMedia(DESKTOP_QUERY).matches;
  }

  function initDonateMenu(root) {
    var list = root.querySelector(":scope > ul.menu");
    if (!list) {
      return;
    }

    var titleEl = root.querySelector(":scope > h2");
    var label = titleEl ? titleEl.textContent.trim() : "Donate";

    // Visually hide rather than remove: aria-labelledby="block-donate-menu"
    // on the <nav> points at this element's id. Deleting it would leave
    // that reference dangling and the nav without an accessible name.
    if (titleEl) {
      titleEl.classList.add("visually-hidden");
    }

    var trigger = document.createElement("button");
    trigger.type = "button";
    trigger.className = "donate-menu__trigger";
    trigger.textContent = label;
    trigger.setAttribute("aria-haspopup", "true");
    trigger.setAttribute("aria-expanded", "false");

    var panel = document.createElement("div");
    panel.className = "donate-menu__panel";
    panel.hidden = true;
    panel.appendChild(list); // moves the existing <ul> — real hrefs/data attributes travel with it, nothing is rebuilt

    root.insertBefore(panel, root.firstChild);
    root.insertBefore(trigger, root.firstChild);
    root.classList.add("donate-menu--enhanced");

    trigger.addEventListener("click", function (event) {
      if (!isDesktop()) {
        return; // let the link behave natively below the breakpoint, if it's shown there at all
      }
      event.preventDefault();
      var wasOpen = root.classList.contains("is-open");
      closeMenu();
      if (!wasOpen) {
        openMenu();
      }
    });

    document.addEventListener("click", function (event) {
      if (!root.contains(event.target)) {
        closeMenu();
      }
    });

    root.addEventListener("keydown", function (event) {
      if (event.key === "Escape" || event.key === "Esc") {
        closeMenu();
        trigger.focus();
      }
    });

    function openMenu() {
      root.classList.add("is-open");
      trigger.setAttribute("aria-expanded", "true");
      panel.hidden = false;
    }

    function closeMenu() {
      root.classList.remove("is-open");
      trigger.setAttribute("aria-expanded", "false");
      panel.hidden = true;
    }
  }
})(Drupal, once);
