NOTE: We have two patches that touch the entity_embed 1.7 version of the compiled web/modules/contrib/entity_embed/js/build/drupalentity.js

Since both patches on D.O. presume the entity_embed 1.7 base version of drupalentity.js, the second patch always fails. The local patch at `web/profiles/contrib/wri_sites/patches/entity_embed--fix-ckeditor-icon-reference--3531672.patch` was created by initially removing the `drupalentity.js` section of the patch, installing both patches, then manually regerating drupalentity.js using `npm run build`.

The local patch was then updated to have the +drupalentity.js from 'Entity embed dialog can no longer use custom data- attributes in CKEditor 5 https://www.drupal.org/project/entity_embed/issues/3410132": "https://www.drupal.org/files/issues/2025-08-07/3410132-MR-31_support-custom-data-attribs.diff-14.patch' as the "expected" start line, and the locally rebuilt drupalentity.js as the + of this local patch.

This works, and we have locked entity_embed to the 1.7 version. If there is an urgent need to upgrade `entity_embed` in the future (this was written on Dec 4, 2025), check if issues `3410132` or `3531672` have been merged into the new version, and remove the local patch (check that the remaining patch has been re-rolled).

If you need to upgrade and neither patch has been merged (just re-rolled), rebuild the local patch:
 - check the patch order: currently `entity_embed--fix-ckeditor-icon-reference` is last and fails.
 - copy the failing patch to the local patch directory, and remove the `drupalentity.js` section.
 - update composer to point at the new local patch. `composer install` should apply the local patch cleanly.

Next, manually compile `drupalentity.js` from the patched source files:
 - `cd web/modules/contrib/entity_embed`
 - `rm -rf node_modules package-lock.json`
 - `rm -rf js/build/drupalentity.js`
 - `npm install`
 - `npm run build`

In your local patch, paste the +drupalentity.js from the working D.O. patch as the start line, and your newly compiled `drupalentity.js` as the +.

Test by going back to Flagship root and running:
 - `rm -rf web/modules/contrib/entity_embed`
 - `composer install`

All patches should now install cleanly.

