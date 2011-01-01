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


class CTemplate {
	protected $queries = array ();
	protected $queryCallbacks = array ();
	protected $valueVars = array ();
	protected $conditionVars = array ();
	protected $loopVars = array ();
	protected $loopCallbacks = array ();
	protected $treeVars = array ();

	protected $templatefile;
	protected $templateContents;

	protected $preBlockVar = "<!-- %%";
	protected $postBlockVar = "%% -->";

	protected $preVar = "%%";
	protected $postVar = "%%";

	//-----------------------------------------------------
	protected function retreiveContents() {
		if ( $this->templatefile != "" ) {
			$this->templateContents = file_get_contents( $this->templatefile );
		}
	}

	protected function resolveValueVars() {
		foreach( $this->valueVars as $key => $value ) {
			$this->templateContents = str_replace( $this->preVar . $key . $this->postVar, $value, $this->templateContents );
		}
	}

	protected function resolveConditionVars( $lstConditions, $sContent ) {
		$sNewContent = $sContent;
		foreach( $lstConditions as $key => $value ) {
			// if's
			$search1 = $this->preBlockVar . "if:begin:" . $key . $this->postBlockVar;
			$search2 = $this->preBlockVar . "if:end:" . $key . $this->postBlockVar;
			if ( $value ) {
				$sNewContent = str_replace( $search1, "", $sNewContent );
				$sNewContent = str_replace( $search2, "", $sNewContent );
			} else {
				$p2 = 0;
				$p1 = 0;
				while ( ($p2 !== false) && ($p1 !== false) ) {
					$p1 = strpos( $sNewContent, $search1, $p2 );
					if ( $p1 !== false ) {
						$p2 = strpos( $sNewContent, $search2, $p1 );
						if ( $p2 !== false ) {
							$sNewContent = substr( $sNewContent, 0, $p1 ) . substr( $sNewContent, $p2 + strlen($search2) );
							$p2 = $p2 - ($p2 - $p1);
						}
					}
				}
			}

			// if not's
			$search1 = $this->preBlockVar . "ifnot:begin:" . $key . $this->postBlockVar;
			$search2 = $this->preBlockVar . "ifnot:end:" . $key . $this->postBlockVar;
			if ( !$value ) {
				$sNewContent = str_replace( $search1, "", $sNewContent );
				$sNewContent = str_replace( $search2, "", $sNewContent );
			} else {
				$p2 = 0;
				$p1 = 0;
				while ( ($p2 !== false) && ($p1 !== false) ) {
					$p1 = strpos( $sNewContent, $search1, $p2 );
					if ( $p1 !== false ) {
						$p2 = strpos( $sNewContent, $search2, $p1 );
						if ( $p2 !== false ) {
							$sNewContent = substr( $sNewContent, 0, $p1 ) . substr( $sNewContent, $p2 + strlen($search2) );
							$p2 = $p2 - ($p2 - $p1);
						}
					}
				}
			}
		}

		return $sNewContent;
	}

	protected function resolveSingleLoop( $loopName, $records, &$content ) {
		if ( isset($this->loopCallbacks[$loopName]) ) {
			list( $callbackobject, $callbackfunction ) = $this->loopCallbacks[$loopName];
		} else {
			$callbackobject = false;
			$callbackfunction = "";
		}
		
		$search1 = $this->preBlockVar . "while:begin:" . $loopName . $this->postBlockVar;
		$search2 = $this->preBlockVar . "while:end:" . $loopName . $this->postBlockVar;

		$p1 = 0;
		$p2 = 0;
		$p1 = strpos( $content, $search1, $p2 );
		if ( $p1 !== false ) {
			$p2 = strpos( $content, $search2, $p1 );
			if ( $p2 !== false ) {
				$loopContentOriginal = substr( $content, $p1 + strlen($search1), $p2 - $p1 - strlen($search1) );
				
				$odd = true;
				$content = substr( $content, 0, $p1 ) . substr( $content, $p2 + strlen($search2) );
				$p2 = $p2 - ($p2 - $p1);
				foreach ( $records as $indval => $values ) {
					if ( $callbackfunction != "" ) {
						if ( $callbackobject ) {
							$callbackobject->$callbackfunction( $values, $indval );
						} else {
							$callbackfunction( $values, $indval );
						}
					}

					$loopContentCopy = "" . $loopContentOriginal;
					foreach ( $values as $key => $value ) {
						if ( is_array( $value ) ) {
							if ( $key == "ifs" ) {
								$loopContentCopy = $this->resolveConditionVars( $value, $loopContentCopy );
							} else if ( $key == "trees" ) {
								$loopContentCopy = $this->resolveTreeVars( $value, $loopContentCopy );
							} else if ( $key == "whiles" ) {
								$this->resolveLoopVars( $value, $loopContentCopy );
							}
						} else {
							$loopContentCopy = str_replace( $this->preVar . $key . $this->postVar, $value, $loopContentCopy );
						}
					}

					if ( $odd ) {
						$loopContentCopy = str_replace( $this->preVar . "oddeventxt" . $this->postVar, "odd", $loopContentCopy );
					} else {
						$loopContentCopy = str_replace( $this->preVar . "oddeventxt" . $this->postVar, "even", $loopContentCopy );
					}

					$content = substr( $content, 0, $p2 ) . $loopContentCopy . substr( $content, $p2 );
					$p2 = $p2 + strlen( $loopContentCopy );

					$odd = !$odd;
				}
			}
		}
	}
	
