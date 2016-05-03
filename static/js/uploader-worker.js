self.formateSpeed = function(speed) {
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
self.addEventListener('message', function(event) {
    var file = event.data.file
    var index = event.data.index
    var uploadContext = event.data.upload_context
    var start = new Date()
    var xhr = new XMLHttpRequest()
    xhr.open('POST', uploadContext.host, true)
    xhr.onload = function(e) {
        if (xhr.readyState == 4) {
            if (xhr.status == 200) {
                self.postMessage({ action: 'success', status: 200, file: file, index: index })
            } else {
                //共享目录下无上传权限
                self.postMessage({ action: 'success', status: 409, file: file, index: index })
            }
        }
    }
    var onprogress = function(e) {
        if (e.lengthComputable) {
            var end = new Date()
            var diff = end.getTime() - start.getTime()
            var speed = e.loaded * 1000 / diff
            var progress = (e.loaded / e.total) * 100
            progress = Math.round(progress * 100) / 100
            if (e.loaded === e.total) {
                progress = 100
            }
            self.postMessage({ action: 'uploading', file: file, index: index, progress: progress, speed: self.formateSpeed(speed) })
        }
    }
    xhr.onprogress = onprogress
    xhr.upload.onprogress = onprogress
        //把外部参数形成FormData
    var fileName = event.data.name
    var form = new FormData()
    form.append('name', fileName)
    form.append('key', uploadContext.path)
    form.append('policy', uploadContext.policy)
    form.append('OSSAccessKeyId', uploadContext.accessid)
    form.append('callback', uploadContext.callback)
    form.append('vedio_convert_start_callback', uploadContext.vedio_convert_start_callback)
    form.append('vedio_convert_end_callback', uploadContext.vedio_convert_end_callback)
    form.append('doc_convert_start_callback', uploadContext.doc_convert_start_callback) 
    form.append('doc_convert_end_callback', uploadContext.doc_convert_end_callback) 

    form.append('success_action_status', '200')
    form.append('signature', uploadContext.signature)
    form.append('Content-Disposition', 'attachment;filename=' + fileName + ';' + 'filename=' + encodeURI(fileName) + ';filename*=UTF-8\'\'' + encodeURI(fileName))
    form.append('file', file)
    xhr.send(form)
})
