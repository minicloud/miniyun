Array.prototype.remove = function(index) {
    for (var i = 0; i < this.length; i++) {
        var item = this[i]
        if (item.index === index) {
            this.splice(i, 1)
            return true
        }
    }
    return false
}

function MiniSecUploader(callback) {
    this.queue = []
    this.maxThread = 2
    this.callback = callback
    var uploader = this
    setInterval(function() {
        uploader.__consumer()
    }, 100)
}
/**
消费者 根据既定的任务数与状态值决定是否开启新任务
*/
MiniSecUploader.prototype.__consumer = function() {
        for (var i = 0; i < this.maxThread; i++) {
            var queueLength = this.queue.length
            if (i < queueLength) {
                var file = this.queue[i]
                if (file.secStatus === 'in') {
                    this.__start(file)
                }
            }
        }
    }
    /**
    生产者 添加文件，新任务放入队列中
    */
MiniSecUploader.prototype.addFile = function(file, secContext) {
        file.secStatus = 'in'
        file.sec_context = secContext
        this.queue.push(file)
    }
    /**
    暂停某个文件的上传
    */
MiniSecUploader.prototype.pause = function(index) {
        var file = this.__getFile(index)
        if (file) {
            if (file.secWorker) {
                file.secWorker.terminate()
            }
            file.secStatus = 'pause'
        }
    }
    /**
    查询指定index的文件对象
    */
MiniSecUploader.prototype.__getFile = function(index) {
    for (var i = 0; i < this.queue.length; i++) {
        var item = this.queue[i]
        if (item.index === index) {
            return item
        }
    }
    return null
}
MiniSecUploader.prototype.__start = function(file) {
        //更改状态
        file.secStatus = 'doing'
        var worker = new Worker(file.host + 'static/js/md5-worker.js')
        worker.addEventListener('message', this.__message())
        file.secWorker = worker
            //采用分块方式，计算文件hash值
        this.__md5File(file, worker)

    }
    /**
        异步计算MD5值返回的结果
    */
MiniSecUploader.prototype.__message = function() {
        var self = this
        return function(event) {
            if (event.data.result) {
                event.data.status=200
                var fileIndex = event.data.block.index
                var file = self.__getFile(fileIndex)
                var hash = event.data.result.toUpperCase()
                    //移除任务区
                self.queue.remove(fileIndex)
                    //向服务器发送秒传接口
                var xhttp = new XMLHttpRequest()
                xhttp.open('POST', file.sec_context.url, true)
                xhttp.onreadystatechange = function() {
                    if (xhttp.readyState == 4) {
                        //从队列删除该文件，开启下一个文件的秒传任务
                        self.queue.remove(fileIndex)
                        if (xhttp.status == 200) {
                            var result = JSON.parse(xhttp.responseText)
                            if (result.path) {
                                //秒传成功
                                self.callback(event)
                            }
                        }else{
                            //秒传失败，而且没有权限
                            event.data.status=409
                            self.callback(event)
                        }
                    }
                }
                var form = new FormData()
                form.append('path', file.sec_context.path)
                form.append('hash', hash)
                xhttp.send(form)
            }
        }
    }
    /**
    计算文件的md5值
    */
MiniSecUploader.prototype.__md5File = function(file, worker) {
    var block, reader, blob
    var handleLoadBlock = function(event) {
        worker.postMessage({
            'message': event.target.result,
            'block': block
        })
    }
    var handleHashBlock = function(event) {
        if (block.end !== file.size) {
            block.start += buffer_size
            block.end += buffer_size

            if (block.end > file.size) {
                block.end = file.size
            }
            reader = new FileReader()
            reader.onload = handleLoadBlock
            blob = file.slice(block.start, block.end)

            reader.readAsArrayBuffer(blob)
        }
    }
    var buffer_size = 1024 * 1024
    block = {
        'file_size': file.size,
        'start': 0,
        'file': file,
        'index': file.index
    }

    block.end = buffer_size > file.size ? file.size : buffer_size
    worker.addEventListener('message', handleHashBlock)
    reader = new FileReader()
    reader.onload = handleLoadBlock
    blob = file.slice(block.start, block.end)
    reader.readAsArrayBuffer(blob)
}