	protected function resolveLoopVars( $loops, &$content ) {
		foreach( $loops as $loopName => $records ) {
			$this->resolveSingleLoop( $loopName, $records, $content );
		}
	}
	
	protected function resolveQueries() {
		foreach( $this->queries as $loopName => $query ) {
			list( $callbackobject, $callbackfunction ) = $this->queryCallbacks[$loopName];

			$search1 = $this->preBlockVar . "while:begin:" . $loopName . $this->postBlockVar;
			$search2 = $this->preBlockVar . "while:end:" . $loopName . $this->postBlockVar;

			$p1 = 0;
			$p2 = 0;
			$p1 = strpos( $this->templateContents, $search1, $p2 );
			if ( $p1 !== false ) {
				$p2 = strpos( $this->templateContents, $search2, $p1 );
				if ( $p2 !== false ) {
					$loopContentOriginal = substr( $this->templateContents, $p1 + strlen($search1), $p2 - $p1 - strlen($search1) );

					$odd = true;
					$this->templateContents = substr( $this->templateContents, 0, $p1 ) . substr( $this->templateContents, $p2 + strlen($search2) );
					$p2 = $p2 - ($p2 - $p1);
					$indval = 0;
					while ( $query->Next() ) {
						$values = $query->GetArray();

						if ( $callbackfunction != "" ) {
							if ( $callbackobject ) {
								$callbackobject->$callbackfunction( $values, $indval );
							} else {
								$callbackfunction( $values, $indval );
							}
						}

						$loopContentCopy = "" . $loopContentOriginal;
						foreach ( $values as $key => $value ) {
							$loopContentCopy = str_replace( $this->preVar . $key . $this->postVar, $value, $loopContentCopy );
						}

						if ( $odd ) {
							$loopContentCopy = str_replace( $this->preVar . "oddeventxt" . $this->postVar, "odd", $loopContentCopy );
						} else {
							$loopContentCopy = str_replace( $this->preVar . "oddeventxt" . $this->postVar, "even", $loopContentCopy );
						}

						$this->templateContents = substr( $this->templateContents, 0, $p2 ) . $loopContentCopy . substr( $this->templateContents, $p2 );
						$p2 = $p2 + strlen( $loopContentCopy );

						$odd = !$odd;
						$indval++;
					}
				}
			}
		}
	}

	protected function getHtmlTree( $lstItems, $ordered ) {
		$html = "";

		if ( $ordered ) {
			$html .= "<ol>";
		} else {
			$html .= "<ul>";
		}

		foreach ( $lstItems as $item ) {
			if ( is_array($item) ) {
				$html .= $this->getHtmlTree( $item, $ordered );
			} else {
				$html .= "<li>" . $item . "</li>";
			}
		}

		if ( $ordered ) {
			$html .= "</ol>";
		} else {
			$html .= "</ul>";
		}

		return $html;
	}

	protected function resolveTreeVars( $lstItems, $sContent ) {
		$newcontent = $sContent;

		foreach( $this->treeVars as $key => $items ) {
			$html = $this->getHtmlTree( $items, false );

			$newcontent = str_replace( $this->preVar . $key . $this->postVar, $html, $newcontent );
		}

		return $newcontent;
	}

	//-----------------------------------------------------

	public function Prepare() {
		// override...
	}

	//-----------------------------------------------------
	public function __construct( $file ) {
		$this->templatefile = $file;
		$this->subtitle = "";
		$this->templateContents = "";
	}

	public function AssignValue( $key, $value ) {
		$this->valueVars[$key] = $value;
	}

	public function AssignValues( $values ) {
		foreach ( $values as $key => $value ) {
			$this->valueVars[$key] = $value;
		}
	}

	public function AssignCondition( $key, $value ) {
		// $value is expected to be true/false
		$this->conditionVars[$key] = $value;
	}

	public function AssignLoop( $key, $value, &$rendercallbackobject = null, $rendercallbackfunction = "" ) {
		// $value is expected to be an array with multiple key => value variables
		$this->loopVars[$key] = $value;
		$this->loopCallbacks[$key] = array( $rendercallbackobject, $rendercallbackfunction );
	}

	public function AssignQuery( $key, $query, &$rendercallbackobject = null, $rendercallbackfunction = "" ) {
		// $query is expected to be a CSQLQuery object
		$this->queries[$key] = $query;
		$this->queryCallbacks[$key] = array( $rendercallbackobject, $rendercallbackfunction );
	}

	public function AssignTree( $key, $value ) {
		$this->treeVars[$key] = $value;
	}

	public function GetSubtitle() {
		return $this->subtitle;
	}

	public function Process() {
		$this->retreiveContents();

		$this->resolveQueries();
		$this->resolveLoopVars( $this->loopVars, $this->templateContents );
		$this->templateContents = $this->resolveConditionVars( $this->conditionVars, $this->templateContents );
		$this->templateContents = $this->resolveTreeVars( $this->treeVars, $this->templateContents );
		$this->resolveValueVars();

		$contents = $this->templateContents;
		$this->templateContents = "";

		return $contents;
	}
}

?>