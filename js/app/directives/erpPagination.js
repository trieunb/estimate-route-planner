angular
    .module('Erp')
    .directive('erpPagination',function() {
        return {
            template:
                '<uib-pagination ng-show="total"' +
                'rotate="true"' +
                'boundary-links="true"' +
                'max-size="10" class="pagination-md"' +
                'total-items="total"' +
                'items-per-page="15"' +
                'ng-model="currentPage"' +
                'ng-change="pageChanged()">' +
                '</uib-pagination>'
            };
        });
