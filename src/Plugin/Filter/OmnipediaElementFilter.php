<?php

namespace Drupal\omnipedia_content\Plugin\Filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\omnipedia_content\OmnipediaElementManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to convert Omnipedia elements into standard HTML.
 *
 * @Filter(
 *   id     = "omnipedia_elements",
 *   title  = @Translation("Omnipedia: convert Omnipedia elements to HTML"),
 *   type   = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class OmnipediaElementFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The OmnipediaElement plug-in manager.
   *
   * @var \Drupal\omnipedia_content\OmnipediaElementManagerInterface
   */
  protected $elementManager;

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
   * @param \Drupal\omnipedia_content\OmnipediaElementManagerInterface $elementManager
   *   The OmnipediaElement plug-in manager.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    OmnipediaElementManagerInterface $elementManager
  ) {
    parent::__construct($configuration, $pluginID, $pluginDefinition);

    // Save dependencies.
    $this->elementManager = $elementManager;
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
      $container->get('plugin.manager.omnipedia_element')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langCode) {
    return new FilterProcessResult(
      $this->elementManager->convertElements($text)
    );
  }

}
