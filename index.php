<html>
<head>
  <title>Ian's Photos (and other memories)</title>
  <link href='http://fonts.googleapis.com/css?family=Indie+Flower' rel='stylesheet' type='text/css'>
  <style>
    html, body { margin: 0; padding: 0.5em; background-color: #eeeeee; }
    h1 { text-align: center; font-family: Georgia, Times, serif; color: black; font-size: 5em; font-weight: 300; }
    h2 { text-align: center; font-family: Georgia, Times, serif; color: black; font-size: 3em; font-weight: 300; }
    h3 { text-align: left; font-family: "Liberation Sans", "Lucida Sans", sans-serif; color: gray; font-size: 2em; margin: 1.5em 0.5em 0.5em; border-bottom: 2px solid gray; }
    p#backlink { float:left; }
    span.photoblock { display: inline-block; margin: 0.5em; padding: 10px; vertical-align: top; width: 200px; height: 200px; background-color: white; border: 1px solid gray; font-family: 'Indie Flower', cursive; font-size: 1.2em; -moz-box-shadow: 5px 5px 8px 0px #ccc; -webkit-box-shadow: 5px 5px 8px 0px #ccc; box-shadow: 5px 5px 8px 0px #ccc;}
    span.photoblock span.imgwrapper { display: inline-block; height: 150px; width: 200px; margin-bottom: 5px; overflow: none;}
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
            if ($year > 1970) {
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
          print("<span class=\"photoblock\"><a href=\"./?album=" . base64_encode($dir) . "\"><span class=\"imgwrapper\"><img src=\"" . $thumbnailFile . "\" /></span><br/>" . $dir . "</a></span>");
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
        print("<span class=\"photoblock\"><a href=\"" . $file . "\"><span class=\"imgwrapper\"><img src=\"" . $thumbnailFile . "\" /></span><br/>" . pathinfo($file, PATHINFO_FILENAME) . "</a></span>");
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
      $new_height = 150;

      // Create new image
      $tmpimg = imagecreatetruecolor( $new_width, $new_height );
      
      // Copy old to new, cropping as necessary depending on the ratio of the
      // original
      if (($width / $new_width) > ($height / $new_height))
      {
        // Full size pic has a wider ratio than thumbnail
        $usable_width = $height / $new_height * $new_width;
        $width_starts_from = ($width - $usable_width) / 2;
        imagecopyresampled( $tmpimg, $img, 0, 0, $width_starts_from, 0, $new_width, $new_height, $usable_width, $height );
      }
      else
      {
        // Full size pic has a narrower ratio than thumbnail
        $usable_height = $width / $new_width * $new_height;
        $height_starts_from = ($height - $usable_height) / 2;
        imagecopyresampled( $tmpimg, $img, 0, 0, 0, $height_starts_from, $new_width, $new_height, $width, $usable_height );
      }

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
