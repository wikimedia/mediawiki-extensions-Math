const { assert, REST } = require( 'api-testing' );

describe( 'Math check endpoint test', () => {
	const client = new REST( 'rest.php/math/v0' );

	it( 'should get a 200 response for a good request', async () => {

		const result = await client.post( '/check/tex', {
			q: '\\sin  x'
		},
		'application/x-www-form-urlencoded'
		);

		assert.strictEqual( result.status, 200 );
		assert.strictEqual( result.body.success, true );
		// Check that the superfluous space in the input was removed
		assert.strictEqual( result.body.checked, '\\sin x' );
		assert.strictEqual( result.header['x-resource-location'], '3e25a0e63fc6d8d183a12ab9f0df047cea44e2d9' );
	} );

	it( 'should get a 400 response for missing post param', async () => {

		const { status, body } = await client.post( '/check/tex', {
			wrong: '\\sin  x'
		},
		'application/x-www-form-urlencoded'
		);

		assert.strictEqual( status, 400 );
		assert.strictEqual( body.failureCode, 'missingparam' );
		assert.strictEqual( body.name, 'q' );
	} );

	it( 'should get a 400 response for bad value of type param', async () => {

		const { status, body } = await client.post( '/check/thebadvalue', {
			q: '\\sin  x'
		},
		'application/x-www-form-urlencoded'
		);

		assert.strictEqual( status, 400 );
		assert.strictEqual( body.failureCode, 'badvalue' );
		assert.strictEqual( body.name, 'type' );
		assert.strictEqual( body.value, 'thebadvalue' );

	} );

	it( 'should get a 400 response for invalid LaTeX', async () => {

		const { status, body } = await client.post( '/check/tex', {
			q: '\\invalid  x'
		},
		'application/x-www-form-urlencoded'
		);

		assert.strictEqual( status, 400 );
		assert.strictEqual( body.success, false );
		assert.strictEqual( body.error, 'SyntaxError: Illegal TeX function' );
		assert.strictEqual( body.detail.details, '\\invalid' );
	} );

	it( 'should not accept GET requests', async () => {

		const { status } = await client.get( '/check/tex', {
			q: '\\sin  x'
		},
		'application/x-www-form-urlencoded'
		);

		assert.strictEqual( status, 405 );
	} );
} );

describe( 'Math formula endpoint test', () => {
	const client = new REST( 'rest.php/math/v0' );

	before( ()=> client.post( '/check/tex', {	q: '\\sin  x' },'application/x-www-form-urlencoded' ) );

	it( 'should get a 200 response for a good request', async () => {

		const result = await client.get( '/formula/3e25a0e63fc6d8d183a12ab9f0df047cea44e2d9' );

		assert.strictEqual( result.status, 200 );
		assert.strictEqual( result.body.type, 'tex' );
		assert.strictEqual( result.body.q, '\\sin x'  );

	} );

	it( 'should get a 404 response for bad value of hash param', async () => {

		const { status, body } = await client.get( '/formula/thebadvalue' );

		assert.strictEqual( status, 404 );
		assert.strictEqual( body.httpReason, 'Not Found' );
		assert.include( body.messageTranslations.en, 'thebadvalue' );

	} );

} );

describe( 'Math render endpoint test', () => {
	const client = new REST( 'rest.php/math/v0' );

	before( ()=> client.post( '/check/tex', {	q: '\\sin  x' },'application/x-www-form-urlencoded' ) );

	it( 'should get a 200 response for a mml request', async () => {

		const result = await client.get( '/render/mml/3e25a0e63fc6d8d183a12ab9f0df047cea44e2d9' );

		assert.strictEqual( result.status, 200 );
		assert.include( result.text, '<math' );
		assert.include( result.text, 'sin' );
		assert.include( result.text, '<mrow' );
	} );

	it( 'should get a 200 response for a svg request', async () => {

		const result = await client.get( '/render/svg/3e25a0e63fc6d8d183a12ab9f0df047cea44e2d9' );

		assert.strictEqual( result.status, 200 );
		assert.include( result.text, '<svg' );
		assert.include( result.text, '<path'  );

	} );

	it( 'should get a 200 response for a png request', async () => {

		const result = await client.get( '/render/png/3e25a0e63fc6d8d183a12ab9f0df047cea44e2d' );

		assert.strictEqual( result.status, 200 );

	} );

	it( 'should get a 404 response for bad value of hash param', async () => {

		const { status, body } = await client.get( '/render/mml/thebadvalue' );

		assert.strictEqual( status, 404 );
		assert.strictEqual( body.httpReason, 'Not Found' );
		assert.include( body.messageTranslations.en, 'thebadvalue' );

	} );

	it( 'should get a 400 response for bad value of format param', async () => {

		const { status, body } = await client.get( '/render/thebadvalue/3e25a0e63fc6d8d183a12ab9f0df047cea44e2d9');
		assert.strictEqual( status, 400 );
		assert.strictEqual( body.failureCode, 'badvalue' );
		assert.strictEqual( body.name, 'format' );
		assert.strictEqual( body.value, 'thebadvalue' );

	} );

} );