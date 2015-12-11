angular
    .module('Erp')
    .service('erpLocalStorage',
        [
            '$q',
            'sharedData',
            'employeeFactory',
            'customerFactory',
            'productServiceFactory',
            'classFactory',
            erpLocalStorage
        ]
    );

function erpLocalStorage(
    $q,
    sharedData,
    employeeFactory,
    customerFactory,
    productServiceFactory,
    classFactory) {

    var _this = this;
    // Cache timestamp
    var cacheTs = null;

    // The data to be cached
    var cacheData = { // Null values meaning it's not initaled yet
        productServices: null,
        employees: null,
        customers: null,
        unsortCustomers: null,
        classes: null
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

    /**
     * Build a tree customers array by given flattern elements
     */
    var buildCustomersTree = function(customers, parentId) {
        var result = [];
        var length = customers.length;
        for (var i = 0; i < length; i++) {
            var item = customers[i];
            if (item.parent_id === parentId) {
                item.childs = buildCustomersTree(customers, item.id);
                result.push(item);
            }
        }

        if (result.length > 0) {
            return result;
        }
        return null;
    };

    /**
     * Flattern a given customer node
     * Sort by A-Z and keep childs next the parent
     */
    var flatternCustomer = function(customer) {
        var results = [];
        results[0] = customer;
        if (customer.childs !== null && customer.childs.length > 0) {
            var childs = customer.childs;
            childs.sort(function(a, b) {
                return a.display_name.localeCompare(b.display_name);
            });
            for (var i = 0; i < customer.childs.length; i++) {
                results = results.concat(flatternCustomer(customer.childs[i]));
            }
        }
        return results;
    };

    /**
     * Sort customers by A-Z and keep the childs next their parents
     * Each levels need to be in A-Z order
     */
    var sortCustomers = function(customers) {
        var tree = buildCustomersTree(customers, null);
        var results = [];
        var treeLen = tree.length;
        for (var i = 0; i < treeLen; i++) {
            results = results.concat(flatternCustomer(tree[i]));
        }
        var resultsLen = results.length;
        for (var j = results.length - 1; j >= 0; j--) {
            results[j].order = j;
        }
        return results;
    };

    this.clearCustomers = function() {
        cacheData.customers = null;
    };

    this.clearProductServices = function() {
        cacheData.productServices = null;
    };

    this.clearClasses = function() {
        cacheData.classes = null;
    };

    this.clearEmployees = function() {
        cacheData.employees = null;
    };

    this.addCustomer = function(customer) {
        if (cacheData.unsortCustomers) {
            cacheData.unsortCustomers.push(customer);
            // Re-sort customers dropdown
            _this.setCustomers(cacheData.unsortCustomers);
        }
    };

    this.updateCustomer = function(customer) {
        if (cacheData.unsortCustomers) {
            for (var i = 0; i < cacheData.unsortCustomers.length; i++) {
                if (cacheData.unsortCustomers[i].id == customer.id) {
                    cacheData.unsortCustomers[i] = {};
                    cacheData.unsortCustomers[i] = customer;
                    // Re-sort customers dropdown
                    _this.setCustomers(cacheData.unsortCustomers);
                    break;
                }
            }
        }
    };

    this.setCustomers = function(customers) {
        rememberData('unsortCustomers', customers);
        var sortedCustomers = [];
        if (customers.length) {
            sortedCustomers = sortCustomers(customers);
        }
        rememberData('customers', sortedCustomers);
    };

    this.getCustomers = function() {
        return $q(function(resolve) {
            if (null === cacheData.customers) {
                customerFactory.all()
                    .success(function(responseData) {
                        _this.setCustomers(responseData);
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
                productServiceFactory.all()
                    .success(function(responseData) {
                        rememberData('productServices', responseData);
                        resolve(cacheData.productServices);
                    });
            } else {
                resolve(cacheData.productServices);
            }
        });
    };

    this.getClasses = function() {
        return $q(function(resolve) {
            if (null === cacheData.classes) {
                classFactory.all()
                    .success(function(responseData) {
                        rememberData('classes', responseData);
                        resolve(cacheData.classes);
                    });
            } else {
                resolve(cacheData.classes);
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
