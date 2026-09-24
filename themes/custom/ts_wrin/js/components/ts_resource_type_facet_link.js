/**
 * @file
 * Links with data-resource-type-facet select that resource type in the
 * on-page resource library, then jump to it.
 */
(function (Drupal, once) {
  Drupal.behaviors.tsResourceTypeFacetLink = {
    attach(context) {
      once("resource-type-facet-link", "a[data-resource-type-facet]", context).forEach((link) => {
        link.addEventListener("click", (event) => {
          const url = new URL(link.href, window.location.href);
          // Only handle links to this page; otherwise navigate normally.
          if (url.pathname !== window.location.pathname) return;

          const anchor = decodeURIComponent(url.hash.slice(1));
          const target = document.querySelector(`a[name="${CSS.escape(anchor)}"], #${CSS.escape(anchor)}`);
          const block = target && (target.closest(".block") || target.parentElement);
          const tid = link.dataset.resourceTypeFacet;
          const checkbox = block && block.querySelector(`input[type="checkbox"][name="type[${tid}]"]`);
          if (!checkbox) return;

          // Make Initiatives the only selected type, then let BEF auto-submit.
          block.querySelectorAll('input[type="checkbox"][name^="type["]').forEach((input) => {
            input.checked = input === checkbox;
          });
          checkbox.dispatchEvent(new Event("change", { bubbles: true }));
        });
      });
    },
  };
})(Drupal, once);
