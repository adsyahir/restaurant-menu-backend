<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds one fully populated demo organization you can log in to and play with:
 * an owner + staff, a menu (categories, items with variants/add-ons), tables,
 * and a handful of orders across every status.
 *
 * Idempotent: keyed on stable columns (workspace slug, user email, item name),
 * so running it repeatedly updates rather than duplicates.
 *
 * Login: owner@warung.test  /  password
 */
class DemoWorkspaceSeeder extends Seeder
{
    use WithoutModelEvents;

    private const PASSWORD = 'password';

    public function run(): void
    {
        $owner = $this->seedOwner();
        $workspace = $this->seedWorkspace($owner);

        // Point the owner at this workspace so the SPA lands on it after login.
        $owner->forceFill(['current_workspace_id' => $workspace->id])->save();

        $staff = $this->seedStaff($workspace, $owner);
        $categories = $this->seedMenu($workspace);
        $tables = $this->seedTables($workspace);
        $this->seedOrders($workspace, $categories, $tables, $staff);
        $this->seedBilling($workspace);
        $this->seedSiblingWorkspaces($owner);

        $this->command?->info("Demo workspace ready: {$workspace->name} ({$workspace->slug})");
        $this->command?->info('Login: owner@warung.test / '.self::PASSWORD);
    }

    private function seedOwner(): User
    {
        return User::updateOrCreate(
            ['email' => 'owner@warung.test'],
            [
                'name' => 'Aisyah Rahman',
                'password' => self::PASSWORD,
                // Pro account: up to 5 restaurants, all writable.
                'plan' => 'pro',
                'subscription_status' => 'active',
                'trial_ends_at' => null,
                'renews_on' => Carbon::now()->addMonth()->toDateString(),
            ],
        );
    }

    private function seedWorkspace(User $owner): Workspace
    {
        return Workspace::updateOrCreate(
            ['slug' => 'warung-nusantara-demo'],
            [
                'name' => 'Warung Nusantara',
                'emoji' => '🍜',
                'cuisine' => 'Malaysian',
                'address' => 'Lot 12, Jalan Bukit Bintang',
                'postcode' => '55100',
                'city' => 'Kuala Lumpur',
                'state' => 'Wilayah Persekutuan Kuala Lumpur',
                'country_code' => 'MY',
                'currency' => 'MYR',
                'timezone' => 'Asia/Kuala_Lumpur',
                'plan' => 'pro',
                'subscription_status' => 'active',
                'renews_on' => Carbon::now()->addMonth()->toDateString(),
                'owner_id' => $owner->id,
            ],
        );
    }

    /**
     * Owner (admin) + waiters + kitchen, all attached to the workspace pivot.
     *
     * @return array<string, User> keyed by role for use when seeding orders
     */
    private function seedStaff(Workspace $workspace, User $owner): array
    {
        $people = [
            ['name' => 'Aisyah Rahman', 'email' => 'owner@warung.test', 'role' => 'admin', 'user' => $owner],
            ['name' => 'Farah Lim', 'email' => 'farah@warung.test', 'role' => 'waiter'],
            ['name' => 'Danish Tan', 'email' => 'danish@warung.test', 'role' => 'waiter'],
            ['name' => 'Hafiz Osman', 'email' => 'hafiz@warung.test', 'role' => 'kitchen'],
        ];

        $byRole = [];

        foreach ($people as $person) {
            $user = $person['user'] ?? User::updateOrCreate(
                ['email' => $person['email']],
                ['name' => $person['name'], 'password' => self::PASSWORD],
            );

            $workspace->members()->syncWithoutDetaching([
                $user->id => ['role' => $person['role'], 'is_active' => true],
            ]);

            $byRole[$person['role']] = $user;
        }

        return $byRole;
    }

