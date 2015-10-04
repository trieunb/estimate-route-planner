angular
    .module('Erp')
    .config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
        $routeProvider.
            when('/', {
               templateUrl: ERPApp.templatesPath + 'dashboard.html'
            }).
            when('/new-job-request', {
               templateUrl: ERPApp.templatesPath + 'job-request/add.html'
            }).
            when('/edit-job-request/:id', {
               templateUrl: ERPApp.templatesPath + 'job-request/edit.html'
            }).
            when('/job-requests', {
               templateUrl: ERPApp.templatesPath + 'job-request/list.html'
            }).
            when('/estimate-routes', {
               templateUrl: ERPApp.templatesPath + 'estimate-route/list.html'
            }).
            when('/new-estimate-route', {
               templateUrl: ERPApp.templatesPath + 'estimate-route/add.html'
            }).
            when('/edit-estimate-route/:id', {
               templateUrl: ERPApp.templatesPath + 'estimate-route/edit.html'
            }).
            when('/new-estimate', {
               templateUrl: ERPApp.templatesPath + 'estimate/add.html'
            }).
            when('/edit-estimate/:id', {
               templateUrl: ERPApp.templatesPath + 'estimate/edit.html'
            }).
            when('/estimates', {
               templateUrl : ERPApp.templatesPath + 'estimate/list.html'
            }).
            when('/estimates/page/:pageNumber', {
               templateUrl : ERPApp.templatesPath + 'estimate/list.html'
            }).
            when('/crew-routes', {
               templateUrl: ERPApp.templatesPath + 'crew-route/list.html'
            }).
            when('/new-crew-route', {
               templateUrl: ERPApp.templatesPath + 'crew-route/add.html'
            }).
            when('/edit-crew-route/:id', {
               templateUrl: ERPApp.templatesPath + 'crew-route/edit.html'
            }).
            when('/crew-route/:id/work-order', {
               templateUrl: ERPApp.templatesPath + 'crew-route/work-order.html'
            }).
            when('/settings', {
               templateUrl: ERPApp.templatesPath + 'settings.html'
            }).
            when('/company-info', {
               templateUrl: ERPApp.templatesPath + 'company-info.html'
            }).
            when('/quickbooks-sync', {
               templateUrl: ERPApp.templatesPath + 'quickbooks-sync.html'
            }).
            when('/not-authorized', {
               templateUrl: ERPApp.templatesPath + 'not-authorized.html'
            }).
            when('/not-found', {
               templateUrl: ERPApp.templatesPath + 'not-found.html'
            }).
            otherwise({
               templateUrl: ERPApp.templatesPath + 'not-found.html'
            });
}]);
