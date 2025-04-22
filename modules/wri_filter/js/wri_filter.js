/**
 * @file
 *
 * WRI content filter logic.
 */

(($, Drupal) => {
  // Load all filters array.
  let allFilters = drupalSettings.wri_filter.allFilters;

  // Get a cookie value by name.
  let getCookie = name => {
    let cookie = {};
    document.cookie.split(';').forEach(function (el) {
      let [k,v] = el.split('=');
      cookie[k.trim()] = v;
    })
    return cookie[name];
  }

  // Get the human readable name of a filter cookie.
  let getFilterCookieHumanReadable = filterId => {
    let name = '';
    // Loop over all filters to find the name of the current filter from its id.
    Object.keys(allFilters).forEach( filter => {
      if (filter === filterId) {
        let filterName = allFilters[filter]['name'];
        name = filterName.toLowerCase();
      }
    });
    return name;
  }

  // Get the filter cookie id from the human readable name.
  let getFilterCookieId = topicName => {
    let id = '';
    // Loop over all filters to find the id of the current filter from its name.
    Object.keys(allFilters).forEach( filter => {
      if (allFilters[filter]['name'] === topicName) {
        id = filter;
      }
    });
    return id;
  }

  // Set the cookie based on the provided href.
  let setCookie = href => {
    fetch(href).then(() => {
      // Build a url for redirection.
      let url = new URL(document.location.toString());
      let params = new URLSearchParams(url.search)
      params.delete('topic');
      let topicCookie = getCookie(drupalSettings.wri_filter.filterCookieName);
      if (topicCookie) {
        params.append('topic', getFilterCookieHumanReadable(topicCookie));
      }
      // Add any params.
      url.search = params;
      // Reload with new url.
      document.location.replace(url.toString());
    });
  }

  let url = new URL(document.location.toString());
  let topicSearchFilter = url.searchParams.get('topic');
  let topicCookie = getCookie(drupalSettings.wri_filter.filterCookieName);
  let allTopicsLink = document.querySelector("a.sitewide-content-filter.all-topics");
  let humanReadableCookie = getFilterCookieHumanReadable(topicCookie);
  let topicChanged = false;
  let pageIsFilterable = drupalSettings.wri_filter.pageIsFilterable;

  // Check for a change to the topic filter via url param change.
  if (pageIsFilterable && topicSearchFilter) {
    if (topicSearchFilter !== humanReadableCookie) {
      let cookieID = getFilterCookieId(topicSearchFilter);
      setCookie('/content-filter/' + cookieID);
      topicChanged = true;
    }
  }

  // If topic cookie is present update link class and push new url.
  if (topicCookie && !topicChanged) {
    // Add active class to active topic filter link.
    let activeTopicFilterLink = document.querySelector('a[data-filter="' + humanReadableCookie + '"');
    activeTopicFilterLink.classList.add('active');
    allTopicsLink.classList.remove('active');

    // If this page is filterable, add the appropriate url pram
    if (pageIsFilterable) {
      // Build url for replacement.
      let url = new URL(document.location.toString());
      let params = new URLSearchParams(url.search)
      // Clear out any existing topic filter and add the new one.
      params.delete('topic');
      params.append('topic', humanReadableCookie);

      // Change url params.
      url.search = params;
      window.history.replaceState(null, null, url);
    }
  }
  else {
    allTopicsLink.classList.add('active');
  }

  // Click handling for filter links.
  document.querySelectorAll("a.sitewide-content-filter").forEach(filter => {
    filter.addEventListener("click", event => {
      event.preventDefault();
      let href = filter.getAttribute('href');
      // Fetch the url to set the cookie.
      setCookie(href);
    });
  });

  // Remove click handling
  let remove = document.querySelector("a.sitewide-content-filter-remove");
  if (remove) {
    remove.addEventListener("click", event => {
      event.preventDefault();
      let href = event.target.getAttribute('href');
      // Fetch url to unset cookie.
      fetch(href).then(() => {
        // Build a url for reloading.
        let url = new URL(document.location.toString());
        // Delete only topic params.
        let params = new URLSearchParams(url.search)
        params.delete('topic');
        url.search = params;
        // Reload with new url.
        document.location.replace(url.toString());
      });
    });
  }

})(jQuery, Drupal);
