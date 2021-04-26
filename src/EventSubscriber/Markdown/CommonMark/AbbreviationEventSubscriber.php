<?php

namespace Drupal\omnipedia_content\EventSubscriber\Markdown\CommonMark;

use Drupal\ambientimpact_markdown\AmbientImpactMarkdownEventInterface;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\CreateEnvironmentEvent;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentParsedEvent;
use Drupal\omnipedia_content\Service\AbbreviationInterface;
use Eightfold\CommonMarkAbbreviations\Abbreviation;
use Eightfold\CommonMarkAbbreviations\AbbreviationExtension;
use League\CommonMark\Inline\Element\Text;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to enable and output CommonMark abbreviations.
 */
class AbbreviationEventSubscriber implements EventSubscriberInterface {

  /**
   * The Omnipedia abbreviation service.
   *
   * @var \Drupal\omnipedia_content\Service\AbbreviationInterface
   */
  protected $abbreviation;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\omnipedia_content\Service\AbbreviationInterface $abbreviation
   *   The Omnipedia abbreviation service.
   */
  public function __construct(AbbreviationInterface $abbreviation) {
    $this->abbreviation = $abbreviation;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      AmbientImpactMarkdownEventInterface::COMMONMARK_CREATE_ENVIRONMENT =>
        'onCommonMarkCreateEnvironment',
      AmbientImpactMarkdownEventInterface::COMMONMARK_DOCUMENT_PARSED =>
        'onCommonMarkDocumentParsed',
    ];
  }

  /**
   * CreateEnvironmentEvent callback.
   *
   * Register the abbreviations extension.
   *
   * @param \Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\CreateEnvironmentEvent $event
   *   The event object.
   */
  public function onCommonMarkCreateEnvironment(
    CreateEnvironmentEvent $event
  ): void {

    /** @var \League\CommonMark\ConfigurableEnvironmentInterface */
    $environment = $event->getEnvironment();

    $environment->addExtension(new AbbreviationExtension());

  }

  /**
   * Alter a provided Text node if it contains one or more abbreviations.
   *
   * This finds CommonMark Text nodes containing available abbreviations, and
   * splits them into Text and Abbreviation elements as needed.
   *
   * @param \League\CommonMark\Inline\Element\Text $textNode
   *   The CommonMark Text node to scan for abbreviations.
   */
  protected function alterTextNode(Text $textNode): void {

    /** @var boolean */
    $isInAbbreviation = false;

    /** @var \League\CommonMark\Node\Node|null */
    $testNode = $textNode;

    // Loop through descendents, stopping when this returns null because we've
    // hit the root element.
    while ($testNode = $testNode->parent()) {
      if (!($testNode instanceof Abbreviation)) {
        continue;
      }

      $isInAbbreviation = true;

      break;
    }

    // Skip this text node if it's already inside of an Abbreviation element.
    if ($isInAbbreviation === true) {
      return;
    }

    /** @var string */
    $content = $textNode->getContent();

    // Get all matches and reverse their order so that we start from the end of
    // the string content, working towards the start.
    $matches = \array_reverse($this->abbreviation->match($content));

    if (empty($matches)) {
      return;
    }

    /** @var \League\CommonMark\Node\Node[] */
    $newNodes = [];

    foreach ($matches as $match) {

      // Attempt to save any text after the abbreviation. Note the use of
      // \mb_strcut() rather than \mb_substr(), as the offsets are in bytes and
      // not characters. If a string contains multi-byte characters, using
      // \mb_substr() would cause unexpected results.
      $trailing = \mb_strcut(
        $content,
        $match['offset'] + \mb_strlen($match['abbreviation'])
      );

      // Save any found text after the abbreviation as a Text node.
      if (\mb_strlen($trailing) > 0) {
        $newNodes[] = new Text($trailing);
      }

      // Now for the abbreviation.
      $newNodes[] = new Abbreviation(
        $match['abbreviation'], $match['description']
      );

      // Remove the abbreviation and trailing content from the original text.
      $content = \mb_substr($content, 0,
        \mb_strlen($content) - \mb_strlen($trailing) -
          \mb_strlen($match['abbreviation'])
      );

    }

    // If there's any text content remaining after the above, insert it as a
    // Text node.
    if (\mb_strlen($content) > 0) {
      $newNodes[] = new Text($content);
    }

    // Insert each new node directly after the original node. Since we're
    // working with the new nodes in reverse order, we can just keep inserting
    // directly after the original node and they'll end up in the correct
    // order.
    foreach ($newNodes as $newNode) {
      $textNode->insertAfter($newNode);
    }

    // Finally, remove the original node.
    $textNode->detach();

  }

  /**
   * DocumentParsedEvent callback.
   *
   * @param \Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentParsedEvent $event
   *   The event object.
   */
  public function onCommonMarkDocumentParsed(DocumentParsedEvent $event): void {

    /** @var \League\CommonMark\Block\Element\Document */
    $document = $event->getDocument();

    /** @var \League\CommonMark\Node\NodeWalker */
    $walker = $document->walker();

    while ($event = $walker->next()) {

      /** @var \League\CommonMark\Node\Node */
      $node = $event->getNode();

      if ($node instanceof Text && $event->isEntering()) {
        $this->alterTextNode($node);
      }

    }

  }

}
