<?php

namespace Drupal\omnipedia_content_legacy\Plugin\Filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\omnipedia_content_legacy\PluginManager\OmnipediaElementLegacyManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to convert legacy Omnipedia Mustache tags to custom HTML.
 *
 * @Filter(
 *   id     = "omnipedia_mustache_to_html",
 *   title  = @Translation("Omnipedia: convert legacy Mustache tags"),
 *   type   = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class MustacheToHtmlFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The OmnipediaElementLegacy plug-in manager.
   *
   * @var \Drupal\omnipedia_content_legacy\PluginManager\OmnipediaElementLegacyManagerInterface
   */
  protected $elementLegacyManager;

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
   * @param \Drupal\omnipedia_content_legacy\PluginManager\OmnipediaElementLegacyManagerInterface $elementLegacyManager
   *   The OmnipediaElementLegacy plug-in manager.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    OmnipediaElementLegacyManagerInterface $elementLegacyManager
  ) {
    parent::__construct($configuration, $pluginID, $pluginDefinition);

    // Save dependencies.
    $this->elementLegacyManager = $elementLegacyManager;
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
      $container->get('plugin.manager.omnipedia_element_legacy')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo i18n?
   */
  public function process($text, $langCode) {
    return new FilterProcessResult(
      $this->elementLegacyManager->convertElements($text)
    );
  }

}
