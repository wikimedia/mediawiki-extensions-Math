<?php
/**
 * MediaWiki math extension
 *
 * (c)2014 Moritz Schubotz
 * GPLv2 license; info in main package.
 *
 * @group Math
 * Remove the group 'Stub' after you have started mathoid and svgtex
 * @group Stub
 * Class MathoidParserTest
 */
class MathoidParserTest extends MediaWikiTestCase {
	const SAMPLEMATHML='<math xmlns="http://www.w3.org/1998/Math/MathML">
  <semantics>
    <mrow>
      <mi>sin</mi>
      <mo>&#x2061;<!-- ⁡ --></mo>
      <mo stretchy="false">(</mo>
      <mi>x</mi>
      <mo stretchy="false">)</mo>
    </mrow>
    <annotation encoding="application/x-tex">\sin(x)</annotation>
  </semantics>
</math>';
	const SAMPLESVG='<svg xmlns:xlink="http://www.w3.org/1999/xlink" style="width: 6ex; height: 2.5ex; vertical-align: -0.75ex; margin-top: 1px; margin-right: 0px; margin-bottom: 1px; margin-left: 0px; position: static; " viewBox="0 -778.5807122916057 2608 1057.1614245832113" xmlns="http://www.w3.org/2000/svg"><defs id="MathJax_SVG_glyphs"><path id="MJMAIN-73" stroke-width="10" d="M295 316Q295 356 268 385T190 414Q154 414 128 401Q98 382 98 349Q97 344 98 336T114 312T157 287Q175 282 201 278T245 269T277 256Q294 248 310 236T342 195T359 133Q359 71 321 31T198 -10H190Q138 -10 94 26L86 19L77 10Q71 4 65 -1L54 -11H46H42Q39 -11 33 -5V74V132Q33 153 35 157T45 162H54Q66 162 70 158T75 146T82 119T101 77Q136 26 198 26Q295 26 295 104Q295 133 277 151Q257 175 194 187T111 210Q75 227 54 256T33 318Q33 357 50 384T93 424T143 442T187 447H198Q238 447 268 432L283 424L292 431Q302 440 314 448H322H326Q329 448 335 442V310L329 304H301Q295 310 295 316Z"></path><path id="MJMAIN-69" stroke-width="10" d="M69 609Q69 637 87 653T131 669Q154 667 171 652T188 609Q188 579 171 564T129 549Q104 549 87 564T69 609ZM247 0Q232 3 143 3Q132 3 106 3T56 1L34 0H26V46H42Q70 46 91 49Q100 53 102 60T104 102V205V293Q104 345 102 359T88 378Q74 385 41 385H30V408Q30 431 32 431L42 432Q52 433 70 434T106 436Q123 437 142 438T171 441T182 442H185V62Q190 52 197 50T232 46H255V0H247Z"></path><path id="MJMAIN-6E" stroke-width="10" d="M41 46H55Q94 46 102 60V68Q102 77 102 91T102 122T103 161T103 203Q103 234 103 269T102 328V351Q99 370 88 376T43 385H25V408Q25 431 27 431L37 432Q47 433 65 434T102 436Q119 437 138 438T167 441T178 442H181V402Q181 364 182 364T187 369T199 384T218 402T247 421T285 437Q305 442 336 442Q450 438 463 329Q464 322 464 190V104Q464 66 466 59T477 49Q498 46 526 46H542V0H534L510 1Q487 2 460 2T422 3Q319 3 310 0H302V46H318Q379 46 379 62Q380 64 380 200Q379 335 378 343Q372 371 358 385T334 402T308 404Q263 404 229 370Q202 343 195 315T187 232V168V108Q187 78 188 68T191 55T200 49Q221 46 249 46H265V0H257L234 1Q210 2 183 2T145 3Q42 3 33 0H25V46H41Z"></path><path id="MJMAIN-28" stroke-width="10" d="M94 250Q94 319 104 381T127 488T164 576T202 643T244 695T277 729T302 750H315H319Q333 750 333 741Q333 738 316 720T275 667T226 581T184 443T167 250T184 58T225 -81T274 -167T316 -220T333 -241Q333 -250 318 -250H315H302L274 -226Q180 -141 137 -14T94 250Z"></path><path id="MJMATHI-78" stroke-width="10" d="M52 289Q59 331 106 386T222 442Q257 442 286 424T329 379Q371 442 430 442Q467 442 494 420T522 361Q522 332 508 314T481 292T458 288Q439 288 427 299T415 328Q415 374 465 391Q454 404 425 404Q412 404 406 402Q368 386 350 336Q290 115 290 78Q290 50 306 38T341 26Q378 26 414 59T463 140Q466 150 469 151T485 153H489Q504 153 504 145Q504 144 502 134Q486 77 440 33T333 -11Q263 -11 227 52Q186 -10 133 -10H127Q78 -10 57 16T35 71Q35 103 54 123T99 143Q142 143 142 101Q142 81 130 66T107 46T94 41L91 40Q91 39 97 36T113 29T132 26Q168 26 194 71Q203 87 217 139T245 247T261 313Q266 340 266 352Q266 380 251 392T217 404Q177 404 142 372T93 290Q91 281 88 280T72 278H58Q52 284 52 289Z"></path><path id="MJMAIN-29" stroke-width="10" d="M60 749L64 750Q69 750 74 750H86L114 726Q208 641 251 514T294 250Q294 182 284 119T261 12T224 -76T186 -143T145 -194T113 -227T90 -246Q87 -249 86 -250H74Q66 -250 63 -250T58 -247T55 -238Q56 -237 66 -225Q221 -64 221 250T66 725Q56 737 55 738Q55 746 60 749Z"></path></defs><g stroke="black" fill="black" stroke-width="0" transform="matrix(1 0 0 -1 0 0)"><use href="#MJMAIN-73" xlink:href="#MJMAIN-73"></use><use href="#MJMAIN-69" x="399" y="0" xlink:href="#MJMAIN-69"></use><use href="#MJMAIN-6E" x="682" y="0" xlink:href="#MJMAIN-6E"></use><use href="#MJMAIN-28" x="1243" y="0" xlink:href="#MJMAIN-28"></use><use href="#MJMATHI-78" x="1637" y="0" xlink:href="#MJMATHI-78"></use><use href="#MJMAIN-29" x="2214" y="0" xlink:href="#MJMAIN-29"></use></g></svg>';

