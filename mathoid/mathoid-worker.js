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
	querystring = require('querystring');

var config;

var ready = false;
var starting = false;

const STOPPED=0,
	START_REQUEST=1,
	STARTING=2,
	START_SUCESS=3,
	READY=4,
	STOPREQUEST=5,
	STOPPING=6;
var state = STOPPED; 
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
	if ( state == START_REQUEST ){
		state = STARTING;
		if (backend) {
			backend.kill();
		}
		backend = null;
		var stdoutListener;
		var backendCB = function (err, stdout, stderr) {
			if (err) {
				restarts--;
				if (restarts > 0) {
					state = STOPREQUEST;
					restartBackend();
				}
				console.error('Strange-Error'+err.toString());
				console.log('Restarts:'+restarts);
				//process.exit(1);
			}
		};
		var designatedBackendPort = Math.floor(9000 + Math.random() * 50000);
		backend = child_process.exec('phantomjs main.js ' + designatedBackendPort, backendCB);
		console.error( instanceName + ': Starting backend on port ' + designatedBackendPort);
		backend.stdout.pipe(process.stdout);
		backend.stderr.pipe(process.stderr);
		backend.stdout.on('data', function (data) {
			if (/^READY on port/.test(data)) {
				var pattern = /\d+/
				var port = parseInt(data.match(pattern));
				if ( state == STARTING){
					state = START_SUCESS;
					setTimeout(function(){
						backendPort= port;
						console.log('phantom listing on port' + backendPort);
						state = READY;}
						,500);	
				} else {
					console.log('igore old startup:' + data);
				}
			}});
		backend.stdout.on('data', function (data) {
			if (/committing/.test(data)) {
				var pattern = /\d+/;
				var port = parseInt(data.match(pattern));
				if (state == READY && port == backendPort ){
					state = STOPREQUEST;
					console.log('phantom died at port '+port);
					stopBackend();
				} else {
					console.log('igore old shutdown: ' +data+ ' instead of '+ backendPort);
				}
			}
		});
	}
};
var stopBackend = function (){
	if (state == STOPREQUEST){
		state = STOPPING;
		backend.kill();
		backendPort=0;
		backend = null;
		state = STOPPED;
	}
};

startBackend();
var restartBackend = function(){
	stopBackend();
	state = START_REQUEST;
	startBackend();
};
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


function handleRequest(req, res, tex, oldPort) {
	// do the backend request
	var query = new Buffer(querystring.stringify({tex:tex})),
		options = {
			hostname: 'localhost',
			port: backendPort.toString(),
			path: '/',
			method: 'POST',
			headers: {
				'Content-Length': query.length,
				'Content-Type': 'application/x-www-form-urlencoded',
				'Connection': 'close'
			},
			agent: false
		};
	var chunks = [];
	//console.log(options);

	var httpreq = http.request(options, function(httpres) {
		httpres.on('data', function(chunk) {
			chunks.push(chunk);
		});
		httpres.on('end', function() {
			var buf = Buffer.concat(chunks);
			res.writeHead(200,
			{
				'Content-type': 'application/json',
				'Content-length': buf.length
			});
			res.write(buf);
			res.end();
			requestQueue.shift();
			handleRequests();
		});
		httpres.on('error', function(err) {
			if ( backendPort == oldPort ){
				if (state == READY){
					state = STOPREQUEST;
					restartBackend();
				}
			}
			console.log(' http error', err.toString());
			res.writeHead(500);
			return res.end(JSON.stringify({sucess:false,error: "Backend error: " + err.toString()}));
		});
	});

	httpreq.setTimeout(200); 

	httpreq.end(query);

}

app.post(/^\/$/, function ( req, res ) {
	// First some rudimentary input validation
	if (!req.body.tex) {
		res.writeHead(200);
		return res.end(JSON.stringify({sucess:false,error: "'tex' post parameter is missing!"}));
	}
	var tex = req.body.tex;
	console.log('getting'+tex);
	console.log('readystate:'+state+' restarts: '+restarts );
	//if ( ! ready ){	setTimeout(function(){console.log('oha');},100)	}
	if ( state == READY ){
		console.log('ready');
		requestQueue.push(handleRequest.bind(null, req, res, tex, backendPort));
		// phantomjs only handles one request at a time. Enforce this.
		if ( requestQueue.length === 1) {
		// Start this process
		handleRequests();
		}
	} else if ( state === STOPPED ){
		state = START_REQUEST;
		startBackend();
		return res.end(JSON.stringify({sucess:false, error: "Backend stopped...trying to restart"}));
	} else if( state == STARTING) {
		setTimeout(function(){
			if(state == STARTING){
			state = STOPPED;
			}}, 2000);
				return res.end(JSON.stringify({sucess:false, error: "Backend is starting"+state+"...try again later: "}));
	} else {
		return res.end(JSON.stringify({sucess:false, error: "Backend in state"+state+"...try again later: "}));
	}
});


console.log( ' - ' + instanceName + ' ready' );

module.exports = app;

