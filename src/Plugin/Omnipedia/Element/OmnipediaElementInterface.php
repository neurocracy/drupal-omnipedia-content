<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\Plugin\Omnipedia\Element;

/**
 * An interface for all OmnipediaElement plug-ins.
 */
interface OmnipediaElementInterface {

  /**
   * Get the name of the HTML element this plug-in handles.
   *
   * @return string
   *   The HTML element name, e.g. "my-element" for <my-element></my-element>.
   */
  public function getHtmlElementName(): string;

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

  /**
   * Determine whether this plug-in instance has logged any errors.
   *
   * @return boolean
   *   True if at least one error has been logged, false otherwise.
   */
  public function hasErrors(): bool;

  /**
   * Get any errors logged by this plug-in instance.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   An array of zero or more TranslatableMarkup objects, each an error
   *   message generated by this plug-in.
   */
  public function getErrors(): array;

}
