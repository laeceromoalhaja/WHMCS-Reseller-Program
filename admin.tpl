<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
    .tier-badge { font-size: 0.8rem; }
    .tier-1 { background-color: #6c757d; }
    .tier-2 { background-color: #17a2b8; }
    .tier-3 { background-color: #28a745; }
    .sync-status { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
    .sync-success { background-color: #28a745; }
    .sync-warning { background-color: #ffc107; }
    .sync-danger { background-color: #dc3545; }
    .stat-card { transition: all 0.3s; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
</style>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="bi bi-people-fill"></i> Tiered Reseller Program</h2>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Last full sync: 
                <strong>{$lastSync|default:'Never'}</strong> | 
                <a href="#" class="btn btn-sm btn-outline-primary" onclick="runFullSync(); return false;">
                    <i class="bi bi-arrow-repeat"></i> Run Full Sync Now
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Active Resellers</h5>
                    <h2 class="mb-0">{$stats.total_resellers|default:0}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Total Accounts</h5>
                    <h2 class="mb-0">{$stats.total_accounts|default:0}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Upcoming Invoices</h5>
                    <h2 class="mb-0">{$stats.upcoming_invoices|default:0}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title">Sync Issues</h5>
                    <h2 class="mb-0">{$stats.sync_issues|default:0}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Reseller Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="bi bi-table"></i> Reseller Accounts</h4>
            <div>
                <div class="input-group">
                    <input type="text" id="resellerSearch" class="form-control" placeholder="Search...">
                    <button class="btn btn-outline-secondary" type="button" onclick="filterResellers()">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="resellersTable">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>WHM User</th>
                            <th class="text-center">Accounts</th>
                            <th class="text-center">Tier</th>
                            <th class="text-center">Sync Status</th>
                            <th class="text-center">Last Invoice</th>
                            <th class="text-center">Next Due</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $resellers as $reseller}
                        <tr data-userid="{$reseller.userid}">
                            <td>
                                <a href="clientssummary.php?userid={$reseller.userid}" target="_blank">
                                    {$reseller.clientname}
                                </a>
                            </td>
                            <td>{$reseller.whm_username|default:'Not Set'}</td>
                            <td class="text-center">{$reseller.account_count}</td>
                            <td class="text-center">
                                <span class="badge tier-badge tier-{$reseller.current_tier}">
                                    Tier {$reseller.current_tier}
                                </span>
                            </td>
                            <td class="text-center">
                                {if $reseller.last_sync}
                                    <span class="sync-status sync-success" title="Last sync: {$reseller.last_sync}"></span>
                                {else}
                                    <span class="sync-status sync-danger" title="Never synced"></span>
                                {/if}
                            </td>
                            <td class="text-center">
                                {if $reseller.last_invoice_date}
                                    {$reseller.last_invoice_date}<br>
                                    <small>${$reseller.last_invoice_amount}</small>
                                {else}
                                    <span class="text-muted">Never</span>
                                {/if}
                            </td>
                            <td class="text-center">
                                {if $reseller.next_due_date}
                                    {$reseller.next_due_date}
                                    {if $reseller.next_due_date == $smarty.now|date_format:'Y-m-d'}
                                        <span class="badge bg-danger">Due Today</span>
                                    {elseif $reseller.next_due_date|date_format:'Y-m-d' < $smarty.now|date_format:'Y-m-d'}
                                        <span class="badge bg-danger">Overdue</span>
                                    {/if}
                                {else}
                                    <span class="text-muted">Not Set</span>
                                {/if}
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" 
                                        onclick="syncReseller({$reseller.userid})" title="Sync Now">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-success" 
                                        onclick="previewInvoice({$reseller.userid})" title="Preview Invoice">
                                        <i class="bi bi-receipt"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-info" 
                                        onclick="editReseller({$reseller.userid})" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        {foreachelse}
                        <tr>
                            <td colspan="8" class="text-center text-muted">No reseller accounts found</td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="float-end">
                <button type="button" class="btn btn-primary" onclick="showAddResellerModal()">
                    <i class="bi bi-plus-circle"></i> Add Reseller
                </button>
            </div>
        </div>
    </div>

    <!-- Logs Section -->
    <div class="card mt-4">
        <div class="card-header">
            <h4 class="mb-0"><i class="bi bi-journal-text"></i> Recent Activity</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $recentLogs as $log}
                        <tr>
                            <td>{$log.created_at}</td>
                            <td>{$log.action}</td>
                            <td>{$log.details}</td>
                        </tr>
                        {foreachelse}
                        <tr>
                            <td colspan="3" class="text-center text-muted">No recent activity</td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Reseller Modal -->
<div class="modal fade" id="addResellerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Reseller</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addResellerForm">
                    <div class="mb-3">
                        <label class="form-label">Client</label>
                        <select class="form-select" name="userid" required>
                            <option value="">Select Client</option>
                            {foreach $clients as $client}
                            <option value="{$client.id}">{$client.firstname} {$client.lastname} (#{$client.id})</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">WHM Username</label>
                        <input type="text" class="form-control" name="whm_username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Initial Account Count</label>
                        <input type="number" class="form-control" name="account_count" value="0" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Next Billing Date</label>
                        <input type="date" class="form-control" name="next_due_date" 
                            value="{$smarty.now|date_format:'Y-m-d'}">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveReseller()">Save Reseller</button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Invoice Modal -->
<div class="modal fade" id="previewInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Invoice Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="invoicePreviewContent">
                <!-- Dynamic content loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="generateInvoiceNow()">Generate Invoice</button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize DataTables
$(document).ready(function() {
    $('#resellersTable').DataTable({
        pageLength: 25,
        order: [[0, 'asc']],
        dom: '<"top"f>rt<"bottom"lip><"clear">'
    });
});

// Filter function
function filterResellers() {
    const search = $('#resellerSearch').val().toLowerCase();
    $('#resellersTable tbody tr').each(function() {
        const rowText = $(this).text().toLowerCase();
        $(this).toggle(rowText.includes(search));
    });
}

// Show add reseller modal
function showAddResellerModal() {
    const modal = new bootstrap.Modal('#addResellerModal');
    modal.show();
}

// Save new reseller
function saveReseller() {
    const form = $('#addResellerForm');
    const data = form.serialize();
    
    $.post('{$modulelink}&action=save', data, function(response) {
        if (response.success) {
            toastr.success('Reseller added successfully');
            $('#addResellerModal').modal('hide');
            location.reload();
        } else {
            toastr.error(response.message || 'Error saving reseller');
        }
    }).fail(function() {
        toastr.error('Server error occurred');
    });
}

// Sync single reseller
function syncReseller(userid) {
    $('#resellersTable tr[data-userid="'+userid+'"]').addClass('table-warning');
    
    $.post('{$modulelink}&action=sync', {userid: userid}, function(response) {
        if (response.success) {
            toastr.success('Sync completed: ' + response.account_count + ' accounts');
            location.reload();
        } else {
            toastr.error(response.message || 'Sync failed');
            $('#resellersTable tr[data-userid="'+userid+'"]').removeClass('table-warning');
        }
    });
}

// Run full sync
function runFullSync() {
    if (!confirm('This will sync ALL resellers. Continue?')) return;
    
    $('.card-header button').prop('disabled', true).html('<i class="bi bi-arrow-repeat spin"></i> Syncing...');
    
    $.post('{$modulelink}&action=fullsync', function(response) {
        if (response.success) {
            toastr.success('Full sync completed');
            location.reload();
        } else {
            toastr.error(response.message || 'Sync failed');
            $('.card-header button').prop('disabled', false).html('<i class="bi bi-arrow-repeat"></i> Run Full Sync Now');
        }
    });
}

// Preview invoice
function previewInvoice(userid) {
    $('#invoicePreviewContent').html('<div class="text-center py-4"><i class="bi bi-arrow-repeat spin"></i> Loading...</div>');
    const modal = new bootstrap.Modal('#previewInvoiceModal');
    modal.show();
    
    $.get('{$modulelink}&action=preview&userid=' + userid, function(response) {
        $('#invoicePreviewContent').html(response);
    });
}

// Generate invoice
function generateInvoiceNow() {
    const userid = $('#invoicePreviewContent').data('userid');
    
    $.post('{$modulelink}&action=generate', {userid: userid}, function(response) {
        if (response.success) {
            toastr.success('Invoice generated successfully');
            $('#previewInvoiceModal').modal('hide');
            location.reload();
        } else {
            toastr.error(response.message || 'Error generating invoice');
        }
    });
}

// Edit reseller
function editReseller(userid) {
    // Implementation would go here
    alert('Edit functionality would be implemented here for reseller #' + userid);
}
</script>
