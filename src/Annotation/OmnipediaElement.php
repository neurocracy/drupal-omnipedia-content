<?php

namespace Drupal\omnipedia_content\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an OmnipediaElement annotation object.
 *
 * @see \Drupal\omnipedia_content\OmnipediaElementManagerInterface
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
  public $html_element;

  /**
   * Whether children of this element should be automatically rendered.
   *
   * If this is false, the plug-in is responsible for rendering any child
   * elements via the element plug-in manager.
   *
   * @var boolean
   */
  public $render_children = true;

  /**
   * The human readable title of the element.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * A brief human readable description of the element.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
