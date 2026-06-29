/**
 * JINC Media AI - AJAX Trigger
 * Intercepts media uploads to process AI alt text generation asynchronously.
 */
(function($) {
    'use strict';

    if (typeof wp === 'undefined' || typeof jincAiData === 'undefined') {
        return;
    }

    // Hook into Backbone Attachments collection
    if (wp.media && wp.media.model && wp.media.model.Attachments) {
        wp.media.model.Attachments.all.on('add', function(attachment) {
            checkAndProcessAI(attachment);
        });
    } else {
        // Fallback for async-upload in Media Library Grid/List View
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings.url && settings.url.indexOf('async-upload.php') !== -1) {
                var response = xhr.responseJSON;
                if (response && response.success && response.data && response.data.id) {
                    // Create a dummy attachment object that mimics Backbone model API for checkAndProcessAI
                    var dummyAttachment = {
                        get: function(key) {
                            return response.data[key];
                        },
                        set: function(key, value) {
                            // Can't easily update UI dynamically without Backbone, but the processing is triggered.
                            // The backend updates the DB, so reloading the page or clicking the item will show it.
                        }
                    };
                    checkAndProcessAI(dummyAttachment);
                }
            }
        });
    }

    function checkAndProcessAI(attachment) {
        var altText = attachment.get('alt');
        
        // Only trigger if the alt text matches the Quarantine string
        if (altText === '[JINC: Processando IA...]') {
            
            var attachmentId = attachment.get('id');

            $.post(jincAiData.ajaxUrl, {
                action: 'jinc_process_ai',
                attachment_id: attachmentId,
                nonce: jincAiData.nonce
            })
            .done(function(response) {
                if (response.success && response.data) {
                    if (response.data.alt) {
                        attachment.set('alt', response.data.alt);
                    }
                    if (response.data.description) {
                        attachment.set('description', response.data.description);
                    }
                    console.log('JINC AI: Imagem processada com sucesso via IA.', response.data);
                } else {
                    console.warn('JINC AI: Falha no processamento (Timeout/Error) para attachment ' + attachmentId);
                }
            })
            .fail(function() {
                console.error('JINC AI: Falha catastrófica de AJAX para attachment ' + attachmentId);
            });
        }
    }
})(jQuery);
