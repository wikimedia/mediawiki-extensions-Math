#!/usr/bin/env node
/**
 * A very basic cluster-based server runner. Restarts failed workers, but does
 * not much else right now.
 */

var cluster = require('cluster'),
	storoid_worker = require('./mathoid-worker.js');

// Start a few more workers than there are cpus visible to the OS, so that we
// get some degree of parallelism even on single-core systems. A single
// long-running request would otherwise hold up all concurrent short requests.
var numCPUs = require('os').cpus().length + 3;

if (cluster.isMaster) {
  // Fork workers.
  for (var i = 0; i < 1 /*numCPUs*/; i++) {
    cluster.fork();
  }

  cluster.on('exit', function(worker, code, signal) {
    if (!worker.suicide) {
      var exitCode = worker.process.exitCode;
      console.log('worker', worker.process.pid,
                  'died ('+exitCode+'), restarting.');
      cluster.fork();
    }
  });

  process.on('SIGTERM', function() {
    console.log('master shutting down, killing workers');
    var workers = cluster.workers;
    Object.keys(workers).forEach(function(id) {
        console.log('Killing worker ' + id);
        workers[id].destroy();
    });
    console.log('Done killing workers, bye');
    process.exit(1);
  } );
} else {
  process.on('SIGTERM', function() {
    console.log('Worker shutting down');
    process.exit(1);
  });
  // when running on appfog.com the listen port for the app
  // is passed in an environment variable.  Most users can ignore this!
  storoid_worker.listen(process.env.VCAP_APP_PORT || 8010);
}
