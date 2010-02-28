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

function GetRawPostVar( $varname, $default = "" ) {
	if ( isset( $_POST[$varname] ) ) {
		return $_POST[$varname];
	}

	return $default;
}

function GetRawGetVar( $varname, $default = "" ) {
	if ( isset( $_GET[$varname] ) ) {
		return $_GET[$varname];
	}

	return $default;
}

function GetPostVarAsInt( $varname, $default = -1 ) {
	$i = $default;
	if ( isset( $_POST[$varname] ) ) {
		if ( is_numeric($_POST[$varname]) ) {
			$i = $_POST[$varname];
		}
	}

	return $i;
}

function GetGetVarAsInt( $varname, $default = -1 ) {
	$i = $default;

	if ( isset( $_GET[$varname] ) ) {
		if ( is_numeric($_GET[$varname]) ) {
			$i = $_GET[$varname];
		}
	}

	return $i;
}

function GetRawPostOrSession( $varname, $default = "" ) {
	$val = $default;

	if ( isset($_POST[$varname] ) ) {
		$val = trim( GetRawPostVar($varname,$default) );
		$_SESSION[$varname] = $val;
	} else {
		if ( isset($_SESSION[$varname]) ) {
			$val = $_SESSION[$varname];
		}
	}

	return $val;
}

function GetPostOrSessionAsInt( $varname, $default = -1 ) {
	$i = $default;

	if ( isset($_POST[$varname] ) ) {
		$i = GetPostVarAsInt($varname,$default);
		$_SESSION[$varname] = $i;
	} else {
		if ( isset($_SESSION[$varname]) ) {
			if ( is_numeric($_SESSION[$varname]) ) {
				$i = $_SESSION[$varname];
			}
		}
	}

	return $val;
}

?>