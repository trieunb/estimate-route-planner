<div id="erp-wrapper">
    <h1 id="erp-header">Estimate and route planner</h1>
    <div id="erp-content" ng-class="{busy: isBusy}">
        <div class="loading-overlay" ng-if="isBusy">
            <div id="spinner"></div>
        </div>        
        <div>
            <div ng-controller="ConfigCtrl">
                <p class="text-warning">
                    Please supply all the required information:
                </p>
                <form class="form-horizontal config-form" name="configForm" ng-submit="submitForm()">
                    <div class="row">
                        <div class="col-md-5">
                            <legend class="text-info">General</legend>
                            <div class="form-group">
                                <label class="control-label col-md-4">Gmap API key:</label>
                                <div class="col-md-8">
                                    <input class="form-control" type="text" name="gmap_api_key"
                                        ng-required="true"
                                        ng-model="config.gmap_api_key">
                                </div>
                            </div>
                            <br>
                            <br>
                            <legend class="text-info">Quickbooks connection</legend>
                            <div class="form-group">
                                <label class="control-label col-md-4">Consumer key:</label>
                                <div class="col-md-8">
                                    <input class="form-control" type="text" name="qb_consumer_key"
                                        ng-required="true"
                                        ng-model="config.qbo_consumer_key">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Consumer secret:</label>
                                <div class="col-md-8">
                                    <input class="form-control" type="text" name="qb_consumer_secret"
                                        ng-required="true"
                                        ng-model="config.qbo_consumer_secret">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5 col-md-offset-1">
                            <legend class="text-info">Gmail setting:</legend>
                            <div class="form-group">
                                <label class="control-label col-md-4">Server:</label>
                                <div class="col-md-8">
                                    <input class="form-control" type="text" name="gmail_server"
                                        placeholder="smtp.gmail.com"
                                        ng-required="true"
                                        ng-model="config.gmail_server">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Port:</label>
                                <div class="col-md-8">
                                    <input class="form-control" type="text" name="gmail_port"
                                        placeholder="587, 465, or 25 ..."
                                        ng-required="true"
                                        ng-model="config.gmail_port">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Username:</label>
                                <div class="col-md-8">
                                    <input class="form-control" type="text" name="gmail_username"
                                        placeholder="yourusername@gmail.com"
                                        ng-required="true"
                                        ng-model="config.gmail_username">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Password:</label>
                                <div class="col-md-8">
                                    <input class="form-control" type="password" name="gmail_password"
                                    ng-required="true"
                                    ng-model="config.gmail_password">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <hr>
                            <button type="submit" class="btn btn-primary" ng-disabled="configForm.$invalid || $root.isBusy">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
