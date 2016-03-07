angular
    .module('Erp')
    .service('emailComposer',
        [
            'sharedData',
            emailComposer
        ]
    );
function emailComposer(sharedData) {
    this.getEmailTemplateEstimate = function(estimate) {
        if (sharedData.companyInfo.email_template) {
            var emailTemplate = sharedData.companyInfo.email_template;
            var listEmbeCodeMap = {
                '{{estimate_number}}' : estimate.doc_number,
                '{{estimate_total}}' : estimate.total,
                '{{billing_customer_display_name}}': estimate.billing_customer_display_name,
                '{{shipping_customer_display_name}}' : estimate.shipping_customer_display_name,
            };
            for (key in listEmbeCodeMap) {
                emailTemplate = emailTemplate.replace(key, listEmbeCodeMap[key])
            }
            return emailTemplate;
        }
    };
}