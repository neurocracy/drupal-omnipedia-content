<?php

namespace Drupal\omnipedia_content_legacy;

/**
 * An interface for all OmnipediaElementLegacy plug-ins.
 */
interface OmnipediaElementLegacyInterface {

  /**
   * Get the hook_theme array for this element.
   *
   * When implementing a plug-in, each plug-in class is required to implement
   * this method.
   *
   * @return array
   *   An array with a valid format for \hook_theme(). Note that this will
   *   automatically be nested under this plug-in's ID, e.g.:
   *
   *   @code
   *   [
   *     'variables' => [
   *       'variable1'  => '',
   *       'variable2'  => '',
   *     ],
   *     'template'  => 'my-element',
   *   ]
   *   @endcode
   *
   *   Then becomes:
   *
   *   @code
   *   [
   *     'my_element' => [
   *       'variables' => [
   *         'variable1'  => '',
   *         'variable2'  => '',
   *       ],
   *       'template'  => 'my-element',
   *     ]
   *   ]
   *   @endcode
   *
   * @see \hook_theme()
   */
  public static function getTheme(): array;

  /**
   * Build a render array for this element.
   *
   * When implementing a plug-in, each plug-in class is required to implement
   * this method.
   *
   * @return array
   *   This element's render array.
   */
  public function getRenderArray(): array;

}
