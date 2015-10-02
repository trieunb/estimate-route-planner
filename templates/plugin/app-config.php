<script type="text/javascript" src="https://appcenter.intuit.com/Content/IA/intuit.ipp.anywhere.js"></script>
<script type="text/javascript">
    intuit.ipp.anywhere.setup({
        menuProxy: '',
        grantUrl: '<?php echo site_url() . '?_do=startQuickbooksAuthenticate' ?>'
    });

    function onSuccessAuthenticed() {
        window.location.hash = '#quickbooks-sync';
        window.location.reload();
    }
</script>

<div id="erp-wrapper">
    <h1 id="erp-header">ER Planner Pro - Quickbooks Online authorization</h1>
    <div id="erp-content" ng-class="{busy: isBusy}" ng-cloak>
        <div class="loading-overlay" ng-if="isBusy">
            <div id="spinner"></div>
        </div>
        <div>
            <div ng-controller="AppConfigCtrl">
                <div ng-show="step == 1">
                    <form class="form-horizontal config-form" name="appConfigForm" ng-submit="submitForm()">
                        <div class="row">
                            <div class="col-md-7 col-md-offset-1">
                                <h4 class="text-info">Please supply your app consumer keys:</h4>
                                <br>
                                <div class="form-group">
                                    <label class="control-label col-md-4 required">Consumer key <span>(*)</span></label>
                                    <div class="col-md-8">
                                        <input class="form-control" type="text" name="qbo_consumer_key"
                                            ng-required="true"
                                            ng-model="config.qbo_consumer_key">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-4">Consumer secret <span>(*)</span></label>
                                    <div class="col-md-8">
                                        <input class="form-control" type="text" name="qbo_consumer_secret"
                                            ng-required="true"
                                            ng-model="config.qbo_consumer_secret">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-md-offset-4 col-md-8">
                                        <button type="submit" class="btn btn-primary" ng-disabled="appConfigForm.$invalid || $root.isBusy">Continue</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div ng-show="step == 2">
                    <div class="row">
                        <div class="col-md-7 col-md-offset-1">
                            <h4 class="text-info">Click to the bellow button to start authorization:</h4>
                            <ipp:connectToIntuit></ipp:connectToIntuit>
                            <br>
                            <br>
                            <button type="button" class="btn btn-default" ng-click="backToSetConsumerKeys()"> Back</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
