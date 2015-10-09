angular
    .module('Erp')
    .controller('ListJobRequestCtrl', [
        '$scope',
        '$routeParams',
        'jobRequestFactory',
        'estimateRouteFactory',
        '$ngBootbox',
        ListJobRequestCtrl
    ]);

function ListJobRequestCtrl($scope, $routeParams, jobRequestFactory, estimateRouteFactory, $ngBootbox) {
    $scope.setPageTitle('List Job Requests');
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

    estimateRouteFactory.all()
        .success(function(responseData){
            $scope.routes = responseData;
        });

    var paginate = function() {
        var query = {
            _do: 'getReferrals',
            page: $scope.currentPage,
            keyword: $scope.filter.keyword
        };
        jobRequestFactory.list(query)
            .success(function(response){
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
            jobRequestFactory.updateStatus($scope.currentReferral)
                .success(function(response) {

                })
                .then(function() {
                    $scope.currentReferral = {};
                    $scope.assignReferralForm.$setPristine();
                });
        }
    };
    paginate();
}
