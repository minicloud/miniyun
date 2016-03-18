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
Date.prototype.format = function(fmt) { //author: meizz   
    var o = {
        "M+": this.getMonth() + 1, //月份   
        "d+": this.getDate(), //日   
        "h+": this.getHours(), //小时   
        "m+": this.getMinutes(), //分   
        "s+": this.getSeconds(), //秒   
        "q+": Math.floor((this.getMonth() + 3) / 3), //季度   
        "S": this.getMilliseconds() //毫秒   
    };
    if (/(y+)/.test(fmt))
        fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
    for (var k in o)
        if (new RegExp("(" + k + ")").test(fmt))
            fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
    return fmt;
}



function MiniUploader(callback) {
    this.queue = []
    this.maxThread = 5
    this.callback = callback
    var uploader = this
        //秒传
    this.secUploader = new MiniSecUploader(function(event) {
        uploader.__secCallback(event)
    })
    setInterval(function() {
        uploader.__consumer()
    }, 100)
}
/**
消费者 根据既定的任务数与状态值决定是否开启新任务
*/
MiniUploader.prototype.__consumer = function() {
        for (var i = 0; i < this.maxThread; i++) {
            var queueLength = this.queue.length
            if (i < queueLength) {
                var file = this.queue[i]
                if (file.status === 'in') {
                    this.__start(file)
                }
            }
        }
    }
    /**
    生产者 添加文件，新任务放入队列中
    */
MiniUploader.prototype.addFile = function(host, dir, file, fileIndex) {
        file.host = host
        file.status = 'in'
        file.index = fileIndex
        file.newName = file.name
        if (file.name === 'image.jpeg') {
            var name = new Date().format("yyyy-MM-dd hh:mm:ss.S")
            name += " " + fileIndex + ".jpeg"
            file.newName = name
        }
        var last = dir[dir.length - 1]
        if (last !== '/') {
            dir += '/'
        }
        file.remotePath = dir + file.newName
        if (file.webkitRelativePath) {
            file.remotePath = dir + file.webkitRelativePath
        }
        this.queue.push(file)
        return file
    }
    /**
        暂停某个文件的上传
        */
MiniUploader.prototype.pause = function(index) {
        var file = this.__getFile(index)
        if (file) {
            if (file.worker) {
                file.worker.terminate()
            }
            //IE浏览器可以停止上传
            if (file.ieUploader) {
                file.ieUploader.stop()
            }
            file.status = 'pause'
                //停止hash值计算任务
            this.secUploader.pause(index)
        }
    }
    /**
        重启某个文件的上传
        */
MiniUploader.prototype.restart = function(index) {
        var file = this.__getFile(index)
        if (file) {
            file.status = 'in'
        }
    }
    /**
            查询指定index的文件对象
            */
MiniUploader.prototype.__getFile = function(index) {
        for (var i = 0; i < this.queue.length; i++) {
            var item = this.queue[i]
            if (item.index === index) {
                return item
            }
        }
        return null
    }
    /**
        释放资源
        */
MiniUploader.prototype.__cleanTask = function(index) {
        var file = this.__getFile(index)
            //移除任务区
        this.queue.remove(index)
            //终止文件上传任务
        if (file) {
            if (file.worker) {
                file.worker.terminate()
            }
            //IE浏览器可以停止上传
            if (file.ieUploader) {
                file.ieUploader.stop()
            }

        }
    }
    /**
    接受worker的消息，如果上传完成，队列移除该文件，消费者会开启新任务。同时把消息回调给前端
    */
MiniUploader.prototype.__message = function() {
        var self = this
        return function(event) {
            var action = event.data.action
            if (action === 'success') {
                //任务执行完成，把queue对应的file拿掉，以便进行下一个任务
                var index = event.data.index
                self.__cleanTask(index)
            }
            //将上下文反馈给前端
            self.callback(event)
        }
    }
    /**
    IE浏览器下采用回掉方式显示进度信息
    */
