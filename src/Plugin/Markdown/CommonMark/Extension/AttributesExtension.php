<?php

namespace Drupal\omnipedia_content\Plugin\Markdown\CommonMark\Extension;

use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Extension\Attributes\AttributesExtension as CommonMarkAttributesExtension;

/**
 * Omnipedia Markdown attributes plug-in class.
 *
 * Note that our alter hook only applies this class to the
 * 'webuni/commonmark-attributes-extensions' extension, meaning that this class
 * will be automatically ignored once the Markdown module switches over to the
 * 'league/commonmark-ext-attributes' extension shipped with CommonMark 1.5+.
 *
 * This does not have an annotation as we don't want Drupal's plug-in system to
 * pick it up.
 *
 * @see \omnipedia_content_markdown_extension_info_alter()
 *   Alter hook where we replace the Markdown module plug-in with this class.
 */
class AttributesExtension extends BaseExtension implements EnvironmentAwareInterface {

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    $environment->addExtension(new CommonMarkAttributesExtension());
  }

}
