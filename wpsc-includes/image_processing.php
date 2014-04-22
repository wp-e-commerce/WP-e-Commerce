<?php
function image_processing($image_input, $image_output, $width = null, $height = null,$imagefield='') {

	/*
	* this handles all resizing of images that results in a file being saved, if no width and height is supplied, then it just copies the image
	*/
	$imagetype = getimagesize($image_input);
	if(file_exists($image_input) && is_numeric($height) && is_numeric($width) && function_exists('imagecreatefrompng') && (($height != $imagetype[1]) && ($width != $imagetype[0]))) {
		switch($imagetype[2]) {
			case IMAGETYPE_JPEG:
			$src_img = imagecreatefromjpeg($image_input);
			$pass_imgtype = true;
			break;

			case IMAGETYPE_GIF:
			$src_img = imagecreatefromgif($image_input);
			$pass_imgtype = true;
			break;

			case IMAGETYPE_PNG:
			$src_img = imagecreatefrompng($image_input);
			$pass_imgtype = true;
			break;

			default:
			move_uploaded_file($image_input, ($imagedir.basename($_FILES[$imagefield]['name'])));
			$image = esc_attr(basename($_FILES[$imagefield]['name']));
			return true;
			exit();
			break;
		}

		if($pass_imgtype === true) {
			$source_w = imagesx($src_img);
			$source_h = imagesy($src_img);

			//Temp dimensions to crop image properly
			$temp_w = $width;
			$temp_h = $height;
			// if the image is wider than it is high and at least as wide as the target width.
				if (($source_h <= $source_w)) {
					if ($height < $width ) {
						$temp_h = ($width / $source_w) * $source_h;
					} else {
						$temp_w = ($height / $source_h) * $source_w;
					}
				} else {
					$temp_h = ($width / $source_w) * $source_h;
				}

			// Create temp resized image
			$temp_img = ImageCreateTrueColor( $temp_w, $temp_h );
			$bgcolor = ImageColorAllocate( $temp_img, 255, 255, 255 );
			ImageFilledRectangle( $temp_img, 0, 0, $width, $height, $bgcolor );
			ImageAlphaBlending( $temp_img, TRUE );
			if($imagetype[2] == IMAGETYPE_PNG) {
				imagesavealpha($temp_img,true);
				ImageAlphaBlending($temp_img, false);
			}

			// resize keeping the perspective
			Imagecopyresampled( $temp_img, $src_img, 0, 0, 0, 0, $temp_w, $temp_h, $source_w, $source_h );


			if($imagetype[2] == IMAGETYPE_PNG) {
				imagesavealpha($temp_img,true);
				ImageAlphaBlending($temp_img, false);
			}


			$dst_img = ImageCreateTrueColor($width,$height);
			$white = ImageColorAllocate( $dst_img, 255, 255, 255 );
			ImageFilledRectangle( $dst_img, 0, 0, $width, $height, $white );
			ImageAlphaBlending($dst_img, TRUE );
			imagecolortransparent($dst_img, $white);


			// X & Y Offset to crop image properly
			if($temp_w < $width) {
				$w1 = ($width/2) - ($temp_w/2);
			} else if($temp_w == $width) {
				$w1 = 0;
			} else {
				$w1 = ($width/2) - ($temp_w/2);
			}

			if($imagetype[2] == IMAGETYPE_PNG) {
				imagesavealpha($dst_img,true);
				ImageAlphaBlending($dst_img, false);
			}


			// Final thumbnail cropped from the center out.
			if(!isset($h1))
				$h1 = 0;
			ImageCopy( $dst_img, $temp_img, $w1, $h1, 0, 0, $temp_w, $temp_h );

			$image_quality = wpsc_image_quality();

			switch($imagetype[2]) {
				case IMAGETYPE_JPEG:
				if(@ ImageJPEG($dst_img, $image_output, $image_quality) == false) { return false; }
				break;

				case IMAGETYPE_GIF:
				if(function_exists("ImageGIF")) {
					if(@ ImageGIF($dst_img, $image_output) == false) { return false; }
				} else {
					ImageAlphaBlending($dst_img, false);
					if(@ ImagePNG($dst_img, $image_output) == false) { return false; }
				}
				break;

				case IMAGETYPE_PNG:
				imagesavealpha($dst_img,true);
				ImageAlphaBlending($dst_img, false);
				if(@ ImagePNG($dst_img, $image_output) == false) { return false; }
				break;
			}
			usleep(50000);  //wait 0.05 of of a second to process and save the new image
			imagedestroy($dst_img);
			//$image_output

			$stat = stat( dirname( $image_output ));
			$perms = $stat['mode'] & 0000666;
			@ chmod( $image_output, $perms );
			return true;
		}
	} else {
		copy($image_input, $image_output);
		$image = esc_attr(basename($_FILES[$imagefield]['name']));
		return $image;
	}
	return false;
}

?>