	/**
	 * Checks tex functionality of svgtex (running on http://localhost:16000)
	 * @test
	 */
	public function testSvgTex(){
		$tex = "\\sin(x)";
		$res = $this->makeRequest('http://localhost:16000', 'q='.rawurlencode( $tex ));
		$this->assertGreaterThan(10,strlen($res),'Request result to short:\''.$res.'\'');
		$this->XmlTypeCheck($res,'svg');
		$this->assertContains('svg',$res);
		$this->assertEquals(self::SAMPLESVG,$res);
	}

	/**
	 * Checks mathml functionality of svgtex (running on http://localhost:16000)
	 * @test
	 */
	public function testSvgMml(){
		$res = $this->makeRequest('http://localhost:16000', 'type=mml&q='.rawurlencode( self::SAMPLEMATHML ));
		$this->assertGreaterThan(10,strlen($res),'Request result to short:\''.$res.'\'');
		$this->XmlTypeCheck($res,'svg');
		$this->assertContains('svg',$res);
		$this->assertEquals(self::SAMPLESVG,$res);
	}

	/**
	 * Checks tex functionality of mathoid (running on http://localhost:10042)
	 * @test
	 */
	public function testSimple(){
		$requestResult = $this->makeRequest('http://localhost:10042', 'q=\sin(x)');
		$res = $this->decodeJson($requestResult);
		$this->assertTrue($res->success);
		$this->check($res->svg,'svg',self::SAMPLESVG,'SVG');
		$this->check($res->mml,'math',self::SAMPLEMATHML,'MathML');
	}

	/**
	 * Checks MathML functionality of mathoid (running on http://localhost:10042)
	 * @test
	 */
	public function testMathML(){
		$requestResult = $this->makeRequest('http://localhost:10042', 'type=mml&q='.rawurlencode( self::SAMPLEMATHML ));
		$res = $this->decodeJson($requestResult);
		$this->assertTrue($res->success);
		$this->check($res->svg,'svg',self::SAMPLESVG,'SVG');
		$this->check($res->mml,'math',self::SAMPLEMATHML,'MathML');
	}

	/**
	 * Checks any XML element
	 * 1) if it's well-formed
	 * 2) contains $contains
	 * 3) is equal to $equals
	 * Here equality is invariant according to whitespace and HTML-comment changes.
	 * @param $xml
	 * @param $contains
	 * @param $equals
	 * @param string $text
	 */
	private function check($xml,$contains,$equals,$text=''){
		$this->XmlTypeCheck($xml,$text);
		$this->assertContains($contains,$xml);
		$this->assertEquals($this->stripWS($equals),$this->stripWS($xml));
	}
	/**
	 * Replaces whitespaces and HTML comments by a single whitespace
	 * @param string $in
	 * @return string
	 */
	private function stripWS($in){
		return preg_replace(array('/\s+/','/<!--(.*?)-->/'),'',$in);
	}

	/**
	 * Posts $postData to $host
	 * equivalent to <code>curl -d '$postData' $host</code>
	 * @param string $host
	 * @param string $postData
	 * @return String body of the result
	 */
	private function makeRequest($host, $postData){
		$options = array( 'method' => 'POST', 'postData' => $postData, 'timeout' => 2 );
		$req = MWHttpRequest::factory( $host, $options );
		$status = $req->execute();
		$this->assertTrue( $status->isGood(), "error in request:".$status->getHTML() );
		return $req->getContent();
	}

	/**
	 * @param $input
	 * @return mixed
	 */
	private function decodeJson($input){
		$jsonRes = json_decode( $input );
		$this->assertFalse(!$jsonRes,"JSON conversion failed");
		return $jsonRes;
	}

	/**
	 * Checks if the input is well formed
	 * @param $xml
	 * @param string $subj
	 */
	private function  XmlTypeCheck($xml,$subj=''){
		$X = new XmlTypeCheck( $xml, null, false );
		$this->assertTrue($X->wellFormed,"$subj XML ist not well formed:\n\t".$xml);
	}


}

