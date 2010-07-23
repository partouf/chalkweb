<?php
/*
   Copyright 2010 P.B. Quist

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

class CXmlUI_Object {
	protected $htmltag = "div";
	public $htmlclass = "object";
	protected $extraattr = ""; 
	
	public $identifier = "";
	public $caption = "";
	protected $x = 0;
	protected $y = 0;
	protected $w = 125;
	protected $h = 25;
	public $parent = null;
	
	public $visible = true;
	
	protected $onHide = "";
	protected $onShow = "";
	protected $onClick = "";
	
	protected $children = array();
	
	public function __construct( $c = "object" ) {
		$this->htmlclass = $c;
	}
	
	public function AddChild( $obj ) {
		$this->children[$obj->identifier] = $obj;
		$obj->parent = $this;
	}
	
	public function LoadFromDOMNode( $node ) {
		$this->htmlclass = $node->nodeName;
		
		if ( $node->hasAttributes() ) {
			$this->identifier = $node->getAttribute("identifier");
			$this->caption = $node->getAttribute("caption");
			$this->x = $node->getAttribute("x");
			$this->y = $node->getAttribute("y");
			
			if ( $node->hasAttribute("w") ) {
				$this->w = $node->getAttribute("w");
			}
			if ( $node->hasAttribute("h") ) {
				$this->h = $node->getAttribute("h");
			}
			if ( $this->htmlclass == "button" ) {
				$this->h = 40;
			}
		
			$this->onHide = $node->getAttribute("onHide"); 
			$this->onShow = $node->getAttribute("onShow");
			$this->onClick = $node->getAttribute("onClick");
			
			$this->visible = !($node->getAttribute("visible") == "false");
		}
		
		if ( $this->htmlclass == "listbox" ) {
			$this->htmltag = "select";
			$this->extraattr = "multiple='multiple'";
		} else if ( $this->htmlclass == "combobox" ) {
			$this->htmltag = "select";
			$this->extraattr = "";
		} else if ( $this->htmlclass == "multiline" ) {
			$this->htmltag = "textarea";
			$this->extraattr = "";
		} else if ( $this->htmlclass == "button" ) {
			$this->htmltag = "input";
			$this->extraattr = "type='button'";
		} else if ( $this->htmlclass == "edit" ) {
			$this->htmltag = "input";
			$this->extraattr = "";
		} else if ( $this->htmlclass == "password" ) {
			$this->htmltag = "input";
			$this->extraattr = "type='password'";
		}
		
		if ( $node->hasChildNodes() ) {
			foreach ( $node->childNodes as $child ) {
				if ( $child->nodeName != "#text" ) { 
					$childobj = new CXmlUI_Object();
					$childobj->LoadFromDOMNode( $child );
					$this->AddChild( $childobj );
				}
			}
		}
	}
	
	public function getJavascriptCode() {
		$js = "";
		
		if ( $this->identifier && ($this->htmlclass == "window") ) {
			$js .=
				"function show_" . $this->identifier . "() {\n" .
				"   var obj = $('#" . $this->identifier . "');\n" .
				"   obj.show();\n";
			if ( $this->onShow ) {
				$js .=
				"   " . $this->onShow . "(obj);\n";
			}
			$js .=
				"}\n\n";
			
			$js .=
				"function hide_" . $this->identifier . "() {\n" .
				"   var obj = $('#" . $this->identifier . "');\n";
			if ( $this->htmlclass == "window" ) {
				$js .=
					"   obj.dialog('close');\n";				
			} else {
				$js .= 
					"   obj.hide();\n";
			}
			$js .=
				"}\n\n";
		}
		
		$js2 = "";
		if ( $this->onClick ) {
			$js2 .=
			"   \$('#" . $this->identifier . "').click( function(obj) {\n" .
			"      " . $this->onClick . "(this);\n" .
			"   });\n\n";
		}
		if ( $this->htmlclass == "window" ) {
			$js2 .= "   \$('#" . $this->identifier . "').dialog( { minWidth: " . $this->getW() . ", minHeight: " . $this->getH() . ", width: " . $this->getW() . ", height: " . $this->getH();
			if ( $this->onHide ) {
				$js2 .= " , close: function(event, ui) { " . $this->onHide . "(); }\n";
			}
			$js2 .= "   });\n";
			if ( !$this->visible ) {
				$js2 .= "   hide_" . $this->identifier . "();\n";
			} else {
				$js2 .= "   show_" . $this->identifier . "();\n";
			}
		} else if ( $this->htmlclass == "button" ) {
			$js2 .= "   \$('#" . $this->identifier . "').button();\n";
		} else if ( $this->htmlclass == "groupbox" ) {
			$js2 .= "   \$('#grpbox_" . $this->identifier . "').accordion( { fillSpace: true } );\n";
		}
		
		if ( $js2 ) {
			$js .= "\$(document).ready( function() {\n";
			$js .= $js2;
			$js .= "});\n\n";
		}
		
		foreach ( $this->children as $child ) {
			$js .= $child->getJavascriptCode();
		}
		
		return $js;
	}
	
	public function getX() {
		if ( $this->parent ) {
			if ( $this->x < 0 ) {
				return $this->parent->getClientW() + $this->x; 
			}
		}
		
		return $this->x;
	}
	
	public function getY() {
		$yp = 0;
		if ( $this->parent ) {
			if ( $this->parent->htmlclass == "window" ) {
				$yp += 60;
			}
			
			if ( $this->y < 0 ) {
				return $this->parent->getClientH() + $this->y + $yp; 
			}
		}
		
		return $this->y + $yp;
	}
	
	public function getW() {
		$wd = 0;
		if ( $this->htmlclass == "groupbox" ) {
			$wd = 10;
		}
		
		if ( $this->parent ) {
			if ( $this->w <= 0 ) {
				return $this->parent->getClientW() + $this->w - $this->getX() - $wd; 
			}
		}
		
		return $this->w;
	}
	
	public function getH() {
		$hd = 0;
		if ( $this->htmlclass == "groupbox" ) {
			$hd = 0;
		}
		
		if ( $this->parent ) {
			$hp = 0;
			if ( $this->parent->htmlclass == "window" ) {
				$hp = 60;
			}
			
			if ( $this->h <= 0 ) {
				return $this->parent->getClientH() + $this->h - $this->getY() + $hp - $hd; 
			}
		}
		
		return $this->h;
	}
	
	public function getClientW() {
		return $this->getW();
	}
	
	public function getClientH() {
		$hp = 0;
		if ( $this->htmlclass == "window" ) {
			$hp = 60;
		} else if ( $this->htmlclass == "groupbox" ) {
			$hp = 55;
		} 
	
		return $this->getH() - $hp;
	}
	
	public function AsHtml() {
		$html = "";
	
		$html .= 
			"<" . $this->htmltag .
			" id='" . $this->identifier . "'" .
			" name='" . $this->identifier . "'" .
			" class='" . $this->htmlclass . "'" .
			" title='" . $this->caption . "'";
		
		if ( $this->htmlclass != "window" ) {
			$html .= " style='" .
					"position: absolute;";
	
			$x = $this->getX();
			$y = $this->getY();

			$html .=
					"left: " . $x . "px;" .
					"top: " . $y . "px;";
		
			if ( ($this->htmlclass == "interface") || ($this->htmlclass == "screen") || ($this->htmlclass == "root")) {
				$html .= 
					"width: 100%;" .
					"height: 100%;'";
			} else {
				$w = $this->getW();
				$h = $this->getH();
				
				$html .= 
					"width: " . $w . "px;" .
					"height: " . $h . "px;'";
			}
			
			if ( $this->caption ) {
				if ( $this->htmlclass == "button" ) {
					$html .= 
						" " . $this->extraattr . " value='" . $this->caption . "'>";
				} else if ( $this->htmlclass == "window" ) {
					$html .= 
						" " . $this->extraattr . ">";
				} else if ( $this->htmlclass == "panel" ) {
					$html .= 
						" " . $this->extraattr . ">";
				} else if ( $this->htmlclass == "edit" ) {
					$html .= 
						" " . $this->extraattr . " value='" . $this->caption . "'>";
				} else if ( $this->htmlclass == "password" ) {
					$html .= 
						" " . $this->extraattr . ">";
				} else if ( $this->htmlclass == "groupbox" ) {
					$w = $this->getW();
					$h = $this->getH();

					$html .= "><div id='grpbox_" . $this->identifier . "' " .
						"style='" . 
						"width: " . $w . "px;" .
						"height: " . $h . "px;'>" .
						"<h3 class='caption'><a href='#'>" . $this->caption . "</a></h3>";

					$html .=
						"<div class='groupbox_content'>";
				} else {
					$html .= 
						" " . $this->extraattr . ">";
					$html .= "<div class='caption'>" . $this->caption . "</div>";
				}
			} else {
				$html .= 
					" " . $this->extraattr . ">";
			}
		} else {
			$html .= ">";
		}
		
		foreach ( $this->children as $child ) {
			$html .= $child->AsHtml(); 
		}

		if ( $this->htmlclass == "groupbox" ) {
			$html .= "</div></div>";
		}
		$html .= "</" . $this->htmltag . ">";
		
		return $html;
	}
}

class CXmlUI_Reader {
	protected $filename;
	protected $rootobj = null;
	
	public function __construct() {
		$this->filename = "";
	}

	public function LoadFromFile( $filename ) {
		$this->filename = $filename;
		
		$objDOM = new DOMDocument();
		$objDOM->load( $filename );

		$this->rootobj = new CXmlUI_Object("screen");
		
		$nodes = $objDOM->getElementsByTagName("window");
		foreach ( $nodes as $child ) {
			$obj = new CXmlUI_Object();
			$obj->LoadFromDOMNode( $child );
			
			$this->rootobj->AddChild( $obj );
		}
	}
	
	public function AsHtml() {
		$js = "<script language='javascript'>" . $this->rootobj->getJavascriptCode() . "</script>";
		return $js . $this->rootobj->AsHtml();
	}
} 

?>