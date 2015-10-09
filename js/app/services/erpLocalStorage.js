angular
    .module('Erp')
    .service('erpLocalStorage',
        [
            '$q',
            'sharedData',
            'employeeFactory',
            'customerFactory',
            erpLocalStorage
        ]
    );

function erpLocalStorage($q, sharedData, employeeFactory, customerFactory) {
    // Cache timestamp
    var cacheTs = null;

    // The data to be cached
    var cacheData = { // Null values meaning it's not initaled yet
        productServices: null,
        employees: null,
        customers: null
    };

    var init = function() {
        // if (isExists('cacheTs') && !isNaN(retriveData('cacheTs'))) {
        //     cacheTs = parseInt(retriveData('cacheTs'));
        // } else {
        //     cacheTs = Math.floor(Date.now() / 1000);
        //     writeData('cacheTs', cacheTs);
        // }
        // for (var key in cacheData) {
        //     if (isExists(key)) {
        //         cacheData[key] = retriveData(key);
        //     }
        // }
    };

    // Check for the given key is exists in the localStorage
    var isExists = function(key) {
        return ('undefined' !== typeof(localStorage[key]));
    };

    /**
     * Write to thelocalStorage
     */
    var writeData = function(key, value) {
        localStorage.setItem(key, JSON.stringify(value));
    };

    /**
     * Retrive data from local storage
     */
    var retriveData = function(key) {
        return JSON.parse(localStorage.getItem(key));
    };

    var rememberData = function(key, value) {
        cacheData[key] = value;
        // writeData(key, value);
    };

    var isDataValid = function() {
        return cacheTs >= parseInt(sharedData.lastSyncAt);
    };

    this.clearCustomers = function() {
        cacheData.customers = null;
    };

    this.clearProductServices = function() {
        cacheData.productServices = null;
    };

    this.getCustomers = function() {
        return $q(function(resolve) {
            if (null === cacheData.customers) {
                customerFactory.all()
                    .success(function(responseData) {
                        rememberData('customers', responseData);
                        resolve(cacheData.customers);
                    });
            } else {
                resolve(cacheData.customers);
            }
        });
    };

    this.getProductServices = function() {
        return $q(function(resolve) {
            if (null === cacheData.productServices) {
                customerFactory.all()
                    .success(function(responseData) {
                        rememberData('productServices', responseData);
                        resolve(cacheData.productServices);
                    });
            } else {
                resolve(cacheData.productServices);
            }
        });
    };

    this.getEmployees = function() {
        return $q(function(resolve) {
            if (null === cacheData.employees) {
                employeeFactory.all()
                    .success(function(responseData) {
                        rememberData('employees', responseData);
                        resolve(responseData);
                    });
            } else {
                resolve(cacheData.employees);
            }
        });
    };

    init();
}
