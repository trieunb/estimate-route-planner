var ERPApp = ERPApp || {};

/* Toastr plugin config */
toastr.options = {
  "closeButton": true,
  "debug": false,
  "positionClass": "toast-bottom-right",
  "timeOut": "7000"
};

angular
    .module('Erp',
        [
            'ngRoute',
            'selectize',
            'ngSanitize',
            'ngAnimate',
            'as.sortable',
            'ngDropzone',
            'uiGmapgoogle-maps',
            'ngBootbox',
            'ui.bootstrap',
            'ngMessages',
            'yaru22.angular-timeago',
            'ui.tree'
        ]
    ).run(['$rootScope', 'dataFactory', 'sharedData', '$location', 'userPermission',
        function($rootScope, dataFactory, sharedData, $location, userPermission) {
        $rootScope.pageTitle = 'ER Planner Pro';
        $rootScope.isBusy = true;
        $rootScope.baseAPIPath = ERPApp.baseAPIPath;
        $rootScope.baseERPPluginUrl = ERPApp.baseERPPluginUrl;
        $rootScope.templatesPath = ERPApp.templatesPath;
        $rootScope.pluginName = ERPApp.pluginName;

        $rootScope.setPageTitle = function(title) {
            $rootScope.pageTitle = title;
            angular.element('title').text($rootScope.pluginName + ' - ' + title);
        };

        $rootScope.loadingOn = function() {
            $rootScope.isBusy = true;
        };

        $rootScope.loadingOff = function() {
            $rootScope.isBusy = false;
        };

        $rootScope.hasCap = function(cap) {
            return userPermission.hasCap(cap);
        };

        $rootScope.referralStatuses = [
            {
                value: 'Pending',
                label: 'Pending'
            },
            {
                value: 'Assigned',
                label: 'Assigned'
            },
            {
                value: 'Completed',
                label: 'Completed'
            }
        ];
        $rootScope.routeStatuses = [
            {
                value: 'Pending',
                label: 'Pending'
            },
            {
                value: 'Assigned',
                label: 'Assigned'
            },
            {
                value: 'Completed',
                label: 'Completed'
            }
        ];
        $rootScope.estimateStatuses = [
            {
                value: 'Pending',
                label: 'Pending'
            },
            {
                value: 'Accepted',
                label: 'Accepted'
            },
            {
                value: 'Completed',
                label: 'Completed/WFI' // NOTE: Quickbooks still shows Accepted
            },
            {
                value: 'Closed',
                label: 'Closed'
            },
            {
                value: 'Rejected',
                label: 'Rejected'
            }
        ];

        $rootScope.$on('notAuthorized', function() {
            $location.path('/not-authorized');
        });

        $rootScope.$on('notFound', function() {
            $location.path('/not-found');
        });

        // Get some data at start
        $rootScope.loadSharedData = function() {
            dataFactory.getSharedData()
                .success(function(response) {
                    sharedData.companyInfo = response.companyInfo;
                });
        };

        /* Listen to route change to update current menu item */
        $rootScope.$on("$routeChangeSuccess", function(event, current, prev) {
            var currentHash = $location.path().replace(/^\//, "#");
            if (currentHash == '#') {
                currentHash = '#/';
            }
            angular.element('li.' + ERPApp.navigationClass + ' a').each(function() {
                var el = angular.element(this);
                if (this.hash === currentHash) {
                    el.parent('li').addClass('current');
                } else {
                    el.parent('li').removeClass('current');
                }
            });
        });

        /* Authorize current user capibilities */
        $rootScope.$on("$routeChangeStart", function(event, current, prev) {
            var requestPath = $location.path();
            if (requestPath === '') {
                requestPath = '/';
            }
            if (!userPermission.canAccessTo(requestPath)) {
                event.preventDefault();
                $rootScope.$broadcast('notAuthorized');
            }
        });
    }]);

angular
    .module('Erp')
    .config(['$httpProvider', 'uiGmapGoogleMapApiProvider',
        function($httpProvider, uiGmapGoogleMapApiProvider) {
        $httpProvider.defaults.headers.common["X-Requested-With"] = 'XMLHttpRequest';
        $httpProvider.defaults.headers.post['Content-Type'] =
            'application/x-www-form-urlencoded; charset=UTF-8';
        $httpProvider.interceptors.push('erpHttpInterceptor');
        uiGmapGoogleMapApiProvider.configure({
            key: '',
            v: '3.17',
            libraries: 'geometry, visualization'
        });
    }]);

var initInjector = angular.injector(['ng']);
var $http = initInjector.get('$http');

$http.get(ERPApp.baseAPIPath, {
    params: { _do: 'getSharedData' }
})
.then(
    function(response) {
        angular.module('Erp')
            .constant('USER_CAPABILITIES', response.data.userData.capabilities)
            .constant('USER_ROLES', response.data.userData.roles)
            .value('sharedData',
                {
                    companyInfo: response.data.companyInfo,
                    currentUserName: response.data.userData.name,
                    lastSyncAt: response.data.lastSyncAt
                }
            );
        angular.element(document).ready(function() {
            angular.bootstrap(document, ['Erp']);
        });
    },
    function() {
        toastr.error("An error has occurred, could not start the app!");
    }
);
