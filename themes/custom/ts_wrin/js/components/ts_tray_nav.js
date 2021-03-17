/**
 * @file
 *
 * TS Tray Nav.
 */
export default function(context) {
  if (context == document) {
    let trayNavWrapper = document.querySelector("#tray-nav-wrapper");
    let pageBody = document.querySelector("body");

    document.querySelectorAll(".tray-toggle").forEach(toggle => {
      toggle.addEventListener("click", event => {
        let dataAttr = event.currentTarget.dataset.tray;
        let dataIdItem = document.querySelector(
          ".region-wrin-custom ." + dataAttr
        );

        if (
          trayNavWrapper.classList.contains("show-tray") &&
          dataIdItem.classList.contains("show")
        ) {
          // Close the tray if the same button is clicked twice.
          trayNavWrapper.classList.remove("show-tray");
          pageBody.classList.remove("noscroll");
          document.querySelector(".toggled").classList.remove("toggled");
        } else if (
          trayNavWrapper.classList.contains("show-tray") &&
          !dataIdItem.classList.contains("show")
        ) {
          // Switch tray contents but keep the tray open.
          hideTrayContents();
          dataIdItem.classList.add("show");
          document.querySelector(".toggled").classList.remove("toggled");
          event.currentTarget.classList.add("toggled");
        } else {
          // Initial click.
          trayNavWrapper.classList.add("show-tray");
          pageBody.classList.add("noscroll");
          event.currentTarget.classList.add("toggled");
        }

        // Make sure only the selected tray content shows.
        if (dataAttr) {
          hideTrayContents();
          dataIdItem.classList.add("show");
        }
      });
    });

    let hideTrayContents = () => {
      document.querySelectorAll(".tray-contents").forEach(tray => {
        tray.classList.remove("show");
      });
    };

    // Clicking on "X" closes tray no matter what.
    document.querySelectorAll(".tray-nav__close").forEach(button => {
      button.addEventListener("click", event => {
        trayNavWrapper.classList.remove("show-tray");
        pageBody.classList.remove("noscroll");
        document.querySelector(".toggled").classList.remove("toggled");
      });
    });

    // Make sure the Esc key works.
    document.addEventListener("keyup", function(e) {
      if (e.key === "Escape") {
        trayNavWrapper.classList.remove("show-tray");
        pageBody.classList.remove("noscroll");
        if (document.querySelector(".toggled") !== null) {
          document.querySelector(".toggled").classList.remove("toggled");
        }
      }
    });
  }
}
