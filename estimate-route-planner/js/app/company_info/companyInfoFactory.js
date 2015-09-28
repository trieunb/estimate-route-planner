angular
    .module('Erp')
    .factory('companyInfoFactory', ['$http', companyInfoFactory]);

function companyInfoFactory($http) {
    return {
        save: function(info) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'updateCompanyInfo',
                data: info
            });
        },
        get: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: {_do: 'getCompanyInfo'}
            });
        },
        uploadLogo: function(photoFile) {
            var fd = new FormData();
            fd.append("file", photoFile);
            fd.append("_do", 'uploadLogo');
            return $http.post(ERPApp.baseAPIPath, fd,
            {
                withCredentials: true,
                transformRequest: angular.identity,
                headers: {'Content-Type':  undefined}
            });
        }
    };
}
