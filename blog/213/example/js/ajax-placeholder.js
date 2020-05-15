/**
 * @file
 * AJAX placeholder strategy behaviors.
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.exampleAjaxPlaceholderStrategy = {
    attach: function (context, settings) {
      const intersectionObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const placeholderElement = entry.target
            intersectionObserver.unobserve(placeholderElement);
            this.load(placeholderElement);
          }
        })
      })

      $('[data-ajax-placeholder]', context).once('ajax-placeholder').each(function (placeholderElement) {
        intersectionObserver.observe(this);
      })
    },

    load: function (placeholderElement) {
      const ajax = new Drupal.ajax({
        url: '/ajax-placeholder-processor',
        progress: false,
        submit: placeholderElement.dataset.ajaxPlaceholder,
      })

      ajax.success = function (response, status) {
        // Call all provided AJAX commands.
        Object.keys(response || {}).forEach(i => {
          if (response[i].command && this.commands[response[i].command]) {
            if (!response[i].selector) {
              // Set selector by our element.
              response[i].selector = placeholderElement;
            }
            this.commands[response[i].command](this, response[i], status);
          }
        });
      };

      ajax.execute();
    },

    htmlStringToElement: function (htmlString) {
      htmlString = htmlString.trim();
      const template = document.createElement('template');
      template.innerHTML = htmlString;
      return template.content.firstChild;
    },
  };

})(jQuery, Drupal);
