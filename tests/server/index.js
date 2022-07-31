var fs = require('fs'),
    http = require('http');

http.createServer(function (req, res) {
    if (req.url === '/') {
        res.write('Welcome home!');
        res.end();
    } else if (req.url.startsWith('/redirect')) {
        res.writeHead(301, { 'Location': req.url.replace('/redirect', '/fixtures') });
        res.end();
    } else if (req.url.startsWith('/fixtures')) {
        fs.readFile(__dirname + req.url, function (err, data) {
            if (err) {
                res.writeHead(404);
                res.end(JSON.stringify(err));
                return;
            }

            fs.stat(__dirname + req.url, function (err, stats) {
                if ((new Date(stats.mtime).getTime()) > (req.headers['if-modified-since'] ? new Date(req.headers['if-modified-since']).getTime() : 0)) {
                    res.writeHead(200, { 'Last-Modified': new Date(stats.mtime).toUTCString() });
                    res.end(data);
                } else {
                    res.writeHead(304);
                    res.end();
                }
            })
        });
    } else if (req.url.startsWith('/private')) {
        if (req.headers.authorization === `Basic ${btoa('client:secret')}`) {
            res.writeHead(301, { 'Location': req.url.replace('/private', '/fixtures') });
            res.end();
        } else {
            res.writeHead(403);
            res.end();
        }
    } else {
        res.writeHead(404);
        res.end();
    }
}).listen(8888);
