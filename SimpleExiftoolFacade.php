<?php

namespace Drupal\exif;

include_once drupal_get_path('module', 'exif') . '/ExifInterface.php';

/**
 * Helper class to handle the whole data processing of EXIF with exiftool.
 */
class SimpleExifToolFacade implements ExifInterface {

  static private $instance = NULL;

  /**
   *
   *
   * @return object
   */
  public static function getInstance() {
    if (is_null(self::$instance)) {
      self::$instance = new self;
    }
    return self::$instance;
  }

  public function getFieldKeys() {
    return array();
  }

  /**
   * Going through all the fields that have been created for a given node type
   * and try to figure out which match the naming convention -> so that we know
   * which exif information we have to read
   *
   * Naming convention are: field_exif_xxx (xxx would be the name of the exif
   * tag to read
   *
   * @param array $fields
   *   The fields to process.
   *
   * @return array
   *   A list of exif tags to read for this image.
   */
  public function getMetadataFields(array $fields = array()) {
    foreach ($fields as $drupal_field => $metadata_settings) {
      $metadata_field = $metadata_settings['metadata_field'];
      $ar = explode('_', $metadata_field);
      if (isset($ar[0])) {
        $section = $ar[0];
        unset($ar[0]);
        $fields[$drupal_field]['metadata_field'] = array(
          'section' => $section,
          'tag' => implode('_', $ar),
        );
      }
    }
    return $fields;
  }

  public static function checkConfiguration() {
    $exiftoolLocation = self::getExecutable();
    return isset($exiftoolLocation) && is_executable($exiftoolLocation);
  }

  public static function getExecutable() {
    return variable_get('exif_exiftool_location');
  }

  public function runTool($file, $enable_sections = TRUE, $enable_markerNote = FALSE, $enable_non_supported_tags = FALSE) {
    $params = ' -E -n -json ';
    if ($enable_sections) {
      $params .= '-g -struct ';
    }
    if ($enable_markerNote) {
      $params .= '-fast ';
    }
    else {
      $params .= '-fast2 ';
    }
    if ($enable_non_supported_tags) {
      $params .= '-u -U ';
    }

    // Escape all of the arguments passed to the function.
    // Note: If params is expanded so it is customizable, make sure that each
    // piece is passed through escapeshellarg().
    $commandline = escapeshellcmd('exiftool' . $params . escapeshellarg($file));
    $output = array();
    $returnCode = 0;
    exec($commandline, $output, $returnCode);

    if ($returnCode != 0) {
      $output = "";
      watchdog('exif', 'Exiftool returns an error. Can not extract metadata from file !file', array(
        '!file' => $file,
      ), WATCHDOG_WARNING);
    }
    return implode("\n", $output);
  }

  public function tolowerJsonResult(array $data) {
    $result = array();
    foreach ($data as $section => $values) {
      if (is_array($values)) {
        $result[strtolower($section)] = array_change_key_case($values);
      }
      else {
        $result[strtolower($section)] = $values;
      }
    }
    return $result;
  }

  public function readAllInformation($file, $enable_sections = TRUE, $enable_markerNote = FALSE, $enable_non_supported_tags = FALSE) {
    $jsonAsString = $this->runTool($file, $enable_sections, $enable_markerNote, $enable_non_supported_tags);
    $json = json_decode($jsonAsString, TRUE);
    $errorCode = json_last_error();
    if ($errorCode == JSON_ERROR_NONE) {
      return $this->tolowerJsonResult($json[0]);
    }
    else {
      $errorMessage = '';
      switch ($errorCode) {
        case JSON_ERROR_DEPTH:
          $errorMessage = 'Maximum stack depth exceeded';
          break;

        case JSON_ERROR_STATE_MISMATCH:
          $errorMessage = 'Underflow or the modes mismatch';
          break;

        case JSON_ERROR_CTRL_CHAR:
          $errorMessage = 'Unexpected control character found';
          break;

        case JSON_ERROR_SYNTAX:
          $errorMessage = 'Syntax error, malformed JSON';
          break;

        case JSON_ERROR_UTF8:
          $errorMessage = 'Malformed UTF-8 characters, possibly incorrectly encoded';
          break;

        default:
          $errorMessage = 'Unknown error';
          break;
      }
      // Logs a notice.
      watchdog('exif', 'Error reading information from exiftool for file !file: !message', array(
        '!file' => $file,
        '!message' => $errorMessage,
      ), WATCHDOG_NOTICE);
      return array();
    }
  }

  /**
   * $arOptions liste of options for the method :
   * # enable_sections : (default : TRUE) retrieve also sections.
   *
   * @param string $file
   * @param bool $enable_sections
   *
   * @return array $data
   */
  public function readMetadataTags($file, $enable_sections = TRUE) {
    if (!file_exists($file)) {
      return array();
    }
    $data = $this->readAllInformation($file, $enable_sections);
    return $data;
  }

  /**
   *
   *
   * @param array $metadata
   * @param array $tag_names
   *
   * @return array
   */
  public function filterMetadataTags(array $metadata, array $tag_names) {
    $info = array();
    foreach ($tag_names as $drupal_field => $metadata_settings) {
      $tagName = $metadata_settings['metadata_field'];
      if (!empty($metadata[$tagName['section']][$tagName['tag']])) {
        $info[$tagName['section']][$tagName['tag']] = $metadata[$tagName['section']][$tagName['tag']];
      }
    }
    return $info;
  }

}
