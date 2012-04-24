<?php

class MathLaTeXML{

	function render($super) {

			$url="http://latexml.mathweb.org/convert";

			$texcmd=urlencode("\$".$super->tex."\$");
			$post="profile=math&tex=$texcmd";

			$result= EDUtils::getDataFromURL( $url, "json", $post );

			$super->mathml= $result['result'][0];

			wfDebug( "Latexml request: $post\n" );
			wfDebug( "Latexml output:\n $result\n---\n" );
		}
		
}