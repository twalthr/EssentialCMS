// web worker for creating checksums of files
self.onmessage = function(e) {
	importScripts('spark-md5.min.js');

	if (e.data.type == 'start') {
		createChecksum(e.data.file);
	}
};

// creates a MD5 checksum of a file object
function createChecksum(file) {
	var blobSlice = File.prototype.slice ||
		File.prototype.mozSlice ||
		File.prototype.webkitSlice ||
		File.prototype.msSlice;
	var chunkSize = 2097152; // read in chunks of 2MB
	var chunks = Math.ceil(file.size / chunkSize);
	var currentChunk = 0;
	var spark = new SparkMD5.ArrayBuffer();
	var fileReader = new FileReader();

	if (typeof blobSlice == 'undefined' || typeof fileReader == 'undefined') {
		postMessage({
			'type': 'error',
			'reason': 'unsupported'
		});
	}

	fileReader.onload = function (e) {
		postMessage({
			'type': 'status',
			'chunk': (currentChunk + 1),
			'of': chunks
		});
		spark.append(e.target.result); // append array buffer
		currentChunk++;

		if (currentChunk < chunks) {
			loadNext();
		} else {
			postMessage({
				'type': 'done',
				'checksum': spark.end()
			});
		}
	};

	fileReader.onerror = function () {
		postMessage({
			'type': 'error',
			'reason': 'unknown'
		});
	};

	function loadNext() {
		var start = currentChunk * chunkSize;
		var end = ((start + chunkSize) >= file.size) ? file.size : start + chunkSize;

		fileReader.readAsArrayBuffer(blobSlice.call(file, start, end));
	}

	loadNext();
}