/**
 * @file
 *
 * WRI CTA Banners - random with a thank you message.
 */

export default function(context) {
  const modals = context.querySelectorAll(".region-top-modals .block-modal");
  const ctaCookie = "wri_cta_displayed";
  const thankYouCookie = "wri_cta_thank_you";

  // Accessibility: aria-live region for announcements
  const announceRegion = document.createElement("div");
  announceRegion.setAttribute("aria-live", "assertive");
  announceRegion.setAttribute("role", "alert");
  announceRegion.style.position = "absolute";
  announceRegion.style.left = "-9999px";
  document.body.appendChild(announceRegion);

  function announce(message) {
    announceRegion.textContent = "";
    // Timeout ensures the message is re-announced when modal changes.
    setTimeout(() => {
      announceRegion.textContent = message;
    }, 100);
  }

  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    return parts.length === 2
      ? parts
          .pop()
          .split(";")
          .shift()
      : null;
  }

  function setCookie(name, value, days) {
    const date = new Date();
    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = `${name}=${value}; expires=${date.toUTCString()}; path=/`;
  }

  function closeModal(modal) {
    modal.style.display = "none";
    setCookie(
      getCookie(thankYouCookie) ? thankYouCookie : ctaCookie,
      "true",
      1
    );
    document.removeEventListener("keydown", closeOnEscape);
    document.removeEventListener("click", clickOutsideModal);
    announce("Modal closed.");
  }

  function closeOnEscape(event) {
    if (event.key === "Escape") {
      const visibleModal = context.querySelector(
        '.block-modal[style*="display: block"]'
      );
      if (visibleModal) closeModal(visibleModal);
    }
  }

  function clickOutsideModal(event) {
    const visibleModal = context.querySelector(
      '.block-modal[style*="display: block"]'
    );
    if (
      visibleModal &&
      !visibleModal.querySelector(".modal_inner").contains(event.target)
    ) {
      closeModal(visibleModal);
    }
  }

  function showModal(modal) {
    modal.style.display = "block";
    modal.querySelector(".modal-close").addEventListener("click", function() {
      closeModal(modal);
    });
    document.addEventListener("keydown", closeOnEscape);
    document.addEventListener("click", clickOutsideModal);
    announce(
      "A message for you: " +
        modal.querySelector(".field--name-field-title").textContent
    );
  }

  function displayThankYouModal() {
    const thankYouMessage = document.createElement("div");
    thankYouMessage.classList.add("block-modal", "thank-you-modal");
    thankYouMessage.innerHTML = `
      <div class="modal_inner" role="dialog" aria-labelledby="thankYouTitle" aria-describedby="thankYouMessage">
        <div class="modal_inner_wrapper">
          <div class="left-text">
            <h2 id="thankYouTitle">Thank You!</h2>
            <p id="thankYouMessage">Thank you for your donation!</p>
          </div>
          <button type="button" class="hidden ui-button modal-close" title="Close">
            <span class="ui-icon-closethick"></span>Close
          </button>
        </div>
      </div>`;
    document.body.appendChild(thankYouMessage);
    showModal(thankYouMessage);
  }

  function displayRandomModal() {
    if (!modals.length) return;
    const randomIndex = Math.floor(Math.random() * modals.length);
    const selectedModal = modals[randomIndex];
    selectedModal.setAttribute("role", "dialog");
    selectedModal.setAttribute("aria-labelledby", "modalTitle");
    selectedModal.setAttribute("aria-describedby", "modalMessage");
    selectedModal.querySelector(".field--name-field-title").id = "modalTitle";
    selectedModal.querySelector(".field--name-field-intro").id = "modalMessage";
    showModal(selectedModal);
  }

  const classyCookie = getCookie("classy_donation");
  const hasDonated = classyCookie && classyCookie === "true";

  if (!getCookie(thankYouCookie) && hasDonated) {
    displayThankYouModal();
  } else if (!getCookie(ctaCookie)) {
    displayRandomModal();
  }
}
