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
        delete: function(id) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'deleteAttachment',
                data: {id: id}
            });
        }
    };
}
