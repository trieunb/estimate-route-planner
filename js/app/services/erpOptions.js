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
        ],
        map: {
            firstPointColor: '#52BC2B',
            middlePointColor: '#3F51B5',
            lastPointColor: '#9A0C02',
            markerLabels: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
        },
        // mapMarkerLabels: [
        //     {
        //         text: 'A',
        //         backgroundColor: '#52BC2B',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'B',
        //         backgroundColor: '#3E88EF',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'C',
        //         backgroundColor: '#FF772D',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'D',
        //         backgroundColor: '#4B18F4',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'E',
        //         backgroundColor: '#ED3160',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'F',
        //         backgroundColor: '#4ED8AC',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'G',
        //         backgroundColor: '#F9CA0C',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'H',
        //         backgroundColor: '#28CCCC',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'I',
        //         backgroundColor: '#985ABC',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'J',
        //         backgroundColor: '#D86877',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'K',
        //         backgroundColor: '#DDED76',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'L',
        //         backgroundColor: '#4963D8',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'M',
        //         backgroundColor: '#',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'N',
        //         backgroundColor: '#',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'O',
        //         backgroundColor: '#',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'P',
        //         backgroundColor: '#',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'Q',
        //         backgroundColor: '#',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'R',
        //         backgroundColor: '#',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'S',
        //         backgroundColor: '#',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'T',
        //         backgroundColor: '#',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'U',
        //         backgroundColor: '#',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'V',
        //         backgroundColor: '#',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'W',
        //         backgroundColor: '#',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'X',
        //         backgroundColor: '#',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'Y',
        //         backgroundColor: '#',
        //         color: '#FFF'
        //     },
        //     {
        //         text: 'Z',
        //         backgroundColor: '#',
        //         color: '#FFF'
        //     }
        // ],
        mapPolylineOptions: {
            strokeColor: '#353BE1',
            strokeOpacity: 0.7,
            strokeWeight: 5
        }
    });
