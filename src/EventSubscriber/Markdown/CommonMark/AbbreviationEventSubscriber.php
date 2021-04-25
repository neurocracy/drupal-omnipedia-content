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
   * DocumentParsedEvent callback.
   *
   * This finds CommonMark Text nodes containing available abbreviations, and
   * splits them into Text and Abbreviation elements as needed.
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
      $originalNode = $event->getNode();

      if (!($originalNode instanceof Text) || !$event->isEntering()) {
        continue;
      }


      /** @var boolean */
      $isInAbbreviation = false;

      /** @var \League\CommonMark\Node\Node|null */
      $testNode = $originalNode;

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
        continue;
      }

      /** @var string */
      $originalContent = $originalNode->getContent();

      // Get all matches and reverse their order so that we start from the end
      // of the string content, working towards the start.
      $matches = \array_reverse($this->abbreviation->match($originalContent));

      if (empty($matches)) {
        continue;
      }

      /** @var \League\CommonMark\Node\Node[] */
      $newNodes = [];

      foreach ($matches as $match) {

        // Attempt to save any text after the abbreviation.
        $trailing = \mb_substr(
          $originalContent,
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
        $originalContent = \mb_substr(
          $originalContent, 0,
          \mb_strlen($originalContent) - \mb_strlen($trailing) -
            \mb_strlen($match['abbreviation'])
        );

      }

      // If there's any text content remaining after the above, insert it as a
      // Text node.
      if (\mb_strlen($originalContent) > 0) {
        $newNodes[] = new Text($originalContent);
      }

      // Insert each new node directly after the original node. Since we're
      // working with the new nodes in reverse order, we can just keep inserting
      // directly after the original node and they'll end up in the correct
      // order.
      foreach ($newNodes as $newNode) {
        $originalNode->insertAfter($newNode);
      }

      // Finally, remove the original node.
      $originalNode->detach();

    }

  }

}
