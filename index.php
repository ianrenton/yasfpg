<html>
<head>
  <title>Ian's Photos (and other memories)</title>
  <style>
    html, body { margin: 0; padding: 0.5em; background-color: #eeeeee; }
    h1 { text-align: center; font-family: Georgia, Times, serif; color: black; font-size: 5em; font-weight: 300; }
    h2 { text-align: center; font-family: Georgia, Times, serif; color: black; font-size: 3em; font-weight: 300; }
    h3 { text-align: left; font-family: "Liberation Sans", "Lucida Sans", sans-serif; color: gray; font-size: 2em; margin: 1.5em 0.5em; border-bottom: 2px solid gray; }
    p#backlink { float:left; }
    span.photoblock { display: inline-block; margin: 0.5em; padding: 10px; vertical-align: top; width: 200px; height: 240px; background-color: #cccccc; border: 1px solid gray; }
    span.photoblock span.imgwrapper { height: 200px; width: 200px; margin-bottom: 5px; }
    span.photoblock img { margin-bottom: 5px; max-height: 200px;}
    span.photoblock a { color: black; text-decoration:none;}
  </style>
</head>
<body>

  <?php
    // Check if we have an album param - if not, serve the home page - if so, serve an album page
    if (!isset($_GET["album"]))
    {
    
      print("<h1>Ian's Photos</h1>");
      // Get directories sorted by modified date
      $dirlist = allAlbums();
      // Print the list, blocking up by year
      $year = 9999;
      foreach ($dirlist as $i => $dir) {
        // Don't display the cache dir
        if ($dir != "cache") {
          $newYear = intval(date("Y", filemtime($dir)));
          if ($newYear < $year) {
            $year = $newYear;
            if ($year > 2001) {
              print("</p><h3>" . $year . "</h3><p>");
            } else {
              // Hack to have a "misc" category at the end
              $year = 0;
              print("</p><h3>No Date</h3><p>");
            }
          }
          // For each album, display the first photo in it, along with the name as a link
          $firstImageInDir = allPhotos($dir)[0];
          $thumbnailFile = getThumbnail($firstImageInDir);
          print("<span class=\"photoblock\"><span class=\"imgwrapper\"><a href=\"./?album=" . base64_encode($dir) . "\"><img src=\"" . $thumbnailFile . "\" /><br/>" . $dir . "</a></span></span>");
        }
      }
      echo("</p>");
      
    } else {
      // We have an "album" param, so display the contents of an album
      $albumName = base64_decode($_GET["album"]);
      
      print("<p id=\"backlink\"><a href=\"./\">&laquo; Back</a></p>");
      print("<h2>" . $albumName . "</h2>");
      print("<p>");
      
      // Display a photo block for every file in the directory
      $filelist = allPhotos($albumName);
      foreach ($filelist as $i => $file) {
        $thumbnailFile = getThumbnail($file);
        print("<span class=\"photoblock\"><span class=\"imgwrapper\"><a href=\"" . $file . "\"><img src=\"" . $thumbnailFile . "\" /><br/>" . basename($file, ".jpg") . "</a></span></span>");
      }
      print("</p>");
    
    }
  ?>
</body>
</html>

<?php

// Return all album directories
function allAlbums() {
  $dirlist = glob("*", GLOB_ONLYDIR);
  usort($dirlist, create_function('$a,$b', 'return filemtime($b) - filemtime($a);'));
  return $dirlist;
}

// Return all photos in an album
function allPhotos($albumName) {
  $filelist = glob($albumName . "/*");
  usort($filelist, create_function('$a,$b', 'return filemtime($a) - filemtime($b);'));
  return $filelist;
}

// Get thumbnail for image, making one if one doesn't exist
function getThumbnail($file) {
  if (!file_exists(getThumbnailFilename($file)))
  {
    // parse path for the extension
    $info = pathinfo($file);
    // continue only if this is a JPEG image
    if (( strtolower($info['extension']) == 'jpg' ) || ( strtolower($info['extension']) == 'png' ))
    {
      // load image and get image size
      if ( strtolower($info['extension']) == 'jpg' ) {
        $img = imagecreatefromjpeg( $file );
      } elseif ( strtolower($info['extension']) == 'png' ) {
        $img = imagecreatefrompng( $file );
      }
      
      // rotate if EXIF data says so
      $exif = exif_read_data($file);
      if (!empty($exif['Orientation'])) {
        switch ($exif['Orientation']) {
        case 3:
          $angle = 180 ;
          break;
        case 6:
          $angle = -90;
          break;
        case 8:
          $angle = 90; 
          break;
        default:
          $angle = 0;
          break;
        }   
      }
      $img = imagerotate($img, $angle, 0);

      // Calculate new size 
      $width = imagesx( $img );
      $height = imagesy( $img );
      $new_width = 200;
      $new_height = floor( $height * ( 200 / $width ) );

      // Create new image
      $tmpimg = imagecreatetruecolor( $new_width, $new_height );
      imagecopyresized( $tmpimg, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

      // Save thumbnail into file
      imagejpeg( $tmpimg, getThumbnailFilename($file) );
      return getThumbnailFilename($file);
    } else {
      return $file;
    }
  } else {
    return getThumbnailFilename($file);
  }
}

// Convert an image filename into the thumbnail filename
function getThumbnailFilename($file) {
  return "./cache/".base64_encode($file).".jpg";
}

?>
