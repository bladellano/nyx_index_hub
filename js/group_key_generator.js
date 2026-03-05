/**
 * @file
 * JavaScript for Group Key UUID generation.
 */

(function (Drupal) {
  'use strict';

  /**
   * Generates a RFC4122 version 4 compliant UUID.
   *
   * @return {string}
   *   A UUID string.
   */
  function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
      var r = Math.random() * 16 | 0;
      var v = c === 'x' ? r : (r & 0x3 | 0x8);
      return v.toString(16);
    });
  }

  /**
   * Sets the UUID value in the group key input field.
   */
  function setGroupKey() {
    var input = document.getElementById('group-key-input');
    if (input) {
      var newUUID = generateUUID();
      input.value = newUUID;

      // Trigger change event for Drupal states API.
      var event = new Event('change', { bubbles: true });
      input.dispatchEvent(event);

      // Visual feedback.
      input.classList.add('group-key-updated');
      setTimeout(function() {
        input.classList.remove('group-key-updated');
      }, 1000);
    }
  }

  Drupal.behaviors.groupKeyGenerator = {
    attach: function (context, settings) {
      // Attach click handler to generate button.
      var generateBtn = context.querySelector('.generate-group-key-btn');
      if (generateBtn) {
        generateBtn.removeEventListener('click', setGroupKey);
        generateBtn.addEventListener('click', function(e) {
          e.preventDefault();
          setGroupKey();
        });
      }

      // Auto-generate UUID on page load if field is empty (new group only).
      var input = context.querySelector('#group-key-input');
      if (input && !input.value.trim()) {
        setGroupKey();
      }
    }
  };

})(Drupal);
