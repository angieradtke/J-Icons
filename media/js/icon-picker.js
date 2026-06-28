/**
 * Icon Picker — Search & Filter
 */
(function() {
  'use strict';

  document.addEventListener('DOMContentLoaded', function() {
    const picker = document.querySelector('.jicon-picker');
    if (!picker) return;

    const searchInput = picker.querySelector('.jicon-search-input');
    const options = picker.querySelectorAll('.jicon-option');

    if (!searchInput) return;

    searchInput.addEventListener('input', function(e) {
      const query = e.target.value.toLowerCase();

      options.forEach(option => {
        const label = option.querySelector('.jicon-label');
        const text = label ? label.textContent.toLowerCase() : '';
        
        if (query === '' || text.includes(query)) {
          option.classList.remove('hidden');
        } else {
          option.classList.add('hidden');
        }
      });

      updateStats();
    });

    function updateStats() {
      const visible = picker.querySelectorAll('.jicon-option:not(.hidden)').length;
      const checked = picker.querySelectorAll('input[type="checkbox"]:checked').length;
      
      const statsDiv = document.querySelector('.jicon-stats');
      if (statsDiv) {
        statsDiv.textContent = `${checked} ausgewählt von ${visible} sichtbar`;
      }
    }

    picker.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
      checkbox.addEventListener('change', updateStats);
    });

    updateStats();
  });
})();
