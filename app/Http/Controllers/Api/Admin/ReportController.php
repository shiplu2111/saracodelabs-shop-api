<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * 📊 1. Sales Report
     * Returns total sales amount grouped by Date/Month
     */
   /**
     * 📊 1. Sales Report (Revenue & Units Sold)
     */
    public function sales(Request $request)
    {
        // 1. Date Setup
        $currentStart = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $currentEnd   = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        // Previous Period Calculation
        $daysDiff = $currentStart->diffInDays($currentEnd) + 1;
        $prevStart = $currentStart->copy()->subDays($daysDiff);
        $prevEnd = $currentEnd->copy()->subDays($daysDiff);

        // 2. Fetch Metrics (Current vs Previous)
        $currentStats = $this->getSalesMetrics($currentStart, $currentEnd);
        $prevStats = $this->getSalesMetrics($prevStart, $prevEnd);

        // 3. Calculate Growth
        $revenueGrowth = $this->calculateGrowth($currentStats['revenue'], $prevStats['revenue']);
        $unitsGrowth = $this->calculateGrowth($currentStats['units_sold'], $prevStats['units_sold']); // 🔥 New
        $aovGrowth = $this->calculateGrowth($currentStats['aov'], $prevStats['aov']);
        // Margin removed, maybe replace with Discount or just keep 3 cards?
        // Let's use "Total Discount" as the 4th card instead of Margin
        $discountGrowth = $this->calculateGrowth($currentStats['discount'], $prevStats['discount']);

        // 4. Chart Data (Revenue vs Units Sold)
        $period = $request->period ?? 'daily';

        if ($period === 'daily') {
            $dateFormat = '%Y-%m-%d';
            $labelFormat = '%d %M';
        } else {
            $dateFormat = '%Y-%m';
            $labelFormat = '%M %Y';
        }

        // Query for Chart
        $chartData = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$currentStart, $currentEnd])
            ->where('orders.payment_status', 'paid')
            ->select(
                DB::raw("DATE_FORMAT(orders.created_at, '$labelFormat') as label"),
                DB::raw("DATE_FORMAT(orders.created_at, '$dateFormat') as date_value"),
                DB::raw('SUM(orders.grand_total) as revenue'), // Note: Usually grand_total is per order, be careful with join duplication.
                // Best approach with join is to sum item prices for revenue OR group by order first.
                // To keep it simple and correct, we will use separate queries or subqueries.
                // But here is a Safe Optimized way:
                DB::raw('SUM(order_items.quantity) as units_sold')
            )
            // Fix for revenue duplication in join: We should calculate revenue from items here
            // OR use a different approach. Let's use sum(order_items.total_price) for chart revenue accuracy
            ->groupBy(
                DB::raw("DATE_FORMAT(orders.created_at, '$dateFormat')"),
                DB::raw("DATE_FORMAT(orders.created_at, '$labelFormat')")
            )
            ->orderBy('date_value', 'ASC')
            ->get()
            // Map to fix revenue (since we need order grand total, but join duplicates it per item)
            // Actually, simpler to just sum(order_items.total_price) for the chart revenue line
            ->map(function($item) {
                return [
                    'label' => $item->label,
                    'date_value' => $item->date_value,
                    'revenue' => (float) $item->revenue, // This might be inflated due to join if we summed grand_total.
                    // Let's fix the Query below properly.
                    'units_sold' => (int) $item->units_sold
                ];
            });

        // 🔥 FIXING CHART QUERY (Join causes duplication of Order Total)
        // We will fetch Revenue and Units separately to be 100% accurate
        $chartData = $this->getChartDataSafe($currentStart, $currentEnd, $dateFormat, $labelFormat);

        return response()->json([
            'cards' => [
                'revenue' => [
                    'value' => $currentStats['revenue'],
                    'growth' => round($revenueGrowth, 1),
                    'label' => 'Total Revenue'
                ],
                'units_sold' => [ // 🔥 Replaced Profit
                    'value' => $currentStats['units_sold'],
                    'growth' => round($unitsGrowth, 1),
                    'label' => 'Units Sold'
                ],
                'aov' => [
                    'value' => $currentStats['aov'],
                    'growth' => round($aovGrowth, 1),
                    'label' => 'Avg. Order Value'
                ],
                'discount' => [ // 🔥 Replaced Margin
                    'value' => $currentStats['discount'],
                    'growth' => round($discountGrowth, 1),
                    'label' => 'Total Discount'
                ]
            ],
            'chart_data' => $chartData
        ]);
    }

    /**
     * 🧮 Helper: Get Sales Metrics (Units Sold & Discount)
     */
    private function getSalesMetrics($start, $end)
    {
        $orders = Order::whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->get();

        $revenue = $orders->sum('grand_total');
        $discount = $orders->sum('discount_amount'); // 🔥 New Metric
        $totalOrders = $orders->count();
        $aov = $totalOrders > 0 ? $revenue / $totalOrders : 0;

        // Calculate Units Sold (Need Join)
        $unitsSold = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.payment_status', 'paid')
            ->sum('order_items.quantity');

        return [
            'revenue'    => (float) $revenue,
            'units_sold' => (int) $unitsSold,
            'aov'        => (float) $aov,
            'discount'   => (float) $discount
        ];
    }

    /**
     * 📈 Helper: Chart Data (Safe Query)
     */
    private function getChartDataSafe($start, $end, $dateFormat, $labelFormat)
    {
        // 1. Get Revenue per Date
        $revenues = Order::whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->selectRaw("DATE_FORMAT(created_at, '$dateFormat') as date, SUM(grand_total) as revenue")
            ->groupBy('date')
            ->pluck('revenue', 'date');

        // 2. Get Units Sold per Date
        $units = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.payment_status', 'paid')
            ->selectRaw("DATE_FORMAT(orders.created_at, '$dateFormat') as date, SUM(order_items.quantity) as units")
            ->groupBy('date')
            ->pluck('units', 'date')
            ->toArray();

        // 3. Merge Data
        $dates = $revenues->keys()->merge(array_keys($units))->unique()->sort();

        $chartData = [];
        foreach ($dates as $date) {
            $formattedLabel = Carbon::parse($date)->format($labelFormat === '%d %M' ? 'd M' : 'M Y');

            $chartData[] = [
                'label' => $formattedLabel,
                'date_value' => $date,
                'revenue' => $revenues[$date] ?? 0,
                'units_sold' => (int) ($units[$date] ?? 0)
            ];
        }

        return array_values($chartData);
    }

    private function calculateGrowth($current, $prev)
    {
        if ($prev == 0) return $current > 0 ? 100 : 0;
        return (($current - $prev) / $prev) * 100;
    }

    /**
     * 📦 2. Orders Report
     * Returns breakdown of Order Status (Pending, Delivered, Cancelled)
     */
    /**
     * 📦 2. Orders Report (With Overview Bar Chart)
     */
    public function orders(Request $request)
    {
        // 1. Date Filter Setup (Default: Last 6 Months)
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->subMonths(6)->startOfMonth();
        $endDate   = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        // Base Query for Date Range
        $query = Order::whereBetween('created_at', [$startDate, $endDate]);

        // -----------------------------------------------------
        // 📊 KPI Summary Cards
        // -----------------------------------------------------
        $totalOrders = (clone $query)->count();

        // Assuming 'delivered' status means Completed
        $completed = (clone $query)->where('order_status', 'delivered')->count();
        $pending   = (clone $query)->where('order_status', 'pending')->count();
        $cancelled = (clone $query)->where('order_status', 'cancelled')->count();

        // -----------------------------------------------------
        // 📊 Chart Data: Orders Overview (Total vs Completed)
        // -----------------------------------------------------
        // Safe Formatting for Strict Mode
        $dateFormat = '%Y-%m';     // Grouping Key (2026-02)
        $labelFormat = '%M %Y';    // Display Label (February 2026)

        $ordersChart = (clone $query)
            ->selectRaw("
                DATE_FORMAT(created_at, '$labelFormat') as label,
                DATE_FORMAT(created_at, '$dateFormat') as date_value,
                COUNT(*) as total_orders,
                SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as completed_orders
            ")
            ->groupBy(
                DB::raw("DATE_FORMAT(created_at, '$dateFormat')"),
                DB::raw("DATE_FORMAT(created_at, '$labelFormat')")
            )
            ->orderBy('date_value', 'ASC')
            ->get();

        return response()->json([
            'summary' => [
                'total_orders' => $totalOrders,
                'completed'    => $completed,
                'pending'      => $pending,
                'cancelled'    => $cancelled,
            ],
            'chart_data' => $ordersChart
        ]);
    }

    /**
     * 💳 3. Payments Report
     * Returns breakdown by Payment Method (COD, SSLCommerz, Bkash)
     */
    /**
     * 💳 3. Payments Report (With Distribution Chart)
     */
    public function payments(Request $request)
    {
        // 1. Date Filter Setup
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->subMonths(1);
        $endDate   = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now();

        $query = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('payment_status', 'paid'); // Only count paid orders

        // -----------------------------------------------------
        // 📊 Summary Cards
        // -----------------------------------------------------
        $totalRevenue = (clone $query)->sum('grand_total');
        $totalTransactions = (clone $query)->count();
        $avgTransactionValue = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;

        // -----------------------------------------------------
        // 📈 Chart Data: Distribution by Method
        // -----------------------------------------------------
        $paymentDistribution = (clone $query)
            ->select(
                'payment_method',
                DB::raw('SUM(grand_total) as total_amount'),
                DB::raw('COUNT(*) as total_count')
            )
            ->groupBy('payment_method')
            ->get()
            ->map(function ($item) use ($totalRevenue) {
                // Calculate Percentage
                $percentage = $totalRevenue > 0
                    ? ($item->total_amount / $totalRevenue) * 100
                    : 0;

                return [
                    'method' => ucfirst($item->payment_method), // e.g., "Sslcommerz"
                    'value' => (float) $item->total_amount,
                    'count' => $item->total_count,
                    'percentage' => round($percentage, 2),
                    'fill' => $this->getPaymentColor($item->payment_method) // Helper for chart colors
                ];
            });

        return response()->json([
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_transactions' => $totalTransactions,
                'avg_transaction_value' => round($avgTransactionValue, 2),
            ],
            'chart_data' => $paymentDistribution
        ]);
    }

    /**
     * 🎨 Helper: Get Color for Payment Method (Optional)
     */
    private function getPaymentColor($method)
    {
        $colors = [
            'cod' => '#F59E0B',        // Orange
            'sslcommerz' => '#10B981', // Emerald
            'manual' => '#3B82F6',     // Blue
            'bkash' => '#E2136E',      // Pink (Bkash Brand Color)
            'nagad' => '#F6921E',      // Orange (Nagad Brand Color)
            'rocket' => '#8C3494',     // Purple (Rocket Brand Color)
        ];

        return $colors[$method] ?? '#6B7280'; // Default Gray
    }
    public function customers(Request $request)
    {
        // 1. Date & Period Setup
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->subMonths(6)->startOfMonth();
        $endDate   = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $period = $request->period ?? 'monthly';

        // Define Formats (Hardcoded safe strings)
        if ($period === 'daily') {
            $dateFormat = '%Y-%m-%d';  // 2026-02-01
            $labelFormat = '%d %M';    // 01 February
        } else {
            $dateFormat = '%Y-%m';     // 2026-02
            $labelFormat = '%M %Y';    // February 2026
        }

        // -----------------------------------------------------
        // 📊 KPI Metrics (Same as before)
        // -----------------------------------------------------
        $totalCustomers = User::role('customer')->count();

        $newCustomers = User::role('customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $customersWithOrders = Order::select('user_id', DB::raw('count(*) as total'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->get();

        $totalBuyingCustomers = $customersWithOrders->count();
        $repeatCustomers = $customersWithOrders->where('total', '>', 1)->count();

        $repeatRate = $totalBuyingCustomers > 0
            ? ($repeatCustomers / $totalBuyingCustomers) * 100
            : 0;

        $totalRevenue = Order::where('payment_status', 'paid')->sum('grand_total');
        $avgLTV = $totalBuyingCustomers > 0 ? $totalRevenue / $totalBuyingCustomers : 0;

        // -----------------------------------------------------
        // 📈 Chart Data (Fixed for Strict Mode)
        // -----------------------------------------------------
        $growthChart = User::role('customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            // 🔥 FIX: সরাসরি ভ্যারিয়েবল ইন্টারপোলেশন ব্যবহার করা হয়েছে (Binding ? বাদ দিয়ে)
            // এটি নিরাপদ কারণ $dateFormat আমরা if/else দিয়ে সেট করেছি।
            ->select(
                DB::raw("DATE_FORMAT(created_at, '$labelFormat') as label"),
                DB::raw("DATE_FORMAT(created_at, '$dateFormat') as date_value"),
                DB::raw('count(*) as new_customers')
            )
            ->groupBy(
                DB::raw("DATE_FORMAT(created_at, '$dateFormat')"),
                DB::raw("DATE_FORMAT(created_at, '$labelFormat')")
            )
            ->orderBy('date_value', 'ASC')
            ->get();

        return response()->json([
            'summary' => [
                'total_customers' => $totalCustomers,
                'new_this_period' => $newCustomers,
                'repeat_rate'     => round($repeatRate, 2) . '%',
                'avg_ltv'         => round($avgLTV, 2),
            ],
            'chart_data' => $growthChart
        ]);
    }

    /**
     * 👕 5. Products Report (With Growth Calculation)
     */
    public function products(Request $request)
    {
        // 1. Date Setup
        $currentStart = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $currentEnd   = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        // Calculate Previous Period (Same duration)
        $daysDiff = $currentStart->diffInDays($currentEnd) + 1;
        $prevStart = $currentStart->copy()->subDays($daysDiff);
        $prevEnd = $currentEnd->copy()->subDays($daysDiff);

        // 2. Fetch Current Data
        $currentData = $this->getProductStats($currentStart, $currentEnd);

        // 3. Fetch Previous Data (For Growth Calc)
        $prevData = $this->getProductStats($prevStart, $prevEnd)->keyBy('product_id');

        // 4. Merge & Calculate Growth
        $report = $currentData->map(function ($item) use ($prevData) {
            $prevItem = $prevData->get($item->product_id);

            $prevSold = $prevItem ? $prevItem->total_sold : 0;
            $prevRevenue = $prevItem ? $prevItem->revenue : 0;

            // Growth Calculation Logic
            $soldGrowth = $prevSold > 0
                ? (($item->total_sold - $prevSold) / $prevSold) * 100
                : ($item->total_sold > 0 ? 100 : 0);

            $revenueGrowth = $prevRevenue > 0
                ? (($item->revenue - $prevRevenue) / $prevRevenue) * 100
                : ($item->revenue > 0 ? 100 : 0);

            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'thumbnail' => $item->thumbnail ? url('storage/' . $item->thumbnail) : null,

                // Metrics
                'units_sold' => (int) $item->total_sold,
                'revenue' => (float) $item->revenue,

                // Growth Stats
                'units_sold_growth' => round($soldGrowth, 2), // Percentage
                'revenue_growth' => round($revenueGrowth, 2), // Percentage
            ];
        });

        // 5. Return Top Performing (Sorted by Revenue)
        return response()->json($report->sortByDesc('revenue')->values());
    }

    /**
     * 🛠 Helper: Get Product Stats Query
     */
    private function getProductStats($startDate, $endDate)
    {
        return OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id') // Join products for thumbnail/details
            // ->where('orders.payment_status', 'paid') // 🔥 Testing: Uncomment for production
            ->whereDate('orders.created_at', '>=', $startDate)
            ->whereDate('orders.created_at', '<=', $endDate)
            ->select(
                'order_items.product_id',
                'order_items.product_name',
                'products.thumbnail',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.total_price) as revenue')
            )
            ->groupBy('order_items.product_id', 'order_items.product_name', 'products.thumbnail')
            ->orderByDesc('revenue')
            ->get();
    }

    /**
     * 🛠 Helper: Apply Date Filter
     */
    private function filterByDate($query, $request)
    {
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        return $query;
    }
    public function export(Request $request)
    {
        $type = $request->type ?? 'sales';
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate   = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $fileName = $type . '_report_' . date('Y-m-d_H-i') . '.csv';

        return response()->streamDownload(function () use ($type, $startDate, $endDate) {
            $file = fopen('php://output', 'w');

            // ==========================================
            // 📦 1. ORDERS REPORT (General Order Info)
            // ==========================================
            if ($type === 'orders' || $type === 'sales') {
                // Header Row
                fputcsv($file, ['Order Date', 'Order No', 'Customer Name', 'Phone', 'Order Status', 'Payment Method', 'Payment Status', 'Items Qty', 'Total Amount']);

                Order::whereBetween('created_at', [$startDate, $endDate])
                    ->withCount('items')
                    ->chunk(500, function ($orders) use ($file) {
                        foreach ($orders as $order) {
                            fputcsv($file, [
                                $order->created_at->format('Y-m-d H:i'),
                                $order->order_number,
                                $order->customer_name,
                                $order->customer_phone,
                                ucfirst($order->order_status),
                                ucfirst($order->payment_method),
                                ucfirst($order->payment_status),
                                $order->items_count,
                                $order->grand_total
                            ]);
                        }
                    });
            }

            // ==========================================
            // 💳 2. PAYMENTS REPORT (Transaction Details)
            // ==========================================
            elseif ($type === 'payments') {
                // Header Row (Focus on Financials)
                fputcsv($file, ['Date', 'Order No', 'Customer', 'Method', 'Transaction ID', 'Payment Status', 'Amount']);

                Order::whereBetween('created_at', [$startDate, $endDate])
                    ->whereNotNull('payment_method') // Ensure payment method exists
                    ->chunk(500, function ($orders) use ($file) {
                        foreach ($orders as $order) {
                            fputcsv($file, [
                                $order->created_at->format('Y-m-d H:i'),
                                $order->order_number,
                                $order->customer_name,
                                ucfirst($order->payment_method), // Bkash, Nagad, SSL
                                $order->payment_id ?? 'N/A',     // Transaction ID from Gateway
                                ucfirst($order->payment_status), // Paid/Pending
                                $order->grand_total
                            ]);
                        }
                    });
            }

            // ==========================================
            // 👕 3. PRODUCTS REPORT (Top Selling)
            // ==========================================
            elseif ($type === 'products') {
                fputcsv($file, ['Product Name', 'Units Sold', 'Total Revenue']);

                $products = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->whereBetween('orders.created_at', [$startDate, $endDate])
                    ->where('orders.payment_status', 'paid')
                    ->select(
                        'order_items.product_name',
                        DB::raw('SUM(order_items.quantity) as total_sold'),
                        DB::raw('SUM(order_items.total_price) as revenue')
                    )
                    ->groupBy('order_items.product_name')
                    ->orderByDesc('total_sold')
                    ->get();

                foreach ($products as $p) {
                    fputcsv($file, [$p->product_name, $p->total_sold, $p->revenue]);
                }
            }

            // ==========================================
            // 👥 4. CUSTOMERS REPORT
            // ==========================================
            elseif ($type === 'customers') {
                fputcsv($file, ['Customer Name', 'Email', 'Phone', 'Total Orders', 'Total Spent']);

                $customers = Order::whereBetween('created_at', [$startDate, $endDate])
                    ->select(
                        'customer_name', 'customer_email', 'customer_phone',
                        DB::raw('COUNT(*) as total_orders'),
                        DB::raw('SUM(grand_total) as total_spent')
                    )
                    ->groupBy('customer_email', 'customer_name', 'customer_phone')
                    ->orderByDesc('total_spent')
                    ->get();

                foreach ($customers as $c) {
                    fputcsv($file, [$c->customer_name, $c->customer_email, $c->customer_phone, $c->total_orders, $c->total_spent]);
                }
            }

            fclose($file);
        }, $fileName, ["Content-Type" => "text/csv"]);
    }
}
