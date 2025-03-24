/**
 * RepliPost Admin JavaScript
 */

(function($) {
    'use strict';

    /**
     * Initialize the admin scripts
     */
    function init() {
        // Add confirmation to duplicate links
        addDuplicateConfirmation();
        
        // Initialize settings page
        initSettingsPage();
        
        // Add confirmation to bulk actions
        addBulkActionConfirmation();
    }

    /**
     * Add confirmation dialog to duplicate links
     */
    function addDuplicateConfirmation() {
        $(document).on('click', 'a[href*="action=replipost_duplicate"]', function(e) {
            if (!confirm(repliPostAdmin.confirmDuplicate)) {
                e.preventDefault();
                return false;
            }
            return true;
        });
    }
    
    /**
     * Add confirmation dialog to bulk actions
     */
    function addBulkActionConfirmation() {
        $('#doaction, #doaction2').on('click', function(e) {
            var selectedAction = $(this).prev('select').val();
            
            if ('duplicate' === selectedAction) {
                if (!confirm(repliPostAdmin.confirmBulkDuplicate)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }

    /**
     * Initialize settings page functionality
     */
    function initSettingsPage() {
        // Dismiss notices
        $('.notice-dismiss').on('click', function() {
            $(this).closest('.notice').fadeOut();
        });

        // Select all post types
        $('#replipost-select-all').on('click', function(e) {
            e.preventDefault();
            $('input[name="replipost_post_types[]"]').prop('checked', true);
        });

        // Deselect all post types
        $('#replipost-deselect-all').on('click', function(e) {
            e.preventDefault();
            $('input[name="replipost_post_types[]"]').prop('checked', false);
        });
    }

    // Initialize when DOM is ready
    $(document).ready(init);

})(jQuery); 