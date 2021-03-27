/**
 * @file
 *
 * WRI Media js.
 */
export default function(context) {
  const $ = jQuery;

  // Fix video controls being under the card text area.
  var eventListener = window.addEventListener("blur", function() {
    if (
      document.activeElement === document.querySelector(".media-oembed-content")
    ) {
      let parent = document.activeElement.parentElement;
      parent.classList.add("playing");
      setTimeout(function() {
        window.focus();
      }, 0);
    }
    window.removeEventListener("blur", eventListener);
  });
}
