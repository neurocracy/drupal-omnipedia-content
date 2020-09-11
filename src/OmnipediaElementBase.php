<?php

namespace Drupal\omnipedia_content;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\omnipedia_content\OmnipediaElementInterface;
use Drupal\omnipedia_content\OmnipediaElementManagerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for implementing OmnipediaElement plug-ins.
 */
abstract class OmnipediaElementBase extends PluginBase implements ContainerFactoryPluginInterface, OmnipediaElementInterface {

  use StringTranslationTrait;

  /**
   * The OmnipediaElement plug-in manager.
   *
   * @var \Drupal\omnipedia_content\OmnipediaElementManagerInterface
   */
  protected $elementManager;

  /**
   * The DOM elements this plug-in instance is to parse and render.
   *
   * @var \Symfony\Component\DomCrawler\Crawler
   */
  protected $elements;

  /**
   * Any errors logged by this plug-in.
   *
   * @var array
   */
  protected $errors = [];

  /**
   * Constructs an OmnipediaElementBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plug-in instance.
   *
   * @param string $pluginID
   *   The plugin_id for the plug-in instance.
   *
   * @param array $pluginDefinition
   *   The plug-in implementation definition. PluginBase defines this as mixed,
   *   but we should always have an array so the type is specified.
   *
   * @param \Drupal\omnipedia_content\OmnipediaElementManagerInterface $elementManager
   *   The OmnipediaElement plug-in manager.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    OmnipediaElementManagerInterface $elementManager,
    TranslationInterface $stringTranslation
  ) {
    parent::__construct($configuration, $pluginID, $pluginDefinition);

    // Save dependencies.
    $this->elementManager     = $elementManager;
    $this->stringTranslation  = $stringTranslation;

    // Pass on the Symfony DomCrawler instance provided by our plug-in manager.
    $this->elements = $configuration['elements'];
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
      $container->get('plugin.manager.omnipedia_element'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getHtmlElementName(): string {
    return $this->getPluginDefinition()['html_element'];
  }

  /**
   * Convert all child elements that have element plug-ins into standard HTML.
   *
   * This should be used whenever an element requires child elements to be
   * rendered. Using this method ensures that infinite recursion is avoided, as
   * the current plug-in ID will be ignored and thus two nested elements of the
   * same type will only have the first one rendered.
   *
   * @param string $html
   *   The HTML to parse.
   *
   * @return string
   *   The $html parameter with any custom elements that have OmnipediaElement
   *   plug-ins rendered as standard HTML.
   *
   * @see \Drupal\omnipedia_content\OmnipediaElementManagerInterface::convertElements()
   *   Wraps this method.
   */
  protected function convertElements(string $html): string {
    return $this->elementManager->convertElements(
      // Note that the plug-in manager will have already added the current
      // plug-in's ID to 'ignorePlugins', so we don't have to do anything here
      // other than pass it along.
      $html, $this->configuration['ignorePlugins']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function hasErrors(): bool {
    return \count($this->errors) > 0;
  }

  /**
   * Set an error for this element.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $error
   *   An error message as a TranslatableMarkup instance. Use $this->t() to
   *   generate this.
   */
  protected function setError(TranslatableMarkup $error): void {
    $this->errors[] = $error;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors(): array {
    return $this->errors;
  }

}
