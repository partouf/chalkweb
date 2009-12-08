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

include_once( "chalkweb/classes/errorhandling.inc.php" );

include_once( "chalkweb/classes/template.inc.php" );

class CSubMenu extends CTemplate {
	protected $pages = array ();

	public function __construct( $addhome = true ) {
		parent::__construct( "chalkweb/basetemplates/submenu.tpl" );

		if ( $addhome ) {
			$this->AddLink( "Home", "/" );
		}
	}

	public function AddLink( $title, $link, $altclass = "menuli" ) {
		$this->pages[] = array ( "title" => $title, "link" => $link, "class" => $altclass );
	}

	public function Prepare() {
		$this->AssignLoop( "menulinks", $this->pages );
	}
}

class CMainMenu extends CTemplate {
	protected $submenus = array ();

	public function __construct() {
		parent::__construct( "chalkweb/basetemplates/mainmenu.tpl" );
	}

	public function AddSubmenu( $name, $submenuobj ) {
		$this->submenus[$name] = $submenuobj;
	}

	public function Prepare() {
		$loop = array();
		foreach ( $this->submenus as $name => $submenuobj ) {
			$submenuobj->Prepare();
			$loop[] = array ( "title" => $name, "submenu" => $submenuobj->Process() );
		}
			$this->AssignLoop( "menu", $loop );
	}
}

?>