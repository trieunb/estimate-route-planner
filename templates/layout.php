<div id="erp-wrapper">
    <h1 id="erp-header"><span ng-bind="pageTitle"></span></h1>
    <div id="erp-content" ng-class="{busy: isBusy}">
        <div class="loading-overlay" ng-if="isBusy">
            <div id="spinner"></div>
        </div>
        <div ng-view id="erp-view"></div>
    </div>
</div>
