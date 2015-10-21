/**
 * Modal for customer signature modal in estimate form
 * Just for DRYing up the code :)
 */
angular.module('Erp')
    .directive('customerSignatureModal',
        [
            '$rootScope',
            '$timeout',
            customerSignatureModal
        ]);

function customerSignatureModal($rootScope, $timeout) {
    return {
        restrict: 'E',
        scope: {
            isShowModal: '=show', // Modal show/hide condition
            onSave: '&' // Callback when click Save
        },
        replace: true,
        templateUrl: $rootScope.templatesPath + 'estimate/modal-signature.html',
        link: function(scope, element, attrs, ctrl) {
            scope.isShowModal = false;
            $timeout(function() {
                var padWrapper = angular.element('.div-customer-signature');
                var canvas = padWrapper.find('canvas').get(0);
                scope.signaturePad = new SignaturePad(canvas);
            });
        },
        controller: function($scope) {
            $scope.hideModal = function() {
                $scope.isShowModal = false;
                $scope.clear();
            };

            $scope.clear = function() {
                $scope.signaturePad.clear();
            };

            $scope.save = function() {
                $scope.isShowModal = false;
                var encoded;
                if ($scope.signaturePad.isEmpty()) {
                    encoded = null;
                } else {
                    encoded = $scope.signaturePad.toDataURL();
                }
                $scope.onSave({signatureEncoded: encoded});
                $scope.clear();
            };
        }
    };
}
