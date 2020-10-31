<?php

namespace Drupal\omnipedia_content\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

/**
 * Plugin implementation of the 'omnipedia_daterange' field type.
 *
 * This extends the Drupal core 'daterange' field type to make a single change:
 * the 'end_value' property is marked as not required, so that it's possible to
 * set a start date but no end date, which is not possible with the core
 * 'daterange' field type.
 *
 * @FieldType(
 *   id = "omnipedia_daterange",
 *   label = @Translation("Date range (Omnipedia)"),
 *   description = @Translation("Create and store date ranges."),
 *   default_widget = "daterange_default",
 *   default_formatter = "daterange_default",
 *   list_class = "\Drupal\datetime_range\Plugin\Field\FieldType\DateRangeFieldItemList"
 * )
 */
class OmnipediaDateRangeItem extends DateRangeItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(
    FieldStorageDefinitionInterface $fieldDefinition
  ) {
    /** @var array */
    $properties = parent::propertyDefinitions($fieldDefinition);

    $properties['end_value']->setRequired(false);

    return $properties;
  }

}
