(function ($, Drupal) {
  Drupal.behaviors.deadlineCheck = {
    attach: function (context, settings) {

      // --- NEW: MANUAL CLOSED CHECK ---
      // Target the div with the specific class that contains the "Job is Closed" label
      const $closedField = $('.field-label-above:contains("Job is Closed")', context).parent();
      const isManuallyClosed = $closedField.text().includes('True');

      const $applyLink = $('.apply-link', context).first();

      // If the manual "Job is Closed" is True, trigger the replacement immediately
      if (isManuallyClosed) {
        if ($applyLink.length > 0) {
          $applyLink.replaceWith('<h3>The application deadline for this job posting has passed.</h3>');
        }
        return; // Exit early since the job is already closed
      }

      // --- EXISTING: AUTOMATIC DEADLINE CHECK ---
      const $labelElement = $('.field-label-inline:contains("Application Deadline")', context).first();

      if ($labelElement.length === 0) {
        return;
      }

      const $deadlineElement = $labelElement.next('time.datetime');

      if ($deadlineElement.length === 0) {
        return;
      }

      const deadlineISO = $deadlineElement.attr('datetime');

      if (deadlineISO) {
        const deadlineDate = new Date(deadlineISO);
        const currentDate = new Date();

        if (currentDate.getTime() > deadlineDate.getTime()) {
          if ($applyLink.length > 0) {
            $applyLink.replaceWith('<h3>The application deadline for this job posting has passed.</h3>');
          }
        }
      }
    }
  };
})(jQuery, Drupal);
