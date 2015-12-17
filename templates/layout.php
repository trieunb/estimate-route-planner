<div id="erp-wrapper">
    <h1 id="erp-header"><span ng-bind="pageTitle"></span></h1>
    <div class="erp-tool-bar text-right">
        <button type="button"
            class="btn btn-default btn-sm ng-cloak btn-refresh-page hide-print"
            ng-disabled="isBusy"
            ng-click="refreshPage()">
            <span class="glyphicon glyphicon-refresh"></span></button>
    </div>
    <div id="erp-content" ng-class="{busy: isBusy}" ng-cloak>
        <div class="loading-overlay" ng-if="isBusy">
            <div style="position: relative; display: inline-block">
                <div id="spinner"></div>
            </div>
        </div>
        <div ng-view id="erp-view"></div>
    </div>
</div>
