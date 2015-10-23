angular
    .module('Erp')
    .controller('ListJobRequestCtrl', [
        '$scope',
        '$routeParams',
        'jobRequestFactory',
        'estimateRouteFactory',
        'erpLocalStorage',
        '$ngBootbox',
        ListJobRequestCtrl
    ]);

function ListJobRequestCtrl(
    $scope,
    $routeParams,
    jobRequestFactory,
    estimateRouteFactory,
    erpLocalStorage,
    $ngBootbox) {

    $scope.setPageTitle('Job Requests List');
    $scope.referrals = {};
    $scope.date = new Date();
    $scope.routes = [];
    $scope.showAssignModal = false;
    $scope.filter = {};
    $scope.total = 0;
    var currentPage = 1;
    if ('undefined' !== typeof($routeParams.pageNumber)) {
        currentPage = $routeParams.pageNumber;
    }
    $scope.currentPage = currentPage;

    // Load estimate route for quick assigned
    estimateRouteFactory.all()
        .success(function(responseData){
            $scope.routes = responseData;
        });

    // Load employees
    erpLocalStorage.getEmployees()
        .then(function(data) {
            $scope.employees = [];
            angular.copy(data, $scope.employees);
        });

    var paginate = function() {
        var query = {
            _do: 'getReferrals',
            page: $scope.currentPage,
            keyword: $scope.filter.keyword
        };
        jobRequestFactory.list(query)
            .success(function(response) {
                $scope.referrals = response.data;
                $scope.total = parseInt(response.total);
            });
    };

    $scope.pageChanged = function() {
        paginate();
    };

    $scope.searchReferral = function() {
        $scope.currentPage = 1;
        paginate();
    };

    $scope.clearSearch = function() {
        $scope.filter = {};
        paginate();
    };

    $scope.showModalUpdateStatus = function(referral) {
        $scope.currentReferral = {};
        $scope.currentReferral.id = referral.id;
        $scope.currentReferral.status = referral.new_status;
        if (referral.new_status == 'Assigned') {
            $scope.showAssignModal = true;
        } else {
            $ngBootbox.confirm("Do you want to save job request status?")
                .then(function() {
                    $scope.showAssignModal = false;
                    $scope.updateReferralStatus();
                    $scope.currentReferral = {};
                }, function() {
                    referral.new_status = referral.status;
                    $scope.currentReferral = {};
                    $scope.assignReferralForm.$setPristine();
                });
        }
    };

    $scope.cancelAssignReferral = function() {
        $scope.showAssignModal = false;
        // Reset new status value
        angular.forEach($scope.referrals, function(referral) {
            if (referral.id == $scope.currentReferral.id) {
                referral.new_status = referral.status;
                return;
            }
        });
        $scope.currentReferral = {};
        $scope.assignReferralForm.$setPristine();
    };

    $scope.updateReferralStatus = function() {
        if ($scope.currentReferral) {
            $scope.showAssignModal = false;
            var newStatus = $scope.currentReferral.status;
            var updateReferralId = $scope.currentReferral.id;
            jobRequestFactory.updateStatus($scope.currentReferral)
                .success(function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        // Remove completed referral from list
                        if (newStatus === 'Completed') {
                            angular.forEach($scope.referrals, function(referral, index) {
                                if (referral.id == updateReferralId) {
                                    $scope.referrals.splice(index, 1);
                                    return;
                                }
                            });
                        }
                    } else {
                        var errorMsg = response.message ||
                            'An error has occurred while saving route';
                        toastr.error(errorMsg);
                    }
                })
                .then(function() {
                    $scope.currentReferral = {};
                    $scope.assignReferralForm.$setPristine();
                });
        }
    };
    paginate();
}
