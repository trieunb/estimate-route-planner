/**
 * For options use in form
 */
angular
    .module('Erp')
    .value('erpOptions', {
        sortCrewRoute: [
            {
               label: 'Custom',
               value: ''
            },
            {
               label: 'Total',
               value: 'total'
            },
            {
               label: 'Exp date',
               value: 'expiration_date'
            }
        ],

        sortEstimateRoute: [
            {
                label: 'Custom',
                value: ''
            },
            {
                label: 'Status',
                value: 'status'
            },
            {
                label: 'Date Requested',
                value: 'date_requested'
            }
        ],
        referralStatuses: [
            {
                value: 'Pending',
                label: 'Pending'
            },
            {
                value: 'Assigned',
                label: 'Assigned'
            },
            {
                value: 'Completed',
                label: 'Completed'
            }
        ],
        routeStatuses: [
            {
                value: 'Pending',
                label: 'Pending'
            },
            {
                value: 'Assigned',
                label: 'Assigned'
            },
            {
                value: 'Completed',
                label: 'Completed'
            }
        ],
        estimateStatuses: [
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
                value: 'Routed',
                label: 'Routed' // NOTE: Quickbooks still shows Accepted
            },
            {
                value: 'Closed',
                label: 'Closed'
            },
            {
                value: 'Rejected',
                label: 'Rejected'
            }
        ]
    });
