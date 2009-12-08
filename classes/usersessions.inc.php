<?php
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

if( isset( $_SESSION['sessionip'] ) && ($_SESSION['sessionip'] != $_SERVER['REMOTE_ADDR']) ) {
	session_unset();
	session_destroy();
	die("Connection does not originate from original IP-address");
}

if( !isset( $_SESSION['sessionip'] ) ) {
	$_SESSION['sessionip'] = $_SERVER['REMOTE_ADDR'];
}


if ( !isset( $_SESSION['loginuser_id'] ) ) {
	$_SESSION['loginuser_id'] = -1;
}

if ( !isset( $_SESSION['loginuser_syncid'] ) ) {
	$_SESSION['loginuser_syncid'] = -1;
}

if ( !isset( $_SESSION['loginuser_name'] ) ) {
	$_SESSION['loginuser_name'] = "nouser";
}

if ( !isset( $_SESSION['loginuser_permissions'] ) ) {
	$_SESSION['loginuser_permissions'] = 0;
}

if ( !isset( $_SESSION['loginuser_email'] ) ) {
	$_SESSION['loginuser_email'] = "";
}

if ( empty( $_SESSION['loginsalt'] ) ) {
	$_SESSION['loginsalt'] = GenerateSalt();
}


$globalCurrentUser = null;

if ( $_SESSION['loginuser_id'] != -1 ) {
	$globalCurrentUser = new CUser();
	$globalCurrentUser->LoadFromSessionUser();
}


// ----------------------------------------------------------------------------------

function GenerateSalt() {
	$s = "";

	for ( $i = 0; $i < 20; $i++ ) {
		$s .= rand( 0, 255 );
		$s = rand( 0, 255 ) . $s;
	}

	return sha1($s);
}

// ----------------------------------------------------------------------------------

class CUser {
	protected $id;
	protected $syncid;
	protected $name;
	protected $permissions;
	protected $email;

	public function __construct( $id = -1, $syncid = -1, $name = "nouser", $permissions = 0, $email = "" ) {
		$this->id			= $id;
		$this->syncid		= $syncid;
		$this->name			= $name;
		$this->permissions	= $permissions;
		$this->email		= $email;
	}

	public function LoadFromSessionUser() {
		$this->id			= $_SESSION['loginuser_id'];
		$this->syncid		= $_SESSION['loginuser_syncid'];
		$this->name			= $_SESSION['loginuser_name'];
		$this->permissions	= $_SESSION['loginuser_permissions'];
		$this->email		= $_SESSION['loginuser_email'];
	}

	public function SaveToSessionUser() {
		$_SESSION['loginuser_id']			= $this->id;
		$_SESSION['loginuser_syncid']		= $this->syncid;
		$_SESSION['loginuser_name']			= $this->name;
		$_SESSION['loginuser_permissions']	= $this->permissions;
		$_SESSION['loginuser_email']		= $this->email;
	}

	public function GetID() {
		return $this->id;
	}

	public function GetSyncID() {
		return $this->syncid;
	}

	public function GetName() {
		return $this->name;
	}

	public function GetPermissions() {
		return $this->permissions;
	}

	public function GetEmail() {
		return $this->email;
	}
}

?>