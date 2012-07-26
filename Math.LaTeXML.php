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
			if( is_array( $wgLaTeXMLUrl ) ) {
				$pick = mt_rand( 0, count( $wgLaTeXMLUrl ) - 1 );
				$host = $wgLaTeXMLUrl[$pick];
			} else {
				$host=$wgLaTeXMLUrl;
			}
			$texcmd=urlencode("\$".$super->tex."\$");
			$post="summary=true&profile=fragment&tex=$texcmd";
			$time_start = microtime(true);
			$res= Http::post($host, array("postData"=> $post,"timeout"=>60));
			$time_end = microtime(true);
			$time = $time_end - $time_start;
			$result=json_decode($res);
			// if ($result->result){
			// $super->mathml= $result->result;
			// }else{
				// var_dump($result);
				// var_dump($post);
			// }
			if($result->result){//$result&&is_array($result)&&is_array($result['result'])&&count($result['result'])>0){
				if($result->status!="No obvious problems"){
				$super->status= $result->status;
				$super->log =$result->log;}
				if((strpos($result->result, '<?xml version="1.0" encoding="utf-8"?>') === 0))
					{
					return false;
					}
				$super->mathml= $result->result;}
			else
				{wfDebug("LaTeXML","\nLaTeXML Error:". var_export(array($result,$post, $wgLaTeXMLUrl),true)."\n\n");
				return false;}

			wfDebugLog( "LaTeXML","Latexml request: $post\n processed in $time seconds." );
			return true;
			//wfDebug( "Latexml output:\n $result\n---\n" );
		}
		static function LaTeXMLRender($URL,$texcmd){

			$texcmd=urlencode("\$".$texcmd>tex."\$");
			$post="profile=math&tex=$texcmd";

			$result= EDUtils::getDataFromURL( $URL, "json", $post );
			if (is_array($result['result'])){
			$super->mathml= $result['result'][0];
			}else{
				var_dump($result);
				var_dump($post);
			}
			wfDebug( "Latexml request: $post\n" );
			wfDebug( "Latexml output:\n $result\n---\n" );

		}
		
}