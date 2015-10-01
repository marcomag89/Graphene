/**
 * Created by Marco on 24/09/15.
 */
var phpServer = require('node-php-server');

var port=80;
phpServer.createServer({
    port: port,
    hostname: '0.0.0.0',
    base: '.',
    keepalive: false,
    open: false,
    bin: 'php',
    router: __dirname + '/index.php'
});

console.log('listening on: '+port);
// Close server
//phpServer.close();