angular
    .module('Erp')
    .config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
        $routeProvider.
            when('/', {
               templateUrl: ERPApp.templatesPath + 'dashboard.html?version=' + ERPApp.version
            }).
            when('/new-referral', {
               templateUrl: ERPApp.templatesPath + 'referral/add.html?version=' + ERPApp.version
            }).
            when('/edit-referral/:id', {
               templateUrl: ERPApp.templatesPath + 'referral/edit.html?version=' + ERPApp.version
            }).
            when('/referrals', {
               templateUrl: ERPApp.templatesPath + 'referral/list.html?version=' + ERPApp.version
            }).
            when('/print-referral/:id', {
               templateUrl: ERPApp.templatesPath + 'print-referral.html?version=' + ERPApp.version
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
            when('/estimate-routes', {
               templateUrl: ERPApp.templatesPath + 'estimate-route/list.html?version=' + ERPApp.version
            }).
            when('/new-estimate-route', {
               templateUrl: ERPApp.templatesPath + 'estimate-route/add.html?version=' + ERPApp.version
            }).
            when('/edit-estimate-route/:id', {
               templateUrl: ERPApp.templatesPath + 'estimate-route/edit.html?version=' + ERPApp.version
            }).
            when('/estimate-route/:id/work-order', {
               templateUrl: ERPApp.templatesPath + 'estimate-route/work-order.html?version=' + ERPApp.version
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
