angular
    .module('Erp')
    .factory('dataFactory', ['$http', dataFactory]);

function dataFactory($http) {
    return {
        getSharedData: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: { _do: 'getSharedData' }
            });
        }
    };
}

angular
    .module('Erp')
    .factory('erpHttpInterceptor', ['$q', '$rootScope', '$injector',
        function ($q, $rootScope, $injector) {
            $rootScope.http = null;
            return {
                'request': function (config) {
                    $rootScope.loadingOn();
                    if (config.data && (typeof config.data === 'object') && !(config.data instanceof FormData)) {
                        config.data = jQuery.param(config.data);
                    }
                    return config || $q.when(config);
                },
                'requestError': function (rejection) {
                    $rootScope.http = $rootScope.http || $injector.get('$http');
                    if ($rootScope.http.pendingRequests.length < 1) {
                        $rootScope.loadingOff();
                    }
                    return $q.reject(rejection);
                },
                'response': function (response) {
                    $rootScope.http = $rootScope.http || $injector.get('$http');
                    if ($rootScope.http.pendingRequests.length < 1) {
                        $rootScope.loadingOff();
                    }
                    return response || $q.when(response);
                },
                'responseError': function (rejection) {
                    $rootScope.http = $rootScope.http || $injector.get('$http');
                    if ($rootScope.http.pendingRequests.length < 1) {
                        $rootScope.loadingOff();
                    }
                    if (rejection.status == 404) {
                        $rootScope.$broadcast('notFound');
                    }
                    return $q.reject(rejection);
                }
            };
        }
    ]);
