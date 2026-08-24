/**
 * @file
 * WRI mobile mega menu behavior — full-screen sliding panels.
 *
 * Reuses the same real Drupal markup that powers the desktop drill-down
 * (megamenu.js) as its single source of truth. .wri-megamenu is rendered
 * regardless of viewport (just hidden below 1024px via CSS), so this file
 * reads that hidden structure once, builds an in-memory tree, and drives
 * its own full-screen sliding-panel overlay from it — rather than
 * maintaining a second hand-written menu structure that could drift out
 * of sync with Drupal content.
 *
 * Triggered by the existing .menu-toggle button (in .header-right) rather
 * than an injected/Twig-authored button of our own — its old click
 * handler (which used to open the now-retired .region-hamburger-nav
 * slider) needs to be removed on the Drupal/theme side so it isn't still
 * firing alongside this. #block-quicklinks lives inside .wri-megamenu
 * (moved there per the Quick Links block placement change).
 *
 * .menu-toggle doesn't carry aria-haspopup/aria-expanded/aria-controls in
 * its own markup, so those are set here at init instead of requiring a
 * template change.
 */
(function (Drupal, once) {
  "use strict";

  var DESKTOP_QUERY = "(min-width: 1024px)";

  // Hardcoded because Drupal's .field--name-field-listing markup contains
  // no heading text of its own — the mobile design shows one (the desktop
  // design doesn't), so there's nothing to read from the DOM here. Keyed
  // to the top-level panel's trigger label.
  var FEATURED_HEADINGS = {
    "Research & Data": "FEATURED RESEARCH",
    "News & Insights": "FEATURED NEWS & INSIGHTS",
    Events: "FEATURED EVENTS",
  };

  Drupal.behaviors.wriMobileMegaMenu = {
    attach: function (context) {
      once("wri-mobile-megamenu", ".wri-megamenu", context).forEach(
        function (root) {
          initMobileMenu(root);
        },
      );
    },
  };

  function isDesktop() {
    return window.matchMedia(DESKTOP_QUERY).matches;
  }

  function initMobileMenu(root) {
    var toggle = document.querySelector(".menu-toggle");
    if (!toggle) {
      return;
    }
    // .menu-toggle's own markup doesn't include these — set them here
    // rather than requiring a template change.
    toggle.setAttribute("aria-haspopup", "dialog");
    toggle.setAttribute("aria-expanded", "false");
    toggle.setAttribute("aria-controls", "wri-mobile-nav");

    var data = null; // built lazily on first open — no need to walk the DOM before anyone actually opens the menu
    var overlay = buildOverlay(root); // built eagerly, not on first click, so toggle's aria-controls resolves to a real element from page load
    var stack = [];
    var lastFocused = null;

    toggle.addEventListener("click", openMenu);

    function openMenu(event) {
      if (isDesktop()) {
        return; // desktop uses the inline mega menu instead
      }
      if (event) {
        event.preventDefault();
      }

      if (!data) {
        data = buildMenuData(root);
      }

      lastFocused = document.activeElement;

      // Measure the real site header each time rather than hardcoding a
      // height — the mobile prototype assumed a fixed 88px standalone
      // topbar, but the live site's actual header may differ or change
      // (e.g. a promo bar above it).
      var header =
        document.querySelector(".header-wrapper") ||
        document.querySelector("header");
      overlay.root.style.setProperty(
        "--mega-mobile-nav-h",
        header ? header.offsetHeight + "px" : "64px",
      );

      overlay.root.classList.add("is-open");
      toggle.setAttribute("aria-expanded", "true");
      lockScroll(true);

      stack = [];
      pushPanel("Menu", data.items, {
        quicklinks: data.quicklinks,
        isRoot: true,
      });

      overlay.closeBtn.focus();
    }

    function closeMenu() {
      overlay.root.classList.remove("is-open");
      toggle.setAttribute("aria-expanded", "false");
      lockScroll(false);
      overlay.track.innerHTML = "";
      stack = [];
      if (lastFocused && typeof lastFocused.focus === "function") {
        lastFocused.focus();
      }
    }

    function buildOverlay(themeSource) {
      var built = buildOverlaySkeleton(themeSource);
      document.body.appendChild(built.root);

      built.closeBtn.addEventListener("click", closeMenu);

      document.addEventListener("keydown", function (event) {
        if (!built.root.classList.contains("is-open")) {
          return;
        }
        if (event.key === "Escape" || event.key === "Esc") {
          event.preventDefault();
          closeMenu();
          return;
        }
        if (event.key === "Tab") {
          trapFocus(event);
        }
      });

      return built;
    }

    function trapFocus(event) {
      var focusables = Array.prototype.slice
        .call(
          overlay.root.querySelectorAll(
            'button, [href], [tabindex]:not([tabindex="-1"])',
          ),
        )
        .filter(function (el) {
          return !el.hasAttribute("disabled") && el.offsetParent !== null;
        });
      if (!focusables.length) {
        return;
      }
      var first = focusables[0];
      var last = focusables[focusables.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }

    function lockScroll(on) {
      document.documentElement.style.overflow = on ? "hidden" : "";
      document.body.style.overflow = on ? "hidden" : "";
    }

    function pushPanel(title, items, opts) {
      var node = { title: title, items: items };
      if (opts) {
        Object.keys(opts).forEach(function (key) {
          node[key] = opts[key];
        });
      }
      stack.push(node);
      renderPanels();
      updateTransform();
    }

    function popPanel() {
      if (stack.length <= 1) {
        return;
      }
      stack.pop();
      renderPanels();
      updateTransform();
    }

    function updateTransform() {
      var idx = stack.length - 1;
      overlay.track.style.transform = "translateX(" + -idx * 100 + "%)";
    }

    function renderPanels() {
      overlay.track.innerHTML = "";

      stack.forEach(function (node) {
        var panel = document.createElement("section");
        panel.className = "wri-mobile-nav__panel";
        panel.setAttribute("aria-label", node.title);

        if (!node.isRoot) {
          var back = document.createElement("button");
          back.type = "button";
          back.className = "wri-mobile-nav__back";
          back.innerHTML =
            '<span aria-hidden="true">&larr;</span><span>Back</span>';
          back.addEventListener("click", popPanel);
          panel.appendChild(back);

          var h2 = document.createElement("h2");
          h2.className = "wri-mobile-nav__title";
          h2.textContent = node.title;
          panel.appendChild(h2);
        }

        var ul = document.createElement("ul");
        ul.className = "menu";

        node.items.forEach(function (item) {
          var li = document.createElement("li");

          if (item.children) {
            var btn = document.createElement("button");
            btn.type = "button";
            btn.className = "wri-mobile-nav__row";
            btn.innerHTML =
              "<span>" +
              escapeHtml(item.label) +
              '</span><span class="wri-mobile-nav__chev" aria-hidden="true"></span>';
            btn.addEventListener("click", function () {
              pushPanel(item.label, item.children, { featured: item.featured });
            });
            li.appendChild(btn);
          } else if (item.disabled) {
            // Leaf item with no href and no children — a content-team
            // placeholder (e.g. "Data & Tools", "Coalitions" today).
            // Rendered as inert rather than a dead link.
            var span = document.createElement("span");
            span.className =
              "wri-mobile-nav__row wri-mobile-nav__row--disabled";
            span.textContent = item.label;
            li.appendChild(span);
          } else {
            var a = document.createElement("a");
            a.className = "wri-mobile-nav__row";
            a.href = item.url || "#";
            var label = item.emphasis
              ? "<strong>" + escapeHtml(item.label) + "</strong>"
              : escapeHtml(item.label);
            a.innerHTML = "<span>" + label + "</span>";
            li.appendChild(a);
          }

          ul.appendChild(li);
        });

        panel.appendChild(ul);

        if (node.featured && node.featured.items.length) {
          panel.appendChild(
            buildBottomBlock(node.featured.heading, node.featured.items, true),
          );
        }

        if (node.isRoot && node.quicklinks && node.quicklinks.length) {
          panel.appendChild(
            buildBottomBlock("Quicklinks", node.quicklinks, false),
          );
        }

        overlay.track.appendChild(panel);
      });
    }

    function buildBottomBlock(heading, items, isFeatured) {
      var wrap = document.createElement("div");
      wrap.className = "wri-mobile-nav__bottom";

      var h3 = document.createElement("h3");
      h3.textContent = heading;
      wrap.appendChild(h3);

      var ul = document.createElement("ul");
      ul.className = "menu";

      items.forEach(function (item) {
        var li = document.createElement("li");
        var a = document.createElement("a");
        a.className =
          "wri-mobile-nav__row" +
          (isFeatured ? " wri-mobile-nav__row--featured" : "");
        a.href = item.url || "#";

        var labelHtml = item.emphasis
          ? "<strong>" + escapeHtml(item.label) + "</strong>"
          : escapeHtml(item.label);
        var typeHtml = item.type
          ? '<span class="wri-mobile-nav__badge">' +
            escapeHtml(item.type) +
            "</span>"
          : "";
        a.innerHTML =
          "<span>" +
          typeHtml +
          '<span class="wri-mobile-nav__row-title">' +
          labelHtml +
          "</span></span>";
        li.appendChild(a);
        ul.appendChild(li);
      });

      wrap.appendChild(ul);
      return wrap;
    }
  }

  function buildOverlaySkeleton(themeSource) {
    var wrap = document.createElement("div");
    wrap.id = "wri-mobile-nav"; // matches aria-controls set on .menu-toggle in initMobileMenu above
    wrap.className = "wri-mobile-nav";
    wrap.setAttribute("role", "dialog");
    wrap.setAttribute("aria-modal", "true");
    wrap.setAttribute("aria-label", "Menu");
    // Inherit whichever green/grey theme modifier .wri-megamenu is using
    // (see _megamenu.scss), so the mobile overlay always matches the
    // desktop panel colors instead of tracking its own copy of the theme.
    wrap.classList.add(
      themeSource.classList.contains("grey") ? "grey" : "green",
    );

    wrap.innerHTML =
      '<div class="wri-mobile-nav__header">' +
      // Reuses the same white-logo asset the site's own branding block
      // already references (#block-ts-wrin-branding), rather than
      // introducing a new asset path or requiring a Twig change.
      '<img class="wri-mobile-nav__logo" src="/sites/default/files/nav_logo_white_0.svg" alt="World Resources Institute">' +
      '<button type="button" class="wri-mobile-nav__close" aria-label="Close menu">' +
      '<span aria-hidden="true">&times;</span>' +
      "</button>" +
      "</div>" +
      '<div class="wri-mobile-nav__viewport">' +
      '<div class="wri-mobile-nav__track"></div>' +
      "</div>";

    return {
      root: wrap,
      closeBtn: wrap.querySelector(".wri-mobile-nav__close"),
      track: wrap.querySelector(".wri-mobile-nav__track"),
    };
  }

  /**
   * Walks the real (possibly hidden) .wri-megamenu DOM and the real
   * #block-quicklinks menu to build the tree the renderer above expects,
   * instead of a second hand-maintained copy of the menu structure.
   */
  function buildMenuData(root) {
    var panelItems = Array.prototype.slice.call(
      root.querySelectorAll(
        ":scope > .field__items > .field__item > .paragraph--type--submenu",
      ),
    );

    var items = panelItems
      .map(function (item) {
        var trigger = item.querySelector(":scope > button");
        var panel = item.querySelector(":scope > .menu-contents");
        if (!trigger || !panel) {
          return null;
        }
        var label = trigger.textContent.trim();

        // NOT :scope > — the desktop drilldown (megamenu.js) runs its
        // enhancePanel() unconditionally on page load regardless of
        // viewport (only the interactive open/close is gated to desktop
        // widths), which moves both of these one level deeper, from
        // direct children of .menu-contents to children of the
        // .mega-grid it builds. A :scope-anchored direct-child query
        // stopped matching anything the moment that ran, which is what
        // was producing an empty <ul> for every panel here.
        var listing = panel.querySelector(".field--name-field-listing");
        var navBlock = panel.querySelector(
          '[id^="block-menu-blockmain-menu-2026"]',
        );
        var rootList = navBlock
          ? navBlock.querySelector(":scope > ul.menu")
          : null;

        // The desktop drilldown also auto-activates the first expandable
        // top-level item (whichever carries .is-active) and, as a side
        // effect of how its own column layout works, physically relocates
        // THAT item's nested <ul> out of its <li> and into a sibling
        // .mega-col-flyout-region. buildChildren() below would otherwise
        // treat that one item as a dead-end placeholder, since its <ul>
        // genuinely isn't inside its <li> anymore — recover it from where
        // desktop actually put it.
        var flyoutRegion = navBlock
          ? navBlock.parentElement.querySelector(
              ":scope > .mega-col-flyout-region",
            )
          : null;
        var recoveryUl = flyoutRegion
          ? flyoutRegion.querySelector(":scope > ul.menu")
          : null;

        return {
          label: label,
          featured: listing ? extractFeatured(listing, label) : null,
          children: rootList ? buildChildren(rootList, recoveryUl) : [],
        };
      })
      .filter(Boolean);

    return { items: items, quicklinks: extractQuicklinks(root) };
  }

  function directChildUl(li) {
    return Array.prototype.slice.call(li.children).find(function (child) {
      return child.tagName === "UL" && child.classList.contains("menu");
    });
  }

  // recoveryUl is only meaningful for the top-level call (see
  // buildMenuData above) — only one item per rootList can be .is-active
  // at page load, so it's unambiguous, and recursive calls below never
  // pass it further since deeper levels haven't been auto-relocated.
  function buildChildren(ul, recoveryUl) {
    return Array.prototype.slice
      .call(ul.children)
      .filter(function (li) {
        return li.classList && li.classList.contains("menu-item");
      })
      .map(function (li) {
        var titleSpan = li.querySelector(":scope > .menu-item-title");
        var a = titleSpan ? titleSpan.querySelector(":scope > a") : null;
        var btn = titleSpan ? titleSpan.querySelector(":scope > button") : null;
        var nested = directChildUl(li);
        var emphasis = li.classList.contains("font-bold");

        if (!nested && recoveryUl && li.classList.contains("is-active")) {
          nested = recoveryUl;
        }

        if (a) {
          return {
            label: a.textContent.trim(),
            url: a.getAttribute("href"),
            emphasis: emphasis,
          };
        }
        if (btn && nested) {
          return {
            label: btn.textContent.trim(),
            children: buildChildren(nested),
          };
        }
        return { label: btn ? btn.textContent.trim() : "", disabled: true };
      });
  }

  function extractFeatured(listingEl, panelLabel) {
    var items = Array.prototype.slice
      .call(
        listingEl.querySelectorAll(
          ".views-element-container .item-list > ul > li",
        ),
      )
      .map(function (li) {
        var type = li.querySelector(".views-field-field-type .field-content");
        var titleLink = li.querySelector(".views-field-title a");
        return {
          type: type ? type.textContent.trim() : null,
          label: titleLink ? titleLink.textContent.trim() : "",
          url: titleLink ? titleLink.getAttribute("href") : "#",
        };
      });

    var moreLink = listingEl.querySelector(".more-link a");
    if (moreLink) {
      items.push({
        label: moreLink.textContent.trim(),
        url: moreLink.getAttribute("href"),
        emphasis: true,
      });
    }

    return {
      heading: FEATURED_HEADINGS[panelLabel] || "Featured",
      items: items,
    };
  }

  // #block-quicklinks now lives inside .wri-megamenu itself. Checking
  // root first (the real, current structure) with a couple of safety-net
  // fallbacks in case it ends up a level up instead — keeps this working
  // either way without needing another edit here if placement shifts again.
  function extractQuicklinks(root) {
    var scope = root.querySelector("#block-quicklinks")
      ? root
      : root.closest(".region-primary-nav") || document;
    var list = scope.querySelector("#block-quicklinks .menu-wrapper ul.menu");
    if (!list) {
      return [];
    }
    return Array.prototype.slice
      .call(list.children)
      .map(function (li) {
        var a = li.querySelector(":scope > a");
        return a
          ? { label: a.textContent.trim(), url: a.getAttribute("href") }
          : null;
      })
      .filter(Boolean); // drops the label-only "Quick Links" <li><span> item
  }

  function escapeHtml(str) {
    return String(str)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }
})(Drupal, once);
