'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var serverRequest = function () {
    function serverRequest(remoteBaseURL) {
        _classCallCheck(this, serverRequest);

        this.SERVER_URL = remoteBaseURL.replace(/\/?$/, '/');
    }
    //------------------------------------------------------------
    _createClass(serverRequest, [{
        key: 'postToServer',
        value: function postToServer(postValue, subDirectory) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                axios.post(_this.SERVER_URL + subDirectory, postValue, { headers: { 'Content-Type': 'application/json' } }).then(function (response) {
                    resolve(response.data);
                }, function (response) {
                    //Failed server response
                    reject();
                });
            });
        }
        //------------------------------------------------------------

    },{
        key: 'postFileToServer',
        value: function postFileToServer(formData, subDirectory) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                axios.post(_this.SERVER_URL + subDirectory, formData, { headers: { 'Content-Type': 'multipart/form-data' } }).then(function (response) {
                    resolve(response.data);
                }, function (response) {
                    //Failed server response
                    reject();
                });
            });
        }
        //------------------------------------------------------------

    },{
        key: 'getFileFromServer',
        value: function getFileFromServer(urlPath) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                axios({
                    method: 'get',
                    url: urlPath,
                    responseType: 'blob'
                }).then(function(response) {
                    let blob = new Blob([response.data], { type: 'application/*' });
                    resolve(blob);
                });
            });
        }
        //------------------------------------------------------------

    }]);

    return serverRequest;
}();