(function ($, Drupal, drupalSettings, once) {
  Drupal.behaviors.tsSlick = {
    attach: function (context, settings) {
      if (context == document) {

        $('.carousel-with-tabs .paragraph--type--tab > .field--name-field-content').slick({

          prevArrow: '<button class="slick-prev"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" data-ab-filters-channel="12c1bb08-157e-42db-beff-97546529baa9"><!--! Font Awesome Free 7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) Copyright 2026 Fonticons, Inc. --><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/><script xmlns=""/></svg></button>',
          nextArrow: '<button class="slick-next"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" data-ab-filters-channel="45da98ff-cf06-4752-9e96-9eda67b54dd3"><!--! Font Awesome Free 7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) Copyright 2026 Fonticons, Inc. --><path fill="currentColor" d="M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/><script xmlns=""/></svg></button>',
          infinite: false,
          dots: false,
          arrows: true,
          slidesToShow: 3,
          slidesToScroll: 1,
          responsive: [
            {
              breakpoint: 1440,
              settings: {
                slidesToShow: 2
              }
            },
            {
              breakpoint: 1024,
              settings: {
                centerMode: true,
                centerPadding: '10px',
                slidesToShow: 1.25
              }
            },
            {
              breakpoint: 768,
              settings: {
                centerMode: true,
                centerPadding: '0px',
                slidesToShow: 1
              }
            },
            {
              breakpoint: 501,
              settings: {
                slidesToShow: 1
              }
            }
          ]
        });

        once('carousel-tabs', '.carousel-with-tabs', context).forEach(function (wrapper) {
          const $wrapper = $(wrapper);
          const $titles = $wrapper.find('.tab-titles h2');
          const $panels = $wrapper.find('.tab-content > div');

          // Set initial state
          $titles.first().addClass('active');
          $panels.first().addClass('active');

          $titles.on('click', 'a', function (e) {
            e.preventDefault();
            const $h2 = $(this).closest('h2');
            const index = $h2.index();

            $titles.removeClass('active');
            $panels.removeClass('active');

            $h2.addClass('active');
            $panels.eq(index).addClass('active');

            // Scroll active tab title into view on mobile
            $h2[0].scrollIntoView({
              behavior: 'smooth',
              block: 'nearest',
              inline: 'center'
            });

            // Recalculate slick dimensions on newly visible panel
            const $slick = $panels.eq(index).find('.slick-initialized');
            if ($slick.length) {
              $slick.slick('setPosition');
            }
          });
        });

      } // context
    } // attach
  }
})(jQuery, Drupal, drupalSettings, once);
