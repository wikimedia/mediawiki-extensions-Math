'use strict';

const { assert, REST } = require( 'api-testing' );

describe( 'Math check endpoint test', () => {
	const client = new REST( 'rest.php/math/v0' );

	it( 'should get a 200 response for a good request', async () => {

		const { status, body } = await client.post( '/check/tex', {
			q: '\\sin  x'
		},
		'application/x-www-form-urlencoded'
		);

		assert.strictEqual( status, 200 );
		assert.strictEqual( body.success, true );
		// Check that the superfluous space in the input was removed
		assert.strictEqual( body.checked, '\\sin x' );
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
