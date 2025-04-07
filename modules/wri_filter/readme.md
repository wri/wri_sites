# Adding views that will be filtered by site-wide filter
- Views should implement a contextual filter (aka argument) for topic taxonomy
  - Provide default value: Cookie variable from cookie
  - `STYXKEY_wri_filter` must have the `STYXKEY` prefix to not be cached by
    Pantheon.
  - Use raw cookie value should be checked
  - Fallback value should be `all`
  - Exception value should be `all`
  - Add validation criteria to `display all results from specified field`

# How the filter is remembered from page to page.
When you set the filter, a cookie is created called `STYXKEY_wri_filter`.
This cookie conforms to the format that Pantheon allows to bypass its varnish
layer and reach Drupal. Javascript also appends the selected filter value to
the URL with ?topic=XYZ. URLs visited with this link appended will also set
the cookie, and therefore the filter, for the site.

The filter returns contents that have been tagged with the term in any field.
That covers both the Primary Topic field and the Tags field.

# How the filter is set to only work on some pages
The pages that are allowed to use the filter are limited by this service:
src/FilterService.php which calls the getFilterablePages method to list pages
that can truly use the filter. The `wri_filter_views_pre_build` function in
wri_filter.module removes the filter argument (always the last argument in
the view) from the view blocks on pages not in the allowed list.

# Note about the Filter status block
The filter status block is made available when this module is enabled.
This block should be placed in the WRI_CUSTOM region.
Once the block is in that region it will display based on logic in page.

Due to some challenges with Pantheon Caching the filter_status block has a
different workflow than most blocks. The block is made available when this
module is enabled. It should be placed inside the WRIN Custom Region.
The markup for the block is added in the theme's page.html.twig and will display
when that block is present in that region. The markup from the block placement
output is hidden with CSS.
