/**
 * Convert an image
 * to a base64 url
 * @param  {String}   url
 * @param  {Function} callback
 * @param  {String}   [outputFormat=image/png]
 */
function convertImgToBase64URL(url, callback, outputFormat){
    var img = new Image();
    img.crossOrigin = 'Anonymous';
    img.onload = function(){
        var canvas = document.createElement('CANVAS'),
        ctx = canvas.getContext('2d'), dataURL;
        canvas.height = this.height;
        canvas.width = this.width;
        ctx.drawImage(this, 0, 0);
        dataURL = canvas.toDataURL(outputFormat);
        callback(dataURL);
        canvas = null;
    };
    img.src = url;
}

angular
    .module('Erp', [
        'ngRoute', 'selectize', 'ngSanitize', 'ngAnimate',
        'ngSignaturePad', 'as.sortable', 'ngDropzone',
        'uiGmapgoogle-maps', 'dndLists', 'ngBootbox', 'ui.bootstrap',
        'ngMessages', 'yaru22.angular-timeago'
    ]).run(function($rootScope, dataFactory, $location) {
        $rootScope.pageTitle = 'Estimate And Route Planner Pro';
        $rootScope.isBusy = true;
        $rootScope.baseAPIPath = ERPApp.baseAPIPath;
        $rootScope.baseERPPluginUrl = ERPApp.baseERPPluginUrl;
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
        // Get some data at start
        dataFactory.getSessionData().
            success(function(data, status, headers, config) {
                $rootScope.currentUser      = data.currentUser;
                $rootScope.preferences      = data.preferences;
                $rootScope.companyInfo      = data.companyInfo;
                $rootScope.productServices  = data.productServices;
            })
            .error(function() {
                $rootScope.isBusy = false;
            })
            .then(function() {
                $rootScope.isBusy = false;
            });

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
    })
    .config(function($httpProvider, uiGmapGoogleMapApiProvider) {
        $httpProvider.defaults.headers.common["X-Requested-With"] = 'XMLHttpRequest';
        $httpProvider.defaults.headers.post['Content-Type']
            = 'application/x-www-form-urlencoded; charset=UTF-8';
        $httpProvider.interceptors.push(['$q', function($q) {
            return {
                request: function(config) {
                    if (config.data && (typeof config.data === 'object') && !(config.data instanceof FormData)) {
                        config.data = jQuery.param(config.data);
                    }
                    return config || $q.when(config);
                }
            };
        }]);
        uiGmapGoogleMapApiProvider.configure({
            key: '123456',
            v: '3.17',
            libraries: 'weather,geometry,visualization'
        });
    });

// Manually start up the app
jQuery(document).ready(function(e) {
    /* Toastr plugin config */
    toastr.options = {
      "closeButton": true,
      "debug": false,
      "positionClass": "toast-bottom-right",
      "timeOut": "5000"
    }
    angular.bootstrap(document, ['Erp']);
    // Temporary fix dashboard menu href hash
    if(jQuery('body').hasClass('toplevel_page_estimate-route-planner')) {
        // var menuContainerSelector = 'li.' + ERPApp.navigationClass;
        // jQuery(menuContainerSelector + ' > a').attr('href', '#/');
        // jQuery(menuContainerSelector + ' ul li > a:first').attr('href', '#/');
    }
});
