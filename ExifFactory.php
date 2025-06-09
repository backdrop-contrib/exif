<?php

/**
 *
 */
class ExifFactory {

  /**
   *
   */
  public static function getExtractionSolutions() {
    return array(
      "simple_exiftool" => "exiftool",
      "php_extensions"  => "php extensions",
    );
  }

  /**
   *
   */
  public static function getExifInterface() {
    $extractionSolution = variable_get('exif_extraction_solution');
    $useExifToolSimple  = $extractionSolution == "simple_exiftool";
    if (isset($useExifToolSimple) && $useExifToolSimple && SimpleExifToolFacade::checkConfiguration()) {
      return SimpleExifToolFacade::getInstance();
    }
    else {
      // Default case for now (same behavior as previous versions).
      return ExifPHPExtension::getInstance();
    }
  }

}
