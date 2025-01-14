// Ensure the Flourish global object exists before loading the script.
if (typeof window.Flourish === "undefined") {
  window.Flourish = {};
}

// Function to add the analytics listener when Flourish is ready.
function addAnalyticsListener() {
  if (typeof window.Flourish.addAnalyticsListener === "function") {
    window.Flourish.addAnalyticsListener(function (event) {
      var action = event.action;

      var category = "flourish_engagement";
      var label = event.story_id
        ? "Flourish story " + event.story_id
        : "Flourish visualization " + event.visualisation_id;

      var dataLayerEvent = {
        event: action,
        event_category: category,
        event_label: label,
        event_action: action,
      };

      // Include additional data from the event object.
      Object.keys(event).forEach(function (key) {
        if (key !== "action") {
          dataLayerEvent[key] = event[key];
        }
      });

      // Push the event to the dataLayer.
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push(dataLayerEvent);
    });
  } else {
    console.warn("Flourish.addAnalyticsListener is not available.");
  }
}

// Wait for DOMContentLoaded to ensure Flourish script has run.
document.addEventListener("DOMContentLoaded", function () {
  // Add the analytics listener after the embed script has initialized.
  if (typeof window.Flourish.addAnalyticsListener === "function") {
    addAnalyticsListener();
  } else {
    console.warn(
      "Flourish.addAnalyticsListener is not available after DOMContentLoaded."
    );
  }
});

// Dynamically load the Flourish embed script if not already loaded.
if (!document.querySelector('script[src="https://public.flourish.studio/resources/embed.js"]')) {
  var script = document.createElement('script');
  script.src = "https://public.flourish.studio/resources/embed.js";
  script.async = true;
  script.onload = function () {
    // Add the listener if Flourish is ready after script load.
    addAnalyticsListener();
  };
  document.head.appendChild(script);
}
