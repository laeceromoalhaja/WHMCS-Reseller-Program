<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

/**
 * Register all module hooks
 */
function tiered_reseller_register_hooks() {
    return [
        // System hooks
        'DailyCronJob' => 'tiered_reseller_daily_sync',
        
        // Order processing
        'AfterModuleCreate' => 'tiered_reseller_after_service_creation',
        'AfterModuleTerminate' => 'tiered_reseller_after_service_termination',
        
        // Invoice generation
        'InvoiceCreation' => 'tiered_reseller_invoice_created',
        'InvoicePaid' => 'tiered_reseller_invoice_paid',
        
        // Client area
        'ClientAreaHomepage' => 'tiered_reseller_client_area_widget',
        
        // Admin area
        'AdminAreaHeader' => 'tiered_reseller_admin_notices'
    ];
}

/**
 * Daily cron job for account sync and invoicing
 */
function tiered_reseller_daily_sync() {
    try {
        require_once __DIR__ . '/includes/WHMAPI.php';
        require_once __DIR__ . '/includes/Utilities.php';
        
        $whmAPI = new TieredReseller_WHMAPI();
        $utils = new TieredReseller_Utilities();
        
        // Get all active resellers
        $resellers = Capsule::table('mod_tiered_reseller')
            ->whereNotNull('whm_username')
            ->get();
        
        foreach ($resellers as $reseller) {
            // 1. Sync account count from WHM
            $accountCount = $whmAPI->getAccountCount($reseller->whm_username);
            
            // 2. Update database
            Capsule::table('mod_tiered_reseller')
                ->where('id', $reseller->id)
                ->update([
                    'account_count' => $accountCount,
                    'last_sync' => Capsule::raw('NOW()')
                ]);
            
            // 3. Check if today is billing anniversary
            if (date('Y-m-d') == $reseller->next_due_date) {
                $utils->generateAnnualInvoice($reseller->userid);
            }
            
            // 4. Check for tier changes
            $currentTier = $reseller->current_tier;
            $newTier = $utils->calculateTier($accountCount);
            
            if ($currentTier != $newTier) {
                $utils->handleTierChange($reseller->userid, $currentTier, $newTier);
            }
        }
        
        logActivity("[Tiered Reseller] Daily sync completed for " . count($resellers) . " resellers");
        
    } catch (Exception $e) {
        logActivity("[Tiered Reseller] Sync Error: " . $e->getMessage());
    }
}

/**
 * After service creation hook
 */
function tiered_reseller_after_service_creation($vars) {
    $serviceId = $vars['params']['serviceid'];
    $productName = $vars['params']['productname'];
    
    if (stripos($productName, 'reseller') !== false) {
        try {
            $userId = $vars['params']['clientsdetails']['userid'];
            
            // Check if already exists
            $exists = Capsule::table('mod_tiered_reseller')
                ->where('userid', $userId)
                ->exists();
            
            if (!$exists) {
                // Add new reseller record
                Capsule::table('mod_tiered_reseller')->insert([
                    'userid' => $userId,
                    'serviceid' => $serviceId,
                    'whm_username' => $vars['params']['username'],
                    'setup_paid' => 0,
                    'next_due_date' => date('Y-m-d', strtotime('+1 year'))
                ]);
                
                // Charge setup fee
                $setupFee = Capsule::table('tbladdonmodules')
                    ->where('module', 'tiered_reseller')
                    ->where('setting', 'setup_fee')
                    ->value('value');
                
                if ($setupFee > 0) {
                    $invoiceId = localAPI('CreateInvoice', [
                        'userid' => $userId,
                        'date' => date('Y-m-d'),
                        'duedate' => date('Y-m-d'),
                        'itemdescription1' => 'Reseller Program Setup Fee',
                        'itemamount1' => $setupFee,
                        'itemtaxed1' => false
                    ]);
                    
                    if ($invoiceId['result'] == 'success') {
                        Capsule::table('mod_tiered_reseller')
                            ->where('userid', $userId)
                            ->update(['setup_paid' => 1]);
                    }
                }
            }
            
        } catch (Exception $e) {
            logActivity("[Tiered Reseller] Service Creation Error: " . $e->getMessage());
        }
    }
}

