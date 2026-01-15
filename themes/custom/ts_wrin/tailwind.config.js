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
    preflight: false,
  },
  theme: {
    extend: {
      // Keep Tailwind defaults, add your project screens.
      screens: {
        ph: "501px",
        xxl: "2000px",
      },

      // Map Tailwind colors to your CSS vars.
      colors: {
        black: "var(--color-black)",
        white: "var(--color-white)",

        neutral: {
          900: "var(--color-neutral-900)",
          800: "var(--color-neutral-800)",
          600: "var(--color-neutral-600)",
          500: "var(--color-neutral-500)",
          400: "var(--color-neutral-400)",
          200: "var(--color-neutral-200)",
        },

        gold: {
          500: "var(--color-gold-500)",
          300: "var(--color-gold-300)",
          100: "var(--color-gold-100)",
        },

        teal: {
          500: "var(--color-teal-500)",
        },

        blue: {
          500: "var(--color-blue-500)",
        },

        green: {
          500: "var(--color-green-500)",
        },
      },

      // Font tokens.
      fontFamily: {
        "heading-condensed": "var(--font-family-heading-condensed)",
        heading: "var(--font-family-heading)",
        serif: "var(--font-family-serif)",
      },

      // Spacing scale: keep Tailwind defaults where they already match,
      // but add your custom keys (7, 11, 15, 25, 30, 40).
      spacing: {
        7: "var(--spacer-7)",     // 30px
        11: "var(--spacer-11)",   // 45px
        15: "var(--spacer-15)",   // 60px
        25: "var(--spacer-25)",   // 100px
        30: "var(--spacer-30)",   // 120px
        40: "var(--spacer-40)",   // 160px
      },

      // Border widths you used in Fakewind patterns.
      borderWidth: {
        3: "3px",
      },

      // If you have rounded-7 etc in markup.
      borderRadius: {
        7: "var(--spacer-7)",
      },

      // If you used z-1 (Tailwind has z-0, z-10, etc by default).
      zIndex: {
        1: "1",
      },

      // Optional, if you want Tailwind container widths to match your grid:
      maxWidth: {
        grid: "var(--grid-max-width)",
        "grid-xxl": "var(--grid-max-width-xxl)",
        site: "var(--site-max-width)",
      },
    },
  },
  safelist: [],
};
