<?php

namespace Drupal\exif;

/**
 *
 */
interface ExifInterface {

  /**
   * Work out which fields have to be processed.
   *
   * Going through all the fields that have been created for a given node type
   * and try to figure out which match the naming convention -> so that we know
   * which exif information we have to read
   *
   * Naming convention are: field_exif_xxx (xxx would be the name of the exif
   * tag to read.
   *
   * @param array $fields
   *   The fields.
   *
   * @return array
   *   A list of EXIF tags to read for this image.
   */
  public function getMetadataFields(array $fields = array());

  /**
   * List of options for the method.
   *
   * @param string $file
   * @param bool $enable_sections
   *   Retrieve sections.
   *
   * @return array $data
   */
  public function readMetadataTags($file, $enable_sections = TRUE);

  /**
   *
   *
   * @return array
   */
  public function getFieldKeys();

}
