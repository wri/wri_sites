/**
 * @file
 *
 * TS Tabs
 */
export default function(context) {
  const $ = jQuery;

  if (context == document) {
    const tabsBlock = $(".block-local-tasks-block .tabs"),
      userLoggedIn = $("body.user-logged-in");

    if (tabsBlock.length && userLoggedIn.length) {
      tabsBlock.prepend(
        '<span class="tabs-toggle">Edit<span class="arrow"></span></span>'
      );

      const tabsToggle = $(".tabs .tabs-toggle");
      tabsToggle.click(function() {
        if ($(this).hasClass("open")) {
          $(this).removeClass("open");
          tabsBlock.animate(
            {
              left: -200
            },
            500
          );
        } else {
          $(this).addClass("open");
          tabsBlock.animate(
            {
              left: 0
            },
            500
          );
        }
      });
    }
  }
}
