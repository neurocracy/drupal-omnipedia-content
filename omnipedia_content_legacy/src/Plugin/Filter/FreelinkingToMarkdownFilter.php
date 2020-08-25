<?php

namespace Drupal\omnipedia_content_legacy\Plugin\Filter;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\freelinking\FreelinkingManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Provides a filter to convert legacy Freelinking links to Markdown.
 *
 * @Filter(
 *   id = "omnipedia_freelinking_to_markdown",
 *   title = @Translation("Omnipedia: convert legacy Freelinking links to Markdown"),
 *   description = @Translation("This converts legacy Freelinking links to Markdown. This should be placed <strong>after</strong> the Freelinking filter in the processing order."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 *
 * @see \Drupal\freelinking\FreelinkingManagerInterface::createFreelinkElement()
 */
class FreelinkingToMarkdownFilter extends FilterBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The Freelinking plug-in manager.
   *
   * @var \Drupal\freelinking\FreelinkingManagerInterface
   */
  protected $freelinkingManager;

  /**
   * The Drupal messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs this filter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plug-in instance.
   *
   * @param string $pluginID
   *   The plugin_id for the plug-in instance.
   *
   * @param array $pluginDefinition
   *   The plug-in implementation definition. PluginBase defines this as mixed,
   *   but we should always have an array so the type is set.
   *
   * @param \Drupal\freelinking\FreelinkingManagerInterface $freelinkingManager
   *   The Freelinking plug-in manager.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Drupal messenger service.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    FreelinkingManagerInterface $freelinkingManager,
    MessengerInterface          $messenger,
    TranslationInterface        $stringTranslation
  ) {
    parent::__construct($configuration, $pluginID, $pluginDefinition);

    // Save dependencies.
    $this->freelinkingManager = $freelinkingManager;
    $this->messenger          = $messenger;
    $this->stringTranslation  = $stringTranslation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginID, $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginID, $pluginDefinition,
      $container->get('freelinking.manager'),
      $container->get('messenger'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langCode) {
    /** @var \Symfony\Component\DomCrawler\Crawler */
    $rootCrawler = new Crawler($text);

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $freelinksCrawler = $rootCrawler->filter(
      'drupal-filter-placeholder[callback="freelinking.manager:createFreelinkElement"]'
    );

    foreach ($freelinksCrawler as $element) {
      /** @var array */
      $parsedArguments = UrlHelper::parse(
        '?' . $element->getAttribute('arguments')
      );

      if (empty($parsedArguments['query'])) {
        $this->messenger->addError($this->t(
          'Could not parse the following Freelink array: <pre>@array</pre>',
          ['@array' => $element->getAttribute('arguments')]
        ));

        continue;
      }

      /** @var array */
      $renderArray = \call_user_func_array([
        $this->freelinkingManager, 'createFreelinkElement'
      ], $parsedArguments['query']);

      // We need to find the new node's parent to use the replaceChild()
      // method, awkward though it may be.
      /** @var \DOMNode|null */
      $elementParent = $element->parentNode;

      if ($elementParent === null) {
        $this->messenger->addError($this->t(
          'Could not find a valid parent node for the following Freelink array: <pre>@array</pre>',
          ['@array' => $element->getAttribute('arguments')]
        ));

        continue;
      }

      // Replace the old node (the Freelinking filter placeholder) with a
      // Markdown link.
      $elementParent->replaceChild(
        // New node.
        $element->ownerDocument->createTextNode(
          '[' . \trim($renderArray['#link']['#title']) . ']' .
          '(' . $renderArray['#link']['#url']->toString() . ')'
        ),
        // Old node.
        $element
      );
    }

    return new FilterProcessResult($rootCrawler->filter('body')->html());
  }

}
