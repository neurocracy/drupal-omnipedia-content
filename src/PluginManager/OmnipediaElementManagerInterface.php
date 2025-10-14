<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\PluginManager;

/**
 * Defines an interface for OmnipediaElement plug-in managers.
 */
interface OmnipediaElementManagerInterface {

  /**
   * Convert all elements that have element plug-ins into standard HTML.
   *
   * @param string $html
   *   The HTML to parse.
   *
   * @param bool $forceRenderChildren
   *   If true, will ignore the 'render_children' definition property of
   *   plug-ins and always render their children. Defaults to false. This is
   *   intended to be called by plug-ins to render their children.
   *
   * @return string
   *   The $html parameter with any custom elements that have OmnipediaElement
   *   plug-ins rendered as standard HTML.
   */
  public function convertElements(
    string $html,
    bool $forceRenderChildren = false
  ): string;

  /**
   * Get theme definitions from all element plug-ins.
   *
   * @return array
   *   An array keyed by plug-in IDs, each containing an array with the
   *   following values:
   *
   *   - 'provider': The machine name of the provider, e.g. the module.
   *
   *   - 'theme': The theme array, containing elements for \hook_theme().
   */
  public function getTheme(): array;

  /**
   * Get all logged element errors.
   *
   * @return array
   *   An array of element errors.
   *
   * @see \Drupal\omnipedia_content\PluginManager\OmnipediaElementManager::elementErrors
   *   Describes the error structure.
   */
  public function getElementErrors(): array;

  /**
   * Get all logged element errors, formatted for form validation messages.
   *
   * @return array
   *   An array of element errors.
   *
   * @see \Drupal\omnipedia_content\PluginManager\OmnipediaElementManager::elementErrors
   *   Describes the error structure.
   */
  public function getElementFormValidationErrors(): array;

}
