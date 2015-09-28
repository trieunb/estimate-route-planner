<div id="erp-wrapper">
    <div id="spinner" ng-show="isBusy"></div>
    <h1 id="erp-header">Quickbooks connection</h1>
    <div id="erp-content">
        <script type="text/javascript" src="https://appcenter.intuit.com/Content/IA/intuit.ipp.anywhere.js"></script>
        <script type="text/javascript">
            intuit.ipp.anywhere.setup({
                menuProxy: '',
                grantUrl: '<?php echo site_url() . '?_do=startQuickbooksAuthenticate' ?>'
            });
        </script>
        <div class="authQuickbook">
            <p>
                The plugin are not currently connected with Quickbooks Online. It might the authenticate token is missing or expires.
                <br>
                Please click to bellow button to connect
            </p>
            <ipp:connectToIntuit></ipp:connectToIntuit>
        </div>
    </div>
</div>
