/*
 * Storoid worker.
 *
 * Configure in storoid.config.json.
 */

// global includes
var express = require('express'),
	cluster = require('cluster'),
	http = require('http'),
	fs = require('fs'),
	child_process = require('child_process'),
	request = require('request'),
	querystring = require('querystring');

var config;

// Get the config
try {
	config = JSON.parse(fs.readFileSync('./mathoid.config.json', 'utf8'));
} catch ( e ) {
	// Build a skeleton localSettings to prevent errors later.
	console.error("Please set up your mathoid.config.json from the example " +
			"storoid.config.json.example");
	process.exit(1);
}

/**
 * The name of this instance.
 * @property {string}
 */
var instanceName = cluster.isWorker ? 'worker(' + process.pid + ')' : 'master';

console.log( ' - ' + instanceName + ' loading...' );



/*
 * Backend setup
 */
var restarts = 10;

var backend,
	backendPort,
	requestQueue = [];

var startBackend = function () {
	if (backend) {
		backend.kill();
	}
	var backendCB = function (err, stdout, stderr) {
		if (err) {
			restarts--;
			if (true || restarts > 0) {
				startBackend();
			}
			console.error(err.toString());
			//process.exit(1);
		}
	};
	backendPort = Math.floor(9000 + Math.random() * 50000);
	console.error(instanceName + ': Starting backend on port ' + backendPort);
	backend = child_process.exec('phantomjs main.js ' + backendPort, backendCB);
	backend.stdout.pipe(process.stderr);
	backend.stderr.pipe(process.stderr);
};
startBackend();

/* -------------------- Web service --------------------- */


var app = express.createServer();

// Increase the form field size limit from the 2M default.
app.use(express.bodyParser({maxFieldsSize: 25 * 1024 * 1024}));
app.use( express.limit( '25mb' ) );

app.get('/', function(req, res){
	res.write('<html><body>\n');
	res.write('Welcome to Mathoid. POST to / with var <code>tex</code>');
	res.write('<form action="/" method="POST"><input type="text" name="tex"></form>');
	res.end('</body></html>');
});

// robots.txt: no indexing.
app.get(/^\/robots.txt$/, function ( req, res ) {
	res.end( "User-agent: *\nDisallow: /\n" );
});

var handleRequests = function() {
	// Call the next request on the queue
	if (requestQueue.length) {
		requestQueue[0]();
	}
};


function handleRequest(req, res, tex) {
	// do the backend request
	var reqbody = new Buffer(querystring.stringify({tex: tex})),
		options = {
		method: 'POST',
		uri: 'http://localhost:' + backendPort.toString() + '/',
		body: reqbody,
		headers: {
			'Content-Length': reqbody.length,
			'Content-Type': 'application/x-www-form-urlencoded'
		},
		timeout: 2000
	};
	request(options, function (err, response, body) {
		if (err) {
			var errBuf = new Buffer(JSON.stringify({
				tex: tex,
				error: err.toString()
			}));
			res.writeHead(500,
				{
					'Content-Type': 'application/json',
					'Content-Length': errBuf.length
				});
			res.end(errBuf);
			// don't retry the request
			requestQueue.shift();
			startBackend();
			return handleRequests();
		}
		res.writeHead(200,
			{
				'Content-Type': 'application/json',
				'Content-length': body.length
			});
		res.end(body);
		requestQueue.shift();
		handleRequests();
	});
}

app.post(/^\/$/, function ( req, res ) {
	// First some rudimentary input validation
	if (!req.body.tex) {
		res.writeHead(400);
		return res.end(JSON.stringify({error: "'tex' post parameter is missing!"}));
	}
	var tex = req.body.tex;

	requestQueue.push(handleRequest.bind(null, req, res, tex));
	// phantomjs only handles one request at a time. Enforce this.
	if (requestQueue.length === 1) {
		// Start this process
		handleRequests();
	}

});


console.log( ' - ' + instanceName + ' ready' );

module.exports = app;

