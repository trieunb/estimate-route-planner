angular
    .module('Erp')
    .factory('attachmentUploader', ['$http', attachmentUploader]);

function attachmentUploader($http) {
    return {
        upload: function(estimate) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'uploadAttachment'
            });
        },
        destroy: function(id) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'deleteAttachment',
                data: {id: id}
            });
        }
    };
}
