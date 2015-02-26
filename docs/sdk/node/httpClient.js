// don't care if the certificate of an https is valid
process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";
// node modules
var fs = require('fs');
var path = require('path'); 

/**
 * Uploads a file to a server via http form data
 * @param options, requestObj
 * see the request option for further option params
 * option.hostname: the host to call against
 * option.port: the port to connect to
 * option.path: the path to the server upload api
 * option.method: POST
 *
 * option.verbose: true: don't log anything, false: log will be shown
 * option.file: the filepath to upload
 * option.progress: callback that gets called when a progess happens
 * option.error: error callback
 * requestObj - a request object default: require('request');
 * @returns {promise|*|Q.promise}
 */
function HttpClient(options) { 

    // default: true -> do not log
    var verbose = true;
    var callbackCalled = false;

     

    // configure the options
    options = options || {};

    // do set headers
    options.headers = options.headers || {};
    // configure verbose
    verbose = options.verbose || verbose;
    // delete to be require option ready
    delete options.verbose;

    // delete to be require option ready    
    if (options.file) {
        filePath = path.normalize(options.file.data.path);
        var fileName = path.basename(filePath);         
    }
    var progress = options.progress || function (chunk) {
        log('uploaded', chunk);
    };
    var error = options.error || function (e) {
        log('problem with request: ' + e.message);
    }; 
    
    /**
     * Call error callback only once
     * If an async call throws an error after an error accoures do not fire it.
     * @param arguments
     */
    var errorCallback = function (arguments) {
        if (!callbackCalled) {
            callbackCalled = true;
            error(arguments);
        }
    };

    /**
     * Use console.log with verbose option.
     */
    var log = function () {
        if (!verbose) {
            console.log.apply(console, arguments);
        }
    }; 

    // random string
    var boundaryKey = Math.random().toString(16); 


    // formdata content
    var content = '';
 
    // the header for the one and only part (need to use CRLF here) 
    var urlInfo = require('url').parse(options.url);

    var httpOptions = {
        host:urlInfo.hostname,
        port:urlInfo.port,
        method:'POST',
        path:urlInfo.path,//上传服务路径
        headers:{
            'User-Agent':'Mozilla/5.0 (Windows NT 6.2; WOW64) miniClient/0.19.5 AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.93 Safari/537.36',
            'Content-Type':'multipart/form-data; boundary=' + boundaryKey,
            'Connection':'keep-alive'
        }
    };
    var r = require('http').request(httpOptions,function(res){
        res.setEncoding('utf8');              
        res.on('data', function (chunk) {
            console.log(chunk.toString());
        });
        res.on('end',function(){
            if(res.statusCode==200){
                options.success();
            }else{ 
                options.error(res.statusMessage);
            }                       
        });
    });
    if (options.file) {
        try {
            content += '--' + boundaryKey + '\r\n';
            content += 'Content-Type: application/octet-stream\r\n';
            content += 'Content-Disposition: form-data; name="file"; filename="' + fileName + '"\r\n' + 'Content-Transfer-Encoding: binary\r\n\r\n';            
             // write the content
            r.write(content);

            r.on('error', function (e) {
                log('error', e);
                errorCallback(e); 
            });

            var fileStream = options.file.data;

            fileStream.on('error', function (e) {
                log('error on filestream', e);
                errorCallback(e);

            });

            fileStream.on('end', function () {
                // mark the end of the one and only part
                r.end('\r\n--' + boundaryKey + '--');
            });

            fileStream.on('data', function (data) {
                // mark the end of the one and only part
                progress(data.length);
            });

            // set "end" to false in the options so .end() isn't called on the request
            fileStream.pipe(r, { end: false });
        } catch (e) {
            log('error', e);
            errorCallback(e);

        }
    }

}

// API
module.exports = HttpClient;