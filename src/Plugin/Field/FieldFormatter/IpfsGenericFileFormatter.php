<?php

namespace Drupal\ipfs\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\DescriptionAwareFileFormatterBase;

/**
 * Plugin implementation for an IPFS implementation of the 'file_default' formatter.
 *
 * @FieldFormatter(
 *   id = "ipfs_file_default",
 *   label = @Translation("IPFS generic file"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class IpfsGenericFileFormatter extends DescriptionAwareFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $gateway = NULL;

    $settings = \Drupal::config('ipfs.settings');
    if ('ipfs' == $settings->get('ipfs_gateway_type')) {
      $gateway = $settings->get('ipfs_gateway');
    } else if ('fission' == $settings->get('ipfs_gateway_type')) {
      $gateway = $settings->get('fission_gateway');
    }
    $gateway .= '/ipfs/';

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;
      $fileUri = $file->getFileUri();
      $fileUri = str_replace('ipfs://', '', $fileUri);
      $file->setFileUri($gateway . $fileUri);
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
