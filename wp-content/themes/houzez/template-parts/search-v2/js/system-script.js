// Ensure jQuery is available
if (typeof jQuery !== 'undefined') {
    (function($) {
        'use strict';
        
        // Initialize only if not already exists
        $.SystemScript = $.SystemScript || {};
        
        let __executeGet = function(path) {
            let dfd = $.Deferred();
            axios.get(path)
            .then(function (response) {
                dfd.resolve(response);
            })
            .catch(function (error) {
                dfd.resolve({
                    status : 'ERROR',
                    message : error
                });

            })
            return dfd.promise();
        };

        let __executePost = function(path, jsonObj) {
            path = path;
            let d = $.Deferred();

            axios.post(path, jsonObj)
            .then(function (response) {
                d.resolve(response)
            })
            .catch(function (error) {
                d.resolve({
                    status : 'ERROR',
                    message : error
                });
                console.log('ee')

            });

            return d.promise();
        };

        // Add other functions as needed...

        // Public API
        $.extend($.SystemScript, {
            executeGet: __executeGet,
            executePost: __executePost
            // Add other methods as needed...
        });

    })(jQuery);
} else {
    console.error('jQuery is not loaded');
}