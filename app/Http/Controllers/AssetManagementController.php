<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAuditDetail;
use App\Models\AssetDisposal;
use App\Models\AssetDocument;
use App\Models\AssetTransfer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\MaintenanceRequest;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use HasinHayder\TyroDashboard\Support\DashboardRoute;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssetManagementController extends Controller
{
    public function __invoke(): View
    {
        $assetStatus = Asset::query()
            ->selectRaw('current_status, count(*) as total')
            ->groupBy('current_status')
            ->pluck('total', 'current_status');

        $stats = [
            'total_assets' => (int) Asset::count(),
            'available' => (int) ($assetStatus['In Stock'] ?? 0),
            'assigned' => (int) ($assetStatus['Assigned'] ?? 0),
            'in_repair' => (int) ($assetStatus['Under Repair'] ?? 0),
            'disposed' => (int) ($assetStatus['Disposed'] ?? 0),
            'employees' => (int) Employee::count(),
            'vendors' => (int) Vendor::count(),
            'departments' => (int) Department::count(),
            'locations' => (int) Location::count(),
            'open_maintenance' => (int) MaintenanceRequest::where('status', 'open')->count(),
            'pending_pos' => (int) PurchaseOrder::where('approval_status', 'Pending')->count(),
            'active_transfers' => (int) AssetTransfer::where('status', '!=', 'completed')->count(),
            'documents' => (int) AssetDocument::count(),
        ];

        $recentAssets = Asset::with(['category', 'location'])
            ->latest('id')
            ->limit(6)
            ->get();

        $expiringWarranty = Asset::whereNotNull('warranty_end')
            ->where('warranty_end', '>=', now()->startOfDay())
            ->where('warranty_end', '<=', now()->addDays(60))
            ->orderBy('warranty_end')
            ->limit(6)
            ->get();

        $openMaintenance = MaintenanceRequest::with('asset')
            ->where('status', 'open')
            ->latest('id')
            ->limit(6)
            ->get();

        $recentDisposals = AssetDisposal::with('asset')
            ->latest('id')
            ->limit(6)
            ->get();

        $needsAudit = Asset::whereNotIn('id', AssetAuditDetail::select('asset_id'))
            ->where('current_status', '!=', 'Disposed')
            ->count();

        $editUrl = static fn (string $resource, int $id): string => route(DashboardRoute::name('resources.edit'), ['resource' => $resource, 'id' => $id]);
        $indexUrl = static fn (string $resource): string => route(DashboardRoute::name('resources.index'), $resource);

        $recentAssetItems = $recentAssets->map(function (Asset $asset) use ($editUrl): array {
            return [
                'title' => $asset->asset_name,
                'subtitle' => $asset->asset_tag.' · '.($asset->category?->category_name ?? 'Uncategorized'),
                'url' => $editUrl('assets', $asset->id),
                'badge' => [
                    'text' => $asset->current_status,
                    'class' => match ($asset->current_status) {
                        'In Stock' => 'badge-success',
                        'Assigned' => 'badge-primary',
                        'Under Repair' => 'badge-warning',
                        'Disposed' => 'badge-danger',
                        default => 'badge-secondary',
                    },
                ],
            ];
        })->all();

        $warrantyItems = $expiringWarranty->map(function (Asset $asset) use ($editUrl): array {
            return [
                'title' => $asset->asset_name,
                'subtitle' => 'Warranty ends '.$asset->warranty_end?->format('M d, Y'),
                'url' => $editUrl('assets', $asset->id),
                'badge' => [
                    'text' => $asset->condition_status,
                    'class' => 'badge-warning',
                ],
            ];
        })->all();

        $maintenanceItems = $openMaintenance->map(function (MaintenanceRequest $request) use ($editUrl): array {
            return [
                'title' => $request->ticket_no,
                'subtitle' => ($request->asset?->asset_name ?? 'Asset').' · '.Str::title($request->priority),
                'url' => $editUrl('maintenance_requests', $request->id),
                'badge' => [
                    'text' => $request->priority,
                    'class' => match ($request->priority) {
                        'Critical' => 'badge-danger',
                        'High' => 'badge-warning',
                        'Medium' => 'badge-primary',
                        default => 'badge-secondary',
                    },
                ],
            ];
        })->all();

        $disposalItems = $recentDisposals->map(function (AssetDisposal $disposal) use ($editUrl): array {
            return [
                'title' => $disposal->asset?->asset_name ?? 'Asset',
                'subtitle' => 'Disposed '.($disposal->disposal_date?->format('M d, Y') ?? ''),
                'url' => $editUrl('asset_disposals', $disposal->id),
                'badge' => [
                    'text' => $disposal->disposal_reason ?? 'N/A',
                    'class' => 'badge-danger',
                ],
            ];
        })->all();

        return view('asset-management.index', [
            'stats' => $stats,
            'recentAssetItems' => $recentAssetItems,
            'warrantyItems' => $warrantyItems,
            'maintenanceItems' => $maintenanceItems,
            'disposalItems' => $disposalItems,
            'needsAudit' => (int) $needsAudit,
            'assetsIndexRoute' => $indexUrl('assets'),
            'maintenanceIndexRoute' => $indexUrl('maintenance_requests'),
            'disposalIndexRoute' => $indexUrl('asset_disposals'),
            'auditIndexRoute' => $indexUrl('asset_audits'),
        ]);
    }
}
