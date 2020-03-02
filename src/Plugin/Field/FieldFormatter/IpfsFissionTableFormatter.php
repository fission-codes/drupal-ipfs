<?php

namespace Drupal\ipfs\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\DescriptionAwareFileFormatterBase;

/**
 * Plugin implementation of the 'file_table' formatter.
 *
 * @FieldFormatter(
 *   id = "ipfs_fission_file_table",
 *   label = @Translation("IPFS Fission table of files"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class IpfsFissionTableFormatter extends DescriptionAwareFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if ($files = $this->getEntitiesToView($items, $langcode)) {
      $header = [t('Attachment'), t('Size')];
      $rows = [];
      $fission_gateway_url = \Drupal::config('ipfs.settings')->get('fission_gateway') . '/ipfs/';
      foreach ($files as $delta => $file) {
        $item = $file->_referringItem;
        $fileUri = $file->getFileUri();
        $fileUri = str_replace('ipfs://', '', $fileUri);
        $file->setFileUri($fission_gateway_url . $fileUri);
        $rows[] = [
          [
            'data' => [
              '#theme' => 'file_link',
              '#file' => $file,
              '#description' => $this->getSetting('use_description_as_link_text') ? $item->description : NULL,
              '#cache' => [
                'tags' => $file->getCacheTags(),
              ],
            ],
          ],
          ['data' => format_size($file->getSize())],
        ];
      }

      $elements[0] = [];
      if (!empty($rows)) {
        $elements[0] = [
          '#theme' => 'table__file_formatter_table',
          '#header' => $header,
          '#rows' => $rows,
        ];
      }
    }

    return $elements;
  }

}
