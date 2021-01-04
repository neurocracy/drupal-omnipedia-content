<?php

namespace Drupal\omnipedia_content\Plugin\Markdown\CommonMark\Extension;

use Drupal\markdown\Plugin\Markdown\CommonMark\Extension\HeadingPermalinkExtension as MarkdownHeadingPermalinkExtension;
use Drupal\omnipedia_content\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension as OmnipediaHeadingPermalinkExtension;
use League\CommonMark\EnvironmentInterface;

/**
 * Omnipedia Markdown heading permalink plug-in class.
 *
 * This extends the original with the only change being that it adds our own
 * heading permalink extension rather than the one that ships with CommonMark.
 *
 * This does not have an annotation as we don't want Drupal's plug-in system to
 * pick it up, because we alter the existing extension in the alter hook,
 * replacing it with this class.
 *
 * @see \omnipedia_content_markdown_extension_info_alter()
 *   Alter hook where we replace the Markdown module plug-in with this class.
 *
 * @see \Drupal\omnipedia_content\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension
 *   Our heading permalink CommonMark extension.
 */
class HeadingPermalinkExtension extends MarkdownHeadingPermalinkExtension {

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    $environment->addExtension(new OmnipediaHeadingPermalinkExtension());
  }

}
