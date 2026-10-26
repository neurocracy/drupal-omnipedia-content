<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an OmnipediaElement attribute for plug-in discovery.
 *
 * @see \Drupal\omnipedia_content\PluginManager\OmnipediaElementManagerInterface
 *
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class OmnipediaElement extends Plugin {

  /**
   * Constructs an OmnipediaElement attribute.
   *
   * @param string $id
   *   The plug-in ID.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The human readable title of the plug-in.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   A brief human readable description of the plug-in.
   *
   * @param string $html_element
   *   The HTML element name for this plug-in. For example, if the element is
   *   "<my-element></my-element>", the element name would be "my-element".
   *
   * @param bool $render_children
   *   Whether children of this element should be automatically rendered. If
   *   this is false, the plug-in is responsible for rendering any child
   *   elements via the element plug-in manager.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $title,
    public readonly TranslatableMarkup $description,
    public readonly string $html_element,
    public readonly bool $render_children = true,
  ) {}

}
