/**
 * DIAGNOSTIC SCRIPT FOR SALES USER AJAX ISSUES
 *
 * INSTRUCTIONS:
 * 1. Log in as a Sales user
 * 2. Navigate to the Pipeline Leads page
 * 3. Open browser DevTools (F12)
 * 4. Go to the Console tab
 * 5. Copy and paste this entire script into the console
 * 6. Press Enter to run it
 * 7. Wait for results to appear in the console
 * 8. Copy the output and send it back
 */

console.log('========================================');
console.log('SALES USER AJAX DIAGNOSTIC TEST');
console.log('========================================\n');

// Test 1: Check if user data is available
console.log('Test 1: Checking WordPress user data...');
if (typeof ajaxurl !== 'undefined') {
    console.log('✓ ajaxurl is defined:', ajaxurl);
} else {
    console.log('✗ ajaxurl is NOT defined - this is a problem!');
}

// Test 2: Test the diagnostic endpoint (no nonce required for testing)
console.log('\nTest 2: Testing diagnostic endpoint...');
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'test_sales_access'
    },
    success: function(response) {
        console.log('✓ Diagnostic endpoint SUCCESS!');
        console.log('Response:', response);

        if (response.success) {
            console.log('\n--- User Information ---');
            console.log('User ID:', response.data.user_id);
            console.log('Username:', response.data.username);
            console.log('Roles:', response.data.roles);
            console.log('Is Logged In:', response.data.is_logged_in);
            console.log('Has Pipeline Access:', response.data.has_pipeline_access);
            console.log('Capabilities (first 10):', response.data.capabilities.slice(0, 10));
        }
    },
    error: function(xhr, status, error) {
        console.log('✗ Diagnostic endpoint FAILED!');
        console.log('Status:', xhr.status, xhr.statusText);
        console.log('Error:', error);
        console.log('Response Text:', xhr.responseText);
    }
});

// Test 3: Check for existing nonces on the page
console.log('\nTest 3: Checking for nonces on the page...');
setTimeout(function() {
    const nonces = {};
    jQuery('input[name*="nonce"]').each(function() {
        nonces[this.name] = this.value;
    });

    if (Object.keys(nonces).length > 0) {
        console.log('✓ Found nonces:', nonces);
    } else {
        console.log('✗ No nonces found on the page');
    }

    // Test 4: Check cookies
    console.log('\nTest 4: Checking WordPress cookies...');
    const cookies = document.cookie.split(';').filter(c => c.includes('wordpress') || c.includes('wp-'));
    if (cookies.length > 0) {
        console.log('✓ Found WordPress cookies:', cookies.length);
        cookies.forEach(c => console.log('  -', c.trim().split('=')[0]));
    } else {
        console.log('✗ No WordPress cookies found - this may be the issue!');
    }

    // Test 5: Try to call the save_pipeline_lead handler with minimal data
    console.log('\nTest 5: Testing save_pipeline_lead handler (will fail without nonce, but checking if it reaches handler)...');

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'save_pipeline_lead',
            nonce: 'invalid_nonce_for_testing',
            lead_id: 1,
            fullname: 'Test',
            firstname: 'Test',
            lastname: 'User',
            email: 'test@test.com',
            contact_number: '1234567890',
            status: 'New'
        },
        success: function(response) {
            console.log('✓ Handler was reached (even if security check failed)');
            console.log('Response:', response);
        },
        error: function(xhr, status, error) {
            console.log('✗ Request failed before reaching handler');
            console.log('Status:', xhr.status, xhr.statusText);
            console.log('This confirms the 302 redirect issue!');

            if (xhr.status === 302 || xhr.status === 0) {
                console.log('\n⚠️  FOUND THE ISSUE: WordPress is redirecting your AJAX request!');
                console.log('This usually means your session is not being recognized.');
                console.log('\nRECOMMENDED FIXES:');
                console.log('1. Clear all browser cookies for this domain');
                console.log('2. Log out completely and log back in');
                console.log('3. Try in an incognito/private window');
                console.log('4. Check if other AJAX features work (post a comment, etc.)');
            }
        }
    });
}, 1000);

console.log('\n========================================');
console.log('Diagnostic tests running...');
console.log('Results will appear above in ~2 seconds');
console.log('========================================');
