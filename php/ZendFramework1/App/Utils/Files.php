<?php

/**
 * Class of File related methods
 *
 *   based on searches to find examples and then modifies for specific use
 *      sources are many and varied and not captured...
 *
 * @author Steven Hill Sr ( steven.hill.sr at gmail com )
 */

class App_Utils_Files {
  /**
   * Returns contents of a file decoded from UTF-8 
   *
   * @return string
   */
  public static function getContentsUtf8($fn)
  {
    $content = file_get_contents($fn);
    $encoding = mb_detect_encoding($content);
//    error_log("Encoding: " . print_r($encoding, TRUE));
    return mb_convert_encoding($content, 'UTF-8', $encoding);
  }

  public static function getContentsIsoLatin1($fn)
  {
    $content = file_get_contents($fn);
    return mb_convert_encoding($content, 'ISO-Latin-1', mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true));
  }

  /**
   * Returns file extension 
   *
   * @param string filename  : the filename ( may include path )
   *
   * @return string
   */
  public static function file_ext($file) {
    $extension = split("[.]", $file);
    $ext_file = $extension[count($extension)-1];
    return strtolower($ext_file);
  }

  /**
   * Process Image type file upload
   *
   * This is a method that stores an uploaded image in the proper place and returns a string variable
   * of the path to that image file relative to the DOCUMENT_ROOT and accounts for the client based on
   * the config value of client_name which is appended to '/sites/' to create the relative path that 
   * is returned. May return a generic image stored in the '/images' folder for non client specific
   * default images.
   * 
   * @param string imageFolder      : the location/path that is the base for the imagePath variable
   * @param string uploadedFileName : the name of the uploaded file
   * @param string imagePath        : the relative path to store image
   *                                    relative to imageFolder variable above
   * @param string holdImagePath    : the value of imagePath from the record being updated
   *                                    for allowing no change
   * @param string genericImagePath : the path to a generic image
   *                                    used when no image is uploaded and none already exists
   * 
   * @return string  : the relative path to the stored image
   */
  public static function processImage($imageFolder, $uploadedFileName, $imagePath, $holdImagePath = NULL, $genericImagePath = NULL) {
 //   error_log('User - _processImage - user id :' . $user_id . ': uploadedFilename :' . $uploadedFileName . ': imagePath :' . $imagePath . ': holdImagePath :' . $holdImagePath . ':' );
    if ($imagePath) {
      $path = '/sites/' . Zend_Registry::get('config')->client_name . '/images/'. $imageFolder;
      if (!is_dir('.' . $path)) {
        mkdir('.' . $path,0775,TRUE);
      }
      $fileName = $path . '/image' . substr($uploadedFileName, strripos($uploadedFileName, '.'));

      if (rename($uploadedFileName, APPLICATION_PATH . '/../public' . $fileName) ) {
        $retImagePath = $fileName;
      } else {
        $retImagePath = 'move_uploaded_file FAILED';
      }
    } else {
      if (empty($holdImagePath)) {
        if ( empty( $genericImagePath ) ) {
          $retImagePath = '/images/generic-image.jpg';
        } else {
          $retImagePath = $genericImagePath;
        }
      } else {
        $retImagePath = $holdImagePath;
      }
    }
    return $retImagePath;
  }

  /**
   * getFiles
   *
   * This is a method that returns a list of files from a directory
   * 
   * 
   * @param string path       : the path to start looking for files in relative to DOCROOT
   * @param string recurse    : recurse through subdirectories
   * @param string skipDirs   : the directories/folders to skip
   * @param string extensions : the file extensions to look for ( defaults to 'jpg', 'gif' and 'png' )
   * 
   * @return array            : the relative paths to the files found
   */
  public static function getFiles( $path = '', $recurse = FALSE, $skipDirs = array(), $extensions = array('jpg','gif','png') ) {
    $excludeDirs = array_merge(array('.','..'),$skipDirs);

    $fileList = array();
    $checkPath = realpath($path);
    if ( is_dir( $checkPath ) ) {
      if ( $dh = opendir( $checkPath ) ) {
          while ( false !== ( $file = readdir( $dh ) ) ) {
            if ( !in_array( $file, $excludeDirs ) ) {
              if ( is_dir( "$checkPath/$file" ) ) {
                if ($recurse) {
                  $folderFiles = self::getFiles("$path$file/", $recurse, $skipDirs, $extensions);
                  $fileList = array_merge($fileList,$folderFiles);
                }
              } else {
                $ext = self::file_ext($file);
                if(isset($ext) && in_array($ext, $extensions)) {
                  $file_size = filesize($checkPath . '/' . $file);
                  $fileList[] = array('name' => "/$path$file",
                                    'last_modified' => filemtime($checkPath . '/' . $file),
                                    'size' => $file_size,
                                     );
                }
              }
            }
          }
          closedir( $dh );
      }
    }
    return $fileList;    
  }

  /**
   * getImageFilesWithThumbnails
   *
   * This is a method that returns a list of files from a directory
   * 
   * 
   * @param string path       : the path to start looking for files in relative to DOCROOT
   * @param string recurse    : recurse through subdirectories
   * @param string skipDirs   : the directories/folders to skip
   * @param string extensions : the file extensions to look for ( defaults to 'jpg', 'gif' and 'png' )
   * 
   * @return array : the relative paths to the files found, path to thumbnail, file size and modified date
   */
  public static function getImageFilesWithThumbnails( $path = '', $recurse = FALSE, $skipDirs = array() ) {
    $imgExts = array('jpeg','jpg','bmp','gif','png');
    $skipDirs = array_merge(array('_th'),$skipDirs);
    $fileList = self::getFiles($path, $recurse, $skipDirs);
    foreach ( $fileList as &$fileInfo) {
      set_time_limit(30);
      if ($fileInfo['size'] > 200000) {
        $path_parts = pathinfo($fileInfo['name']);
        $thumbPath = $path_parts['dirname'] . '/_th';
        $thumbDir = realpath('.' . $thumbPath);
        if (!is_dir($thumbDir)) {
          mkdir($thumbDir,0775,TRUE);
        }
        $thumbnail = $thumbPath . '/' . $path_parts['basename'];
        if (!file_exists('.' . $thumbnail)) {
          $pic_error = self::image_resize('.' . $fileInfo['name'], '.' . $thumbnail, 100, 100, 1);
        }
      } else {
        $thumbnail = $fileInfo['name'];
      }
      $fileInfo['thumbnail'] = $thumbnail;
    }
    return $fileList;
  }
                  
  public static function image_resize($src, $dst, $width, $height, $crop=0){

    if(!list($w, $h) = getimagesize($src)) return "Unsupported picture type!";

    $type = strtolower(substr(strrchr($src,"."),1));
    if($type == 'jpeg') $type = 'jpg';
    switch($type){
      case 'bmp': $img = imagecreatefromwbmp($src); break;
      case 'gif': $img = imagecreatefromgif($src); break;
      case 'jpg': $img = imagecreatefromjpeg($src); break;
      case 'png': $img = imagecreatefrompng($src); break;
      default : return "Unsupported picture type!";
    }

    // resize
    if($crop){
      if($w < $width or $h < $height) return "Picture is too small!";
      $ratio = max($width/$w, $height/$h);
      $h = $height / $ratio;
      $x = ($w - $width / $ratio) / 2;
      $w = $width / $ratio;
    }
    else{
      if($w < $width and $h < $height) return "Picture is too small!";
      $ratio = min($width/$w, $height/$h);
      $width = $w * $ratio;
      $height = $h * $ratio;
      $x = 0;
    }

    $new = imagecreatetruecolor($width, $height);

    // preserve transparency
    if($type == "gif" or $type == "png"){
      imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
      imagealphablending($new, false);
      imagesavealpha($new, true);
    }

    imagecopyresampled($new, $img, 0, 0, $x, 0, $width, $height, $w, $h);

    switch($type){
      case 'bmp': imagewbmp($new, $dst); break;
      case 'gif': imagegif($new, $dst); break;
      case 'jpg': imagejpeg($new, $dst); break;
      case 'png': imagepng($new, $dst); break;
    }
    return true;
  }

}