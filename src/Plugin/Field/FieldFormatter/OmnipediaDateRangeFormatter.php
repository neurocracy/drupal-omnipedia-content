<?php

namespace Drupal\omnipedia_content\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\omnipedia_core\Service\TimelineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plug-in implementation of the 'omnipedia_daterange' formatter.
 *
 * @FieldFormatter(
 *   id           = "omnipedia_daterange",
 *   label        = @Translation("Omnipedia date range"),
 *   field_types  = {
 *     "daterange",
 *     "omnipedia_daterange"
 *   }
 * )
 *
 * @todo Can we move the 'first' and 'last' keyword stuff from the
 *   viewElements() method to the OmnipediaDateRangeItem class so that this
 *   formatter doesn't need to know about their existence?
 *
 * @todo Implement omnipedia-date-range-formatter.html.twig template.
 */
class OmnipediaDateRangeFormatter extends FormatterBase {

  /**
   * The Omnipedia timeline service.
   *
   * @var \Drupal\omnipedia_core\Service\TimelineInterface
   */
  protected $timeline;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\omnipedia_core\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   */
  public function __construct(
    $plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition,
    array $settings, $label, $view_mode, array $third_party_settings,
    TimelineInterface $timeline
  ) {
    parent::__construct(
      $plugin_id, $plugin_definition, $field_definition,
      $settings, $label, $view_mode, $third_party_settings
    );

    // Save dependencies.
    $this->timeline = $timeline;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $plugin_id, $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('omnipedia.timeline')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var array */
    $elements = [];

    foreach ($items as $delta => $item) {
      /** @var array */
      $elements[$delta] = [
        'start_date'  => [],
        'separator'   => ['#plain_text' => ' -> '],
        'end_date'    => [],
      ];

      foreach ([
        'start' => 'first',
        'end'   => 'last',
      ] as $valueKey => $nullString) {
        $value = $item->{$valueKey . '_date'};

        if ($value === null) {
          $value = $nullString;
        }

        $elements[$delta][$valueKey . '_date']['#plain_text'] =
          $this->timeline->getDateFormatted($value, 'short');
      }
    }

    return $elements;
  }

}
