/**
 * @file
 *
 * Wri show insights.
 */

export default function (context) {
  if (context == document) {
    const bodyClasses = context.body.classList;
    if (bodyClasses.contains("abjs-test")) {} else {
      let articleBody = context.querySelectorAll(
        ".article-columns, .article-b"
      );

      articleBody.forEach(function (articleBody) {
        articleBody.classList.add("reveal");
      });
    }
  }
  else if (context.classList.contains("article-b")) {
    context.classList.add("reveal");
  }
}