/**
 * After service termination hook
 */
function tiered_reseller_after_service_termination($vars) {
    $serviceId = $vars['params']['serviceid'];
    
    try {
        Capsule::table('mod_tiered_reseller')
            ->where('serviceid', $serviceId)
            ->delete();
            
    } catch (Exception $e) {
        logActivity("[Tiered Reseller] Service Termination Error: " . $e->getMessage());
    }
}

/**
 * Invoice creation hook
 */
function tiered_reseller_invoice_created($vars) {
    $invoiceId = $vars['invoiceid'];
    
    try {
        $invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);
        
        foreach ($invoice['items']['item'] as $item) {
            if (strpos($item['description'], 'hosted accounts') !== false) {
                $userId = $invoice['userid'];
                
                // Update last invoice date
                Capsule::table('mod_tiered_reseller')
                    ->where('userid', $userId)
                    ->update([
                        'last_invoice_date' => date('Y-m-d'),
                        'last_invoice_amount' => $item['amount']
                    ]);
                
                break;
            }
        }
        
    } catch (Exception $e) {
        logActivity("[Tiered Reseller] Invoice Creation Error: " . $e->getMessage());
    }
}

/**
 * Invoice paid hook
 */
function tiered_reseller_invoice_paid($vars) {
    $invoiceId = $vars['invoiceid'];
    
    try {
        $invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);
        
        foreach ($invoice['items']['item'] as $item) {
            if (strpos($item['description'], 'hosted accounts') !== false) {
                $userId = $invoice['userid'];
                
                // Send confirmation email
                $config = Capsule::table('tbladdonmodules')
                    ->where('module', 'tiered_reseller')
                    ->pluck('value', 'setting');
                
                if ($config['enable_emails'] == 'on') {
                    sendMessage('Reseller Invoice Paid', $userId, [
                        'invoice_id' => $invoiceId,
                        'invoice_amount' => $item['amount'],
                        'account_count' => Capsule::table('mod_tiered_reseller')
                            ->where('userid', $userId)
                            ->value('account_count')
                    ]);
                }
                
                break;
            }
        }
        
    } catch (Exception $e) {
        logActivity("[Tiered Reseller] Invoice Paid Error: " . $e->getMessage());
    }
}

/**
 * Client area widget
 */
function tiered_reseller_client_area_widget() {
    $userId = $_SESSION['uid'] ?? 0;
    
    try {
        $resellerData = Capsule::table('mod_tiered_reseller')
            ->where('userid', $userId)
            ->first();
            
        if ($resellerData) {
            return [
                'title' => 'Reseller Program',
                'data' => [
                    'Hosted Accounts' => $resellerData->account_count,
                    'Current Tier' => 'Tier ' . $resellerData->current_tier,
                    'Next Billing Date' => $resellerData->next_due_date
                ],
                'footer' => '<a href="index.php?m=tiered_reseller">View Details</a>'
            ];
        }
        
    } catch (Exception $e) {
        logActivity("[Tiered Reseller] Client Widget Error: " . $e->getMessage());
    }
    
    return [];
}

/**
 * Admin area notices
 */
function tiered_reseller_admin_notices() {
    $needsSync = Capsule::table('mod_tiered_reseller')
        ->whereNull('last_sync')
        ->orWhere('last_sync', '<', date('Y-m-d', strtotime('-2 days')))
        ->exists();
        
    if ($needsSync) {
        echo '<div class="alert alert-warning text-center">
            <strong>Tiered Reseller:</strong> Some accounts need synchronization
            <a href="addonmodules.php?module=tiered_reseller" class="btn btn-xs btn-default">Sync Now</a>
        </div>';
    }
}

// Register all hooks
add_hook('AdminAreaHeadOutput', 1, function() {
    $hooks = tiered_reseller_register_hooks();
    foreach ($hooks as $hookPoint => $function) {
        add_hook($hookPoint, 1, $function);
    }
});