    /**
     * Categories with their menu items.
     *
     * @return array<int, Category> the created categories (with menuItems loaded)
     */
    private function seedMenu(Workspace $workspace): array
    {
        $spiceVariants = [
            ['name' => 'Mild', 'priceModifier' => 0],
            ['name' => 'Spicy', 'priceModifier' => 0],
            ['name' => 'Extra Spicy', 'priceModifier' => 1.00],
        ];

        $menu = [
            'Appetizers' => [
                ['Roti Canai', 'Flaky flatbread with dhal & curry dip', 3.50, ['halal', 'vegetarian'], [], [['name' => 'Extra Curry', 'priceModifier' => 1.50]]],
                ['Satay Ayam (6)', 'Grilled chicken skewers, peanut sauce', 9.00, ['halal'], [], [['name' => 'Extra Peanut Sauce', 'priceModifier' => 2.00]]],
                ['Keropok Lekor', 'Fried fish crackers with chilli sauce', 6.00, ['halal', 'seafood'], [], []],
            ],
            'Rice & Noodles' => [
                ['Nasi Lemak Ayam', 'Coconut rice, fried chicken, sambal, egg', 12.00, ['halal', 'spicy'], $spiceVariants, [['name' => 'Extra Egg', 'priceModifier' => 2.00], ['name' => 'Extra Sambal', 'priceModifier' => 1.00]]],
                ['Mee Goreng Mamak', 'Spicy fried yellow noodles', 10.00, ['halal', 'spicy', 'vegetarian'], $spiceVariants, [['name' => 'Add Chicken', 'priceModifier' => 4.00]]],
                ['Char Kuey Teow', 'Wok-fried flat noodles, prawns, cockles', 13.50, ['halal', 'seafood'], [], []],
                ['Nasi Goreng Kampung', 'Village-style fried rice, anchovies', 11.00, ['halal', 'spicy'], $spiceVariants, [['name' => 'Fried Egg', 'priceModifier' => 2.00]]],
            ],
            'Grilled' => [
                ['Ikan Bakar', 'Grilled stingray in banana leaf, sambal', 22.00, ['halal', 'seafood', 'spicy'], [], []],
                ['Ayam Percik', 'Grilled chicken, spiced coconut glaze', 16.00, ['halal', 'spicy'], [['name' => 'Half', 'priceModifier' => 0], ['name' => 'Whole', 'priceModifier' => 14.00]], []],
            ],
            'Beverages' => [
                ['Teh Tarik', 'Pulled milk tea', 3.00, ['vegetarian'], [['name' => 'Hot', 'priceModifier' => 0], ['name' => 'Iced', 'priceModifier' => 0.50]], []],
                ['Milo Dinosaur', 'Iced Milo topped with Milo powder', 5.50, ['vegetarian'], [], []],
                ['Sirap Bandung', 'Rose syrup with milk', 4.00, ['vegetarian'], [], []],
                ['Kopi O', 'Black coffee', 2.50, ['vegan'], [['name' => 'Hot', 'priceModifier' => 0], ['name' => 'Iced', 'priceModifier' => 0.50]], []],
            ],
            'Desserts' => [
                ['Cendol', 'Shaved ice, coconut milk, palm sugar', 6.00, ['vegetarian', 'contains-nuts'], [], []],
                ['Pisang Goreng (5)', 'Crispy fried bananas', 5.00, ['halal', 'vegetarian'], [], [['name' => 'Chocolate Drizzle', 'priceModifier' => 1.50]]],
            ],
        ];

        $categories = [];
        $order = 1;

        foreach ($menu as $categoryName => $items) {
            $category = Category::updateOrCreate(
                ['workspace_id' => $workspace->id, 'name' => $categoryName],
                ['display_order' => $order++, 'is_active' => true],
            );

            foreach ($items as [$name, $description, $price, $tags, $variants, $addOns]) {
                MenuItem::updateOrCreate(
                    ['workspace_id' => $workspace->id, 'name' => $name],
                    [
                        'category_id' => $category->id,
                        'description' => $description,
                        'base_price' => $price,
                        'is_available' => true,
                        'dietary_tags' => $tags,
                        'variants' => $variants,
                        'add_ons' => $addOns,
                    ],
                );
            }

            $categories[] = $category->load('menuItems');
        }

        return $categories;
    }

