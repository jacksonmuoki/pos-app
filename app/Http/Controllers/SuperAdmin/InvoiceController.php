<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    /**
     * Display invoices with filters and summary statistics.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['tenant', 'subscription'])
            ->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('tenant_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('issue_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('issue_date', '<=', $request->to);
        }

        $invoices = $query->paginate(15)->withQueryString();

        $totalInvoices = Invoice::count();

        $totalBilled = Invoice::whereNotIn('status', ['cancelled'])
            ->sum('total');

        $totalCollected = InvoicePayment::where('status', 'successful')
            ->sum('amount');

        $totalOutstanding = Invoice::whereNotIn('status', ['paid', 'cancelled', 'draft'])
            ->sum('balance_due');

        $overdueCount = Invoice::whereNotIn('status', ['paid', 'cancelled', 'draft'])
            ->where('balance_due', '>', 0)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->count();

        return view('super.invoices.index', compact(
            'invoices',
            'totalInvoices',
            'totalBilled',
            'totalCollected',
            'totalOutstanding',
            'overdueCount'
        ));
    }

    /**
     * Show the invoice creation form.
     */
    public function create()
    {
        $tenants = Tenant::all()
            ->sortBy(fn ($tenant) => strtolower($tenant->name ?? $tenant->id))
            ->values();

        $subscriptions = Subscription::with('plan')
            ->whereIn('status', ['active', 'trial'])
            ->latest()
            ->get();

        return view('super.invoices.create', compact(
            'tenants',
            'subscriptions'
        ));
    }

    /**
     * Create an invoice and its line items.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => [
                'required',
                'string',
                'exists:tenants,id',
            ],

            'subscription_id' => [
                'nullable',
                'integer',
                'exists:subscriptions,id',
            ],

            'issue_date' => [
                'required',
                'date',
            ],

            'due_date' => [
                'nullable',
                'date',
                'after_or_equal:issue_date',
            ],

            'currency' => [
                'required',
                'string',
                'size:3',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.description' => [
                'required',
                'string',
                'max:255',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999999',
            ],

            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999',
            ],

            'items.*.discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'items.*.tax_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
        ]);

        if (!empty($data['subscription_id'])) {
            $subscription = Subscription::findOrFail(
                $data['subscription_id']
            );

            if ($subscription->tenant_id !== $data['tenant_id']) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'subscription_id' =>
                            'The selected subscription does not belong to this company.',
                    ]);
            }
        }

        // Calculate invoice totals.
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;
        $grandTotal = 0;

        foreach ($data['items'] as $index => $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discount = (float) ($item['discount_amount'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? 0);

            $gross = round($quantity * $unitPrice, 2);

            if ($discount > $gross) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "items.{$index}.discount_amount" =>
                            'An item discount cannot exceed its gross amount.',
                    ]);
            }

            $taxableAmount = round($gross - $discount, 2);
            $tax = round($taxableAmount * $taxRate / 100, 2);
            $lineTotal = round($taxableAmount + $tax, 2);

            $subtotal += $gross;
            $discountTotal += $discount;
            $taxTotal += $tax;
            $grandTotal += $lineTotal;
        }

        $subtotal = round($subtotal, 2);
        $discountTotal = round($discountTotal, 2);
        $taxTotal = round($taxTotal, 2);
        $grandTotal = round($grandTotal, 2);

        $invoice = DB::transaction(function () use (
            $data,
            $subtotal,
            $discountTotal,
            $taxTotal,
            $grandTotal
        ) {
            $invoice = Invoice::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'tenant_id' => $data['tenant_id'],
                'subscription_id' => $data['subscription_id'] ?? null,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discountTotal,
                'tax_amount' => $taxTotal,
                'total' => $grandTotal,
                'amount_paid' => 0,
                'balance_due' => $grandTotal,
                'currency' => strtoupper($data['currency']),
                'status' => 'issued',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $discount = (float) ($item['discount_amount'] ?? 0);
                $taxRate = (float) ($item['tax_rate'] ?? 0);

                $taxableAmount = round(
                    ($quantity * $unitPrice) - $discount,
                    2
                );

                $tax = round($taxableAmount * $taxRate / 100, 2);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discount,
                    'tax_rate' => $taxRate,
                    'line_total' => round($taxableAmount + $tax, 2),
                ]);
            }

            return $invoice;
        });

        ActivityLog::record('invoice.created', $invoice->tenant_id, [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'total' => $invoice->total,
        ]);

        return redirect()
            ->route('super.invoices.show', $invoice)
            ->with('status', 'Invoice created successfully.');
    }

    /**
     * Display invoice details.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load([
            'tenant',
            'subscription.plan',
            'items',
            'payments',
        ]);

        return view('super.invoices.show', compact('invoice'));
    }

    /**
     * Download a printable HTML copy of the invoice.
     * A PDF package can be added later for PDF generation.
     */
    public function download(Invoice $invoice)
    {
        $invoice->load(['tenant', 'subscription.plan', 'items', 'payments']);

        $html = view('super.invoices.print', compact('invoice'))->render();

        return response()->streamDownload(
            function () use ($html) {
                echo $html;
            },
            $invoice->invoice_number . '.html',
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    /**
     * Display the print-friendly invoice.
     */
    public function print(Invoice $invoice)
    {
        $invoice->load(['tenant', 'subscription.plan', 'items', 'payments']);

        return view('super.invoices.print', compact('invoice'));
    }

    /**
     * Record an administrator-confirmed payment.
     *
     * This is not a payment-provider callback.
     * Automated payments must only be recorded after provider verification.
     */
    public function markPaid(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'payment_method' => [
                'required',
                Rule::in([
                    'mpesa',
                    'bank_transfer',
                    'cash',
                    'card',
                    'other',
                ]),
            ],

            'payment_reference' => [
                'nullable',
                'string',
                'max:150',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $lockedInvoice = Invoice::whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedInvoice->status, ['draft', 'cancelled', 'paid'], true)) {
                abort(422, 'This invoice cannot receive another payment.');
            }

            $balance = round(
                (float) $lockedInvoice->total -
                (float) $lockedInvoice->amount_paid,
                2
            );

            $amount = round((float) $data['amount'], 2);

            if ($amount > $balance) {
                abort(422, 'Payment amount cannot exceed the outstanding balance.');
            }

            InvoicePayment::create([
                'invoice_id' => $lockedInvoice->id,
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_method' => $data['payment_method'],
                'amount' => $amount,
                'currency' => $lockedInvoice->currency,
                'status' => 'successful',
                'paid_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $lockedInvoice->amount_paid = round(
                (float) $lockedInvoice->amount_paid + $amount,
                2
            );

            $lockedInvoice->save();

            $lockedInvoice->updatePaymentStatus();
        });

        ActivityLog::record('invoice.payment_recorded', $invoice->tenant_id, [
            'invoice_id' => $invoice->id,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'payment_reference' => $data['payment_reference'] ?? null,
        ]);

        return back()->with('status', 'Payment recorded successfully.');
    }

    /**
     * Cancel an invoice that has not received payments.
     */
    public function cancel(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'reason' => [
                'required',
                'string',
                'max:500',
            ],
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $lockedInvoice = Invoice::whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedInvoice->status === 'cancelled') {
                abort(422, 'This invoice is already cancelled.');
            }

            if ((float) $lockedInvoice->amount_paid > 0) {
                abort(422, 'An invoice with recorded payments cannot be cancelled. A refund or credit note must be handled separately.');
            }

            $lockedInvoice->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'metadata' => array_merge(
                    $lockedInvoice->metadata ?? [],
                    ['cancellation_reason' => $data['reason']]
                ),
            ]);
        });

        ActivityLog::record('invoice.cancelled', $invoice->tenant_id, [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'reason' => $data['reason'],
        ]);

        return back()->with('status', 'Invoice cancelled successfully.');
    }

    /**
     * Generate a unique invoice number.
     */
    private function generateInvoiceNumber(): string
    {
        do {
            $number = 'INV-' . now()->format('Ymd') . '-' .
                strtoupper(Str::random(6));
        } while (Invoice::where('invoice_number', $number)->exists());

        return $number;
    }
}
