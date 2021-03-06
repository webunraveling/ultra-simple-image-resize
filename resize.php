<?php
/*
 * Ultra Simple Image Resize - Provides a simple form for people to upload
 * and then receive a resize image.
 * Copyright (C) 2016 Jason Raveling
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Set target directory to temporarily save images
$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
$maxsize=800; // Max vert or horiz pixels

// Check if image file is a actual image or fake image
if (isset($_POST["submit"])) {
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if($check !== false) {
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }
}
// Check if file already exists...NOT really needed now as I change script and all uploads are deleted
if (file_exists($target_file)) {
    echo "Sorry, file already exists.";
    $uploadOk = 0;
}
// Check file size and set max upload weight
if ($_FILES["fileToUpload"]["size"] > 20000000) {
    echo "Sorry, your file is too large. (20mb max)";
    $uploadOk = 0;
}
// Allow certain file formats
if ($imageFileType != "jpg" && $imageFileType != "JPG" && $imageFileType != "png" && $imageFileType != "PNG" && $imageFileType != "jpeg" && $imageFileType != "JPEG"
    && $imageFileType != "gif" && $imageFileType != "GIF") {
    echo "Sorry, only jpg, jpeg, png & gif files are allowed.";
    $uploadOk = 0;
}
// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    echo "     Sorry, your file was not uploaded.";
    // if everything is ok, try to upload file
} else {
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
    } else {
        echo "Sorry, there was an error uploading your file.";
    }

    // function to rotate images that are on side or upsidedown..mostly IOS. Is called later
    function autoRotateImage($image) {
        $orientation = $image->getImageOrientation();

        switch($orientation) {
        case imagick::ORIENTATION_BOTTOMRIGHT:
            $image->rotateimage("#000", 180); // rotate 180 degrees
            break;

        case imagick::ORIENTATION_RIGHTTOP:
            $image->rotateimage("#000", 90); // rotate 90 degrees CW
            break;

        case imagick::ORIENTATION_LEFTBOTTOM:
            $image->rotateimage("#000", -90); // rotate 90 degrees CCW
            break;
        }
    }

    // create new Imagick object
    $image = new Imagick($target_file);

    // Resizes to whichever is larger, width or height
    if ($image->getImageHeight() <= $image->getImageWidth()) {
        // Resize image using the lanczos resampling algorithm based on width
        $image->resizeImage($maxsize,0,Imagick::FILTER_LANCZOS,1);
    } else {
        // Resize image using the lanczos resampling algorithm based on height
        $image->resizeImage(0,$maxsize,Imagick::FILTER_LANCZOS,1);
    }
    // call rotation function
    autoRotateImage($image); 
    // Set to use jpeg compression
    $image->setImageCompression(Imagick::COMPRESSION_JPEG);
    // Set compression level (1 lowest quality, 100 highest quality)
    $image->setImageCompressionQuality(75);
    // Strip out unneeded meta data
    $image->stripImage();
    // Writes resultant image to output directory
    $image->writeImage($target_file);
    // Destroys Imagick object, freeing allocated resources in the process
    $image->destroy();

    // download the file
    if (file_exists($target_file)) {
        // rename file on download
        $temp = explode(".", $target_file);
        $newFileName = current($temp) . "Resized." . end($temp);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($newFileName).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($target_file));
        readfile($target_file);
        unlink($target_file);
        exit;
    }
}
?>