    /**
     * @return array<int, RestaurantTable>
     */
    private function seedTables(Workspace $workspace): array
    {
        $layout = [
            ['T1', 2, 'available'],
            ['T2', 2, 'occupied'],
            ['T3', 4, 'available'],
            ['T4', 4, 'occupied'],
            ['T5', 4, 'needs_cleaning'],
            ['T6', 6, 'available'],
            ['T7', 6, 'occupied'],
            ['T8', 8, 'available'],
        ];

        $tables = [];

        foreach ($layout as [$label, $capacity, $status]) {
            $tables[] = RestaurantTable::updateOrCreate(
                ['workspace_id' => $workspace->id, 'label' => $label],
                ['seating_capacity' => $capacity, 'status' => $status],
            );
        }

        return $tables;
    }

    /**
     * A spread of orders across every status, each with real menu items.
     *
     * @param  array<int, Category>  $categories
     * @param  array<int, RestaurantTable>  $tables
     * @param  array<string, User>  $staff
     */
    private function seedOrders(Workspace $workspace, array $categories, array $tables, array $staff): void
    {
        // Flatten all menu items into a lookup by name for readable order lines.
        $items = collect($categories)->flatMap->menuItems->keyBy('name');

        $waiter = $staff['waiter'] ?? $staff['admin'];
        $tableByLabel = collect($tables)->keyBy('label');

        $orders = [
            [
                'number' => 'ORD-0001', 'table' => 'T2', 'status' => 'preparing', 'payment' => null, 'minutesAgo' => 8,
                'lines' => [['Nasi Lemak Ayam', 'Spicy', 2], ['Teh Tarik', 'Iced', 2]],
            ],
            [
                'number' => 'ORD-0002', 'table' => 'T4', 'status' => 'ready', 'payment' => null, 'minutesAgo' => 15,
                'lines' => [['Char Kuey Teow', null, 1], ['Mee Goreng Mamak', 'Extra Spicy', 1], ['Milo Dinosaur', null, 2]],
            ],
            [
                'number' => 'ORD-0003', 'table' => 'T7', 'status' => 'served', 'payment' => null, 'minutesAgo' => 25,
                'lines' => [['Ikan Bakar', null, 1], ['Nasi Goreng Kampung', 'Mild', 2], ['Sirap Bandung', null, 3]],
            ],
            [
                'number' => 'ORD-0004', 'table' => 'T1', 'status' => 'paid', 'payment' => 'card', 'minutesAgo' => 90,
                'lines' => [['Ayam Percik', 'Whole', 1], ['Kopi O', 'Hot', 2], ['Cendol', null, 2]],
            ],
            [
                'number' => 'ORD-0005', 'table' => 'T3', 'status' => 'placed', 'payment' => null, 'minutesAgo' => 2,
                'lines' => [['Roti Canai', null, 3], ['Satay Ayam (6)', null, 2], ['Teh Tarik', 'Hot', 3]],
            ],
            [
                'number' => 'ORD-0006', 'table' => 'T5', 'status' => 'cancelled', 'payment' => null, 'minutesAgo' => 45,
                'lines' => [['Pisang Goreng (5)', null, 1]],
            ],
        ];

        foreach ($orders as $spec) {
            $order = Order::updateOrCreate(
                ['workspace_id' => $workspace->id, 'order_number' => $spec['number']],
                [
                    'table_id' => $tableByLabel->get($spec['table'])?->id,
                    'created_by' => $waiter->id,
                    'status' => $spec['status'],
                    'payment_method' => $spec['payment'],
                    'notes' => null,
                    'placed_at' => Carbon::now()->subMinutes($spec['minutesAgo']),
                    // Totals filled in after lines are built.
                    'subtotal' => 0,
                    'total' => 0,
                ],
            );

            // Rebuild lines from scratch so reseeding stays consistent.
            $order->items()->delete();

            $subtotal = 0.0;

            foreach ($spec['lines'] as [$itemName, $variantLabel, $quantity]) {
                $menuItem = $items->get($itemName);

                if (! $menuItem) {
                    continue;
                }

                $unitPrice = (float) $menuItem->base_price + $this->variantModifier($menuItem, $variantLabel);
                $subtotal += $unitPrice * $quantity;

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'name' => $menuItem->name,
                    'variant_label' => $variantLabel,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'notes' => null,
                ]);
            }

