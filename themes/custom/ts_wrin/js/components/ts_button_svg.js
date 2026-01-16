/**
 * @file
 *
 * TS Button SVG custom JS.
 */

export default function (context) {
  if (context == document) {
    const arrowLinks = document.querySelectorAll(
      "a.arrow-link, div.arrow-link a",
    );

    arrowLinks.forEach(function (arrowLink) {
      const innerDiv = document.createElement("DIV");

      innerDiv.innerHTML =
        '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 23 22" version="1.1">\n' +
        "    <title>1C8169EB-82BD-47E2-BD18-1E3111E875FB</title>\n" +
        '    <g id="SPEC-styles" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">\n' +
        '        <g id="CTA-content-blocks---large" transform="translate(-393.000000, -1792.000000)" stroke="#F3B229" stroke-width="2">\n' +
        '            <g id="block/CTA/small-teal" transform="translate(0.000000, 1750.000000)">\n' +
        '                <g id="icon/arrow/white" transform="translate(393.000000, 42.000000)">\n' +
        '                    <g id="Group-5" transform="translate(11.134146, 10.634146) scale(-1, 1) rotate(90.000000) translate(-11.134146, -10.634146) translate(1.134146, 0.134146)">\n' +
        '                        <polyline id="Stroke-1" points="0 13 10 21 20 13"/>\n' +
        '                        <line x1="10" y1="20" x2="10.3658537" y2="0.365853659" id="Line-7"/>\n' +
        "                    </g>\n" +
        "                </g>\n" +
        "            </g>\n" +
        "        </g>\n" +
        "    </g>\n" +
        "</svg>";

      arrowLink.appendChild(innerDiv);
    });
  }
}
