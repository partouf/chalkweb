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

include_once( "chalkweb/classes/menu.inc.php" );
include_once( "chalkweb/classes/template.inc.php" );
include_once( "chalkweb/classes/subpage.inc.php" );


class CWebPage {
	protected $menu = null;
	protected $mainTemplate = null;
	protected $contentTemplate = null;
	protected $title = "Untitled";
	protected $pages = array();
	protected $requestedPage = "";

	//-------------------------------------------------
	protected function initMenu() {
		if ( $this->menu == null ) {
			$this->menu = new CMenu();
		}
	}

	protected function initTemplates() {
		if ( $this->mainTemplate == null ) {
			$this->mainTemplate = new CTemplate( "chalkweb/basetemplates/table2080.tpl" );
			$this->mainTemplate->Prepare();
		}

		if ( $this->contentTemplate == null ) {
			$this->contentTemplate = new CSubPage( "chalkweb/basetemplates/loremipsum.tpl" );
			$this->contentTemplate->Prepare();
		}
	}

	protected function loadPage( $page ) {
		include_once( "pages/" . $page . ".php" );

		$classname = "C" . $page;
		$this->contentTemplate = new $classname();
	}

	protected function checkAndLoadPage() {
		if ( isset( $_GET['page'] ) ) {
			$selectedPage = $_GET['page'];
		} else {
			$selectedPage = "main";
		}

		foreach ( $this->pages as $page ) {
			if ( $page == $selectedPage ) {
				$this->loadPage( $page );

				return $page;
			}
		}

		return false;
	}


	//-------------------------------------------------

	public function __construct() {
	}

	public function SetMenu( $menu ) {
		$this->menu = $menu;
	}

	public function SetMainTemplate( &$template ) {
		$this->mainTemplate = $template;
	}

	public function SetContentTemplate( &$template ) {
		$this->contentTemplate = $template;
	}

	public function SetTitle( $title ) {
		$this->title = $title;
	}

	public function AddPage( $name ) {
		$this->pages[] = $name;
	}

	public function OnPageLoaded( $pagename ) {
		// override
	}

	public function OnPageNotFound() {
		// override
	}

	public function Show() {
		$page = $this->checkAndLoadPage();
		if ( !$page ) {
			$this->OnPageNotFound( $this->requestedPage );

			if ( $this->contentTemplate == null ) {
				$this->contentTemplate = new CTemplate( "chalkweb/basetemplates/pagenotfound.tpl" );
				$this->contentTemplate->Prepare();
			}
		} else {
			$this->contentTemplate->Prepare();
			$this->OnPageLoaded( $page );
		}

		$this->initMenu();
		$this->initTemplates();

		$this->menu->Prepare();

		$this->mainTemplate->AssignValue( "TITLE", $this->title );
		$this->mainTemplate->AssignValue( "CONTENT", $this->contentTemplate->Process() );
		$this->mainTemplate->AssignValue( "MENU", $this->menu->Process() );

		echo $this->mainTemplate->Process();
	}
}

?>