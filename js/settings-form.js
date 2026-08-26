(function () {
  'use strict';

  Drupal.behaviors.analyzeSettingsCards = {
    attach: function (context) {
      var cards = context.querySelectorAll
        ? context.querySelectorAll('.analyze-plugin-card')
        : [];

      cards.forEach(function (card) {
        var checkbox = card.querySelector('input[type="checkbox"]');
        if (!checkbox || checkbox.dataset.analyzeCardBound) {
          return;
        }
        checkbox.dataset.analyzeCardBound = '1';
        checkbox.addEventListener('change', function () {
          if (this.checked) {
            card.classList.add('is-enabled');
          } else {
            card.classList.remove('is-enabled');
          }
        });
      });
    }
  };
})();
