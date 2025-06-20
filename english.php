<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

$_ADDONLANG = array(

    // Module general
    'title' => "Tiered Reseller Program",
    'description' => "Manage your reseller hosting accounts and billing tiers",
    'module_name' => "Tiered Reseller",
    
    // Admin interface
    'admin_tab_title' => "Reseller Management",
    'admin_heading' => "Reseller Accounts Overview",
    'admin_no_resellers' => "No reseller accounts found",
    'admin_sync_now' => "Sync Now",
    'admin_preview_invoice' => "Preview Invoice",
    'admin_edit_account' => "Edit Account",
    'admin_add_reseller' => "Add Reseller",
    'admin_whm_username' => "WHM Username",
    'admin_account_count' => "Account Count",
    'admin_current_tier' => "Current Tier",
    'admin_last_invoice' => "Last Invoice",
    'admin_next_due' => "Next Due",
    'admin_actions' => "Actions",
    'admin_tier_1' => "Tier 1 (1-150)",
    'admin_tier_2' => "Tier 2 (151-300)",
    'admin_tier_3' => "Tier 3 (301+)",
    'admin_sync_status' => "Sync Status",
    'admin_last_sync' => "Last Sync",
    'admin_never' => "Never",
    'admin_sync_success' => "Sync completed successfully",
    'admin_sync_failed' => "Sync failed: please check logs",
    'admin_invoice_generated' => "Invoice generated successfully",
    
    // Client area
    'client_welcome' => "Your Reseller Dashboard",
    'client_accounts' => "Hosted Accounts",
    'client_current_tier' => "Current Tier",
    'client_next_billing' => "Next Billing Date",
    'client_last_invoice' => "Last Invoice",
    'client_tier_details' => "Tier Details",
    'client_accounts_range' => "Accounts in current tier",
    'client_price_per_account' => "Price per account",
    'client_next_tier_threshold' => "Next tier threshold",
    'client_max_tier_reached' => "You've reached the highest tier!",
    'client_billing_info' => "Billing Information",
    'client_estimated_total' => "Estimated renewal",
    'client_billing_cycle' => "Billing cycle",
    'client_need_help' => "Need Help?",
    'client_contact_support' => "Contact support for any questions",
    'client_manage_service' => "Manage Service",
    
    // Tiers
    'tier_name_1' => "Basic",
    'tier_name_2' => "Professional",
    'tier_name_3' => "Enterprise",
    'tier_price_format' => "$:price/account/year",
    
    // Notifications
    'notify_invoice_subject' => "New Reseller Program Invoice",
    'notify_invoice_body' => "A new invoice for your reseller account has been generated. Invoice ID: :invoice_id",
    'notify_tier_change_subject' => "Your Reseller Tier Has Changed",
    'notify_tier_change_body' => "Your account has moved from Tier :old_tier to Tier :new_tier. New price per account: $:new_price",
    'notify_renewal_reminder_subject' => "Reseller Program Renewal Reminder",
    'notify_renewal_reminder_body' => "Your reseller program will renew in 7 days. Estimated total: $:estimated_total",
    
    // Status messages
    'status_sync_in_progress' => "Sync in progress...",
    'status_invoice_generating' => "Generating invoice...",
    'status_success' => "Success!",
    'status_error' => "Error!",
    
    // Time-related
    'time_days_remaining' => ":days days remaining",
    'time_due_today' => "Due today",
    'time_overdue' => "Overdue by :days days",
    
    // Log messages
    'log_sync_success' => "Synced :count accounts for reseller :username",
    'log_sync_failed' => "Sync failed for reseller :username: :error",
    'log_tier_change' => "Tier changed from :old_tier to :new_tier",
    'log_invoice_generated' => "Generated invoice #:invoice_id for :amount",
    'log_setup_fee_charged' => "Setup fee charged for new reseller",
    
    // Errors
    'error_missing_whm_username' => "WHM username not configured",
    'error_api_connection' => "Could not connect to WHM API",
    'error_invalid_tier' => "Invalid tier level",
    'error_no_accounts' => "No accounts found",
    'error_invoice_failed' => "Invoice generation failed",

    // Settings
    'setting_enable_emails' => "Enable Email Notifications",
    'setting_whm_api_token' => "WHM API Token",
    'setting_whm_server_ip' => "WHM Server IP",
    'setting_setup_fee' => "Setup Fee Amount",
    'setting_tier_prices' => "Tier Pricing",

    // Buttons
    'btn_save' => "Save Changes",
    'btn_cancel' => "Cancel",
    'btn_confirm' => "Confirm",
    'btn_back' => "Back",

    // Tooltips
    'tooltip_sync' => "Force immediate sync with WHM",
    'tooltip_preview' => "Preview next invoice",
    'tooltip_edit' => "Edit reseller details",

    // Miscellaneous
    'misc_loading' => "Loading...",
    'misc_unknown' => "Unknown",
    'misc_na' => "N/A"
);
