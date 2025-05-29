jQuery(document).ready(function($) {
    // Test SMTP functionality
    $('#dmr-test-smtp').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $result = $('#dmr-smtp-test-result');
        var originalText = $button.text();
        
        // Disable button and show loading
        $button.prop('disabled', true).text('Sending...');
        $result.html('').removeClass('notice notice-success notice-error');
        
        // Make AJAX request
        $.ajax({
            url: dmrAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'dmr_test_smtp',
                nonce: dmrAdmin.testSmtpNonce || ''
            },
            success: function(response) {
                $button.prop('disabled', false).text(originalText);
                
                if (response.success) {
                    $result.html('<span style="color: #46b450;">✓ ' + response.data.message + '</span>');
                } else {
                    $result.html('<span style="color: #dc3232;">✗ ' + (response.data.message || 'Failed to send test email.') + '</span>');
                }
            },
            error: function() {
                $button.prop('disabled', false).text(originalText);
                $result.html('<span style="color: #dc3232;">✗ An error occurred while testing SMTP.</span>');
            }
        });
    });
});
