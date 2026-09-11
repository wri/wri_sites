/**
 * @file
 * Open/close toggle and side-scroll carousel for the "Looking for something
 * specific" block.
 */
(function ($, Drupal, once) {
  var prevArrow = '<button class="slick-prev"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg></button>';
  var nextArrow = '<button class="slick-next"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path fill="currentColor" d="M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg></button>';

  Drupal.behaviors.wriLookingTerms = {
    attach: function (context) {
      once('looking-terms-toggle', '.looking-terms', context).forEach(function (block) {
        var $block = $(block);
        var $toggle = $block.find('.looking-terms-toggle');
        var $content = $block.find('> .looking-terms-content');

        // Only the Listing's own carousel - not the plain Related tags list
        // nested inside each card, which happens to share the same Views
        // ".view-content" markup one level deeper.
        var $carouselTrack = $block.find('.looking-terms-listing .view-content').filter(function () {
          return $(this).closest('.looking-terms-related-tags').length === 0;
        });
        var slickInitialized = false;

        // Collapsed by default.
        $content.attr('hidden', true);
        $toggle.attr('aria-expanded', 'false');

        $toggle.on('click', function () {
          var isOpen = $block.hasClass('is-open');
          $block.toggleClass('is-open', !isOpen);
          $toggle.attr('aria-expanded', String(!isOpen));
          $content.attr('hidden', isOpen);

          if (isOpen || !$carouselTrack.length) {
            return;
          }

          // Slick measures slide widths from its container, which reports 0
          // while this panel is [hidden]. Initialize the carousel (or, if
          // already initialized, recalculate its slide positions) only once
          // the panel is actually visible.
          if (!slickInitialized) {
            slickInitialized = true;
            $carouselTrack.slick({
              prevArrow: prevArrow,
              nextArrow: nextArrow,
              infinite: false,
              dots: false,
              arrows: true,
              slidesToShow: 4,
              slidesToScroll: 1,
              responsive: [
                {
                  breakpoint: 1440,
                  settings: {
                    slidesToShow: 3
                  }
                },
                {
                  breakpoint: 1024,
                  settings: {
                    slidesToShow: 2
                  }
                },
                {
                  breakpoint: 768,
                  settings: {
                    centerMode: true,
                    centerPadding: '10px',
                    slidesToShow: 1
                  }
                }
              ]
            });
          }
          else {
            $carouselTrack.slick('setPosition');
          }
        });
      });
    }
  };
})(jQuery, Drupal, once);