MiniUploader.prototype.__ieMessage = function(data) {
        var action = data.action
        if (action === 'success') {
            //任务执行完成，把queue对应的file拿掉，以便进行下一个任务
            var index = data.index
            this.__cleanTask(index)
        }
        //将上下文反馈给前端
        this.callback({ data: data })
    }
    /**
    接受秒传worker的消息，如果上传完成，队列移除该文件，消费者会开启新任务。同时把消息回调给前端
    */
MiniUploader.prototype.__secCallback = function(event) {
        var index = event.data.block.index
        event.data.action = 'sec'
        event.data.index = index
            //释放资源
        this.__cleanTask(index)
            //秒传成功
        this.callback(event)
    }
    /**
        判断是否是IE浏览器
        */
MiniUploader.prototype.__isIE = function() {
    var userAgent = navigator.userAgent.toLowerCase()
    return (userAgent.indexOf('trident') > 0 || userAgent.indexOf('edge') > 0) ? true : false
}
MiniUploader.prototype.__start = function(file) {
    file.status = 'doing'
    var self = this
    var uploaderHelper = new MiniUploaderHelper()
        //获得上传策略
    uploaderHelper.start(file.host, file.remotePath, function(error, data) {
        if (!error) {
            //秒传
            self.secUploader.addFile(file, data.sec_context)
            if (self.__isIE()) {
                //IE浏览器下formData在WebWorker不可用，这里单独出来了
                var ieUploader = new IEUploader()
                ieUploader.upload(file, data.upload_context, function(data) {
                    self.__ieMessage(data)
                })
                file.ieUploader = ieUploader
            } else {
                //Chrome/FF浏览器采用webworker方式上传文件
                var worker = new Worker(file.host + 'static/js/uploader-worker.js')
                worker.addEventListener('message', self.__message())
                worker.postMessage({
                        upload_context: data.upload_context,
                        file: file,
                        index: file.index,
                        name: file.newName
                    })
                    //更改状态 
                file.worker = worker
            }

        }
    })

}

function IEUploader() {
    this.xhr = new XMLHttpRequest()
}
IEUploader.prototype.stop = function() {
    this.xhr.abort()
}
IEUploader.prototype.upload = function(file, uploadContext, callback) {
    var self = this
    var index = file.index
    var fileName = file.newName
    var start = new Date()
    var xhr = this.xhr
    xhr.open('POST', uploadContext.host, true)
    xhr.onload = function(e) {
        if (xhr.readyState == 4) {
            console.log(xhr.responseText)
            if (xhr.status == 200) {
                callback({ action: 'success', file: file, index: index })
            }
        }
    }
    var onProgress = function(e) {
            if (e.lengthComputable) {
                var end = new Date()
                var diff = end.getTime() - start.getTime()
                var speed = e.loaded * 1000 / diff
                var progress = (e.loaded / e.total) * 100
                progress = Math.round(progress * 100) / 100
                if (e.loaded === e.total) {
                    progress = 100
                }
                callback({ action: 'uploading', file: file, index: index, progress: progress, speed: self.formateSpeed(speed) })
            }
        }
        // xhr.onprogress = onProgress
    xhr.upload.onprogress = onProgress
        //把外部参数形成FormData
    var form = new FormData()
    form.append('name', fileName)
    form.append('key', uploadContext.path)
    form.append('policy', uploadContext.policy)
    form.append('OSSAccessKeyId', uploadContext.accessid)
    form.append('callback', uploadContext.callback)
    form.append('success_action_status', '200')
    form.append('signature', uploadContext.signature)
    form.append('Content-Disposition', 'attachment;filename=' + fileName + ';' + 'filename=' + encodeURI(fileName) + ';filename*=UTF-8\'\'' + encodeURI(fileName))
    form.append('file', file)
    xhr.send(form)
}
IEUploader.prototype.formateSpeed = function(speed) {
    if (speed > 1024 * 1024) {
        speed = speed / (1024 * 1024)
        speed = Math.round(speed * 10) / 10
        return speed + 'M/s'
    } else if (speed > 1024) {
        speed = speed / (1024)
        speed = Math.round(speed * 10) / 10
        return speed + 'KB/s'
    } else {
        return speed + 'B/s'
    }
}
