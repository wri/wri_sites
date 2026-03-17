/**
 * @file
 *
 * Wri show insights.
 */

export default function (context) {
  if (context != document && context.classList.contains("article-b")) {
    context.classList.add("reveal");
  }
}
