<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content_legacy\Plugin\Omnipedia\ElementLegacy;

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
   *   An array with a valid format for \hook_theme() containing one or more
   *   elements; example:
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
