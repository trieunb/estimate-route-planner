angular
    .module('Erp')
    .service('emailComposer',
        [
            'sharedData',
            emailComposer
        ]
    );
function emailComposer(sharedData) {
    this.getEstimateEmailContent = function(estimate, customer) {
        if (sharedData.companyInfo.email_template) {
            var emailTemplate = sharedData.companyInfo.email_template;
            var listEmbeCodeMap = {
                '{{estimate_number}}': estimate.doc_number,
                '{{estimate_total}}': estimate.total,
                '{{billing_customer_first_name}}': customer.given_name,
                '{{billing_customer_last_name}}': customer.family_name
            };
            for (key in listEmbeCodeMap) {
                emailTemplate = emailTemplate.replace(key, listEmbeCodeMap[key]);
            }
            return emailTemplate;
        }
    };
}