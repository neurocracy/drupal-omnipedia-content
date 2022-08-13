<?php

declare(strict_types=1);

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

/**
 * Omnipedia Markdown footnotes plug-in class.
 *
 * This extends the Markdown module's class to customize footnote settings and
 * to alter the output with the following:
 *
 * - Rewrites the inline reference link text to add square brackets around the
 *   numbers to match Wikipedia. I.e.: "1" becomes "[1]", etc. Also replaces any
 *   trailing space in the text preceding the inline reference with a single
 *   non-breaking space so that the reference link can't occasionally end up by
 *   itself on a new line.
 *
 * - Inserts a heading just before the footnotes container, named "References".
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
 */
class FootnoteExtension extends MarkdownFootnoteExtension {

  /**
   * {@inheritdoc}
   *
   * This sets our expected classes, ID prefix, and disables the <hr> by
   * default. These should not be altered without altering the CSS that uses
   * them as well.
   */
  public static function defaultSettings($pluginDefinition) {
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
  public function register($environment) {

    parent::register($environment);

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
   * Alter CommonMark inline footnote reference text and preceding space.
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

    /** @var \League\CommonMark\Node\Node|null */
    $previousNode = $node->previous();

    if (\is_object($previousNode) && $previousNode instanceof Text) {

      /** @var string */
      $precedingText = $previousNode->getContent();

      // Attempt to match one or more trailing spaces in the preceding text node
      // to be replaced by a non-breaking space.
      if (
        \preg_match(
          '/\s+$/', $precedingText, $matches, \PREG_OFFSET_CAPTURE
        ) === 1 &&
        isset($matches[0][1])
      ) {

        // Note that the ' ' is a Unicode non-breaking space. '&nbsp;' seems to
        // get escaped here, likely due to this being a text node and thus
        // doesn't allow HTML input. A more elegant solution to this in the
        // future may be to implement a new inline node type to represent one or
        // more non-breaking spaces that renders to '&nbsp;' HTML.
        $previousNode->setContent(
          \mb_substr($precedingText, 0, $matches[0][1]) . ' '
        );

      }

    }

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
      new Text((string) $headingContent)
    );

    // Insert the heading right before the footnotes container.
    $node->insertBefore($heading);
  }

}
