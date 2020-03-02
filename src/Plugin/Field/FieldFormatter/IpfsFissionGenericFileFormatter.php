<?php

namespace Drupal\ipfs\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\DescriptionAwareFileFormatterBase;

/**
 * Plugin implementation for an IPFS implementation of the 'file_default' formatter.
 *
 * @FieldFormatter(
 *   id = "ipfs_fission_file_default",
 *   label = @Translation("IPFS Fission generic file"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class IpfsFissionGenericFileFormatter extends DescriptionAwareFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $fission_gateway_url = \Drupal::config('ipfs.settings')->get('fission_gateway') . '/ipfs/';

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;
      $fileUri = $file->getFileUri();
      $fileUri = str_replace('ipfs://', '', $fileUri);
      $file->setFileUri($fission_gateway_url . $fileUri);
      $elements[$delta] = [
        '#theme' => 'file_link',
        '#file' => $file,
        '#description' => $this->getSetting('use_description_as_link_text') ? $item->description : NULL,
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];
      // Pass field item attributes to the theme function.
      if (isset($item->_attributes)) {
        $elements[$delta] += ['#attributes' => []];
        $elements[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    return $elements;
  }

}
