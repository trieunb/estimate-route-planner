angular
    .module('Erp')
    .controller('ListEstimateCtrl', ['$scope', '$rootScope', '$routeParams', 'estimateFactory', ListEstimateCtrl]);

function ListEstimateCtrl($scope, $rootScope, $routeParams, estimateFactory) {
    $rootScope.pageTitle = 'List Estimates';
    $rootScope.isBusy = true;
    $scope.estimates = {};
    $scope.selectedStatus = '';
    $scope.sendMailData = {};

    estimateFactory.list()
        .success(function(response) {
            if (response) {
                $scope.estimates = response;
            }
        })
        .then(function() {
            $rootScope.isBusy = false;
        })

    $scope.filterStatuses = [
        {
            value: '',
            label: 'All'
        },
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

    $scope.openSendMailModal = function(estimate) {
        $scope.showModal = true;
        $scope.sendMailData.id = estimate.id;
        $scope.sendMailData.to = estimate.email;
        $scope.sendMailData.subject = 'Estimate from ' + $rootScope.companyInfo.name;
        $scope.sendMailData.body = estimate.estimate_footer;
        $scope.sendEmailForm.$setPristine();
    };

    $scope.sendMailEstimate = function() {
        $scope.showModal = false;
        $rootScope.isBusy = true;
        estimateFactory.sendMail($scope.sendMailData)
            .success(function(response){
                if (response.success) {
                    toastr['success'](response.message);

                } else {
                    toastr['error'](response.message);
                }
            })
            .then(function() {
                $rootScope.isBusy = false;
            });
    };
}
