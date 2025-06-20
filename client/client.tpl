<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
    .reseller-card {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    .reseller-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    }
    .tier-badge {
        font-size: 0.9rem;
        padding: 8px 12px;
        border-radius: 20px;
    }
    .tier-1 { background-color: #6c757d; }
    .tier-2 { background-color: #17a2b8; }
    .tier-3 { background-color: #28a745; }
    .stat-value {
        font-size: 1.5rem;
        font-weight: 600;
        color: #2c3e50;
    }
    .progress {
        height: 10px;
        border-radius: 5px;
    }
    .progress-bar {
        background-color: #3498db;
    }
    .countdown {
        font-size: 0.9rem;
    }
    .next-billing-card {
        border-left: 4px solid #3498db;
    }
</style>

<div class="container">
    <!-- Dashboard Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="bi bi-person-badge me-2"></i>Reseller Dashboard
                </h2>
                <span class="text-muted">Last updated: {$lastUpdated|default:'Never'}</span>
            </div>
            <hr>
        </div>
    </div>

    <!-- Status Overview Cards -->
    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <!-- Accounts Card -->
        <div class="col">
            <div class="reseller-card card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">
                        <i class="bi bi-server me-2"></i>Hosted Accounts
                    </h5>
                    <div class="stat-value">{$accountCount|default:0}</div>
                    <div class="mt-2">
                        {if $accountCount > 0}
                            <div class="progress">
                                {assign var="percentage" value=($accountCount/$nextTierThreshold)*100}
                                {if $percentage > 100}
                                    {assign var="percentage" value=100}
                                {/if}
                                <div class="progress-bar" style="width: {$percentage}%"></div>
                            </div>
                            <div class="countdown mt-1">
                                {if $nextTierThreshold > 0 && $accountCount < $nextTierThreshold}
                                    {$nextTierThreshold - $accountCount} more for next tier
                                {elseif $accountCount >= $nextTierThreshold}
                                    Max tier reached
                                {/if}
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>

        <!-- Tier Card -->
        <div class="col">
            <div class="reseller-card card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">
                        <i class="bi bi-award me-2"></i>Current Tier
                    </h5>
                    <div class="my-3">
                        <span class="tier-badge tier-{$currentTier}">
                            Tier {$currentTier} - {$tierName|default:''}
                        </span>
                    </div>
                    <div>
                        <small class="text-muted">
                            {if $currentTier == 1}
                                ${$tier1Price}/account/year
                            {elseif $currentTier == 2}
                                ${$tier2Price}/account/year
                            {else}
                                ${$tier3Price}/account/year
                            {/if}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Billing Card -->
        <div class="col">
            <div class="reseller-card card h-100 next-billing-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-calendar-check me-2"></i>Next Billing Date
                    </h5>
                    <div class="my-3">
                        <div class="stat-value">
                            {$nextDueDate|date_format:"%B %e, %Y"}
                        </div>
                        <div class="countdown mt-1">
                            {if $nextDueDate}
                                {math equation="floor((x - y)/(24*60*60))" x=strtotime($nextDueDate) y=$smarty.now assign="daysLeft"}
                                {if $daysLeft > 0}
                                    ({$daysLeft} days remaining)
                                {elseif $daysLeft == 0}
                                    (Due today)
                                {else}
                                    ({abs($daysLeft)} days overdue)
                                {/if}
                            {/if}
                        </div>
                    </div>
                    {if $daysLeft <= 30 && $daysLeft >= 0}
                        <div class="alert alert-info p-2 mb-0">
                            <i class="bi bi-info-circle"></i> 
                            {if $daysLeft <= 7} 
                                Your annual billing will be processed soon
                            {else}
                                Next billing in {$daysLeft} days
                            {/if}
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Information Section -->
    <div class="row">
        <div class="col-12">
            <div class="card reseller-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Reseller Program Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <h6><i class="bi bi-list-check me-2"></i>Current Tier Benefits</h6>
                            <ul class="list-group list-group-flush mb-4">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Accounts in current tier</span>
                                    <span class="badge bg-primary rounded-pill">{$currentTierMinAccounts} - {$currentTierMaxAccounts|default:'Unlimited'}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Price per account</span>
                                    <span>
                                        {if $currentTier == 1}
                                            ${$tier1Price}/year
                                        {elseif $currentTier == 2}
                                            ${$tier2Price}/year
                                        {else}
                                            ${$tier3Price}/year
                                        {/if}
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Next tier threshold</span>
                                    <span>
                                        {if $nextTierThreshold}
                                            {$nextTierThreshold} accounts
                                        {else}
                                            Max tier reached
                                        {/if}
                                    </span>
                                </li>
                            </ul>

                            <h6><i class="bi bi-arrow-up-circle me-2"></i>Tier Advancement</h6>
                            <div class="alert alert-light">
                                {if $nextTierThreshold && $accountCount < $nextTierThreshold}
                                    You need <strong>{$nextTierThreshold - $accountCount}</strong> more accounts to reach Tier {$currentTier + 1}, which would reduce your per-account cost.
                                {elseif $accountCount >= $lastTierThreshold}
                                    You've reached the highest tier with the lowest per-account price!
                                {/if}
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <h6><i class="bi bi-receipt me-2"></i>Billing Information</h6>
                            <ul class="list-group list-group-flush mb-4">
                                {if $lastInvoiceDate}
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Last invoice</span>
                                    <span>{$lastInvoiceDate|date_format:"%b %e, %Y"} (${$lastInvoiceAmount})</span>
                                </li>
                                {/if}
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Next invoice estimate</span>
                                    <span>
                                        {if $accountCount > 0}
                                            ${$nextInvoiceEstimate|number_format:2}
                                        {else}
                                            $0.00
                                        {/if}
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Billing cycle</span>
                                    <span>Annual</span>
                                </li>
                            </ul>

                            <h6><i class="bi bi-question-circle me-2"></i>Need Help?</h6>
                            <div class="alert alert-light">
                                <p>For questions about your reseller account or billing, please <a href="submitticket.php">submit a ticket</a> to our support team.</p>
                                <div class="d-grid gap-2">
                                    <a href="clientarea.php?action=productdetails&id={$serviceId}" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-gear me-1"></i> Manage Service
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Last Sync Notice -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-secondary">
                <i class="bi bi-info-circle me-2"></i>
                Account counts are updated every 24 hours. Last sync: {$lastSync|default:'unknown'}.
                {if $lastSync}
                    {math equation="floor((x - y)/(60*60))" x=$smarty.now y=strtotime($lastSync) assign="hoursSinceSync"}
                    {if $hoursSinceSync > 24}
                        <span class="text-warning">(Sync may be delayed)</span>
                    {/if}
                {/if}
            </div>
        </div>
    </div>
</div>

<script>
// Initialize tooltips
$(document).ready(function(){
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Update the progress bar animation
    $('.progress-bar').each(function() {
        const width = $(this).attr('style').match(/\d+/)[0];
        $(this).css('width', '0').animate({
            width: width + '%'
        }, 800);
    });
});
</script>
