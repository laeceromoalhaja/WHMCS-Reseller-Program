<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/includes/WHMAPI.php';
require_once __DIR__ . '/includes/Utilities.php';

/**
 * Main cron function - registered via hook
 */
function tiered_reseller_daily_cron() {
    try {
        $startTime = microtime(true);
        $stats = [
            'resellers_processed' => 0,
            'invoices_generated' => 0,
            'tier_changes' => 0,
            'errors' => 0
        ];

        // Get module configuration
        $config = Capsule::table('tbladdonmodules')
            ->where('module', 'tiered_reseller')
            ->pluck('value', 'setting');

        // Initialize services
        $whmAPI = new TieredReseller_WHMAPI();
        $utils = new TieredReseller_Utilities();

        // Process active resellers
        $resellers = Capsule::table('mod_tiered_reseller as tr')
            ->join('tblhosting as h', 'tr.serviceid', '=', 'h.id')
            ->where('h.domainstatus', 'Active')
            ->select('tr.*')
            ->get();

        foreach ($resellers as $reseller) {
            try {
                $stats['resellers_processed']++;

                /************************************
                 * 1. ACCOUNT COUNT SYNC FROM WHM
                 ************************************/
                $accountCount = $whmAPI->getAccountCount($reseller->whm_username);
                
                // Log if count changed significantly (>10% change)
                $threshold = $reseller->account_count * 0.1;
                if (abs($accountCount - $reseller->account_count) > $threshold) {
                    logActivity("[Tiered Reseller] Significant account count change for reseller {$reseller->userid}: {$reseller->account_count} => {$accountCount}");
                }

                /************************************
                 * 2. TIER CALCULATION
                 ************************************/
                $newTier = $utils->calculateTier($accountCount, $config);
                $tierChanged = ($newTier != $reseller->current_tier);

                /************************************
                 * 3. INVOICE GENERATION
                 ************************************/
                $today = date('Y-m-d');
                $invoiceGenerated = false;
                
                if ($reseller->next_due_date == $today) {
                    $invoiceId = $utils->generateResellerInvoice(
                        $reseller->userid,
                        $accountCount,
                        $newTier
                    );
                    
                    if ($invoiceId) {
                        $invoiceGenerated = true;
                        $stats['invoices_generated']++;
                        
                        // Update next due date (1 year from now)
                        $newDueDate = date('Y-m-d', strtotime('+1 year'));
                    }
                }

                /************************************
                 * 4. DATABASE UPDATES
                 ************************************/
                $updateData = [
                    'account_count' => $accountCount,
                    'last_sync' => Capsule::raw('NOW()')
                ];
                
                if ($tierChanged) {
                    $updateData['current_tier'] = $newTier;
                    $stats['tier_changes']++;
                }
                
                if ($invoiceGenerated) {
                    $updateData['last_invoice_date'] = $today;
                    $updateData['next_due_date'] = $newDueDate;
                }

                Capsule::table('mod_tiered_reseller')
                    ->where('id', $reseller->id)
                    ->update($updateData);

                /************************************
                 * 5. NOTIFICATIONS
                 ************************************/
                if ($config['enable_emails'] == 'on') {
                    // Tier change notification
                    if ($tierChanged) {
                        $utils->sendTierChangeNotification(
                            $reseller->userid,
                            $reseller->current_tier,
                            $newTier
                        );
                    }
                    
                    // Upcoming renewal (7-day notice)
                    if (date('Y-m-d', strtotime('+7 days')) == $reseller->next_due_date) {
                        $utils->sendRenewalReminder($reseller->userid);
                    }
                }

            } catch (Exception $e) {
                $stats['errors']++;
                logActivity("[Tiered Reseller] Error processing reseller #{$reseller->id}: " . $e->getMessage());
                continue;
            }
        }

        /************************************
         * FINAL REPORTING
         ************************************/
        $executionTime = round(microtime(true) - $startTime, 2);
        $report = sprintf(
            "Tiered Reseller Cron: %d resellers processed | %d invoices | %d tier changes | %d errors | %s sec",
            $stats['resellers_processed'],
            $stats['invoices_generated'],
            $stats['tier_changes'],
            $stats['errors'],
            $executionTime
        );
        
        logActivity($report);

        // Store last run time
        Capsule::table('tbladdonmodules')
            ->updateOrInsert(
                ['module' => 'tiered_reseller', 'setting' => 'last_cron_run'],
                ['value' => date('Y-m-d H:i:s')]
            );

    } catch (Exception $mainError) {
        logActivity("[Tiered Reseller] CRITICAL CRON ERROR: " . $mainError->getMessage());
    }
}

/**
 * WHM API Test Function (Manual Trigger)
 */
function tiered_reseller_test_whm_api() {
    try {
        $whmAPI = new TieredReseller_WHMAPI();
        
        // Test with first reseller found
        $testReseller = Capsule::table('mod_tiered_reseller')
            ->whereNotNull('whm_username')
            ->first();
        
        if (!$testReseller) {
            return [
                'status' => 'error',
                'message' => 'No reseller accounts with WHM usernames found'
            ];
        }
        
        $count = $whmAPI->getAccountCount($testReseller->whm_username);
        
        return [
            'status' => 'success',
            'message' => "API connection successful. Reseller {$testReseller->whm_username} has {$count} accounts."
        ];
        
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'API test failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Quick Sync Function (Manual Trigger)
 */
function tiered_reseller_quick_sync($params) {
    try {
        $userId = $params['userid'] ?? 0;
        $whmAPI = new TieredReseller_WHMAPI();
        $utils = new TieredReseller_Utilities();
        
        $reseller = Capsule::table('mod_tiered_reseller')
            ->where('userid', $userId)
            ->first();
        
        if (!$reseller) {
            throw new Exception("Reseller not found");
        }
        
        $accountCount = $whmAPI->getAccountCount($reseller->whm_username);
        $newTier = $utils->calculateTier($accountCount);
        
        Capsule::table('mod_tiered_reseller')
            ->where('userid', $userId)
            ->update([
                'account_count' => $accountCount,
                'current_tier' => $newTier,
                'last_sync' => Capsule::raw('NOW()')
            ]);
            
        return [
            'success' => true,
            'account_count' => $accountCount,
            'tier' => $newTier
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Register cron hook (if not already registered in hooks.php)
if (!function_exists('tiered_reseller_register_hooks')) {
    add_hook('DailyCronJob', 1, 'tiered_reseller_daily_cron');
}
