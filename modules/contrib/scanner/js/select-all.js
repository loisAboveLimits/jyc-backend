/**
 * @file
 * Provides Javascript behaviors to handle select all/none with checkboxes.
 *
 * If there are any elements with the 'data-scanner-select-all' attribute,
 * 'Select all' and 'Select none' buttons are added after the first legend
 * element. When clicked they select/deselect any enabled checkboxes within the
 * element with the custom attribute.
 */

(function (Drupal, once) {
  /**
   * Inserts a themed button element after a target element.
   *
   * @param {HTMLElement} target
   *   The target element; the button will be inserted after it.
   * @param {string} label
   *   The visible label.
   * @param {string} screenReaderLabel
   *   The label for assistive technologies such as screen readers.
   * @param {string} identifier
   *   A button identifier that will be directly added as an attribute.
   *
   * @returns {HTMLElement}
   *   The button element.
   */
  function addButton(target, label, screenReaderLabel, identifier) {
    const html = Drupal.theme.scannerSelectButton(
      label,
      screenReaderLabel,
      identifier,
    );
    target.insertAdjacentHTML('afterend', html);
    return target.parentNode.querySelector(`[${identifier}]`);
  }

  /**
   * Adds 'Select all' and 'Select none' buttons to groups of checkboxes.
   *
   * @param {HTMLElement} container
   *   The element that contains the group of checkboxes.
   */
  function addButtons(container) {
    const checkboxes = container.querySelectorAll(
      'input[type=checkbox]:not([disabled])',
    );
    // Don't do anything if there aren't multiple checkboxes.
    if (checkboxes.length < 2) {
      return;
    }

    // Add the buttons after the legend element.
    const legend = container.querySelector('legend');
    const selectNoneButton = addButton(
      legend,
      Drupal.t('Select none'),
      container.dataset.scannerSelectNoneScreenReaderLabel,
      'data-scanner-select-none-button',
    );
    const selectAllButton = addButton(
      legend,
      Drupal.t('Select all'),
      container.dataset.scannerSelectAllScreenReaderLabel,
      'data-scanner-select-all-button',
    );

    /**
     * Updates the disabled attribute on the buttons.
     */
    const updateButtons = function () {
      // Filter the checkboxes by value.
      const filter = function (value) {
        return Array.prototype.filter.call(checkboxes, function (c) {
          return c.checked === value;
        });
      };
      selectNoneButton.disabled = filter(true).length === 0;
      selectAllButton.disabled = filter(false).length === 0;
    };

    /**
     * Returns an event handler that sets all checkboxes to a preset value.
     *
     * @param {boolean} value
     *   The value to set the checkboxes to.
     *
     * @returns {function}
     *   The event handler.
     */
    const updateCheckboxes = function (value) {
      return function () {
        checkboxes.forEach((checkbox) => {
          checkbox.checked = value;
        });
        // Dispatch a single 'change' event on an arbitrary checkbox in case
        // there's a listener on the checkboxes. This means it doesn't perfectly
        // simulate a user clicking all the checkboxes, but also in case of an
        // AJAX listener, it won't submit a request per checkbox.
        checkboxes[0].dispatchEvent(new Event('change'));
      };
    };

    // Make the buttons change the checkbox values.
    selectNoneButton.addEventListener('click', updateCheckboxes(false));
    selectAllButton.addEventListener('click', updateCheckboxes(true));

    // Update the buttons' disabled attribute every time a checkbox is changed.
    checkboxes.forEach((checkbox) => {
      checkbox.addEventListener('change', updateButtons);
    });

    // Update the buttons based on the initial state.
    updateButtons();
  }

  /**
   * Behaviors to handle select all/none with checkboxes.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the select all/none buttons.
   */
  Drupal.behaviors.scannerSelectAll = {
    attach(context) {
      once('scanner-select-all', '[data-scanner-select-all]', context).forEach(
        addButtons,
      );
    },
  };

  /**
   * Returns markup for a select all/none button.
   *
   * @param {string} label
   *   The visible text for the button.
   * @param {string} screenReaderLabel
   *   The label for assistive technologies.
   * @param {string} identifier
   *   An attribute to uniquely identify the button within a single group of
   *   checkboxes.
   *
   * @return {string}
   *   The button markup.
   */
  Drupal.theme.scannerSelectButton = function (
    label,
    screenReaderLabel,
    identifier,
  ) {
    return (
      `<button ${identifier} type="button" class="button button--extrasmall">` +
      `<span aria-hidden="true">${label}</span>` +
      `<span class="visually-hidden">${screenReaderLabel}</span>` +
      `</button>`
    );
  };
})(Drupal, once);
