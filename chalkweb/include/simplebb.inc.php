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

function translateBBCode( $sText, $disallowembeds ) {
	$bbreplace = array (
		'/(\[[Bb]\])(.+)(\[\/[Bb]\])/',
		'/(\[[Ii]\])(.+)(\[\/[Ii]\])/',
		'/(\[url\])(.+)(\[\/url\])/',
		'/(\[url=)(.+)(\])(.+)(\[\/url\])/',
		'/(\[imgurl=)(.+)(\])(.+)(\[\/imgurl\])/',
		'/(\[img\])(.+)(\[\/img\])/',
		'/(\[img=)(.+)(\])(.+)(\[\/img\])/',
		'/(\[code\])(.+)(\[\/code\])/',
		'/(\[quote\])(.+)(\[\/quote\])/'
	);
	$bbreplacements = array (
		'<b class="bbcode_b">\\2</b>',
		'<em class="bbcode_em">\\2</em>',
		'<a class="bbcode_a" target="_BLANK" href="\\2">\\2</a>',
		'<a class="bbcode_a" target="_BLANK" href="\\2">\\4</a>',
		'<a class="bbcode_a imglink" target="_BLANK" href="\\2">\\4</a>',
		'<a class="imglink" href="\\2" target="_BLANK"><img class="bbcode_img" src="\\2" border="0" /></a>',
		'<a class="imglink" href="\\2" target="_BLANK"><img class="bbcode_img" src="\\4" border="0" /></a>',
		'<pre class="bbcode_code">\\2</pre>',
		'<block class="bbcode_quote>\\2</block>'
	);

	$sRetText = preg_replace( $bbreplace, $bbreplacements, $sText );

	if ( !$disallowembeds ) {
		$sRetText = translateBBvids_ToEmbeds( $sRetText );
	} else {
		$sRetText = translateBBvids_ToLinks( $sRetText );
	}

	$sRetText = str_ireplace( "[code]", '<pre class="bbcode_code">', $sRetText );
	$sRetText = str_ireplace( "[/code]", '</pre>', $sRetText );

	$sRetText = str_ireplace( "[quote]", '<block class="bbcode_quote">', $sRetText );
	$sRetText = str_ireplace( "[/quote]", '</block>', $sRetText );

	return $sRetText;
}

function translateBBvids_ToEmbeds( $sText ) {
	$bbreplace = array (
		'/(\[youtube\])(http:\/\/www\.youtube\.com\/watch\?v\=)(.+)(\[\/youtube\])/'
	);
	$bbreplacements = array (
		'<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/\\3&fs=1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/\\3&fs=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344" wmode="opaque"></embed></object>'
	);

	$sRetText = preg_replace( $bbreplace, $bbreplacements, $sText );

	return $sRetText;
}

function translateBBvids_ToLinks( $sText ) {
	$bbreplace = array (
		'/(\[youtube\])(http:\/\/www\.youtube\.com\/watch\?v\=)(.+)(\[\/youtube\])/'
	);
	$bbreplacements = array (
		'<a class="bbcode_a" target="_BLANK" href="http://www.youtube.com/v/\\3&fs=1">{embed}</a>'
	);

	$sRetText = preg_replace( $bbreplace, $bbreplacements, $sText );

	return $sRetText;

}

function testBB() {
	$string = translateBBCode("This [i]is[/i] [b]cool[/b] - [url=http://www.tutorio.com]Tutorio.com Tutorials[/url] - [url]http://www.tutorio.com[/url] [img]http://www.google.nl/intl/nl_nl/images/logo.gif[/img]");

	if ( $string == "This <em class=\"bbcode_em\">is</em> <b class=\"bbcode_b\">cool</b> - <a class=\"bbcode_a\" href=\"http://www.tutorio.com\">Tutorio.com Tutorials</a> - <a class=\"bbcode_a\" href=\"http://www.tutorio.com\">http://www.tutorio.com</a> <img class=\"bbcode_img\" src=\"http://www.google.nl/intl/nl_nl/images/logo.gif\" />" ) {
		echo "succes ($string)\n";
	} else {
		echo "failed ($string)\n";
	}
}


?>