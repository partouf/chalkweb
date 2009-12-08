<?
/*
   Copyright 2009 P.B. Quist

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

function getExtension( $filename ) {
    $path_info = pathinfo($filename);
    return $path_info['extension'];
}

function resizeImage( $filename, $newfilename, $sourcelocation, $destinationlocation, $maxwidth, $maxheight = 0 ) {
	$ext = strtolower( getExtension($filename) );

	list($width, $height) = getimagesize( $sourcelocation . $filename );
	if( $width <= $maxwidth ) {
		$maxwidth = $width;
	}

	$multiplier = $maxwidth / $width;

	$newwidth = $width * $multiplier;
	$newheight = $maxheight;
	if ( $maxheight === 0 ) {
		$newheight = $height * $multiplier;
	}

	$newimg = imagecreatetruecolor($newwidth, $newheight);

	if($ext=='jpg' || $ext=='jpeg') {
		$source = imagecreatefromjpeg( $sourcelocation . $filename );
	} else if($ext=='gif') {
		$source = imagecreatefromgif( $sourcelocation . $filename );
	} else if($ext=='png') {
		$source = imagecreatefrompng( $sourcelocation . $filename );
	} else {
		return false;
	}

	imagecopyresampled( $newimg, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height );

	$ext = strtolower( getExtension($filename) );
	if($ext=='jpg' || $ext=='jpeg') {
		return imagejpeg( $newimg, $destinationlocation . $filename );
	} else if($ext=='gif') {
		return imagegif( $newimg, $destinationlocation . $filename );
	} else if($ext=='png') {
		return imagepng( $newimg, $destinationlocation . $filename );
	} else {
		return false;
	}
}

?>