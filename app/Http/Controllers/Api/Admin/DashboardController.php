<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ==========================================
        // 📊 1. Monthly Sales & Orders (Bar/Area Chart)
        // ==========================================
        $monthlySales = Order::select(
            DB::raw('sum(grand_total) as total_sales'),
            DB::raw('count(id) as total_orders'),
            DB::raw("DATE_FORMAT(created_at,'%M') as month")
        )
        ->where('payment_status', 'paid')
        ->where('created_at', '>=', Carbon::now()->subMonths(6)) // Last 6 months
        ->groupBy('month')
        ->orderBy(DB::raw('MIN(created_at)'), 'ASC')
        ->get();

        // ==========================================
        // 🍩 2. Order Status Breakdown (Pie Chart)
        // ==========================================
        $orderStatus = Order::select('order_status', DB::raw('count(*) as count'))
            ->groupBy('order_status')
            ->get();

        // ==========================================
        // 🥧 3. Top Selling Categories (Donut Chart)
        // ==========================================
        // Logic: OrderItems table er sathe Product join kore Category ber kora
        $topCategories = OrderItem::join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('sum(order_items.quantity) as total_sold'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        // ==========================================
        // 📈 4. Customer Growth (Line Chart)
        // ==========================================
        $customerGrowth = User::role('customer') // Assuming Spatie role used
            ->select(
                DB::raw("DATE_FORMAT(created_at,'%M') as month"),
                DB::raw('count(*) as new_customers')
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->groupBy('month')
            ->orderBy(DB::raw('MIN(created_at)'), 'ASC')
            ->get();

        // ==========================================
        // 📊 5. Top 5 Selling Products (Horizontal Bar)
        // ==========================================
        $topProducts = OrderItem::select('product_name', DB::raw('sum(quantity) as total_sold'))
            ->groupBy('product_name')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();


        // ==========================================
        // 📦 Other Stats (Cards & Tables)
        // ==========================================

        // Low Stock
        $lowStockProducts = Product::where('stock', '<=', 5)
            ->select('id', 'name', 'stock', 'thumbnail')
            ->take(5)->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'stock' => $p->stock,
                    'thumbnail' => $p->thumbnail ? url('storage/' . $p->thumbnail) : null,
                ];
            });

        // Summary Cards
        $stats = [
            'total_orders' => Order::count(),
            'total_sales' => Order::where('payment_status', 'paid')->sum('grand_total'),
            'total_customers' => User::role('customer')->count(),
            'pending_orders' => Order::where('order_status', 'pending')->count(),
        ];

        return response()->json([
            'cards' => $stats,
            'charts' => [
                'monthly_sales' => $monthlySales,      // For Bar/Area Chart
                'order_status' => $orderStatus,        // For Pie Chart
                'top_categories' => $topCategories,    // For Donut Chart
                'customer_growth' => $customerGrowth,  // For Line Chart
                'top_products' => $topProducts,        // For Horizontal Bar Chart
            ],
            'tables' => [
                'low_stock' => $lowStockProducts
            ]
        ]);
    }
}
