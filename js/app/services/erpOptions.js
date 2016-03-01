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
               label: 'Estimate date',
               value: 'txn_date'
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
        ],
        estimatePriorities: [
            {
                value: 'Normal',
                label: 'Normal'
            },
            {
                value: 'High',
                label: 'High'
            },
            {
                value: 'Storm Damage',
                label: 'Storm Damage'
            }
        ],
        map: {
            firstPointColor: '#52BC2B',
            middlePointColor: '#3F51B5',
            lastPointColor: '#9A0C02',
            markerLabels: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789',
            polylineOptions: {
                strokeColor: '#353BE1',
                strokeOpacity: 0.7,
                strokeWeight: 5
            }
        }
    });
