<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetAudit;
use App\Models\AssetAuditDetail;
use App\Models\AssetCategory;
use App\Models\AssetDisposal;
use App\Models\AssetDocument;
use App\Models\AssetSubCategory;
use App\Models\AssetTransfer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptDetail;
use App\Models\Location;
use App\Models\MaintenanceHistory;
use App\Models\MaintenanceRequest;
use App\Models\Permission;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\RolePermission;
use App\Models\SoftwareInstallation;
use App\Models\SoftwareLicense;
use App\Models\SoftwareProduct;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ItamSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $faker = fake();

        // ---------------------------------------------------------------
        // Foundation: Departments, Locations, Vendors
        // ---------------------------------------------------------------
        $departments = collect([
            ['Engineering', 'ENG'],
            ['Human Resources', 'HR'],
            ['Finance', 'FIN'],
            ['Sales & Marketing', 'SAL'],
            ['Operations', 'OPS'],
        ])->map(function (array $d) {
            return Department::create([
                'department_name' => $d[0],
                'department_code' => $d[1],
                'status' => 'active',
            ]);
        });

        $locations = collect([
            ['Head Office', 'HQ', '12 Gulshan Avenue', 'Dhaka', 'Bangladesh'],
            ['Uttara Branch', 'UTT', '45 Uttara', 'Dhaka', 'Bangladesh'],
            ['Chittagong Hub', 'CTG', '8 Agrabad', 'Chittagong', 'Bangladesh'],
            ['Remote / WFH', 'REM', null, null, null],
        ])->map(function (array $l) {
            return Location::create([
                'location_name' => $l[0],
                'location_code' => $l[1],
                'address' => $l[2],
                'city' => $l[3],
                'country' => $l[4],
                'status' => 'active',
            ]);
        });

        $vendors = collect([
            ['Dell Technologies', 'Rahman Ali', 'bd-sales@dell.example', '+8801700000001', 'Dell Tower, Dhaka'],
            ['Apple Reseller Ltd', 'Nusrat Jahan', 'sales@applereseller.example', '+8801700000002', 'Banani, Dhaka'],
            ['HP Bangladesh', 'Karim Uddin', 'contact@hpbangladesh.example', '+8801700000003', 'Motijheel, Dhaka'],
            ['Microsoft Volume', 'Sultana Yesmin', 'vl@microsoft.example', '+8801700000004', 'Online'],
            ['Local IT Wholesale', 'Jamal Hossain', 'trade@localit.example', '+8801700000005', 'Elephant Road, Dhaka'],
        ])->map(function (array $v) {
            return Vendor::create([
                'vendor_name' => $v[0],
                'contact_person' => $v[1],
                'email' => $v[2],
                'phone' => $v[3],
                'address' => $v[4],
                'website' => 'https://'.Str::slug($v[0]).'.example',
                'status' => 'active',
            ]);
        });

        // ---------------------------------------------------------------
        // Employees
        // ---------------------------------------------------------------
        $employees = collect();
        for ($i = 1; $i <= 30; $i++) {
            $employees->push(Employee::create([
                'employee_code' => 'EMP-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'employee_name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'phone' => '+88017'.str_pad((string) $faker->numberBetween(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'designation' => $faker->jobTitle(),
                'department_id' => $departments->random()->id,
                'location_id' => $locations->random()->id,
                'joining_date' => $faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
                'status' => $faker->randomElement(['active', 'active', 'active', 'inactive']),
            ]));
        }

        // Assign a head of department (an employee) to each department now
        // that employees exist.
        $departments->each(function (Department $department) use ($employees) {
            $department->update([
                'head_of_department_id' => $employees->random()->id,
            ]);
        });

        // ---------------------------------------------------------------
        // Asset Categories & Sub Categories
        // ---------------------------------------------------------------
        $categoryDefs = [
            'Laptop' => ['Ultrabook', 'Business Laptop', 'Workstation'],
            'Desktop' => ['All-in-One', 'Tower PC'],
            'Monitor' => ['24 Inch', '27 Inch', 'Ultrawide'],
            'Network' => ['Router', 'Switch', 'Access Point'],
            'Printer' => ['Laser', 'Inkjet', 'Multifunction'],
            'Mobile' => ['Smartphone', 'Tablet'],
            'Server' => ['Rack Server', 'Blade Server'],
            'Furniture' => ['Desk', 'Chair'],
        ];

        $categories = collect();
        $subCategories = collect();
        foreach ($categoryDefs as $catName => $subs) {
            $category = AssetCategory::create([
                'category_name' => $catName,
                'description' => $catName.' assets used across the organization.',
                'status' => 'active',
            ]);
            $categories->push($category);

            foreach ($subs as $subName) {
                $subCategories->push(AssetSubCategory::create([
                    'category_id' => $category->id,
                    'sub_category_name' => $subName,
                    'description' => $subName.' under '.$catName.'.',
                    'status' => 'active',
                ]));
            }
        }

        // ---------------------------------------------------------------
        // Assets
        // ---------------------------------------------------------------
        $brands = ['Dell', 'HP', 'Apple', 'Lenovo', 'Cisco', 'Microsoft', 'Samsung', 'Logitech'];
        $statuses = ['In Stock', 'Assigned', 'Spare', 'Under Repair', 'Returned', 'Lost', 'Stolen', 'Damaged', 'Disposed', 'Scrapped', 'Donated', 'Awaiting Disposal'];
        $conditions = ['New', 'Excellent', 'Good', 'Fair', 'Poor', 'Faulty', 'Under Repair', 'Repaired', 'Damaged', 'Obsolete', 'End of Life (EOL)', 'Beyond Economic Repair (BER)', 'Scrapped', 'Disposed', 'Lost', 'Stolen', 'Retired'];

        $assets = collect();
        for ($i = 1; $i <= 40; $i++) {
            $category = $categories->random();
            $subCategory = $subCategories->where('category_id', $category->id)->random();
            $purchase = $faker->dateTimeBetween('-3 years', '-1 month');
            $warrantyEnd = (clone $purchase)->modify('+'.fake()->numberBetween(12, 36).' months');

            $assets->push(Asset::create([
                'asset_tag' => 'AST-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'asset_name' => $category->category_name.' '.$subCategory->sub_category_name.' #'.$i,
                'category_id' => $category->id,
                'sub_category_id' => $subCategory->id,
                'brand' => $faker->randomElement($brands),
                'model' => 'Model-'.fake()->bothify('??-###'),
                'serial_number' => strtoupper(Str::random(12)),
                'service_tag' => strtoupper(Str::random(8)),
                'purchase_date' => $purchase->format('Y-m-d'),
                'purchase_cost' => $faker->randomFloat(2, 15000, 350000),
                'vendor_id' => $vendors->random()->id,
                'warranty_start' => $purchase->format('Y-m-d'),
                'warranty_end' => $warrantyEnd->format('Y-m-d'),
                'location_id' => $locations->random()->id,
                'current_status' => $faker->randomElement($statuses),
                'condition_status' => $faker->randomElement($conditions),
                'depreciation_years' => fake()->numberBetween(3, 7),
                'remarks' => fake()->sentence(),
            ]));
        }

        // ---------------------------------------------------------------
        // Asset Assignments
        // ---------------------------------------------------------------
        $assignedAssets = $assets->where('current_status', 'Assigned')->take(15);
        foreach ($assignedAssets as $asset) {
            AssetAssignment::create([
                'asset_id' => $asset->id,
                'employee_id' => $employees->random()->id,
                'assigned_date' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                'expected_return_date' => $faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
                'returned_date' => null,
                'assigned_by' => User::inRandomOrder()->value('id'),
                'received_by' => $faker->name(),
                'assignment_note' => 'Standard assignment.',
                'status' => 'Assigned',
            ]);
        }

        // ---------------------------------------------------------------
        // Asset Transfers
        // ---------------------------------------------------------------
        for ($i = 0; $i < 6; $i++) {
            $from = $locations->random();
            $to = $locations->where('id', '!=', $from->id)->random();
            AssetTransfer::create([
                'asset_id' => $assets->random()->id,
                'from_location_id' => $from->id,
                'to_location_id' => $to->id,
                'transfer_date' => $faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                'approved_by' => User::inRandomOrder()->value('id'),
                'received_by' => User::inRandomOrder()->value('id'),
                'transfer_reason' => $faker->sentence(),
                'status' => $faker->randomElement(['pending', 'completed']),
            ]);
        }

        // ---------------------------------------------------------------
        // Procurement: Purchase Orders + Goods Receipts
        // ---------------------------------------------------------------
        for ($i = 1; $i <= 8; $i++) {
            $po = PurchaseOrder::create([
                'po_number' => 'PO-'.date('Y').'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'vendor_id' => $vendors->random()->id,
                'po_date' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                'expected_delivery_date' => $faker->dateTimeBetween('-6 months', '+2 months')->format('Y-m-d'),
                'total_amount' => 0,
                'approval_status' => $faker->randomElement(['Pending', 'Approved', 'Rejected']),
                'created_by' => 'Admin',
                'approved_by' => $faker->randomElement(['Admin', null]),
                'remarks' => $faker->sentence(),
            ]);

            $total = 0;
            $lineCount = fake()->numberBetween(1, 3);
            for ($j = 0; $j < $lineCount; $j++) {
                $qty = fake()->numberBetween(2, 20);
                $unit = $faker->randomFloat(2, 5000, 80000);
                $lineTotal = $qty * $unit;
                $total += $lineTotal;
                PurchaseOrderDetail::create([
                    'purchase_order_id' => $po->id,
                    'category_id' => $categories->random()->id,
                    'description' => $faker->sentence(4),
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'total_price' => $lineTotal,
                ]);
            }
            $po->update(['total_amount' => $total]);

            if ($po->approval_status === 'Approved') {
                $grn = GoodsReceipt::create([
                    'grn_number' => 'GRN-'.date('Y').'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'vendor_id' => $po->vendor_id,
                    'purchase_order_id' => $po->id,
                    'received_date' => $faker->dateTimeBetween($po->po_date, 'now')->format('Y-m-d'),
                    'received_by' => User::inRandomOrder()->value('id'),
                    'remarks' => 'Received in good condition.',
                ]);

                foreach ($assets->random(fake()->numberBetween(1, 3)) as $receivedAsset) {
                    GoodsReceiptDetail::create([
                        'goods_receipt_id' => $grn->id,
                        'asset_id' => $receivedAsset->id,
                        'serial_number' => strtoupper(Str::random(10)),
                    ]);
                }
            }
        }

        // ---------------------------------------------------------------
        // Software Products, Licenses & Installations
        // ---------------------------------------------------------------
        $softwareDefs = [
            ['Microsoft Office 365', '2021', 'Microsoft', 'Subscription'],
            ['Adobe Creative Cloud', '2023', 'Adobe', 'Subscription'],
            ['Autodesk AutoCAD', '2024', 'Autodesk', 'Perpetual'],
            ['Windows 11 Pro', '11', 'Microsoft', 'OEM'],
            ['Antivirus Pro', '2024', 'TrendMicro', 'Subscription'],
        ];
        $softwareProducts = collect();
        foreach ($softwareDefs as $s) {
            $softwareProducts->push(SoftwareProduct::create([
                'software_name' => $s[0],
                'version' => $s[1],
                'vendor' => $s[2],
                'license_type' => $s[3],
                'status' => 'active',
            ]));
        }

        $licenses = collect();
        for ($i = 0; $i < 12; $i++) {
            $product = $softwareProducts->random();
            $licenses->push(SoftwareLicense::create([
                'software_product_id' => $product->id,
                'license_key' => strtoupper(Str::random(5)).'-'.strtoupper(Str::random(5)).'-'.strtoupper(Str::random(5)).'-'.strtoupper(Str::random(5)),
                'purchase_date' => $faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
                'expiry_date' => $faker->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
                'quantity' => fake()->numberBetween(5, 100),
                'cost' => $faker->randomFloat(2, 5000, 500000),
                'vendor_id' => $vendors->random()->id,
                'status' => 'active',
            ]));
        }

        foreach ($licenses->random(10) as $license) {
            SoftwareInstallation::create([
                'license_id' => $license->id,
                'asset_id' => $assets->random()->id,
                'installed_date' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                'installed_by' => $faker->name(),
                'status' => 'active',
            ]);
        }

        // ---------------------------------------------------------------
        // Maintenance Requests & History
        // ---------------------------------------------------------------
        for ($i = 1; $i <= 12; $i++) {
            $request = MaintenanceRequest::create([
                'ticket_no' => 'MT-'.date('Y').'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'asset_id' => $assets->random()->id,
                'reported_by' => $employees->random()->employee_name,
                'issue_description' => $faker->sentence(8),
                'priority' => $faker->randomElement(['Low', 'Medium', 'High', 'Critical']),
                'status' => $faker->randomElement(['open', 'open', 'in_progress', 'resolved', 'closed']),
            ]);

            if (in_array($request->status, ['resolved', 'closed', 'in_progress'], true)) {
                MaintenanceHistory::create([
                    'maintenance_request_id' => $request->id,
                    'vendor_id' => $vendors->random()->id,
                    'repair_date' => $faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                    'repair_cost' => $faker->randomFloat(2, 500, 50000),
                    'resolution' => $faker->sentence(6),
                    'downtime_hours' => $faker->randomFloat(2, 1, 72),
                    'completed_by' => $faker->name(),
                ]);
            }
        }

        // ---------------------------------------------------------------
        // Asset Audits & Details
        // ---------------------------------------------------------------
        for ($i = 1; $i <= 4; $i++) {
            $audit = AssetAudit::create([
                'audit_date' => $faker->dateTimeBetween('-4 months', 'now')->format('Y-m-d'),
                'location_id' => $locations->random()->id,
                'auditor_name' => $faker->name(),
                'remarks' => 'Periodic physical verification.',
            ]);

            foreach ($assets->random(fake()->numberBetween(3, 8)) as $auditedAsset) {
                AssetAuditDetail::create([
                    'audit_id' => $audit->id,
                    'asset_id' => $auditedAsset->id,
                    'physical_status' => $faker->randomElement(['Found', 'Found', 'Found', 'Missing', 'Damaged', 'Replaced']),
                    'remarks' => $faker->sentence(),
                ]);
            }
        }

        // ---------------------------------------------------------------
        // Asset Disposals
        // ---------------------------------------------------------------
        $disposalAssets = $assets->where('condition_status', 'Damaged')->take(5);
        foreach ($disposalAssets as $asset) {
            AssetDisposal::create([
                'asset_id' => $asset->id,
                'disposal_date' => $faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                'book_value' => $faker->randomFloat(2, 1000, 50000),
                'sale_value' => $faker->randomFloat(2, 0, 30000),
                'disposal_reason' => $faker->randomElement(['Obsolete', 'Damaged', 'Lost', 'Sold', 'Scrapped']),
                'approved_by' => 'Admin',
                'remarks' => $faker->sentence(),
            ]);
            $asset->update(['current_status' => 'Disposed']);
        }

        // ---------------------------------------------------------------
        // Asset Documents
        // ---------------------------------------------------------------
        foreach ($assets->random(10) as $asset) {
            AssetDocument::create([
                'asset_id' => $asset->id,
                'document_type' => $faker->randomElement(['Invoice', 'Warranty', 'Manual', 'AMC', 'Insurance', 'Photo']),
                'file_name' => Str::slug($asset->asset_name).'.pdf',
                'file_path' => 'documents/'.Str::random(16).'.pdf',
                'uploaded_by' => 'Admin',
                'uploaded_at' => $faker->dateTimeBetween('-1 year', 'now'),
            ]);
        }

        // ---------------------------------------------------------------
        // Permissions, RolePermissions & Activity Logs (ITAM tables)
        // Note: roles/user_roles are owned by Tyro's built-in RBAC.
        // ---------------------------------------------------------------
        $permissionNames = [
            'asset.view', 'asset.create', 'asset.edit', 'asset.delete',
            'maintenance.manage', 'audit.manage', 'report.view',
            'task.view', 'task.manage',
            'residence.view', 'residence.manage',
            'user.manage', 'role.manage', 'privilege.manage',
            'invitation.manage', 'system.manage', 'checkpoint.manage',
            'database.backup', 'media.manage',
            'activity.view', 'audit.view',
        ];
        $permissions = collect();
        foreach ($permissionNames as $p) {
            $permissions->push(Permission::create(['permission_name' => $p]));
        }

        // Link permissions to an existing role via the shared `roles` table.
        $roleId = DB::table('roles')->value('id');
        if ($roleId) {
            foreach ($permissions as $permission) {
                RolePermission::create([
                    'role_id' => $roleId,
                    'permission_id' => $permission->id,
                ]);
            }
        }

        for ($i = 0; $i < 20; $i++) {
            ActivityLog::create([
                'user_id' => User::inRandomOrder()->value('id'),
                'module_name' => $faker->randomElement(['assets', 'asset_assignments', 'maintenance_requests', 'purchase_orders']),
                'record_id' => fake()->numberBetween(1, 40),
                'action' => $faker->randomElement(['created', 'updated', 'deleted', 'viewed']),
                'old_value' => null,
                'new_value' => json_encode(['sample' => $faker->word()]),
                'ip_address' => $faker->ipv4(),
                'created_at' => $faker->dateTimeBetween('-3 months', 'now'),
            ]);
        }
    }
}
