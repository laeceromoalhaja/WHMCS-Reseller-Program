<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
// Include the necessary WHMCS classes
use WHMCS\Database\Capsule;

/**
 * Module configuration
 */
function tiered_reseller_config() {
    return [
        'name' => 'Tiered Reseller Program',
        'description' => 'Automates tiered pricing for resellers based on hosted accounts',
        'version' => '2.0',
        'author' => 'LAECER ADMOLL',
        'fields' => [
            // WHM API Configuration
            'whm_server_ip' => [
                'FriendlyName' => 'WHM Server IP/Host',
                'Type' => 'text',
                'Default' => '127.0.0.1',
                'Description' => 'IP or hostname of your WHM server',
            ],
            'whm_api_token' => [
                'FriendlyName' => 'WHM API Token',
                'Type' => 'password',
                'Description' => '<a href="https://docs.whmcs.com/WHM_API_Tokens" target="_blank">Generate in WHM</a>',
            ],
            
            // Pricing Tiers
            'tier1_limit' => [
                'FriendlyName' => 'Tier 1 Max Accounts',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '150',
            ],
            'tier1_price' => [
                'FriendlyName' => 'Tier 1 Price',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '2.00',
                'Description' => 'Per account/year (1-150 accounts)',
            ],
            'tier2_limit' => [
                'FriendlyName' => 'Tier 2 Max Accounts',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '300',
            ],
            'tier2_price' => [
                'FriendlyName' => 'Tier 2 Price',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '1.50',
                'Description' => 'Per account/year (151-300 accounts)',
            ],
            'tier3_price' => [
                'FriendlyName' => 'Tier 3 Price',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '1.00',
                'Description' => 'Per account/year (301+ accounts)',
            ],
            
            // Fees
            'setup_fee' => [
                'FriendlyName' => 'Setup Fee',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '25.00',
            ],
            
            // Features
            'enable_emails' => [
                'FriendlyName' => 'Enable Email Notifications',
                'Type' => 'yesno',
                'Default' => 'on',
            ],
            'sync_frequency' => [
                'FriendlyName' => 'Sync Frequency',
                'Type' => 'dropdown',
                'Options' => 'Daily,Weekly,Monthly',
                'Default' => 'Daily',
            ],
        ],
    ];
}

/**
 * Module activation
 */
function tiered_reseller_activate() {
    try {
        // Drop existing table if it's incomplete (uncomment if needed)
        // Capsule::schema()->dropIfExists('mod_tiered_reseller');
        
        // Create or update the main table
        if (!Capsule::schema()->hasTable('mod_tiered_reseller')) {
            Capsule::schema()->create('mod_tiered_reseller', function ($table) {
                $table->increments('id');
                $table->integer('userid')->unsigned()->unique();
                $table->integer('serviceid')->unsigned()->nullable();
                $table->string('whm_username', 64);
                $table->integer('account_count')->default(0);
                $table->tinyInteger('current_tier')->default(1);
                $table->dateTime('last_sync')->nullable();
                $table->date('last_invoice_date')->nullable();
                $table->decimal('last_invoice_amount', 10, 2)->nullable();
                $table->date('next_due_date')->nullable();
                $table->boolean('setup_paid')->default(false);
                $table->timestamps();
            });
        } else {
            // Add missing columns one by one with try-catch
            try {
                if (!Capsule::schema()->hasColumn('mod_tiered_reseller', 'current_tier')) {
                    Capsule::schema()->table('mod_tiered_reseller', function ($table) {
                        $table->tinyInteger('current_tier')->default(1)->after('account_count');
                    });
                }
            } catch (Exception $e) {
                logActivity("Tiered Reseller: Error adding current_tier - " . $e->getMessage());
            }

            try {
                if (!Capsule::schema()->hasColumn('mod_tiered_reseller', 'last_sync')) {
                    Capsule::schema()->table('mod_tiered_reseller', function ($table) {
                        $table->dateTime('last_sync')->nullable()->after('current_tier');
                    });
                }
            } catch (Exception $e) {
                logActivity("Tiered Reseller: Error adding last_sync - " . $e->getMessage());
            }
            // Add similar blocks for other missing columns if needed
        }

        return [
            'status' => 'success',
            'description' => 'Module activated successfully with database updates'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Activation failed: ' . $e->getMessage()
        ];
    }
}



/**
 * Module deactivation
 */
function tiered_reseller_deactivate() {
    // Optionally remove cron task (commented out for safety)
    /*
    Capsule::table('tbladdoncrons')
        ->where('module', 'tiered_reseller')
        ->delete();
    */
    
    return [
        'status' => 'success',
        'description' => 'Module deactivated. Database tables preserved.'
    ];
}

/**
 * Admin area output
 */
function tiered_reseller_output($vars) {
    // Load WHMCS helper classes
    require_once __DIR__ . '/includes/Utilities.php';
    $utils = new TieredReseller_Utilities();

    try {
        // Get the last sync time (only non-null values)
        $lastSync = Capsule::table('mod_tiered_reseller')
            ->select('last_sync')
            ->whereNotNull('last_sync')
            ->orderBy('last_sync', 'desc')
            ->value('last_sync');

        // Get all resellers for the admin table
        $resellers = Capsule::table('mod_tiered_reseller')
            ->join('tblclients', 'tblclients.id', '=', 'mod_tiered_reseller.userid')
            ->select('mod_tiered_reseller.*', 'tblclients.firstname', 'tblclients.lastname')
            ->get();

        // Prepare data for the template
        $viewData = [
            'module_link' => $vars['modulelink'],
            'resellers' => $resellers,
            'lastSync' => $lastSync ?: 'Never', // Fallback if NULL
            'message' => $message ?? null
        ];

        // Render the admin template
        return include __DIR__ . '/admin/admin.tpl';

    } catch (Exception $e) {
        return '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}


/**
 * Client area output
 */
function tiered_reseller_clientarea($vars) {
    $userId = (int)$_SESSION['uid'];
    
    try {
        $resellerData = Capsule::table('mod_tiered_reseller')
            ->where('userid', $userId)
            ->first();
            
        if (!$resellerData) {
            return '<p>No active reseller account found.</p>';
        }
        
        // Prepare tier display names
        $tierNames = [
            1 => 'Standard (1-150 accounts)',
            2 => 'Premium (151-300 accounts)',
            3 => 'Enterprise (301+ accounts)'
        ];
        
        // Render template
        $viewData = [
            'account_count' => $resellerData->account_count,
            'tier' => $tierNames[$resellerData->current_tier] ?? 'Unknown',
            'next_due_date' => $resellerData->next_due_date,
            'reminder' => ($resellerData->next_due_date == date('Y-m-d', strtotime('+7 days'))) 
                ? 'Your annual charge will be processed in 7 days'
                : null
        ];
        
        $template = file_get_contents(__DIR__ . '/client/client.tpl');
        foreach ($viewData as $key => $value) {
            $template = str_replace('{$' . $key . '}', htmlspecialchars($value), $template);
        }
        return $template;
        
    } catch (Exception $e) {
        return '<div class="alert alert-danger">Error loading reseller data</div>';
    }
}