            $order->update(['subtotal' => $subtotal, 'total' => $subtotal]);
        }
    }

    /**
     * A couple of extra restaurants the same owner belongs to, so the
     * workspace switcher has something to switch between.
     */
    private function seedSiblingWorkspaces(User $owner): void
    {
        $siblings = [
            ['Kopitiam Corner', 'kopitiam-corner-demo', '☕', 'Penang', 'Pulau Pinang', 'free'],
            ['Seri Melayu Bistro', 'seri-melayu-bistro-demo', '🍛', 'Johor Bahru', 'Johor', 'pro'],
        ];

        foreach ($siblings as [$name, $slug, $emoji, $city, $state, $plan]) {
            $workspace = Workspace::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'emoji' => $emoji,
                    'cuisine' => 'Malaysian',
                    'city' => $city,
                    'state' => $state,
                    'country_code' => 'MY',
                    'currency' => 'MYR',
                    'timezone' => 'Asia/Kuala_Lumpur',
                    'plan' => $plan,
                    'subscription_status' => 'active',
                    'renews_on' => Carbon::now()->addMonth()->toDateString(),
                    'owner_id' => $owner->id,
                ],
            );

            $workspace->members()->syncWithoutDetaching([
                $owner->id => ['role' => 'admin', 'is_active' => true],
            ]);

            // Give each restaurant its own card + billing history.
            $this->seedBilling($workspace);
        }
    }

    /**
     * A default card + a spread of past invoices across every status
     * (local demo data — no Stripe).
     */
    private function seedBilling(Workspace $workspace): void
    {
        PaymentMethod::updateOrCreate(
            ['workspace_id' => $workspace->id, 'last4' => '4242'],
            ['brand' => 'Visa', 'exp_month' => 8, 'exp_year' => 2029, 'is_default' => true],
        );

        // Pro plan billed monthly at RM149. A realistic history: the current
        // month is still due, one past charge failed, the rest paid.
        // Rebuild history from scratch so reseeding stays clean (drops any
        // invoices from an earlier numbering scheme).
        $workspace->invoices()->delete();

        $prefix = strtoupper(substr($workspace->slug, 0, 3));
        $invoices = [
            [0, 149.00, 'due'],     // this month — not yet paid
            [1, 149.00, 'paid'],
            [2, 149.00, 'paid'],
            [3, 149.00, 'failed'],  // card declined that cycle
            [4, 149.00, 'paid'],
            [5, 149.00, 'paid'],
        ];

        foreach ($invoices as [$monthsAgo, $amount, $status]) {
            $issued = Carbon::now()->subMonths($monthsAgo)->startOfMonth();
            $number = "INV-{$prefix}-".$issued->format('Ym');

            Invoice::updateOrCreate(
                ['workspace_id' => $workspace->id, 'number' => $number],
                [
                    'issued_on' => $issued->toDateString(),
                    'amount' => $amount,
                    'status' => $status,
                ],
            );
        }
    }

    /**
     * Price delta for a chosen variant, matched by name against the item's variants JSON.
     */
    private function variantModifier(MenuItem $menuItem, ?string $variantLabel): float
    {
        if ($variantLabel === null) {
            return 0.0;
        }

        foreach ($menuItem->variants ?? [] as $variant) {
            if (($variant['name'] ?? null) === $variantLabel) {
                return (float) ($variant['priceModifier'] ?? 0);
            }
        }

        return 0.0;
    }
}
