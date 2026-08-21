/**
 * @file
 * WRI mega menu behavior.
 *
 * Scoped entirely to elements inside .wri-megamenu — never touches or
 * queries anything else on the page, so it can't affect the legacy menu.
 *
 * Handles two independent layers:
 *  1. Top-level panel open/close (Explore, Research & Data, News &
 *     Insights, Events, About).
 *  2. Inside an open panel, a recursive "drill-down" for the nested
 *     <ul class="menu"> structure Drupal renders — any li.menu-item--
 *     expanded reveals its child <ul> as the next flyout column, to
 *     whatever depth the real menu goes (1 level for Research & Data,
 *     2 levels for Explore's "By Topic", etc). Nothing here assumes a
 *     fixed number of columns.
 *
 * Desktop-only by design (min-width gate below) — the mobile prototype
 * uses a different interaction pattern (off-canvas / accordion) and
 * should get its own behavior once that markup is finalized.
 */
(function (Drupal, once) {
  "use strict";

  var DESKTOP_QUERY = "(min-width: 1024px)";

  Drupal.behaviors.wriMegaMenu = {
    attach: function (context) {
      once("wri-megamenu", ".wri-megamenu", context).forEach(function (root) {
        initMegaMenu(root);
      });
    },
  };

  function isDesktop() {
    return window.matchMedia(DESKTOP_QUERY).matches;
  }

  function initMegaMenu(root) {
    var panelItems = Array.prototype.slice.call(
      root.querySelectorAll(
        ":scope > .field__items > .field__item > .paragraph--type--submenu",
      ),
    );

    var entries = panelItems
      .map(function (item) {
        var trigger = item.querySelector(":scope > button");
        var panel = item.querySelector(":scope > .menu-contents");
        if (!trigger || !panel) {
          return null;
        }
        trigger.setAttribute("aria-haspopup", "true");
        trigger.setAttribute("aria-expanded", "false");
        enhancePanel(panel);
        return { item: item, trigger: trigger, panel: panel };
      })
      .filter(Boolean);

    entries.forEach(function (entry) {
      entry.trigger.addEventListener("click", function (event) {
        if (!isDesktop()) {
          return; // let mobile's own nav handling take over
        }
        event.preventDefault();
        var willOpen = !entry.item.classList.contains("is-open");
        closeAll(entries);
        if (willOpen) {
          openPanel(entry);
        }
      });
    });

    // Click outside closes everything.
    document.addEventListener("click", function (event) {
      if (!root.contains(event.target)) {
        closeAll(entries);
      }
    });

    // Escape closes everything and returns focus to the open trigger.
    root.addEventListener("keydown", function (event) {
      if (event.key === "Escape" || event.key === "Esc") {
        var openEntry = entries.find(function (entry) {
          return entry.item.classList.contains("is-open");
        });
        closeAll(entries);
        if (openEntry) {
          openEntry.trigger.focus();
        }
      }
    });

    // Moving focus entirely outside the mega menu (e.g. tabbing into the
    // page body) closes any open panel.
    root.addEventListener(
      "focusout",
      function (event) {
        if (!root.contains(event.relatedTarget)) {
          closeAll(entries);
        }
      },
      true,
    );
  }

  function openPanel(entry) {
    entry.item.classList.add("is-open");
    entry.trigger.setAttribute("aria-expanded", "true");
  }

  function closeAll(entries) {
    entries.forEach(function (entry) {
      entry.item.classList.remove("is-open");
      entry.trigger.setAttribute("aria-expanded", "false");
      if (typeof entry.panel._wriMegaReset === "function") {
        entry.panel._wriMegaReset();
      }
    });
  }

  /**
   * Rebuilds a .menu-contents panel into the .mega-grid / .mega-col
   * structure the Sass expects, then wires up the drill-down if a nav
   * block is present. Idempotent — safe to call once per panel.
   */
  function enhancePanel(panel) {
    if (panel.dataset.megaEnhanced) {
      return;
    }
    panel.dataset.megaEnhanced = "true";

    var title = panel.querySelector(":scope > h3");
    var desc = panel.querySelector(":scope > .field--name-field-text");
    var listing = panel.querySelector(":scope > .field--name-field-listing");
    var navBlock = panel.querySelector(
      ':scope > [id^="block-menu-blockmain-menu-2026"]',
    );

    var grid = document.createElement("div");
    grid.className = "mega-grid";

    var introCol = document.createElement("div");
    introCol.className = "mega-col flush-left";
    if (title) {
      introCol.appendChild(title);
    }
    if (desc) {
      introCol.appendChild(desc);
    }
    grid.appendChild(introCol);

    if (listing) {
      listing.classList.add("mega-col", "has-sep");
      grid.appendChild(listing);
    }

    var flyoutRegion = null;

    if (navBlock) {
      navBlock.classList.add("mega-col", "has-sep");
      grid.appendChild(navBlock);

      flyoutRegion = document.createElement("div");
      flyoutRegion.className = "mega-col-flyout-region";
      grid.appendChild(flyoutRegion);
    }

    panel.appendChild(grid);

    if (navBlock && flyoutRegion) {
      panel._wriMegaReset = setupDrilldown(navBlock, flyoutRegion);
    }
  }

  /**
   * Wires up the recursive drill-down for one nav block.
   * Returns a reset() function that collapses back to the default
   * (first expandable item open), for use when the panel closes.
   */
  function setupDrilldown(navBlock, flyoutRegion) {
    var rootList = navBlock.querySelector(":scope > ul.menu");
    if (!rootList) {
      return function () {};
    }

    // li -> { btn, nested, depth }
    var registry = new Map();
    var activePath = [];

    function directChildUl(li) {
      var children = Array.prototype.slice.call(li.children);
      return children.find(function (child) {
        return child.tagName === "UL" && child.classList.contains("menu");
      });
    }

    function processList(ul, depth) {
      Array.prototype.slice.call(ul.children).forEach(function (li) {
        if (!li.classList || !li.classList.contains("menu-item")) {
          return;
        }
        var titleSpan = li.querySelector(":scope > .menu-item-title");
        var btn = titleSpan ? titleSpan.querySelector(":scope > button") : null;
        var nested = directChildUl(li);

        if (btn && nested) {
          nested.hidden = true;
          nested.classList.add("mega-flyout");
          nested.style.setProperty("--depth", String(depth));
          registry.set(li, { btn: btn, nested: nested, depth: depth });
          btn.setAttribute("aria-expanded", "false");
          btn.addEventListener("click", function (event) {
            event.preventDefault();
            activate(li);
          });
          processList(nested, depth + 1);
        }
      });
    }

    processList(rootList, 1);

    function collapseFromDepth(depth) {
      activePath.slice(depth - 1).forEach(function (li) {
        var info = registry.get(li);
        if (!info) {
          return;
        }
        info.btn.setAttribute("aria-expanded", "false");
        li.classList.remove("is-active");
        info.nested.hidden = true;
      });
      activePath = activePath.slice(0, depth - 1);
    }

    function activate(li) {
      var info = registry.get(li);
      if (!info) {
        return;
      }

      // Collapse this depth and anything deeper before opening the new selection.
      collapseFromDepth(info.depth);

      // Deactivate sibling items at the same depth.
      Array.prototype.slice
        .call(li.parentElement.children)
        .forEach(function (sibling) {
          if (sibling === li) {
            return;
          }
          sibling.classList.remove("is-active");
          var siblingInfo = registry.get(sibling);
          if (siblingInfo) {
            siblingInfo.btn.setAttribute("aria-expanded", "false");
          }
        });

      li.classList.add("is-active");
      info.btn.setAttribute("aria-expanded", "true");
      info.nested.hidden = false;
      // Moving the node brings its entire (still-hidden) subtree along,
      // so deeper columns "just work" once their own trigger is clicked.
      flyoutRegion.appendChild(info.nested);

      activePath[info.depth - 1] = li;
    }

    function reset() {
      collapseFromDepth(1);
      var firstExpandable = Array.prototype.slice
        .call(rootList.children)
        .find(function (li) {
          return registry.has(li);
        });
      if (firstExpandable) {
        activate(firstExpandable);
      }
    }

    reset();
    return reset;
  }
})(Drupal, once);
