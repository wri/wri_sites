/**
 * @file
 *
 * Wri move main_content_b article end content after extra body content.
 */

export default function (context) {
  if (context == document) {
    const scope = context === document ? document : context;
    const textBlock = scope.querySelector(
      ".block-layout-builder > .main-content",
    );
    const postBodyContent = scope.querySelector(".post-body-content");

    if (!textBlock || !postBodyContent) {
      return;
    }

    if (!postBodyContent.innerHTML.trim()) {
      return;
    }
    textBlock.appendChild(postBodyContent);
    postBodyContent.classList.add("article-b", "margin-bottom-lg");
  }
}
