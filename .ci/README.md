## How to use these scripts

Set a couple of environment variables to be able to run the script, namely
`$TERMINUS_TOKEN` (or we could check to see if the user is logged in before running `terminus -n auth:login`) and `$TERMINUS_SITE`.  I think it's fine to either tell people to run `export TERMINUS_SITE=yoursitename` in console or read in the contents of the `.env` file using `export $(cat .env | xargs)` before running this script.

Then, within the site root, run:
```
./web/profiles/contrib/wri_sites/.ci/check-for-new-tag.sh
```
