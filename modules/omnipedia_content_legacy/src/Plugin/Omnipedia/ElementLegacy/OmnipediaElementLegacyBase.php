<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content_legacy\Plugin\Omnipedia\ElementLegacy;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\omnipedia_content_legacy\PluginManager\OmnipediaElementLegacyManagerInterface;
use Drupal\omnipedia_content_legacy\Plugin\Omnipedia\ElementLegacy\OmnipediaElementLegacyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for implementing OmnipediaElementLegacy plug-ins.
 */
abstract class OmnipediaElementLegacyBase extends PluginBase implements ContainerFactoryPluginInterface, OmnipediaElementLegacyInterface {

  use StringTranslationTrait;

  /**
   * The OmnipediaElementLegacy plug-in manager.
   *
   * @var \Drupal\omnipedia_content_legacy\PluginManager\OmnipediaElementLegacyManagerInterface
   */
  protected $legacyElementManager;

  /**
   * Element options parsed from the content provided by our plug-in manager.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Constructs an OmnipediaElementLegacyBase object.
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
   * @param \Drupal\omnipedia_content_legacy\PluginManager\OmnipediaElementLegacyManagerInterface $legacyElementManager
   *   The OmnipediaElement plug-in manager.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    OmnipediaElementLegacyManagerInterface $legacyElementManager,
    TranslationInterface $stringTranslation
  ) {
    parent::__construct($configuration, $pluginID, $pluginDefinition);

    // Save dependencies.
    $this->legacyElementManager = $legacyElementManager;
    $this->stringTranslation    = $stringTranslation;

    // Determine what to do with the content passed to us.
    if (isset($configuration['content'])) {
      // If content is a string, attempt to parse it.
      if (\is_string($configuration['content'])) {
        $this->options = $this->parseOptions($configuration['content']);

      // If it's an array, use it as-is.
      } else if (\is_array($configuration['content'])) {
        $this->options = $configuration['content'];
      }
    }
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
      $container->get('plugin.manager.omnipedia_element_legacy'),
      $container->get('string_translation')
    );
  }

  /**
   * Parse legacy Omnipedia tag options.
   *
   * This is a very simplified and pared down port of the code from Drupal 7,
   * with anything not needed for backward compatibility removed.
   *
   * @param string $text
   *   Text containing key/value pairs, with an equals sign ('=') between a key
   *   and its value, with multiple pairs delimited by a pipe character ('|').
   *
   * @return array
   *   Keys as parsed key names, and values their corresponding parsed values.
   */
  protected function parseOptions(string $text): array {
    /** @var array */
    $options = [];

    /** @var string */
    $delimiter = '|';

    /** @var string */
    $equals = '=';

    // Attempt to split by the delimiter.
    /** @var array */
    $textArray = \explode($delimiter, $text);

    foreach ($textArray as $option) {
      // This stores the built optionName (index 0) and optionValue (index 1)
      // strings if the current option is a named option, or the optionValue
      // (index 0) if this is an unnamed option. In the latter case, the second
      // element in the array will have been removed.
      /** @var array */
      $currentOption = ['', ''];
      \reset($currentOption);

      // Split text nodes by the first equals sign found. Since we limit this to
      // two parts, this will leave any HTML with attributes intact (which use
      // the equals character), so there's no need to do anything complex here.
      /** @var array */
      $textExploded = \explode($equals, $option, 2);

      // If the exploded text has been split into two indices and we're
      // still on the first index for $currentOption, we've found the equals
      // sign, so we move from the option name to the value.
      if (\count($textExploded) === 2) {
        if (\key($currentOption) === 0) {
          $currentOption[0] .= $textExploded[0];
          \next($currentOption);
          $currentOption[1] .= $textExploded[1];
        }

      } else {
        $currentOption[\key($currentOption)] .= $textExploded[0];
      }

      // If the second index is empty, remove it to indicate this is an unnamed
      // option.
      if (empty($currentOption[1])) {
        unset($currentOption[1]);
      }

      // If there's only one index, this is an unnamed option.
      if (\count($currentOption) === 1) {
        $options[] = \trim($currentOption[0]);

      // Otherwise there should be a name and value pair, so this is a named
      // option.
      } else {
        $options[\trim($currentOption[0])] = \trim($currentOption[1]);
      }
    }

    return $options;
  }

}
