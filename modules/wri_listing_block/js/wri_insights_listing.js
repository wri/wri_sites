/**
 * @file
 *
 * Wri show insights.
 */

function hideSingleItemsInRow() {
  const viewportWidth = window.innerWidth;
  if (viewportWidth > 500) {
    const items = document.querySelectorAll('.desktop-3 ul.listing-items > li');

    // 1. Reset visibility to calculate accurately on resize
    items.forEach(item => item.style.display = '');

    // 2. Group items by their vertical offset top position
    const rows = {};
    items.forEach(item => {
      const topPosition = item.offsetTop;
      if (!rows[topPosition]) {
        rows[topPosition] = [];
      }
      rows[topPosition].push(item);
    });

    // 3. Find rows with only 1 item and hide that item
    Object.values(rows).forEach(rowItems => {
      if (rowItems.length === 1) {
        rowItems[0].style.display = 'none';
      }
    });
  }
}

// Run on load and whenever the screen resizes
window.addEventListener('load', hideSingleItemsInRow);
window.addEventListener('resize', hideSingleItemsInRow);
