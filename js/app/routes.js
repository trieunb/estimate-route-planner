angular
    .module('Erp')
    .config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
        $routeProvider.
            when('/', {
               templateUrl: ERPApp.templatesPath + 'dashboard.html?version=' + ERPApp.version
            }).
            when('/new-job-request', {
               templateUrl: ERPApp.templatesPath + 'job-request/add.html?version=' + ERPApp.version
            }).
            when('/edit-job-request/:id', {
               templateUrl: ERPApp.templatesPath + 'job-request/edit.html?version=' + ERPApp.version
            }).
            when('/job-requests', {
               templateUrl: ERPApp.templatesPath + 'job-request/list.html?version=' + ERPApp.version
            }).
            when('/referral-routes', {
               templateUrl: ERPApp.templatesPath + 'referral-route/list.html?version=' + ERPApp.version
            }).
            when('/new-referral-route', {
               templateUrl: ERPApp.templatesPath + 'referral-route/add.html?version=' + ERPApp.version
            }).
            when('/edit-referral-route/:id', {
               templateUrl: ERPApp.templatesPath + 'referral-route/edit.html?version=' + ERPApp.version
            }).
            when('/new-estimate', {
               templateUrl: ERPApp.templatesPath + 'estimate/add.html?version=' + ERPApp.version
            }).
            when('/edit-estimate/:id', {
               templateUrl: ERPApp.templatesPath + 'estimate/edit.html?version=' + ERPApp.version
            }).
            when('/estimates', {
               templateUrl : ERPApp.templatesPath + 'estimate/list.html?version=' + ERPApp.version
            }).
            when('/estimates/page/:pageNumber', {
               templateUrl : ERPApp.templatesPath + 'estimate/list.html?version=' + ERPApp.version
            }).
            when('/crew-routes', {
               templateUrl: ERPApp.templatesPath + 'crew-route/list.html?version=' + ERPApp.version
            }).
            when('/new-crew-route', {
               templateUrl: ERPApp.templatesPath + 'crew-route/add.html?version=' + ERPApp.version
            }).
            when('/edit-crew-route/:id', {
               templateUrl: ERPApp.templatesPath + 'crew-route/edit.html?version=' + ERPApp.version
            }).
            when('/crew-route/:id/work-order', {
               templateUrl: ERPApp.templatesPath + 'crew-route/work-order.html?version=' + ERPApp.version
            }).
            when('/settings', {
               templateUrl: ERPApp.templatesPath + 'settings.html?version=' + ERPApp.version
            }).
            when('/company-info', {
               templateUrl: ERPApp.templatesPath + 'company-info.html?version=' + ERPApp.version
            }).
            when('/quickbooks-sync', {
               templateUrl: ERPApp.templatesPath + 'quickbooks-sync.html?version=' + ERPApp.version
            }).
            when('/not-authorized', {
               templateUrl: ERPApp.templatesPath + 'not-authorized.html?version=' + ERPApp.version
            }).
            when('/not-found', {
               templateUrl: ERPApp.templatesPath + 'not-found.html?version=' + ERPApp.version
            }).
            otherwise({
               templateUrl: ERPApp.templatesPath + 'not-found.html?version=' + ERPApp.version
            });
}]);
