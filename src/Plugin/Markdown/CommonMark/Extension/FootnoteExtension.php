<?php

namespace Drupal\omnipedia_content\Plugin\Markdown\CommonMark\Extension;

use Drupal\markdown\Plugin\Markdown\CommonMark\Extension\FootnoteExtension as MarkdownFootnoteExtension;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Extension\Footnote\FootnoteExtension as CommonMarkFootnoteExtension;
use Drupal\markdown\Plugin\Markdown\SettingsInterface;
use Drupal\markdown\Traits\SettingsTrait;

/**
 * Omnipedia Markdown footnotes plug-in class.
 *
 * This extends the Markdown module's class to customize footnote settings.
 *
 * Note that the alter hook only applies this class to the
 * 'rezozero/commonmark-ext-footnotes' extension, meaning that this class will
 * be ignored once the Markdown module switches over to the
 * 'league/commonmark-ext-footnotes' extension shipped with CommonMark 1.5+.
 *
 * This does not have an annotation as we don't want Drupal's plug-in system to
 * pick it up, because we alter the existing extension in the alter hook,
 * replacing it with this class.
 *
 * @see \omnipedia_content_markdown_extension_info_alter()
 *   Alter hook where we replace the Markdown module plug-in with this class.
 */
class FootnoteExtension extends MarkdownFootnoteExtension implements SettingsInterface {

  use SettingsTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'backref_class'       => 'references__backreference-link',
      'container_add_hr'    => false,
      'container_class'     => 'references',
      'footnote_class'      => 'references__list-item',
      'footnote_id_prefix'  => 'reference-',
      'ref_class'           => 'reference',
      'ref_id_prefix'       => 'backreference-',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsKey() {
    return 'footnote';
  }

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    $environment->addExtension(new CommonMarkFootnoteExtension());
  }

}
