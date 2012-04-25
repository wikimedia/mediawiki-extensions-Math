<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2012 Tomasz Wegrzanowski, Brion Vibber, and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * Contains the driver function for the LaTeXML daemon
 * @file
 * @ingroup Parser
 */
class MathLaTeXML{

	function render($super) {
			global $wgLaTeXMLUrl;


			$texcmd=urlencode("\$".$super->tex."\$");
			$post="profile=math&tex=$texcmd";

			$result= EDUtils::getDataFromURL( $wgLaTeXMLUrl, "json", $post );

			$super->mathml= $result['result'][0];

			wfDebug( "Latexml request: $post\n" );
			wfDebug( "Latexml output:\n $result\n---\n" );
		}
		
}