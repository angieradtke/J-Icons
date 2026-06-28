/**
 * @package     Joomla.Plugin
 * @subpackage  System.jicon
 * @copyright   (C) 2026 Angie Radtke
 * @license     GNU/GPL
 * Backward Compatibility — FontAwesome to SVG
 */

(function() {
  'use strict';

  document.addEventListener('DOMContentLoaded', function() {
    convertFontAwesomeToSVG();
  });


  function convertFontAwesomeToSVG() {

    const faElements = document.querySelectorAll('i.fa, span.fa, .icon-fw');

    faElements.forEach(element => {

      const iconName = extractIconName(element.classList);
        if (!iconName) {
        return;
      }

      const jicon = document.createElement('j-icon');
      jicon.setAttribute('name', `#icon-${iconName}`);

      element.replaceWith(jicon);

    });
  }


  function extractIconName(classes) {

    for (const cls of classes) {

      if (cls.startsWith('icon-') && cls !== 'icon-fw') {
        return cls.substring(5);
      }



    }

    return null;
  }


})();
