## How to use these scripts

1. In your site's root `.env` file, you should have the following variables set:
  * `$TERMINUS_TOKEN` -- the Terminus authentication token, which can be generated from your Pantheon dashboard.
You can get this by looking it your `~/.terminus/cache/tokens/` folder, or print it out using
`cat ~/.terminus/cache/tokens/$(terminus whoami) | jq -r .token`

  * `$TERMINUS_SITE` -- the name of your Pantheon site, which is usually in the format `wriflagship` or `wri-brasil` for example.


2. Then read in the contents of the `.env` file using `export $(cat .env | xargs)` before running this script.

3. Within the site root, run:
```
./web/profiles/contrib/wri_sites/.ci/check-for-new-tag.sh
```
