# wri_sites
WRI profile

## How to use this profile

Find instructions on creating a new project using this profile at the https://github.com/thinkshout/wri-starter-kit repo.

## Post-install recommended configuration changes:

After initial install, we recommend doing the following:

1. Enable solr: `terminus solr:enable [your-site-name]` -- or you can do this using the Pantheon Dashboard.
2. Pull your site from dev -> test -> live so you can start making configuration changes without fear of losing them with new deploys.
3. On live, create a homepage `/node/add/homepage`
4. On live, create the Site title, etc at `/admin/config/system/site-information` including linking to the homepage you just created.
5. On live, add Topics, Types and Region terms to the taxonomy (or pull via a migration).
6. On live, enable the publishing workflow for all content types at `/admin/config/workflow/workflows/manage/content_publishing`
