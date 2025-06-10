# Ts Wrin - Grid

A base theme for a Drupal 8 site using CSS Grid Layout, Webpack, and JS ES6.
Requires
[components](https://www.drupal.org/project/components) module to be added to
the project build for the visual grid checker to be useful.

Check out the Wiki to learn more about this theme:
https://github.com/thinkshout/ts_wrin/wiki

## Setup and use

You can run the commands below from the theme directory, but the final css files
are placed in the "dist" directory and committed.

- Run `npm install` to install dependencies.
- Run `npm run build` to run the production build. See note above. 
- Run `npm run build:dev` to run the development build. Do not commit changes
  from development mode!
- Run `npm run start` to watch for changes and build (see note above).
- Run `npm run start:dev` to watch for changes and build in development mode.
  In this mode, your local site should automatically refresh when files change.
- Run `npm run prettier` to fix styling errors in js/scss files. This is ran
  automatically if you commit files while inside the theme directory.

Note: `*:dev` command output is written to `dist_dev`, which cannot be
committed. To commit your work in `dist`, run `npm run build` or
`npm run start`, then commit your changes.

## Javascript compilation

Javascript is transpiled from ES6 ES5 using Babel, and then minified and
tree-shook by Webpack.

For more information on ES6, see https://dev.to/srebalaji/es6-for-beginners-with-example-c7
