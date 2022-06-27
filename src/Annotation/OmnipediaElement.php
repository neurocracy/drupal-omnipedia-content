<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines an OmnipediaElement annotation object.
 *
 * @see \Drupal\omnipedia_content\PluginManager\OmnipediaElementManagerInterface
 *
 * @see plugin_api
 *
 * @Annotation
 */
class OmnipediaElement extends Plugin {

  /**
   * The HTML element name for this plug-in.
   *
   * For example, if the element is "<my-element></my-element>", the element
   * name would be "my-element".
   *
   * @var string
   */
  public string $html_element;

  /**
   * Whether children of this element should be automatically rendered.
   *
   * If this is false, the plug-in is responsible for rendering any child
   * elements via the element plug-in manager.
   *
   * @var boolean
   */
  public bool $render_children = true;

  /**
   * The human readable title of the element.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $title;

  /**
   * A brief human readable description of the element.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $description;

}
