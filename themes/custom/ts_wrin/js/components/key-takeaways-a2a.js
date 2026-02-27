(function (Drupal, once) {
  function closePopover(trigger, popover) {
    if (!trigger || !popover) return;
    popover.hidden = true;
    trigger.setAttribute("aria-expanded", "false");
  }

  function openPopover(trigger, popover) {
    if (!trigger || !popover) return;
    popover.hidden = false;
    trigger.setAttribute("aria-expanded", "true");

    // Initialize AddToAny buttons inside the newly shown popover.
    if (window.a2a && typeof window.a2a.init_all === "function") {
      window.a2a.init_all();
    }
  }

  function closeAllPopovers(context) {
    const root = context || document;
    root
      .querySelectorAll(".key-takeaways__share-trigger")
      .forEach((trigger) => {
        const wrapper = trigger.closest(".key-takeaways__share");
        const popover =
          wrapper && wrapper.querySelector(".key-takeaways__share-popover");
        closePopover(trigger, popover);
      });
  }

  Drupal.behaviors.wriKeyTakeawaysShare = {
    attach(context) {
      // Ensure A2A binds to kits rendered by LB/BigPipe/AJAX.
      if (window.a2a && typeof window.a2a.init_all === "function") {
        window.a2a.init_all();
      }

      // Bind triggers once.
      once(
        "wriKeyTakeawaysShare",
        ".key-takeaways__share-trigger",
        context,
      ).forEach((trigger) => {
        const wrapper = trigger.closest(".key-takeaways__share");
        const popover =
          wrapper && wrapper.querySelector(".key-takeaways__share-popover");
        if (!popover) return;

        // Toggle on click.
        trigger.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();

          const isOpen = trigger.getAttribute("aria-expanded") === "true";

          // Close others first (nice UX).
          closeAllPopovers(document);

          if (!isOpen) {
            openPopover(trigger, popover);

            // Focus first link inside popover for keyboard users.
            const firstLink = popover.querySelector(
              "a, button, [tabindex]:not([tabindex='-1'])",
            );
            if (firstLink) firstLink.focus();
          } else {
            closePopover(trigger, popover);
            trigger.focus();
          }
        });

        // Close on Escape when focus is inside popover.
        popover.addEventListener("keydown", (e) => {
          if (e.key === "Escape" || e.key === "Esc") {
            e.preventDefault();
            closePopover(trigger, popover);
            trigger.focus();
          }
        });

        // Close when clicking outside.
        document.addEventListener(
          "click",
          (e) => {
            if (!wrapper.contains(e.target)) {
              closePopover(trigger, popover);
            }
          },
          { passive: true },
        );

        // Close on focus leaving the wrapper (keyboard tabbing away).
        wrapper.addEventListener("focusout", (e) => {
          // If focus moved to an element outside wrapper, close.
          if (!wrapper.contains(e.relatedTarget)) {
            closePopover(trigger, popover);
          }
        });

        // Start closed.
        closePopover(trigger, popover);
      });
    },
  };
})(Drupal, once);
