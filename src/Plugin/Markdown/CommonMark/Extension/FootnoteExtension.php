<?php

namespace Drupal\omnipedia_content\Plugin\Markdown\CommonMark\Extension;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\markdown\Plugin\Markdown\CommonMark\Extension\FootnoteExtension as MarkdownFootnoteExtension;
use League\CommonMark\Block\Element\Document;
use League\CommonMark\Block\Element\Heading;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\Footnote\FootnoteExtension as CommonMarkFootnoteExtension;
use League\CommonMark\Extension\Footnote\Node\Footnote;
use League\CommonMark\Extension\Footnote\Node\FootnoteContainer;
use League\CommonMark\Extension\Footnote\Node\FootnoteRef;
use League\CommonMark\Inline\Element\Text;
use League\CommonMark\Reference\Reference;
use Drupal\markdown\Plugin\Markdown\SettingsInterface;
use Drupal\markdown\Traits\SettingsTrait;

/**
 * Omnipedia Markdown footnotes plug-in class.
 *
 * This extends the Markdown module's class to customize footnote settings and
 * to alter the output with the following:
 *
 * - Rewrites the inline reference link text to add square brackets around the
 *   numbers to match Wikipedia. I.e.: "1" becomes "[1]", etc.
 *
 * - Inserts a heading just before the footnotes container, named "References".
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
 *
 * @see \Drupal\omnipedia_content\Plugin\Filter\MarkdownAlterationsFilter
 *   Performs additional alterations that are too difficult or not possible in
 *   this extension.
 *
 * @todo When @link https://www.drupal.org/project/markdown/issues/3136378
 *   Markdown starts to support the built-in CommonMark footnotes @endLink,
 *   revise this extension as needed.
 *
 * @see https://git.drupalcode.org/project/markdown/-/commit/0749113699124bc2c1b4347b884445dc074aaf83
 *   Markdown module commit that adds support for the built-in CommonMark
 *   footnotes extension.
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
      'ref_class'           => 'reference__link',
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

    $environment->addEventListener(
      DocumentParsedEvent::class,
      [\get_class($this), 'onDocumentParsed'],
      -10
    );
  }

  /**
   * CommonMark document parsed event handler.
   *
   * Note that this is static because we don't need the service container and
   * the amount of Markdown processing we do can result in running out of memory
   * if we used an instantiated object as the event handler.
   *
   * @param \League\CommonMark\Event\DocumentParsedEvent $event
   *   The event object.
   */
  public static function onDocumentParsed(DocumentParsedEvent $event): void {
    /** @var \League\CommonMark\Block\Element\Document */
    $document = $event->getDocument();

    /** @var \League\CommonMark\Node\NodeWalker */
    $walker = $document->walker();

    while ($event = $walker->next()) {
      /** @var \League\CommonMark\Node\Node */
      $node = $event->getNode();

      if ($node instanceof FootnoteRef && $event->isEntering()) {
        static::alterFootnoteRef($document, $node);
      }

      if ($node instanceof FootnoteContainer && $event->isEntering()) {
        static::alterFootnoteContainer($document, $node);
      }
    }

    /** @var \League\CommonMark\Node\NodeWalker */
    $walker = $document->walker();

    // This needs to be a separate loop from the above to avoid messing up the
    // above callbacks.
    while ($event = $walker->next()) {

      /** @var \League\CommonMark\Node\Node */
      $node = $event->getNode();

      if ($node instanceof Footnote && $event->isEntering()) {
        static::removeUnusedFootnotes($node);
      }

    }

  }

  /**
   * Remove footnotes if they're not used in the document.
   *
   * When a footnote is defined but unused in the document, it seems to be left
   * as a list item outside of the footnotes container. This detaches the
   * provided Footnote element if none of its ancestors is a FootnoteContainer.
   *
   * @param \League\CommonMark\Extension\Footnote\Node\Footnote $footnote
   */
  protected static function removeUnusedFootnotes(Footnote $footnote): void {

    /** @var \League\CommonMark\Node\Node */
    $parent = $footnote;

    while ($parent = $parent->parent()) {
      if ($parent instanceof FootnoteContainer) {
        return;
      }
    }

    $footnote->detach();

  }

  /**
   * Alter CommonMark inline footnote reference text.
   *
   * @param \League\CommonMark\Block\Element\Document $document
   *   The CommonMark document object.
   *
   * @param \League\CommonMark\Extension\Footnote\Node\FootnoteRef $node
   *   The inline footnote reference.
   *
   * @see \League\CommonMark\Extension\Footnote\Event\NumberFootnotesListener::onDocumentParsed()
   *   Inspiration for how to alter the footnote reference text.
   */
  protected static function alterFootnoteRef(
    Document $document, FootnoteRef $node
  ): void {
    $existingReference = $node->getReference();

    /** @var \League\CommonMark\Reference\Reference */
    $newReference = new Reference(
      $existingReference->getLabel(),
      $existingReference->getDestination(),
      '[' . $existingReference->getTitle() . ']'
    );

    $node->setReference($newReference);
    $document->getReferenceMap()->addReference($newReference);
  }

  /**
   * Alter CommonMark footnotes container.
   *
   * This inserts a heading before the footnotes container.
   *
   * @param \League\CommonMark\Block\Element\Document $document
   *   The CommonMark document object.
   *
   * @param \League\CommonMark\Extension\Footnote\Node\FootnoteContainer $node
   *   The footnotes container.
   *
   * @see https://github.com/thephpleague/commonmark/issues/596
   *   Open issue on problem setting text on the newly created heading.
   */
  protected static function alterFootnoteContainer(
    Document $document, FootnoteContainer $node
  ): void {
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup */
    $headingContent = new TranslatableMarkup('References');

    /** @var \League\CommonMark\Block\Element\Heading */
    $heading = new Heading(3, $headingContent);

    // Append the text as a Text element because the above doesn't seem to
    // stick. See the GitHub issue linked in the docblock for this method.
    $heading->appendChild(
      /** @var \League\CommonMark\Inline\Element\Text */
      new Text($headingContent)
    );

    // Insert the heading right before the footnotes container.
    $node->insertBefore($heading);
  }

}
