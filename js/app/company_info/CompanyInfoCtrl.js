angular
    .module('Erp')
    .controller('CompanyInfoCtrl', [
        '$scope',
        '$rootScope',
        'companyInfoFactory',
        'sharedData',
        CompanyInfoCtrl
    ]);

function CompanyInfoCtrl($scope, $rootScope, companyInfoFactory, sharedData) {
    $scope.setPageTitle('Company Info');
    $scope.info = {};

    companyInfoFactory.get()
        .success(function(response) {
            $scope.info = response;
        });

    $scope.submitForm = function() {
        companyInfoFactory.save($scope.info)
            .success(function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    // Update global data of company info
                    sharedData.companyInfo = response.data;
                } else {
                    var msg = response.message || 'An error occurred while saving info';
                    toastr.error(msg);
                }
            });
    };

    $scope.imageChange = function(element) {
        var photofile = element.files[0];
        if ('undefined' !== typeof(photofile)) {
            $scope.photofile = photofile;
            var reader = new FileReader();
            reader.onload = function(e) {
                angular.element('#logo_upload_img').attr('src', e.target.result);
            };
            reader.readAsDataURL(photofile);
        } else {
            $scope.photofile = null;
        }
    };

    $scope.uploadLogo = function() {
        if ($scope.photofile !== null) {
            companyInfoFactory.uploadLogo($scope.photofile)
                .success(function(response) {
                    if (response.success) {
                        $scope.photofile = null;
                        toastr.success(response.message);
                        angular.element('#logo-form').get(0).reset();
                        sharedData.companyInfo = response.data;
                    } else {
                        var msg = response.message || 'An error occurred while uploading logo';
                        toastr.error(msg);
                    }
                });
        }
    };
}
