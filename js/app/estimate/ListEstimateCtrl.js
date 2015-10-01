angular
    .module('Erp')
    .controller(
        'ListEstimateCtrl',
        [
            '$scope',
            '$rootScope',
            '$routeParams',
            '$location',
            'estimateFactory',
            'sharedData',
            ListEstimateCtrl
        ]
    );

function ListEstimateCtrl(
        $scope,
        $rootScope,
        $routeParams,
        $location,
        estimateFactory,
        sharedData) {
    $scope.setPageTitle('List Estimates');
    $scope.estimates = {};
    $scope.selectedStatus = '';
    $scope.sendMailData = {};
    $scope.filter = {
        keyword: '',
        status: ''
    };
    // Paginate
    $scope.total = 0;
    var currentPage = 1;
    if ('undefined' != typeof($routeParams.pageNumber)) {
        currentPage = $routeParams.pageNumber;
    }
    $scope.currentPage = currentPage;
    var paginate = function() {
        var query = {
            _do: 'getEstimates',
            page: $scope.currentPage,
            status: $scope.filter.status,
            keyword: $scope.filter.keyword
        };
        estimateFactory.list(query)
            .success(function(response) {
                $scope.estimates = response.data;
                $scope.total = parseInt(response.total);
            });
    };
    $scope.pageChanged = function() {
        paginate();
    };

    $scope.changeFilterStatus = function() {
        // Reset page to 1
        $scope.currentPage = 1;
        paginate();
    };

    $scope.searchEstimate = function() {
        $scope.currentPage = 1;
        paginate();
    };

    $scope.clearSearch = function() {
        $scope.filter = {
            keyword: '',
            status: ''
        };
    };

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
        $scope.sendMailData.subject = 'Estimate from ' + sharedData.companyInfo.name;
        $scope.sendMailData.body = '';
        $scope.sendEmailForm.$setPristine();
    };

    $scope.sendMailEstimate = function() {
        $scope.showModal = false;
        estimateFactory.sendMail($scope.sendMailData)
            .success(function(response){
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            })
            .error(function() {
                toastr.error('An error has occurred while sending estimate.');
            });
    };
    paginate();
}
