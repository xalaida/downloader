var fs = require('fs');
var http = require('http');
var path = require('path');

http.createServer(function (req, res) {
    console.log(`Ping at "${req.url}" with headers [${JSON.stringify(req.headers)}]`);

    if (req.url === '/') {
        res.write('Welcome home!');
        res.end();
    } else if (req.url === '/hello-world') {
        const filename = 'hello-world.txt';
        const filePath = path.join(__dirname, 'fixtures', filename);

        fs.readFile(filePath, function (err, data) {
            if (err) {
                res.writeHead(404);
                res.end(JSON.stringify(err));
                return;
            }

            fs.stat(filePath, function (err, stats) {
                const lastModifiedAt = new Date(stats.mtime)
                lastModifiedAt.setMilliseconds(0);
                res.writeHead(200, {
                    'Last-Modified': lastModifiedAt.toUTCString(),
                    'Content-Type': 'text/plain'
                });
                res.end(data);
            })
        });
    } else if (req.url === '/redirect') {
        res.writeHead(301, { 'Location': req.url.replace('/redirect', '/redirect/hello-world.txt') });
        res.end();
    } else if (req.url.startsWith('/redirect')) {
        const location = req.url.replace('/redirect', '');

        console.log('Redirect to location: ' + location)

        res.writeHead(301, { 'Location': location });
        res.end();
    } else if (req.url.startsWith('/private')) {
        if (req.headers.authorization === `Basic ${btoa('client:secret')}`) {
            res.writeHead(301, { 'Location': req.url.replace('/private', '') });
            res.end();
        } else {
            res.writeHead(403);
            res.end();
        }
    } else if (req.url.startsWith('/content')) {
        const filename = 'hello-world.txt';
        const filepath = path.join(__dirname, 'fixtures', filename);

        fs.readFile(filepath, function (err, data) {
            if (err) {
                res.writeHead(404);
                res.end(JSON.stringify(err));
                return;
            }

            res.setHeader('Content-Disposition', `attachment; filename="${filename}"`);
            res.writeHead(200);
            res.end(data);
        });
    } else {
        const filename = req.url;
        const filePath = path.join(__dirname, 'fixtures', filename);

        console.log('File path: ' + filePath);

        fs.readFile(filePath, function (err, data) {
            if (err) {
                res.writeHead(404);
                res.end(JSON.stringify(err));
                return;
            }

            fs.stat(filePath, function (err, stats) {
                const lastModifiedAt = new Date(stats.mtime)
                lastModifiedAt.setMilliseconds(0);

                if (! req.headers['if-modified-since']) {
                    res.writeHead(200, { 'Last-Modified': lastModifiedAt.toUTCString() });
                    res.end(data);
                } else if (lastModifiedAt.getTime() > (new Date(req.headers['if-modified-since']).getTime())) {
                    res.writeHead(200, { 'Last-Modified': lastModifiedAt.toUTCString() });
                    res.end(data);
                } else {
                    res.writeHead(304);
                    res.end();
                }
            })
        });
    }
}).listen(4020);
