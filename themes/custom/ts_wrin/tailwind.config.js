/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    // Theme templates + JS
    "./templates/**/*.twig",
    "./js/**/*.js",

    // wri_sites profile modules
    "../../../modules/**/*.{twig,js,php}",

    // Project-level custom modules (web/modules/custom)
    "../../../../../../modules/custom/**/*.{twig,php}",
  ],
  corePlugins: {
    // preflight: false,
  },
  theme: {
    extend: {},
  },
  safelist: [],
};
